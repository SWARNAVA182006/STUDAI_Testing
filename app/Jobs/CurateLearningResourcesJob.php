<?php

namespace App\Jobs;

use App\Models\LearningPath;
use App\Services\AI\LearningPathCuratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CurateLearningResourcesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes (longer for batch processing)
    public $backoff = [120, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(LearningPathCuratorService $curator): void
    {
        try {
            Log::info("Starting weekly learning resource curation");

            $activePaths = LearningPath::with(['resources', 'skillGap'])
                ->where('status', 'active')
                ->orWhere('status', 'paused')
                ->get();

            $stats = [
                'total_paths' => $activePaths->count(),
                'resources_updated' => 0,
                'stale_resources_removed' => 0,
                'new_resources_added' => 0,
                'broken_links_fixed' => 0,
            ];

            foreach ($activePaths as $path) {
                try {
                    Log::info("Curating resources for learning path {$path->id}", [
                        'skill' => $path->skillGap->skill_name ?? 'Unknown',
                        'resource_count' => $path->resources->count(),
                    ]);

                    // Refresh resource ratings and relevance scores
                    foreach ($path->resources as $resource) {
                        // Check if resource URL is still valid
                        if ($this->isResourceBroken($resource->url)) {
                            Log::warning("Broken resource detected", [
                                'resource_id' => $resource->id,
                                'url' => $resource->url,
                            ]);

                            // Try to find replacement resource
                            $replacement = $curator->findReplacementResource(
                                $resource->title,
                                $resource->resource_type,
                                $path->skillGap->skill_name ?? ''
                            );

                            if ($replacement) {
                                $resource->update([
                                    'url' => $replacement['url'],
                                    'rating' => $replacement['rating'] ?? $resource->rating,
                                    'ai_analysis' => array_merge(
                                        $resource->ai_analysis ?? [],
                                        ['replaced_at' => now(), 'reason' => 'broken_link']
                                    ),
                                ]);
                                $stats['broken_links_fixed']++;
                            } else {
                                // Mark as stale if no replacement found
                                $resource->update(['is_stale' => true]);
                                $stats['stale_resources_removed']++;
                            }
                        }

                        // Update relevance score based on current trends
                        if ($resource->created_at->diffInMonths(now()) > 3) {
                            $relevanceScore = $curator->calculateResourceRelevance(
                                $resource,
                                $path->skillGap
                            );

                            $resource->update([
                                'relevance_score' => $relevanceScore,
                                'last_reviewed_at' => now(),
                            ]);
                            $stats['resources_updated']++;
                        }
                    }

                    // Find and add new trending resources
                    $newResources = $curator->discoverNewResources(
                        $path->skillGap,
                        $limit = 3
                    );

                    foreach ($newResources as $resourceData) {
                        $path->resources()->create($resourceData);
                        $stats['new_resources_added']++;
                    }

                    // Update path metadata
                    $path->update([
                        'last_curated_at' => now(),
                        'resource_count' => $path->resources()->where('is_stale', false)->count(),
                    ]);

                } catch (\Exception $e) {
                    Log::error("Failed to curate resources for path {$path->id}", [
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with other paths
                }
            }

            Log::info("Learning resource curation completed", $stats);

        } catch (\Exception $e) {
            Log::error("Learning resource curation job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a resource URL is broken
     */
    protected function isResourceBroken(string $url): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $statusCode >= 400; // 4xx or 5xx = broken
        } catch (\Exception $e) {
            return true; // Assume broken if check fails
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Learning resource curation job failed permanently", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['resource-curation', 'scheduled'];
    }
}
