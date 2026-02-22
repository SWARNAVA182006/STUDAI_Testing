<?php

namespace App\Services\Agent;

use App\Models\JobSource;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Indeed Job Scraper Service
 *
 * @deprecated since 2026-02-06. This scraper returns demo data only and should not
 *             be used in production. Use RSSJobFeedService instead for real job data.
 *             Indeed requires Publisher API access for legitimate use.
 *
 * @see \App\Services\Agent\RSSJobFeedService for production job discovery
 */
class IndeedScraperService
{
    protected string $baseUrl = 'https://www.indeed.com/jobs';
    protected int $timeout = 30;

    /**
     * Scrape jobs from Indeed
     *
     * @param array $params Search parameters
     * @return array Discovered jobs
     */
    public function scrape(array $params = []): array
    {
        try {
            $jobSource = JobSource::firstOrCreate(
                ['name' => 'Indeed'],
                [
                    'type' => 'job_board',
                    'url' => $this->baseUrl,
                    'is_active' => true,
                    'priority' => 8,
                ]
            );

            $jobs = $this->fetchJobs($params);
            
            foreach ($jobs as $jobData) {
                $this->storeJob($jobSource, $jobData);
            }

            $jobSource->incrementJobsFound();
            $jobSource->updateSuccessRate(true);

            Log::info("Indeed scraper: Found " . count($jobs) . " jobs");

            return $jobs;

        } catch (\Exception $e) {
            Log::error("Indeed scraper failed: " . $e->getMessage());
            
            if (isset($jobSource)) {
                $jobSource->updateSuccessRate(false);
            }

            return [];
        }
    }

    /**
     * Fetch jobs from Indeed
     *
     * @param array $params
     * @return array
     */
    protected function fetchJobs(array $params): array
    {
        $keywords = $params['keywords'] ?? 'software engineer';
        $location = $params['location'] ?? '';
        $radius = $params['radius'] ?? 25;
        $jobType = $params['job_type'] ?? '';

        // Build query parameters
        $queryParams = [
            'q' => $keywords,
            'l' => $location,
            'radius' => $radius,
            'sort' => 'date',
        ];

        if ($jobType) {
            $queryParams['jt'] = $jobType; // fulltime, parttime, contract, etc.
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($this->baseUrl, $queryParams);

            if (!$response->successful()) {
                throw new \Exception("HTTP " . $response->status());
            }

            return $this->parseJobListings($response->body());

        } catch (\Exception $e) {
            Log::error("Failed to fetch Indeed jobs: " . $e->getMessage());
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

        // Indeed job cards selector (may need updating)
        $crawler->filter('.job_seen_beacon, .jobsearch-ResultsList > li')->each(function (Crawler $node) use (&$jobs) {
            try {
                $jobData = $this->extractJobFromNode($node);
                if ($jobData && !empty($jobData['url'])) {
                    $jobs[] = $jobData;
                }
            } catch (\Exception $e) {
                Log::debug("Failed to parse job node: " . $e->getMessage());
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
            $titleNode = $node->filter('h2.jobTitle, .title a');
            $companyNode = $node->filter('.companyName, .company');
            $locationNode = $node->filter('.companyLocation, .location');
            $snippetNode = $node->filter('.job-snippet, .summary');

            $title = $titleNode->count() ? $titleNode->text() : null;
            $company = $companyNode->count() ? $companyNode->text() : null;
            $location = $locationNode->count() ? $locationNode->text() : null;
            $snippet = $snippetNode->count() ? $snippetNode->text() : '';

            // Get job URL
            $link = $titleNode->count() ? $titleNode->attr('href') : null;
            if ($link && !str_starts_with($link, 'http')) {
                $link = 'https://www.indeed.com' . $link;
            }

            if (!$title || !$company || !$link) {
                return null;
            }

            // Extract job key/ID from URL
            preg_match('/jk=([a-f0-9]+)/', $link, $matches);
            $jobKey = $matches[1] ?? null;

            return [
                'external_id' => $jobKey ? "indeed_{$jobKey}" : "indeed_" . md5($link),
                'url' => $link,
                'title' => trim($title),
                'company_name' => trim($company),
                'location' => trim($location),
                'description' => trim($snippet),
                'is_remote' => stripos($location, 'remote') !== false,
                'posted_at' => now()->subDays(rand(1, 7)),
            ];

        } catch (\Exception $e) {
            Log::debug("Failed to extract job from node: " . $e->getMessage());
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

            // Extract skills from description
            $skills = $this->extractSkills($jobData['description']);

            $job = DiscoveredJob::create([
                'job_source_id' => $jobSource->id,
                'external_id' => $jobData['external_id'],
                'url' => $jobData['url'],
                'title' => $jobData['title'],
                'company_name' => $jobData['company_name'],
                'description' => $jobData['description'],
                'extracted_skills' => $skills,
                'location' => $jobData['location'],
                'is_remote' => $jobData['is_remote'],
                'posted_at' => $jobData['posted_at'] ?? now(),
            ]);

            return $job;

        } catch (\Exception $e) {
            Log::error("Failed to store Indeed job: " . $e->getMessage());
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
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());

            $description = $crawler->filter('#jobDescriptionText, .jobsearch-jobDescriptionText')->html('');
            $salary = $crawler->filter('.salary-snippet, .metadata.salary-snippet-container')->text('');

            return [
                'description' => strip_tags($description),
                'salary_text' => $salary,
                'skills' => $this->extractSkills($description),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch Indeed job details: " . $e->getMessage());
            return [];
        }
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
            'JavaScript', 'TypeScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go',
            'React', 'Angular', 'Vue', 'Node.js', 'Django', 'Flask', 'Laravel', 'Spring',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Git',
            'REST', 'GraphQL', 'Microservices', 'Agile', 'Scrum',
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
                'external_id' => 'indeed_' . uniqid(),
                'url' => 'https://www.indeed.com/viewjob?jk=' . bin2hex(random_bytes(16)),
                'title' => 'Software Engineer',
                'company_name' => 'InnovateTech',
                'description' => 'Looking for a talented Software Engineer proficient in Python, Django, and AWS.',
                'location' => $location ?: 'San Francisco, CA',
                'is_remote' => str_contains($location, 'Remote'),
                'posted_at' => now()->subDays(rand(1, 5)),
            ],
        ];
    }
}
