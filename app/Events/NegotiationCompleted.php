<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\NegotiationSession;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a negotiation session is completed.
 */
class NegotiationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public NegotiationSession $session,
        public string $outcome, // accepted, rejected, counter_offered, withdrawn
        public ?float $finalAmount = null
    ) {}
}
