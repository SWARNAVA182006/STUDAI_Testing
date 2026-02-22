<?php

namespace App\Jobs\Agent;

use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Notifications\Agent\DailyDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Send Digest Job
 * 
 * Sends daily email digest to users about their agent activity.
 * Includes applications submitted, outcomes received, and performance metrics.
 */
class SendDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting daily digest sending');

        $configs = AgentConfiguration::with('user')
            ->where('is_active', true)
            ->get();

        Log::info('Found active agent configurations', [
            'count' => $configs->count(),
        ]);

        foreach ($configs as $config) {
            try {
                // Skip if user doesn't want digest
                if (!$config->send_digest) {
                    continue;
                }

                Log::info('Preparing digest for user', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                    'user_email' => $config->user->email,
                ]);

                // Get yesterday's activity
                $yesterday = Carbon::yesterday();
                $startOfDay = $yesterday->copy()->startOfDay();
                $endOfDay = $yesterday->copy()->endOfDay();

                $applications = AutoApplication::where('agent_configuration_id', $config->id)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->with('job')
                    ->get();

                // Get new outcomes (outcomes received yesterday)
                $newOutcomes = AutoApplication::where('agent_configuration_id', $config->id)
                    ->whereBetween('outcome_received_at', [$startOfDay, $endOfDay])
                    ->whereNotNull('outcome')
                    ->with('job')
                    ->get();

                // Calculate statistics
                $stats = $this->calculateStats($config, $applications, $newOutcomes);

                // Only send digest if there's activity
                if ($applications->count() > 0 || $newOutcomes->count() > 0) {
                    Log::info('Sending digest with activity', [
                        'config_id' => $config->id,
                        'user_id' => $config->user_id,
                        'applications_count' => $applications->count(),
                        'outcomes_count' => $newOutcomes->count(),
                    ]);

                    $config->user->notify(new DailyDigestNotification(
                        $config,
                        $applications,
                        $newOutcomes,
                        $stats
                    ));
                } else {
                    Log::info('No activity for digest, skipping', [
                        'config_id' => $config->id,
                        'user_id' => $config->user_id,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Digest sending failed for user', [
                    'config_id' => $config->id,
                    'user_id' => $config->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Rate limiting
            sleep(1);
        }

        Log::info('Daily digest sending completed');
    }

    /**
     * Calculate statistics for the digest
     */
    private function calculateStats(AgentConfiguration $config, $applications, $newOutcomes): array
    {
        // Total applications ever
        $totalApplications = AutoApplication::where('agent_configuration_id', $config->id)
            ->count();

        // Success rate
        $totalWithOutcomes = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNotNull('outcome')
            ->count();

        $successfulOutcomes = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereIn('outcome', ['interview_scheduled', 'offer_received', 'accepted'])
            ->count();

        $successRate = $totalWithOutcomes > 0 
            ? round(($successfulOutcomes / $totalWithOutcomes) * 100, 1)
            : 0;

        // Average match score for submitted applications
        $avgMatchScore = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNotNull('match_score')
            ->avg('match_score');

        // Pending applications (no outcome yet)
        $pendingApplications = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNull('outcome')
            ->count();

        // Applications this week
        $weekStart = Carbon::now()->startOfWeek();
        $applicationsThisWeek = AutoApplication::where('agent_configuration_id', $config->id)
            ->where('created_at', '>=', $weekStart)
            ->count();

        // Applications this month
        $monthStart = Carbon::now()->startOfMonth();
        $applicationsThisMonth = AutoApplication::where('agent_configuration_id', $config->id)
            ->where('created_at', '>=', $monthStart)
            ->count();

        // Response rate (how many applications got responses)
        $responseRate = $totalApplications > 0
            ? round(($totalWithOutcomes / $totalApplications) * 100, 1)
            : 0;

        // Time to response (average days from application to outcome)
        $avgDaysToResponse = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNotNull('outcome_received_at')
            ->selectRaw('AVG(DATEDIFF(outcome_received_at, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'total_applications' => $totalApplications,
            'total_with_outcomes' => $totalWithOutcomes,
            'successful_outcomes' => $successfulOutcomes,
            'success_rate' => $successRate,
            'avg_match_score' => $avgMatchScore ? round($avgMatchScore, 1) : null,
            'pending_applications' => $pendingApplications,
            'applications_this_week' => $applicationsThisWeek,
            'applications_this_month' => $applicationsThisMonth,
            'response_rate' => $responseRate,
            'avg_days_to_response' => $avgDaysToResponse ? round($avgDaysToResponse, 1) : null,
            'yesterday_applications' => $applications->count(),
            'yesterday_outcomes' => $newOutcomes->count(),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendDigestJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
