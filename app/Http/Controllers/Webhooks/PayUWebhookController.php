<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PayU Server-to-Server Notification Handler
 *
 * Handles PayU's "notify_url" callback for async payment confirmations.
 * This provides a safety net beyond the client-side redirect callbacks.
 */
class PayUWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayService $paymentGateway,
    ) {}

    /**
     * Handle PayU server notification (notify_url).
     *
     * PayU sends a POST with the same parameters as the success/failure redirect,
     * but directly server-to-server so it bypasses client-side redirect failures.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        Log::info('PayU webhook notification received', [
            'txnid' => $request->input('txnid'),
            'status' => $request->input('status'),
            'ip' => $request->ip(),
        ]);

        try {
            // 1. Verify the hash signature
            if (!$this->verifySignature($request)) {
                Log::warning('PayU webhook signature verification failed', [
                    'txnid' => $request->input('txnid'),
                    'ip' => $request->ip(),
                ]);

                return response()->json(['status' => 'signature_invalid'], 403);
            }

            // 2. Find the transaction
            $txnId = $request->input('txnid');
            $transaction = PaymentTransaction::where('transaction_id', $txnId)
                ->where('payment_gateway', 'payu')
                ->first();

            if (!$transaction) {
                Log::warning('PayU webhook: transaction not found', ['txnid' => $txnId]);
                return response()->json(['status' => 'not_found'], 404);
            }

            // 3. Idempotency check — skip if already completed or failed
            if (in_array($transaction->status, ['success', 'refunded'], true)) {
                Log::info('PayU webhook: transaction already finalized', [
                    'txnid' => $txnId,
                    'status' => $transaction->status,
                ]);
                return response()->json(['status' => 'already_processed']);
            }

            $payuStatus = strtolower($request->input('status', ''));

            // 4. Process based on PayU status
            DB::beginTransaction();
            try {
                if ($payuStatus === 'success') {
                    $this->paymentGateway->processSuccess($transaction, [
                        'txnid' => $txnId,
                        'status' => $request->input('status'),
                        'amount' => $request->input('amount'),
                        'mode' => $request->input('mode', 'unknown'),
                        'bank_ref_num' => $request->input('bank_ref_num'),
                        'mihpayid' => $request->input('mihpayid'),
                        'source' => 'webhook',
                    ]);
                } elseif (in_array($payuStatus, ['failure', 'failed'], true)) {
                    $errorMessage = $request->input('error_Message', 'Payment failed');
                    $transaction->markAsFailed($errorMessage, $request->all());
                } elseif ($payuStatus === 'pending') {
                    Log::info('PayU webhook: payment pending', ['txnid' => $txnId]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('PayU webhook processing failed', [
                'txnid' => $request->input('txnid'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Verify PayU notification hash signature.
     *
     * PayU provides a reverse hash in the response using:
     * SALT|status|||||||||||udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
     */
    private function verifySignature(Request $request): bool
    {
        try {
            $salt = config('payment.payu.merchant_salt');
            $key = config('payment.payu.merchant_key');

            if (empty($salt) || empty($key)) {
                Log::error('PayU webhook verification: missing merchant_key or merchant_salt in config');
                return false;
            }

            $providedHash = $request->input('hash');
            if (empty($providedHash)) {
                return false;
            }

            // PayU reverse hash: SALT|status|||||||||||udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
            $hashString = $salt . '|'
                . ($request->input('status') ?? '') . '|||||||||||'
                . ($request->input('udf3') ?? '') . '|'
                . ($request->input('udf2') ?? '') . '|'
                . ($request->input('udf1') ?? '') . '|'
                . ($request->input('email') ?? '') . '|'
                . ($request->input('firstname') ?? '') . '|'
                . ($request->input('productinfo') ?? '') . '|'
                . ($request->input('amount') ?? '') . '|'
                . ($request->input('txnid') ?? '') . '|'
                . $key;

            $calculatedHash = strtolower(hash('sha512', $hashString));

            return hash_equals($calculatedHash, $providedHash);
        } catch (\Exception $e) {
            Log::error('PayU hash verification error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
