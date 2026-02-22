<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency Middleware
 *
 * Prevents duplicate processing of POST/PUT/PATCH requests by storing
 * and returning cached responses for requests with the same idempotency key.
 *
 * This is critical for payment endpoints to prevent double-charging.
 *
 * Usage:
 * - Client sends X-Idempotency-Key header with a unique value (e.g., UUID)
 * - If the same key is sent again within the TTL, the cached response is returned
 * - If no key is sent, the request is processed normally (no idempotency protection)
 *
 * Configuration:
 * - Default TTL: 24 hours for payment endpoints, 1 hour for others
 * - Applies only to POST, PUT, PATCH methods
 */
class IdempotencyMiddleware
{
    /**
     * The header name for idempotency key.
     */
    public const HEADER_NAME = 'X-Idempotency-Key';

    /**
     * Header to indicate response was from cache.
     */
    public const CACHED_HEADER = 'X-Idempotency-Replay';

    /**
     * Default TTL in seconds.
     */
    protected const DEFAULT_TTL = 3600; // 1 hour

    /**
     * TTL for payment endpoints in seconds.
     */
    protected const PAYMENT_TTL = 86400; // 24 hours

    /**
     * HTTP methods that support idempotency.
     */
    protected const IDEMPOTENT_METHODS = ['POST', 'PUT', 'PATCH'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $ttlType = null): Response
    {
        // Only apply to idempotent methods
        if (!in_array($request->method(), self::IDEMPOTENT_METHODS)) {
            return $next($request);
        }

        // Check for idempotency key header
        $idempotencyKey = $request->header(self::HEADER_NAME);

        if (!$idempotencyKey) {
            // No idempotency key provided - process normally
            return $next($request);
        }

        // Validate key format (must be valid UUID or string up to 64 chars)
        if (strlen($idempotencyKey) > 64) {
            return response()->json([
                'error' => 'Invalid Idempotency Key',
                'message' => 'Idempotency key must be 64 characters or less',
            ], 400);
        }

        $userId = $request->user()?->id;
        $endpoint = $request->path();

        // Check for existing cached response
        $cached = IdempotencyKey::findValid($idempotencyKey, $userId, $endpoint);

        if ($cached) {
            Log::info('Idempotency cache hit', [
                'key' => $idempotencyKey,
                'user_id' => $userId,
                'endpoint' => $endpoint,
            ]);

            // Return cached response
            $response = response($cached->response_body, $cached->response_status);

            // Restore original headers
            if ($cached->response_headers) {
                foreach ($cached->response_headers as $name => $value) {
                    $response->headers->set($name, $value);
                }
            }

            // Mark as replay
            $response->headers->set(self::CACHED_HEADER, 'true');

            return $response;
        }

        // Process the request
        $response = $next($request);

        // Store response for idempotency (only for successful responses or client errors)
        if ($response->getStatusCode() < 500) {
            $this->storeResponse(
                $idempotencyKey,
                $userId,
                $endpoint,
                $request->method(),
                $response,
                $this->getTtl($ttlType, $endpoint)
            );
        }

        return $response;
    }

    /**
     * Store the response for future idempotent requests.
     */
    protected function storeResponse(
        string $key,
        ?int $userId,
        string $endpoint,
        string $method,
        Response $response,
        int $ttl
    ): void {
        try {
            // Extract important headers to preserve
            $headersToPreserve = ['Content-Type', 'X-Correlation-ID'];
            $headers = [];

            foreach ($headersToPreserve as $headerName) {
                if ($response->headers->has($headerName)) {
                    $headers[$headerName] = $response->headers->get($headerName);
                }
            }

            IdempotencyKey::create([
                'key' => $key,
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'method' => $method,
                'response_status' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
                'response_headers' => $headers,
                'expires_at' => now()->addSeconds($ttl),
            ]);

            Log::debug('Idempotency response stored', [
                'key' => $key,
                'endpoint' => $endpoint,
                'status' => $response->getStatusCode(),
                'ttl' => $ttl,
            ]);
        } catch (\Exception $e) {
            // Don't fail the request if storage fails
            Log::warning('Failed to store idempotency response', [
                'key' => $key,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the TTL based on endpoint type.
     */
    protected function getTtl(?string $ttlType, string $endpoint): int
    {
        if ($ttlType === 'payment') {
            return self::PAYMENT_TTL;
        }

        // Auto-detect payment endpoints
        if (str_contains($endpoint, 'payment') || str_contains($endpoint, 'subscribe')) {
            return self::PAYMENT_TTL;
        }

        return self::DEFAULT_TTL;
    }
}
