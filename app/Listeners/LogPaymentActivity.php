<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Events\PaymentInitiated;
use App\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Logs payment activity for audit and analytics.
 */
class LogPaymentActivity implements ShouldQueue
{
    /**
     * Handle successful payment.
     */
    public function handlePaymentSucceeded(PaymentSucceeded $event): void
    {
        $this->logActivity($event->user->id, 'payment_succeeded', [
            'transaction_id' => $event->transaction->id,
            'gateway' => $event->gateway,
            'amount' => $event->amount,
            'gateway_payment_id' => $event->transaction->gateway_payment_id,
        ]);

        Log::info('Payment succeeded', [
            'user_id' => $event->user->id,
            'transaction_id' => $event->transaction->id,
            'amount' => $event->amount,
        ]);
    }

    /**
     * Handle failed payment.
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $this->logActivity($event->user->id, 'payment_failed', [
            'transaction_id' => $event->transaction->id,
            'gateway' => $event->gateway,
            'failure_reason' => $event->failureReason,
            'attempt_number' => $event->attemptNumber,
        ]);

        Log::warning('Payment failed', [
            'user_id' => $event->user->id,
            'transaction_id' => $event->transaction->id,
            'reason' => $event->failureReason,
        ]);
    }

    /**
     * Handle payment initiation.
     */
    public function handlePaymentInitiated(PaymentInitiated $event): void
    {
        $this->logActivity($event->user->id, 'payment_initiated', [
            'transaction_id' => $event->transaction->id,
            'gateway' => $event->gateway,
            'amount' => $event->amount,
        ]);

        Log::info('Payment initiated', [
            'user_id' => $event->user->id,
            'transaction_id' => $event->transaction->id,
            'gateway' => $event->gateway,
        ]);
    }

    /**
     * Log activity to database.
     */
    protected function logActivity(int $userId, string $action, array $data): void
    {
        DB::table('payment_activity_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'data' => json_encode($data),
            'created_at' => now(),
        ]);
    }

    /**
     * Subscribe to payment events.
     */
    public function subscribe($events): array
    {
        return [
            PaymentInitiated::class => 'handlePaymentInitiated',
            PaymentSucceeded::class => 'handlePaymentSucceeded',
            PaymentFailed::class    => 'handlePaymentFailed',
        ];
    }
}
