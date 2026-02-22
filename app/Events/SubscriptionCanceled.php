<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a subscription is canceled.
 */
class SubscriptionCanceled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public UserSubscription $subscription,
        public string $reason,
        public bool $immediate = false
    ) {}
}
