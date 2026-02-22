<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\EmployerBrandScore;
use App\Services\AI\Scout\CandidateExperienceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackEmployerBrandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company to track brand for
     */
    protected Company $company;

    /**
     * Period to calculate (weekly, monthly, quarterly)
     */
    protected string $period;

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
    public function __construct(Company $company, string $period = 'monthly')
    {
        $this->company = $company;
        $this->period = $period;
        $this->onQueue('brand-tracking');
    }

    /**
     * Execute the job.
     */
    public function handle(CandidateExperienceService $candidateExperience): void
    {
        $startTime = now();
        $progressKey = "brand_tracking_progress_{$this->company->id}";
        
        try {
            Log::info('Starting employer brand tracking', [
                'company_id' => $this->company->id,
                'period' => $this->period,
            ]);

            // Initialize progress
            $this->updateProgress($progressKey, 0, 'Initializing brand tracking...');

            // Step 1: Determine date range (20%)
            $this->updateProgress($progressKey, 10, 'Calculating period...');
            [$startDate, $endDate] = $this->getDateRange();
            
            $this->updateProgress($progressKey, 20, "Analyzing period: {$startDate->format('M d')} - {$endDate->format('M d, Y')}");

            // Step 2: Calculate brand score (60%)
            $this->updateProgress($progressKey, 30, 'Calculating employer brand score...');
            $brandScore = $candidateExperience->calculateEmployerBrandScore(
                $this->company,
                $startDate,
                $endDate
            );
            
            $this->updateProgress($progressKey, 60, "Brand score calculated: {$brandScore->overall_brand_score}");

            // Step 3: Analyze trends (80%)
            $this->updateProgress($progressKey, 70, 'Analyzing trends...');
            $trendAnalysis = $this->analyzeTrends($brandScore);
            
            $this->updateProgress($progressKey, 80, "Trend: {$brandScore->trend}");

            // Step 4: Identify risks (90%)
            $this->updateProgress($progressKey, 85, 'Identifying brand risks...');
            $risks = $candidateExperience->identifyBrandRisks($this->company);
            
            $riskCount = count($risks);
            $this->updateProgress($progressKey, 90, "Identified {$riskCount} potential risks");

            // Step 5: Generate recommendations (100%)
            $this->updateProgress($progressKey, 95, 'Generating recommendations...');
            $recommendations = $this->generateRecommendations($brandScore, $risks);
            
            $this->updateProgress($progressKey, 100, 'Brand tracking completed');

            $duration = now()->diffInSeconds($startTime);

            Log::info('Employer brand tracking completed', [
                'company_id' => $this->company->id,
                'period' => $this->period,
                'duration_seconds' => $duration,
                'overall_score' => $brandScore->overall_brand_score,
                'trend' => $brandScore->trend,
                'brand_health' => $brandScore->brand_health_status,
                'risks_identified' => $riskCount,
                'recommendations_generated' => count($recommendations),
            ]);

            // Store results for dashboard
            $this->storeResults($brandScore, $trendAnalysis, $risks, $recommendations);

            // Store completion timestamp
            Cache::put("brand_tracking_last_run_{$this->company->id}", now(), 86400);

            // Send alerts if needed
            $this->sendAlertsIfNeeded($brandScore, $risks);

        } catch (\Exception $e) {
            Log::error('Failed to track employer brand', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress($progressKey, -1, 'Tracking failed: ' . $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Get date range based on period
     *
     * @return array [startDate, endDate]
     */
    protected function getDateRange(): array
    {
        $endDate = now();
        
        $startDate = match($this->period) {
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            'quarterly' => now()->subMonths(3),
            'yearly' => now()->subYear(),
            default => now()->subMonth(),
        };

        return [$startDate, $endDate];
    }

    /**
     * Analyze brand score trends
     *
     * @param EmployerBrandScore $currentScore
     * @return array
     */
    protected function analyzeTrends(EmployerBrandScore $currentScore): array
    {
        // Get previous period's score
        $previousScore = EmployerBrandScore::where('company_id', $this->company->id)
            ->where('id', '!=', $currentScore->id)
            ->where('measurement_period_end', '<', $currentScore->measurement_period_start)
            ->orderBy('measurement_period_end', 'desc')
            ->first();

        if (!$previousScore) {
            return [
                'has_history' => false,
                'message' => 'No historical data available for comparison',
            ];
        }

        $scoreDelta = $currentScore->overall_brand_score - $previousScore->overall_brand_score;
        $npsDelta = $currentScore->nps_score - $previousScore->nps_score;

        $componentChanges = [
            'application_experience' => $currentScore->application_experience_score - $previousScore->application_experience_score,
            'communication' => $currentScore->communication_score - $previousScore->communication_score,
            'interview_experience' => $currentScore->interview_experience_score - $previousScore->interview_experience_score,
            'feedback_quality' => $currentScore->feedback_quality_score - $previousScore->feedback_quality_score,
            'transparency' => $currentScore->transparency_score - $previousScore->transparency_score,
            'respect' => $currentScore->respect_score - $previousScore->respect_score,
        ];

        // Find most improved and most declined components
        arsort($componentChanges);
        $mostImproved = array_key_first($componentChanges);
        $componentChanges = array_reverse($componentChanges, true);
        $mostDeclined = array_key_first($componentChanges);

        return [
            'has_history' => true,
            'overall_change' => $scoreDelta,
            'nps_change' => $npsDelta,
            'trend_direction' => $currentScore->trend,
            'most_improved_component' => $mostImproved,
            'most_declined_component' => $mostDeclined,
            'component_changes' => $componentChanges,
            'previous_period' => [
                'start' => $previousScore->measurement_period_start->format('Y-m-d'),
                'end' => $previousScore->measurement_period_end->format('Y-m-d'),
                'score' => $previousScore->overall_brand_score,
            ],
        ];
    }

    /**
     * Generate recommendations based on score and risks
     *
     * @param EmployerBrandScore $brandScore
     * @param array $risks
     * @return array
     */
    protected function generateRecommendations(EmployerBrandScore $brandScore, array $risks): array
    {
        $recommendations = [];

        // Overall brand health recommendations
        if ($brandScore->brand_health_status === 'critical' || $brandScore->brand_health_status === 'poor') {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'overall_health',
                'title' => 'Critical Brand Health Issue',
                'description' => 'Your employer brand score is critically low. Immediate action is required to improve candidate experience.',
                'actions' => [
                    'Conduct a full audit of your hiring process',
                    'Review all candidate touchpoints for pain points',
                    'Implement immediate improvements to communication frequency',
                    'Provide constructive feedback to all candidates',
                ],
            ];
        }

        // Component-specific recommendations
        if ($brandScore->communication_score < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'communication',
                'title' => 'Improve Communication Frequency',
                'description' => 'Candidates report poor communication during the hiring process.',
                'actions' => [
                    'Send status updates within 48 hours of application',
                    'Set up automated weekly update emails',
                    'Provide clear timelines at each stage',
                    'Assign dedicated recruiters to maintain contact',
                ],
            ];
        }

        if ($brandScore->transparency_score < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'transparency',
                'title' => 'Increase Process Transparency',
                'description' => 'Candidates feel unclear about the hiring process and timeline.',
                'actions' => [
                    'Share detailed hiring stage information upfront',
                    'Provide expected timelines for each stage',
                    'Explain decision criteria to candidates',
                    'Be upfront about salary ranges and benefits',
                ],
            ];
        }

        if ($brandScore->feedback_quality_score < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'feedback',
                'title' => 'Enhance Candidate Feedback',
                'description' => 'Candidates desire more constructive feedback after interviews.',
                'actions' => [
                    'Provide personalized feedback to all interviewed candidates',
                    'Use AI to generate constructive rejection letters',
                    'Offer feedback calls for final-round candidates',
                    'Create feedback templates that are specific and helpful',
                ],
            ];
        }

        if ($brandScore->interview_experience_score < 60) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'interview_experience',
                'title' => 'Improve Interview Experience',
                'description' => 'Candidates report poor interview experiences.',
                'actions' => [
                    'Train interviewers on best practices',
                    'Ensure interviews start on time',
                    'Provide clear interview formats and expectations',
                    'Follow up promptly after interviews',
                ],
            ];
        }

        // NPS-based recommendations
        if ($brandScore->nps_score < 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'nps',
                'title' => 'Negative Net Promoter Score',
                'description' => 'More candidates are detractors than promoters of your brand.',
                'actions' => [
                    'Analyze feedback from detractors to identify common issues',
                    'Implement changes based on recurring negative themes',
                    'Follow up with detractors to understand their concerns',
                    'Create a candidate experience improvement task force',
                ],
            ];
        }

        // Trend-based recommendations
        if ($brandScore->trend === 'declining') {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'trend',
                'title' => 'Declining Brand Health Trend',
                'description' => 'Your employer brand score is declining compared to the previous period.',
                'actions' => [
                    'Identify what changed in your hiring process recently',
                    'Review negative feedback themes from recent candidates',
                    'Compare current vs. previous period touchpoints',
                    'Implement quick wins to reverse the trend',
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Store tracking results
     *
     * @param EmployerBrandScore $brandScore
     * @param array $trendAnalysis
     * @param array $risks
     * @param array $recommendations
     * @return void
     */
    protected function storeResults(
        EmployerBrandScore $brandScore,
        array $trendAnalysis,
        array $risks,
        array $recommendations
    ): void {
        $results = [
            'brand_score' => $brandScore->toArray(),
            'trend_analysis' => $trendAnalysis,
            'risks' => $risks,
            'recommendations' => $recommendations,
            'tracked_at' => now()->toIso8601String(),
        ];

        Cache::put(
            "brand_tracking_results_{$this->company->id}",
            $results,
            86400 * 7 // 7 days
        );
    }

    /**
     * Send alerts if brand health is critical
     *
     * @param EmployerBrandScore $brandScore
     * @param array $risks
     * @return void
     */
    protected function sendAlertsIfNeeded(EmployerBrandScore $brandScore, array $risks): void
    {
        // Send alert if brand health is critical
        if ($brandScore->brand_health_status === 'critical') {
            Log::alert('Critical employer brand health detected', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'overall_score' => $brandScore->overall_brand_score,
                'nps_score' => $brandScore->nps_score,
                'risks' => $risks,
            ]);

            // TODO: Send email notification to company administrators
            // TODO: Create in-app notification
        }

        // Send warning if declining trend
        if ($brandScore->trend === 'declining' && $brandScore->overall_brand_score < 60) {
            Log::warning('Declining employer brand detected', [
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'overall_score' => $brandScore->overall_brand_score,
                'trend' => $brandScore->trend,
            ]);

            // TODO: Send email notification to company administrators
        }
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
        return Cache::get("brand_tracking_progress_{$companyId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TrackEmployerBrandJob failed', [
            'company_id' => $this->company->id,
            'period' => $this->period,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators
    }
}
