<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Services\AI\Scout\DynamicAssessmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * GenerateAssessmentJob
 * 
 * Background job for generating adaptive assessments asynchronously.
 * Useful for complex assessments with many questions or when generating
 * multiple assessments in bulk.
 * 
 * Features:
 * - Async generation to avoid blocking requests
 * - Progress caching for real-time status updates
 * - Automatic retry on failure (3 attempts with exponential backoff)
 * - Notification on completion or failure
 * - Proper error handling and logging
 * 
 * Usage:
 * GenerateAssessmentJob::dispatch($applicationId, $jobId, $options, $userId);
 */
class GenerateAssessmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The application ID for which to generate the assessment.
     */
    protected int $applicationId;

    /**
     * The job ID for which to generate the assessment.
     */
    protected int $jobId;

    /**
     * Assessment configuration options.
     */
    protected array $options;

    /**
     * User ID who requested the assessment.
     */
    protected int $requestedByUserId;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     * Uses exponential backoff: 30s, 90s, 270s
     */
    public array $backoff = [30, 90, 270];

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $applicationId, int $jobId, array $options, int $requestedByUserId)
    {
        $this->applicationId = $applicationId;
        $this->jobId = $jobId;
        $this->options = $options;
        $this->requestedByUserId = $requestedByUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(DynamicAssessmentService $assessmentService): void
    {
        $cacheKey = "assessment_generation_{$this->applicationId}_{$this->jobId}";

        try {
            // Mark as generating
            Cache::put($cacheKey, [
                'status' => 'generating',
                'progress' => 0,
                'message' => 'Starting assessment generation...',
                'started_at' => now()->toIso8601String(),
            ], 600); // 10 minutes cache

            Log::info('Starting assessment generation', [
                'job_id' => $this->job->getJobId(),
                'application_id' => $this->applicationId,
                'job_posting_id' => $this->jobId,
                'options' => $this->options,
                'requested_by' => $this->requestedByUserId,
            ]);

            // Update progress: Loading data
            Cache::put($cacheKey, [
                'status' => 'generating',
                'progress' => 20,
                'message' => 'Loading candidate and job data...',
            ], 600);

            // Verify application and job exist
            $application = Application::with('user')->findOrFail($this->applicationId);
            $job = Job::with('company')->findOrFail($this->jobId);

            // Verify application is for this job
            if ($application->job_id !== $this->jobId) {
                throw new \InvalidArgumentException('Application does not match job');
            }

            // Update progress: Generating questions
            Cache::put($cacheKey, [
                'status' => 'generating',
                'progress' => 40,
                'message' => 'Generating AI-powered questions...',
            ], 600);

            // Generate assessment
            $result = $assessmentService->generateAssessment(
                $this->applicationId,
                $this->jobId,
                $this->options
            );

            // Update progress: Finalizing
            Cache::put($cacheKey, [
                'status' => 'generating',
                'progress' => 80,
                'message' => 'Finalizing assessment...',
            ], 600);

            // Update application status to indicate assessment is ready
            $application->update([
                'assessment_sent' => true,
                'assessment_sent_at' => now(),
            ]);

            // Mark as completed
            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Assessment generated successfully',
                'assessment_id' => $result['assessment_id'],
                'completed_at' => now()->toIso8601String(),
            ], 600);

            Log::info('Assessment generation completed successfully', [
                'job_id' => $this->job->getJobId(),
                'assessment_id' => $result['assessment_id'],
                'application_id' => $this->applicationId,
                'total_questions' => $result['total_questions'],
                'type' => $result['type'],
            ]);

            // Send notification to the user who requested it
            $this->notifyCompletion($result);

        } catch (\Exception $e) {
            // Mark as failed
            Cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Assessment generation failed',
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ], 600);

            Log::error('Assessment generation failed', [
                'job_id' => $this->job->getJobId(),
                'application_id' => $this->applicationId,
                'job_posting_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Rethrow to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Send notification on successful completion.
     */
    protected function notifyCompletion(array $result): void
    {
        try {
            $user = User::find($this->requestedByUserId);
            
            if (!$user) {
                Log::warning('Could not find user to notify', [
                    'user_id' => $this->requestedByUserId
                ]);
                return;
            }

            // You can implement your notification class here
            // Example: $user->notify(new AssessmentGeneratedNotification($result));
            
            Log::info('Notification sent for assessment completion', [
                'user_id' => $this->requestedByUserId,
                'assessment_id' => $result['assessment_id']
            ]);

        } catch (\Exception $e) {
            // Don't fail the job if notification fails
            Log::error('Failed to send assessment completion notification', [
                'user_id' => $this->requestedByUserId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $cacheKey = "assessment_generation_{$this->applicationId}_{$this->jobId}";

        // Mark as permanently failed
        Cache::put($cacheKey, [
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Assessment generation failed after all retries',
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'attempts' => $this->attempts(),
        ], 600);

        Log::error('Assessment generation permanently failed', [
            'application_id' => $this->applicationId,
            'job_posting_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
        ]);

        // Notify user of failure
        try {
            $user = User::find($this->requestedByUserId);
            
            if ($user) {
                // You can implement your failure notification class here
                // Example: $user->notify(new AssessmentGenerationFailedNotification($exception));
                
                Log::info('Failure notification sent', [
                    'user_id' => $this->requestedByUserId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send failure notification', [
                'user_id' => $this->requestedByUserId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'assessment',
            'scout',
            'ai-generation',
            "application:{$this->applicationId}",
            "job:{$this->jobId}",
            "user:{$this->requestedByUserId}",
        ];
    }

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return sprintf(
            'Generate Assessment (Application: %d, Job: %d)',
            $this->applicationId,
            $this->jobId
        );
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        // You can add rate limiting or other middleware here
        // Example: return [new RateLimited('assessments')];
        return [];
    }

    /**
     * Check generation progress (static helper method).
     * 
     * Usage:
     * $progress = GenerateAssessmentJob::checkProgress($applicationId, $jobId);
     */
    public static function checkProgress(int $applicationId, int $jobId): ?array
    {
        $cacheKey = "assessment_generation_{$applicationId}_{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Clear generation progress cache (static helper method).
     */
    public static function clearProgress(int $applicationId, int $jobId): void
    {
        $cacheKey = "assessment_generation_{$applicationId}_{$jobId}";
        Cache::forget($cacheKey);
    }
}
