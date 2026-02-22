<?php

namespace App\Jobs\Agent;

use App\Models\AgentConfiguration;
use App\Services\Agent\JobDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Discover Jobs Job
 * 
 * Runs hourly to discover new job opportunities for all active agents.
 */
class DiscoverJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(JobDiscoveryService $discoveryService): void
    {
        Log::info('Starting job discovery for all active agents');

        $activeConfigs = AgentConfiguration::active()
            ->where('is_paused', false)
            ->get();

        Log::info('Found active agent configurations', [
            'count' => $activeConfigs->count(),
        ]);

        foreach ($activeConfigs as $config) {
            try {
                // Check if within active hours
                if (!$this->isWithinActiveHours($config)) {
                    Log::debug('Agent not within active hours', [
                        'config_id' => $config->id,
                        'user_id' => $config->user_id,
                    ]);
                    continue;
                }

                Log::info('Discovering jobs for agent', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                ]);

                $jobMatches = $discoveryService->discoverJobs($config);

                Log::info('Job discovery completed for agent', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                    'matches_found' => $jobMatches->count(),
                ]);

                // Update last run timestamp
                $config->update(['last_run_at' => now()]);

                // Queue analysis and submission jobs if matches found
                if ($jobMatches->isNotEmpty()) {
                    SubmitApplicationsJob::dispatch($config)->delay(now()->addMinutes(5));
                }

            } catch (\Exception $e) {
                Log::error('Job discovery failed for agent', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Job discovery completed for all agents');
    }

    /**
     * Check if current time is within agent's active hours
     */
    protected function isWithinActiveHours(AgentConfiguration $config): bool
    {
        if (empty($config->active_hours)) {
            return true; // No restrictions
        }

        $currentHour = now()->hour;
        
        foreach ($config->active_hours as $range) {
            if (str_contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                if ($currentHour >= (int)$start && $currentHour <= (int)$end) {
                    return true;
                }
            } elseif ($currentHour == (int)$range) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('DiscoverJobsJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
