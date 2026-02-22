<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PaymentFailed;
use App\Events\PaymentInitiated;
use App\Events\PaymentSucceeded;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGatewayService
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for subscription purchase.
     */
    public function createCheckoutSession(User $user, SubscriptionPlan $plan): array
    {
        try {
            $customer = $this->getOrCreateCustomer($user);
            $orderId = 'ORD_STRIPE_' . time() . '_' . $user->id;

            $session = StripeSession::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => config('payment.stripe.currency', 'usd'),
                            'product_data' => [
                                'name' => $plan->name . ' Subscription',
                                'description' => $plan->description ?? 'StudAI Career Subscription',
                            ],
                            'unit_amount' => (int) ($plan->price * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                ],
                'success_url' => url('/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/subscriptions/pricing'),
            ]);

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $session->id,
                'order_id' => $orderId,
                'gateway_order_id' => $session->id,
                'payment_gateway' => 'stripe',
                'amount' => $plan->price,
                'currency' => config('payment.stripe.currency', 'usd'),
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'stripe_session_id' => $session->id,
                    'stripe_customer_id' => $customer->id,
                    'plan_slug' => $plan->slug ?? $plan->name,
                ],
            ]);

            PaymentInitiated::dispatch($transaction);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'order_id' => $orderId,
                'amount' => $plan->price,
                'currency' => config('payment.stripe.currency', 'usd'),
                'gateway' => 'stripe',
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout Session Creation Failed', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a PaymentIntent for direct charge.
     */
    public function createPaymentIntent(User $user, SubscriptionPlan $plan): array
    {
        try {
            $customer = $this->getOrCreateCustomer($user);
            $orderId = 'ORD_STRIPE_' . time() . '_' . $user->id;

            $intent = PaymentIntent::create([
                'amount' => (int) ($plan->price * 100),
                'currency' => config('payment.stripe.currency', 'usd'),
                'customer' => $customer->id,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'order_id' => $orderId,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $intent->id,
                'order_id' => $orderId,
                'gateway_order_id' => $intent->id,
                'payment_gateway' => 'stripe',
                'amount' => $plan->price,
                'currency' => config('payment.stripe.currency', 'usd'),
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'stripe_payment_intent_id' => $intent->id,
                    'stripe_customer_id' => $customer->id,
                    'plan_slug' => $plan->slug ?? $plan->name,
                ],
            ]);

            PaymentInitiated::dispatch($transaction);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
                'amount' => $plan->price,
                'currency' => config('payment.stripe.currency', 'usd'),
                'gateway' => 'stripe',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent Creation Failed', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle successful Stripe payment after webhook or checkout callback.
     */
    public function processSuccessfulPayment(PaymentTransaction $transaction, string $paymentIntentId): bool
    {
        try {
            if ($transaction->status === 'completed') {
                return true; // Idempotency
            }

            $transaction->update([
                'status' => PaymentTransaction::STATUS_COMPLETED ?? 'completed',
                'transaction_id' => $paymentIntentId,
                'completed_at' => now(),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]),
            ]);

            // Activate subscription
            $this->activateSubscription($transaction);

            PaymentSucceeded::dispatch($transaction);

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe Payment Processing Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Handle failed Stripe payment.
     */
    public function processFailedPayment(PaymentTransaction $transaction, string $reason = 'Unknown'): void
    {
        $transaction->update([
            'status' => 'failed',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'failure_reason' => $reason,
            ]),
        ]);

        PaymentFailed::dispatch($transaction);
    }

    /**
     * Process a Stripe refund.
     */
    public function processRefund(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        try {
            $refundAmount = $amount ?? $transaction->amount;

            $refund = Refund::create([
                'payment_intent' => $transaction->transaction_id,
                'amount' => (int) ($refundAmount * 100),
                'reason' => 'requested_by_customer',
            ]);

            $isFullRefund = $refundAmount >= $transaction->amount;

            $transaction->update([
                'status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'refund_amount' => $refundAmount,
                'refund_id' => $refund->id,
                'refunded_at' => now(),
            ]);

            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verify Stripe webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('payment.stripe.webhook_secret')
        );
    }

    /**
     * Get or create Stripe customer for user.
     */
    protected function getOrCreateCustomer(User $user): Customer
    {
        $stripeCustomerId = $user->stripe_customer_id ?? null;

        if ($stripeCustomerId) {
            try {
                return Customer::retrieve($stripeCustomerId);
            } catch (ApiErrorException $e) {
                // Customer may have been deleted, create a new one
                Log::warning('Stripe customer not found, creating new', ['user_id' => $user->id]);
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Activate user subscription after successful payment.
     */
    protected function activateSubscription(PaymentTransaction $transaction): void
    {
        $user = $transaction->user;
        $plan = $transaction->subscriptionPlan;

        if (!$plan) {
            Log::error('No subscription plan found for transaction', ['transaction_id' => $transaction->id]);
            return;
        }

        $subscription = $user->subscription;

        if ($subscription) {
            $subscription->update([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($plan->duration_days ?? 30),
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]);
        } else {
            $user->subscription()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($plan->duration_days ?? 30),
                'applications_limit_per_month' => $plan->applications_limit ?? 50,
                'ai_credits_limit_per_month' => $plan->ai_credits ?? 100,
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]);
        }
    }
}
