<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Services\AI\EmbeddingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected EmbeddingService $embeddingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->embeddingService = app(EmbeddingService::class);
    }

    public function test_generate_returns_embedding_vector(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.05)]
                ],
                'usage' => ['total_tokens' => 15]
            ], 200)
        ]);

        $result = $this->embeddingService->generate('Test text');

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
        $this->assertEquals(0.05, $result[0]);
    }

    public function test_generate_batch_returns_multiple_embeddings(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)],
                    ['embedding' => array_fill(0, 1536, 0.2)],
                    ['embedding' => array_fill(0, 1536, 0.3)],
                ],
                'usage' => ['total_tokens' => 45]
            ], 200)
        ]);

        $result = $this->embeddingService->generateBatch(['Text 1', 'Text 2', 'Text 3']);

        $this->assertCount(3, $result);
        $this->assertCount(1536, $result[0]);
        $this->assertEquals(0.1, $result[0][0]);
        $this->assertEquals(0.2, $result[1][0]);
        $this->assertEquals(0.3, $result[2][0]);
    }

    public function test_generate_batch_chunks_large_batches(): void
    {
        $callCount = 0;
        Http::fake(function () use (&$callCount) {
            $callCount++;
            return Http::response([
                'data' => array_fill(0, 10, ['embedding' => array_fill(0, 1536, 0.1)]),
                'usage' => ['total_tokens' => 100]
            ], 200);
        });

        // Batch of 25 texts should result in 3 API calls (10, 10, 5)
        $texts = array_fill(0, 25, 'Sample text');
        $result = $this->embeddingService->generateBatch($texts);

        $this->assertCount(25, $result);
        $this->assertEquals(3, $callCount);
    }

    public function test_cosine_similarity_returns_correct_value(): void
    {
        $vector1 = [1.0, 0.0, 0.0];
        $vector2 = [1.0, 0.0, 0.0];

        $similarity = $this->embeddingService->cosineSimilarity($vector1, $vector2);

        $this->assertEquals(1.0, $similarity);
    }

    public function test_cosine_similarity_orthogonal_vectors(): void
    {
        $vector1 = [1.0, 0.0, 0.0];
        $vector2 = [0.0, 1.0, 0.0];

        $similarity = $this->embeddingService->cosineSimilarity($vector1, $vector2);

        $this->assertEquals(0.0, $similarity);
    }

    public function test_cosine_similarity_opposite_vectors(): void
    {
        $vector1 = [1.0, 0.0, 0.0];
        $vector2 = [-1.0, 0.0, 0.0];

        $similarity = $this->embeddingService->cosineSimilarity($vector1, $vector2);

        $this->assertEquals(-1.0, $similarity);
    }

    public function test_cosine_similarity_partial_match(): void
    {
        $vector1 = [1.0, 1.0, 0.0];
        $vector2 = [1.0, 0.0, 0.0];

        $similarity = $this->embeddingService->cosineSimilarity($vector1, $vector2);

        // cos(45°) ≈ 0.707
        $this->assertEqualsWithDelta(0.707, $similarity, 0.01);
    }

    public function test_cosine_similarity_handles_zero_vectors(): void
    {
        $vector1 = [0.0, 0.0, 0.0];
        $vector2 = [1.0, 0.0, 0.0];

        $similarity = $this->embeddingService->cosineSimilarity($vector1, $vector2);

        $this->assertEquals(0.0, $similarity);
    }

    public function test_caches_embeddings_when_enabled(): void
    {
        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)]
                ],
                'usage' => ['total_tokens' => 10]
            ], 200)
        ]);

        $this->embeddingService->generate('Cacheable text', useCache: true);
    }

    public function test_returns_cached_embedding(): void
    {
        $cachedEmbedding = array_fill(0, 1536, 0.5);
        Cache::shouldReceive('get')->once()->andReturn($cachedEmbedding);

        $result = $this->embeddingService->generate('Cached text', useCache: true);

        $this->assertEquals($cachedEmbedding, $result);
        Http::assertNothingSent();
    }

    public function test_is_available_returns_true(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)]
                ],
                'usage' => ['total_tokens' => 5]
            ], 200)
        ]);

        $this->assertTrue($this->embeddingService->isAvailable());
    }

    public function test_is_available_returns_false_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service down'], 500)
        ]);

        $this->assertFalse($this->embeddingService->isAvailable());
    }

    public function test_handles_empty_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.0)]
                ],
                'usage' => ['total_tokens' => 1]
            ], 200)
        ]);

        $result = $this->embeddingService->generate('');

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    public function test_normalizes_text_before_embedding(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)]
                ],
                'usage' => ['total_tokens' => 10]
            ], 200)
        ]);

        $this->embeddingService->generate("  Text with   multiple\n\nspaces  ");

        Http::assertSent(function ($request) {
            $input = $request->data()['input'] ?? '';
            return !str_contains($input, '  '); // No double spaces
        });
    }

    public function test_truncates_long_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)]
                ],
                'usage' => ['total_tokens' => 8000]
            ], 200)
        ]);

        // Very long text that exceeds token limit
        $longText = str_repeat('word ', 10000);

        $result = $this->embeddingService->generate($longText);

        $this->assertIsArray($result);
        $this->assertCount(1536, $result);
    }

    public function test_find_most_similar_returns_sorted_results(): void
    {
        $query = array_fill(0, 1536, 0.5);
        $candidates = [
            'doc1' => array_fill(0, 1536, 0.5),  // Perfect match
            'doc2' => array_fill(0, 1536, 0.1),  // Low match
            'doc3' => array_fill(0, 1536, 0.4),  // Good match
        ];

        $results = $this->embeddingService->findMostSimilar($query, $candidates, 3);

        $this->assertCount(3, $results);
        $this->assertEquals('doc1', $results[0]['id']);
        $this->assertEquals('doc3', $results[1]['id']);
        $this->assertEquals('doc2', $results[2]['id']);
    }

    public function test_find_most_similar_respects_limit(): void
    {
        $query = array_fill(0, 1536, 0.5);
        $candidates = [
            'doc1' => array_fill(0, 1536, 0.5),
            'doc2' => array_fill(0, 1536, 0.4),
            'doc3' => array_fill(0, 1536, 0.3),
            'doc4' => array_fill(0, 1536, 0.2),
        ];

        $results = $this->embeddingService->findMostSimilar($query, $candidates, 2);

        $this->assertCount(2, $results);
    }

    public function test_find_most_similar_respects_threshold(): void
    {
        $query = array_fill(0, 1536, 0.5);
        $candidates = [
            'doc1' => array_fill(0, 1536, 0.5),  // Score ~1.0
            'doc2' => array_fill(0, 1536, 0.1),  // Score ~0.2
        ];

        $results = $this->embeddingService->findMostSimilar($query, $candidates, 10, 0.5);

        $this->assertCount(1, $results);
        $this->assertEquals('doc1', $results[0]['id']);
    }
}
