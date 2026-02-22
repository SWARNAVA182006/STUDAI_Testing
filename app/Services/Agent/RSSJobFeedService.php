<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\DiscoveredJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * RSS Job Feed Service
 *
 * Aggregates job listings from free RSS feeds and APIs that don't require API keys.
 * This provides a cost-free source of real job data for the autonomous agent.
 *
 * Supported Sources:
 * - RemoteOK (RSS)
 * - We Work Remotely (RSS)
 * - HackerNews Jobs (API)
 * - GitHub Jobs (via alternative sources)
 * - Jobicy (RSS)
 *
 * Usage:
 *   $service = app(RSSJobFeedService::class);
 *   $jobs = $service->fetchAll();
 *   $jobs = $service->fetchFromSource('remoteok');
 */
class RSSJobFeedService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * HTTP timeout in seconds.
     */
    protected const HTTP_TIMEOUT = 30;

    /**
     * RSS Feed sources configuration.
     */
    protected array $sources = [
        'remoteok' => [
            'name' => 'RemoteOK',
            'url' => 'https://remoteok.com/remote-jobs.rss',
            'type' => 'rss',
            'enabled' => true,
        ],
        'weworkremotely' => [
            'name' => 'We Work Remotely',
            'url' => 'https://weworkremotely.com/remote-jobs.rss',
            'type' => 'rss',
            'enabled' => true,
        ],
        'hackernews' => [
            'name' => 'HackerNews Jobs',
            'url' => 'https://hacker-news.firebaseio.com/v0/jobstories.json',
            'type' => 'api',
            'enabled' => true,
        ],
        'jobicy' => [
            'name' => 'Jobicy',
            'url' => 'https://jobicy.com/feed/newjobs',
            'type' => 'rss',
            'enabled' => true,
        ],
        'remotive' => [
            'name' => 'Remotive',
            'url' => 'https://remotive.com/api/remote-jobs',
            'type' => 'api',
            'enabled' => true,
        ],
        'arbeitnow' => [
            'name' => 'Arbeitnow',
            'url' => 'https://arbeitnow.com/api/job-board-api',
            'type' => 'api',
            'enabled' => true,
        ],
        'himalayas' => [
            'name' => 'Himalayas',
            'url' => 'https://himalayas.app/jobs/api?limit=100',
            'type' => 'api',
            'enabled' => true,
        ],
    ];

    /**
     * Fetch jobs from all enabled sources.
     */
    public function fetchAll(bool $useCache = true): Collection
    {
        $allJobs = collect();

        foreach ($this->sources as $key => $source) {
            if (!$source['enabled']) {
                continue;
            }

            try {
                $jobs = $this->fetchFromSource($key, $useCache);
                $allJobs = $allJobs->merge($jobs);
            } catch (\Exception $e) {
                Log::warning("Failed to fetch jobs from {$source['name']}", [
                    'source' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('RSS job feed fetch completed', [
            'total_jobs' => $allJobs->count(),
            'sources' => array_keys(array_filter($this->sources, fn($s) => $s['enabled'])),
        ]);

        return $allJobs;
    }

    /**
     * Fetch jobs from a specific source.
     */
    public function fetchFromSource(string $sourceKey, bool $useCache = true): Collection
    {
        if (!isset($this->sources[$sourceKey])) {
            throw new \InvalidArgumentException("Unknown source: {$sourceKey}");
        }

        $source = $this->sources[$sourceKey];

        if (!$source['enabled']) {
            return collect();
        }

        $cacheKey = "rss_jobs:{$sourceKey}";

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return collect($cached);
            }
        }

        $jobs = match ($source['type']) {
            'rss' => $this->fetchRSSFeed($source['url'], $sourceKey),
            'api' => $this->fetchAPIFeed($sourceKey),
            default => collect(),
        };

        if ($useCache && $jobs->isNotEmpty()) {
            Cache::put($cacheKey, $jobs->toArray(), self::CACHE_TTL);
        }

        return $jobs;
    }

    /**
     * Fetch and parse an RSS feed.
     */
    protected function fetchRSSFeed(string $url, string $sourceKey): Collection
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->withHeaders([
                'User-Agent' => 'StudAI-Career-Bot/1.0 (+https://studai-career.com)',
                'Accept' => 'application/rss+xml, application/xml, text/xml',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch RSS feed: HTTP {$response->status()}");
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            throw new \RuntimeException("Failed to parse RSS feed XML");
        }

        return $this->parseRSSItems($xml, $sourceKey);
    }

    /**
     * Parse RSS feed items into job format.
     */
    protected function parseRSSItems(SimpleXMLElement $xml, string $sourceKey): Collection
    {
        $jobs = collect();
        $items = $xml->channel->item ?? $xml->item ?? [];

        foreach ($items as $item) {
            try {
                $job = $this->normalizeRSSItem($item, $sourceKey);
                if ($job) {
                    $jobs->push($job);
                }
            } catch (\Exception $e) {
                Log::debug("Failed to parse RSS item", [
                    'source' => $sourceKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $jobs;
    }

    /**
     * Normalize an RSS item to a standard job format.
     */
    protected function normalizeRSSItem(SimpleXMLElement $item, string $sourceKey): ?array
    {
        $title = (string) $item->title;
        $link = (string) $item->link;
        $description = (string) $item->description;
        $pubDate = (string) $item->pubDate;

        if (empty($title) || empty($link)) {
            return null;
        }

        // Extract company name from title if possible (common format: "Job Title at Company")
        $company = null;
        if (preg_match('/\bat\s+(.+?)(?:\s*[\(\[\|]|$)/i', $title, $matches)) {
            $company = trim($matches[1]);
        }

        // Clean up title
        $cleanTitle = preg_replace('/\s+at\s+.+$/i', '', $title);

        // Extract skills/tags from description or categories
        $skills = $this->extractSkillsFromDescription($description);

        // Parse location from description
        $location = $this->extractLocationFromDescription($description);

        return [
            'external_id' => md5($link),
            'source' => $sourceKey,
            'source_name' => $this->sources[$sourceKey]['name'],
            'title' => trim($cleanTitle),
            'company_name' => $company,
            'description' => strip_tags($description),
            'url' => $link,
            'location' => $location ?? 'Remote',
            'is_remote' => true,
            'skills' => $skills,
            'posted_at' => $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : now()->toDateTimeString(),
            'fetched_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Fetch jobs from a JSON API feed.
     */
    protected function fetchAPIFeed(string $sourceKey): Collection
    {
        return match ($sourceKey) {
            'hackernews' => $this->fetchHackerNewsJobs(),
            'remotive'   => $this->fetchRemotiveJobs(),
            'arbeitnow'  => $this->fetchArbeitnowJobs(),
            'himalayas'  => $this->fetchHimalayasJobs(),
            default      => collect(),
        };
    }

    /**
     * Fetch jobs from the Remotive API (no auth required).
     */
    protected function fetchRemotiveJobs(): Collection
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->withHeaders(['User-Agent' => 'StudAI-Career-Bot/1.0'])
            ->get('https://remotive.com/api/remote-jobs');

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch Remotive jobs: HTTP {$response->status()}");
        }

        $data = $response->json('jobs', []);

        return collect($data)->filter()->map(function (array $item): array {
            return [
                'external_id'  => 'remotive_' . ($item['id'] ?? md5($item['url'] ?? '')),
                'source'       => 'remotive',
                'source_name'  => 'Remotive',
                'title'        => $item['title'] ?? '',
                'company_name' => $item['company_name'] ?? null,
                'description'  => strip_tags($item['description'] ?? ''),
                'url'          => $item['url'] ?? '',
                'location'     => $item['candidate_required_location'] ?? 'Remote',
                'is_remote'    => true,
                'skills'       => $this->extractSkillsFromDescription($item['description'] ?? ''),
                'posted_at'    => isset($item['publication_date'])
                    ? date('Y-m-d H:i:s', strtotime($item['publication_date']))
                    : now()->toDateTimeString(),
                'fetched_at'   => now()->toDateTimeString(),
            ];
        })->values();
    }

    /**
     * Fetch jobs from the Arbeitnow API (no auth required).
     */
    protected function fetchArbeitnowJobs(): Collection
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->withHeaders(['User-Agent' => 'StudAI-Career-Bot/1.0'])
            ->get('https://arbeitnow.com/api/job-board-api');

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch Arbeitnow jobs: HTTP {$response->status()}");
        }

        $data = $response->json('data', []);

        return collect($data)->filter()->map(function (array $item): array {
            $tags = array_merge(
                $item['tags'] ?? [],
                $this->extractSkillsFromDescription($item['description'] ?? '')
            );

            return [
                'external_id'  => 'arbeitnow_' . ($item['slug'] ?? md5($item['url'] ?? '')),
                'source'       => 'arbeitnow',
                'source_name'  => 'Arbeitnow',
                'title'        => $item['title'] ?? '',
                'company_name' => $item['company_name'] ?? null,
                'description'  => strip_tags($item['description'] ?? ''),
                'url'          => $item['url'] ?? '',
                'location'     => $item['location'] ?? 'Remote',
                'is_remote'    => (bool) ($item['remote'] ?? true),
                'skills'       => array_unique($tags),
                'posted_at'    => isset($item['created_at'])
                    ? date('Y-m-d H:i:s', (int) $item['created_at'])
                    : now()->toDateTimeString(),
                'fetched_at'   => now()->toDateTimeString(),
            ];
        })->values();
    }

    /**
     * Fetch jobs from the Himalayas API (no auth required).
     */
    protected function fetchHimalayasJobs(): Collection
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->withHeaders(['User-Agent' => 'StudAI-Career-Bot/1.0'])
            ->get('https://himalayas.app/jobs/api', ['limit' => 100]);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch Himalayas jobs: HTTP {$response->status()}");
        }

        $data = $response->json('jobs', []);

        return collect($data)->filter()->map(function (array $item): array {
            return [
                'external_id'  => 'himalayas_' . ($item['id'] ?? md5($item['applicationLink'] ?? '')),
                'source'       => 'himalayas',
                'source_name'  => 'Himalayas',
                'title'        => $item['title'] ?? '',
                'company_name' => $item['companyName'] ?? null,
                'description'  => strip_tags($item['description'] ?? ''),
                'url'          => $item['applicationLink'] ?? $item['jobUrl'] ?? '',
                'location'     => $item['locationRestrictions'][0] ?? 'Worldwide',
                'is_remote'    => true,
                'skills'       => $this->extractSkillsFromDescription($item['description'] ?? ''),
                'posted_at'    => isset($item['publishedAt'])
                    ? date('Y-m-d H:i:s', strtotime($item['publishedAt']))
                    : now()->toDateTimeString(),
                'fetched_at'   => now()->toDateTimeString(),
            ];
        })->values();
    }

    /**
     * Fetch jobs from HackerNews API.
     */
    protected function fetchHackerNewsJobs(): Collection
    {
        if (false) {
            return collect(); // unreachable — kept for IDE compatibility
        }

        // Get job story IDs
        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->get('https://hacker-news.firebaseio.com/v0/jobstories.json');

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch HackerNews job stories");
        }

        $storyIds = $response->json();
        if (!is_array($storyIds)) {
            return collect();
        }

        // Limit to 50 most recent jobs
        $storyIds = array_slice($storyIds, 0, 50);

        $jobs = collect();

        foreach ($storyIds as $storyId) {
            try {
                $job = $this->fetchHackerNewsJob($storyId);
                if ($job) {
                    $jobs->push($job);
                }
            } catch (\Exception $e) {
                Log::debug("Failed to fetch HackerNews job", [
                    'story_id' => $storyId,
                    'error' => $e->getMessage(),
                ]);
            }

            // Small delay to be respectful to the API
            usleep(50000); // 50ms
        }

        return $jobs;
    }

    /**
     * Fetch a single HackerNews job posting.
     */
    protected function fetchHackerNewsJob(int $storyId): ?array
    {
        $response = Http::timeout(10)
            ->get("https://hacker-news.firebaseio.com/v0/item/{$storyId}.json");

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (empty($data) || ($data['type'] ?? '') !== 'job') {
            return null;
        }

        $title = $data['title'] ?? '';
        $text = $data['text'] ?? '';
        $url = $data['url'] ?? "https://news.ycombinator.com/item?id={$storyId}";
        $time = $data['time'] ?? time();

        // Extract company from title (YC jobs often follow pattern: "Company (YC XX) is hiring...")
        $company = null;
        if (preg_match('/^([A-Za-z0-9\s\-\.]+?)(?:\s*\(YC|\s+is\s+hiring)/i', $title, $matches)) {
            $company = trim($matches[1]);
        }

        // Clean title
        $cleanTitle = preg_replace('/^[^:]+:\s*/', '', $title);
        $cleanTitle = preg_replace('/\s*\(YC [A-Z]\d{2}\)/', '', $cleanTitle);

        return [
            'external_id' => "hn_{$storyId}",
            'source' => 'hackernews',
            'source_name' => 'HackerNews',
            'title' => trim($cleanTitle) ?: $title,
            'company_name' => $company,
            'description' => strip_tags($text),
            'url' => $url,
            'location' => 'Remote',
            'is_remote' => true,
            'skills' => $this->extractSkillsFromDescription($text),
            'posted_at' => date('Y-m-d H:i:s', $time),
            'fetched_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Extract skills from job description.
     */
    protected function extractSkillsFromDescription(string $description): array
    {
        $skillKeywords = [
            'PHP', 'Python', 'JavaScript', 'TypeScript', 'Java', 'C#', 'C++', 'Go', 'Golang', 'Rust',
            'Ruby', 'Swift', 'Kotlin', 'Scala', 'R', 'MATLAB',
            'React', 'Vue', 'Angular', 'Next.js', 'Nuxt', 'Svelte', 'Node.js', 'Express', 'Django',
            'Flask', 'FastAPI', 'Laravel', 'Rails', 'Spring', 'ASP.NET', '.NET',
            'AWS', 'Azure', 'GCP', 'Google Cloud', 'Docker', 'Kubernetes', 'K8s', 'Terraform',
            'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch', 'GraphQL',
            'Machine Learning', 'ML', 'AI', 'Deep Learning', 'NLP', 'Computer Vision',
            'DevOps', 'CI/CD', 'Jenkins', 'GitHub Actions', 'GitLab CI',
            'Figma', 'Sketch', 'Adobe XD', 'UI/UX', 'UX Design',
            'Agile', 'Scrum', 'Kanban', 'JIRA', 'Confluence',
            'REST', 'API', 'Microservices', 'Serverless', 'WebSocket',
            'Linux', 'Unix', 'Shell', 'Bash', 'Git',
            'TensorFlow', 'PyTorch', 'Pandas', 'NumPy', 'Scikit-learn',
            'Tableau', 'Power BI', 'Data Analysis', 'SQL', 'ETL',
            'Salesforce', 'HubSpot', 'Shopify', 'WordPress',
        ];

        $foundSkills = [];
        $descriptionLower = strtolower($description);

        foreach ($skillKeywords as $skill) {
            if (stripos($descriptionLower, strtolower($skill)) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return array_unique($foundSkills);
    }

    /**
     * Extract location from job description.
     */
    protected function extractLocationFromDescription(string $description): ?string
    {
        // Common location patterns
        $patterns = [
            '/\b(San Francisco|New York|NYC|Los Angeles|LA|Seattle|Austin|Chicago|Boston|Denver|Portland|Miami|Atlanta|Dallas|Houston)\b/i',
            '/\b(London|Berlin|Amsterdam|Paris|Dublin|Toronto|Vancouver|Sydney|Melbourne|Singapore|Tokyo|Mumbai|Bangalore)\b/i',
            '/\b(Remote|Work from Home|WFH|Anywhere|Distributed)\b/i',
            '/Location:\s*([^,\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Store fetched jobs as discovered jobs.
     */
    public function storeAsDiscoveredJobs(Collection $jobs, int $userId): int
    {
        $stored = 0;

        foreach ($jobs as $jobData) {
            try {
                DiscoveredJob::updateOrCreate(
                    [
                        'external_id' => $jobData['external_id'],
                        'source' => $jobData['source'],
                    ],
                    [
                        'user_id' => $userId,
                        'source_name' => $jobData['source_name'],
                        'title' => $jobData['title'],
                        'company_name' => $jobData['company_name'],
                        'description' => $jobData['description'],
                        'url' => $jobData['url'],
                        'location' => $jobData['location'],
                        'is_remote' => $jobData['is_remote'],
                        'skills' => $jobData['skills'],
                        'posted_at' => $jobData['posted_at'],
                        'status' => 'new',
                    ]
                );
                $stored++;
            } catch (\Exception $e) {
                Log::debug('Failed to store discovered job', [
                    'external_id' => $jobData['external_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('RSS jobs stored as discovered jobs', [
            'user_id' => $userId,
            'stored' => $stored,
        ]);

        return $stored;
    }

    /**
     * Get available sources.
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Enable or disable a source.
     */
    public function setSourceEnabled(string $sourceKey, bool $enabled): void
    {
        if (isset($this->sources[$sourceKey])) {
            $this->sources[$sourceKey]['enabled'] = $enabled;
        }
    }

    /**
     * Clear cache for all sources.
     */
    public function clearCache(): void
    {
        foreach (array_keys($this->sources) as $sourceKey) {
            Cache::forget("rss_jobs:{$sourceKey}");
        }

        Log::info('RSS job feed cache cleared');
    }
}
