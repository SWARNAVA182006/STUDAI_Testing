<?php

declare(strict_types=1);

namespace App\Services\JobBoard;

use App\Models\DiscoveredJob;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Indeed Publisher API Integration Service
 *
 * Connects to Indeed's Publisher/Job Search API for job discovery.
 * Requires an Indeed Publisher ID (free tier) or Indeed Apply API key (premium).
 *
 * @see https://developer.indeed.com/docs/job-search
 */
class IndeedPublisherService
{
    private const BASE_URL = 'https://api.indeed.com/ads/apisearch';
    private const CACHE_PREFIX = 'indeed_';
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_RESULTS_PER_PAGE = 25;

    private string $publisherId;
    private int $rateLimitPerMinute;

    public function __construct()
    {
        $this->publisherId = config('services.indeed.publisher_id', '');
        $this->rateLimitPerMinute = (int) config('services.indeed.rate_limit', 10);
    }

    /**
     * Search for jobs on Indeed.
     *
     * @param array $params Search parameters:
     *   - q: string (keywords / job title)
     *   - l: string (location)
     *   - radius: int (km)
     *   - jt: string (job type: fulltime, parttime, contract, internship, temporary)
     *   - sort: string (relevance|date)
     *   - fromage: int (max days since posted)
     *   - start: int (pagination offset)
     *   - limit: int (results per page, max 25)
     * @return array{jobs: array, total: int, page: int}
     */
    public function searchJobs(array $params): array
    {
        $this->validateConfiguration();

        $cacheKey = self::CACHE_PREFIX . 'search_' . md5(json_encode($params));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($params) {
            try {
                $this->checkRateLimit();

                $queryParams = $this->buildQueryParams($params);

                $response = Http::timeout(15)
                    ->retry(2, 1000)
                    ->get(self::BASE_URL, $queryParams);

                if (!$response->successful()) {
                    Log::error('Indeed API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return ['jobs' => [], 'total' => 0, 'page' => 0];
                }

                $data = $response->json();

                $jobs = array_map(
                    fn(array $result) => $this->normalizeJob($result),
                    $data['results'] ?? []
                );

                $this->incrementRateCounter();

                return [
                    'jobs' => $jobs,
                    'total' => $data['totalResults'] ?? 0,
                    'page' => (int) floor(($params['start'] ?? 0) / self::MAX_RESULTS_PER_PAGE),
                ];
            } catch (\Exception $e) {
                Log::error('Indeed job search failed', [
                    'params' => $params,
                    'error' => $e->getMessage(),
                ]);

                return ['jobs' => [], 'total' => 0, 'page' => 0];
            }
        });
    }

    /**
     * Import discovered jobs into the DiscoveredJob table.
     *
     * @param array $searchParams Indeed search parameters
     * @param int   $userId       The user requesting the import
     * @return int  Number of jobs imported
     */
    public function importJobs(array $searchParams, int $userId): int
    {
        $result = $this->searchJobs($searchParams);
        $imported = 0;

        foreach ($result['jobs'] as $job) {
            try {
                $company = Company::firstOrCreate(
                    ['name' => $job['company']],
                    [
                        'slug' => Str::slug($job['company']),
                        'is_verified' => false,
                    ]
                );

                DiscoveredJob::updateOrCreate(
                    ['external_id' => $job['external_id'], 'source' => 'indeed'],
                    [
                        'user_id' => $userId,
                        'company_id' => $company->id,
                        'title' => $job['title'],
                        'description' => $job['description'],
                        'location' => $job['location'],
                        'job_type' => $job['job_type'],
                        'url' => $job['url'],
                        'source' => 'indeed',
                        'external_id' => $job['external_id'],
                        'posted_at' => $job['posted_at'],
                        'discovered_at' => now(),
                        'metadata' => [
                            'indeed_key' => $job['external_id'],
                            'sponsored' => $job['sponsored'] ?? false,
                            'formatted_location' => $job['formatted_location'] ?? null,
                        ],
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                Log::warning('Indeed job import failed for individual job', [
                    'job_key' => $job['external_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Indeed job import completed', [
            'user_id' => $userId,
            'total_found' => $result['total'],
            'imported' => $imported,
        ]);

        return $imported;
    }

    /**
     * Get trending job titles for a given location.
     */
    public function getTrendingJobs(string $location, int $limit = 10): array
    {
        $cacheKey = self::CACHE_PREFIX . 'trending_' . md5($location);

        return Cache::remember($cacheKey, 86400, function () use ($location, $limit) {
            // Perform searches for popular categories
            $categories = ['software engineer', 'data scientist', 'product manager', 'devops', 'designer'];
            $trending = [];

            foreach ($categories as $category) {
                $result = $this->searchJobs([
                    'q' => $category,
                    'l' => $location,
                    'sort' => 'date',
                    'limit' => 5,
                ]);

                if ($result['total'] > 0) {
                    $trending[] = [
                        'category' => $category,
                        'total_listings' => $result['total'],
                        'sample_jobs' => array_slice($result['jobs'], 0, 3),
                    ];
                }
            }

            usort($trending, fn($a, $b) => $b['total_listings'] <=> $a['total_listings']);

            return array_slice($trending, 0, $limit);
        });
    }

    /**
     * Build query parameters for the Indeed API.
     */
    private function buildQueryParams(array $params): array
    {
        return [
            'publisher' => $this->publisherId,
            'q' => $params['q'] ?? '',
            'l' => $params['l'] ?? '',
            'radius' => $params['radius'] ?? 50,
            'jt' => $params['jt'] ?? '',
            'sort' => $params['sort'] ?? 'relevance',
            'fromage' => $params['fromage'] ?? 30,
            'start' => $params['start'] ?? 0,
            'limit' => min($params['limit'] ?? self::MAX_RESULTS_PER_PAGE, self::MAX_RESULTS_PER_PAGE),
            'format' => 'json',
            'v' => 2,
            'highlight' => 0,
            'latlong' => 1,
            'co' => $params['country'] ?? 'in',
            'userip' => request()->ip() ?? '1.2.3.4',
            'useragent' => request()->userAgent() ?? 'StudAI-Career/2.0',
        ];
    }

    /**
     * Normalize an Indeed API result into a consistent DiscoveredJob-compatible format.
     */
    private function normalizeJob(array $result): array
    {
        return [
            'external_id' => $result['jobkey'] ?? Str::uuid()->toString(),
            'title' => $result['jobtitle'] ?? 'Unknown Position',
            'company' => $result['company'] ?? 'Unknown Company',
            'location' => $result['formattedLocation'] ?? $result['city'] ?? '',
            'formatted_location' => $result['formattedLocation'] ?? null,
            'description' => strip_tags($result['snippet'] ?? ''),
            'url' => $result['url'] ?? '',
            'job_type' => $this->mapJobType($result['jobtype'] ?? ''),
            'posted_at' => isset($result['date']) ? Carbon::parse($result['date']) : now(),
            'sponsored' => $result['sponsored'] ?? false,
            'latitude' => $result['latitude'] ?? null,
            'longitude' => $result['longitude'] ?? null,
            'source' => 'indeed',
        ];
    }

    /**
     * Map Indeed job type to application job type.
     */
    private function mapJobType(string $indeedType): string
    {
        return match (strtolower($indeedType)) {
            'fulltime' => 'full_time',
            'parttime' => 'part_time',
            'contract' => 'contract',
            'internship' => 'internship',
            'temporary' => 'temporary',
            default => 'full_time',
        };
    }

    /**
     * Validate that the required configuration is present.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->publisherId)) {
            throw new \RuntimeException(
                'Indeed Publisher ID not configured. Set INDEED_PUBLISHER_ID in .env'
            );
        }
    }

    /**
     * Check rate limit before making an API call.
     */
    private function checkRateLimit(): void
    {
        $key = self::CACHE_PREFIX . 'rate_' . now()->format('Y-m-d-H-i');
        $currentCount = (int) Cache::get($key, 0);

        if ($currentCount >= $this->rateLimitPerMinute) {
            throw new \RuntimeException('Indeed API rate limit exceeded. Please try again later.');
        }
    }

    /**
     * Increment the rate limit counter.
     */
    private function incrementRateCounter(): void
    {
        $key = self::CACHE_PREFIX . 'rate_' . now()->format('Y-m-d-H-i');
        Cache::increment($key);
        Cache::put($key, (int) Cache::get($key, 1), 120); // TTL = 2 minutes
    }
}
