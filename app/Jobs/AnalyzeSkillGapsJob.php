<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AI\SkillGapAnalyzerService;
use App\Notifications\CriticalSkillGapsDetectedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeSkillGapsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 120, 240]; // Exponential backoff in seconds

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(SkillGapAnalyzerService $analyzer): void
    {
        try {
            Log::info("Starting skill gap analysis for user {$this->user->id}");

            // Analyze user's skill gaps using AI
            $gaps = $analyzer->analyzeUserSkillGaps($this->user);

            Log::info("Skill gap analysis completed for user {$this->user->id}", [
                'total_gaps' => $gaps->count(),
                'critical_gaps' => $gaps->where('gap_severity', 'critical')->count(),
            ]);

            // Check for critical gaps and send notification if needed
            $criticalGaps = $gaps->where('gap_severity', 'critical');
            
            if ($criticalGaps->count() > 0) {
                $this->user->notify(new CriticalSkillGapsDetectedNotification($criticalGaps));
                
                Log::warning("Critical skill gaps detected for user {$this->user->id}", [
                    'critical_count' => $criticalGaps->count(),
                    'skills' => $criticalGaps->pluck('skill_name')->toArray(),
                ]);
            }

            // Update user's last_analysis_at timestamp
            $this->user->profile->update([
                'last_skill_analysis_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Skill gap analysis failed for user {$this->user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Skill gap analysis job failed permanently for user {$this->user->id}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could send notification to user about analysis failure
        // $this->user->notify(new SkillAnalysisFailedNotification($exception));
    }

    /**
     * Get the tags for the job (for monitoring).
     */
    public function tags(): array
    {
        return ['skill-analysis', 'user:' . $this->user->id];
    }
}
