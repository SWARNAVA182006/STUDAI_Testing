<?php

namespace App\Listeners;

use App\Events\ApplicationSubmitted;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicationSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ApplicationSubmitted $event): void
    {
        $application = $event->application;
        $user = $application->user;

        // Send notification to user
        $user->notify(new ApplicationSubmittedNotification($application));
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(ApplicationSubmitted $event): bool
    {
        return true;
    }
}
