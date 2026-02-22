<?php

declare(strict_types=1);

namespace Tests\Feature\Workflows;

use App\Jobs\RetryFailedPaymentJob;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\PaymentFailedNotification;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $basicPlan;
    protected SubscriptionPlan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Queue::fake();

        $this->user = User::factory()->create();
        $this->basicPlan = SubscriptionPlan::factory()->create([
            'name' => 'Basic',
            'price' => 999.00,
            'interval' => 'monthly',
            'is_active' => true,
        ]);
        $this->proPlan = SubscriptionPlan::factory()->create([
            'name' => 'Pro',
            'price' => 2499.00,
            'interval' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_complete_subscription_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Step 1: View available plans
        $plansResponse = $this->getJson('/api/subscriptions/plans');
        $plansResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($plansResponse->json('data')));

        // Step 2: Initiate payment for Basic plan
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'order_basic_123',
                'amount' => 99900,
                'currency' => 'INR',
                'status' => 'created',
            ], 200),
        ]);

        $initiateResponse = $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->basicPlan->id,
            'gateway' => 'razorpay',
        ]);
        $initiateResponse->assertStatus(200);

        // Step 3: Complete payment (simulate callback)
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'pay_basic_456',
                'status' => 'captured',
            ], 200),
        ]);

        $callbackResponse = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_basic_456',
            'razorpay_order_id' => 'order_basic_123',
            'razorpay_signature' => $this->generateSignature('order_basic_123', 'pay_basic_456'),
        ]);
        $callbackResponse->assertStatus(200);

        // Verify subscription is active
        $subscription = UserSubscription::where('user_id', $this->user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $subscription->status);
        $this->assertEquals($this->basicPlan->id, $subscription->subscription_plan_id);

        // Step 4: View active subscription
        $subscriptionResponse = $this->getJson('/api/subscriptions/current');
        $subscriptionResponse->assertStatus(200)
            ->assertJson([
                'plan_name' => 'Basic',
                'status' => 'active',
            ]);
    }

    public function test_subscription_upgrade_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Create existing Basic subscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        // Step 1: Preview upgrade proration
        $previewResponse = $this->getJson("/api/subscriptions/preview-change?plan_id={$this->proPlan->id}");
        $previewResponse->assertStatus(200)
            ->assertJsonStructure([
                'current_plan',
                'new_plan',
                'credit_amount',
                'amount_due',
            ]);

        // Step 2: Initiate upgrade payment
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'order_upgrade_123',
                'amount' => 150000, // Prorated amount
                'currency' => 'INR',
                'status' => 'created',
            ], 200),
        ]);

        $upgradeResponse = $this->postJson('/api/subscriptions/upgrade', [
            'plan_id' => $this->proPlan->id,
            'gateway' => 'razorpay',
        ]);
        $upgradeResponse->assertStatus(200);

        // Step 3: Complete upgrade payment
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'pay_upgrade_456',
                'status' => 'captured',
            ], 200),
        ]);

        $callbackResponse = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_upgrade_456',
            'razorpay_order_id' => 'order_upgrade_123',
            'razorpay_signature' => $this->generateSignature('order_upgrade_123', 'pay_upgrade_456'),
        ]);
        $callbackResponse->assertStatus(200);

        // Verify subscription upgraded
        $subscription->refresh();
        $this->assertEquals($this->proPlan->id, $subscription->subscription_plan_id);
    }

    public function test_subscription_downgrade_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Create existing Pro subscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_ends_at' => now()->addDays(15),
        ]);

        // Schedule downgrade (takes effect at period end)
        $downgradeResponse = $this->postJson('/api/subscriptions/downgrade', [
            'plan_id' => $this->basicPlan->id,
        ]);
        $downgradeResponse->assertStatus(200)
            ->assertJson([
                'scheduled' => true,
            ]);

        // Verify scheduled change
        $subscription->refresh();
        $this->assertEquals($this->basicPlan->id, $subscription->scheduled_plan_id);
        $this->assertEquals($this->proPlan->id, $subscription->subscription_plan_id); // Still Pro until period end
    }

    public function test_subscription_cancellation_workflow(): void
    {
        Sanctum::actingAs($this->user);

        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_ends_at' => now()->addDays(20),
        ]);

        // Step 1: Request cancellation
        $cancelResponse = $this->postJson('/api/subscriptions/cancel', [
            'reason' => 'No longer needed',
            'feedback' => 'Great product, but budget constraints',
        ]);
        $cancelResponse->assertStatus(200);

        // Verify cancellation scheduled
        $subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_CANCELED, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);

        // Verify access continues until period end
        $stateMachine = new SubscriptionStateMachine($subscription);
        // Canceled subscriptions don't have access
        $this->assertFalse($stateMachine->hasAccess());
    }

    public function test_payment_failure_and_retry_workflow(): void
    {
        Queue::fake();

        // Create active subscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'current_period_ends_at' => now()->subDay(), // Period ended
        ]);

        // Simulate renewal payment failure
        $stateMachine = new SubscriptionStateMachine($subscription);
        $stateMachine->startGracePeriod(7);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_PAST_DUE, $subscription->status);
        $this->assertNotNull($subscription->grace_period_ends_at);

        // Verify retry job was dispatched
        Queue::assertPushed(RetryFailedPaymentJob::class);

        // User should still have access during grace period
        $this->assertTrue($stateMachine->isInGracePeriod());
    }

    public function test_grace_period_expiration_workflow(): void
    {
        Notification::fake();

        // Create past_due subscription with expired grace period
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_PAST_DUE,
            'grace_period_ends_at' => now()->subDay(), // Grace period ended
        ]);

        // Execute grace period check
        $stateMachine = new SubscriptionStateMachine($subscription);
        $stateMachine->expire('Grace period ended');

        $subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_EXPIRED, $subscription->status);

        // User notified
        Notification::assertSentTo(
            $this->user,
            PaymentFailedNotification::class
        );
    }

    public function test_subscription_reactivation_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Create expired subscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_EXPIRED,
        ]);

        // Step 1: User initiates reactivation
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'order_reactivate_123',
                'amount' => 99900,
                'status' => 'created',
            ], 200),
        ]);

        $reactivateResponse = $this->postJson('/api/subscriptions/reactivate', [
            'plan_id' => $this->basicPlan->id,
            'gateway' => 'razorpay',
        ]);
        $reactivateResponse->assertStatus(200);

        // Step 2: Complete payment
        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'pay_reactivate_456',
                'status' => 'captured',
            ], 200),
        ]);

        $callbackResponse = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_reactivate_456',
            'razorpay_order_id' => 'order_reactivate_123',
            'razorpay_signature' => $this->generateSignature('order_reactivate_123', 'pay_reactivate_456'),
        ]);
        $callbackResponse->assertStatus(200);

        // Verify reactivation
        $subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_ACTIVE, $subscription->status);
    }

    public function test_trial_subscription_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Create trial plan
        $trialPlan = SubscriptionPlan::factory()->create([
            'name' => 'Pro Trial',
            'price' => 0,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        // Start trial
        $trialResponse = $this->postJson('/api/subscriptions/start-trial', [
            'plan_id' => $trialPlan->id,
        ]);
        $trialResponse->assertStatus(201);

        // Verify trial subscription
        $subscription = UserSubscription::where('user_id', $this->user->id)->first();
        $this->assertEquals(SubscriptionStateMachine::STATE_TRIALING, $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);

        // User has access during trial
        $stateMachine = new SubscriptionStateMachine($subscription);
        $this->assertTrue($stateMachine->hasAccess());
    }

    public function test_refund_within_period_workflow(): void
    {
        Sanctum::actingAs($this->user);

        // Create recent subscription
        $subscription = UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
            'created_at' => now()->subDays(3), // Within 7-day refund period
        ]);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => $this->basicPlan->price,
            'status' => 'completed',
            'created_at' => now()->subDays(3),
        ]);

        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'rfnd_123',
                'status' => 'processed',
            ], 200),
        ]);

        // Request refund
        $refundResponse = $this->postJson("/api/payment/refund/{$transaction->id}", [
            'reason' => 'Changed my mind',
        ]);
        $refundResponse->assertStatus(200);

        // Verify subscription canceled
        $subscription->refresh();
        $this->assertEquals(SubscriptionStateMachine::STATE_CANCELED, $subscription->status);
    }

    protected function generateSignature(string $orderId, string $paymentId): string
    {
        $secret = config('payment.razorpay.key_secret', 'test_secret');
        return hash_hmac('sha256', "{$orderId}|{$paymentId}", $secret);
    }
}
