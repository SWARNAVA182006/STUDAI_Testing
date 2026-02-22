<?php

namespace App\Jobs\Agent;

use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Services\Agent\AgentLearningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Update Learning Job
 * 
 * Processes application outcomes and optimizes agent strategy.
 * Runs daily to analyze patterns and improve performance.
 */
class UpdateLearningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    /**
     * Execute the job.
     */
    public function handle(AgentLearningService $learningService): void
    {
        Log::info('Starting learning update for all agents');

        $configs = AgentConfiguration::where('enable_learning', true)
            ->get();

        Log::info('Found agent configurations with learning enabled', [
            'count' => $configs->count(),
        ]);

        foreach ($configs as $config) {
            try {
                Log::info('Processing learning update for agent', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                ]);

                // Analyze patterns
                $analysis = $learningService->analyzePatterns($config);

                Log::info('Pattern analysis completed', [
                    'config_id' => $config->id,
                    'insights_count' => count($analysis['insights'] ?? []),
                    'recommendations_count' => count($analysis['recommendations'] ?? []),
                ]);

                // Optimize strategy if enough data
                $applicationsCount = AutoApplication::where('agent_configuration_id', $config->id)
                    ->whereNotNull('outcome')
                    ->count();

                if ($applicationsCount >= 10) {
                    Log::info('Optimizing agent strategy', [
                        'config_id' => $config->id,
                        'applications_with_outcomes' => $applicationsCount,
                    ]);

                    $learningService->optimizeStrategy($config);

                    Log::info('Strategy optimization completed', [
                        'config_id' => $config->id,
                        'last_optimization' => $config->fresh()->last_optimization_at,
                    ]);
                } else {
                    Log::info('Not enough data for optimization yet', [
                        'config_id' => $config->id,
                        'applications_with_outcomes' => $applicationsCount,
                        'required' => 10,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Learning update failed for agent', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Learning update completed for all agents');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateLearningJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
