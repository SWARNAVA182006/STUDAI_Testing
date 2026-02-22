<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Razorpay\Api\Api as RazorpayApi;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\StripeGatewayService;

class PaymentGatewayService
{
    protected RazorpayApi $razorpay;
    protected array $payuConfig;
    protected StripeGatewayService $stripe;

    public function __construct(StripeGatewayService $stripe)
    {
        // Initialize Razorpay
        $this->razorpay = new RazorpayApi(
            config('payment.razorpay.key'),
            config('payment.razorpay.secret')
        );

        // Initialize PayU config
        $this->payuConfig = config('payment.payu');

        // Initialize Stripe
        $this->stripe = $stripe;
    }

    /**
     * Create order in selected payment gateway
     */
    public function createOrder(User $user, SubscriptionPlan $plan, string $gateway = 'razorpay'): array
    {
        $metadata = [
            'user_id' => $user->id,
            'email' => $user->email,
            'firstname' => explode(' ', $user->name)[0],
            'phone' => $user->phone ?? '9999999999',
        ];

        return match($gateway) {
            'razorpay' => $this->createRazorpayOrder($user, $plan, $metadata),
            'payu' => $this->createPayUOrder($user, $plan, $metadata),
            'stripe' => $this->stripe->createCheckoutSession($user, $plan),
            default => throw new Exception("Unsupported payment gateway: {$gateway}"),
        };
    }

    /**
     * Create Razorpay Order
     */
    protected function createRazorpayOrder(User $user, SubscriptionPlan $plan, array $metadata): array
    {
        try {
            $orderId = 'ORD_' . time() . '_' . $user->id;
            
            $orderData = [
                'receipt' => $orderId,
                'amount' => $plan->price * 100, // Amount in paise
                'currency' => config('payment.razorpay.currency'),
                'notes' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]
            ];

            $order = $this->razorpay->order->create($orderData);

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $order->id,
                'order_id' => $orderId,
                'payment_gateway' => 'razorpay',
                'amount' => $plan->price,
                'currency' => $order->currency,
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'razorpay_order_id' => $order->id,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'order_id' => $order->id,
                'amount' => $order->amount / 100,
                'currency' => $order->currency,
                'key' => config('payment.razorpay.key'),
                'name' => config('app.name'),
                'description' => $plan->name . ' Subscription',
                'image' => config('payment.razorpay.logo'),
                'theme' => ['color' => config('payment.razorpay.theme_color')],
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone ?? '',
                ],
                'gateway' => 'razorpay',
            ];
        } catch (Exception $e) {
            Log::error('Razorpay Order Creation Failed', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create PayU Order
     */
    protected function createPayUOrder(User $user, SubscriptionPlan $plan, array $metadata): array
    {
        try {
            $txnId = 'TXN_' . time() . '_' . $user->id;
            $orderId = 'ORD_' . time() . '_' . $user->id;
            
            $payuData = [
                'key' => $this->payuConfig['merchant_key'],
                'txnid' => $txnId,
                'amount' => number_format($plan->price, 2, '.', ''),
                'productinfo' => $plan->name . ' Subscription',
                'firstname' => $metadata['firstname'],
                'email' => $metadata['email'],
                'phone' => $metadata['phone'],
                'surl' => $this->payuConfig['surl'],
                'furl' => $this->payuConfig['furl'],
                'service_provider' => 'payu_paisa',
                'udf1' => $plan->id,
                'udf2' => $user->id,
                'udf3' => $orderId,
            ];

            // Generate hash
            $hashString = $this->payuConfig['merchant_key'] . '|' . 
                         $payuData['txnid'] . '|' .
                         $payuData['amount'] . '|' .
                         $payuData['productinfo'] . '|' .
                         $payuData['firstname'] . '|' .
                         $payuData['email'] . '|||||||||||' .
                         $this->payuConfig['merchant_salt'];
            
            $payuData['hash'] = strtolower(hash('sha512', $hashString));

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'transaction_id' => $txnId,
                'order_id' => $orderId,
                'payment_gateway' => 'payu',
                'amount' => $plan->price,
                'currency' => $this->payuConfig['currency'],
                'status' => PaymentTransaction::STATUS_PENDING,
                'initiated_at' => now(),
                'metadata' => [
                    'payu_txnid' => $txnId,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'order_id' => $orderId,
                'amount' => $plan->price,
                'currency' => $this->payuConfig['currency'],
                'gateway' => 'payu',
                'payment_url' => $this->payuConfig['payment_url'],
                'form_data' => $payuData,
            ];
        } catch (Exception $e) {
            Log::error('PayU Order Creation Failed', [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify payment signature
     */
    public function verifyPayment(array $data, string $gateway): bool
    {
        return match($gateway) {
            'razorpay' => $this->verifyRazorpayPayment($data),
            'payu' => $this->verifyPayUPayment($data),
            'stripe' => true, // Stripe verification handled via webhook signature
            default => false,
        };
    }

    /**
     * Get Stripe gateway service instance.
     */
    public function getStripeService(): StripeGatewayService
    {
        return $this->stripe;
    }

    /**
     * Verify Razorpay Payment Signature
     */
    protected function verifyRazorpayPayment(array $data): bool
    {
        try {
            $attributes = [
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature']
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);
            
            return true;
        } catch (Exception $e) {
            Log::error('Razorpay Payment Verification Failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify PayU Payment
     */
    protected function verifyPayUPayment(array $data): bool
    {
        try {
            // Reverse hash calculation for PayU
            $hashString = $this->payuConfig['merchant_salt'] . '|' .
                         $data['status'] . '|||||||||||' .
                         ($data['udf3'] ?? '') . '|' .
                         ($data['udf2'] ?? '') . '|' .
                         ($data['udf1'] ?? '') . '|' .
                         $data['email'] . '|' .
                         $data['firstname'] . '|' .
                         $data['productinfo'] . '|' .
                         $data['amount'] . '|' .
                         $data['txnid'] . '|' .
                         $this->payuConfig['merchant_key'];

            $hash = strtolower(hash('sha512', $hashString));

            if ($hash === $data['hash'] && $data['status'] === 'success') {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('PayU Payment Verification Failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process payment success
     */
    public function processSuccess(PaymentTransaction $transaction, array $gatewayData): bool
    {
        try {
            // Update transaction status
            $transaction->markAsSuccess($gatewayData);

            // Extract payment method from gateway data
            $paymentMethod = $this->extractPaymentMethod($gatewayData, $transaction->payment_gateway);
            if ($paymentMethod) {
                $transaction->update(['payment_method' => $paymentMethod]);
            }

            // Activate or create subscription
            $subscription = $this->activateSubscription($transaction);

            // Dispatch event (listeners handle notification + payment logging)
            \App\Events\PaymentSucceeded::dispatch(
                $transaction->user,
                $transaction,
                $transaction->payment_gateway,
                (float) $transaction->amount
            );

            // Keep direct notification as fallback in case event listeners fail
            $transaction->user->notify(new \App\Notifications\PaymentSuccessNotification($transaction, $subscription));

            return true;
        } catch (Exception $e) {
            Log::error('Payment Success Processing Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Activate user subscription
     */
    protected function activateSubscription(PaymentTransaction $transaction): \App\Models\UserSubscription
    {
        $user = $transaction->user;
        $plan = $transaction->subscriptionPlan;

        // Check if user already has subscription
        $subscription = $user->subscription;

        if ($subscription) {
            // Update existing subscription
            $subscription->update([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($plan->duration_days),
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]);
        } else {
            // Create new subscription
            $subscription = $user->subscription()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addDays($plan->duration_days),
                'applications_limit_per_month' => $plan->applications_limit,
                'ai_credits_limit_per_month' => $plan->ai_credits,
                'applications_used_this_month' => 0,
                'ai_credits_used_this_month' => 0,
            ]);
        }

        return $subscription;
    }

    /**
     * Extract payment method from gateway response
     */
    protected function extractPaymentMethod(array $data, string $gateway): ?string
    {
        return match($gateway) {
            'razorpay' => $data['method'] ?? null,
            'payu' => $data['mode'] ?? null,
            default => null,
        };
    }

    /**
     * Process refund
     */
    public function processRefund(PaymentTransaction $transaction, ?float $amount = null): bool
    {
        $gateway = $transaction->payment_gateway;
        $refundAmount = $amount ?? $transaction->amount;

        return match($gateway) {
            'razorpay' => $this->processRazorpayRefund($transaction, $refundAmount),
            'payu' => $this->processPayURefund($transaction, $refundAmount),
            'stripe' => $this->stripe->processRefund($transaction, $refundAmount),
            default => throw new Exception("Unsupported gateway for refund: {$gateway}"),
        };
    }

    /**
     * Process Razorpay Refund
     */
    protected function processRazorpayRefund(PaymentTransaction $transaction, float $amount): bool
    {
        try {
            $payment = $this->razorpay->payment->fetch($transaction->transaction_id);
            $refund = $payment->refund([
                'amount' => $amount * 100,
                'notes' => [
                    'reason' => 'Customer request',
                    'transaction_id' => $transaction->id
                ]
            ]);

            $transaction->update([
                'status' => $refund->amount == $transaction->amount * 100 
                    ? PaymentTransaction::STATUS_REFUNDED 
                    : PaymentTransaction::STATUS_PARTIALLY_REFUNDED,
                'refund_amount' => $amount,
                'refund_id' => $refund->id,
                'refunded_at' => now(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Razorpay Refund Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process PayU Refund
     */
    protected function processPayURefund(PaymentTransaction $transaction, float $amount): bool
    {
        // Note: PayU refunds typically need to be processed through PayU dashboard
        // or via their refund API which requires additional setup
        
        Log::info('PayU Refund Request', [
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'note' => 'PayU refunds need manual processing through dashboard'
        ]);

        // Update transaction status
        $transaction->update([
            'status' => 'refund_pending',
            'refund_amount' => $amount,
            'notes' => 'Refund request submitted. Will be processed within 5-7 business days.',
        ]);

        return true;
    }

    /**
     * Get payment gateway configuration for frontend
     */
    public function getGatewayConfig(string $gateway): array
    {
        return match($gateway) {
            'razorpay' => [
                'key' => config('payment.razorpay.key'),
                'currency' => config('payment.razorpay.currency'),
                'name' => config('app.name'),
                'logo' => config('payment.razorpay.logo'),
                'theme_color' => config('payment.razorpay.theme_color'),
            ],
            'payu' => [
                'merchant_key' => $this->payuConfig['merchant_key'],
                'payment_url' => $this->payuConfig['payment_url'],
                'currency' => $this->payuConfig['currency'],
            ],
            'stripe' => [
                'key' => config('payment.stripe.key'),
                'currency' => config('payment.stripe.currency'),
            ],
            default => [],
        };
    }
}
