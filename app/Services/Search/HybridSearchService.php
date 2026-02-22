<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\JobListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Builder;
use MeiliSearch\Client as MeiliSearchClient;

/**
 * Hybrid Search Service
 *
 * Combines keyword search (Meilisearch) with semantic search (vector embeddings)
 * using Reciprocal Rank Fusion (RRF) for optimal result ranking.
 *
 * Search Flow:
 * 1. Query → Meilisearch keyword search → Top 100 keyword matches
 * 2. Query → Embed query → Vector similarity → Top 100 semantic matches
 * 3. Reciprocal Rank Fusion → Merged ranked list
 * 4. Return top N results
 *
 * Usage:
 *   $results = app(HybridSearchService::class)->search('remote python developer', [
 *       'limit' => 20,
 *       'filters' => ['location' => 'Remote'],
 *   ]);
 */
class HybridSearchService
{
    /**
     * Weight for keyword search results.
     */
    protected const KEYWORD_WEIGHT = 0.7;

    /**
     * Weight for semantic search results.
     */
    protected const SEMANTIC_WEIGHT = 0.3;

    /**
     * RRF constant (k parameter).
     */
    protected const RRF_K = 60;

    /**
     * Cache TTL for search results (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Vector search service.
     */
    protected VectorSearchService $vectorSearch;

    /**
     * Create a new HybridSearchService instance.
     */
    public function __construct(VectorSearchService $vectorSearch)
    {
        $this->vectorSearch = $vectorSearch;
    }

    /**
     * Perform hybrid search.
     */
    public function search(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;
        $filters = $options['filters'] ?? [];
        $useCache = $options['use_cache'] ?? true;
        $keywordWeight = $options['keyword_weight'] ?? self::KEYWORD_WEIGHT;
        $semanticWeight = $options['semantic_weight'] ?? self::SEMANTIC_WEIGHT;

        // Cache key based on query and filters
        $cacheKey = $this->getCacheKey($query, $filters, $limit);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $startTime = microtime(true);

        // Get results from both search methods
        $keywordResults = $this->keywordSearch($query, 100, $filters);
        $semanticResults = $this->semanticSearch($query, 100);

        // Merge results using RRF
        $mergedResults = $this->mergeWithRRF(
            $keywordResults,
            $semanticResults,
            $keywordWeight,
            $semanticWeight
        );

        // Get top N results
        $finalResults = array_slice($mergedResults, 0, $limit);

        // Enrich with job data
        $enrichedResults = $this->enrichResults($finalResults);

        $latencyMs = (microtime(true) - $startTime) * 1000;

        $result = [
            'results' => $enrichedResults,
            'total' => count($mergedResults),
            'query' => $query,
            'latency_ms' => round($latencyMs, 2),
            'search_type' => 'hybrid',
            'keyword_count' => count($keywordResults),
            'semantic_count' => count($semanticResults),
        ];

        if ($useCache) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        Log::debug('Hybrid search completed', [
            'query' => $query,
            'keyword_results' => count($keywordResults),
            'semantic_results' => count($semanticResults),
            'merged_results' => count($mergedResults),
            'latency_ms' => $result['latency_ms'],
        ]);

        return $result;
    }

    /**
     * Perform keyword search using Meilisearch.
     */
    protected function keywordSearch(string $query, int $limit, array $filters = []): array
    {
        try {
            $builder = JobListing::search($query);

            // Apply filters
            if (!empty($filters['location'])) {
                $builder->where('location', $filters['location']);
            }

            if (!empty($filters['job_type'])) {
                $builder->where('job_type', $filters['job_type']);
            }

            if (!empty($filters['experience_level'])) {
                $builder->where('experience_level', $filters['experience_level']);
            }

            if (!empty($filters['salary_min'])) {
                $builder->where('salary_min', '>=', $filters['salary_min']);
            }

            if (!empty($filters['is_remote'])) {
                $builder->where('is_remote', $filters['is_remote']);
            }

            if (!empty($filters['company_id'])) {
                $builder->where('company_id', $filters['company_id']);
            }

            $results = $builder->take($limit)->get();

            return $results->map(function ($job, $rank) {
                return [
                    'id' => $job->id,
                    'rank' => $rank + 1,
                    'source' => 'keyword',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('Keyword search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            // Fallback to database search
            return $this->fallbackKeywordSearch($query, $limit, $filters);
        }
    }

    /**
     * Fallback keyword search using database.
     */
    protected function fallbackKeywordSearch(string $query, int $limit, array $filters = []): array
    {
        $builder = JobListing::query()
            ->where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereJsonContains('skills', $query);
            });

        // Apply filters
        if (!empty($filters['location'])) {
            $builder->where('location', $filters['location']);
        }

        if (!empty($filters['job_type'])) {
            $builder->where('job_type', $filters['job_type']);
        }

        if (!empty($filters['is_remote'])) {
            $builder->where('is_remote', $filters['is_remote']);
        }

        $results = $builder->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $results->map(function ($job, $rank) {
            return [
                'id' => $job->id,
                'rank' => $rank + 1,
                'source' => 'database',
            ];
        })->toArray();
    }

    /**
     * Perform semantic search using vector embeddings.
     */
    protected function semanticSearch(string $query, int $limit): array
    {
        if (!$this->vectorSearch->isAvailable()) {
            return [];
        }

        try {
            $results = $this->vectorSearch->search($query, $limit, 0.5);

            // Convert to ranked format
            return array_map(function ($result, $rank) {
                return [
                    'id' => $result['id'],
                    'rank' => $rank + 1,
                    'source' => 'semantic',
                    'similarity' => $result['score'],
                ];
            }, $results, array_keys($results));
        } catch (\Exception $e) {
            Log::warning('Semantic search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Merge results using Reciprocal Rank Fusion (RRF).
     *
     * RRF Score = Σ (weight / (k + rank))
     * where k is a constant (typically 60) and rank is the position in each list.
     */
    protected function mergeWithRRF(
        array $keywordResults,
        array $semanticResults,
        float $keywordWeight,
        float $semanticWeight
    ): array {
        $scores = [];

        // Add keyword scores
        foreach ($keywordResults as $result) {
            $id = $result['id'];
            $rrfScore = $keywordWeight / (self::RRF_K + $result['rank']);

            if (!isset($scores[$id])) {
                $scores[$id] = [
                    'id' => $id,
                    'score' => 0,
                    'keyword_rank' => null,
                    'semantic_rank' => null,
                    'sources' => [],
                ];
            }

            $scores[$id]['score'] += $rrfScore;
            $scores[$id]['keyword_rank'] = $result['rank'];
            $scores[$id]['sources'][] = 'keyword';
        }

        // Add semantic scores
        foreach ($semanticResults as $result) {
            $id = $result['id'];
            $rrfScore = $semanticWeight / (self::RRF_K + $result['rank']);

            if (!isset($scores[$id])) {
                $scores[$id] = [
                    'id' => $id,
                    'score' => 0,
                    'keyword_rank' => null,
                    'semantic_rank' => null,
                    'sources' => [],
                ];
            }

            $scores[$id]['score'] += $rrfScore;
            $scores[$id]['semantic_rank'] = $result['rank'];
            $scores[$id]['semantic_similarity'] = $result['similarity'] ?? null;
            $scores[$id]['sources'][] = 'semantic';
        }

        // Sort by score descending
        uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_values($scores);
    }

    /**
     * Enrich results with full job data.
     */
    protected function enrichResults(array $results): array
    {
        $jobIds = array_column($results, 'id');

        $jobs = JobListing::with(['company'])
            ->whereIn('id', $jobIds)
            ->get()
            ->keyBy('id');

        return array_map(function ($result) use ($jobs) {
            $job = $jobs[$result['id']] ?? null;

            return [
                'id' => $result['id'],
                'job' => $job,
                'score' => round($result['score'], 4),
                'keyword_rank' => $result['keyword_rank'],
                'semantic_rank' => $result['semantic_rank'],
                'semantic_similarity' => $result['semantic_similarity'] ?? null,
                'sources' => array_unique($result['sources']),
                'is_hybrid' => count($result['sources']) > 1,
            ];
        }, $results);
    }

    /**
     * Get cache key for search query.
     */
    protected function getCacheKey(string $query, array $filters, int $limit): string
    {
        $filterHash = md5(json_encode($filters));
        return "hybrid_search:" . md5("{$query}:{$filterHash}:{$limit}");
    }

    /**
     * Clear search cache.
     */
    public function clearCache(?string $query = null): void
    {
        if ($query) {
            // Clear specific query cache
            $pattern = "hybrid_search:" . md5($query);
            Cache::forget($pattern);
        }

        // Note: Full cache clear requires Redis SCAN or similar
        Log::info('Hybrid search cache cleared', ['query' => $query]);
    }

    /**
     * Search with keyword only (fallback mode).
     */
    public function keywordOnly(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;
        $filters = $options['filters'] ?? [];

        $results = $this->keywordSearch($query, $limit, $filters);

        return [
            'results' => $this->enrichResults($results),
            'total' => count($results),
            'query' => $query,
            'search_type' => 'keyword_only',
        ];
    }

    /**
     * Search with semantic only.
     */
    public function semanticOnly(string $query, array $options = []): array
    {
        $limit = $options['limit'] ?? 20;

        $results = $this->semanticSearch($query, $limit);

        return [
            'results' => $this->enrichResults($results),
            'total' => count($results),
            'query' => $query,
            'search_type' => 'semantic_only',
        ];
    }

    /**
     * Check if hybrid search is fully available.
     */
    public function isFullyAvailable(): bool
    {
        return $this->vectorSearch->isAvailable();
    }
}
