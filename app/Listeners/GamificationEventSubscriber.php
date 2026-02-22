<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\JobApplied;
use App\Events\JobSaved;
use App\Events\JobViewed;
use App\Events\ProfileUpdated;
use App\Events\ResumeUploaded;
use App\Events\SkillAdded;
use App\Events\SkillTestCompleted;
use App\Events\UserLoggedIn;
use App\Services\GamificationService;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;

class GamificationEventSubscriber implements ShouldQueue
{
    public function __construct(
        protected GamificationService $gamificationService
    ) {}

    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        $user = $event->user;
        
        // Check if this is the first login today
        $hasLoginToday = \App\Models\GamificationActivity::forUser($user->id)
            ->byAction('daily_login')
            ->today()
            ->exists();

        if (!$hasLoginToday) {
            $this->gamificationService->trackActivity($user, 'daily_login');
        } else {
            $this->gamificationService->trackActivity($user, 'login');
        }
    }

    /**
     * Handle job application events.
     */
    public function handleJobApplied($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'job_applied',
                get_class($event->job ?? $event),
                $event->job->id ?? $event->jobId ?? null,
                ['job_title' => $event->job->title ?? null]
            );
        }
    }

    /**
     * Handle job saved events.
     */
    public function handleJobSaved($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'job_saved',
                get_class($event->job ?? $event),
                $event->job->id ?? $event->jobId ?? null
            );
        }
    }

    /**
     * Handle job viewed events.
     */
    public function handleJobViewed($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'job_viewed',
                get_class($event->job ?? $event),
                $event->job->id ?? $event->jobId ?? null
            );
        }
    }

    /**
     * Handle profile update events.
     */
    public function handleProfileUpdated($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity($user, 'profile_updated');
            
            // Check for profile completion milestones
            $this->gamificationService->checkProfileCompletionMilestones($user);
        }
    }

    /**
     * Handle resume upload events.
     */
    public function handleResumeUploaded($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity($user, 'resume_uploaded');
        }
    }

    /**
     * Handle skill added events.
     */
    public function handleSkillAdded($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'skill_added',
                get_class($event->skill ?? $event),
                $event->skill->id ?? $event->skillId ?? null
            );
        }
    }

    /**
     * Handle skill test completion events.
     */
    public function handleSkillTestCompleted($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $action = $event->passed ? 'skill_test_passed' : 'skill_test_completed';
            
            $this->gamificationService->trackActivity(
                $user,
                $action,
                get_class($event->test ?? $event),
                $event->test->id ?? $event->testId ?? null,
                ['score' => $event->score ?? null]
            );
        }
    }

    /**
     * Handle AI coach session events.
     */
    public function handleAiCoachSession($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'ai_coach_session',
                null,
                null,
                ['session_type' => $event->sessionType ?? 'general']
            );
        }
    }

    /**
     * Handle interview scheduled events.
     */
    public function handleInterviewScheduled($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'interview_scheduled',
                get_class($event->interview ?? $event),
                $event->interview->id ?? $event->interviewId ?? null
            );
        }
    }

    /**
     * Handle offer received events.
     */
    public function handleOfferReceived($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'offer_received',
                get_class($event->offer ?? $event),
                $event->offer->id ?? $event->offerId ?? null
            );
        }
    }

    /**
     * Handle proposal submitted events (marketplace).
     */
    public function handleProposalSubmitted($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'proposal_submitted',
                get_class($event->proposal ?? $event),
                $event->proposal->id ?? $event->proposalId ?? null
            );
        }
    }

    /**
     * Handle proposal accepted events (marketplace).
     */
    public function handleProposalAccepted($event): void
    {
        $user = $event->freelancer ?? $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'proposal_accepted',
                get_class($event->proposal ?? $event),
                $event->proposal->id ?? $event->proposalId ?? null
            );
        }
    }

    /**
     * Handle project completed events (marketplace).
     */
    public function handleProjectCompleted($event): void
    {
        $user = $event->freelancer ?? $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity(
                $user,
                'project_completed',
                get_class($event->project ?? $event),
                $event->project->id ?? $event->projectId ?? null
            );
        }
    }

    /**
     * Handle review received events.
     */
    public function handleReviewReceived($event): void
    {
        $user = $event->reviewee ?? $event->user ?? auth()->user();
        
        if ($user) {
            $action = ($event->rating ?? 0) >= 5 ? '5_star_review' : 'review_received';
            
            $this->gamificationService->trackActivity(
                $user,
                $action,
                get_class($event->review ?? $event),
                $event->review->id ?? $event->reviewId ?? null,
                ['rating' => $event->rating ?? null]
            );
        }
    }

    /**
     * Handle connection accepted events.
     */
    public function handleConnectionAccepted($event): void
    {
        $user = $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity($user, 'connection_accepted');
        }
    }

    /**
     * Handle message sent events.
     */
    public function handleMessageSent($event): void
    {
        $user = $event->sender ?? $event->user ?? auth()->user();
        
        if ($user) {
            $this->gamificationService->trackActivity($user, 'message_sent');
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            // Laravel built-in events
            Login::class => 'handleUserLogin',
            
            // Job-related events (check if these exist in your app)
            'App\Events\JobApplied' => 'handleJobApplied',
            'App\Events\JobSaved' => 'handleJobSaved',
            'App\Events\JobViewed' => 'handleJobViewed',
            
            // Profile events
            'App\Events\ProfileUpdated' => 'handleProfileUpdated',
            'App\Events\ResumeUploaded' => 'handleResumeUploaded',
            
            // Skill events
            'App\Events\SkillAdded' => 'handleSkillAdded',
            'App\Events\SkillTestCompleted' => 'handleSkillTestCompleted',
            
            // AI events
            'App\Events\AiCoachSessionCompleted' => 'handleAiCoachSession',
            
            // Interview/Offer events
            'App\Events\InterviewScheduled' => 'handleInterviewScheduled',
            'App\Events\OfferReceived' => 'handleOfferReceived',
            
            // Marketplace events
            'App\Events\ProposalSubmitted' => 'handleProposalSubmitted',
            'App\Events\ProposalAccepted' => 'handleProposalAccepted',
            'App\Events\ProjectCompleted' => 'handleProjectCompleted',
            'App\Events\ReviewReceived' => 'handleReviewReceived',
            
            // Networking events
            'App\Events\ConnectionAccepted' => 'handleConnectionAccepted',
            'App\Events\MessageSent' => 'handleMessageSent',
        ];
    }
}
