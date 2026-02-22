<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Company;
use App\Services\AI\Scout\CandidateExperienceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendCandidateUpdatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The company to send updates for
     */
    protected Company $company;

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
    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->onQueue('candidate-updates');
    }

    /**
     * Execute the job.
     */
    public function handle(CandidateExperienceService $candidateExperience): void
    {
        $startTime = now();
        $progressKey = "candidate_updates_progress_{$this->company->id}";
        
        try {
            Log::info('Starting candidate status updates', [
                'company_id' => $this->company->id,
            ]);

            // Initialize progress
            $this->updateProgress($progressKey, 0, 'Identifying applications needing updates...');

            // Step 1: Find applications needing status updates (30%)
            $applicationsToUpdate = $this->findApplicationsNeedingUpdates();
            $this->updateProgress($progressKey, 30, "Found {$applicationsToUpdate->count()} applications to update");

            if ($applicationsToUpdate->isEmpty()) {
                $this->updateProgress($progressKey, 100, 'No applications need updates');
                Log::info('No applications need status updates', [
                    'company_id' => $this->company->id,
                ]);
                return;
            }

            // Step 2: Send status updates (70%)
            $sentCount = 0;
            $totalCount = $applicationsToUpdate->count();

            foreach ($applicationsToUpdate as $index => $application) {
                $progress = 30 + (($index / $totalCount) * 40);
                $this->updateProgress($progressKey, (int)$progress, "Sending update {$index}/{$totalCount}...");

                try {
                    $candidateExperience->sendStatusUpdate(
                        $application,
                        $application->status,
                        ['automated' => true]
                    );

                    // Mark as updated
                    $application->update(['last_status_update_sent_at' => now()]);
                    $sentCount++;

                } catch (\Exception $e) {
                    Log::warning('Failed to send status update', [
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->updateProgress($progressKey, 70, "Sent {$sentCount} status updates");

            // Step 3: Request feedback from eligible applications (100%)
            $this->updateProgress($progressKey, 80, 'Requesting feedback...');
            $feedbackRequestsCount = $this->requestFeedback($candidateExperience);
            $this->updateProgress($progressKey, 100, 'Updates completed');

            $duration = now()->diffInSeconds($startTime);

            Log::info('Candidate status updates completed', [
                'company_id' => $this->company->id,
                'duration_seconds' => $duration,
                'updates_sent' => $sentCount,
                'feedback_requests' => $feedbackRequestsCount,
            ]);

            // Store completion timestamp
            Cache::put("candidate_updates_last_run_{$this->company->id}", now(), 86400);

        } catch (\Exception $e) {
            Log::error('Failed to send candidate updates', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress($progressKey, -1, 'Update failed: ' . $e->getMessage());
            
            throw $e;
        }
    }

    /**
     * Find applications that need status updates
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function findApplicationsNeedingUpdates()
    {
        return Application::whereHas('job', function($query) {
                $query->where('company_id', $this->company->id);
            })
            ->whereIn('status', ['under_review', 'shortlisted', 'interview_scheduled'])
            ->where(function($query) {
                // No update sent yet, or last update was 7+ days ago
                $query->whereNull('last_status_update_sent_at')
                    ->orWhere('last_status_update_sent_at', '<', now()->subDays(7));
            })
            ->where('updated_at', '>', now()->subDays(30)) // Only recent applications
            ->with(['user', 'job'])
            ->orderBy('updated_at')
            ->limit(100) // Process max 100 per run
            ->get();
    }

    /**
     * Request feedback from completed applications
     *
     * @param CandidateExperienceService $service
     * @return int
     */
    protected function requestFeedback(CandidateExperienceService $service): int
    {
        // Find applications that should receive feedback requests
        $applications = Application::whereHas('job', function($query) {
                $query->where('company_id', $this->company->id);
            })
            ->whereIn('status', ['rejected', 'withdrawn', 'hired'])
            ->whereDoesntHave('candidateFeedback') // No feedback requested yet
            ->where('updated_at', '>', now()->subDays(7)) // Status changed in last 7 days
            ->with(['user', 'job'])
            ->limit(50) // Max 50 feedback requests per run
            ->get();

        $count = 0;
        foreach ($applications as $application) {
            try {
                $service->requestFeedback($application);
                $count++;
                
                Log::info('Feedback requested from candidate', [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'status' => $application->status,
                ]);

            } catch (\Exception $e) {
                Log::warning('Failed to request feedback', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
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
        return Cache::get("candidate_updates_progress_{$companyId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendCandidateUpdatesJob failed', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators
    }
}
