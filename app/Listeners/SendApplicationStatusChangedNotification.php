<?php

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicationStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ApplicationStatusChanged $event): void
    {
        $application = $event->application;
        $user = $application->user;

        // Only notify for significant status changes
        $notifiableStatuses = ['viewed', 'shortlisted', 'interview_scheduled', 'rejected', 'offer'];
        
        if (in_array($event->newStatus, $notifiableStatuses)) {
            $user->notify(new ApplicationStatusChangedNotification($application, $event->oldStatus, $event->newStatus));
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(ApplicationStatusChanged $event): bool
    {
        return true;
    }
}
