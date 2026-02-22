<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to generate and propagate correlation IDs for request tracing.
 *
 * This enables end-to-end request tracing across logs, services, and async jobs.
 * The correlation ID is:
 * - Generated if not present in incoming X-Correlation-ID header
 * - Stored in request attributes for access throughout the request lifecycle
 * - Added to all log entries via Log::shareContext()
 * - Returned in response X-Correlation-ID header
 */
class CorrelationIdMiddleware
{
    /**
     * The header name for correlation ID.
     */
    public const HEADER_NAME = 'X-Correlation-ID';

    /**
     * The request attribute key for correlation ID.
     */
    public const ATTRIBUTE_KEY = 'correlation_id';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get existing correlation ID from header or generate new UUID
        $correlationId = $request->header(self::HEADER_NAME) ?? Str::uuid()->toString();

        // Store in request attributes for access throughout the application
        $request->attributes->set(self::ATTRIBUTE_KEY, $correlationId);

        // Add to all log entries for this request
        Log::shareContext([self::ATTRIBUTE_KEY => $correlationId]);

        // Process the request
        $response = $next($request);

        // Add correlation ID to response headers
        $response->headers->set(self::HEADER_NAME, $correlationId);

        return $response;
    }

    /**
     * Get the correlation ID from the current request.
     *
     * Helper method to retrieve correlation ID from anywhere in the application.
     */
    public static function getCorrelationId(): ?string
    {
        $request = request();

        if ($request && $request->attributes->has(self::ATTRIBUTE_KEY)) {
            return $request->attributes->get(self::ATTRIBUTE_KEY);
        }

        return null;
    }
}
