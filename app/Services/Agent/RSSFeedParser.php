<?php

namespace App\Services\Agent;

use App\Models\JobSource;
use App\Models\DiscoveredJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class RSSFeedParser
{
    protected int $timeout = 20;

    /**
     * Popular job RSS feeds
     */
    protected array $knownFeeds = [
        'Stack Overflow Jobs' => 'https://stackoverflow.com/jobs/feed',
        'GitHub Jobs' => 'https://jobs.github.com/positions.atom',
        'RemoteOK' => 'https://remoteok.com/remote-jobs.rss',
        'We Work Remotely' => 'https://weworkremotely.com/categories/remote-programming-jobs.rss',
        'AngelList' => 'https://angel.co/jobs.rss',
    ];

    /**
     * Parse jobs from RSS/Atom feed
     *
     * @param string $feedUrl
     * @param string|null $sourceName
     * @return array Discovered jobs
     */
    public function parse(string $feedUrl, ?string $sourceName = null): array
    {
        try {
            $jobSource = $this->getOrCreateJobSource($feedUrl, $sourceName);

            $items = $this->fetchFeedItems($feedUrl);
            
            if (empty($items)) {
                Log::warning("No items found in feed: {$feedUrl}");
                return [];
            }

            $jobs = [];
            foreach ($items as $item) {
                $jobData = $this->extractJobFromItem($item, $feedUrl);
                if ($jobData) {
                    $stored = $this->storeJob($jobSource, $jobData);
                    if ($stored) {
                        $jobs[] = $jobData;
                    }
                }
            }

            $jobSource->incrementJobsFound(count($jobs));
            $jobSource->updateSuccessRate(true);

            Log::info("RSS parser: Found " . count($jobs) . " jobs from {$feedUrl}");

            return $jobs;

        } catch (\Exception $e) {
            Log::error("RSS parser failed for {$feedUrl}: " . $e->getMessage());
            
            if (isset($jobSource)) {
                $jobSource->updateSuccessRate(false);
            }

            return [];
        }
    }

    /**
     * Parse jobs from multiple RSS feeds
     *
     * @param array $feedUrls
     * @return array All discovered jobs
     */
    public function parseMultiple(array $feedUrls): array
    {
        $allJobs = [];

        foreach ($feedUrls as $feedUrl) {
            $jobs = $this->parse($feedUrl);
            $allJobs = array_merge($allJobs, $jobs);
        }

        return $allJobs;
    }

    /**
     * Subscribe to known job feeds
     *
     * @return array Discovered jobs from all known feeds
     */
    public function subscribeToKnownFeeds(): array
    {
        $allJobs = [];

        foreach ($this->knownFeeds as $name => $url) {
            $jobs = $this->parse($url, $name);
            $allJobs = array_merge($allJobs, $jobs);
            
            // Rate limiting - wait between feeds
            sleep(2);
        }

        return $allJobs;
    }

    /**
     * Fetch feed items
     *
     * @param string $feedUrl
     * @return array
     */
    protected function fetchFeedItems(string $feedUrl): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; StudAIBot/1.0)',
                ])
                ->get($feedUrl);

            if (!$response->successful()) {
                throw new \Exception("HTTP " . $response->status());
            }

            $content = $response->body();
            
            // Try to parse as XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception("XML parsing failed: " . json_encode($errors));
            }

            return $this->extractItemsFromXML($xml);

        } catch (\Exception $e) {
            Log::error("Failed to fetch RSS feed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract items from XML
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected function extractItemsFromXML(SimpleXMLElement $xml): array
    {
        $items = [];

        // Detect feed type (RSS vs Atom)
        if (isset($xml->channel)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => (string) $item->description,
                    'pubDate' => (string) $item->pubDate,
                    'guid' => (string) ($item->guid ?? $item->link),
                ];
            }
        } elseif (isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                $items[] = [
                    'title' => (string) $entry->title,
                    'link' => (string) $entry->link['href'],
                    'description' => (string) ($entry->summary ?? $entry->content),
                    'pubDate' => (string) ($entry->published ?? $entry->updated),
                    'guid' => (string) $entry->id,
                ];
            }
        }

        return $items;
    }

    /**
     * Extract job data from feed item
     *
     * @param array $item
     * @param string $feedUrl
     * @return array|null
     */
    protected function extractJobFromItem(array $item, string $feedUrl): ?array
    {
        try {
            $title = $item['title'] ?? null;
            $link = $item['link'] ?? null;
            $description = strip_tags($item['description'] ?? '');
            $pubDate = $item['pubDate'] ?? null;
            $guid = $item['guid'] ?? $link;

            if (!$title || !$link) {
                return null;
            }

            // Try to extract company and location from title
            // Common formats: "Software Engineer at TechCorp (San Francisco)"
            $company = null;
            $location = null;

            if (preg_match('/at\s+([^(]+)/i', $title, $matches)) {
                $company = trim($matches[1]);
            }

            if (preg_match('/\(([^)]+)\)/', $title, $matches)) {
                $location = trim($matches[1]);
            }

            // Clean title (remove company and location)
            $cleanTitle = preg_replace('/\s+at\s+.+$/i', '', $title);
            $cleanTitle = preg_replace('/\s*\([^)]+\)/', '', $cleanTitle);

            // Parse published date
            $postedAt = $pubDate ? $this->parseDate($pubDate) : now();

            return [
                'external_id' => 'rss_' . md5($guid),
                'url' => $link,
                'title' => trim($cleanTitle),
                'company_name' => $company ?: 'Unknown',
                'description' => $description,
                'location' => $location ?: 'Unknown',
                'is_remote' => $location ? stripos($location, 'remote') !== false : false,
                'posted_at' => $postedAt,
            ];

        } catch (\Exception $e) {
            Log::debug("Failed to extract job from RSS item: " . $e->getMessage());
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
                'posted_at' => $jobData['posted_at'],
            ]);

            return $job;

        } catch (\Exception $e) {
            Log::error("Failed to store RSS job: " . $e->getMessage());
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
            'JavaScript', 'TypeScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go',
            'React', 'Angular', 'Vue', 'Node.js', 'Django', 'Laravel',
            'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
            'AWS', 'Azure', 'GCP', 'Docker', 'Kubernetes', 'Git',
            'REST API', 'GraphQL', 'Agile',
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
     * Parse date from various formats
     *
     * @param string $dateString
     * @return \Illuminate\Support\Carbon
     */
    protected function parseDate(string $dateString): \Illuminate\Support\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }

    /**
     * Get or create job source for feed
     *
     * @param string $feedUrl
     * @param string|null $sourceName
     * @return JobSource
     */
    protected function getOrCreateJobSource(string $feedUrl, ?string $sourceName): JobSource
    {
        $name = $sourceName ?: $this->extractSourceName($feedUrl);

        return JobSource::firstOrCreate(
            ['url' => $feedUrl],
            [
                'name' => $name,
                'type' => 'rss_feed',
                'is_active' => true,
                'priority' => 5,
            ]
        );
    }

    /**
     * Extract source name from feed URL
     *
     * @param string $url
     * @return string
     */
    protected function extractSourceName(string $url): string
    {
        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? 'Unknown Feed';
        
        // Clean up domain
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = preg_replace('/\.[a-z]{2,}$/i', '', $domain);
        
        return ucwords(str_replace(['-', '_'], ' ', $domain));
    }

    /**
     * Add custom feed
     *
     * @param string $feedUrl
     * @param string $name
     */
    public function addCustomFeed(string $feedUrl, string $name): void
    {
        $this->knownFeeds[$name] = $feedUrl;
    }
}
