<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AI\SkillValidatorService;
use App\Notifications\SkillValidationCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ValidateUserSkillsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 120, 240];

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
    public function handle(SkillValidatorService $validator): void
    {
        try {
            Log::info("Starting skill validation for user {$this->user->id}");

            // Validate user's skills from various sources
            $validations = $validator->validateUserSkills($this->user);

            $stats = [
                'total_validations' => $validations->count(),
                'verified_skills' => $validations->where('is_verified', true)->count(),
                'high_confidence' => $validations->where('confidence_score', '>=', 80)->count(),
                'needs_evidence' => $validations->where('confidence_score', '<', 70)->count(),
            ];

            Log::info("Skill validation completed for user {$this->user->id}", $stats);

            // Update UserSkill records with verification status
            foreach ($validations as $validation) {
                if ($validation->is_verified && $validation->confidence_score >= 80) {
                    $userSkill = $this->user->userSkills()
                        ->where('skill_name', $validation->skill_name)
                        ->first();

                    if ($userSkill) {
                        $userSkill->update([
                            'is_verified' => true,
                            'proficiency_level' => $validation->proficiency_detected,
                            'years_of_experience' => max(
                                $userSkill->years_of_experience ?? 0,
                                $validation->years_of_experience
                            ),
                            'last_validated_at' => now(),
                        ]);

                        Log::info("Updated UserSkill verification", [
                            'user_id' => $this->user->id,
                            'skill' => $userSkill->skill_name,
                            'proficiency' => $validation->proficiency_detected,
                        ]);
                    } else {
                        // Create new UserSkill record from validation
                        $this->user->userSkills()->create([
                            'skill_name' => $validation->skill_name,
                            'proficiency_level' => $validation->proficiency_detected,
                            'years_of_experience' => $validation->years_of_experience,
                            'is_verified' => true,
                            'last_validated_at' => now(),
                        ]);

                        Log::info("Created new UserSkill from validation", [
                            'user_id' => $this->user->id,
                            'skill' => $validation->skill_name,
                        ]);
                    }
                }
            }

            // Send notification with validation summary
            $this->user->notify(new SkillValidationCompletedNotification($validations, $stats));

            // Update user's profile
            $this->user->profile->update([
                'last_skill_validation_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Skill validation failed for user {$this->user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Skill validation job failed permanently for user {$this->user->id}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could send notification to user about validation failure
        // $this->user->notify(new SkillValidationFailedNotification($exception));
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['skill-validation', 'user:' . $this->user->id];
    }
}
