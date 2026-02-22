<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $plan;
    protected UserSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->plan = SubscriptionPlan::factory()->create([
            'price' => 999.00,
            'interval' => 'monthly',
        ]);
        $this->subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => SubscriptionStateMachine::STATE_PENDING,
        ]);
    }

    public function test_can_transition_from_pending_to_trialing(): void
    {
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_TRIALING));

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_TRIALING);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_TRIALING, $this->subscription->status);
    }

    public function test_can_transition_from_pending_to_active(): void
    {
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_ACTIVE);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $this->subscription->status);
    }

    public function test_cannot_transition_from_pending_to_past_due(): void
    {
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_PAST_DUE));
    }

    public function test_transition_to_invalid_state_throws_exception(): void
    {
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->expectException(\InvalidArgumentException::class);
        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_PAST_DUE);
    }

    public function test_can_transition_from_trialing_to_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_TRIALING]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_ACTIVE);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $this->subscription->status);
    }

    public function test_can_transition_from_trialing_to_canceled(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_TRIALING]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_CANCELED));
    }

    public function test_can_transition_from_active_to_past_due(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_PAST_DUE));

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_PAST_DUE);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_PAST_DUE, $this->subscription->status);
    }

    public function test_can_transition_from_active_to_canceled(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_CANCELED));
    }

    public function test_can_transition_from_past_due_to_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_PAST_DUE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_ACTIVE);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $this->subscription->status);
    }

    public function test_can_transition_from_past_due_to_expired(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_PAST_DUE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_EXPIRED));
    }

    public function test_can_transition_from_canceled_to_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_CANCELED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));
    }

    public function test_can_transition_from_expired_to_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_EXPIRED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));
    }

    public function test_cannot_transition_from_suspended_to_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_SUSPENDED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->canTransitionTo(SubscriptionStateMachine::STATE_ACTIVE));
    }

    public function test_has_access_returns_true_for_active(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->hasAccess());
    }

    public function test_has_access_returns_true_for_trialing(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_TRIALING]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->hasAccess());
    }

    public function test_has_access_returns_true_for_past_due_in_grace_period(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->addDays(3),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->hasAccess());
    }

    public function test_has_access_returns_false_for_past_due_after_grace_period(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->subDay(),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->hasAccess());
    }

    public function test_has_access_returns_false_for_canceled(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_CANCELED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->hasAccess());
    }

    public function test_has_access_returns_false_for_expired(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_EXPIRED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->hasAccess());
    }

    public function test_has_access_returns_false_for_suspended(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_SUSPENDED]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->hasAccess());
    }

    public function test_is_in_grace_period(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->addDays(5),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertTrue($stateMachine->isInGracePeriod());
    }

    public function test_is_not_in_grace_period_when_expired(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->subDay(),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertFalse($stateMachine->isInGracePeriod());
    }

    public function test_transition_logs_metadata(): void
    {
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $stateMachine->transitionTo(SubscriptionStateMachine::STATE_ACTIVE, [
            'payment_id' => 'pay_123',
            'reason' => 'Initial payment successful',
        ]);

        $this->subscription->refresh();

        $this->assertDatabaseHas('subscription_status_histories', [
            'user_subscription_id' => $this->subscription->id,
            'from_status' => SubscriptionStateMachine::STATE_PENDING,
            'to_status' => SubscriptionStateMachine::STATE_ACTIVE,
        ]);
    }

    public function test_get_current_state_returns_status(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $stateMachine->getCurrentState());
    }

    public function test_get_available_transitions_returns_valid_states(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $available = $stateMachine->getAvailableTransitions();

        $this->assertContains(SubscriptionStateMachine::STATE_PAST_DUE, $available);
        $this->assertContains(SubscriptionStateMachine::STATE_CANCELED, $available);
        $this->assertNotContains(SubscriptionStateMachine::STATE_PENDING, $available);
    }

    public function test_start_grace_period(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $stateMachine->startGracePeriod(7);

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_PAST_DUE, $this->subscription->status);
        $this->assertNotNull($this->subscription->grace_period_ends_at);
        $this->assertTrue($this->subscription->grace_period_ends_at->isFuture());
    }

    public function test_cancel_subscription(): void
    {
        $this->subscription->update(['status' => SubscriptionStateMachine::STATE_ACTIVE]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $stateMachine->cancel('User requested cancellation');

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_CANCELED, $this->subscription->status);
        $this->assertNotNull($this->subscription->canceled_at);
    }

    public function test_reactivate_subscription(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_CANCELED,
            'canceled_at' => now()->subDays(5),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $stateMachine->reactivate();

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $this->subscription->status);
        $this->assertNull($this->subscription->canceled_at);
    }

    public function test_expire_subscription(): void
    {
        $this->subscription->update([
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->subDay(),
        ]);
        $stateMachine = new SubscriptionStateMachine($this->subscription);

        $stateMachine->expire('Grace period ended');

        $this->subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_EXPIRED, $this->subscription->status);
    }
}
