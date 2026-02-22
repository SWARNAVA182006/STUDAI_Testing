<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\JobEmbedding;
use App\Models\JobListing;
use App\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Embedding Service
 *
 * Manages vector embedding generation using Azure OpenAI's text-embedding-3-large model.
 * Provides methods for generating, caching, and comparing embeddings.
 *
 * Usage:
 *   $embedding = app(EmbeddingService::class)->generate('Developer with Python experience');
 *   $similarity = app(EmbeddingService::class)->cosineSimilarity($embedding1, $embedding2);
 */
class EmbeddingService
{
    /**
     * Default embedding model.
     */
    protected const DEFAULT_MODEL = 'text-embedding-3-large';

    /**
     * Default embedding dimensions.
     */
    protected const DEFAULT_DIMENSIONS = 1536;

    /**
     * Cache TTL for embeddings (7 days).
     */
    protected const CACHE_TTL = 604800;

    /**
     * Maximum batch size for bulk embedding.
     */
    protected const MAX_BATCH_SIZE = 100;

    /**
     * Circuit breaker instance.
     */
    protected CircuitBreakerService $circuitBreaker;

    /**
     * Create a new EmbeddingService instance.
     */
    public function __construct()
    {
        $this->circuitBreaker = CircuitBreakerService::forAzureOpenAI();
    }

    /**
     * Generate an embedding for a single text.
     */
    public function generate(string $text, bool $useCache = true): array
    {
        $cacheKey = $this->getCacheKey($text);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        if (!$this->circuitBreaker->isAvailable()) {
            Log::warning('EmbeddingService circuit is open, returning empty embedding');
            return $this->getEmptyEmbedding();
        }

        try {
            $embedding = $this->callEmbeddingAPI($text);
            $this->circuitBreaker->recordSuccess();

            if ($useCache) {
                Cache::put($cacheKey, $embedding, self::CACHE_TTL);
            }

            return $embedding;
        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure($e->getMessage());
            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            return $this->getEmptyEmbedding();
        }
    }

    /**
     * Generate embeddings for multiple texts in batch.
     */
    public function generateBatch(array $texts, bool $useCache = true): array
    {
        $results = [];
        $toGenerate = [];
        $indices = [];

        // Check cache first
        foreach ($texts as $index => $text) {
            if ($useCache) {
                $cacheKey = $this->getCacheKey($text);
                $cached = Cache::get($cacheKey);

                if ($cached !== null) {
                    $results[$index] = $cached;
                    continue;
                }
            }

            $toGenerate[] = $text;
            $indices[] = $index;
        }

        // Generate missing embeddings in batches
        if (!empty($toGenerate)) {
            $batches = array_chunk($toGenerate, self::MAX_BATCH_SIZE);
            $batchIndices = array_chunk($indices, self::MAX_BATCH_SIZE);

            foreach ($batches as $batchNum => $batch) {
                if (!$this->circuitBreaker->isAvailable()) {
                    // Fill remaining with empty embeddings
                    foreach ($batchIndices[$batchNum] as $index) {
                        $results[$index] = $this->getEmptyEmbedding();
                    }
                    continue;
                }

                try {
                    $embeddings = $this->callBatchEmbeddingAPI($batch);
                    $this->circuitBreaker->recordSuccess();

                    foreach ($embeddings as $i => $embedding) {
                        $originalIndex = $batchIndices[$batchNum][$i];
                        $results[$originalIndex] = $embedding;

                        if ($useCache) {
                            $cacheKey = $this->getCacheKey($batch[$i]);
                            Cache::put($cacheKey, $embedding, self::CACHE_TTL);
                        }
                    }
                } catch (\Exception $e) {
                    $this->circuitBreaker->recordFailure($e->getMessage());
                    Log::error('Batch embedding generation failed', [
                        'error' => $e->getMessage(),
                        'batch_size' => count($batch),
                    ]);

                    // Fill failed batch with empty embeddings
                    foreach ($batchIndices[$batchNum] as $index) {
                        $results[$index] = $this->getEmptyEmbedding();
                    }
                }
            }
        }

        // Sort by original indices
        ksort($results);

        return array_values($results);
    }

    /**
     * Generate and store embedding for a job listing.
     */
    public function generateForJob(JobListing $job, bool $force = false): ?JobEmbedding
    {
        $contentHash = JobEmbedding::generateContentHash($job);

        // Check if embedding exists and is current
        $existing = JobEmbedding::where('job_listing_id', $job->id)->first();

        if ($existing && !$force && !$existing->needsRegeneration($contentHash)) {
            return $existing;
        }

        // Generate embedding from job content
        $content = $this->buildJobContent($job);
        $embedding = $this->generate($content, false);

        if (empty(array_filter($embedding))) {
            Log::warning('Failed to generate job embedding', ['job_id' => $job->id]);
            return null;
        }

        // Store or update embedding
        $jobEmbedding = JobEmbedding::updateOrCreate(
            ['job_listing_id' => $job->id],
            [
                'embedding' => $embedding,
                'model_version' => self::DEFAULT_MODEL,
                'dimensions' => self::DEFAULT_DIMENSIONS,
                'content_hash' => $contentHash,
            ]
        );

        Log::info('Job embedding generated', [
            'job_id' => $job->id,
            'is_update' => $existing !== null,
        ]);

        return $jobEmbedding;
    }

    /**
     * Find similar jobs by query text.
     */
    public function findSimilarJobs(string $query, int $limit = 20, float $minSimilarity = 0.7): array
    {
        $queryEmbedding = $this->generate($query);

        if (empty(array_filter($queryEmbedding))) {
            return [];
        }

        return JobEmbedding::findSimilar($queryEmbedding, $limit, $minSimilarity);
    }

    /**
     * Calculate cosine similarity between two embeddings.
     */
    public function cosineSimilarity(array $embedding1, array $embedding2): float
    {
        if (count($embedding1) !== count($embedding2)) {
            throw new \InvalidArgumentException('Embedding dimensions must match');
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $normA += $embedding1[$i] * $embedding1[$i];
            $normB += $embedding2[$i] * $embedding2[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Call the Azure OpenAI embedding API.
     */
    protected function callEmbeddingAPI(string $text): array
    {
        $endpoint = config('ai.azure.endpoint');
        $apiKey = config('ai.azure.api_key');
        $apiVersion = config('ai.azure.api_version');
        $model = config('ai.azure.models.embeddings', self::DEFAULT_MODEL);

        $url = rtrim($endpoint, '/') . "/openai/deployments/{$model}/embeddings?api-version={$apiVersion}";

        $response = Http::timeout(config('ai.timeout.embeddings', 15))
            ->withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'input' => $text,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Embedding API failed: ' . $response->body());
        }

        $data = $response->json();

        return $data['data'][0]['embedding'] ?? [];
    }

    /**
     * Call the Azure OpenAI embedding API for batch processing.
     */
    protected function callBatchEmbeddingAPI(array $texts): array
    {
        $endpoint = config('ai.azure.endpoint');
        $apiKey = config('ai.azure.api_key');
        $apiVersion = config('ai.azure.api_version');
        $model = config('ai.azure.models.embeddings', self::DEFAULT_MODEL);

        $url = rtrim($endpoint, '/') . "/openai/deployments/{$model}/embeddings?api-version={$apiVersion}";

        $response = Http::timeout(config('ai.timeout.embeddings', 15) * 2)
            ->withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'input' => $texts,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Batch embedding API failed: ' . $response->body());
        }

        $data = $response->json();
        $embeddings = [];

        foreach ($data['data'] ?? [] as $item) {
            $embeddings[$item['index']] = $item['embedding'];
        }

        // Sort by index
        ksort($embeddings);

        return array_values($embeddings);
    }

    /**
     * Build searchable content from a job listing.
     */
    protected function buildJobContent(JobListing $job): string
    {
        $parts = [
            $job->title,
            $job->description ?? '',
            $job->requirements ?? '',
        ];

        if (!empty($job->skills)) {
            $parts[] = 'Skills: ' . implode(', ', $job->skills);
        }

        if ($job->company) {
            $parts[] = 'Company: ' . $job->company->name;
        }

        if ($job->location) {
            $parts[] = 'Location: ' . $job->location;
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Get cache key for an embedding.
     */
    protected function getCacheKey(string $text): string
    {
        return 'embedding:' . hash('sha256', $text);
    }

    /**
     * Get an empty embedding (used as fallback).
     */
    protected function getEmptyEmbedding(): array
    {
        return array_fill(0, self::DEFAULT_DIMENSIONS, 0.0);
    }

    /**
     * Check if embedding service is available.
     */
    public function isAvailable(): bool
    {
        return $this->circuitBreaker->isAvailable();
    }
}
