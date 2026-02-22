<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\UserSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Subscription State Machine
 *
 * Manages subscription lifecycle state transitions with validation.
 * Ensures only valid state transitions are allowed.
 *
 * States:
 * - pending: Initial state, awaiting payment confirmation
 * - trialing: Free trial period active
 * - active: Subscription is active and paid
 * - past_due: Payment failed, grace period active
 * - canceled: User requested cancellation (still active until period ends)
 * - expired: Subscription period ended
 * - suspended: Admin suspended the subscription
 *
 * Usage:
 *   $machine = new SubscriptionStateMachine($subscription);
 *   $machine->transitionTo('active');
 *   $machine->canTransitionTo('canceled');
 */
class SubscriptionStateMachine
{
    /**
     * Subscription states.
     */
    public const STATE_PENDING = 'pending';
    public const STATE_TRIALING = 'trialing';
    public const STATE_ACTIVE = 'active';
    public const STATE_PAST_DUE = 'past_due';
    public const STATE_CANCELED = 'canceled';
    public const STATE_EXPIRED = 'expired';
    public const STATE_SUSPENDED = 'suspended';

    /**
     * Valid state transitions.
     * Key: current state, Value: array of allowed next states.
     */
    protected const TRANSITIONS = [
        self::STATE_PENDING => [
            self::STATE_TRIALING,
            self::STATE_ACTIVE,
            self::STATE_EXPIRED,
        ],
        self::STATE_TRIALING => [
            self::STATE_ACTIVE,
            self::STATE_CANCELED,
            self::STATE_EXPIRED,
        ],
        self::STATE_ACTIVE => [
            self::STATE_PAST_DUE,
            self::STATE_CANCELED,
            self::STATE_EXPIRED,
            self::STATE_SUSPENDED,
        ],
        self::STATE_PAST_DUE => [
            self::STATE_ACTIVE,     // Payment succeeded
            self::STATE_CANCELED,   // User canceled
            self::STATE_EXPIRED,    // Grace period ended
            self::STATE_SUSPENDED,
        ],
        self::STATE_CANCELED => [
            self::STATE_ACTIVE,     // User reactivated
            self::STATE_EXPIRED,    // Period ended
        ],
        self::STATE_EXPIRED => [
            self::STATE_PENDING,    // New subscription started
            self::STATE_TRIALING,   // New trial started
            self::STATE_ACTIVE,     // Immediate reactivation
        ],
        self::STATE_SUSPENDED => [
            self::STATE_ACTIVE,     // Admin unsuspended
            self::STATE_EXPIRED,
        ],
    ];

    /**
     * The subscription instance.
     */
    protected UserSubscription $subscription;

    /**
     * Create a new state machine instance.
     */
    public function __construct(UserSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the current state.
     */
    public function currentState(): string
    {
        return $this->subscription->status ?? self::STATE_PENDING;
    }

    /**
     * Check if a transition to the given state is allowed.
     */
    public function canTransitionTo(string $newState): bool
    {
        $currentState = $this->currentState();

        if (!isset(self::TRANSITIONS[$currentState])) {
            return false;
        }

        return in_array($newState, self::TRANSITIONS[$currentState], true);
    }

    /**
     * Get all allowed transitions from the current state.
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->currentState()] ?? [];
    }

    /**
     * Transition to a new state.
     *
     * @throws \InvalidArgumentException If the transition is not allowed
     */
    public function transitionTo(string $newState, array $metadata = []): UserSubscription
    {
        if (!$this->canTransitionTo($newState)) {
            throw new \InvalidArgumentException(
                "Invalid transition from '{$this->currentState()}' to '{$newState}'"
            );
        }

        $oldState = $this->currentState();

        // Update subscription status
        $this->subscription->update([
            'status' => $newState,
            'status_changed_at' => now(),
            'status_metadata' => array_merge(
                $this->subscription->status_metadata ?? [],
                [
                    'previous_state' => $oldState,
                    'transition_at' => now()->toIso8601String(),
                    'metadata' => $metadata,
                ]
            ),
        ]);

        // Handle state-specific actions
        $this->handleStateTransition($oldState, $newState, $metadata);

        Log::info('Subscription state transition', [
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->subscription->user_id,
            'from' => $oldState,
            'to' => $newState,
            'metadata' => $metadata,
        ]);

        return $this->subscription->fresh();
    }

    /**
     * Handle state-specific side effects.
     */
    protected function handleStateTransition(string $from, string $to, array $metadata): void
    {
        match ($to) {
            self::STATE_ACTIVE => $this->onActivated($from, $metadata),
            self::STATE_PAST_DUE => $this->onPastDue($metadata),
            self::STATE_CANCELED => $this->onCanceled($metadata),
            self::STATE_EXPIRED => $this->onExpired($from, $metadata),
            self::STATE_SUSPENDED => $this->onSuspended($metadata),
            self::STATE_TRIALING => $this->onTrialStarted($metadata),
            default => null,
        };
    }

    /**
     * Handle activation.
     */
    protected function onActivated(string $from, array $metadata): void
    {
        $this->subscription->update([
            'activated_at' => now(),
            'grace_period_ends_at' => null,
            'failure_count' => 0,
        ]);

        // Dispatch event
        // event(new SubscriptionActivated($this->subscription));
    }

    /**
     * Handle past due state.
     */
    protected function onPastDue(array $metadata): void
    {
        $graceDays = config('subscription.grace_period_days', 7);

        $this->subscription->update([
            'grace_period_ends_at' => now()->addDays($graceDays),
            'failure_count' => ($this->subscription->failure_count ?? 0) + 1,
        ]);

        // Dispatch event
        // event(new PaymentFailed($this->subscription));
    }

    /**
     * Handle cancellation.
     */
    protected function onCanceled(array $metadata): void
    {
        $this->subscription->update([
            'canceled_at' => now(),
            'cancel_reason' => $metadata['reason'] ?? null,
        ]);

        // Dispatch event
        // event(new SubscriptionCanceled($this->subscription));
    }

    /**
     * Handle expiration.
     */
    protected function onExpired(string $from, array $metadata): void
    {
        $this->subscription->update([
            'expired_at' => now(),
        ]);

        // Revoke features/access
        // $this->subscription->user->revokeSubscriptionFeatures();

        // Dispatch event
        // event(new SubscriptionExpired($this->subscription));
    }

    /**
     * Handle suspension.
     */
    protected function onSuspended(array $metadata): void
    {
        $this->subscription->update([
            'suspended_at' => now(),
            'suspended_by' => $metadata['suspended_by'] ?? null,
            'suspend_reason' => $metadata['reason'] ?? null,
        ]);
    }

    /**
     * Handle trial start.
     */
    protected function onTrialStarted(array $metadata): void
    {
        $trialDays = config('subscription.trial_days', 14);

        $this->subscription->update([
            'trial_ends_at' => now()->addDays($trialDays),
        ]);
    }

    /**
     * Check if the subscription is in a grace period.
     */
    public function isInGracePeriod(): bool
    {
        if ($this->currentState() !== self::STATE_PAST_DUE) {
            return false;
        }

        return $this->subscription->grace_period_ends_at
            && $this->subscription->grace_period_ends_at->isFuture();
    }

    /**
     * Check if the subscription grants access to features.
     */
    public function hasAccess(): bool
    {
        $accessStates = [
            self::STATE_TRIALING,
            self::STATE_ACTIVE,
            self::STATE_PAST_DUE,  // During grace period
            self::STATE_CANCELED, // Until period ends
        ];

        if (!in_array($this->currentState(), $accessStates, true)) {
            return false;
        }

        // Check if canceled subscription has ended
        if ($this->currentState() === self::STATE_CANCELED) {
            return $this->subscription->ends_at
                && $this->subscription->ends_at->isFuture();
        }

        // Check if grace period has ended
        if ($this->currentState() === self::STATE_PAST_DUE) {
            return $this->isInGracePeriod();
        }

        return true;
    }

    /**
     * Get all possible states.
     */
    public static function getStates(): array
    {
        return [
            self::STATE_PENDING => 'Pending',
            self::STATE_TRIALING => 'Trial',
            self::STATE_ACTIVE => 'Active',
            self::STATE_PAST_DUE => 'Past Due',
            self::STATE_CANCELED => 'Canceled',
            self::STATE_EXPIRED => 'Expired',
            self::STATE_SUSPENDED => 'Suspended',
        ];
    }

    /**
     * Get the transition graph (for documentation/visualization).
     */
    public static function getTransitionGraph(): array
    {
        return self::TRANSITIONS;
    }
}
