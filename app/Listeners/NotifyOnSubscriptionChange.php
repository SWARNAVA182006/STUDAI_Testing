<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCanceled;
use App\Notifications\SubscriptionActivatedNotification;
use App\Notifications\SubscriptionCanceledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Sends notifications when subscription status changes.
 */
class NotifyOnSubscriptionChange implements ShouldQueue
{
    /**
     * The queue connection that should handle the job.
     */
    public string $connection = 'redis';

    /**
     * Handle subscription activation.
     */
    public function handleSubscriptionActivated(SubscriptionActivated $event): void
    {
        $event->user->notify(new SubscriptionActivatedNotification(
            $event->subscription,
            $event->plan,
            $event->isUpgrade,
            $event->isReactivation
        ));

        Log::info('Subscription activated notification sent', [
            'user_id' => $event->user->id,
            'plan' => $event->plan->name,
        ]);
    }

    /**
     * Handle subscription cancellation.
     */
    public function handleSubscriptionCanceled(SubscriptionCanceled $event): void
    {
        $event->user->notify(new SubscriptionCanceledNotification(
            $event->subscription,
            $event->reason,
            $event->immediate
        ));

        Log::info('Subscription canceled notification sent', [
            'user_id' => $event->user->id,
            'reason' => $event->reason,
        ]);
    }

    /**
     * Subscribe to subscription events.
     */
    public function subscribe($events): array
    {
        return [
            SubscriptionActivated::class => 'handleSubscriptionActivated',
            SubscriptionCanceled::class => 'handleSubscriptionCanceled',
        ];
    }
}
