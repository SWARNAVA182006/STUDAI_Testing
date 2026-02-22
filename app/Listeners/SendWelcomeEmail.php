<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a welcome email when a user registers.
 */
class SendWelcomeEmail implements ShouldQueue
{
    /**
     * The queue connection that should handle the job.
     */
    public string $connection = 'redis';

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'default';

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        Mail::to($user->email)->send(new WelcomeMail($user, $event->registrationSource));
    }
}
