<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\AI\Scout\PredictiveAnalyticsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The application instance for which predictions are being updated.
     *
     * @var Application
     */
    protected Application $application;

    /**
     * Should predictions be force-refreshed (bypass cache)?
     *
     * @var bool
     */
    protected bool $forceRefresh;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds before the job should timeout.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param Application $application
     * @param bool $forceRefresh
     */
    public function __construct(Application $application, bool $forceRefresh = false)
    {
        $this->application = $application;
        $this->forceRefresh = $forceRefresh;
        $this->onQueue('predictions');
    }

    /**
     * Execute the job.
     *
     * @param PredictiveAnalyticsService $analyticsService
     * @return void
     */
    public function handle(PredictiveAnalyticsService $analyticsService): void
    {
        $progressKey = $this->getProgressKey();
        $options = ['force_refresh' => $this->forceRefresh];

        try {
            Log::info('Starting prediction update job', [
                'application_id' => $this->application->id,
                'job_id' => $this->job?->getJobId(),
                'force_refresh' => $this->forceRefresh,
            ]);

            $this->updateProgress($progressKey, 0, 'Initializing prediction update...');

            // Step 1: Recalculate success probability (20% progress)
            $this->updateProgress($progressKey, 10, 'Analyzing success factors...');
            $successPrediction = $analyticsService->predictSuccessProbability($this->application, $options);
            $this->persistSuccessPrediction($successPrediction);
            $this->updateProgress($progressKey, 20, 'Success probability calculated.');

            // Step 2: Update tenure forecast (40% progress)
            $this->updateProgress($progressKey, 25, 'Forecasting candidate tenure...');
            $tenureForecast = $analyticsService->forecastTenure($this->application, $options);
            $this->persistTenureForecast($tenureForecast);
            $this->updateProgress($progressKey, 40, 'Tenure forecast updated.');

            // Step 3: Refresh productivity estimate (60% progress)
            $this->updateProgress($progressKey, 45, 'Estimating time to productivity...');
            $productivityEstimate = $analyticsService->estimateTimeToProductivity($this->application, null, $options);
            $this->persistProductivityEstimate($productivityEstimate);
            $this->updateProgress($progressKey, 60, 'Productivity timeline estimated.');

            // Step 4: Reassess flight risks (80% progress)
            $this->updateProgress($progressKey, 65, 'Assessing flight risk indicators...');
            $flightRiskAssessment = $analyticsService->identifyFlightRisks($this->application, $options);
            $this->persistFlightRiskAssessment($flightRiskAssessment);
            $this->updateProgress($progressKey, 80, 'Flight risk assessment complete.');

            // Step 5: Generate development needs and career path (100% progress)
            $this->updateProgress($progressKey, 85, 'Generating development plan...');
            $analyticsService->predictDevelopmentNeeds($this->application, null, $options);
            $this->updateProgress($progressKey, 90, 'Development plan generated.');

            $this->updateProgress($progressKey, 95, 'Predicting career trajectory...');
            $analyticsService->predictCareerPath($this->application, null, $options);
            $this->updateProgress($progressKey, 100, 'All predictions updated successfully.');

            // Mark completion timestamp
            $this->recordCompletionTimestamp();

            Log::info('Prediction update completed successfully', [
                'application_id' => $this->application->id,
                'job_id' => $this->job?->getJobId(),
            ]);

        } catch (Exception $exception) {
            $this->updateProgress($progressKey, -1, 'Prediction update failed.');
            
            Log::error('Prediction update job failed', [
                'application_id' => $this->application->id,
                'job_id' => $this->job?->getJobId(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Prediction update job failed permanently', [
            'application_id' => $this->application->id,
            'job_id' => $this->job?->getJobId(),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        $progressKey = $this->getProgressKey();
        Cache::forget($progressKey);

        // Optionally notify relevant stakeholders
        // event(new PredictionUpdateFailedEvent($this->application, $exception));
    }

    /**
     * Persist success prediction to database.
     *
     * @param array $data
     * @return void
     */
    protected function persistSuccessPrediction(array $data): void
    {
        DB::table('success_predictions')->updateOrInsert(
            [
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
            ],
            [
                'job_id' => $this->application->job_id,
                'company_id' => $this->application->job->company_id,
                'success_probability' => $data['success_probability'] ?? 0,
                'confidence_score' => $data['confidence_score'] ?? 0,
                'success_category' => $data['success_category'] ?? 'moderate',
                'factor_scores' => json_encode($data['factor_scores'] ?? []),
                'key_strengths' => json_encode($data['key_strengths'] ?? []),
                'key_concerns' => json_encode($data['key_concerns'] ?? []),
                'ai_insights' => json_encode($data['ai_insights'] ?? []),
                'prediction_basis' => $data['prediction_basis'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
                'comparable_profiles' => json_encode($data['comparable_profiles'] ?? []),
                'predicted_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Persist tenure forecast to database.
     *
     * @param array $data
     * @return void
     */
    protected function persistTenureForecast(array $data): void
    {
        DB::table('tenure_forecasts')->updateOrInsert(
            [
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
            ],
            [
                'job_id' => $this->application->job_id,
                'company_id' => $this->application->job->company_id,
                'predicted_tenure_months' => $data['predicted_tenure_months'] ?? 0,
                'predicted_tenure_years' => $data['predicted_tenure_years'] ?? 0,
                'tenure_range' => json_encode($data['tenure_range'] ?? []),
                'player_type' => $data['player_type'] ?? 'moderate_risk',
                'confidence_level' => $data['confidence_level'] ?? 'moderate',
                'confidence_score' => $data['confidence_score'] ?? 0,
                'flight_risk_score' => $data['flight_risk_score'] ?? 0,
                'risk_category' => $data['risk_category'] ?? 'medium',
                'retention_factors' => json_encode($data['retention_factors'] ?? []),
                'risk_indicators' => json_encode($data['risk_indicators'] ?? []),
                'ai_insights' => json_encode($data['ai_insights'] ?? []),
                'recommendation' => $data['recommendation'] ?? null,
                'probability_curve' => json_encode($data['probability_curve'] ?? []),
                'is_flight_risk' => $data['is_flight_risk'] ?? false,
                'forecasted_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Persist productivity estimate to database.
     *
     * @param array $data
     * @return void
     */
    protected function persistProductivityEstimate(array $data): void
    {
        DB::table('productivity_estimates')->updateOrInsert(
            [
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
            ],
            [
                'job_id' => $this->application->job_id,
                'company_id' => $this->application->job->company_id,
                'estimated_weeks' => $data['estimated_weeks'] ?? 0,
                'productivity_category' => $data['productivity_category'] ?? 'average_ramp',
                'productivity_milestones' => json_encode($data['productivity_milestones'] ?? []),
                'learning_curve_factors' => json_encode($data['learning_curve_factors'] ?? []),
                'experience_gap_analysis' => json_encode($data['experience_gap_analysis'] ?? []),
                'support_requirements' => json_encode($data['support_requirements'] ?? []),
                'ai_insights' => json_encode($data['ai_insights'] ?? []),
                'recommendation' => $data['recommendation'] ?? null,
                'estimated_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Persist flight risk assessment to database.
     *
     * @param array $data
     * @return void
     */
    protected function persistFlightRiskAssessment(array $data): void
    {
        DB::table('flight_risk_assessments')->updateOrInsert(
            [
                'application_id' => $this->application->id,
                'user_id' => $this->application->user_id,
            ],
            [
                'job_id' => $this->application->job_id,
                'company_id' => $this->application->job->company_id,
                'risk_score' => $data['risk_score'] ?? 0,
                'risk_level' => $data['risk_level'] ?? 'medium',
                'risk_category' => $data['risk_category'] ?? 'short_term_flight',
                'risk_factors' => json_encode($data['risk_factors'] ?? []),
                'mitigation_strategies' => json_encode($data['mitigation_strategies'] ?? []),
                'ai_insights' => json_encode($data['ai_insights'] ?? []),
                'recommendation' => $data['recommendation'] ?? null,
                'assessed_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Update job progress in cache.
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
            'updated_at' => Carbon::now()->toIso8601String(),
        ], now()->addHours(2));
    }

    /**
     * Get the cache key for tracking progress.
     *
     * @return string
     */
    protected function getProgressKey(): string
    {
        return "prediction_update_progress_{$this->application->id}";
    }

    /**
     * Record completion timestamp for tracking purposes.
     *
     * @return void
     */
    protected function recordCompletionTimestamp(): void
    {
        Cache::put(
            "prediction_last_updated_{$this->application->id}",
            Carbon::now()->toIso8601String(),
            now()->addDays(30)
        );
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'predictions',
            'application:' . $this->application->id,
            'company:' . $this->application->job->company_id,
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        return $this->attempts() * 60; // 1 min, 2 min, 3 min
    }

    /**
     * Determine if the job should be retried based on the exception.
     *
     * @param Exception $exception
     * @return bool
     */
    public function shouldRetry(Exception $exception): bool
    {
        // Don't retry validation errors or permanent failures
        if (str_contains($exception->getMessage(), 'Application not found')) {
            return false;
        }

        if (str_contains($exception->getMessage(), 'Invalid application state')) {
            return false;
        }

        // Retry network/API errors
        return true;
    }

    /**
     * Dispatch batch prediction updates for multiple applications.
     *
     * @param array $applicationIds
     * @param bool $forceRefresh
     * @return void
     */
    public static function dispatchBatch(array $applicationIds, bool $forceRefresh = false): void
    {
        $applications = Application::with(['user', 'job.company'])
            ->whereIn('id', $applicationIds)
            ->get();

        foreach ($applications as $application) {
            self::dispatch($application, $forceRefresh)
                ->delay(now()->addSeconds(rand(1, 30))); // Stagger dispatches
        }

        Log::info('Batch prediction update dispatched', [
            'application_count' => $applications->count(),
            'force_refresh' => $forceRefresh,
        ]);
    }

    /**
     * Dispatch prediction updates for all active applications.
     *
     * @param bool $forceRefresh
     * @return void
     */
    public static function dispatchForAllActive(bool $forceRefresh = false): void
    {
        $applicationIds = Application::query()
            ->whereIn('status', ['under_review', 'interviewing', 'offer_extended'])
            ->whereHas('job', function ($query) {
                $query->where('status', 'published');
            })
            ->pluck('id')
            ->toArray();

        self::dispatchBatch($applicationIds, $forceRefresh);
    }

    /**
     * Get progress for a specific application's prediction update.
     *
     * @param int $applicationId
     * @return array|null
     */
    public static function getProgress(int $applicationId): ?array
    {
        return Cache::get("prediction_update_progress_{$applicationId}");
    }

    /**
     * Get the last update timestamp for an application's predictions.
     *
     * @param int $applicationId
     * @return Carbon|null
     */
    public static function getLastUpdated(int $applicationId): ?Carbon
    {
        $timestamp = Cache::get("prediction_last_updated_{$applicationId}");
        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Clear progress tracking for an application.
     *
     * @param int $applicationId
     * @return void
     */
    public static function clearProgress(int $applicationId): void
    {
        Cache::forget("prediction_update_progress_{$applicationId}");
    }
}
