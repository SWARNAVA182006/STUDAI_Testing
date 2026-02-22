<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UserSubscription;
use App\Notifications\PaymentFailedNotification;
use App\Services\PaymentGatewayService;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Retry Failed Payment Job
 *
 * Automatically retries failed payments during the grace period.
 * Implements exponential backoff with configurable retry limits.
 *
 * Retry Schedule:
 * - Attempt 1: Immediate (when payment fails)
 * - Attempt 2: 24 hours later
 * - Attempt 3: 72 hours later (3 days)
 * - Final Notice: 5 days later
 * - Expiration: 7 days (end of grace period)
 */
class RetryFailedPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum retry attempts.
     */
    public const MAX_RETRIES = 3;

    /**
     * The subscription ID to retry payment for.
     */
    protected int $subscriptionId;

    /**
     * Current retry attempt.
     */
    protected int $attempt;

    /**
     * Create a new job instance.
     */
    public function __construct(int $subscriptionId, int $attempt = 1)
    {
        $this->subscriptionId = $subscriptionId;
        $this->attempt = $attempt;
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewayService $paymentService): void
    {
        $subscription = UserSubscription::with(['user', 'plan'])->find($this->subscriptionId);

        if (!$subscription) {
            Log::warning('Subscription not found for retry', [
                'subscription_id' => $this->subscriptionId,
            ]);
            return;
        }

        // Check if subscription is still in past_due state
        if ($subscription->status !== SubscriptionStateMachine::STATE_PAST_DUE) {
            Log::info('Subscription no longer in past_due state, skipping retry', [
                'subscription_id' => $this->subscriptionId,
                'status' => $subscription->status,
            ]);
            return;
        }

        // Check if grace period has ended
        if ($subscription->grace_period_ends_at && $subscription->grace_period_ends_at->isPast()) {
            $this->handleGracePeriodExpired($subscription);
            return;
        }

        Log::info('Attempting payment retry', [
            'subscription_id' => $this->subscriptionId,
            'attempt' => $this->attempt,
            'user_id' => $subscription->user_id,
        ]);

        try {
            // Attempt to charge the stored payment method
            $result = $this->attemptPayment($subscription, $paymentService);

            if ($result['success']) {
                $this->handlePaymentSuccess($subscription);
            } else {
                $this->handlePaymentFailure($subscription, $result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Payment retry exception', [
                'subscription_id' => $this->subscriptionId,
                'attempt' => $this->attempt,
                'error' => $e->getMessage(),
            ]);

            $this->handlePaymentFailure($subscription, $e->getMessage());
        }
    }

    /**
     * Attempt to process the payment.
     */
    protected function attemptPayment(UserSubscription $subscription, PaymentGatewayService $paymentService): array
    {
        $user = $subscription->user;
        $plan = $subscription->plan;

        if (!$plan) {
            return ['success' => false, 'error' => 'No plan associated with subscription'];
        }

        // Check for stored payment token
        $paymentToken = $user->default_payment_token;

        if (!$paymentToken) {
            return ['success' => false, 'error' => 'No stored payment method'];
        }

        // Create payment for subscription renewal
        try {
            $transaction = $paymentService->createRecurringPayment(
                $user,
                $plan->price,
                [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'retry_attempt' => $this->attempt,
                    'token' => $paymentToken,
                ]
            );

            // Check if payment was successful
            if ($transaction && $transaction->status === 'completed') {
                return ['success' => true, 'transaction' => $transaction];
            }

            return [
                'success' => false,
                'error' => $transaction?->failure_reason ?? 'Payment failed',
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentSuccess(UserSubscription $subscription): void
    {
        $stateMachine = new SubscriptionStateMachine($subscription);

        // Transition back to active
        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_ACTIVE, [
            'payment_retry_attempt' => $this->attempt,
            'recovered_at' => now()->toIso8601String(),
        ]);

        // Update subscription period
        $subscription->update([
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(), // Adjust based on plan interval
            'failure_count' => 0,
        ]);

        // Notify user of successful payment
        $subscription->user->notify(new PaymentFailedNotification(
            $subscription,
            'recovered',
            'Your payment has been successfully processed!'
        ));

        Log::info('Payment retry successful', [
            'subscription_id' => $subscription->id,
            'attempt' => $this->attempt,
        ]);
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailure(UserSubscription $subscription, string $error): void
    {
        // Update failure count
        $subscription->increment('failure_count');

        Log::warning('Payment retry failed', [
            'subscription_id' => $subscription->id,
            'attempt' => $this->attempt,
            'error' => $error,
            'failure_count' => $subscription->failure_count,
        ]);

        // Determine next action
        if ($this->attempt < self::MAX_RETRIES) {
            // Schedule next retry with exponential backoff
            $this->scheduleNextRetry($subscription);

            // Notify user of failed attempt
            $subscription->user->notify(new PaymentFailedNotification(
                $subscription,
                'retry_failed',
                $error,
                $this->attempt,
                self::MAX_RETRIES
            ));
        } else {
            // All retries exhausted - send final warning
            $subscription->user->notify(new PaymentFailedNotification(
                $subscription,
                'final_warning',
                'Please update your payment method to avoid service interruption.',
                $this->attempt,
                self::MAX_RETRIES
            ));
        }
    }

    /**
     * Schedule the next retry attempt.
     */
    protected function scheduleNextRetry(UserSubscription $subscription): void
    {
        // Exponential backoff: 1 day, 3 days, 5 days
        $delayHours = match ($this->attempt) {
            1 => 24,
            2 => 72,
            default => 120,
        };

        $nextAttempt = $this->attempt + 1;

        self::dispatch($subscription->id, $nextAttempt)
            ->delay(now()->addHours($delayHours));

        Log::info('Next payment retry scheduled', [
            'subscription_id' => $subscription->id,
            'next_attempt' => $nextAttempt,
            'delay_hours' => $delayHours,
        ]);
    }

    /**
     * Handle grace period expiration.
     */
    protected function handleGracePeriodExpired(UserSubscription $subscription): void
    {
        $stateMachine = new SubscriptionStateMachine($subscription);

        // Transition to expired
        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_EXPIRED, [
            'reason' => 'Grace period expired after failed payment retries',
            'total_retry_attempts' => $this->attempt,
        ]);

        // Notify user
        $subscription->user->notify(new PaymentFailedNotification(
            $subscription,
            'expired',
            'Your subscription has expired due to payment failure.'
        ));

        Log::info('Subscription expired after grace period', [
            'subscription_id' => $subscription->id,
            'total_attempts' => $this->attempt,
        ]);
    }

    /**
     * Get retry delay based on attempt number.
     */
    public static function getRetryDelay(int $attempt): int
    {
        return match ($attempt) {
            1 => 24,   // 1 day
            2 => 72,   // 3 days
            3 => 120,  // 5 days
            default => 168, // 7 days
        };
    }
}
