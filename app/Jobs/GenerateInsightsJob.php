<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\MarketDataSnapshot;
use App\Models\UserMarketPosition;
use App\Services\AI\MarketIntelligenceService;
use App\Notifications\MarketInsightsDigestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Generate Insights Job
 * 
 * Runs weekly to generate deep AI insights, identify market shifts,
 * and send personalized digest emails to active users.
 */
class GenerateInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 3600; // 1 hour

    /**
     * Execute the job.
     */
    public function handle(MarketIntelligenceService $marketIntelligence): void
    {
        Log::info('GenerateInsightsJob: Starting weekly insights generation');

        try {
            // Generate global market insights
            $globalInsights = $this->generateGlobalInsights($marketIntelligence);
            
            // Send personalized digest emails
            $this->sendPersonalizedDigests($globalInsights);
            
            Log::info('GenerateInsightsJob: Weekly insights generation completed');
            
        } catch (Exception $e) {
            Log::error('GenerateInsightsJob: Failed to generate insights', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate global market insights
     */
    protected function generateGlobalInsights(MarketIntelligenceService $marketIntelligence): array
    {
        Log::info('GenerateInsightsJob: Generating global market insights');
        
        // Get latest market overview
        $overview = $marketIntelligence->getMarketOverview();
        
        // Compare with last week's data
        $lastWeekSnapshot = MarketDataSnapshot::where('role', 'Global')
            ->where('snapshot_date', '>=', now()->subWeek()->toDateString())
            ->where('snapshot_date', '<', now()->toDateString())
            ->latest('snapshot_date')
            ->first();
        
        $thisWeekSnapshot = MarketDataSnapshot::where('role', 'Global')
            ->latest('snapshot_date')
            ->first();
        
        // Identify major market shifts
        $shifts = [];
        
        if ($lastWeekSnapshot && $thisWeekSnapshot) {
            // Demand shift
            $demandChange = $thisWeekSnapshot->demand_score - $lastWeekSnapshot->demand_score;
            if (abs($demandChange) > 10) {
                $shifts[] = [
                    'type' => 'demand',
                    'direction' => $demandChange > 0 ? 'increase' : 'decrease',
                    'magnitude' => abs($demandChange),
                    'message' => "Overall job market demand " . ($demandChange > 0 ? 'increased' : 'decreased') . " by " . round(abs($demandChange), 1) . " points this week.",
                ];
            }
            
            // Salary shift
            $salaryChange = (($thisWeekSnapshot->avg_salary - $lastWeekSnapshot->avg_salary) / $lastWeekSnapshot->avg_salary) * 100;
            if (abs($salaryChange) > 5) {
                $shifts[] = [
                    'type' => 'salary',
                    'direction' => $salaryChange > 0 ? 'increase' : 'decrease',
                    'magnitude' => abs($salaryChange),
                    'message' => "Average salaries " . ($salaryChange > 0 ? 'increased' : 'decreased') . " by " . round(abs($salaryChange), 1) . "% this week.",
                ];
            }
            
            // New emerging skills
            $newSkills = array_diff(
                $thisWeekSnapshot->emerging_skills ?? [],
                $lastWeekSnapshot->emerging_skills ?? []
            );
            
            if (!empty($newSkills)) {
                $shifts[] = [
                    'type' => 'skills',
                    'direction' => 'new',
                    'data' => $newSkills,
                    'message' => "New emerging skills detected: " . implode(', ', array_slice($newSkills, 0, 5)),
                ];
            }
        }
        
        return [
            'overview' => $overview,
            'shifts' => $shifts,
            'top_roles' => $overview['top_roles'] ?? [],
            'emerging_skills' => $overview['emerging_skills'] ?? [],
            'hot_locations' => $overview['top_locations'] ?? [],
        ];
    }

    /**
     * Send personalized digest emails to active users
     */
    protected function sendPersonalizedDigests(array $globalInsights): void
    {
        Log::info('GenerateInsightsJob: Sending personalized digest emails');
        
        // Get users who opted in for weekly digest
        $users = User::whereHas('profile')
            ->where('account_type', 'job_seeker')
            ->whereNotNull('email_verified_at')
            // ->where('email_preferences->weekly_digest', true) // Uncomment when preference system is ready
            ->get();
        
        $sent = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            try {
                // Get user's latest market position
                $position = UserMarketPosition::where('user_id', $user->id)
                    ->latest('updated_at')
                    ->first();
                
                if (!$position) {
                    continue; // Skip users without positioning data
                }
                
                // Build personalized insights
                $personalizedInsights = $this->buildPersonalizedInsights($user, $position, $globalInsights);
                
                // Send notification
                $user->notify(new MarketInsightsDigestNotification($personalizedInsights));
                
                $sent++;
                
                if ($sent % 100 == 0) {
                    Log::info('GenerateInsightsJob: Email progress update', [
                        'sent' => $sent,
                        'total' => $users->count(),
                    ]);
                }
                
            } catch (Exception $e) {
                $failed++;
                Log::warning('GenerateInsightsJob: Failed to send digest email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('GenerateInsightsJob: Digest emails sent', [
            'total_users' => $users->count(),
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    /**
     * Build personalized insights for user
     */
    protected function buildPersonalizedInsights(
        User $user,
        UserMarketPosition $position,
        array $globalInsights
    ): array {
        $insights = [
            'user_name' => $user->name,
            'readiness_score' => $position->readiness_score,
            'readiness_change' => $this->calculateReadinessChange($user),
            'percentile_ranking' => $position->overall_percentile,
            'competitive_advantages' => $position->competitive_advantages ?? [],
            'skill_gaps' => $position->skill_gaps ?? [],
            'recommended_actions' => $position->recommendations ?? [],
            'global_shifts' => $globalInsights['shifts'] ?? [],
            'trending_roles' => [],
            'salary_insights' => [],
        ];
        
        // Find trending roles matching user's profile
        $userSkills = $user->profile->skills ?? [];
        foreach ($globalInsights['top_roles'] ?? [] as $role) {
            $roleSkills = $role['required_skills'] ?? [];
            $matchScore = count(array_intersect($userSkills, $roleSkills));
            
            if ($matchScore >= 2) {
                $insights['trending_roles'][] = [
                    'title' => $role['title'],
                    'job_count' => $role['job_count'],
                    'match_score' => $matchScore,
                ];
            }
        }
        
        // Add salary insights
        $insights['salary_insights'] = [
            'current_percentile' => $position->compensation_percentile,
            'market_median' => $globalInsights['overview']['avg_salary'] ?? 0,
            'potential_increase' => $position->potential_salary_increase ?? 0,
        ];
        
        return $insights;
    }

    /**
     * Calculate readiness score change from last week
     */
    protected function calculateReadinessChange(User $user): float
    {
        $current = UserMarketPosition::where('user_id', $user->id)
            ->latest('updated_at')
            ->first();
        
        $lastWeek = UserMarketPosition::where('user_id', $user->id)
            ->where('updated_at', '>=', now()->subWeek())
            ->where('updated_at', '<', now()->subDay())
            ->latest('updated_at')
            ->first();
        
        if (!$current || !$lastWeek) {
            return 0;
        }
        
        return $current->readiness_score - $lastWeek->readiness_score;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('GenerateInsightsJob: Job failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }
}
