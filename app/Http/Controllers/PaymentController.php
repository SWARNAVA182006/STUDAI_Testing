<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentGateway
    ) {}

    /**
     * Initiate payment for a subscription plan
     * 
     * POST /api/payment/initiate
     *
     * @OA\Post(
     *     path="/api/payment/initiate",
     *     operationId="initiatePayment",
     *     tags={"Payments"},
     *     summary="Initiate a payment for a subscription plan",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id","gateway"},
     *             @OA\Property(property="plan_id", type="integer", example=1),
     *             @OA\Property(property="gateway", type="string", enum={"razorpay","payu","stripe"}, example="stripe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'required|in:razorpay,payu,stripe',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $gateway = $request->gateway;

        try {
            $orderData = $this->paymentGateway->createOrder($user, $plan, $gateway);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $orderData,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Initiation Failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $gateway,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Razorpay payment success callback
     * 
     * POST /api/payment/razorpay/callback
     */
    public function razorpayCallback(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        try {
            // Verify payment signature
            $isValid = $this->paymentGateway->verifyPayment($request->all(), 'razorpay');

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 400);
            }

            // Find transaction
            $transaction = PaymentTransaction::where('transaction_id', $request->razorpay_order_id)
                ->where('payment_gateway', 'razorpay')
                ->firstOrFail();

            // Process success
            DB::beginTransaction();
            try {
                $this->paymentGateway->processSuccess($transaction, [
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'method' => $request->input('method', 'unknown'),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful! Your subscription is now active.',
                    'transaction' => $transaction->load('subscriptionPlan'),
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Razorpay Callback Processing Failed', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle PayU payment success callback
     * 
     * POST /payment/success
     */
    public function payuSuccess(Request $request)
    {
        try {
            // Verify payment
            $isValid = $this->paymentGateway->verifyPayment($request->all(), 'payu');

            if (!$isValid) {
                return redirect('/pricing')->with('error', 'Payment verification failed');
            }

            // Find transaction
            $transaction = PaymentTransaction::where('transaction_id', $request->txnid)
                ->where('payment_gateway', 'payu')
                ->firstOrFail();

            // Process success
            DB::beginTransaction();
            try {
                $this->paymentGateway->processSuccess($transaction, [
                    'txnid' => $request->txnid,
                    'status' => $request->status,
                    'amount' => $request->amount,
                    'mode' => $request->mode ?? 'unknown',
                    'bank_ref_num' => $request->bank_ref_num ?? null,
                ]);

                DB::commit();

                return redirect('/dashboard')->with('success', 'Payment successful! Your subscription is now active.');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('PayU Success Callback Processing Failed', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect('/pricing')->with('error', 'Payment processing failed');
        }
    }

    /**
     * Handle PayU payment failure callback
     * 
     * POST /payment/failure
     */
    public function payuFailure(Request $request)
    {
        try {
            // Find transaction
            $transaction = PaymentTransaction::where('transaction_id', $request->txnid)
                ->where('payment_gateway', 'payu')
                ->first();

            if ($transaction) {
                $transaction->markAsFailed(
                    $request->error_Message ?? 'Payment failed',
                    $request->all()
                );
            }

            return redirect('/pricing')->with('error', 'Payment failed. Please try again.');

        } catch (\Exception $e) {
            Log::error('PayU Failure Callback Processing Failed', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return redirect('/pricing')->with('error', 'Payment failed');
        }
    }

    /**
     * Handle Razorpay webhook
     * 
     * POST /webhooks/razorpay
     */
    public function razorpayWebhook(Request $request)
    {
        try {
            // Verify webhook signature
            $webhookSecret = config('payment.razorpay.webhook_secret');
            $webhookSignature = $request->header('X-Razorpay-Signature');
            $webhookBody = file_get_contents('php://input');

            $expectedSignature = hash_hmac('sha256', $webhookBody, $webhookSecret);

            if ($webhookSignature !== $expectedSignature) {
                Log::warning('Razorpay Webhook Signature Mismatch');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $payload = $request->all();
            $event = $payload['event'];

            // Handle different webhook events
            match($event) {
                'payment.authorized' => $this->handlePaymentAuthorized($payload),
                'payment.captured' => $this->handlePaymentCaptured($payload),
                'payment.failed' => $this->handlePaymentFailed($payload),
                'refund.created' => $this->handleRefundCreated($payload),
                default => Log::info('Unhandled Razorpay Webhook Event', ['event' => $event]),
            };

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Razorpay Webhook Processing Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Get payment history for authenticated user
     * 
     * GET /api/payment/history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $transactions = PaymentTransaction::with('subscriptionPlan')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Get single transaction details
     * 
     * GET /api/payment/transaction/{id}
     */
    public function transaction(PaymentTransaction $transaction, Request $request)
    {
        $user = $request->user();

        // Ensure user owns this transaction
        if ($transaction->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($transaction->load('subscriptionPlan'));
    }

    /**
     * Request refund for a transaction
     * 
     * POST /api/payment/refund/{transaction}
     */
    public function requestRefund(PaymentTransaction $transaction, Request $request)
    {
        $user = $request->user();

        // Validate ownership
        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate transaction is refundable
        if (!$transaction->is_successful) {
            return response()->json(['message' => 'Only successful payments can be refunded'], 400);
        }

        if ($transaction->is_refunded) {
            return response()->json(['message' => 'Transaction already refunded'], 400);
        }

        try {
            $success = $this->paymentGateway->processRefund($transaction);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'transaction' => $transaction->fresh()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Refund Request Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Refund request failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Webhook event handlers
     */
    protected function handlePaymentAuthorized(array $payload): void
    {
        Log::info('Payment Authorized', ['payload' => $payload]);
    }

    protected function handlePaymentCaptured(array $payload): void
    {
        $orderId = $payload['payload']['payment']['entity']['order_id'];
        
        $transaction = PaymentTransaction::where('transaction_id', $orderId)->first();
        
        if ($transaction && $transaction->status === PaymentTransaction::STATUS_PENDING) {
            $this->paymentGateway->processSuccess($transaction, $payload['payload']['payment']['entity']);
        }
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
        
        if ($orderId) {
            $transaction = PaymentTransaction::where('transaction_id', $orderId)->first();
            
            if ($transaction) {
                $transaction->markAsFailed(
                    $payload['payload']['payment']['entity']['error_description'] ?? 'Payment failed',
                    $payload['payload']['payment']['entity']
                );
            }
        }
    }

    protected function handleRefundCreated(array $payload): void
    {
        Log::info('Refund Created', ['payload' => $payload]);
    }
}

