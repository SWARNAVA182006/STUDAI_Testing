<?php

namespace App\Jobs\Agent;

use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Follow Up Job
 * 
 * Sends follow-up messages for applications that haven't received a response.
 */
class FollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    public function __construct(
        public AgentConfiguration $config
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting follow-up process', [
            'config_id' => $this->config->id,
            'user_id' => $this->config->user_id,
        ]);

        // Check if follow-up is enabled
        if (!$this->config->auto_follow_up) {
            Log::info('Auto follow-up not enabled', [
                'config_id' => $this->config->id,
            ]);
            return;
        }

        $followUpDays = $this->config->follow_up_days ?? 7;
        $followUpDate = now()->subDays($followUpDays);

        // Get applications that need follow-up
        $applications = AutoApplication::where('agent_configuration_id', $this->config->id)
            ->where('status', 'submitted')
            ->where('submitted_at', '<=', $followUpDate)
            ->whereNull('followed_up_at')
            ->whereNull('outcome')
            ->get();

        Log::info('Found applications needing follow-up', [
            'config_id' => $this->config->id,
            'count' => $applications->count(),
            'follow_up_after_days' => $followUpDays,
        ]);

        $followUpCount = 0;

        foreach ($applications as $application) {
            try {
                Log::info('Processing follow-up for application', [
                    'application_id' => $application->id,
                    'job_title' => $application->job_title,
                    'company' => $application->company_name,
                    'submitted_at' => $application->submitted_at,
                ]);

                // For platform jobs, we can send internal follow-up
                if ($application->jobMatch && $application->jobMatch->source === 'platform') {
                    $this->sendPlatformFollowUp($application);
                    $followUpCount++;
                }
                // For external jobs, just mark as followed up and notify user
                else {
                    $this->markForManualFollowUp($application);
                }

                // Update follow-up timestamp
                $application->update([
                    'followed_up_at' => now(),
                ]);

                // Rate limiting
                sleep(2);

            } catch (\Exception $e) {
                Log::error('Follow-up failed for application', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Follow-up process completed', [
            'config_id' => $this->config->id,
            'follow_up_count' => $followUpCount,
        ]);
    }

    /**
     * Send follow-up for platform job
     */
    protected function sendPlatformFollowUp(AutoApplication $application): void
    {
        // Create a follow-up message or notification
        // This would integrate with your platform's messaging system
        
        Log::info('Platform follow-up sent', [
            'application_id' => $application->id,
        ]);
    }

    /**
     * Mark application for manual follow-up
     */
    protected function markForManualFollowUp(AutoApplication $application): void
    {
        // Notify user to follow up manually
        // TODO: Send notification to user
        
        Log::info('Application marked for manual follow-up', [
            'application_id' => $application->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FollowUpJob failed permanently', [
            'config_id' => $this->config->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
