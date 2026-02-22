<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\WebhookService;

class JobObserver
{
    protected WebhookService $webhookService;
    
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    
    /**
     * Handle the Job "updated" event.
     */
    public function updated(Job $job): void
    {
        // Check if status changed to published
        if ($job->isDirty('status')) {
            if ($job->status === 'published' && $job->getOriginal('status') !== 'published') {
                $this->webhookService->trigger(
                    'job.published',
                    [
                        'job_id' => $job->id,
                        'title' => $job->title,
                        'category' => $job->category,
                        'location' => $job->location,
                        'employment_type' => $job->employment_type,
                        'work_mode' => $job->work_mode,
                        'published_at' => $job->published_at->toIso8601String(),
                        'expires_at' => $job->expires_at?->toIso8601String(),
                    ],
                    $job->company_id
                );
            }
            
            if ($job->status === 'closed' && $job->getOriginal('status') !== 'closed') {
                $this->webhookService->trigger(
                    'job.closed',
                    [
                        'job_id' => $job->id,
                        'title' => $job->title,
                        'closed_at' => now()->toIso8601String(),
                        'total_applications' => $job->applications()->count(),
                    ],
                    $job->company_id
                );
            }
        }
    }
}
