<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a payment is successfully processed.
 */
class PaymentSucceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public PaymentTransaction $transaction,
        public string $gateway,
        public float $amount
    ) {}
}
