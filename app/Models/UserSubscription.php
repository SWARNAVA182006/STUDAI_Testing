<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'payment_gateway',
        'gateway_subscription_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'current_period_starts_at',
        'current_period_ends_at',
        'applications_used_this_month',
        'ai_credits_used_this_month',
        'canceled_at',
        'grace_period_ends_at',
        'failure_count',
        'last_retry_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'current_period_starts_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'applications_used_this_month' => 'integer',
        'ai_credits_used_this_month' => 'integer',
        'failure_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Alias for subscriptionPlan relationship
     */
    public function plan(): BelongsTo
    {
        return $this->subscriptionPlan();
    }

    /**
     * Get payment transactions for this subscription
     */
    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
    
    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'])
            && $this->current_period_end->isFuture();
    }
    
    /**
     * Check if in trial period
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }
    
    /**
     * Check if subscription has expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || $this->current_period_end->isPast();
    }
    
    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled' && $this->canceled_at !== null;
    }
    
    /**
     * Reset monthly usage counters
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'applications_used_this_month' => 0,
            'ai_credits_used_this_month' => 0,
            'assessments_taken_this_month' => 0,
            'last_reset_at' => now(),
        ]);
    }
    
    /**
     * Renew subscription for next period
     */
    public function renew(): void
    {
        $this->current_period_start = $this->current_period_end;
        $this->current_period_end = $this->billing_cycle === 'yearly'
            ? now()->addYear()
            : now()->addMonth();
        $this->status = 'active';
        $this->save();
        
        $this->resetMonthlyUsage();
    }
    
    /**
     * Cancel subscription
     */
    public function cancel(): void
    {
        $this->status = 'canceled';
        $this->canceled_at = now();
        $this->save();
    }

    /**
     * Check if subscription is past due (in grace period)
     */
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Check if still within grace period
     */
    public function isWithinGracePeriod(): bool
    {
        return $this->isPastDue()
            && $this->grace_period_ends_at
            && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if grace period has expired
     */
    public function hasGracePeriodExpired(): bool
    {
        return $this->grace_period_ends_at
            && $this->grace_period_ends_at->isPast();
    }

    /**
     * Check if subscription needs a payment retry
     * Used by the scheduler to find subscriptions that missed their retry window
     */
    public function needsPaymentRetry(): bool
    {
        if (!$this->isPastDue() || !$this->isWithinGracePeriod()) {
            return false;
        }

        // If never retried, needs retry
        if (!$this->last_retry_at) {
            return true;
        }

        // Calculate expected retry intervals based on attempt
        $failureCount = $this->failure_count ?? 0;
        $hoursNeeded = match ($failureCount) {
            0 => 0,          // Immediate
            1 => 24,         // 1 day
            2 => 72,         // 3 days
            default => 120,  // 5 days
        };

        // Check if enough time has passed since last retry
        return $this->last_retry_at->addHours($hoursNeeded)->isPast();
    }

    /**
     * Enter grace period due to failed payment
     */
    public function enterGracePeriod(int $graceDays = 7): void
    {
        $this->update([
            'status' => 'past_due',
            'grace_period_ends_at' => now()->addDays($graceDays),
            'failure_count' => ($this->failure_count ?? 0) + 1,
        ]);
    }

    /**
     * Record a payment retry attempt
     */
    public function recordRetryAttempt(): void
    {
        $this->update([
            'last_retry_at' => now(),
            'failure_count' => ($this->failure_count ?? 0) + 1,
        ]);
    }

    /**
     * Recover from past_due state after successful payment
     */
    public function recoverFromPastDue(): void
    {
        $this->update([
            'status' => 'active',
            'grace_period_ends_at' => null,
            'failure_count' => 0,
            'last_retry_at' => null,
        ]);
    }
}
