<?php

namespace App\Observers;

use App\Models\Application;
use App\Services\WebhookService;

class ApplicationObserver
{
    protected WebhookService $webhookService;
    
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        $this->webhookService->trigger(
            'application.received',
            [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'job_title' => $application->job->title,
                'candidate_id' => $application->user_id,
                'candidate_name' => $application->user->name,
                'candidate_email' => $application->user->email,
                'applied_at' => $application->applied_at->toIso8601String(),
                'status' => $application->status,
                'source' => $application->source,
            ],
            $application->job->company_id
        );
    }
    
    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        // Check if status changed
        if ($application->isDirty('status')) {
            $this->webhookService->trigger(
                'application.status_changed',
                [
                    'application_id' => $application->id,
                    'job_id' => $application->job_id,
                    'job_title' => $application->job->title,
                    'candidate_id' => $application->user_id,
                    'candidate_name' => $application->user->name,
                    'old_status' => $application->getOriginal('status'),
                    'new_status' => $application->status,
                    'updated_at' => now()->toIso8601String(),
                ],
                $application->job->company_id
            );
            
            // Special event for hires
            if ($application->status === 'hired') {
                $this->webhookService->trigger(
                    'candidate.hired',
                    [
                        'application_id' => $application->id,
                        'job_id' => $application->job_id,
                        'job_title' => $application->job->title,
                        'candidate_id' => $application->user_id,
                        'candidate_name' => $application->user->name,
                        'candidate_email' => $application->user->email,
                        'hired_at' => now()->toIso8601String(),
                    ],
                    $application->job->company_id
                );
            }
        }
    }
}
