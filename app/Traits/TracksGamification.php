<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\GamificationService;

/**
 * Trait for tracking gamification activities in controllers/services.
 * 
 * Use this trait in any class that needs to track user actions for gamification.
 */
trait TracksGamification
{
    /**
     * Track a gamification activity for the current user.
     */
    protected function trackActivity(
        string $action,
        ?string $actionableType = null,
        ?int $actionableId = null,
        array $metadata = []
    ): array {
        $user = auth()->user();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'No authenticated user',
                'points' => 0,
                'xp' => 0,
            ];
        }

        $gamificationService = app(GamificationService::class);
        
        return $gamificationService->trackActivity(
            $user,
            $action,
            $actionableType,
            $actionableId,
            $metadata
        );
    }

    /**
     * Track job application.
     */
    protected function trackJobApplied($job): array
    {
        return $this->trackActivity(
            'job_applied',
            get_class($job),
            $job->id,
            ['job_title' => $job->title ?? null]
        );
    }

    /**
     * Track job saved.
     */
    protected function trackJobSaved($job): array
    {
        return $this->trackActivity(
            'job_saved',
            get_class($job),
            $job->id
        );
    }

    /**
     * Track profile update.
     */
    protected function trackProfileUpdated(): array
    {
        $result = $this->trackActivity('profile_updated');
        
        // Also check profile completion milestones
        $user = auth()->user();
        if ($user) {
            app(GamificationService::class)->checkProfileCompletionMilestones($user);
        }
        
        return $result;
    }

    /**
     * Track resume upload.
     */
    protected function trackResumeUploaded(): array
    {
        return $this->trackActivity('resume_uploaded');
    }

    /**
     * Track skill test completion.
     */
    protected function trackSkillTestCompleted($test, bool $passed = false, ?int $score = null): array
    {
        $action = $passed ? 'skill_test_passed' : 'skill_test_completed';
        
        return $this->trackActivity(
            $action,
            get_class($test),
            $test->id,
            ['score' => $score]
        );
    }

    /**
     * Track AI coach session.
     */
    protected function trackAiCoachSession(string $sessionType = 'general'): array
    {
        return $this->trackActivity(
            'ai_coach_session',
            null,
            null,
            ['session_type' => $sessionType]
        );
    }

    /**
     * Track AI resume review.
     */
    protected function trackAiResumeReview(): array
    {
        return $this->trackActivity('ai_resume_review');
    }

    /**
     * Track AI interview practice.
     */
    protected function trackAiInterviewPractice(): array
    {
        return $this->trackActivity('ai_interview_practice');
    }

    /**
     * Track marketplace proposal submission.
     */
    protected function trackProposalSubmitted($proposal): array
    {
        return $this->trackActivity(
            'proposal_submitted',
            get_class($proposal),
            $proposal->id
        );
    }

    /**
     * Track project completion.
     */
    protected function trackProjectCompleted($project): array
    {
        return $this->trackActivity(
            'project_completed',
            get_class($project),
            $project->id
        );
    }

    /**
     * Track receiving a review.
     */
    protected function trackReviewReceived($review, int $rating): array
    {
        $action = $rating >= 5 ? '5_star_review' : 'review_received';
        
        return $this->trackActivity(
            $action,
            get_class($review),
            $review->id,
            ['rating' => $rating]
        );
    }

    /**
     * Track connection made.
     */
    protected function trackConnectionAccepted(): array
    {
        return $this->trackActivity('connection_accepted');
    }

    /**
     * Track course completion.
     */
    protected function trackCourseCompleted($course): array
    {
        return $this->trackActivity(
            'course_completed',
            get_class($course),
            $course->id
        );
    }

    /**
     * Track certification earned.
     */
    protected function trackCertificationEarned($certification): array
    {
        return $this->trackActivity(
            'certification_earned',
            get_class($certification),
            $certification->id
        );
    }
}
