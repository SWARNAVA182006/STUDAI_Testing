<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\PipelineCandidate;
use App\Models\TalentPipeline;
use App\Services\AI\Scout\TalentPipelineService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateTalentPipelinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company to update pipelines for
     */
    protected Company $company;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->onQueue('pipeline-updates');
    }

    /**
     * Execute the job.
     */
    public function handle(TalentPipelineService $talentPipelineService): void
    {
        $startTime = now();
        $progressKey = "pipeline_update_progress_{$this->company->id}";
        
        try {
            Log::info('Starting talent pipeline update', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
            ]);

            // Initialize progress tracking
            $this->updateProgress($progressKey, 0, 'Initializing pipeline update...');

            // Step 1: Update pipeline health scores (20%)
            $this->updateProgress($progressKey, 10, 'Updating pipeline health scores...');
            $talentPipelineService->updatePipelineHealthScores($this->company);
            $this->updateProgress($progressKey, 20, 'Health scores updated');

            // Step 2: Clean up stale candidates (40%)
            $this->updateProgress($progressKey, 30, 'Identifying stale candidates...');
            $staleCandidatesCount = $this->handleStaleCandidates();
            $this->updateProgress($progressKey, 40, "Processed {$staleCandidatesCount} stale candidates");

            // Step 3: Update candidate priorities (60%)
            $this->updateProgress($progressKey, 50, 'Updating candidate priorities...');
            $priorityUpdatesCount = $this->updateCandidatePriorities();
            $this->updateProgress($progressKey, 60, "Updated {$priorityUpdatesCount} candidate priorities");

            // Step 4: Schedule follow-ups (80%)
            $this->updateProgress($progressKey, 70, 'Scheduling follow-ups...');
            $followUpsCount = $this->scheduleFollowUps();
            $this->updateProgress($progressKey, 80, "Scheduled {$followUpsCount} follow-ups");

            // Step 5: Generate pipeline insights (100%)
            $this->updateProgress($progressKey, 90, 'Generating pipeline insights...');
            $insights = $this->generatePipelineInsights();
            $this->updateProgress($progressKey, 100, 'Pipeline update completed');

            $duration = now()->diffInSeconds($startTime);

            Log::info('Talent pipeline update completed', [
                'company_id' => $this->company->id,
                'duration_seconds' => $duration,
                'stale_candidates' => $staleCandidatesCount,
                'priority_updates' => $priorityUpdatesCount,
                'follow_ups_scheduled' => $followUpsCount,
                'insights_generated' => count($insights),
            ]);

            // Store completion timestamp
            Cache::put("pipeline_last_updated_{$this->company->id}", now(), 86400); // 24 hours

        } catch (\Exception $e) {
            Log::error('Failed to update talent pipelines', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress($progressKey, -1, 'Update failed: ' . $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Handle stale candidates in pipelines
     *
     * @return int
     */
    protected function handleStaleCandidates(): int
    {
        $count = 0;
        $pipelines = TalentPipeline::where('company_id', $this->company->id)
            ->active()
            ->get();

        foreach ($pipelines as $pipeline) {
            // Get stale candidates (no engagement in 90+ days)
            $staleCandidates = $pipeline->candidates()
                ->stale(90)
                ->whereNotIn('pipeline_stage', ['archived', 'cool'])
                ->get();

            foreach ($staleCandidates as $candidate) {
                // Cool down stale candidates
                if (in_array($candidate->pipeline_stage, ['hot', 'warm'])) {
                    $candidate->coolDown();
                    $count++;
                    
                    Log::info('Stale candidate cooled down', [
                        'candidate_id' => $candidate->id,
                        'user_id' => $candidate->user_id,
                        'days_since_engagement' => $candidate->days_since_last_engagement,
                    ]);
                }

                // Archive very old candidates (180+ days)
                if ($candidate->days_since_last_engagement > 180 && $candidate->pipeline_stage === 'cool') {
                    $candidate->update(['pipeline_stage' => 'archived']);
                    $count++;
                    
                    Log::info('Very stale candidate archived', [
                        'candidate_id' => $candidate->id,
                        'user_id' => $candidate->user_id,
                        'days_since_engagement' => $candidate->days_since_last_engagement,
                    ]);
                }
            }

            // Update pipeline health after changes
            $pipeline->updateHealthScore();
        }

        return $count;
    }

    /**
     * Update candidate priorities based on recent activity
     *
     * @return int
     */
    protected function updateCandidatePriorities(): int
    {
        $count = 0;
        $pipelines = TalentPipeline::where('company_id', $this->company->id)
            ->active()
            ->get();

        foreach ($pipelines as $pipeline) {
            $candidates = $pipeline->candidates()
                ->whereNotIn('pipeline_stage', ['archived'])
                ->get();

            foreach ($candidates as $candidate) {
                // Promote warm candidates with high engagement
                if ($candidate->pipeline_stage === 'warm' && 
                    $candidate->engagement_count >= 5 && 
                    $candidate->days_since_last_engagement < 14) {
                    
                    $candidate->advanceStage('hot');
                    $count++;
                    
                    Log::info('Candidate promoted to hot', [
                        'candidate_id' => $candidate->id,
                        'engagement_count' => $candidate->engagement_count,
                    ]);
                }

                // Update follow-up dates based on priority
                if (!$candidate->next_follow_up_date || $candidate->next_follow_up_date < now()) {
                    $followUpDate = $this->calculateNextFollowUpDate($candidate);
                    $candidate->update(['next_follow_up_date' => $followUpDate]);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Calculate next follow-up date based on candidate stage and activity
     *
     * @param PipelineCandidate $candidate
     * @return Carbon
     */
    protected function calculateNextFollowUpDate(PipelineCandidate $candidate): Carbon
    {
        return match($candidate->pipeline_stage) {
            'hot' => now()->addDays(7),      // Weekly follow-up
            'warm' => now()->addDays(14),    // Bi-weekly follow-up
            'qualified' => now()->addDays(21), // Every 3 weeks
            'pre_screened' => now()->addDays(30), // Monthly
            'engaged' => now()->addDays(45),  // Every 6 weeks
            'sourced' => now()->addDays(60),  // Every 2 months
            'cool' => now()->addMonths(3),    // Quarterly
            default => now()->addDays(30),    // Default monthly
        };
    }

    /**
     * Schedule follow-ups for candidates
     *
     * @return int
     */
    protected function scheduleFollowUps(): int
    {
        $count = 0;
        $candidates = PipelineCandidate::whereHas('talentPipeline', function($query) {
                $query->where('company_id', $this->company->id)
                    ->where('pipeline_status', 'active');
            })
            ->needsFollowUp()
            ->get();

        foreach ($candidates as $candidate) {
            // Mark as needing follow-up in external system
            // This could trigger email notifications, calendar events, etc.
            
            Log::info('Follow-up scheduled for candidate', [
                'candidate_id' => $candidate->id,
                'user_id' => $candidate->user_id,
                'pipeline_id' => $candidate->talent_pipeline_id,
                'follow_up_date' => $candidate->next_follow_up_date,
            ]);
            
            $count++;
        }

        return $count;
    }

    /**
     * Generate insights about pipeline performance
     *
     * @return array
     */
    protected function generatePipelineInsights(): array
    {
        $insights = [];
        $pipelines = TalentPipeline::where('company_id', $this->company->id)
            ->active()
            ->get();

        foreach ($pipelines as $pipeline) {
            $pipelineInsights = [
                'pipeline_id' => $pipeline->id,
                'pipeline_name' => $pipeline->pipeline_name,
                'health_status' => $pipeline->health_status,
            ];

            // Check if understaffed
            if ($pipeline->is_understaffed) {
                $pipelineInsights['issues'][] = [
                    'type' => 'understaffed',
                    'message' => "Pipeline is at {$pipeline->fill_rate}% capacity",
                    'recommendation' => 'Increase sourcing efforts for this role',
                ];
            }

            // Check for lack of warm candidates
            $warmCount = $pipeline->candidates()->warm()->count();
            if ($warmCount < 3) {
                $pipelineInsights['issues'][] = [
                    'type' => 'low_warm_candidates',
                    'message' => "Only {$warmCount} warm candidates available",
                    'recommendation' => 'Engage more candidates to move them to warm status',
                ];
            }

            // Check for stale candidates
            $staleCount = $pipeline->candidates()->stale(60)->count();
            if ($staleCount > 0) {
                $pipelineInsights['issues'][] = [
                    'type' => 'stale_candidates',
                    'message' => "{$staleCount} candidates have not been engaged in 60+ days",
                    'recommendation' => 'Re-engage or archive stale candidates',
                ];
            }

            // Check average match score
            $avgMatchScore = $pipeline->candidates()->avg('match_score');
            if ($avgMatchScore < 60) {
                $pipelineInsights['issues'][] = [
                    'type' => 'low_match_quality',
                    'message' => "Average match score is {$avgMatchScore}%",
                    'recommendation' => 'Review candidate sourcing criteria',
                ];
            }

            if (!empty($pipelineInsights['issues'])) {
                $insights[] = $pipelineInsights;
            }
        }

        // Store insights for dashboard
        Cache::put(
            "pipeline_insights_{$this->company->id}",
            $insights,
            86400 // 24 hours
        );

        return $insights;
    }

    /**
     * Update job progress
     *
     * @param string $key
     * @param int $percentage
     * @param string $message
     * @return void
     */
    protected function updateProgress(string $key, int $percentage, string $message): void
    {
        Cache::put($key, [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toIso8601String(),
        ], 3600); // 1 hour
    }

    /**
     * Get progress for a company
     *
     * @param int $companyId
     * @return array|null
     */
    public static function getProgress(int $companyId): ?array
    {
        return Cache::get("pipeline_update_progress_{$companyId}");
    }

    /**
     * Get last updated timestamp
     *
     * @param int $companyId
     * @return Carbon|null
     */
    public static function getLastUpdated(int $companyId): ?Carbon
    {
        return Cache::get("pipeline_last_updated_{$companyId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateTalentPipelinesJob failed', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators or send alert
    }
}
