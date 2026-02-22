<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Proration Service
 *
 * Calculates prorated amounts when users upgrade or downgrade their subscriptions.
 * Supports both immediate proration and end-of-period changes.
 *
 * Usage:
 *   $service = app(ProrationService::class);
 *   $proration = $service->calculateUpgradeProration($subscription, $newPlan);
 *   $proration = $service->calculateDowngradeCredit($subscription, $newPlan);
 */
class ProrationService
{
    /**
     * Calculate proration for an upgrade.
     *
     * Returns the amount the user needs to pay for the upgrade,
     * taking into account the unused portion of their current plan.
     */
    public function calculateUpgradeProration(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan
    ): array {
        $currentPlan = $subscription->plan;

        if (!$currentPlan) {
            return $this->formatResult(
                $newPlan->price,
                0,
                $newPlan->price,
                'No current plan, full price applies'
            );
        }

        // Calculate days remaining in current period
        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getBillingCycleDays($subscription);

        if ($daysRemaining <= 0 || $totalDays <= 0) {
            return $this->formatResult(
                $newPlan->price,
                0,
                $newPlan->price,
                'Current period ended, full price applies'
            );
        }

        // Calculate unused credit from current plan
        $dailyRateCurrent = $currentPlan->price / $totalDays;
        $unusedCredit = $dailyRateCurrent * $daysRemaining;

        // Calculate cost for new plan for remaining days
        $dailyRateNew = $newPlan->price / $totalDays;
        $newPlanCost = $dailyRateNew * $daysRemaining;

        // Amount due is the difference (can be negative for credit)
        $amountDue = max(0, $newPlanCost - $unusedCredit);

        Log::info('Upgrade proration calculated', [
            'subscription_id' => $subscription->id,
            'current_plan' => $currentPlan->name,
            'new_plan' => $newPlan->name,
            'days_remaining' => $daysRemaining,
            'unused_credit' => round($unusedCredit, 2),
            'new_plan_cost' => round($newPlanCost, 2),
            'amount_due' => round($amountDue, 2),
        ]);

        return $this->formatResult(
            round($newPlanCost, 2),
            round($unusedCredit, 2),
            round($amountDue, 2),
            'Prorated upgrade amount',
            [
                'days_remaining' => $daysRemaining,
                'daily_rate_current' => round($dailyRateCurrent, 2),
                'daily_rate_new' => round($dailyRateNew, 2),
            ]
        );
    }

    /**
     * Calculate credit for a downgrade.
     *
     * Returns the credit amount to be applied to the user's account.
     */
    public function calculateDowngradeCredit(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan
    ): array {
        $currentPlan = $subscription->plan;

        if (!$currentPlan) {
            return $this->formatResult(0, 0, $newPlan->price, 'No current plan');
        }

        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getBillingCycleDays($subscription);

        if ($daysRemaining <= 0 || $totalDays <= 0) {
            return $this->formatResult(0, 0, $newPlan->price, 'Current period ended');
        }

        // Calculate unused credit from current plan
        $dailyRateCurrent = $currentPlan->price / $totalDays;
        $unusedCredit = $dailyRateCurrent * $daysRemaining;

        // New plan cost for remaining period
        $dailyRateNew = $newPlan->price / $totalDays;
        $newPlanCost = $dailyRateNew * $daysRemaining;

        // Credit is the difference (current minus new plan cost)
        $credit = max(0, $unusedCredit - $newPlanCost);

        Log::info('Downgrade credit calculated', [
            'subscription_id' => $subscription->id,
            'current_plan' => $currentPlan->name,
            'new_plan' => $newPlan->name,
            'days_remaining' => $daysRemaining,
            'credit' => round($credit, 2),
        ]);

        return $this->formatResult(
            round($newPlanCost, 2),
            round($credit, 2),
            0, // No amount due for downgrade
            'Credit will be applied to account',
            [
                'days_remaining' => $daysRemaining,
                'credit_amount' => round($credit, 2),
            ]
        );
    }

    /**
     * Calculate proration for plan change (handles both upgrade and downgrade).
     */
    public function calculatePlanChange(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan
    ): array {
        $currentPlan = $subscription->plan;

        if (!$currentPlan) {
            return $this->formatResult(
                $newPlan->price,
                0,
                $newPlan->price,
                'New subscription'
            );
        }

        // Determine if upgrade or downgrade
        $isUpgrade = $newPlan->price > $currentPlan->price;

        if ($isUpgrade) {
            return $this->calculateUpgradeProration($subscription, $newPlan);
        }

        return $this->calculateDowngradeCredit($subscription, $newPlan);
    }

    /**
     * Calculate prorated refund for early cancellation.
     */
    public function calculateCancellationRefund(UserSubscription $subscription): array
    {
        $currentPlan = $subscription->plan;

        if (!$currentPlan || $currentPlan->price <= 0) {
            return $this->formatResult(0, 0, 0, 'No refund available');
        }

        $daysRemaining = $this->getDaysRemaining($subscription);
        $totalDays = $this->getBillingCycleDays($subscription);

        if ($daysRemaining <= 0 || $totalDays <= 0) {
            return $this->formatResult(0, 0, 0, 'Period ended, no refund');
        }

        // Calculate refund based on unused days
        $dailyRate = $currentPlan->price / $totalDays;
        $refundAmount = $dailyRate * $daysRemaining;

        // Apply minimum usage policy (e.g., no refund if less than 7 days remaining)
        $minDaysForRefund = config('subscription.min_days_for_refund', 7);
        if ($daysRemaining < $minDaysForRefund) {
            return $this->formatResult(
                0,
                0,
                0,
                "Less than {$minDaysForRefund} days remaining, no refund"
            );
        }

        return $this->formatResult(
            0,
            round($refundAmount, 2),
            0,
            'Cancellation refund',
            ['days_remaining' => $daysRemaining]
        );
    }

    /**
     * Calculate the number of days remaining in the billing period.
     */
    protected function getDaysRemaining(UserSubscription $subscription): int
    {
        if (!$subscription->current_period_ends_at) {
            return 0;
        }

        $now = now();
        $periodEnd = $subscription->current_period_ends_at;

        if ($periodEnd->isPast()) {
            return 0;
        }

        return (int) $now->diffInDays($periodEnd);
    }

    /**
     * Get the total days in the billing cycle.
     */
    protected function getBillingCycleDays(UserSubscription $subscription): int
    {
        $interval = $subscription->plan?->billing_interval ?? 'monthly';

        return match ($interval) {
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'yearly', 'annual' => 365,
            default => 30,
        };
    }

    /**
     * Format the proration result.
     */
    protected function formatResult(
        float $newCost,
        float $credit,
        float $amountDue,
        string $description,
        array $details = []
    ): array {
        return [
            'new_plan_cost' => $newCost,
            'credit' => $credit,
            'amount_due' => $amountDue,
            'description' => $description,
            'details' => $details,
            'currency' => 'INR',
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Apply proration and update subscription.
     */
    public function applyPlanChange(
        UserSubscription $subscription,
        SubscriptionPlan $newPlan,
        bool $immediate = true
    ): array {
        $proration = $this->calculatePlanChange($subscription, $newPlan);

        if (!$immediate) {
            // Schedule change for end of current period
            $subscription->update([
                'scheduled_plan_id' => $newPlan->id,
                'scheduled_change_at' => $subscription->current_period_ends_at,
            ]);

            $proration['scheduled'] = true;
            $proration['change_at'] = $subscription->current_period_ends_at?->toIso8601String();

            Log::info('Plan change scheduled', [
                'subscription_id' => $subscription->id,
                'new_plan' => $newPlan->name,
                'change_at' => $subscription->current_period_ends_at,
            ]);

            return $proration;
        }

        // Apply credit if any
        if ($proration['credit'] > 0) {
            $user = $subscription->user;
            $currentCredits = $user->account_credit ?? 0;
            $user->update([
                'account_credit' => $currentCredits + $proration['credit'],
            ]);
        }

        // Update subscription to new plan
        $subscription->update([
            'subscription_plan_id' => $newPlan->id,
            'plan_changed_at' => now(),
            'previous_plan_id' => $subscription->plan?->id,
        ]);

        $proration['applied'] = true;

        Log::info('Plan change applied', [
            'subscription_id' => $subscription->id,
            'new_plan' => $newPlan->name,
            'proration' => $proration,
        ]);

        return $proration;
    }
}
