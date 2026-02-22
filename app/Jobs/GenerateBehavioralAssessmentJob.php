<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Job;
use App\Models\BehavioralAssessment;
use App\Services\AI\Scout\BehavioralIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateBehavioralAssessmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * The maximum number of seconds the job should be allowed to run.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes for complex AI processing

    /**
     * Application ID for the assessment
     *
     * @var int
     */
    protected $applicationId;

    /**
     * Job ID for the assessment
     *
     * @var int
     */
    protected $jobId;

    /**
     * Assessment generation options
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
     * User ID (for notifications)
     *
     * @var int
     */
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @param int $applicationId
     * @param int $jobId
     * @param array $options
     * @param int|null $userId
     * @return void
     */
    public function __construct(int $applicationId, int $jobId, array $options = [], ?int $userId = null)
    {
        $this->applicationId = $applicationId;
        $this->jobId = $jobId;
        $this->options = $options;
        $this->userId = $userId;
        $this->progressCacheKey = "behavioral_assessment_progress:{$applicationId}:{$jobId}";
    }

    /**
     * Execute the job.
     *
     * @param BehavioralIntelligenceService $behavioralService
     * @return void
     */
    public function handle(BehavioralIntelligenceService $behavioralService)
    {
        try {
            Log::info('Starting behavioral assessment generation job', [
                'application_id' => $this->applicationId,
                'job_id' => $this->jobId,
                'options' => $this->options
            ]);

            // Update progress: Starting
            $this->updateProgress(0, 'Starting assessment generation...');

            // Validate application exists
            $application = Application::with(['user', 'job.company'])
                ->findOrFail($this->applicationId);

            if ($application->job_id !== $this->jobId) {
                throw new Exception('Application does not belong to specified job');
            }

            // Update progress: Extracting company culture
            $this->updateProgress(20, 'Analyzing company culture and values...');

            // Check for existing assessment
            $existingAssessment = BehavioralAssessment::where('application_id', $this->applicationId)
                ->where('job_id', $this->jobId)
                ->first();

            if ($existingAssessment) {
                Log::warning('Behavioral assessment already exists', [
                    'assessment_id' => $existingAssessment->id
                ]);
                
                $this->updateProgress(100, 'Assessment already exists', [
                    'assessment_id' => $existingAssessment->id,
                    'status' => 'duplicate'
                ]);
                
                return;
            }

            // Update progress: Generating scenarios
            $this->updateProgress(40, 'Generating workplace scenarios with AI...');

            // Generate the assessment
            $assessment = $behavioralService->generateBehavioralAssessment(
                $this->applicationId,
                $this->jobId,
                $this->options
            );

            // Update progress: Finalizing
            $this->updateProgress(80, 'Finalizing assessment setup...');

            Log::info('Behavioral assessment generated successfully', [
                'assessment_id' => $assessment->id,
                'scenario_count' => $assessment->scenario_count
            ]);

            // Update progress: Complete
            $this->updateProgress(100, 'Assessment ready!', [
                'assessment_id' => $assessment->id,
                'scenario_count' => $assessment->scenario_count,
                'status' => 'success'
            ]);

            // Send notification to user if applicable
            if ($this->userId) {
                $this->notifyUser($assessment);
            }

        } catch (Exception $e) {
            Log::error('Failed to generate behavioral assessment', [
                'application_id' => $this->applicationId,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress: Failed
            $this->updateProgress(-1, 'Assessment generation failed: ' . $e->getMessage(), [
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);

            // Re-throw to trigger retry mechanism
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
        Log::error('Behavioral assessment job failed permanently', [
            'application_id' => $this->applicationId,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update progress: Permanently failed
        $this->updateProgress(-1, 'Assessment generation failed permanently', [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Notify user of failure if applicable
        if ($this->userId) {
            $this->notifyUserOfFailure($exception);
        }
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
            'application_id' => $this->applicationId,
            'job_id' => $this->jobId,
        ];

        // Merge additional data
        if (!empty($data)) {
            $progressData = array_merge($progressData, $data);
        }

        // Cache for 1 hour (enough time for user to check status)
        Cache::put($this->progressCacheKey, $progressData, 3600);

        Log::debug('Behavioral assessment progress updated', $progressData);
    }

    /**
     * Get current progress from cache
     *
     * @param int $applicationId
     * @param int $jobId
     * @return array|null
     */
    public static function getProgress(int $applicationId, int $jobId): ?array
    {
        $cacheKey = "behavioral_assessment_progress:{$applicationId}:{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear progress cache
     *
     * @param int $applicationId
     * @param int $jobId
     * @return void
     */
    public static function clearProgress(int $applicationId, int $jobId): void
    {
        $cacheKey = "behavioral_assessment_progress:{$applicationId}:{$jobId}";
        Cache::forget($cacheKey);
    }

    /**
     * Notify user of successful assessment generation
     *
     * @param BehavioralAssessment $assessment
     * @return void
     */
    protected function notifyUser(BehavioralAssessment $assessment)
    {
        try {
            // TODO: Implement notification logic (email, in-app notification, etc.)
            // For now, just log
            Log::info('Notifying user of behavioral assessment completion', [
                'user_id' => $this->userId,
                'assessment_id' => $assessment->id
            ]);

            // Example notification structure:
            // Notification::send(
            //     User::find($this->userId),
            //     new BehavioralAssessmentReadyNotification($assessment)
            // );

        } catch (Exception $e) {
            // Don't fail the job if notification fails
            Log::warning('Failed to notify user of assessment completion', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify user of assessment generation failure
     *
     * @param Exception $exception
     * @return void
     */
    protected function notifyUserOfFailure(Exception $exception)
    {
        try {
            // TODO: Implement failure notification logic
            Log::info('Notifying user of behavioral assessment failure', [
                'user_id' => $this->userId,
                'error' => $exception->getMessage()
            ]);

            // Example notification structure:
            // Notification::send(
            //     User::find($this->userId),
            //     new BehavioralAssessmentFailedNotification($exception)
            // );

        } catch (Exception $e) {
            Log::warning('Failed to notify user of assessment failure', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff()
    {
        // Exponential backoff: 30s, 90s, 270s (30 * 3^attempt)
        return 30 * pow(3, $this->attempts() - 1);
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // Allow retries for up to 30 minutes
        return now()->addMinutes(30);
    }

    /**
     * Get the tags for the job (for monitoring/debugging)
     *
     * @return array
     */
    public function tags()
    {
        return [
            'behavioral_assessment',
            'scout',
            "application:{$this->applicationId}",
            "job:{$this->jobId}",
            "user:{$this->userId}"
        ];
    }

    /**
     * Dispatch the job with progress tracking enabled
     *
     * @param int $applicationId
     * @param int $jobId
     * @param array $options
     * @param int|null $userId
     * @return void
     */
    public static function dispatchWithProgress(
        int $applicationId,
        int $jobId,
        array $options = [],
        ?int $userId = null
    ): void {
        // Initialize progress cache
        $cacheKey = "behavioral_assessment_progress:{$applicationId}:{$jobId}";
        Cache::put($cacheKey, [
            'percentage' => 0,
            'message' => 'Queued for processing...',
            'updated_at' => now()->toISOString(),
            'application_id' => $applicationId,
            'job_id' => $jobId,
            'status' => 'queued'
        ], 3600);

        // Dispatch the job
        static::dispatch($applicationId, $jobId, $options, $userId);
    }

    /**
     * Check if assessment generation is in progress
     *
     * @param int $applicationId
     * @param int $jobId
     * @return bool
     */
    public static function isInProgress(int $applicationId, int $jobId): bool
    {
        $progress = static::getProgress($applicationId, $jobId);
        
        if (!$progress) {
            return false;
        }

        // Consider in progress if percentage is between 0 and 99
        return isset($progress['percentage']) 
            && $progress['percentage'] >= 0 
            && $progress['percentage'] < 100
            && ($progress['status'] ?? 'queued') !== 'failed';
    }

    /**
     * Get human-readable status
     *
     * @param int $applicationId
     * @param int $jobId
     * @return string
     */
    public static function getStatus(int $applicationId, int $jobId): string
    {
        $progress = static::getProgress($applicationId, $jobId);

        if (!$progress) {
            return 'not_started';
        }

        $percentage = $progress['percentage'] ?? 0;
        $status = $progress['status'] ?? 'processing';

        if ($percentage === 100 && $status === 'success') {
            return 'completed';
        }

        if ($percentage === -1 || $status === 'failed') {
            return 'failed';
        }

        if ($status === 'duplicate') {
            return 'duplicate';
        }

        if ($percentage === 0 && $status === 'queued') {
            return 'queued';
        }

        return 'processing';
    }
}
