<?php

namespace App\Observers;

use App\Models\Interview;
use App\Services\WebhookService;

class InterviewObserver
{
    protected WebhookService $webhookService;
    
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    
    /**
     * Handle the Interview "created" event.
     */
    public function created(Interview $interview): void
    {
        $this->webhookService->trigger(
            'interview.scheduled',
            [
                'interview_id' => $interview->id,
                'application_id' => $interview->application_id,
                'job_id' => $interview->application->job_id,
                'job_title' => $interview->application->job->title,
                'candidate_id' => $interview->application->user_id,
                'candidate_name' => $interview->application->user->name,
                'interview_type' => $interview->interview_type,
                'scheduled_at' => $interview->scheduled_at->toIso8601String(),
                'duration' => $interview->duration,
                'location' => $interview->location,
                'meeting_link' => $interview->meeting_link,
            ],
            $interview->application->job->company_id
        );
    }
    
    /**
     * Handle the Interview "updated" event.
     */
    public function updated(Interview $interview): void
    {
        if ($interview->isDirty('status')) {
            if ($interview->status === 'completed') {
                $this->webhookService->trigger(
                    'interview.completed',
                    [
                        'interview_id' => $interview->id,
                        'application_id' => $interview->application_id,
                        'candidate_id' => $interview->application->user_id,
                        'candidate_name' => $interview->application->user->name,
                        'completed_at' => $interview->completed_at?->toIso8601String(),
                        'rating' => $interview->rating,
                        'feedback' => $interview->feedback,
                    ],
                    $interview->application->job->company_id
                );
            }
            
            if ($interview->status === 'cancelled') {
                $this->webhookService->trigger(
                    'interview.cancelled',
                    [
                        'interview_id' => $interview->id,
                        'application_id' => $interview->application_id,
                        'candidate_id' => $interview->application->user_id,
                        'candidate_name' => $interview->application->user->name,
                        'cancelled_at' => now()->toIso8601String(),
                        'cancellation_reason' => $interview->cancellation_reason,
                    ],
                    $interview->application->job->company_id
                );
            }
        }
    }
}
