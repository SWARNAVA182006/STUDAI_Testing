<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a subscription is activated.
 */
class SubscriptionActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public UserSubscription $subscription,
        public SubscriptionPlan $plan,
        public bool $isUpgrade = false,
        public bool $isReactivation = false
    ) {}
}
