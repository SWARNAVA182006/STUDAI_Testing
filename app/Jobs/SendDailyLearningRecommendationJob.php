<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\DailyLearningRecommendationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyLearningRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if user has opted in for daily emails
            $preferences = $this->user->learning_preferences ?? [];
            
            if (!($preferences['daily_emails'] ?? true)) {
                Log::info("Skipping daily email for user {$this->user->id} - opted out");
                return;
            }

            // Get user's active learning paths
            $activePaths = $this->user->learningPaths()
                ->where('status', 'active')
                ->with(['resources', 'skillGap'])
                ->get();

            if ($activePaths->isEmpty()) {
                Log::info("No active learning paths for user {$this->user->id}");
                return;
            }

            // Build recommendations array
            $recommendations = [];
            $dailyGoalMinutes = $preferences['daily_minutes'] ?? 30;

            foreach ($activePaths as $path) {
                $nextResource = $path->getNextResource();
                
                if (!$nextResource) {
                    continue; // Path completed
                }

                $recommendations[] = [
                    'skill' => $path->skillGap->skill_name ?? 'Skill',
                    'path_name' => $path->custom_name ?? "Learning Path",
                    'title' => $nextResource->title,
                    'type' => $nextResource->resource_type,
                    'duration' => $nextResource->estimated_duration_minutes,
                    'url' => $nextResource->url,
                    'description' => $nextResource->description,
                    'difficulty' => $nextResource->difficulty_level,
                    'fits_schedule' => $nextResource->estimated_duration_minutes <= $dailyGoalMinutes,
                ];

                // Limit to top 3 recommendations
                if (count($recommendations) >= 3) {
                    break;
                }
            }

            if (empty($recommendations)) {
                Log::info("No recommendations available for user {$this->user->id}");
                return;
            }

            // Get learning streak
            $streak = $this->calculateLearningStreak();

            // Send email
            Mail::to($this->user->email)->send(
                new DailyLearningRecommendationMail(
                    $this->user,
                    $recommendations,
                    $streak,
                    $dailyGoalMinutes
                )
            );

            Log::info("Daily learning email sent to user {$this->user->id}", [
                'recommendations_count' => count($recommendations),
                'streak' => $streak,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send daily learning email to user {$this->user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate user's learning streak
     */
    protected function calculateLearningStreak(): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();

        // Count consecutive days with learning activity
        while (true) {
            $hasActivity = $this->user->learningProgress()
                ->whereDate('created_at', $currentDate)
                ->where('time_spent_minutes', '>', 0)
                ->exists();

            if (!$hasActivity) {
                break;
            }

            $streak++;
            $currentDate->subDay();

            // Cap at 365 days for performance
            if ($streak >= 365) {
                break;
            }
        }

        return $streak;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Daily learning email job failed permanently for user {$this->user->id}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['daily-email', 'user:' . $this->user->id];
    }
}
