<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\Application;
use App\Services\AI\Scout\AutomatedShortlistingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class AutomatedShortlistingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $jobId;
    protected array $applicationIds;
    protected ?int $requestedByUserId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes for large batches

    /**
     * Create a new job instance.
     *
     * @param int $jobId
     * @param array $applicationIds
     * @param int|null $requestedByUserId
     */
    public function __construct(int $jobId, array $applicationIds, ?int $requestedByUserId = null)
    {
        $this->jobId = $jobId;
        $this->applicationIds = $applicationIds;
        $this->requestedByUserId = $requestedByUserId;
    }

    /**
     * Execute the job.
     *
     * @param AutomatedShortlistingService $shortlistingService
     * @return void
     */
    public function handle(AutomatedShortlistingService $shortlistingService): void
    {
        try {
            Log::info('Automated shortlisting job started', [
                'job_id' => $this->jobId,
                'application_count' => count($this->applicationIds),
                'requested_by' => $this->requestedByUserId
            ]);

            // Set cache key for progress tracking
            $cacheKey = "shortlisting_progress_{$this->jobId}_" . md5(implode(',', $this->applicationIds));
            
            Cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => 0,
                'total' => count($this->applicationIds),
                'started_at' => now()->toIso8601String()
            ], 3600); // 1 hour cache

            // Execute the shortlisting pipeline
            $result = $shortlistingService->executeShortlistingPipeline(
                $this->jobId,
                $this->applicationIds
            );

            if (!$result['success']) {
                throw new Exception($result['message'] ?? 'Shortlisting pipeline failed');
            }

            // Cache the results for retrieval
            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'total' => count($this->applicationIds),
                'results' => $result['data'],
                'completed_at' => now()->toIso8601String()
            ], 86400); // 24 hours cache

            // Update application statuses for shortlisted candidates
            $this->updateApplicationStatuses($result['data']);

            // Send notification to requester (if applicable)
            if ($this->requestedByUserId) {
                $this->notifyRequester($result['data']);
            }

            Log::info('Automated shortlisting job completed successfully', [
                'job_id' => $this->jobId,
                'shortlisted_count' => count($result['data']['shortlisted']),
                'total_evaluated' => $result['data']['total_applications'],
                'processing_time' => $result['data']['processing_time']
            ]);

        } catch (Exception $e) {
            Log::error('Automated shortlisting job failed', [
                'job_id' => $this->jobId,
                'application_count' => count($this->applicationIds),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update cache with failure status
            $cacheKey = "shortlisting_progress_{$this->jobId}_" . md5(implode(',', $this->applicationIds));
            Cache::put($cacheKey, [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String()
            ], 3600);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Update application statuses based on shortlisting results.
     *
     * @param array $shortlistingData
     * @return void
     */
    protected function updateApplicationStatuses(array $shortlistingData): void
    {
        try {
            // Mark shortlisted applications
            $shortlistedIds = collect($shortlistingData['shortlisted'])
                ->pluck('application_id')
                ->toArray();

            if (!empty($shortlistedIds)) {
                Application::whereIn('id', $shortlistedIds)
                    ->update([
                        'status' => 'shortlisted',
                        'shortlisted_at' => now(),
                        'updated_at' => now()
                    ]);

                Log::info('Updated shortlisted applications', [
                    'count' => count($shortlistedIds),
                    'application_ids' => $shortlistedIds
                ]);
            }

            // Mark rejected applications with rejection round metadata
            foreach ($shortlistingData['rejected_by_round'] as $round => $rejections) {
                $rejectedIds = collect($rejections)->pluck('application_id')->toArray();
                
                if (!empty($rejectedIds)) {
                    Application::whereIn('id', $rejectedIds)
                        ->update([
                            'status' => 'rejected',
                            'rejection_reason' => "Automated shortlisting - Failed at {$round}",
                            'rejected_at' => now(),
                            'updated_at' => now()
                        ]);
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to update application statuses', [
                'error' => $e->getMessage()
            ]);
            // Don't throw - this is non-critical
        }
    }

    /**
     * Send notification to the user who requested the shortlisting.
     *
     * @param array $shortlistingData
     * @return void
     */
    protected function notifyRequester(array $shortlistingData): void
    {
        try {
            $user = \App\Models\User::find($this->requestedByUserId);
            if (!$user) {
                return;
            }

            $job = Job::find($this->jobId);
            if (!$job) {
                return;
            }

            // Send email notification
            $user->notify(new \App\Notifications\ShortlistingCompletedNotification(
                $job,
                $shortlistingData['total_applications'],
                count($shortlistingData['shortlisted']),
                $shortlistingData['processing_time']
            ));

            Log::info('Sent shortlisting completion notification', [
                'user_id' => $this->requestedByUserId,
                'job_id' => $this->jobId
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send shortlisting notification', [
                'user_id' => $this->requestedByUserId,
                'error' => $e->getMessage()
            ]);
            // Don't throw - this is non-critical
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
        Log::error('Automated shortlisting job permanently failed', [
            'job_id' => $this->jobId,
            'application_count' => count($this->applicationIds),
            'requested_by' => $this->requestedByUserId,
            'error' => $exception->getMessage()
        ]);

        // Update cache with permanent failure status
        $cacheKey = "shortlisting_progress_{$this->jobId}_" . md5(implode(',', $this->applicationIds));
        Cache::put($cacheKey, [
            'status' => 'failed_permanently',
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String()
        ], 3600);

        // Notify requester of failure
        if ($this->requestedByUserId) {
            try {
                $user = \App\Models\User::find($this->requestedByUserId);
                if ($user) {
                    $user->notify(new \App\Notifications\ShortlistingFailedNotification(
                        $this->jobId,
                        $exception->getMessage()
                    ));
                }
            } catch (Exception $e) {
                Log::error('Failed to send failure notification', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        // Exponential backoff: 1 minute, 5 minutes, 15 minutes
        return [60, 300, 900][$this->attempts() - 1] ?? 900;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'shortlisting',
            'job:' . $this->jobId,
            'applications:' . count($this->applicationIds)
        ];
    }
}
