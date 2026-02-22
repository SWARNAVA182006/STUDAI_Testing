<?php

namespace App\Jobs\Agent;

use App\Models\AgentConfiguration;
use App\Models\JobMatch;
use App\Models\AutoApplication;
use App\Services\Agent\ApplicationSubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Submit Applications Job
 * 
 * Submits applications for qualified job matches while respecting daily limits.
 */
class SubmitApplicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     * 
     * @param AgentConfiguration|null $config If null, process all active agents
     */
    public function __construct(
        public ?AgentConfiguration $config = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ApplicationSubmissionService $submissionService): void
    {
        // If no specific config provided, process all active agents
        if ($this->config === null) {
            $this->processAllAgents($submissionService);
            return;
        }

        // Process specific agent
        $this->processAgent($this->config, $submissionService);
    }

    /**
     * Process all active agents
     */
    private function processAllAgents(ApplicationSubmissionService $submissionService): void
    {
        Log::info('Processing application submissions for all active agents');

        $configs = AgentConfiguration::where('is_active', true)
            ->where('is_paused', false)
            ->get();

        Log::info('Found active agent configurations', [
            'count' => $configs->count(),
        ]);

        foreach ($configs as $config) {
            try {
                $this->processAgent($config, $submissionService);
            } catch (\Exception $e) {
                Log::error('Failed to process agent', [
                    'config_id' => $config->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Completed processing all agents');
    }

    /**
     * Process a specific agent
     */
    private function processAgent(AgentConfiguration $config, ApplicationSubmissionService $submissionService): void
    {
        Log::info('Starting application submission', [
            'config_id' => $config->id,
            'user_id' => $config->user_id,
        ]);

        // Check if agent is still active and not paused
        if (!$config->is_active || $config->is_paused) {
            Log::info('Agent is not active or paused, skipping', [
                'config_id' => $config->id,
            ]);
            return;
        }

        // Check daily limit
        $todayCount = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereDate('created_at', today())
            ->count();

        $dailyLimit = $config->daily_application_limit ?? 10;
        $remaining = max(0, $dailyLimit - $todayCount);

        if ($remaining <= 0) {
            Log::info('Daily application limit reached', [
                'config_id' => $config->id,
                'limit' => $dailyLimit,
            ]);
            return;
        }

        Log::info('Applications remaining today', [
            'config_id' => $config->id,
            'remaining' => $remaining,
        ]);

        // Get qualified job matches that haven't been applied to
        $jobMatches = JobMatch::where('agent_configuration_id', $config->id)
            ->where('status', 'qualified')
            ->whereDoesntHave('autoApplications')
            ->orderByDesc('match_score')
            ->take($remaining)
            ->get();

        Log::info('Found job matches to apply', [
            'config_id' => $config->id,
            'count' => $jobMatches->count(),
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($jobMatches as $jobMatch) {
            try {
                // Check if we still have applications remaining (limit may be shared across jobs)
                $config->refresh();
                $currentTodayCount = AutoApplication::where('agent_configuration_id', $config->id)
                    ->whereDate('created_at', today())
                    ->count();

                if ($currentTodayCount >= $dailyLimit) {
                    Log::info('Daily limit reached during processing', [
                        'config_id' => $config->id,
                    ]);
                    break;
                }

                // Check if approval is required
                if ($config->require_approval) {
                    Log::info('Manual approval required, creating pending application', [
                        'job_match_id' => $jobMatch->id,
                        'job_title' => $jobMatch->job_title,
                    ]);

                    AutoApplication::create([
                        'agent_configuration_id' => $config->id,
                        'user_id' => $config->user_id,
                        'job_match_id' => $jobMatch->id,
                        'job_title' => $jobMatch->job_title,
                        'company_name' => $jobMatch->company_name,
                        'status' => 'pending_approval',
                        'match_score' => $jobMatch->match_score,
                    ]);

                    // TODO: Dispatch approval notification
                    continue;
                }

                Log::info('Submitting application', [
                    'job_match_id' => $jobMatch->id,
                    'job_title' => $jobMatch->job_title,
                    'company' => $jobMatch->company_name,
                ]);

                $application = $submissionService->submitApplication(
                    $config->user,
                    $jobMatch,
                    $config
                );

                if ($application->status === 'submitted' || $application->status === 'pending') {
                    $successCount++;
                    
                    Log::info('Application submitted successfully', [
                        'application_id' => $application->id,
                        'job_title' => $jobMatch->job_title,
                    ]);

                    // TODO: Dispatch success notification
                } else {
                    $failCount++;
                    
                    Log::warning('Application submission failed', [
                        'job_match_id' => $jobMatch->id,
                        'status' => $application->status,
                        'error' => $application->error_message,
                    ]);
                }

                // Rate limiting: wait 5 seconds between submissions
                sleep(5);

            } catch (\Exception $e) {
                $failCount++;
                
                Log::error('Application submission exception', [
                    'job_match_id' => $jobMatch->id,
                    'job_title' => $jobMatch->job_title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Application submission completed', [
            'config_id' => $config->id,
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ]);

        // Schedule follow-up job if configured
        if ($config->auto_follow_up && $successCount > 0) {
            FollowUpJob::dispatch($config)->delay(now()->addDays($config->follow_up_days ?? 7));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SubmitApplicationsJob failed permanently', [
            'config_id' => $this->config?->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
