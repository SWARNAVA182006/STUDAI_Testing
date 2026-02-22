<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AgentActivated;
use App\Events\AgentApplicationSubmitted;
use App\Events\AgentDeactivated;
use App\Events\AgentJobDiscovered;
use App\Events\AgentJobMatched;
use App\Events\ApplicationStatusChanged;
use App\Events\ApplicationSubmitted;
use App\Events\BiasAuditCompleted;
use App\Events\CandidateShortlisted;
use App\Events\InterviewCompleted;
use App\Events\InterviewStarted;
use App\Events\JobApplied;
use App\Events\JobSaved;
use App\Events\LearningPathCompleted;
use App\Events\LearningPathStarted;
use App\Events\NegotiationCompleted;
use App\Events\PaymentFailed;
use App\Events\PaymentInitiated;
use App\Events\PaymentSucceeded;
use App\Events\PredictionGenerated;
use App\Events\ProfileCompleted;
use App\Events\ResumeAnalyzed;
use App\Events\ResumeUploaded;
use App\Events\SkillAssessmentPassed;
use App\Events\SkillGapIdentified;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCanceled;
use App\Events\MessageSent;
use App\Events\ReferralReviewed;
use App\Events\UserRegistered;
use App\Listeners\AwardGamificationPoints;
use App\Listeners\GamificationEventSubscriber;
use App\Listeners\HandleJobApplied;
use App\Listeners\HandleLearningProgress;
use App\Listeners\LogAgentActivity;
use App\Listeners\LogPaymentActivity;
use App\Listeners\LogScoutActivity;
use App\Listeners\NotifyOnCareerMilestone;
use App\Listeners\NotifyOnMessagingAndReferral;
use App\Listeners\NotifyOnSubscriptionChange;
use App\Listeners\SendApplicationStatusChangedNotification;
use App\Listeners\SendApplicationSubmittedNotification;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\UpdateSearchIndex;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Laravel Auth Events
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // User Events
        UserRegistered::class => [
            SendWelcomeEmail::class,
        ],

        // Application Events
        ApplicationSubmitted::class => [
            SendApplicationSubmittedNotification::class,
        ],

        ApplicationStatusChanged::class => [
            SendApplicationStatusChangedNotification::class,
        ],

        // Payment Events
        // Listeners: LogPaymentActivity (subscriber), GamificationEventSubscriber
        PaymentSucceeded::class  => [], // → LogPaymentActivity::handlePaymentSucceeded, AwardGamificationPoints
        PaymentFailed::class     => [], // → LogPaymentActivity::handlePaymentFailed
        PaymentInitiated::class  => [], // → LogPaymentActivity::handlePaymentInitiated

        // Subscription Events
        // Listeners: NotifyOnSubscriptionChange (subscriber)
        SubscriptionActivated::class => [], // → NotifyOnSubscriptionChange::handleActivated
        SubscriptionCanceled::class  => [], // → NotifyOnSubscriptionChange::handleCanceled

        // Agent Events
        // Listeners: LogAgentActivity (subscriber), GamificationEventSubscriber, AwardGamificationPoints
        AgentActivated::class            => [], // → LogAgentActivity::handleActivated
        AgentDeactivated::class          => [], // → LogAgentActivity::handleDeactivated
        AgentJobMatched::class           => [], // → LogAgentActivity::handleJobMatched, AwardGamificationPoints
        AgentJobDiscovered::class        => [], // → LogAgentActivity::handleJobDiscovered
        AgentApplicationSubmitted::class => [], // → LogAgentActivity::handleApplicationSubmitted, AwardGamificationPoints

        // Resume & Profile Events
        // Listeners: NotifyOnCareerMilestone (subscriber), GamificationEventSubscriber
        ResumeUploaded::class  => [], // → NotifyOnCareerMilestone::handleResumeUploaded, AwardGamificationPoints
        ResumeAnalyzed::class  => [], // → NotifyOnCareerMilestone::handleResumeAnalyzed
        ProfileCompleted::class => [], // → NotifyOnCareerMilestone::handleProfileCompleted, AwardGamificationPoints

        // Career Events
        // Listeners: NotifyOnCareerMilestone (subscriber), HandleLearningProgress, GamificationEventSubscriber
        JobApplied::class => [
            HandleJobApplied::class,
        ],
        JobSaved::class             => [], // → GamificationEventSubscriber
        InterviewStarted::class     => [], // → NotifyOnCareerMilestone::handleInterviewStarted
        InterviewCompleted::class   => [], // → NotifyOnCareerMilestone::handleInterviewCompleted, AwardGamificationPoints
        NegotiationCompleted::class => [], // → NotifyOnCareerMilestone::handleNegotiationCompleted, AwardGamificationPoints
        SkillGapIdentified::class   => [], // → HandleLearningProgress::handleSkillGapIdentified
        SkillAssessmentPassed::class => [], // → HandleLearningProgress::handleAssessmentPassed, AwardGamificationPoints
        LearningPathStarted::class  => [], // → HandleLearningProgress::handleLearningPathStarted
        LearningPathCompleted::class => [], // → HandleLearningProgress::handleLearningPathCompleted, AwardGamificationPoints

        // S.C.O.U.T. Employer Events
        // Listeners: LogScoutActivity (subscriber)
        CandidateShortlisted::class => [], // → LogScoutActivity::handleShortlisted
        PredictionGenerated::class  => [], // → LogScoutActivity::handlePrediction
        BiasAuditCompleted::class   => [], // → LogScoutActivity::handleBiasAudit

        // Messaging & Referral Events
        // Listeners: NotifyOnMessagingAndReferral (subscriber)
        MessageSent::class      => [], // → NotifyOnMessagingAndReferral::handleMessageSent
        ReferralReviewed::class => [], // → NotifyOnMessagingAndReferral::handleReferralReviewed
    ];

    /**
     * The subscriber classes to register.
     *
     * Event subscribers are classes that can listen to multiple events.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        // Original gamification subscriber
        GamificationEventSubscriber::class,

        // New comprehensive gamification points
        AwardGamificationPoints::class,

        // Payment activity logging
        LogPaymentActivity::class,

        // Subscription notifications
        NotifyOnSubscriptionChange::class,

        // Search index updates
        UpdateSearchIndex::class,

        // Agent activity logging
        LogAgentActivity::class,

        // Career milestone notifications (interview, negotiation, resume analysis)
        NotifyOnCareerMilestone::class,

        // S.C.O.U.T. activity logging (predictions, bias audits, shortlisting)
        LogScoutActivity::class,

        // Learning progress tracking (skill gaps, assessments, learning paths)
        HandleLearningProgress::class,

        // Messaging & referral notifications
        NotifyOnMessagingAndReferral::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
