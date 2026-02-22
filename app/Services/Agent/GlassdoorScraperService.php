<?php

namespace App\Services\Agent;

use App\Models\JobSource;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Glassdoor Job Scraper Service
 *
 * @deprecated since 2026-02-06. This scraper returns demo data only and should not
 *             be used in production. Use RSSJobFeedService instead for real job data.
 *             Glassdoor has strict anti-scraping measures and requires API partnership.
 *
 * @see \App\Services\Agent\RSSJobFeedService for production job discovery
 */
class GlassdoorScraperService
{
    protected string $baseUrl = 'https://www.glassdoor.com/Job/jobs.htm';
    protected int $timeout = 30;
    protected int $maxRetries = 3;
    protected int $rateLimitPerHour = 50; // Glassdoor is stricter

    /**
     * Scrape jobs from Glassdoor
     *
     * @param array $params Search parameters
     * @return array Discovered jobs
     */
    public function scrape(array $params = []): array
    {
        try {
            // Check rate limit
            if ($this->isRateLimited()) {
                Log::warning("Glassdoor scraper: Rate limit exceeded");
                return [];
            }

            $jobSource = JobSource::firstOrCreate(
                ['name' => 'Glassdoor'],
                [
                    'type' => 'job_board',
                    'url' => $this->baseUrl,
                    'is_active' => true,
                    'priority' => 7,
                ]
            );

            $jobs = $this->fetchJobs($params);
            
            foreach ($jobs as $jobData) {
                $this->storeJob($jobSource, $jobData);
            }

            $jobSource->incrementJobsFound();
            $jobSource->updateSuccessRate(true);

            Log::info("Glassdoor scraper: Found " . count($jobs) . " jobs");

            return $jobs;

        } catch (\Exception $e) {
            Log::error("Glassdoor scraper failed: " . $e->getMessage());
            
            if (isset($jobSource)) {
                $jobSource->updateSuccessRate(false);
            }

            return [];
        }
    }

    /**
     * Fetch jobs from Glassdoor
     *
     * @param array $params
     * @return array
     */
    protected function fetchJobs(array $params): array
    {
        $keywords = $params['keywords'] ?? 'software engineer';
        $location = $params['location'] ?? '';
        $experienceLevel = $params['experience_level'] ?? '';

        // Build query parameters
        $queryParams = [
            'sc.keyword' => $keywords,
            'locT' => 'C', // City
            'locId' => $this->getLocationId($location),
            'jobType' => $params['job_type'] ?? '',
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($this->baseUrl, $queryParams);

            if (!$response->successful()) {
                throw new \Exception("HTTP " . $response->status());
            }

            // Increment rate limit counter
            $this->incrementRateLimit();

            return $this->parseJobListings($response->body());

        } catch (\Exception $e) {
            Log::error("Failed to fetch Glassdoor jobs: " . $e->getMessage());
            return $this->getDemoJobs($keywords, $location);
        }
    }

    /**
     * Parse job listings from HTML
     *
     * @param string $html
     * @return array
     */
    protected function parseJobListings(string $html): array
    {
        $crawler = new Crawler($html);
        $jobs = [];

        // Glassdoor job card selectors
        $crawler->filter('li[data-id], .react-job-listing')->each(function (Crawler $node) use (&$jobs) {
            try {
                $jobData = $this->extractJobFromNode($node);
                if ($jobData && !empty($jobData['url'])) {
                    $jobs[] = $jobData;
                }
            } catch (\Exception $e) {
                Log::debug("Failed to parse Glassdoor job node: " . $e->getMessage());
            }
        });

        return $jobs;
    }

    /**
     * Extract job data from HTML node
     *
     * @param Crawler $node
     * @return array|null
     */
    protected function extractJobFromNode(Crawler $node): ?array
    {
        try {
            $titleNode = $node->filter('a.job-title, .jobTitle');
            $companyNode = $node->filter('.employerName, .employer');
            $locationNode = $node->filter('.location, .loc');
            $salaryNode = $node->filter('.salaryText, .salary-estimate');
            $ratingNode = $node->filter('.rating, .employer-rating');

            $title = $titleNode->count() ? $titleNode->text() : null;
            $company = $companyNode->count() ? $companyNode->text() : null;
            $location = $locationNode->count() ? $locationNode->text() : null;
            $salaryText = $salaryNode->count() ? $salaryNode->text() : null;
            $rating = $ratingNode->count() ? floatval($ratingNode->text()) : null;

            // Get job URL
            $link = $titleNode->count() ? $titleNode->attr('href') : null;
            if ($link && !str_starts_with($link, 'http')) {
                $link = 'https://www.glassdoor.com' . $link;
            }

            if (!$title || !$company || !$link) {
                return null;
            }

            // Extract job ID from data attribute or URL
            $jobId = $node->attr('data-id');
            if (!$jobId) {
                preg_match('/jobListingId=(\d+)/', $link, $matches);
                $jobId = $matches[1] ?? null;
            }

            // Parse salary if present
            $salary = $salaryText ? $this->parseSalary($salaryText) : null;

            return [
                'external_id' => $jobId ? "glassdoor_{$jobId}" : "glassdoor_" . md5($link),
                'url' => $link,
                'title' => trim($title),
                'company_name' => trim($company),
                'location' => trim($location),
                'salary_min' => $salary['min'] ?? null,
                'salary_max' => $salary['max'] ?? null,
                'salary_period' => $salary['period'] ?? null,
                'is_remote' => stripos($location, 'remote') !== false,
                'company_rating' => $rating,
                'posted_at' => now()->subDays(rand(1, 7)),
            ];

        } catch (\Exception $e) {
            Log::debug("Failed to extract job from Glassdoor node: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store job in database
     *
     * @param JobSource $jobSource
     * @param array $jobData
     * @return DiscoveredJob|null
     */
    protected function storeJob(JobSource $jobSource, array $jobData): ?DiscoveredJob
    {
        try {
            $existing = DiscoveredJob::where('url', $jobData['url'])->first();
            
            if ($existing) {
                return $existing;
            }

            // Fetch full job details
            $details = $this->fetchJobDetails($jobData['url']);

            $job = DiscoveredJob::create([
                'job_source_id' => $jobSource->id,
                'external_id' => $jobData['external_id'],
                'url' => $jobData['url'],
                'title' => $jobData['title'],
                'company_name' => $jobData['company_name'],
                'description' => $details['description'] ?? '',
                'extracted_skills' => $details['skills'] ?? [],
                'location' => $jobData['location'],
                'is_remote' => $jobData['is_remote'],
                'salary_min' => $jobData['salary_min'],
                'salary_max' => $jobData['salary_max'],
                'salary_period' => $jobData['salary_period'],
                'posted_at' => $jobData['posted_at'] ?? now(),
                'metadata' => [
                    'company_rating' => $jobData['company_rating'],
                ],
            ]);

            return $job;

        } catch (\Exception $e) {
            Log::error("Failed to store Glassdoor job: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch detailed job information
     *
     * @param string $url
     * @return array
     */
    public function fetchJobDetails(string $url): array
    {
        try {
            if ($this->isRateLimited()) {
                return [];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $this->incrementRateLimit();

            $crawler = new Crawler($response->body());

            $description = $crawler->filter('.desc, .jobDescriptionContent')->html('');
            
            return [
                'description' => strip_tags($description),
                'skills' => $this->extractSkills($description),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch Glassdoor job details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse salary from text
     *
     * @param string $text
     * @return array|null
     */
    protected function parseSalary(string $text): ?array
    {
        // Glassdoor format: "$100K - $150K (Glassdoor est.)"
        // Or: "$50 - $75 Per Hour"
        
        $pattern = '/\$?([\d,]+)k?\s*-\s*\$?([\d,]+)k?/i';
        
        if (preg_match($pattern, $text, $matches)) {
            $min = intval(str_replace(',', '', $matches[1]));
            $max = intval(str_replace(',', '', $matches[2]));

            // If values are in thousands (e.g., 100K)
            if (stripos($text, 'k') !== false && $min < 1000) {
                $min *= 1000;
                $max *= 1000;
            }

            // Detect period
            $period = 'year';
            if (stripos($text, 'hour') !== false) {
                $period = 'hour';
            } elseif (stripos($text, 'month') !== false) {
                $period = 'month';
            }

            return [
                'min' => $min,
                'max' => $max,
                'period' => $period,
            ];
        }

        return null;
    }

    /**
     * Extract skills from text
     *
     * @param string $text
     * @return array
     */
    protected function extractSkills(string $text): array
    {
        $skillKeywords = [
            'JavaScript', 'TypeScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go', 'Rust',
            'React', 'Angular', 'Vue', 'Svelte',
            'Node.js', 'Express', 'Django', 'Flask', 'Laravel', 'Spring', 'ASP.NET',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'CI/CD', 'Git',
            'REST API', 'GraphQL', 'Microservices',
            'Agile', 'Scrum', 'TDD', 'DevOps',
        ];

        $foundSkills = [];

        foreach ($skillKeywords as $skill) {
            if (stripos($text, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return array_unique($foundSkills);
    }

    /**
     * Get Glassdoor location ID (simplified)
     *
     * @param string $location
     * @return int
     */
    protected function getLocationId(string $location): int
    {
        // This would typically require a lookup table or API call
        // For demo purposes, return a default ID
        $locationMap = [
            'San Francisco' => 1147401,
            'New York' => 1132348,
            'Los Angeles' => 1146821,
            'Seattle' => 1150505,
            'Austin' => 1139761,
        ];

        foreach ($locationMap as $city => $id) {
            if (stripos($location, $city) !== false) {
                return $id;
            }
        }

        return 1; // Default
    }

    /**
     * Check if rate limited
     *
     * @return bool
     */
    protected function isRateLimited(): bool
    {
        $cacheKey = 'glassdoor_scraper_rate_limit';
        $count = Cache::get($cacheKey, 0);
        
        return $count >= $this->rateLimitPerHour;
    }

    /**
     * Increment rate limit counter
     */
    protected function incrementRateLimit(): void
    {
        $cacheKey = 'glassdoor_scraper_rate_limit';
        $ttl = 3600; // 1 hour
        
        $count = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, $ttl);
    }

    /**
     * Demo jobs for testing
     *
     * @param string $keywords
     * @param string $location
     * @return array
     */
    protected function getDemoJobs(string $keywords, string $location): array
    {
        return [
            [
                'external_id' => 'glassdoor_' . uniqid(),
                'url' => 'https://www.glassdoor.com/job-listing/software-engineer-' . uniqid() . '.htm',
                'title' => 'Backend Engineer',
                'company_name' => 'DataCorp',
                'description' => 'Seeking experienced Backend Engineer with Python, Django, PostgreSQL, and AWS expertise.',
                'location' => $location ?: 'New York, NY',
                'salary_min' => 110000,
                'salary_max' => 160000,
                'salary_period' => 'year',
                'is_remote' => str_contains($location, 'Remote'),
                'company_rating' => 4.2,
                'posted_at' => now()->subDays(rand(1, 5)),
            ],
            [
                'external_id' => 'glassdoor_' . uniqid(),
                'url' => 'https://www.glassdoor.com/job-listing/frontend-developer-' . uniqid() . '.htm',
                'title' => 'Frontend Developer',
                'company_name' => 'UI Innovators',
                'description' => 'Join our team as a Frontend Developer. React, TypeScript, and modern CSS required.',
                'location' => 'Remote',
                'salary_min' => 95000,
                'salary_max' => 135000,
                'salary_period' => 'year',
                'is_remote' => true,
                'company_rating' => 3.9,
                'posted_at' => now()->subDays(rand(1, 5)),
            ],
        ];
    }
}
