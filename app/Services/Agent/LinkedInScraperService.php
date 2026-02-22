<?php

namespace App\Services\Agent;

use App\Models\JobSource;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;

/**
 * LinkedIn Job Scraper Service
 *
 * @deprecated since 2026-02-06. This scraper returns demo data only and should not
 *             be used in production. Use RSSJobFeedService instead for real job data.
 *             LinkedIn requires API partnership for legitimate access.
 *
 * @see \App\Services\Agent\RSSJobFeedService for production job discovery
 */
class LinkedInScraperService
{
    protected string $baseUrl = 'https://www.linkedin.com/jobs/search';
    protected int $timeout = 30;
    protected int $maxRetries = 3;

    /**
     * Scrape jobs from LinkedIn
     *
     * @param array $params Search parameters
     * @return array Discovered jobs
     */
    public function scrape(array $params = []): array
    {
        try {
            $jobSource = JobSource::firstOrCreate(
                ['name' => 'LinkedIn'],
                [
                    'type' => 'job_board',
                    'url' => $this->baseUrl,
                    'is_active' => true,
                    'priority' => 9,
                ]
            );

            $jobs = $this->fetchJobs($params);
            
            foreach ($jobs as $jobData) {
                $this->storeJob($jobSource, $jobData);
            }

            $jobSource->incrementJobsFound();
            $jobSource->updateSuccessRate(true);

            Log::info("LinkedIn scraper: Found " . count($jobs) . " jobs");

            return $jobs;

        } catch (\Exception $e) {
            Log::error("LinkedIn scraper failed: " . $e->getMessage());
            
            if (isset($jobSource)) {
                $jobSource->updateSuccessRate(false);
            }

            return [];
        }
    }

    /**
     * Fetch jobs from LinkedIn API/scraping
     *
     * @param array $params Search parameters
     * @return array Raw job data
     */
    protected function fetchJobs(array $params): array
    {
        // In production, this would use LinkedIn's official API or careful scraping
        // LinkedIn has strict anti-scraping measures, so API access is recommended
        
        $keywords = $params['keywords'] ?? 'software engineer';
        $location = $params['location'] ?? 'United States';
        $experienceLevel = $params['experience_level'] ?? null;
        $remote = $params['remote'] ?? false;

        // Placeholder: In production, use LinkedIn API with OAuth
        // https://docs.microsoft.com/en-us/linkedin/shared/integrations/jobs
        
        Log::info("LinkedIn scraper: Searching for '{$keywords}' in '{$location}'");

        // For demonstration, return structured data format
        return $this->getDemoJobs($keywords, $location);
    }

    /**
     * Store discovered job in database
     *
     * @param JobSource $jobSource
     * @param array $jobData
     * @return DiscoveredJob|null
     */
    protected function storeJob(JobSource $jobSource, array $jobData): ?DiscoveredJob
    {
        try {
            // Check if job already exists
            $existing = DiscoveredJob::where('url', $jobData['url'])->first();
            
            if ($existing) {
                Log::debug("Job already exists: " . $jobData['url']);
                return $existing;
            }

            $job = DiscoveredJob::create([
                'job_source_id' => $jobSource->id,
                'external_id' => $jobData['external_id'] ?? null,
                'url' => $jobData['url'],
                'title' => $jobData['title'],
                'company_name' => $jobData['company_name'],
                'description' => $jobData['description'],
                'requirements' => $jobData['requirements'] ?? null,
                'extracted_skills' => $jobData['skills'] ?? [],
                'location' => $jobData['location'] ?? null,
                'is_remote' => $jobData['is_remote'] ?? false,
                'work_arrangement' => $jobData['work_arrangement'] ?? null,
                'salary_min' => $jobData['salary_min'] ?? null,
                'salary_max' => $jobData['salary_max'] ?? null,
                'salary_period' => $jobData['salary_period'] ?? 'yearly',
                'employment_type' => $jobData['employment_type'] ?? null,
                'experience_level' => $jobData['experience_level'] ?? null,
                'applicant_count' => $jobData['applicant_count'] ?? null,
                'posted_at' => $jobData['posted_at'] ?? now(),
            ]);

            Log::info("Stored new job: {$job->title} at {$job->company_name}");

            return $job;

        } catch (\Exception $e) {
            Log::error("Failed to store job: " . $e->getMessage(), [
                'job_data' => $jobData
            ]);
            return null;
        }
    }

    /**
     * Extract job details from LinkedIn job page
     *
     * @param string $url Job URL
     * @return array Job details
     */
    public function extractJobDetails(string $url): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch job page: " . $response->status());
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            // Extract job details using CSS selectors
            // Note: LinkedIn's selectors change frequently
            $title = $crawler->filter('.job-title, .top-card-layout__title')->text('');
            $company = $crawler->filter('.company-name, .top-card-layout__company-name')->text('');
            $location = $crawler->filter('.job-location, .top-card-layout__location')->text('');
            $description = $crawler->filter('.job-description, .description__text')->html('');

            return [
                'url' => $url,
                'title' => trim($title),
                'company_name' => trim($company),
                'location' => trim($location),
                'description' => strip_tags($description),
                'is_remote' => stripos($location, 'remote') !== false,
            ];

        } catch (\Exception $e) {
            Log::error("Failed to extract job details from {$url}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse salary from job description
     *
     * @param string $text Job description
     * @return array Salary data
     */
    protected function parseSalary(string $text): array
    {
        $salary = [
            'min' => null,
            'max' => null,
            'period' => 'yearly',
        ];

        // Match patterns like "$100,000 - $150,000", "$100K-$150K", etc.
        if (preg_match('/\$?([\d,]+)k?\s*-\s*\$?([\d,]+)k?/i', $text, $matches)) {
            $salary['min'] = (int) str_replace(',', '', $matches[1]) * 1000;
            $salary['max'] = (int) str_replace(',', '', $matches[2]) * 1000;
        }

        // Detect period
        if (stripos($text, 'per hour') !== false || stripos($text, '/hour') !== false) {
            $salary['period'] = 'hourly';
        } elseif (stripos($text, 'per month') !== false || stripos($text, '/month') !== false) {
            $salary['period'] = 'monthly';
        }

        return $salary;
    }

    /**
     * Extract skills from job description using AI or keywords
     *
     * @param string $description Job description
     * @return array Skills
     */
    protected function extractSkills(string $description): array
    {
        // Common tech skills to look for
        $skillKeywords = [
            'JavaScript', 'TypeScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go', 'Rust',
            'React', 'Angular', 'Vue', 'Node.js', 'Express', 'Django', 'Flask', 'Laravel',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'CI/CD', 'Git',
            'REST API', 'GraphQL', 'Microservices', 'Agile', 'Scrum',
        ];

        $foundSkills = [];

        foreach ($skillKeywords as $skill) {
            if (stripos($description, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return array_unique($foundSkills);
    }

    /**
     * Get demo jobs for testing
     *
     * @param string $keywords
     * @param string $location
     * @return array
     */
    protected function getDemoJobs(string $keywords, string $location): array
    {
        return [
            [
                'external_id' => 'linkedin_' . uniqid(),
                'url' => 'https://www.linkedin.com/jobs/view/' . rand(1000000000, 9999999999),
                'title' => 'Senior Software Engineer',
                'company_name' => 'TechCorp Inc',
                'description' => 'We are seeking a Senior Software Engineer to join our team. You will work on building scalable web applications using React, Node.js, and AWS. Strong experience with TypeScript and PostgreSQL required.',
                'requirements' => '5+ years of experience, BS in Computer Science or equivalent',
                'skills' => ['React', 'Node.js', 'TypeScript', 'AWS', 'PostgreSQL'],
                'location' => $location,
                'is_remote' => str_contains($location, 'Remote'),
                'work_arrangement' => 'hybrid',
                'salary_min' => 120000,
                'salary_max' => 180000,
                'salary_period' => 'yearly',
                'employment_type' => 'full_time',
                'experience_level' => 'senior',
                'applicant_count' => rand(50, 200),
                'posted_at' => now()->subDays(rand(1, 5)),
            ],
            [
                'external_id' => 'linkedin_' . uniqid(),
                'url' => 'https://www.linkedin.com/jobs/view/' . rand(1000000000, 9999999999),
                'title' => 'Full Stack Developer',
                'company_name' => 'Startup Ventures',
                'description' => 'Join our fast-growing startup as a Full Stack Developer. Work with Python, Django, React, and Docker to build innovative solutions.',
                'requirements' => '3+ years of experience',
                'skills' => ['Python', 'Django', 'React', 'Docker', 'PostgreSQL'],
                'location' => $location,
                'is_remote' => true,
                'work_arrangement' => 'remote',
                'salary_min' => 100000,
                'salary_max' => 140000,
                'salary_period' => 'yearly',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'applicant_count' => rand(20, 100),
                'posted_at' => now()->subDays(rand(1, 3)),
            ],
        ];
    }

    /**
     * Check if rate limit is reached
     *
     * @return bool
     */
    protected function isRateLimited(): bool
    {
        $cacheKey = 'linkedin_scraper_rate_limit';
        $requestCount = Cache::get($cacheKey, 0);

        if ($requestCount >= 100) { // Max 100 requests per hour
            return true;
        }

        Cache::put($cacheKey, $requestCount + 1, 3600); // 1 hour
        return false;
    }
}
