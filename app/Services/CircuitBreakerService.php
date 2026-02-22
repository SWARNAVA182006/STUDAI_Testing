<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Service
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures
 * when external services (AI APIs, payment gateways, etc.) are unavailable.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Service is down, requests fail fast without attempting
 * - HALF_OPEN: Testing if service has recovered
 *
 * Usage:
 *   $circuitBreaker = new CircuitBreakerService('azure-openai');
 *
 *   if (!$circuitBreaker->isAvailable()) {
 *       throw new ServiceUnavailableException('Service circuit is open');
 *   }
 *
 *   try {
 *       $result = $this->callExternalService();
 *       $circuitBreaker->recordSuccess();
 *       return $result;
 *   } catch (\Exception $e) {
 *       $circuitBreaker->recordFailure();
 *       throw $e;
 *   }
 */
class CircuitBreakerService
{
    /**
     * Circuit breaker states.
     */
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    /**
     * Cache key prefix for circuit breaker state.
     */
    protected const CACHE_PREFIX = 'circuit_breaker:';

    /**
     * The service identifier.
     */
    protected string $service;

    /**
     * Number of failures before opening the circuit.
     */
    protected int $failureThreshold;

    /**
     * Number of successes required to close the circuit from half-open.
     */
    protected int $successThreshold;

    /**
     * Time in seconds before attempting to recover (move from open to half-open).
     */
    protected int $recoveryTimeout;

    /**
     * Time in seconds to remember failures.
     */
    protected int $failureWindowSeconds;

    /**
     * Create a new Circuit Breaker instance.
     */
    public function __construct(
        string $service,
        ?int $failureThreshold = null,
        ?int $successThreshold = null,
        ?int $recoveryTimeout = null,
        ?int $failureWindowSeconds = null
    ) {
        $this->service = $service;
        $this->failureThreshold = $failureThreshold ?? config('circuit_breaker.failure_threshold', 5);
        $this->successThreshold = $successThreshold ?? config('circuit_breaker.success_threshold', 2);
        $this->recoveryTimeout = $recoveryTimeout ?? config('circuit_breaker.recovery_timeout', 30);
        $this->failureWindowSeconds = $failureWindowSeconds ?? config('circuit_breaker.failure_window', 60);
    }

    /**
     * Check if the service is available (circuit is not open).
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state === self::STATE_CLOSED) {
            return true;
        }

        if ($state === self::STATE_OPEN) {
            // Check if it's time to attempt recovery
            if ($this->shouldAttemptRecovery()) {
                $this->transitionTo(self::STATE_HALF_OPEN);
                return true;
            }
            return false;
        }

        // HALF_OPEN state - allow one request through to test
        return true;
    }

    /**
     * Record a successful call to the service.
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = $this->incrementSuccessCount();

            if ($successCount >= $this->successThreshold) {
                $this->transitionTo(self::STATE_CLOSED);
                $this->resetCounters();

                Log::info('Circuit breaker closed', [
                    'service' => $this->service,
                    'success_count' => $successCount,
                ]);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success
            $this->resetFailureCount();
        }
    }

    /**
     * Record a failed call to the service.
     */
    public function recordFailure(?string $reason = null): void
    {
        $state = $this->getState();
        $failureCount = $this->incrementFailureCount();

        if ($state === self::STATE_HALF_OPEN) {
            // Any failure in half-open returns to open
            $this->transitionTo(self::STATE_OPEN);
            $this->setOpenedAt(now()->timestamp);

            Log::warning('Circuit breaker reopened from half-open', [
                'service' => $this->service,
                'reason' => $reason,
            ]);
        } elseif ($state === self::STATE_CLOSED && $failureCount >= $this->failureThreshold) {
            $this->transitionTo(self::STATE_OPEN);
            $this->setOpenedAt(now()->timestamp);

            Log::warning('Circuit breaker opened', [
                'service' => $this->service,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Get the current circuit state.
     */
    public function getState(): string
    {
        return Cache::get($this->getCacheKey('state'), self::STATE_CLOSED);
    }

    /**
     * Get circuit breaker statistics.
     */
    public function getStats(): array
    {
        return [
            'service' => $this->service,
            'state' => $this->getState(),
            'failure_count' => $this->getFailureCount(),
            'success_count' => $this->getSuccessCount(),
            'opened_at' => $this->getOpenedAt(),
            'recovery_timeout' => $this->recoveryTimeout,
            'failure_threshold' => $this->failureThreshold,
            'success_threshold' => $this->successThreshold,
        ];
    }

    /**
     * Force the circuit to a specific state (for admin/testing purposes).
     */
    public function forceState(string $state): void
    {
        if (!in_array($state, [self::STATE_CLOSED, self::STATE_OPEN, self::STATE_HALF_OPEN])) {
            throw new \InvalidArgumentException("Invalid circuit state: {$state}");
        }

        $this->transitionTo($state);

        if ($state === self::STATE_OPEN) {
            $this->setOpenedAt(now()->timestamp);
        }

        Log::info('Circuit breaker state forced', [
            'service' => $this->service,
            'new_state' => $state,
        ]);
    }

    /**
     * Reset the circuit breaker to closed state.
     */
    public function reset(): void
    {
        $this->transitionTo(self::STATE_CLOSED);
        $this->resetCounters();

        Log::info('Circuit breaker reset', [
            'service' => $this->service,
        ]);
    }

    /**
     * Execute a callback with circuit breaker protection.
     *
     * @template T
     * @param callable(): T $callback
     * @param callable(): T|null $fallback
     * @return T
     * @throws \App\Exceptions\CircuitOpenException
     */
    public function execute(callable $callback, ?callable $fallback = null): mixed
    {
        if (!$this->isAvailable()) {
            if ($fallback !== null) {
                Log::info('Circuit breaker using fallback', [
                    'service' => $this->service,
                ]);
                return $fallback();
            }

            throw new \App\Exceptions\CircuitOpenException(
                "Circuit breaker is open for service: {$this->service}"
            );
        }

        try {
            $result = $callback();
            $this->recordSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($e->getMessage());
            throw $e;
        }
    }

    /**
     * Transition to a new state.
     */
    protected function transitionTo(string $state): void
    {
        Cache::put($this->getCacheKey('state'), $state, now()->addHours(24));
    }

    /**
     * Check if it's time to attempt recovery.
     */
    protected function shouldAttemptRecovery(): bool
    {
        $openedAt = $this->getOpenedAt();

        if ($openedAt === null) {
            return true;
        }

        return (now()->timestamp - $openedAt) >= $this->recoveryTimeout;
    }

    /**
     * Increment and return the failure count.
     */
    protected function incrementFailureCount(): int
    {
        $key = $this->getCacheKey('failures');

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addSeconds($this->failureWindowSeconds));
        }

        return Cache::increment($key);
    }

    /**
     * Get the current failure count.
     */
    protected function getFailureCount(): int
    {
        return (int) Cache::get($this->getCacheKey('failures'), 0);
    }

    /**
     * Reset the failure count.
     */
    protected function resetFailureCount(): void
    {
        Cache::forget($this->getCacheKey('failures'));
    }

    /**
     * Increment and return the success count (for half-open state).
     */
    protected function incrementSuccessCount(): int
    {
        $key = $this->getCacheKey('successes');

        if (!Cache::has($key)) {
            Cache::put($key, 0, now()->addMinutes(5));
        }

        return Cache::increment($key);
    }

    /**
     * Get the current success count.
     */
    protected function getSuccessCount(): int
    {
        return (int) Cache::get($this->getCacheKey('successes'), 0);
    }

    /**
     * Reset all counters.
     */
    protected function resetCounters(): void
    {
        Cache::forget($this->getCacheKey('failures'));
        Cache::forget($this->getCacheKey('successes'));
        Cache::forget($this->getCacheKey('opened_at'));
    }

    /**
     * Set the timestamp when the circuit was opened.
     */
    protected function setOpenedAt(int $timestamp): void
    {
        Cache::put($this->getCacheKey('opened_at'), $timestamp, now()->addHours(24));
    }

    /**
     * Get the timestamp when the circuit was opened.
     */
    protected function getOpenedAt(): ?int
    {
        return Cache::get($this->getCacheKey('opened_at'));
    }

    /**
     * Get the cache key for a specific attribute.
     */
    protected function getCacheKey(string $attribute): string
    {
        return self::CACHE_PREFIX . $this->service . ':' . $attribute;
    }

    /**
     * Create a circuit breaker for Azure OpenAI service.
     */
    public static function forAzureOpenAI(): self
    {
        return new self(
            'azure-openai',
            config('ai.circuit_breaker.failure_threshold', 5),
            config('ai.circuit_breaker.success_threshold', 2),
            config('ai.circuit_breaker.recovery_timeout', 30)
        );
    }

    /**
     * Create a circuit breaker for Azure Anthropic service.
     */
    public static function forAzureAnthropic(): self
    {
        return new self(
            'azure-anthropic',
            config('ai.circuit_breaker.failure_threshold', 5),
            config('ai.circuit_breaker.success_threshold', 2),
            config('ai.circuit_breaker.recovery_timeout', 30)
        );
    }

    /**
     * Create a circuit breaker for Meilisearch service.
     */
    public static function forMeilisearch(): self
    {
        return new self(
            'meilisearch',
            config('scout.circuit_breaker.failure_threshold', 3),
            config('scout.circuit_breaker.success_threshold', 1),
            config('scout.circuit_breaker.recovery_timeout', 15)
        );
    }

    /**
     * Create a circuit breaker for payment gateway service.
     */
    public static function forPaymentGateway(string $gateway = 'razorpay'): self
    {
        return new self(
            "payment-{$gateway}",
            config('payment.circuit_breaker.failure_threshold', 3),
            config('payment.circuit_breaker.success_threshold', 1),
            config('payment.circuit_breaker.recovery_timeout', 60)
        );
    }
}
