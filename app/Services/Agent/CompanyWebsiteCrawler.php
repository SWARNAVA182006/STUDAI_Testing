<?php

namespace App\Services\Agent;

use App\Models\JobSource;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\DomCrawler\Crawler;

class CompanyWebsiteCrawler
{
    protected int $timeout = 30;
    protected int $maxRetries = 2;
    protected array $commonCareersPaths = [
        '/careers',
        '/jobs',
        '/work-with-us',
        '/join-us',
        '/opportunities',
        '/working-here',
        '/careers/jobs',
        '/about/careers',
        '/company/careers',
    ];

    protected array $careerKeywords = [
        'careers', 'jobs', 'opportunities', 'join us', 'work with us', 'openings',
    ];

    /**
     * Crawl company website for job postings
     *
     * @param string $companyUrl Base company URL
     * @param string|null $companyName
     * @return array Discovered jobs
     */
    public function crawl(string $companyUrl, ?string $companyName = null): array
    {
        try {
            $jobSource = $this->getOrCreateJobSource($companyUrl, $companyName);

            // Find careers page
            $careersPageUrl = $this->findCareersPage($companyUrl);
            
            if (!$careersPageUrl) {
                Log::warning("No careers page found for: {$companyUrl}");
                return [];
            }

            // Extract jobs from careers page
            $jobs = $this->extractJobs($careersPageUrl, $companyName);
            
            foreach ($jobs as $jobData) {
                $this->storeJob($jobSource, $jobData);
            }

            $jobSource->incrementJobsFound(count($jobs));
            $jobSource->updateSuccessRate(true);

            Log::info("Company crawler: Found " . count($jobs) . " jobs at {$companyUrl}");

            return $jobs;

        } catch (\Exception $e) {
            Log::error("Company crawler failed for {$companyUrl}: " . $e->getMessage());
            
            if (isset($jobSource)) {
                $jobSource->updateSuccessRate(false);
            }

            return [];
        }
    }

    /**
     * Find careers page on company website
     *
     * @param string $baseUrl
     * @return string|null
     */
    protected function findCareersPage(string $baseUrl): ?string
    {
        // Normalize URL
        $baseUrl = rtrim($baseUrl, '/');
        
        // Try common career page paths
        foreach ($this->commonCareersPaths as $path) {
            $url = $baseUrl . $path;
            
            if ($this->urlExists($url)) {
                return $url;
            }
        }

        // Try to find career links in homepage
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => $this->getUserAgent()])
                ->get($baseUrl);

            if (!$response->successful()) {
                return null;
            }

            $crawler = new Crawler($response->body(), $baseUrl);

            // Find links containing career keywords
            $careerLink = null;
            $crawler->filter('a')->each(function (Crawler $node) use (&$careerLink) {
                $href = $node->attr('href');
                $text = strtolower($node->text());

                foreach ($this->careerKeywords as $keyword) {
                    if (str_contains($text, $keyword) || str_contains(strtolower($href ?? ''), $keyword)) {
                        $careerLink = $node->link()->getUri();
                        return false; // Stop iteration
                    }
                }
            });

            return $careerLink;

        } catch (\Exception $e) {
            Log::error("Failed to find careers page: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract jobs from careers page
     *
     * @param string $careersUrl
     * @param string|null $companyName
     * @return array
     */
    protected function extractJobs(string $careersUrl, ?string $companyName): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => $this->getUserAgent()])
                ->get($careersUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body(), $careersUrl);
            $jobs = [];

            // Try different common job listing patterns
            $selectors = [
                '.job-listing, .job-item, .position, .opening',
                '[class*="job"], [class*="position"], [class*="career"]',
                'tr.job, li.job, div.job',
            ];

            foreach ($selectors as $selector) {
                $crawler->filter($selector)->each(function (Crawler $node) use (&$jobs, $companyName, $careersUrl) {
                    try {
                        $jobData = $this->extractJobFromNode($node, $companyName, $careersUrl);
                        if ($jobData) {
                            $jobs[] = $jobData;
                        }
                    } catch (\Exception $e) {
                        Log::debug("Failed to extract job from node: " . $e->getMessage());
                    }
                });

                if (count($jobs) > 0) {
                    break; // Found jobs with this selector
                }
            }

            // If no structured listings found, look for job detail links
            if (empty($jobs)) {
                $jobs = $this->findJobLinks($crawler, $companyName, $careersUrl);
            }

            return $jobs;

        } catch (\Exception $e) {
            Log::error("Failed to extract jobs from careers page: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract job data from HTML node
     *
     * @param Crawler $node
     * @param string|null $companyName
     * @param string $baseUrl
     * @return array|null
     */
    protected function extractJobFromNode(Crawler $node, ?string $companyName, string $baseUrl): ?array
    {
        try {
            // Try to find title
            $titleNode = $node->filter('h2, h3, h4, .title, .job-title, [class*="title"]')->first();
            $title = $titleNode->count() ? trim($titleNode->text()) : null;

            // Try to find location
            $locationNode = $node->filter('.location, [class*="location"]')->first();
            $location = $locationNode->count() ? trim($locationNode->text()) : null;

            // Try to find link
            $linkNode = $node->filter('a')->first();
            $link = $linkNode->count() ? $linkNode->link()->getUri() : null;

            if (!$title) {
                return null;
            }

            // Generate unique ID from URL or title
            $externalId = $link ? md5($link) : md5($companyName . $title);

            return [
                'external_id' => 'company_' . $externalId,
                'url' => $link ?: $baseUrl,
                'title' => $title,
                'company_name' => $companyName ?: $this->extractCompanyNameFromUrl($baseUrl),
                'location' => $location ?: 'Unknown',
                'is_remote' => $location ? stripos($location, 'remote') !== false : false,
                'posted_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::debug("Failed to extract job from node: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find job detail links on page
     *
     * @param Crawler $crawler
     * @param string|null $companyName
     * @param string $baseUrl
     * @return array
     */
    protected function findJobLinks(Crawler $crawler, ?string $companyName, string $baseUrl): array
    {
        $jobs = [];
        $jobKeywords = ['position', 'role', 'job', 'opening', 'opportunity'];

        $crawler->filter('a')->each(function (Crawler $node) use (&$jobs, $jobKeywords, $companyName, $baseUrl) {
            $href = $node->attr('href');
            $text = strtolower($node->text());

            // Check if link text or URL suggests it's a job posting
            $isJobLink = false;
            foreach ($jobKeywords as $keyword) {
                if (str_contains($text, $keyword) || str_contains(strtolower($href ?? ''), $keyword)) {
                    $isJobLink = true;
                    break;
                }
            }

            if ($isJobLink && !empty(trim($node->text()))) {
                try {
                    $jobData = [
                        'external_id' => 'company_' . md5($node->link()->getUri()),
                        'url' => $node->link()->getUri(),
                        'title' => trim($node->text()),
                        'company_name' => $companyName ?: $this->extractCompanyNameFromUrl($baseUrl),
                        'location' => 'Unknown',
                        'is_remote' => false,
                        'posted_at' => now(),
                    ];

                    $jobs[] = $jobData;
                } catch (\Exception $e) {
                    // Skip invalid links
                }
            }
        });

        return array_slice($jobs, 0, 20); // Limit to 20 jobs
    }

    /**
     * Fetch detailed job information from job page
     *
     * @param string $url
     * @return array
     */
    public function fetchJobDetails(string $url): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['User-Agent' => $this->getUserAgent()])
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());

            // Try to find job description
            $descriptionNode = $crawler->filter('.description, .job-description, [class*="description"]')->first();
            $description = $descriptionNode->count() ? $descriptionNode->text() : '';

            // Try to find requirements
            $requirementsNode = $crawler->filter('.requirements, [class*="requirement"]')->first();
            $requirements = $requirementsNode->count() ? $requirementsNode->text() : '';

            $fullText = $description . ' ' . $requirements;

            return [
                'description' => trim($description),
                'requirements' => trim($requirements),
                'skills' => $this->extractSkills($fullText),
            ];

        } catch (\Exception $e) {
            Log::error("Failed to fetch company job details: " . $e->getMessage());
            return [];
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

            // Fetch full details if we have a job-specific URL
            $details = [];
            if ($jobData['url'] && !str_ends_with($jobData['url'], '/careers')) {
                $details = $this->fetchJobDetails($jobData['url']);
            }

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
                'posted_at' => $jobData['posted_at'],
            ]);

            return $job;

        } catch (\Exception $e) {
            Log::error("Failed to store company job: " . $e->getMessage());
            return null;
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
            'JavaScript', 'TypeScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go', 'Rust',
            'React', 'Angular', 'Vue', 'Svelte',
            'Node.js', 'Express', 'Django', 'Flask', 'Laravel', 'Spring',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Git',
            'REST API', 'GraphQL', 'Microservices',
            'Agile', 'Scrum',
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
     * Get or create job source for company
     *
     * @param string $url
     * @param string|null $companyName
     * @return JobSource
     */
    protected function getOrCreateJobSource(string $url, ?string $companyName): JobSource
    {
        $name = $companyName ?: $this->extractCompanyNameFromUrl($url);

        return JobSource::firstOrCreate(
            ['url' => $url],
            [
                'name' => $name,
                'type' => 'company_website',
                'is_active' => true,
                'priority' => 6,
            ]
        );
    }

    /**
     * Extract company name from URL
     *
     * @param string $url
     * @return string
     */
    protected function extractCompanyNameFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? '';
        
        // Remove www. and TLD
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = preg_replace('/\.[a-z]{2,}$/i', '', $domain);
        
        return ucfirst($domain);
    }

    /**
     * Check if URL exists
     *
     * @param string $url
     * @return bool
     */
    protected function urlExists(string $url): bool
    {
        try {
            $response = Http::timeout(10)->head($url);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get random user agent
     *
     * @return string
     */
    protected function getUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
