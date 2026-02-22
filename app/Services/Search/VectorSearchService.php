<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\JobEmbedding;
use App\Models\JobListing;
use App\Services\AI\EmbeddingService;
use App\Services\CircuitBreakerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Vector Search Service
 *
 * Provides semantic search using vector embeddings.
 * Falls back gracefully when embedding service is unavailable.
 */
class VectorSearchService
{
    /**
     * The embedding service instance.
     */
    protected EmbeddingService $embeddingService;

    /**
     * Create a new VectorSearchService instance.
     */
    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Search jobs using semantic similarity.
     */
    public function search(string $query, int $limit = 100, float $minSimilarity = 0.6): array
    {
        if (!$this->embeddingService->isAvailable()) {
            Log::warning('VectorSearch: Embedding service unavailable');
            return [];
        }

        $results = $this->embeddingService->findSimilarJobs($query, $limit, $minSimilarity);

        return array_map(function ($result) {
            return [
                'id' => $result['job_listing_id'],
                'job' => $result['job_listing'],
                'score' => $result['similarity'],
            ];
        }, $results);
    }

    /**
     * Check if vector search is available.
     */
    public function isAvailable(): bool
    {
        return $this->embeddingService->isAvailable();
    }

    /**
     * Get embedding for a query (for external use).
     */
    public function getQueryEmbedding(string $query): array
    {
        return $this->embeddingService->generate($query);
    }
}
