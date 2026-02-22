<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Subscription\SubscriptionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentAPITest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->plan = SubscriptionPlan::factory()->create([
            'name' => 'Pro Plan',
            'price' => 2499.00,
            'interval' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_can_initiate_payment(): void
    {
        Sanctum::actingAs($this->user);

        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'order_123456',
                'amount' => 249900,
                'currency' => 'INR',
                'status' => 'created',
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->plan->id,
            'gateway' => 'razorpay',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order_id',
                'amount',
                'currency',
                'key',
            ]);

        $this->assertDatabaseHas('payment_transactions', [
            'user_id' => $this->user->id,
            'amount' => 2499.00,
            'status' => 'pending',
        ]);
    }

    public function test_initiate_payment_requires_plan(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/payment/initiate', [
            'gateway' => 'razorpay',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    public function test_initiate_payment_validates_plan_exists(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/payment/initiate', [
            'plan_id' => 99999,
            'gateway' => 'razorpay',
        ]);

        $response->assertStatus(422);
    }

    public function test_razorpay_callback_success(): void
    {
        Sanctum::actingAs($this->user);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'status' => 'pending',
            'amount' => 2499.00,
        ]);

        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'pay_987654',
                'status' => 'captured',
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_987654',
            'razorpay_order_id' => 'order_123456',
            'razorpay_signature' => $this->generateRazorpaySignature('order_123456', 'pay_987654'),
        ]);

        $response->assertStatus(200);

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_razorpay_callback_invalid_signature(): void
    {
        Sanctum::actingAs($this->user);

        PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_987654',
            'razorpay_order_id' => 'order_123456',
            'razorpay_signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    public function test_can_get_payment_history(): void
    {
        Sanctum::actingAs($this->user);

        PaymentTransaction::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/payment/history');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'status',
                        'gateway',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_payment_history_is_user_specific(): void
    {
        Sanctum::actingAs($this->user);

        // Create transactions for current user
        PaymentTransaction::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create transactions for another user
        $otherUser = User::factory()->create();
        PaymentTransaction::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/payment/history');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_transaction_details(): void
    {
        Sanctum::actingAs($this->user);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 2499.00,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/payment/transaction/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $transaction->id,
                'amount' => 2499.00,
                'status' => 'completed',
            ]);
    }

    public function test_cannot_get_other_users_transaction(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/payment/transaction/{$transaction->id}");

        $response->assertStatus(404);
    }

    public function test_can_request_refund(): void
    {
        Sanctum::actingAs($this->user);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'amount' => 2499.00,
            'created_at' => now()->subDays(3), // Within refund period
        ]);

        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'rfnd_123456',
                'status' => 'processed',
            ], 200),
        ]);

        $response = $this->postJson("/api/payment/refund/{$transaction->id}", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'refund_id',
                'status',
            ]);
    }

    public function test_refund_outside_period_fails(): void
    {
        Sanctum::actingAs($this->user);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'created_at' => now()->subDays(10), // Outside 7-day refund period
        ]);

        $response = $this->postJson("/api/payment/refund/{$transaction->id}", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(403);
    }

    public function test_razorpay_webhook_payment_captured(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_987654',
                        'order_id' => 'order_123456',
                        'status' => 'captured',
                        'amount' => 249900,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => $this->generateWebhookSignature($payload),
        ]);

        $response->assertStatus(200);

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
    }

    public function test_razorpay_webhook_payment_failed(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'status' => 'pending',
        ]);

        $payload = [
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_987654',
                        'order_id' => 'order_123456',
                        'status' => 'failed',
                        'error_code' => 'BAD_REQUEST_ERROR',
                        'error_description' => 'Insufficient funds',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => $this->generateWebhookSignature($payload),
        ]);

        $response->assertStatus(200);

        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
    }

    public function test_subscription_activated_on_payment_success(): void
    {
        Sanctum::actingAs($this->user);

        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'status' => 'pending',
            'amount' => $this->plan->price,
            'metadata' => ['plan_id' => $this->plan->id],
        ]);

        Http::fake([
            'api.razorpay.com/*' => Http::response([
                'id' => 'pay_987654',
                'status' => 'captured',
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/razorpay/callback', [
            'razorpay_payment_id' => 'pay_987654',
            'razorpay_order_id' => 'order_123456',
            'razorpay_signature' => $this->generateRazorpaySignature('order_123456', 'pay_987654'),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->user->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => SubscriptionStateMachine::STATE_ACTIVE,
        ]);
    }

    public function test_idempotent_webhook_processing(): void
    {
        $transaction = PaymentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'gateway_order_id' => 'order_123456',
            'gateway_payment_id' => 'pay_987654',
            'status' => 'completed', // Already processed
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_987654',
                        'order_id' => 'order_123456',
                        'status' => 'captured',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => $this->generateWebhookSignature($payload),
        ]);

        // Should succeed without duplicate processing
        $response->assertStatus(200);

        // Should still be only one transaction
        $this->assertEquals(1, PaymentTransaction::where('gateway_order_id', 'order_123456')->count());
    }

    public function test_payment_requires_authentication(): void
    {
        $response = $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->plan->id,
        ]);

        $response->assertStatus(401);
    }

    protected function generateRazorpaySignature(string $orderId, string $paymentId): string
    {
        $secret = config('payment.razorpay.key_secret', 'test_secret');
        return hash_hmac('sha256', "{$orderId}|{$paymentId}", $secret);
    }

    protected function generateWebhookSignature(array $payload): string
    {
        $secret = config('payment.razorpay.webhook_secret', 'test_webhook_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
