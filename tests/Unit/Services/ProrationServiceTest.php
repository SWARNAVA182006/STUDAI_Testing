<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Subscription\ProrationService;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProrationService $prorationService;
    protected User $user;
    protected SubscriptionPlan $basicPlan;
    protected SubscriptionPlan $proPlan;
    protected SubscriptionPlan $enterprisePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prorationService = app(ProrationService::class);
        $this->user = User::factory()->create();

        $this->basicPlan = SubscriptionPlan::factory()->create([
            'name' => 'Basic',
            'price' => 999.00,
            'interval' => 'monthly',
        ]);

        $this->proPlan = SubscriptionPlan::factory()->create([
            'name' => 'Pro',
            'price' => 2499.00,
            'interval' => 'monthly',
        ]);

        $this->enterprisePlan = SubscriptionPlan::factory()->create([
            'name' => 'Enterprise',
            'price' => 4999.00,
            'interval' => 'monthly',
        ]);
    }

    public function test_calculate_upgrade_proration_on_day_one(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->proPlan);

        // Full price difference on day 1
        $this->assertArrayHasKey('amount_due', $result);
        $this->assertArrayHasKey('credit_amount', $result);
        $this->assertArrayHasKey('days_remaining', $result);

        // Should be close to full price difference (2499 - 999 = 1500)
        $this->assertEqualsWithDelta(1500.00, $result['amount_due'], 100);
    }

    public function test_calculate_upgrade_proration_mid_period(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->proPlan);

        // Half period remaining = half credit, half new cost
        $this->assertArrayHasKey('amount_due', $result);
        $this->assertEquals(15, $result['days_remaining']);

        // Credit should be about half of basic plan
        $this->assertEqualsWithDelta(499.50, $result['credit_amount'], 50);
    }

    public function test_calculate_upgrade_proration_last_day(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(29),
            'current_period_ends_at' => now()->addDay(),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->proPlan);

        // Only 1 day remaining = minimal credit
        $this->assertEquals(1, $result['days_remaining']);
        $this->assertLessThan(100, $result['credit_amount']);
    }

    public function test_calculate_downgrade_credit(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $result = $this->prorationService->calculateDowngradeCredit($subscription, $this->basicPlan);

        $this->assertArrayHasKey('credit_amount', $result);
        $this->assertArrayHasKey('effective_date', $result);

        // Should have credit from current Pro plan
        $this->assertGreaterThan(0, $result['credit_amount']);
    }

    public function test_calculate_cancellation_refund_within_refund_period(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(3),
            'current_period_ends_at' => now()->addDays(27),
        ]);

        $result = $this->prorationService->calculateCancellationRefund($subscription);

        $this->assertArrayHasKey('refund_amount', $result);
        $this->assertArrayHasKey('eligible_for_refund', $result);

        // Within 7-day refund period
        $this->assertTrue($result['eligible_for_refund']);
        $this->assertGreaterThan(0, $result['refund_amount']);
    }

    public function test_calculate_cancellation_refund_outside_refund_period(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $result = $this->prorationService->calculateCancellationRefund($subscription);

        // Outside 7-day refund period
        $this->assertFalse($result['eligible_for_refund']);
        $this->assertEquals(0, $result['refund_amount']);
    }

    public function test_apply_plan_change_upgrade_immediate(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(20),
        ]);

        $result = $this->prorationService->applyPlanChange($subscription, $this->proPlan, immediate: true);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('amount_due', $result);
        $this->assertArrayHasKey('new_plan', $result);
        $this->assertEquals('Pro', $result['new_plan']);
    }

    public function test_apply_plan_change_downgrade_at_period_end(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(20),
        ]);

        $result = $this->prorationService->applyPlanChange($subscription, $this->basicPlan, immediate: false);

        $this->assertArrayHasKey('scheduled_change', $result);
        $this->assertTrue($result['scheduled_change']);
        $this->assertArrayHasKey('effective_date', $result);
    }

    public function test_calculates_daily_rate_correctly(): void
    {
        $daysInMonth = 30;
        $monthlyPrice = 999.00;
        $expectedDailyRate = $monthlyPrice / $daysInMonth;

        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays($daysInMonth),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->proPlan);

        $this->assertArrayHasKey('daily_rate_old', $result);
        $this->assertArrayHasKey('daily_rate_new', $result);
        $this->assertEqualsWithDelta($expectedDailyRate, $result['daily_rate_old'], 0.01);
    }

    public function test_handles_annual_subscription_proration(): void
    {
        $annualPlan = SubscriptionPlan::factory()->create([
            'name' => 'Annual Basic',
            'price' => 9999.00,
            'interval' => 'yearly',
        ]);

        $annualProPlan = SubscriptionPlan::factory()->create([
            'name' => 'Annual Pro',
            'price' => 24999.00,
            'interval' => 'yearly',
        ]);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $annualPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subMonths(6),
            'current_period_ends_at' => now()->addMonths(6),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $annualProPlan);

        // Should calculate based on ~180 days remaining
        $this->assertArrayHasKey('days_remaining', $result);
        $this->assertGreaterThan(170, $result['days_remaining']);
        $this->assertLessThan(190, $result['days_remaining']);
    }

    public function test_proration_never_negative(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(35), // Past end date
            'current_period_ends_at' => now()->subDays(5),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->proPlan);

        $this->assertGreaterThanOrEqual(0, $result['amount_due']);
        $this->assertGreaterThanOrEqual(0, $result['credit_amount']);
    }

    public function test_preview_plan_change_returns_breakdown(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(20),
        ]);

        $preview = $this->prorationService->previewPlanChange($subscription, $this->proPlan);

        $this->assertArrayHasKey('current_plan', $preview);
        $this->assertArrayHasKey('new_plan', $preview);
        $this->assertArrayHasKey('price_difference', $preview);
        $this->assertArrayHasKey('credit_amount', $preview);
        $this->assertArrayHasKey('amount_due', $preview);
        $this->assertArrayHasKey('effective_immediately', $preview);

        $this->assertEquals('Basic', $preview['current_plan']);
        $this->assertEquals('Pro', $preview['new_plan']);
    }

    public function test_same_plan_returns_zero_proration(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(20),
        ]);

        $result = $this->prorationService->calculateUpgradeProration($subscription, $this->basicPlan);

        $this->assertEquals(0, $result['amount_due']);
    }

    public function test_get_credit_balance(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'credit_balance' => 500.00,
        ]);

        $balance = $this->prorationService->getCreditBalance($subscription);

        $this->assertEquals(500.00, $balance);
    }

    public function test_apply_credit_to_invoice(): void
    {
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'credit_balance' => 500.00,
        ]);

        $invoiceAmount = 2499.00;
        $result = $this->prorationService->applyCreditToInvoice($subscription, $invoiceAmount);

        $this->assertArrayHasKey('original_amount', $result);
        $this->assertArrayHasKey('credit_applied', $result);
        $this->assertArrayHasKey('final_amount', $result);
        $this->assertArrayHasKey('remaining_credit', $result);

        $this->assertEquals(2499.00, $result['original_amount']);
        $this->assertEquals(500.00, $result['credit_applied']);
        $this->assertEquals(1999.00, $result['final_amount']);
        $this->assertEquals(0, $result['remaining_credit']);
    }
}
