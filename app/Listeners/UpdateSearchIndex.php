<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\JobApplied;
use App\Events\ProfileCompleted;
use App\Events\ResumeUploaded;
use App\Events\SkillGapIdentified;
use App\Jobs\GenerateJobEmbeddings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Updates search indexes when relevant data changes.
 */
class UpdateSearchIndex implements ShouldQueue
{
    /**
     * The queue to use.
     */
    public string $queue = 'search';

    /**
     * Handle profile completion - update user in search index.
     */
    public function handleProfileCompleted(ProfileCompleted $event): void
    {
        try {
            $event->user->searchable();

            Log::info('Search index updated for user profile', [
                'user_id' => $event->user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update search index for user', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle resume upload - extract and index skills.
     */
    public function handleResumeUploaded(ResumeUploaded $event): void
    {
        try {
            // Update resume in search index
            $event->resume->searchable();

            // Also update user profile with extracted skills
            $event->user->searchable();

            Log::info('Search index updated for resume', [
                'user_id' => $event->user->id,
                'resume_id' => $event->resume->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update search index for resume', [
                'resume_id' => $event->resume->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job application - update application statistics.
     */
    public function handleJobApplied(JobApplied $event): void
    {
        try {
            // Update job's application count in index
            $event->job->searchable();

            Log::info('Search index updated for job application', [
                'job_id' => $event->job->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update search index for job', [
                'job_id' => $event->job->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle skill gap identification - update user skills index.
     */
    public function handleSkillGapIdentified(SkillGapIdentified $event): void
    {
        try {
            $event->user->searchable();

            Log::info('Search index updated for skill gaps', [
                'user_id' => $event->user->id,
                'skill' => $event->skillGap->skill_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update search index for skill gap', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to events.
     */
    public function subscribe($events): array
    {
        return [
            ProfileCompleted::class => 'handleProfileCompleted',
            ResumeUploaded::class => 'handleResumeUploaded',
            JobApplied::class => 'handleJobApplied',
            SkillGapIdentified::class => 'handleSkillGapIdentified',
        ];
    }
}
