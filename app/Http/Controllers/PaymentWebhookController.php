<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected $paymentService;
    
    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Handle Razorpay webhook
     */
    public function razorpayWebhook(Request $request)
    {
        // Verify webhook signature
        $webhookSecret = config('services.razorpay.webhook_secret');
        $webhookSignature = $request->header('X-Razorpay-Signature');
        $webhookBody = $request->getContent();
        
        $expectedSignature = hash_hmac('sha256', $webhookBody, $webhookSecret);
        
        if ($webhookSignature !== $expectedSignature) {
            Log::error('Razorpay webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }
        
        $event = $request->all();
        
        Log::info('Razorpay webhook received', ['event' => $event['event']]);
        
        switch ($event['event']) {
            case 'payment.captured':
                $this->handleRazorpayPaymentCaptured($event['payload']['payment']['entity']);
                break;
                
            case 'payment.failed':
                $this->handleRazorpayPaymentFailed($event['payload']['payment']['entity']);
                break;
                
            default:
                Log::info('Unhandled Razorpay webhook event: ' . $event['event']);
        }
        
        return response()->json(['status' => 'success']);
    }
    
    /**
     * Handle Razorpay payment captured
     */
    protected function handleRazorpayPaymentCaptured($payment)
    {
        $orderId = $payment['order_id'];
        $paymentId = $payment['id'];
        
        $transaction = PaymentTransaction::where('gateway_order_id', $orderId)->first();
        
        if (!$transaction) {
            Log::error('Transaction not found for Razorpay order: ' . $orderId);
            return;
        }
        
        if ($transaction->status === 'completed') {
            Log::info('Transaction already completed: ' . $transaction->id);
            return;
        }
        
        $this->paymentService->processSuccessfulPayment($transaction, $paymentId);
        
        Log::info('Razorpay payment processed successfully', [
            'transaction_id' => $transaction->transaction_id,
            'payment_id' => $paymentId,
        ]);
    }
    
    /**
     * Handle Razorpay payment failed
     */
    protected function handleRazorpayPaymentFailed($payment)
    {
        $orderId = $payment['order_id'];
        
        $transaction = PaymentTransaction::where('gateway_order_id', $orderId)->first();
        
        if ($transaction) {
            $transaction->markFailed();
            Log::info('Razorpay payment failed', ['transaction_id' => $transaction->transaction_id]);
        }
    }
    
    /**
     * Handle Razorpay payment callback (frontend redirect)
     */
    public function razorpayCallback(Request $request)
    {
        $razorpayOrderId = $request->razorpay_order_id;
        $razorpayPaymentId = $request->razorpay_payment_id;
        $razorpaySignature = $request->razorpay_signature;
        
        if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Payment verification failed');
        }
        
        // Verify signature
        $isValid = $this->paymentService->verifyRazorpayPayment(
            $razorpayOrderId,
            $razorpayPaymentId,
            $razorpaySignature
        );
        
        if (!$isValid) {
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Payment verification failed');
        }
        
        // Find transaction
        $transaction = PaymentTransaction::where('gateway_order_id', $razorpayOrderId)->first();
        
        if (!$transaction) {
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Transaction not found');
        }
        
        // Process payment if not already processed
        if ($transaction->status !== 'completed') {
            $this->paymentService->processSuccessfulPayment($transaction, $razorpayPaymentId, $razorpaySignature);
        }
        
        return redirect()->route('subscriptions.index')
            ->with('success', 'Payment successful! Your subscription is now active.');
    }
    
    /**
     * Handle PayU success callback
     */
    public function payuSuccess(Request $request)
    {
        $response = $request->all();
        
        Log::info('PayU success callback received', $response);
        
        // Verify hash
        $isValid = $this->paymentService->verifyPayUPayment($response);
        
        if (!$isValid) {
            Log::error('PayU hash verification failed', $response);
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Payment verification failed');
        }
        
        $txnId = $response['txnid'];
        $paymentId = $response['mihpayid'] ?? $txnId;
        $status = $response['status'];
        
        $transaction = PaymentTransaction::where('gateway_order_id', $txnId)->first();
        
        if (!$transaction) {
            Log::error('Transaction not found for PayU txnid: ' . $txnId);
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Transaction not found');
        }
        
        if ($status === 'success') {
            $this->paymentService->processSuccessfulPayment($transaction, $paymentId);
            
            return redirect()->route('subscriptions.index')
                ->with('success', 'Payment successful! Your subscription is now active.');
        } else {
            $transaction->markFailed();
            
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Payment failed: ' . ($response['error_Message'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Handle PayU failure callback
     */
    public function payuFailure(Request $request)
    {
        $response = $request->all();
        
        Log::info('PayU failure callback received', $response);
        
        $txnId = $response['txnid'] ?? null;
        
        if ($txnId) {
            $transaction = PaymentTransaction::where('gateway_order_id', $txnId)->first();
            
            if ($transaction) {
                $transaction->markFailed();
            }
        }
        
        return redirect()->route('subscriptions.pricing')
            ->with('error', 'Payment failed. Please try again.');
    }
}
