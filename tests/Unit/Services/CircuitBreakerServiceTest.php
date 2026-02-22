<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    protected CircuitBreakerService $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circuitBreaker = new CircuitBreakerService();
        Cache::flush();
    }

    public function test_initial_state_is_closed(): void
    {
        $state = $this->circuitBreaker->getState('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $state);
    }

    public function test_is_available_when_closed(): void
    {
        $this->assertTrue($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_records_failure(): void
    {
        $this->circuitBreaker->recordFailure('test_service');

        $this->assertEquals(1, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_opens_circuit_after_threshold_failures(): void
    {
        $threshold = 5;

        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));
        $this->assertFalse($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_is_not_available_when_open(): void
    {
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->assertFalse($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_records_success(): void
    {
        $this->circuitBreaker->recordSuccess('test_service');

        $this->assertEquals(0, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_success_resets_failure_count(): void
    {
        $this->circuitBreaker->recordFailure('test_service');
        $this->circuitBreaker->recordFailure('test_service');
        $this->circuitBreaker->recordSuccess('test_service');

        $this->assertEquals(0, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_transitions_to_half_open_after_timeout(): void
    {
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));

        // Fast forward time (mock the timeout)
        Cache::put('circuit_breaker:test_service:opened_at', now()->subSeconds(31)->timestamp, 3600);

        $state = $this->circuitBreaker->getState('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $state);
    }

    public function test_half_open_allows_single_request(): void
    {
        // Open the circuit then timeout
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }
        Cache::put('circuit_breaker:test_service:opened_at', now()->subSeconds(31)->timestamp, 3600);

        // Half-open state should allow trial request
        $this->assertTrue($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_success_in_half_open_closes_circuit(): void
    {
        // Open the circuit then timeout to half-open
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }
        Cache::put('circuit_breaker:test_service:opened_at', now()->subSeconds(31)->timestamp, 3600);

        // Record success in half-open state
        $this->circuitBreaker->recordSuccess('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
    }

    public function test_failure_in_half_open_reopens_circuit(): void
    {
        // Open the circuit then timeout to half-open
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }
        Cache::put('circuit_breaker:test_service:opened_at', now()->subSeconds(31)->timestamp, 3600);

        // Record failure in half-open state
        $this->circuitBreaker->recordFailure('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));
    }

    public function test_execute_with_fallback_returns_result_on_success(): void
    {
        $result = $this->circuitBreaker->execute(
            'test_service',
            fn() => 'success',
            fn() => 'fallback'
        );

        $this->assertEquals('success', $result);
    }

    public function test_execute_returns_fallback_when_circuit_open(): void
    {
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $result = $this->circuitBreaker->execute(
            'test_service',
            fn() => 'success',
            fn() => 'fallback'
        );

        $this->assertEquals('fallback', $result);
    }

    public function test_execute_returns_fallback_on_exception(): void
    {
        $result = $this->circuitBreaker->execute(
            'test_service',
            fn() => throw new \RuntimeException('Service failed'),
            fn() => 'fallback'
        );

        $this->assertEquals('fallback', $result);
        $this->assertEquals(1, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_execute_records_success(): void
    {
        $this->circuitBreaker->recordFailure('test_service');
        $this->circuitBreaker->recordFailure('test_service');

        $this->circuitBreaker->execute(
            'test_service',
            fn() => 'success',
            fn() => 'fallback'
        );

        $this->assertEquals(0, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_different_services_have_independent_states(): void
    {
        // Open circuit for service A
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('service_a');
        }

        // Service B should still be available
        $this->assertFalse($this->circuitBreaker->isAvailable('service_a'));
        $this->assertTrue($this->circuitBreaker->isAvailable('service_b'));
    }

    public function test_reset_clears_circuit_state(): void
    {
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->circuitBreaker->reset('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount('test_service'));
    }

    public function test_get_stats_returns_all_info(): void
    {
        $this->circuitBreaker->recordFailure('test_service');
        $this->circuitBreaker->recordFailure('test_service');

        $stats = $this->circuitBreaker->getStats('test_service');

        $this->assertArrayHasKey('state', $stats);
        $this->assertArrayHasKey('failure_count', $stats);
        $this->assertArrayHasKey('failure_threshold', $stats);
        $this->assertArrayHasKey('timeout_seconds', $stats);
        $this->assertArrayHasKey('is_available', $stats);
    }

    public function test_custom_failure_threshold(): void
    {
        $customCircuitBreaker = new CircuitBreakerService(failureThreshold: 3);

        for ($i = 0; $i < 3; $i++) {
            $customCircuitBreaker->recordFailure('test_service');
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $customCircuitBreaker->getState('test_service'));
    }

    public function test_custom_timeout(): void
    {
        $customCircuitBreaker = new CircuitBreakerService(timeoutSeconds: 60);

        // Open circuit
        for ($i = 0; $i < 5; $i++) {
            $customCircuitBreaker->recordFailure('test_service');
        }

        // After 31 seconds with default timeout, it would be half-open
        // But with 60 second timeout, it should still be open
        Cache::put('circuit_breaker:test_service:opened_at', now()->subSeconds(31)->timestamp, 3600);

        $state = $customCircuitBreaker->getState('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $state);
    }

    public function test_force_open(): void
    {
        $this->circuitBreaker->forceOpen('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $this->circuitBreaker->getState('test_service'));
        $this->assertFalse($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_force_close(): void
    {
        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->circuitBreaker->forceClose('test_service');

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $this->circuitBreaker->getState('test_service'));
        $this->assertTrue($this->circuitBreaker->isAvailable('test_service'));
    }

    public function test_records_state_transitions(): void
    {
        $transitions = [];

        $this->circuitBreaker->onStateChange(function ($service, $from, $to) use (&$transitions) {
            $transitions[] = ['service' => $service, 'from' => $from, 'to' => $to];
        });

        // Trigger state change to OPEN
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure('test_service');
        }

        $this->assertNotEmpty($transitions);
        $this->assertEquals('test_service', $transitions[0]['service']);
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $transitions[0]['from']);
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $transitions[0]['to']);
    }
}
