<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MessageSent;
use App\Events\ReferralReviewed;
use App\Notifications\NewMessage;
use App\Notifications\ReferralStatusUpdated;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Subscriber that handles messaging and referral notification events.
 *
 * Centralises notification dispatch so controllers stay thin.
 */
class NotifyOnMessagingAndReferral
{
    /**
     * Handle MessageSent events.
     */
    public function handleMessageSent(MessageSent $event): void
    {
        try {
            $event->recipient->notify(new NewMessage($event->conversation, $event->body));
        } catch (\Throwable $e) {
            Log::error('Failed to send message notification', [
                'recipient_id' => $event->recipient->id,
                'conversation_id' => $event->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle ReferralReviewed events.
     */
    public function handleReferralReviewed(ReferralReviewed $event): void
    {
        try {
            $event->referral->referrer->notify(
                new ReferralStatusUpdated($event->referral, $event->decision)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send referral status notification', [
                'referral_id' => $event->referral->id,
                'decision' => $event->decision,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MessageSent::class, [self::class, 'handleMessageSent']);
        $events->listen(ReferralReviewed::class, [self::class, 'handleReferralReviewed']);
    }
}
