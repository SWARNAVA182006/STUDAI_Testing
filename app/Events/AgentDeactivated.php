<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AgentConfiguration;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when the autonomous agent is deactivated.
 */
class AgentDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public AgentConfiguration $configuration,
        public string $reason = 'user_requested' // user_requested, subscription_expired, emergency_stop
    ) {}
}
