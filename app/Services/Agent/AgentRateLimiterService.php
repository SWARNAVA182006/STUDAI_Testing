<?php

declare(strict_types=1);

namespace App\Services\Agent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized rate limiter for all agent operations.
 *
 * Provides unified rate-limiting for scrapers, API calls,
 * and application submissions instead of ad-hoc per-service throttling.
 */
class AgentRateLimiterService
{
    /**
     * Default limits (per minute) by operation type.
     */
    private const DEFAULT_LIMITS = [
        'scraper_request'           => 10,
        'api_request'               => 30,
        'application_submission'    => 5,
        'resume_parse'              => 20,
        'job_search'                => 15,
        'indeed_api'                => 10,
        'linkedin_api'              => 10,
        'rss_fetch'                 => 20,
    ];

    /**
     * Check if an operation is allowed under the rate limit.
     */
    public function attempt(string $operation, ?string $identifier = null, ?int $maxAttempts = null, int $decaySeconds = 60): bool
    {
        $key = $this->buildKey($operation, $identifier);
        $limit = $maxAttempts ?? $this->getLimit($operation);

        $current = (int) Cache::get($key, 0);

        if ($current >= $limit) {
            Log::debug('Agent rate limit hit', [
                'operation'  => $operation,
                'identifier' => $identifier,
                'current'    => $current,
                'limit'      => $limit,
            ]);

            return false;
        }

        Cache::put($key, $current + 1, now()->addSeconds($decaySeconds));

        return true;
    }

    /**
     * Get remaining attempts for an operation.
     */
    public function remaining(string $operation, ?string $identifier = null): int
    {
        $key = $this->buildKey($operation, $identifier);
        $limit = $this->getLimit($operation);
        $current = (int) Cache::get($key, 0);

        return max(0, $limit - $current);
    }

    /**
     * Check if the operation is currently rate-limited.
     */
    public function isLimited(string $operation, ?string $identifier = null): bool
    {
        return $this->remaining($operation, $identifier) <= 0;
    }

    /**
     * Reset the rate limit counter for an operation.
     */
    public function reset(string $operation, ?string $identifier = null): void
    {
        Cache::forget($this->buildKey($operation, $identifier));
    }

    /**
     * Execute a callback only if the rate limit allows.
     *
     * @template T
     * @param string    $operation
     * @param callable  $callback
     * @param ?string   $identifier
     * @return T|null
     */
    public function throttle(string $operation, callable $callback, ?string $identifier = null): mixed
    {
        if (!$this->attempt($operation, $identifier)) {
            return null;
        }

        return $callback();
    }

    /**
     * Get the configured limit for an operation.
     */
    private function getLimit(string $operation): int
    {
        return (int) config(
            "agent.rate_limits.{$operation}",
            self::DEFAULT_LIMITS[$operation] ?? 30
        );
    }

    /**
     * Build the cache key for an operation.
     */
    private function buildKey(string $operation, ?string $identifier): string
    {
        $suffix = $identifier ? ":{$identifier}" : '';

        return "agent_rate_limit:{$operation}{$suffix}";
    }
}
