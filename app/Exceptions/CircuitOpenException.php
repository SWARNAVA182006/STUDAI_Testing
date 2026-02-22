<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a circuit breaker is in open state.
 *
 * This indicates that the protected service is currently unavailable
 * and requests should fail fast without attempting to call the service.
 */
class CircuitOpenException extends Exception
{
    /**
     * The name of the service that is unavailable.
     */
    protected string $service;

    /**
     * Create a new CircuitOpenException.
     */
    public function __construct(string $message = '', string $service = '', int $code = 503, ?\Throwable $previous = null)
    {
        $this->service = $service;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the service name.
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * Create an exception for a specific service.
     */
    public static function forService(string $service): self
    {
        return new self(
            "Circuit breaker is open for service: {$service}. The service is temporarily unavailable.",
            $service
        );
    }

    /**
     * Report the exception to logging channels.
     */
    public function report(): void
    {
        \Illuminate\Support\Facades\Log::warning('Circuit breaker prevented request', [
            'service' => $this->service,
            'message' => $this->getMessage(),
        ]);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Service Unavailable',
                'message' => 'The requested service is temporarily unavailable. Please try again later.',
                'service' => $this->service,
                'retry_after' => 30,
            ], 503)->header('Retry-After', '30');
        }

        return response()->view('errors.503', [
            'message' => 'The requested service is temporarily unavailable. Please try again later.',
        ], 503)->header('Retry-After', '30');
    }
}
