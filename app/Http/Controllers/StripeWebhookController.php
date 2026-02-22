<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\StripeGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeGatewayService $stripeService
    ) {}

    /**
     * Handle Stripe webhook events.
     *
     * POST /webhooks/stripe
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripeService->verifyWebhookSignature($payload, $sigHeader);
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: Signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type, 'id' => $event->id]);

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
            'charge.refunded' => $this->handleChargeRefunded($event->data->object),
            default => Log::info('Unhandled Stripe event: ' . $event->type),
        };

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout.session.completed event.
     */
    protected function handleCheckoutCompleted(object $session): void
    {
        $transaction = PaymentTransaction::where('gateway_order_id', $session->id)->first();

        if (!$transaction) {
            Log::error('Stripe webhook: Transaction not found for session', ['session_id' => $session->id]);
            return;
        }

        if ($transaction->status === 'completed') {
            Log::info('Stripe webhook: Transaction already completed', ['id' => $transaction->id]);
            return;
        }

        $paymentIntentId = $session->payment_intent ?? $session->id;
        $this->stripeService->processSuccessfulPayment($transaction, $paymentIntentId);

        Log::info('Stripe checkout session completed', [
            'transaction_id' => $transaction->id,
            'payment_intent' => $paymentIntentId,
        ]);
    }

    /**
     * Handle payment_intent.succeeded event.
     */
    protected function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        $transaction = PaymentTransaction::where('gateway_order_id', $paymentIntent->id)->first();

        if (!$transaction) {
            // May be handled via checkout.session.completed already
            Log::info('Stripe webhook: No direct transaction for payment_intent', ['id' => $paymentIntent->id]);
            return;
        }

        if ($transaction->status === 'completed') {
            return;
        }

        $this->stripeService->processSuccessfulPayment($transaction, $paymentIntent->id);
    }

    /**
     * Handle payment_intent.payment_failed event.
     */
    protected function handlePaymentIntentFailed(object $paymentIntent): void
    {
        $transaction = PaymentTransaction::where('gateway_order_id', $paymentIntent->id)->first();

        if (!$transaction) {
            return;
        }

        $reason = $paymentIntent->last_payment_error->message ?? 'Payment failed';
        $this->stripeService->processFailedPayment($transaction, $reason);

        Log::warning('Stripe payment failed', [
            'transaction_id' => $transaction->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Handle charge.refunded event.
     */
    protected function handleChargeRefunded(object $charge): void
    {
        Log::info('Stripe charge refunded', [
            'charge_id' => $charge->id,
            'amount_refunded' => $charge->amount_refunded / 100,
        ]);
    }

    /**
     * Handle Stripe checkout success redirect.
     *
     * GET /payment/stripe/success
     */
    public function checkoutSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Invalid payment session.');
        }

        $transaction = PaymentTransaction::where('gateway_order_id', $sessionId)->first();

        if (!$transaction) {
            return redirect()->route('subscriptions.pricing')
                ->with('error', 'Transaction not found.');
        }

        // Process if webhook hasn't already
        if ($transaction->status !== 'completed') {
            try {
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                if ($session->payment_status === 'paid') {
                    $paymentIntentId = $session->payment_intent ?? $sessionId;
                    $this->stripeService->processSuccessfulPayment($transaction, $paymentIntentId);
                }
            } catch (\Exception $e) {
                Log::error('Stripe success redirect: Failed to verify session', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('subscriptions.index')
            ->with('success', 'Payment successful! Your subscription is now active.');
    }
}
