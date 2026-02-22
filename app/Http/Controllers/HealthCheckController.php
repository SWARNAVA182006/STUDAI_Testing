<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CircuitBreakerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 *
 * Provides health check endpoints for monitoring and load balancer configuration.
 *
 * Endpoints:
 * - GET /health - Basic liveness check (always returns 200 if app is running)
 * - GET /ready  - Full readiness check (returns 200 only if all dependencies are healthy)
 */
class HealthCheckController extends Controller
{
    /**
     * Basic liveness check.
     *
     * Returns 200 if the application is running and can respond to requests.
     * This is used by load balancers to determine if the instance is alive.
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Full readiness check.
     *
     * Checks all critical dependencies (database, cache, queue, search).
     * Returns 200 only if all checks pass. Used by load balancers to determine
     * if the instance is ready to receive traffic.
     *
     * @return JsonResponse
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'redis' => $this->checkRedis(),
        ];

        // Optional checks (don't fail readiness if not configured)
        $optionalChecks = [
            'meilisearch' => $this->checkMeilisearch(),
            'ai_circuit' => $this->checkAICircuitBreaker(),
        ];

        $allHealthy = true;
        $details = [];

        foreach ($checks as $name => $result) {
            $details[$name] = $result;
            if ($result['status'] !== 'healthy') {
                $allHealthy = false;
            }
        }

        foreach ($optionalChecks as $name => $result) {
            $details[$name] = $result;
            // Optional checks don't affect overall health
        }

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'status' => $allHealthy ? 'ready' : 'not_ready',
            'timestamp' => now()->toIso8601String(),
            'checks' => $details,
        ], $statusCode);
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_' . time();

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved !== $testValue) {
                throw new \Exception('Cache read/write mismatch');
            }

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connectivity.
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');

            // For database queue, just verify the table exists
            if ($connection === 'database') {
                DB::table('jobs')->count();

                return [
                    'status' => 'healthy',
                    'driver' => 'database',
                ];
            }

            // For Redis queue, check Redis connection
            if ($connection === 'redis') {
                $redis = Queue::connection()->getRedis();
                $redis->ping();

                return [
                    'status' => 'healthy',
                    'driver' => 'redis',
                ];
            }

            return [
                'status' => 'healthy',
                'driver' => $connection,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    protected function checkRedis(): array
    {
        try {
            // Skip if Redis is not configured
            if (config('database.redis.client') === null) {
                return [
                    'status' => 'skipped',
                    'reason' => 'Redis not configured',
                ];
            }

            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            // Redis might not be required in some configurations
            if (config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
                return [
                    'status' => 'skipped',
                    'reason' => 'Redis not required for current configuration',
                ];
            }

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Meilisearch connectivity (optional).
     */
    protected function checkMeilisearch(): array
    {
        try {
            // Skip if not configured
            if (config('scout.driver') !== 'meilisearch') {
                return [
                    'status' => 'skipped',
                    'reason' => 'Meilisearch not configured as Scout driver',
                ];
            }

            $host = config('scout.meilisearch.host');

            if (empty($host)) {
                return [
                    'status' => 'skipped',
                    'reason' => 'Meilisearch host not configured',
                ];
            }

            // Simple health check via HTTP
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $response = $client->get($host . '/health');

            if ($response->getStatusCode() === 200) {
                return [
                    'status' => 'healthy',
                    'host' => $host,
                ];
            }

            return [
                'status' => 'unhealthy',
                'error' => 'Unexpected response from Meilisearch',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check AI service circuit breaker status.
     */
    protected function checkAICircuitBreaker(): array
    {
        try {
            $circuitBreaker = CircuitBreakerService::forAzureOpenAI();
            $stats = $circuitBreaker->getStats();

            return [
                'status' => $stats['state'] === 'closed' ? 'healthy' : 'degraded',
                'circuit_state' => $stats['state'],
                'failure_count' => $stats['failure_count'],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'error' => $e->getMessage(),
            ];
        }
    }
}
