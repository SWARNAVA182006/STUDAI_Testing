<?php

use App\Jobs\ProcessAutoApplications;
use App\Jobs\Agent\DiscoverJobsJob;
use App\Jobs\Agent\SubmitApplicationsJob;
use App\Jobs\Agent\UpdateLearningJob;
use App\Jobs\Agent\SendDigestJob;
use App\Jobs\RetryFailedPaymentJob;
use App\Jobs\UpdateMarketDataJob;
use App\Jobs\AnalyzeTrendsJob;
use App\Jobs\UpdateUserPositioningJob;
use App\Jobs\GenerateInsightsJob;
use App\Jobs\AnalyzeSkillGapsJob;
use App\Jobs\CurateLearningResourcesJob;
use App\Jobs\SendDailyLearningRecommendationJob;
use App\Jobs\UpdateMarketTrendsJob;
use App\Jobs\ValidateUserSkillsJob;
use App\Jobs\UpdatePredictionsJob;
use App\Jobs\UpdateTalentPipelinesJob;
use App\Jobs\DiscoverPassiveCandidatesJob;
use App\Jobs\SendCandidateUpdatesJob;
use App\Jobs\TrackEmployerBrandJob;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule job alerts to run daily at 9 AM
Schedule::command('jobs:send-alerts')->dailyAt('09:00')->name('Send Job Alerts');

// ===============================================
// AUTONOMOUS AGENT JOB SCHEDULING
// ===============================================

// Discover new jobs for all active agents (runs hourly)
Schedule::job(new DiscoverJobsJob())
    ->hourly()
    ->name('Agent: Discover Jobs')
    ->onOneServer()
    ->withoutOverlapping();

// Submit applications for qualified matches (runs every 15 minutes)
// Note: Individual SubmitApplicationsJob instances are also dispatched 
// by DiscoverJobsJob when new matches are found
Schedule::job(new SubmitApplicationsJob(null))
    ->everyFifteenMinutes()
    ->name('Agent: Submit Applications (Scheduled)')
    ->onOneServer()
    ->withoutOverlapping()
    ->skip(function () {
        // Skip if there are no pending applications across all agents
        return \App\Models\JobMatch::where('status', 'qualified')
            ->where('auto_application_submitted', false)
            ->doesntExist();
    });

// Update learning models and optimize strategies (runs daily at 2 AM)
Schedule::job(new UpdateLearningJob())
    ->dailyAt('02:00')
    ->name('Agent: Update Learning')
    ->onOneServer()
    ->withoutOverlapping();

// Send daily digest emails to users (runs daily at 8 AM)
Schedule::job(new SendDigestJob())
    ->dailyAt('08:00')
    ->name('Agent: Send Daily Digest')
    ->onOneServer()
    ->withoutOverlapping();

// Legacy: Process autonomous job applications every 4 hours
// TODO: Remove this once new agent system is fully deployed
Schedule::job(new ProcessAutoApplications())
    ->everyFourHours()
    ->name('Process Auto Applications (Legacy)')
    ->onOneServer()
    ->withoutOverlapping();

// ===============================================
// MARKET INTELLIGENCE JOB SCHEDULING
// ===============================================

// Update market data snapshots (runs hourly)
Schedule::job(new UpdateMarketDataJob())
    ->hourly()
    ->name('Market: Update Market Data')
    ->onOneServer()
    ->withoutOverlapping();

// Analyze salary and skill trends (runs daily at 1 AM)
Schedule::job(new AnalyzeTrendsJob())
    ->dailyAt('01:00')
    ->name('Market: Analyze Trends')
    ->onOneServer()
    ->withoutOverlapping();

// Recalculate user market positions (runs daily at 3 AM)
Schedule::job(new UpdateUserPositioningJob())
    ->dailyAt('03:00')
    ->name('Market: Update User Positioning')
    ->onOneServer()
    ->withoutOverlapping();

// Generate AI insights and send weekly digest (runs Sunday at 6 AM)
Schedule::job(new GenerateInsightsJob())
    ->weekly()
    ->sundays()
    ->at('06:00')
    ->name('Market: Generate Weekly Insights')
    ->onOneServer()
    ->withoutOverlapping();


// ===============================================
// SKILL GAP ANALYZER SCHEDULING
// ===============================================

// Analyze skill gaps for all users (runs daily at 2 AM)
Schedule::call(function () {
    User::whereHas('profile')
        ->chunk(100, function ($users) {
            foreach ($users as $user) {
                AnalyzeSkillGapsJob::dispatch($user)
                    ->onQueue('skill-analysis');
            }
        });
})
    ->dailyAt('02:00')
    ->name('Skill Analyzer: Daily Gap Analysis')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Curate learning resources (runs weekly on Sundays at 3 AM)
Schedule::job(new CurateLearningResourcesJob())
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('Skill Analyzer: Weekly Resource Curation')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Send daily learning recommendations (runs daily at 8 AM)
Schedule::call(function () {
    User::whereHas('learningPaths', function ($query) {
            $query->where('status', 'active');
        })
        ->whereJsonContains('learning_preferences->daily_emails', true)
        ->chunk(100, function ($users) {
            foreach ($users as $user) {
                SendDailyLearningRecommendationJob::dispatch($user)
                    ->onQueue('email')
                    ->delay(now()->addSeconds(rand(1, 300))); // Stagger emails
            }
        });
})
    ->dailyAt('08:00')
    ->name('Skill Analyzer: Daily Learning Emails')
    ->onOneServer()
    ->withoutOverlapping();

// Update market trends (runs weekly on Mondays at 1 AM)
Schedule::job(new UpdateMarketTrendsJob())
    ->weekly()
    ->mondays()
    ->at('01:00')
    ->name('Skill Analyzer: Weekly Market Trends Update')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// ValidateUserSkillsJob is triggered on-demand via API, not scheduled


// ===============================================
// S.C.O.U.T. PREDICTIVE ANALYTICS SCHEDULING
// ===============================================

// Update predictive analytics for active applications (runs daily at 4 AM)
Schedule::call(function () {
    Application::with(['user', 'job.company'])
        ->whereIn('status', ['under_review', 'interviewing', 'offer_extended'])
        ->whereHas('job', function ($query) {
            $query->where('status', 'published');
        })
        ->chunk(50, function ($applications) {
            foreach ($applications as $application) {
                UpdatePredictionsJob::dispatch($application, false)
                    ->onQueue('predictions')
                    ->delay(now()->addSeconds(rand(1, 120))); // Stagger dispatches
            }
        });
})
    ->dailyAt('04:00')
    ->name('S.C.O.U.T.: Update Predictive Analytics')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Force refresh all predictions weekly (runs Saturdays at 5 AM)
Schedule::call(function () {
    Application::with(['user', 'job.company'])
        ->whereIn('status', ['under_review', 'interviewing', 'offer_extended'])
        ->whereHas('job', function ($query) {
            $query->where('status', 'published');
        })
        ->chunk(50, function ($applications) {
            foreach ($applications as $application) {
                UpdatePredictionsJob::dispatch($application, true) // Force refresh
                    ->onQueue('predictions')
                    ->delay(now()->addSeconds(rand(1, 180))); // Stagger dispatches
            }
        });
})
    ->weekly()
    ->saturdays()
    ->at('05:00')
    ->name('S.C.O.U.T.: Force Refresh All Predictions')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Update high-priority applications every 6 hours
Schedule::call(function () {
    Application::with(['user', 'job.company'])
        ->where('status', 'interviewing') // Only applications in interviewing stage
        ->whereHas('job', function ($query) {
            $query->where('status', 'published');
        })
        ->whereHas('successPrediction', function ($query) {
            $query->where('success_probability', '>', 75); // Only high-probability candidates
        })
        ->chunk(25, function ($applications) {
            foreach ($applications as $application) {
                UpdatePredictionsJob::dispatch($application, false)
                    ->onQueue('predictions')
                    ->delay(now()->addSeconds(rand(1, 60)));
            }
        });
})
    ->everySixHours()
    ->name('S.C.O.U.T.: Update High-Priority Predictions')
    ->onOneServer()
    ->withoutOverlapping();

// ===============================================
// TALENT PIPELINE AUTOMATION SCHEDULING
// ===============================================

// Update talent pipelines daily at 2 AM (health scoring, stale cleanup, priorities)
Schedule::call(function () {
    \App\Models\Company::whereHas('talentPipelines', function ($query) {
        $query->where('pipeline_status', 'active');
    })->chunk(10, function ($companies) {
        foreach ($companies as $company) {
            UpdateTalentPipelinesJob::dispatch($company)
                ->onQueue('pipeline-updates')
                ->delay(now()->addSeconds(rand(1, 300))); // Stagger by 5 minutes
        }
    });
})
    ->dailyAt('02:00')
    ->name('Talent Pipeline: Daily Health Update')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Discover passive candidates weekly on Mondays at 3 AM
Schedule::call(function () {
    \App\Models\Company::whereHas('talentPipelines', function ($query) {
        $query->where('pipeline_status', 'active');
    })->chunk(5, function ($companies) {
        foreach ($companies as $company) {
            DiscoverPassiveCandidatesJob::dispatch($company, null, 50)
                ->onQueue('candidate-discovery')
                ->delay(now()->addMinutes(rand(1, 30))); // Stagger by 30 minutes
        }
    });
})
    ->weekly()
    ->mondays()
    ->at('03:00')
    ->name('Talent Pipeline: Discover Passive Candidates')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Send candidate status updates daily at 10 AM
Schedule::call(function () {
    \App\Models\Company::has('jobs')->chunk(20, function ($companies) {
        foreach ($companies as $company) {
            SendCandidateUpdatesJob::dispatch($company)
                ->onQueue('candidate-updates')
                ->delay(now()->addSeconds(rand(1, 180))); // Stagger by 3 minutes
        }
    });
})
    ->dailyAt('10:00')
    ->name('Talent Pipeline: Send Candidate Updates')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Track employer brand weekly on Sundays at 6 AM
Schedule::call(function () {
    \App\Models\Company::has('jobs')->chunk(10, function ($companies) {
        foreach ($companies as $company) {
            TrackEmployerBrandJob::dispatch($company, 'weekly')
                ->onQueue('brand-tracking')
                ->delay(now()->addMinutes(rand(1, 20))); // Stagger by 20 minutes
        }
    });
})
    ->weekly()
    ->sundays()
    ->at('06:00')
    ->name('Talent Pipeline: Track Employer Brand (Weekly)')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Track employer brand monthly on 1st of month at 7 AM
Schedule::call(function () {
    \App\Models\Company::has('jobs')->chunk(10, function ($companies) {
        foreach ($companies as $company) {
            TrackEmployerBrandJob::dispatch($company, 'monthly')
                ->onQueue('brand-tracking')
                ->delay(now()->addMinutes(rand(1, 30))); // Stagger by 30 minutes
        }
    });
})
    ->monthlyOn(1, '07:00')
    ->name('Talent Pipeline: Track Employer Brand (Monthly)')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));


// ===============================================
// FAILED JOBS MONITORING
// ===============================================

// Check failed jobs hourly and alert if threshold exceeded
Schedule::command('queue:process-failed --threshold=5 --hours=1')
    ->hourly()
    ->name('Failed Jobs: Hourly Check')
    ->onOneServer()
    ->withoutOverlapping();

// Clean up old failed jobs weekly (retain 30 days)
Schedule::command('queue:process-failed --cleanup --retention=30')
    ->weekly()
    ->sundays()
    ->at('04:00')
    ->name('Failed Jobs: Weekly Cleanup')
    ->onOneServer()
    ->withoutOverlapping();

// Clean up expired idempotency keys daily
Schedule::call(function () {
    \App\Models\IdempotencyKey::cleanupExpired();
})
    ->daily()
    ->at('05:00')
    ->name('Cleanup: Expired Idempotency Keys')
    ->onOneServer();

// ===============================================
// PAYMENT GRACE PERIOD & RETRY SCHEDULING
// ===============================================

// Check for past_due subscriptions that need payment retry (runs every 6 hours)
// This catches any subscriptions that missed their scheduled retry
Schedule::call(function () {
    \App\Models\UserSubscription::where('status', 'past_due')
        ->where('grace_period_ends_at', '>', now()) // Still within grace period
        ->get()
        ->filter(fn ($sub) => $sub->needsPaymentRetry())
        ->each(function ($subscription) {
            // Calculate which retry attempt we should be on
            $failureCount = $subscription->failure_count ?? 0;
            $attempt = min($failureCount + 1, RetryFailedPaymentJob::MAX_RETRIES);

            // Only dispatch if we haven't exceeded max retries
            if ($attempt <= RetryFailedPaymentJob::MAX_RETRIES) {
                RetryFailedPaymentJob::dispatch($subscription->id, $attempt)
                    ->onQueue('high')
                    ->delay(now()->addMinutes(rand(1, 30))); // Stagger dispatches
            }
        });
})
    ->everySixHours()
    ->name('Payment: Retry Failed Subscriptions')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Check for expired grace periods and transition subscriptions (runs hourly)
Schedule::call(function () {
    \App\Models\UserSubscription::where('status', 'past_due')
        ->where('grace_period_ends_at', '<', now()) // Grace period expired
        ->each(function ($subscription) {
            $stateMachine = new \App\Services\Subscription\SubscriptionStateMachine($subscription);
            $stateMachine->transitionTo('expired', [
                'reason' => 'Grace period expired - no successful payment',
                'expired_at' => now()->toIso8601String(),
            ]);

            // Send expiration notification
            $subscription->user->notify(new \App\Notifications\PaymentFailedNotification(
                $subscription,
                'expired',
                'Your subscription has expired due to payment failure.'
            ));
        });
})
    ->hourly()
    ->name('Payment: Process Expired Grace Periods')
    ->onOneServer()
    ->withoutOverlapping()
    ->emailOutputOnFailure(config('mail.admin_email'));

// Send grace period warning emails (runs daily at 9 AM)
Schedule::call(function () {
    // Warn users whose grace period ends in 2 days
    \App\Models\UserSubscription::where('status', 'past_due')
        ->whereBetween('grace_period_ends_at', [
            now()->addDays(1),
            now()->addDays(3)
        ])
        ->with('user')
        ->each(function ($subscription) {
            $daysRemaining = now()->diffInDays($subscription->grace_period_ends_at, false);

            $subscription->user->notify(new \App\Notifications\PaymentFailedNotification(
                $subscription,
                'grace_period_warning',
                "Your subscription will expire in {$daysRemaining} days if payment is not updated.",
                null,
                null,
                $daysRemaining
            ));
        });
})
    ->dailyAt('09:00')
    ->name('Payment: Grace Period Warning Emails')
    ->onOneServer()
    ->withoutOverlapping();