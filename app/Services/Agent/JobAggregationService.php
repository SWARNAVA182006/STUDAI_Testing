<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\DiscoveredJob;
use App\Models\JobSource;
use App\Services\JobBoard\IndeedPublisherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Job Aggregation Service
 *
 * Aggregates real jobs from multiple production-grade sources:
 *  - RSS/API feeds (RemoteOK, We Work Remotely, HackerNews, Jobicy, Remotive, Arbeitnow, Himalayas)
 *  - Indeed Publisher API (when credentials are configured)
 *  - Company website crawlers (via CompanyWebsiteCrawler)
 *
 * The deprecated LinkedIn/Indeed/Glassdoor HTML scrapers have been removed.
 * Use RSSJobFeedService for free real-time job data.
 */
class JobAggregationService
{
    protected RSSJobFeedService $rssFeedService;
    protected IndeedPublisherService $indeedPublisher;
    protected CompanyWebsiteCrawler $companyCrawler;

    protected float $duplicateThreshold = 0.80; // 80% similarity
    protected int $batchSize = 50;

    public function __construct(
        RSSJobFeedService $rssFeedService,
        IndeedPublisherService $indeedPublisher,
        CompanyWebsiteCrawler $companyCrawler
    ) {
        $this->rssFeedService = $rssFeedService;
        $this->indeedPublisher = $indeedPublisher;
        $this->companyCrawler = $companyCrawler;
    }

    /**
     * Aggregate jobs from all active sources.
     *
     * Sources used (in order of priority):
     *  1. RSS/API feeds  — free, no credentials needed (RemoteOK, WWR, HN, Jobicy, Remotive, Arbeitnow, Himalayas)
     *  2. Indeed Publisher API — real structured data when INDEED_PUBLISHER_ID is set
     *  3. Company Website Crawler — employer-posted roles
     *
     * @param array $searchParams
     * @return array Statistics
     */
    public function aggregateFromAllSources(array $searchParams = []): array
    {
        $stats = [
            'total_discovered' => 0,
            'by_source' => [],
            'duplicates_found' => 0,
            'unique_jobs' => 0,
            'errors' => [],
        ];

        try {
            // ── 1. RSS / free API sources ─────────────────────────────────
            try {
                $rssJobs = $this->rssFeedService->fetchAll();
                $count = $rssJobs->count();
                $stats['by_source']['RSS Feeds'] = $count;
                $stats['total_discovered'] += $count;
                Log::info("RSS Feeds: Discovered {$count} jobs");
            } catch (\Exception $e) {
                $stats['errors']['RSS Feeds'] = $e->getMessage();
                Log::error('RSS aggregation failed: ' . $e->getMessage());
            }

            // ── 2. Indeed Publisher API (when configured) ─────────────────
            if (config('services.indeed.publisher_id')) {
                try {
                    $keywords = $searchParams['keywords'] ?? 'software engineer';
                    $location = $searchParams['location'] ?? '';

                    $result = $this->indeedPublisher->searchJobs([
                        'q' => $keywords,
                        'l' => $location,
                        'limit' => 25,
                    ]);

                    $count = count($result['jobs'] ?? []);
                    $stats['by_source']['Indeed Publisher'] = $count;
                    $stats['total_discovered'] += $count;
                    Log::info("Indeed Publisher: Discovered {$count} jobs");
                } catch (\Exception $e) {
                    $stats['errors']['Indeed Publisher'] = $e->getMessage();
                    Log::error('Indeed Publisher aggregation failed: ' . $e->getMessage());
                }
            }

            // ── 3. Company website crawler (per watched company) ─────────
            try {
                $watchedCompanies = JobSource::where('type', 'company_website')
                    ->where('is_active', true)
                    ->get();

                $crawledCount = 0;
                foreach ($watchedCompanies as $companySource) {
                    $crawledJobs = $this->companyCrawler->crawl($companySource->url, $companySource->name);
                    $crawledCount += count($crawledJobs);
                }

                $stats['by_source']['Company Websites'] = $crawledCount;
                $stats['total_discovered'] += $crawledCount;
                Log::info("Company Websites: Discovered {$crawledCount} jobs");
            } catch (\Exception $e) {
                $stats['errors']['Company Websites'] = $e->getMessage();
                Log::error('Company crawler failed: ' . $e->getMessage());
            }

            // ── Deduplication ──────────────────────────────────────────────
            $duplicates = $this->deduplicateJobs();
            $stats['duplicates_found'] = $duplicates;

            // Count unique jobs
            $stats['unique_jobs'] = DiscoveredJob::whereNull('duplicate_of_id')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            Log::info("Job aggregation complete", $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error("Job aggregation failed: " . $e->getMessage());
            $stats['errors']['aggregation'] = $e->getMessage();
            return $stats;
        }
    }

    /**
     * Deduplicate jobs across all sources
     *
     * @return int Number of duplicates found
     */
    public function deduplicateJobs(): int
    {
        $duplicatesCount = 0;

        try {
            // Get recent unprocessed jobs
            $jobs = DiscoveredJob::whereNull('duplicate_of_id')
                ->whereNull('processed_at')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($jobs as $job) {
                $duplicate = $this->findDuplicateFor($job);
                
                if ($duplicate) {
                    $job->markAsDuplicate($duplicate->id);
                    $this->mergeJobData($duplicate, $job);
                    $duplicatesCount++;
                    
                    Log::debug("Duplicate found: '{$job->title}' at {$job->company_name}");
                }
                
                $job->markAsProcessed();
            }

            Log::info("Deduplication complete: {$duplicatesCount} duplicates found");

            return $duplicatesCount;

        } catch (\Exception $e) {
            Log::error("Deduplication failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Find duplicate job for given job
     *
     * @param DiscoveredJob $job
     * @return DiscoveredJob|null
     */
    protected function findDuplicateFor(DiscoveredJob $job): ?DiscoveredJob
    {
        // Get potential duplicates from same company
        $candidates = DiscoveredJob::where('company_name', $job->company_name)
            ->where('id', '!=', $job->id)
            ->whereNull('duplicate_of_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        foreach ($candidates as $candidate) {
            $similarity = $this->calculateSimilarity($job, $candidate);
            
            if ($similarity >= $this->duplicateThreshold) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Calculate similarity between two jobs
     *
     * @param DiscoveredJob $job1
     * @param DiscoveredJob $job2
     * @return float Similarity score (0.0 to 1.0)
     */
    public function calculateSimilarity(DiscoveredJob $job1, DiscoveredJob $job2): float
    {
        $weights = [
            'title' => 0.50,
            'company' => 0.20,
            'location' => 0.15,
            'description' => 0.10,
            'url' => 0.05,
        ];

        $scores = [
            'title' => $this->stringSimilarity($job1->title, $job2->title),
            'company' => $this->stringSimilarity($job1->company_name, $job2->company_name),
            'location' => $this->stringSimilarity($job1->location ?? '', $job2->location ?? ''),
            'description' => $this->textSimilarity($job1->description ?? '', $job2->description ?? ''),
            'url' => $job1->url === $job2->url ? 1.0 : 0.0,
        ];

        $totalScore = 0.0;
        foreach ($weights as $key => $weight) {
            $totalScore += $scores[$key] * $weight;
        }

        return $totalScore;
    }

    /**
     * Calculate string similarity (0.0 to 1.0)
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    protected function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        if (empty($str1) || empty($str2)) {
            return 0.0;
        }

        // Levenshtein distance (max 255 chars)
        $str1 = substr($str1, 0, 255);
        $str2 = substr($str2, 0, 255);
        
        $maxLen = max(strlen($str1), strlen($str2));
        $distance = levenshtein($str1, $str2);
        
        return 1.0 - ($distance / $maxLen);
    }

    /**
     * Calculate text similarity using word overlap
     *
     * @param string $text1
     * @param string $text2
     * @return float
     */
    protected function textSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0.0;
        }

        // Extract words
        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        // Calculate Jaccard similarity
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Extract meaningful words from text
     *
     * @param string $text
     * @return array
     */
    protected function extractWords(string $text): array
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove HTML tags
        $text = strip_tags($text);
        
        // Extract words (alphanumeric + common tech symbols)
        preg_match_all('/\b[a-z0-9#+.]+\b/', $text, $matches);
        $words = $matches[0] ?? [];
        
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been'];
        $words = array_diff($words, $stopWords);
        
        // Remove very short words
        $words = array_filter($words, fn($w) => strlen($w) > 2);
        
        return array_values($words);
    }

    /**
     * Merge data from duplicate into original job
     *
     * @param DiscoveredJob $original
     * @param DiscoveredJob $duplicate
     * @return void
     */
    protected function mergeJobData(DiscoveredJob $original, DiscoveredJob $duplicate): void
    {
        try {
            DB::transaction(function () use ($original, $duplicate) {
                // Use better description if available
                if (empty($original->description) && !empty($duplicate->description)) {
                    $original->description = $duplicate->description;
                }

                // Merge skills
                $originalSkills = $original->extracted_skills ?? [];
                $duplicateSkills = $duplicate->extracted_skills ?? [];
                $mergedSkills = array_unique(array_merge($originalSkills, $duplicateSkills));
                $original->extracted_skills = $mergedSkills;

                // Use salary if original doesn't have it
                if (!$original->salary_min && $duplicate->salary_min) {
                    $original->salary_min = $duplicate->salary_min;
                    $original->salary_max = $duplicate->salary_max;
                    $original->salary_period = $duplicate->salary_period;
                }

                // Use better location
                if (($original->location === 'Unknown' || empty($original->location)) && !empty($duplicate->location)) {
                    $original->location = $duplicate->location;
                    $original->is_remote = $duplicate->is_remote;
                }

                // Merge metadata
                $originalMeta = $original->metadata ?? [];
                $duplicateMeta = $duplicate->metadata ?? [];
                $original->metadata = array_merge($originalMeta, $duplicateMeta);

                $original->save();
            });

            Log::debug("Merged data from duplicate job #{$duplicate->id} into #{$original->id}");

        } catch (\Exception $e) {
            Log::error("Failed to merge job data: " . $e->getMessage());
        }
    }

    /**
     * Enrich job data from multiple sources
     *
     * @param DiscoveredJob $job
     * @return void
     */
    public function enrichJobData(DiscoveredJob $job): void
    {
        try {
            // Find all duplicates
            $duplicates = DiscoveredJob::where('duplicate_of_id', $job->id)->get();

            foreach ($duplicates as $duplicate) {
                $this->mergeJobData($job, $duplicate);
            }

            Log::info("Enriched job #{$job->id} with data from {$duplicates->count()} duplicates");

        } catch (\Exception $e) {
            Log::error("Failed to enrich job data: " . $e->getMessage());
        }
    }

    /**
     * Get aggregation statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_jobs' => DiscoveredJob::count(),
            'unique_jobs' => DiscoveredJob::whereNull('duplicate_of_id')->count(),
            'duplicates' => DiscoveredJob::whereNotNull('duplicate_of_id')->count(),
            'by_source' => $this->getJobCountBySource(),
            'recent_jobs' => DiscoveredJob::where('created_at', '>=', now()->subHours(24))->count(),
        ];
    }

    /**
     * Get job count by source
     *
     * @return array
     */
    protected function getJobCountBySource(): array
    {
        return DiscoveredJob::select('job_source_id', DB::raw('count(*) as count'))
            ->whereNull('duplicate_of_id')
            ->groupBy('job_source_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $source = JobSource::find($item->job_source_id);
                return [$source->name ?? 'Unknown' => $item->count];
            })
            ->toArray();
    }

    /**
     * Clean old jobs
     *
     * @param int $daysOld
     * @return int Number of jobs deleted
     */
    public function cleanOldJobs(int $daysOld = 90): int
    {
        try {
            $count = DiscoveredJob::where('created_at', '<', now()->subDays($daysOld))
                ->delete();

            Log::info("Cleaned {$count} old jobs (>{$daysOld} days)");

            return $count;

        } catch (\Exception $e) {
            Log::error("Failed to clean old jobs: " . $e->getMessage());
            return 0;
        }
    }
}
