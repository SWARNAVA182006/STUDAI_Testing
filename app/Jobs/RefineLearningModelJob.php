<?php

namespace App\Jobs;

use App\Services\AI\Scout\ContinuousLearningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class RefineLearningModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 300; // 5 minutes

    /**
     * The maximum number of seconds the job should be allowed to run.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes for comprehensive refinement

    /**
     * Company ID for refinement scope
     *
     * @var int
     */
    protected $companyId;

    /**
     * Refinement options
     *
     * @var array
     */
    protected $options;

    /**
     * Cache key for progress tracking
     *
     * @var string
     */
    protected $progressCacheKey;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param array $options
     * @return void
     */
    public function __construct(int $companyId, array $options = [])
    {
        $this->companyId = $companyId;
        $this->options = array_merge([
            'refinement_type' => 'full', // full, criteria_only, predictions_only
            'include_predictions' => true,
            'analyze_patterns' => true,
            'update_weights' => true,
            'generate_insights' => true,
        ], $options);
        
        $this->progressCacheKey = "learning_refinement_progress:company_{$companyId}";
    }

    /**
     * Execute the job.
     *
     * @param ContinuousLearningService $learningService
     * @return void
     */
    public function handle(ContinuousLearningService $learningService)
    {
        try {
            Log::info('Starting learning model refinement job', [
                'company_id' => $this->companyId,
                'options' => $this->options
            ]);

            $this->updateProgress(0, 'Initializing model refinement...');

            $refinementType = $this->options['refinement_type'];
            $results = [];

            // Step 1: Analyze success patterns (20%)
            if ($refinementType === 'full' || $this->options['analyze_patterns']) {
                $this->updateProgress(10, 'Analyzing success and failure patterns...');
                
                $patternResults = $learningService->analyzeSuccessPatterns($this->companyId);
                $results['patterns_analyzed'] = $patternResults['patterns_found'] ?? 0;
                
                Log::info('Success patterns analyzed', [
                    'company_id' => $this->companyId,
                    'patterns_found' => $results['patterns_analyzed']
                ]);

                $this->updateProgress(20, 'Pattern analysis complete');
            }

            // Step 2: Refine assessment criteria (40%)
            if ($refinementType === 'full' || $refinementType === 'criteria_only' || $this->options['update_weights']) {
                $this->updateProgress(30, 'Refining assessment criteria weights...');
                
                $refinementResults = $learningService->refineAssessmentCriteria($this->companyId);
                $results['criteria_refined'] = $refinementResults['refinements_made'] ?? 0;
                $results['accuracy_improvement'] = $refinementResults['accuracy_improvement'] ?? 0;
                
                Log::info('Criteria refinement complete', [
                    'company_id' => $this->companyId,
                    'refinements_made' => $results['criteria_refined'],
                    'accuracy_improvement' => $results['accuracy_improvement']
                ]);

                $this->updateProgress(40, 'Criteria refinement complete');
            }

            // Step 3: Learn from manager overrides (60%)
            if ($refinementType === 'full') {
                $this->updateProgress(50, 'Learning from hiring manager decisions...');
                
                $overrideResults = $learningService->analyzeManagerOverrides($this->companyId);
                $results['preferences_learned'] = $overrideResults['preferences_identified'] ?? 0;
                
                Log::info('Manager override analysis complete', [
                    'company_id' => $this->companyId,
                    'preferences_learned' => $results['preferences_learned']
                ]);

                $this->updateProgress(60, 'Manager preference learning complete');
            }

            // Step 4: Identify emerging skills (75%)
            if ($refinementType === 'full' || $this->options['analyze_patterns']) {
                $this->updateProgress(65, 'Identifying emerging skills and qualities...');
                
                $emergingSkills = $learningService->identifyEmergingSkills($this->companyId);
                $results['emerging_skills_found'] = count($emergingSkills);
                
                Log::info('Emerging skills identified', [
                    'company_id' => $this->companyId,
                    'skills_found' => $results['emerging_skills_found']
                ]);

                $this->updateProgress(75, 'Emerging skills identification complete');
            }

            // Step 5: Generate talent predictions (90%)
            if ($refinementType === 'full' || $refinementType === 'predictions_only' || $this->options['include_predictions']) {
                $this->updateProgress(80, 'Generating future talent need predictions...');
                
                $predictionResults = $learningService->generateTalentPredictions($this->companyId);
                $results['predictions_generated'] = count($predictionResults);
                
                Log::info('Talent predictions generated', [
                    'company_id' => $this->companyId,
                    'predictions_count' => $results['predictions_generated']
                ]);

                $this->updateProgress(90, 'Talent predictions generated');
            }

            // Step 6: Generate comprehensive insights (100%)
            if ($refinementType === 'full' || $this->options['generate_insights']) {
                $this->updateProgress(95, 'Generating comprehensive insights...');
                
                $insightResults = $learningService->generateLearningInsights($this->companyId);
                $results['insights_cached'] = true;
                
                $this->updateProgress(98, 'Insights generation complete');
            }

            // Final statistics
            $finalStats = [
                'company_id' => $this->companyId,
                'refinement_type' => $refinementType,
                'completed_at' => now()->toISOString(),
                'results' => $results,
                'next_refinement_recommended' => now()->addDays(7)->toISOString(),
            ];

            Log::info('Learning model refinement completed successfully', $finalStats);

            $this->updateProgress(100, 'Refinement complete!', $finalStats);

            // Clear old cached insights to force reload
            $this->clearOldCache();

        } catch (Exception $e) {
            Log::error('Failed to refine learning model', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateProgress(-1, 'Refinement failed: ' . $e->getMessage(), [
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        Log::error('Learning refinement job failed permanently', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->updateProgress(-1, 'Refinement failed permanently', [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Update progress in cache for real-time tracking
     *
     * @param int $percentage Progress percentage (-1 for error, 0-100 for progress)
     * @param string $message Status message
     * @param array $data Additional data
     * @return void
     */
    protected function updateProgress(int $percentage, string $message, array $data = [])
    {
        $progressData = [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toISOString(),
            'company_id' => $this->companyId,
            'refinement_type' => $this->options['refinement_type'],
        ];

        if (!empty($data)) {
            $progressData = array_merge($progressData, $data);
        }

        // Cache for 2 hours
        Cache::put($this->progressCacheKey, $progressData, 7200);

        Log::debug('Learning refinement progress updated', $progressData);
    }

    /**
     * Clear old cached data to force reload
     *
     * @return void
     */
    protected function clearOldCache()
    {
        $cacheKeys = [
            "learning_insights:company_{$this->companyId}",
            "talent_predictions:company_{$this->companyId}",
            "success_patterns:company_{$this->companyId}",
            "emerging_skills:company_{$this->companyId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Log::debug('Cleared old learning cache', [
            'company_id' => $this->companyId,
            'keys_cleared' => count($cacheKeys)
        ]);
    }

    /**
     * Get current progress from cache
     *
     * @param int $companyId
     * @return array|null
     */
    public static function getProgress(int $companyId): ?array
    {
        $cacheKey = "learning_refinement_progress:company_{$companyId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear progress cache
     *
     * @param int $companyId
     * @return void
     */
    public static function clearProgress(int $companyId): void
    {
        $cacheKey = "learning_refinement_progress:company_{$companyId}";
        Cache::forget($cacheKey);
    }

    /**
     * Check if refinement is in progress
     *
     * @param int $companyId
     * @return bool
     */
    public static function isInProgress(int $companyId): bool
    {
        $progress = static::getProgress($companyId);
        
        if (!$progress) {
            return false;
        }

        $percentage = $progress['percentage'] ?? 0;
        $status = $progress['status'] ?? 'processing';

        return $percentage >= 0 && $percentage < 100 && $status !== 'failed';
    }

    /**
     * Get human-readable status
     *
     * @param int $companyId
     * @return string
     */
    public static function getStatus(int $companyId): string
    {
        $progress = static::getProgress($companyId);

        if (!$progress) {
            return 'not_started';
        }

        $percentage = $progress['percentage'] ?? 0;
        $status = $progress['status'] ?? 'processing';

        if ($percentage === 100 && isset($progress['completed_at'])) {
            return 'completed';
        }

        if ($percentage === -1 || $status === 'failed') {
            return 'failed';
        }

        if ($percentage === 0) {
            return 'queued';
        }

        return 'processing';
    }

    /**
     * Dispatch refinement job with progress tracking
     *
     * @param int $companyId
     * @param array $options
     * @return void
     */
    public static function dispatchWithProgress(int $companyId, array $options = []): void
    {
        // Initialize progress cache
        $cacheKey = "learning_refinement_progress:company_{$companyId}";
        Cache::put($cacheKey, [
            'percentage' => 0,
            'message' => 'Queued for refinement...',
            'updated_at' => now()->toISOString(),
            'company_id' => $companyId,
            'status' => 'queued'
        ], 7200);

        // Dispatch the job
        static::dispatch($companyId, $options);
    }

    /**
     * Schedule periodic refinement for all companies
     *
     * @return void
     */
    public static function schedulePeriodicRefinement(): void
    {
        // This would typically be called from a scheduled task
        // For now, just log the intent
        Log::info('Periodic learning refinement scheduled for all companies');

        // TODO: Implement logic to get all active companies and dispatch jobs
        // Company::active()->each(function($company) {
        //     static::dispatchWithProgress($company->id, [
        //         'refinement_type' => 'full',
        //         'include_predictions' => true
        //     ]);
        // });
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff()
    {
        // Exponential backoff: 5min, 15min
        return 300 * pow(3, $this->attempts() - 1);
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // Allow retries for up to 1 hour
        return now()->addHour();
    }

    /**
     * Get the tags for the job
     *
     * @return array
     */
    public function tags()
    {
        return [
            'learning_refinement',
            'scout',
            "company:{$this->companyId}",
            "type:{$this->options['refinement_type']}"
        ];
    }

    /**
     * Get estimated completion time based on refinement type
     *
     * @param string $refinementType
     * @return int Estimated seconds
     */
    public static function getEstimatedDuration(string $refinementType): int
    {
        $durations = [
            'full' => 600,           // 10 minutes
            'criteria_only' => 180,  // 3 minutes
            'predictions_only' => 240, // 4 minutes
        ];

        return $durations[$refinementType] ?? 300;
    }
}
