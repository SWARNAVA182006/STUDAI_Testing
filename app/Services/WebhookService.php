<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger webhooks for an event
     */
    public function trigger(string $event, array $payload, int $companyId): void
    {
        $webhooks = Webhook::where('company_id', $companyId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliver($webhook, $event, $payload);
        }
    }

    /**
     * Deliver webhook
     */
    protected function deliver(Webhook $webhook, string $event, array $payload): void
    {
        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $event,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Add metadata to payload
        $fullPayload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        // Generate signature
        $signature = $webhook->generateSignature($fullPayload);

        try {
            $startTime = microtime(true);

            // Send webhook
            $response = Http::timeout($webhook->timeout_seconds)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $fullPayload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $responseTime
                );
                $webhook->markAsTriggered();
            } else {
                $delivery->markAsFailed(
                    "HTTP {$response->status()}: {$response->body()}",
                    $response->status()
                );
                $this->scheduleRetry($delivery, $webhook);
            }

        } catch (\Exception $e) {
            Log::error("Webhook delivery failed: {$e->getMessage()}", [
                'webhook_id' => $webhook->id,
                'event' => $event,
            ]);

            $delivery->markAsFailed($e->getMessage());
            $this->scheduleRetry($delivery, $webhook);
        }
    }

    /**
     * Schedule retry for failed delivery
     */
    protected function scheduleRetry(WebhookDelivery $delivery, Webhook $webhook): void
    {
        if ($delivery->attempt_number < $webhook->retry_attempts) {
            // Create new delivery for retry
            WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'event_type' => $delivery->event_type,
                'payload' => $delivery->payload,
                'attempt_number' => $delivery->attempt_number + 1,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
