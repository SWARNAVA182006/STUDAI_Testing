<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AI\MarketPositioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Update User Positioning Job
 * 
 * Runs daily at 3am to recalculate all user market positions, readiness scores,
 * percentile rankings, and competitive analysis.
 */
class UpdateUserPositioningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 3600; // 1 hour

    /**
     * Execute the job.
     */
    public function handle(MarketPositioningService $marketPositioning): void
    {
        Log::info('UpdateUserPositioningJob: Starting user positioning update');

        try {
            // Get all active users with profiles
            $users = User::whereHas('profile')
                ->where('account_type', 'job_seeker')
                ->whereNotNull('email_verified_at')
                ->get();
            
            $processed = 0;
            $failed = 0;
            
            foreach ($users as $user) {
                try {
                    // Calculate and store market position
                    $marketPositioning->calculateMarketPosition($user);
                    $processed++;
                    
                    if ($processed % 100 == 0) {
                        Log::info('UpdateUserPositioningJob: Progress update', [
                            'processed' => $processed,
                            'total' => $users->count(),
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $failed++;
                    Log::warning('UpdateUserPositioningJob: Failed to update user positioning', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('UpdateUserPositioningJob: User positioning update completed', [
                'total_users' => $users->count(),
                'processed' => $processed,
                'failed' => $failed,
            ]);
            
        } catch (Exception $e) {
            Log::error('UpdateUserPositioningJob: Failed to update user positioning', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('UpdateUserPositioningJob: Job failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }
}
