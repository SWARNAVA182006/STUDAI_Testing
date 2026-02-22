<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InterviewCompleted;
use App\Events\InterviewStarted;
use App\Events\NegotiationCompleted;
use App\Events\ResumeAnalyzed;
use App\Notifications\InterviewCompletedNotification;
use App\Notifications\NegotiationResultNotification;
use App\Notifications\ResumeAnalysisReadyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyOnCareerMilestone implements ShouldQueue
{
    public string $queue = 'default';

    public function handleResumeAnalyzed(ResumeAnalyzed $event): void
    {
        try {
            $event->user->notify(new ResumeAnalysisReadyNotification(
                $event->resume,
                $event->analysisResults
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send resume analysis notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleInterviewStarted(InterviewStarted $event): void
    {
        Log::info('Interview session started', [
            'user_id' => $event->user->id,
            'session_id' => $event->session->id,
            'type' => $event->interviewType,
        ]);
    }

    public function handleInterviewCompleted(InterviewCompleted $event): void
    {
        try {
            $event->user->notify(new InterviewCompletedNotification(
                $event->session,
                $event->overallScore,
                $event->questionsAnswered
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send interview completed notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleNegotiationCompleted(NegotiationCompleted $event): void
    {
        try {
            $event->user->notify(new NegotiationResultNotification(
                $event->session,
                $event->outcome,
                $event->finalAmount
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send negotiation result notification', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function subscribe($events): array
    {
        return [
            ResumeAnalyzed::class => 'handleResumeAnalyzed',
            InterviewStarted::class => 'handleInterviewStarted',
            InterviewCompleted::class => 'handleInterviewCompleted',
            NegotiationCompleted::class => 'handleNegotiationCompleted',
        ];
    }
}
