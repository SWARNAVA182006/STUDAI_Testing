<?php

declare(strict_types=1);

namespace App\Services\JobBoard;

use App\Models\Company;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * LinkedIn Partner / Jobs API Integration Service
 *
 * Integrates with LinkedIn's Partner APIs for job posting, candidate search,
 * and company data retrieval.
 *
 * Requires LinkedIn Developer Application with appropriate partner-level access:
 *   - Marketing Developer Platform (job postings)
 *   - Consumer Solutions Platform (profile data)
 *
 * @see https://learn.microsoft.com/en-us/linkedin/
 */
class LinkedInPartnerService
{
    private const API_BASE = 'https://api.linkedin.com/v2';
    private const JOBS_BASE = 'https://api.linkedin.com/v2/simpleJobPostings';
    private const CACHE_PREFIX = 'linkedin_';
    private const CACHE_TTL = 3600;

    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken;
    private string $organizationId;

    public function __construct()
    {
        $this->clientId = config('services.linkedin.client_id', '');
        $this->clientSecret = config('services.linkedin.client_secret', '');
        $this->accessToken = config('services.linkedin.access_token');
        $this->organizationId = config('services.linkedin.organization_id', '');
    }

    /**
     * Post a job to LinkedIn on behalf of a company.
     *
     * @param array $jobData Job posting data:
     *   - title: string
     *   - description: string
     *   - location: string
     *   - employment_type: string (FULL_TIME, PART_TIME, CONTRACT, INTERNSHIP)
     *   - experience_level: string (ENTRY_LEVEL, ASSOCIATE, MID_SENIOR, DIRECTOR, EXECUTIVE)
     *   - company_name: string
     *   - apply_url: string
     *   - skills: array
     * @return array{success: bool, linkedin_job_id: ?string, message: string}
     */
    public function postJob(array $jobData): array
    {
        $this->validateConfiguration();

        try {
            $payload = $this->buildJobPostPayload($jobData);

            $response = Http::withToken($this->getAccessToken())
                ->timeout(30)
                ->post(self::JOBS_BASE, $payload);

            if ($response->successful()) {
                $linkedinJobId = $response->header('X-RestLi-Id') ?? $response->json('id');

                Log::info('LinkedIn job posted successfully', [
                    'linkedin_job_id' => $linkedinJobId,
                    'title' => $jobData['title'],
                ]);

                return [
                    'success' => true,
                    'linkedin_job_id' => $linkedinJobId,
                    'message' => 'Job posted to LinkedIn successfully.',
                ];
            }

            Log::error('LinkedIn job posting failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'linkedin_job_id' => null,
                'message' => 'Failed to post job: ' . ($response->json('message') ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('LinkedIn job posting exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'linkedin_job_id' => null,
                'message' => 'LinkedIn API error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Close a job posting on LinkedIn.
     */
    public function closeJob(string $linkedinJobId): bool
    {
        $this->validateConfiguration();

        try {
            $response = Http::withToken($this->getAccessToken())
                ->timeout(15)
                ->delete(self::JOBS_BASE . "/{$linkedinJobId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('LinkedIn close job failed', [
                'job_id' => $linkedinJobId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetch company profile data from LinkedIn.
     *
     * @param string $companyName Company name or vanity URL
     * @return array Company data or empty array on failure
     */
    public function getCompanyProfile(string $companyName): array
    {
        $cacheKey = self::CACHE_PREFIX . 'company_' . md5($companyName);

        return Cache::remember($cacheKey, 86400, function () use ($companyName) {
            try {
                $response = Http::withToken($this->getAccessToken())
                    ->timeout(15)
                    ->get(self::API_BASE . '/organizations', [
                        'q' => 'vanityName',
                        'vanityName' => Str::slug($companyName),
                    ]);

                if ($response->successful()) {
                    $org = $response->json('elements.0');

                    if ($org) {
                        return [
                            'linkedin_id' => $org['id'] ?? null,
                            'name' => $org['localizedName'] ?? $companyName,
                            'description' => $org['localizedDescription'] ?? null,
                            'website' => $org['localizedWebsite'] ?? null,
                            'industry' => $org['industries'][0] ?? null,
                            'company_size' => $org['staffCountRange'] ?? null,
                            'headquarters' => $org['locations'][0] ?? null,
                            'founded_year' => $org['foundedOn']['year'] ?? null,
                            'specialties' => $org['specialties'] ?? [],
                            'logo_url' => $org['logoV2']['original'] ?? null,
                        ];
                    }
                }

                return [];
            } catch (\Exception $e) {
                Log::error('LinkedIn company profile fetch failed', [
                    'company' => $companyName,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Search for candidates matching criteria (requires Partner-level access).
     *
     * @param array $criteria Search criteria:
     *   - keywords: string
     *   - location: string
     *   - skills: array
     *   - experience_years: int
     *   - current_company: string
     * @return array
     */
    public function searchCandidates(array $criteria): array
    {
        $this->validateConfiguration();

        $cacheKey = self::CACHE_PREFIX . 'candidates_' . md5(json_encode($criteria));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($criteria) {
            try {
                $response = Http::withToken($this->getAccessToken())
                    ->timeout(30)
                    ->get(self::API_BASE . '/talentSearch', [
                        'q' => 'search',
                        'keywords' => $criteria['keywords'] ?? '',
                        'locationURN' => $criteria['location'] ?? '',
                        'count' => $criteria['limit'] ?? 20,
                    ]);

                if ($response->successful()) {
                    return array_map(
                        fn($element) => $this->normalizeCandidate($element),
                        $response->json('elements', [])
                    );
                }

                return [];
            } catch (\Exception $e) {
                Log::error('LinkedIn candidate search failed', [
                    'criteria' => $criteria,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Import jobs from LinkedIn job search into DiscoveredJob table.
     * Note: LinkedIn's public job search API is limited; this uses web scraping fallback.
     *
     * @param array $searchParams Search parameters
     * @param int   $userId       The user requesting the import
     * @return int  Number of jobs imported
     */
    public function importJobs(array $searchParams, int $userId): int
    {
        $cacheKey = self::CACHE_PREFIX . 'import_' . md5(json_encode($searchParams));

        $jobs = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($searchParams) {
            try {
                $response = Http::withToken($this->getAccessToken())
                    ->timeout(30)
                    ->get(self::API_BASE . '/jobSearch', [
                        'keywords' => $searchParams['keywords'] ?? '',
                        'location' => $searchParams['location'] ?? '',
                        'count' => $searchParams['limit'] ?? 25,
                    ]);

                if ($response->successful()) {
                    return $response->json('elements', []);
                }

                return [];
            } catch (\Exception $e) {
                Log::error('LinkedIn job import search failed', ['error' => $e->getMessage()]);
                return [];
            }
        });

        $imported = 0;

        foreach ($jobs as $job) {
            try {
                $companyName = $job['companyName'] ?? 'Unknown Company';
                $company = Company::firstOrCreate(
                    ['name' => $companyName],
                    [
                        'slug' => Str::slug($companyName),
                        'is_verified' => false,
                    ]
                );

                DiscoveredJob::updateOrCreate(
                    ['external_id' => $job['id'] ?? Str::uuid()->toString(), 'source' => 'linkedin'],
                    [
                        'user_id' => $userId,
                        'company_id' => $company->id,
                        'title' => $job['title'] ?? 'Position',
                        'description' => $job['description'] ?? '',
                        'location' => $job['location'] ?? '',
                        'job_type' => $this->mapEmploymentType($job['employmentType'] ?? ''),
                        'url' => $job['applyUrl'] ?? "https://www.linkedin.com/jobs/view/{$job['id']}",
                        'source' => 'linkedin',
                        'external_id' => $job['id'] ?? Str::uuid()->toString(),
                        'posted_at' => isset($job['listedAt']) ? Carbon::createFromTimestampMs($job['listedAt']) : now(),
                        'discovered_at' => now(),
                        'metadata' => [
                            'linkedin_job_id' => $job['id'] ?? null,
                            'experience_level' => $job['experienceLevel'] ?? null,
                            'industries' => $job['industries'] ?? [],
                        ],
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                Log::warning('LinkedIn job import failed for individual job', [
                    'job_id' => $job['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('LinkedIn job import completed', [
            'user_id' => $userId,
            'total_found' => count($jobs),
            'imported' => $imported,
        ]);

        return $imported;
    }

    /**
     * Build the job posting payload for LinkedIn API.
     */
    private function buildJobPostPayload(array $jobData): array
    {
        return [
            'externalJobPostingId' => Str::uuid()->toString(),
            'title' => $jobData['title'],
            'description' => [
                'text' => $jobData['description'],
            ],
            'integrationContext' => 'urn:li:organization:' . $this->organizationId,
            'listedAt' => now()->getTimestampMs(),
            'jobPostingOperationType' => 'CREATE',
            'location' => $jobData['location'],
            'employmentStatus' => $this->mapToLinkedInEmploymentType($jobData['employment_type'] ?? 'FULL_TIME'),
            'seniorityLevel' => $jobData['experience_level'] ?? 'MID_SENIOR_LEVEL',
            'companyApplyUrl' => $jobData['apply_url'] ?? null,
            'skills' => array_map(fn($skill) => ['skill' => $skill], $jobData['skills'] ?? []),
        ];
    }

    /**
     * Map LinkedIn employment type to application job type.
     */
    private function mapEmploymentType(string $linkedinType): string
    {
        return match (strtoupper($linkedinType)) {
            'FULL_TIME' => 'full_time',
            'PART_TIME' => 'part_time',
            'CONTRACT' => 'contract',
            'INTERNSHIP' => 'internship',
            'TEMPORARY' => 'temporary',
            'VOLUNTEER' => 'volunteer',
            default => 'full_time',
        };
    }

    /**
     * Map application employment type to LinkedIn format.
     */
    private function mapToLinkedInEmploymentType(string $type): string
    {
        return match (strtolower($type)) {
            'full_time', 'fulltime' => 'FULL_TIME',
            'part_time', 'parttime' => 'PART_TIME',
            'contract' => 'CONTRACT',
            'internship' => 'INTERNSHIP',
            'temporary' => 'TEMPORARY',
            default => 'FULL_TIME',
        };
    }

    /**
     * Normalize a LinkedIn candidate search result.
     */
    private function normalizeCandidate(array $element): array
    {
        return [
            'linkedin_id' => $element['person']['id'] ?? null,
            'name' => trim(($element['person']['firstName'] ?? '') . ' ' . ($element['person']['lastName'] ?? '')),
            'headline' => $element['person']['headline'] ?? null,
            'location' => $element['person']['location'] ?? null,
            'current_company' => $element['currentPositions'][0]['companyName'] ?? null,
            'current_title' => $element['currentPositions'][0]['title'] ?? null,
            'profile_url' => $element['person']['profileUrl'] ?? null,
        ];
    }

    /**
     * Get OAuth2 access token (refresh if needed).
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $cacheKey = self::CACHE_PREFIX . 'access_token';
        $cached = Cache::get($cacheKey);

        if ($cached) {
            $this->accessToken = $cached;
            return $cached;
        }

        // Exchange client credentials for access token
        $response = Http::asForm()
            ->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if ($response->successful()) {
            $token = $response->json('access_token');
            $expiresIn = $response->json('expires_in', 3600);

            Cache::put($cacheKey, $token, $expiresIn - 60);
            $this->accessToken = $token;

            return $token;
        }

        throw new \RuntimeException('Failed to obtain LinkedIn access token');
    }

    /**
     * Validate required configuration.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \RuntimeException(
                'LinkedIn API not configured. Set LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET in .env'
            );
        }
    }
}
