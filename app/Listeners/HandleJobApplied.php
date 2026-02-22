<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\JobApplied;
use App\Notifications\NotifyEmployer;
use App\Notifications\SendApplicationConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleJobApplied implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    /**
     * Handle the event.
     */
    public function handle(JobApplied $event): void
    {
        $application = $event->application;
        $user = $event->user;
        $job = $event->job;

        // Notify the applicant with confirmation
        $user->notify(new SendApplicationConfirmation($application));

        // Notify the employer about the new application
        $employer = $job->company?->owner;
        if ($employer) {
            $employer->notify(new NotifyEmployer($application));
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(JobApplied $event): bool
    {
        return true;
    }
}
