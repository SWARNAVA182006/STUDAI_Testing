<?php

namespace App\Jobs;

use App\Models\SkillGap;
use App\Services\AI\SkillTrendPredictorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UpdateMarketTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 900; // 15 minutes for comprehensive trend analysis
    public $backoff = [180, 360, 720];

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
    public function handle(SkillTrendPredictorService $trendPredictor): void
    {
        try {
            Log::info("Starting weekly market trends update");

            $industries = [
                'software_development',
                'data_science',
                'cloud_computing',
                'cybersecurity',
                'ai_machine_learning',
                'web_development',
                'mobile_development',
                'devops',
                'product_management',
                'ux_ui_design',
            ];

            $stats = [
                'industries_analyzed' => 0,
                'skills_updated' => 0,
                'emerging_skills_detected' => 0,
                'declining_skills_detected' => 0,
            ];

            foreach ($industries as $industry) {
                try {
                    Log::info("Analyzing trends for industry: {$industry}");

                    // Get industry trends from AI service
                    $trends = $trendPredictor->getIndustryTrends($industry);

                    // Update skill gaps with latest trend data
                    foreach ($trends as $skillName => $trendData) {
                        $skillGaps = SkillGap::where('skill_name', 'LIKE', "%{$skillName}%")
                            ->get();

                        foreach ($skillGaps as $gap) {
                            $oldDirection = $gap->trend_direction;
                            $oldScore = $gap->trend_score;

                            $gap->update([
                                'market_demand_score' => $trendData['demand_score'] ?? $gap->market_demand_score,
                                'trend_score' => $trendData['trend_score'] ?? $gap->trend_score,
                                'trend_direction' => $trendData['trend_direction'] ?? $gap->trend_direction,
                                'industry_growth_rate' => $trendData['growth_rate'] ?? null,
                                'last_trend_update' => now(),
                            ]);

                            // Detect significant trend changes
                            if ($oldDirection !== $gap->trend_direction) {
                                Log::info("Trend direction change detected", [
                                    'skill' => $gap->skill_name,
                                    'old' => $oldDirection,
                                    'new' => $gap->trend_direction,
                                ]);

                                if ($gap->trend_direction === 'rising') {
                                    $stats['emerging_skills_detected']++;
                                } elseif ($gap->trend_direction === 'declining') {
                                    $stats['declining_skills_detected']++;
                                }
                            }

                            $stats['skills_updated']++;
                        }
                    }

                    $stats['industries_analyzed']++;

                    // Rate limit between industries to avoid API throttling
                    sleep(2);

                } catch (\Exception $e) {
                    Log::error("Failed to analyze trends for industry {$industry}", [
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with other industries
                }
            }

            // Cache aggregated trend data for dashboard
            $this->cacheAggregatedTrends();

            Log::info("Market trends update completed", $stats);

        } catch (\Exception $e) {
            Log::error("Market trends update job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cache aggregated trend data for quick dashboard access
     */
    protected function cacheAggregatedTrends(): void
    {
        $trendData = [
            'emerging_skills' => SkillGap::where('trend_direction', 'rising')
                ->where('trend_score', '>=', 70)
                ->orderBy('trend_score', 'desc')
                ->limit(10)
                ->get(['skill_name', 'trend_score', 'market_demand_score'])
                ->toArray(),

            'declining_skills' => SkillGap::where('trend_direction', 'declining')
                ->orderBy('trend_score', 'asc')
                ->limit(10)
                ->get(['skill_name', 'trend_score', 'market_demand_score'])
                ->toArray(),

            'high_demand_skills' => SkillGap::where('market_demand_score', '>=', 80)
                ->orderBy('market_demand_score', 'desc')
                ->limit(10)
                ->get(['skill_name', 'market_demand_score', 'trend_direction'])
                ->toArray(),

            'last_updated' => now()->toIso8601String(),
        ];

        // Cache for 1 week (until next update)
        Cache::put('market_trends_summary', $trendData, now()->addWeek());

        Log::info("Aggregated trends cached", [
            'emerging_count' => count($trendData['emerging_skills']),
            'declining_count' => count($trendData['declining_skills']),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Market trends update job failed permanently", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Clear stale cache to avoid serving outdated data
        Cache::forget('market_trends_summary');
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['market-trends', 'scheduled', 'weekly'];
    }
}
