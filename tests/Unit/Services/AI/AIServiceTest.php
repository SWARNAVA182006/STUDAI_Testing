<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Services\AI\AIService;
use App\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AIServiceTest extends TestCase
{
    protected AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = app(AIService::class);
    }

    public function test_generate_text_returns_string(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Generated response text']]
                ],
                'usage' => ['total_tokens' => 100]
            ], 200)
        ]);

        $result = $this->aiService->generateText('Test prompt');

        $this->assertIsString($result);
        $this->assertEquals('Generated response text', $result);
    }

    public function test_generate_json_returns_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"key": "value", "number": 42}']]
                ],
                'usage' => ['total_tokens' => 50]
            ], 200)
        ]);

        $result = $this->aiService->generateJSON('Return JSON');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals(42, $result['number']);
    }

    public function test_generate_json_handles_markdown_wrapped_json(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "```json\n{\"wrapped\": true}\n```"]]
                ],
                'usage' => ['total_tokens' => 30]
            ], 200)
        ]);

        $result = $this->aiService->generateJSON('Return wrapped JSON');

        $this->assertIsArray($result);
        $this->assertTrue($result['wrapped']);
    }

    public function test_generate_text_with_system_prompt(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'System-aware response']]
                ],
                'usage' => ['total_tokens' => 75]
            ], 200)
        ]);

        $result = $this->aiService->generateText(
            'User prompt',
            'You are a helpful assistant.'
        );

        $this->assertEquals('System-aware response', $result);
        Http::assertSent(function ($request) {
            $body = $request->data();
            return isset($body['messages']) &&
                   $body['messages'][0]['role'] === 'system';
        });
    }

    public function test_generate_text_respects_temperature(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Creative response']]
                ],
                'usage' => ['total_tokens' => 50]
            ], 200)
        ]);

        $result = $this->aiService->generateText(
            'Be creative',
            null,
            0.9
        );

        Http::assertSent(function ($request) {
            return $request->data()['temperature'] === 0.9;
        });
    }

    public function test_generate_text_respects_max_tokens(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Short response']]
                ],
                'usage' => ['total_tokens' => 25]
            ], 200)
        ]);

        $result = $this->aiService->generateText(
            'Short response please',
            null,
            0.7,
            100
        );

        Http::assertSent(function ($request) {
            return $request->data()['max_tokens'] === 100;
        });
    }

    public function test_handles_api_error_gracefully(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Log::shouldReceive('error')->once();

        $this->expectException(\RuntimeException::class);
        $this->aiService->generateText('Test prompt');
    }

    public function test_handles_timeout(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            }
        ]);

        Log::shouldReceive('error')->once();

        $this->expectException(\RuntimeException::class);
        $this->aiService->generateText('Test prompt');
    }

    public function test_is_available_returns_true_when_service_responds(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'pong']]
                ],
                'usage' => ['total_tokens' => 5]
            ], 200)
        ]);

        $this->assertTrue($this->aiService->isAvailable());
    }

    public function test_is_available_returns_false_when_service_fails(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Down'], 500)
        ]);

        $this->assertFalse($this->aiService->isAvailable());
    }

    public function test_analyze_returns_structured_analysis(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"analysis": "Complete", "score": 85, "recommendations": ["Item 1"]}']]
                ],
                'usage' => ['total_tokens' => 100]
            ], 200)
        ]);

        $result = $this->aiService->analyze('Analyze this text');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis', $result);
        $this->assertEquals(85, $result['score']);
    }

    public function test_summarize_returns_concise_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is a concise summary of the input text.']]
                ],
                'usage' => ['total_tokens' => 40]
            ], 200)
        ]);

        $result = $this->aiService->summarize('Long text that needs summarization...');

        $this->assertIsString($result);
        $this->assertStringContainsString('summary', $result);
    }

    public function test_caches_responses_when_enabled(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Cached response']]
                ],
                'usage' => ['total_tokens' => 20]
            ], 200)
        ]);

        $this->aiService->generateText('Cacheable prompt', null, 0.7, 1000, true);
    }

    public function test_returns_cached_response_when_available(): void
    {
        Cache::shouldReceive('get')->once()->andReturn('Previously cached response');

        $result = $this->aiService->generateText('Cacheable prompt', null, 0.7, 1000, true);

        $this->assertEquals('Previously cached response', $result);
        Http::assertNothingSent();
    }

    public function test_retries_on_rate_limit(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                return Http::response(['error' => 'Rate limited'], 429);
            }
            return Http::response([
                'choices' => [
                    ['message' => ['content' => 'Success after retry']]
                ],
                'usage' => ['total_tokens' => 30]
            ], 200);
        });

        $result = $this->aiService->generateText('Test retry');

        $this->assertEquals('Success after retry', $result);
        $this->assertEquals(3, $attempts);
    }

    public function test_respects_circuit_breaker(): void
    {
        // Open the circuit breaker
        $circuitBreaker = app(CircuitBreakerService::class);

        // Simulate failures to open circuit
        for ($i = 0; $i < 5; $i++) {
            $circuitBreaker->recordFailure('azure_openai');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circuit breaker is open');

        $this->aiService->generateText('Test prompt');
    }

    public function test_generates_embeddings(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)]
                ],
                'usage' => ['total_tokens' => 10]
            ], 200)
        ]);

        $result = $this->aiService->generateEmbedding('Text to embed');

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    public function test_tracks_token_usage(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Response']]
                ],
                'usage' => [
                    'prompt_tokens' => 50,
                    'completion_tokens' => 30,
                    'total_tokens' => 80
                ]
            ], 200)
        ]);

        $this->aiService->generateText('Test usage tracking');

        $stats = $this->aiService->getUsageStats();
        $this->assertArrayHasKey('total_tokens', $stats);
        $this->assertGreaterThan(0, $stats['total_tokens']);
    }
}
