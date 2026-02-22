<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AgentActivated;
use App\Events\AgentDeactivated;
use App\Events\InterviewCompleted;
use App\Events\JobApplied;
use App\Events\JobSaved;
use App\Events\LearningPathStarted;
use App\Events\NegotiationCompleted;
use App\Events\ProfileCompleted;
use App\Events\ResumeUploaded;
use App\Events\SkillAssessmentPassed;
use App\Events\SkillGapIdentified;
use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Awards gamification points based on user actions.
 */
class AwardGamificationPoints implements ShouldQueue
{
    /**
     * Point values for different actions.
     */
    protected const POINTS = [
        'user_registered' => 100,
        'profile_completed' => 250,
        'resume_uploaded' => 150,
        'job_applied' => 50,
        'job_saved' => 10,
        'interview_completed' => 100,
        'skill_gap_identified' => 25,
        'learning_path_started' => 50,
        'skill_assessment_passed' => 200,
        'negotiation_completed' => 75,
        'agent_activated' => 100,
    ];

    /**
     * Handle user registration.
     */
    public function handleUserRegistered(UserRegistered $event): void
    {
        $this->awardPoints($event->user->id, 'user_registered', self::POINTS['user_registered']);
    }

    /**
     * Handle profile completion.
     */
    public function handleProfileCompleted(ProfileCompleted $event): void
    {
        $this->awardPoints($event->user->id, 'profile_completed', self::POINTS['profile_completed']);
    }

    /**
     * Handle resume upload.
     */
    public function handleResumeUploaded(ResumeUploaded $event): void
    {
        $this->awardPoints($event->user->id, 'resume_uploaded', self::POINTS['resume_uploaded']);
    }

    /**
     * Handle job application.
     */
    public function handleJobApplied(JobApplied $event): void
    {
        $points = self::POINTS['job_applied'];

        // Bonus points for AI-assisted applications
        if ($event->aiAssisted) {
            $points += 25;
        }

        $this->awardPoints($event->user->id, 'job_applied', $points);
    }

    /**
     * Handle job saved.
     */
    public function handleJobSaved(JobSaved $event): void
    {
        $this->awardPoints($event->user->id, 'job_saved', self::POINTS['job_saved']);
    }

    /**
     * Handle interview completion.
     */
    public function handleInterviewCompleted(InterviewCompleted $event): void
    {
        $basePoints = self::POINTS['interview_completed'];

        // Bonus points for high scores
        if ($event->overallScore >= 90) {
            $basePoints += 100;
        } elseif ($event->overallScore >= 80) {
            $basePoints += 50;
        }

        $this->awardPoints($event->user->id, 'interview_completed', $basePoints);
    }

    /**
     * Handle skill gap identification.
     */
    public function handleSkillGapIdentified(SkillGapIdentified $event): void
    {
        $this->awardPoints($event->user->id, 'skill_gap_identified', self::POINTS['skill_gap_identified']);
    }

    /**
     * Handle learning path started.
     */
    public function handleLearningPathStarted(LearningPathStarted $event): void
    {
        $this->awardPoints($event->user->id, 'learning_path_started', self::POINTS['learning_path_started']);
    }

    /**
     * Handle skill assessment passed.
     */
    public function handleSkillAssessmentPassed(SkillAssessmentPassed $event): void
    {
        $basePoints = self::POINTS['skill_assessment_passed'];

        // Bonus points for high scores
        if ($event->score >= 95) {
            $basePoints += 100;
        } elseif ($event->score >= 90) {
            $basePoints += 50;
        }

        $this->awardPoints($event->user->id, 'skill_assessment_passed', $basePoints);
    }

    /**
     * Handle negotiation completion.
     */
    public function handleNegotiationCompleted(NegotiationCompleted $event): void
    {
        $this->awardPoints($event->user->id, 'negotiation_completed', self::POINTS['negotiation_completed']);
    }

    /**
     * Handle agent activation.
     */
    public function handleAgentActivated(AgentActivated $event): void
    {
        $this->awardPoints($event->user->id, 'agent_activated', self::POINTS['agent_activated']);
    }

    /**
     * Award points to a user.
     */
    protected function awardPoints(int $userId, string $action, int $points): void
    {
        try {
            DB::table('gamification_points')->insert([
                'user_id' => $userId,
                'action' => $action,
                'points' => $points,
                'created_at' => now(),
            ]);

            // Update user's total points
            DB::table('users')
                ->where('id', $userId)
                ->increment('total_points', $points);

            Log::info('Gamification: Points awarded', [
                'user_id' => $userId,
                'action' => $action,
                'points' => $points,
            ]);
        } catch (\Exception $e) {
            Log::error('Gamification: Failed to award points', [
                'user_id' => $userId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to multiple events.
     */
    public function subscribe($events): array
    {
        return [
            UserRegistered::class => 'handleUserRegistered',
            ProfileCompleted::class => 'handleProfileCompleted',
            ResumeUploaded::class => 'handleResumeUploaded',
            JobApplied::class => 'handleJobApplied',
            JobSaved::class => 'handleJobSaved',
            InterviewCompleted::class => 'handleInterviewCompleted',
            SkillGapIdentified::class => 'handleSkillGapIdentified',
            LearningPathStarted::class => 'handleLearningPathStarted',
            SkillAssessmentPassed::class => 'handleSkillAssessmentPassed',
            NegotiationCompleted::class => 'handleNegotiationCompleted',
            AgentActivated::class => 'handleAgentActivated',
        ];
    }
}
