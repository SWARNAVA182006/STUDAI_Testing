<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Job Embedding Model
 *
 * Stores vector embeddings for job listings to enable semantic search.
 * Embeddings are generated using Azure OpenAI's text-embedding-3-large model.
 *
 * @property int $id
 * @property int $job_listing_id
 * @property array $embedding
 * @property string $model_version
 * @property int $dimensions
 * @property string|null $content_hash
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\JobListing $jobListing
 */
class JobEmbedding extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'job_embeddings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'job_listing_id',
        'embedding',
        'model_version',
        'dimensions',
        'content_hash',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'embedding' => 'array',
        'dimensions' => 'integer',
    ];

    /**
     * Get the job listing that owns this embedding.
     */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    /**
     * Calculate cosine similarity between this embedding and another.
     */
    public function cosineSimilarity(array $otherEmbedding): float
    {
        $embedding = $this->embedding;

        if (count($embedding) !== count($otherEmbedding)) {
            throw new \InvalidArgumentException('Embedding dimensions must match');
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($embedding); $i++) {
            $dotProduct += $embedding[$i] * $otherEmbedding[$i];
            $normA += $embedding[$i] * $embedding[$i];
            $normB += $otherEmbedding[$i] * $otherEmbedding[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Calculate Euclidean distance between this embedding and another.
     */
    public function euclideanDistance(array $otherEmbedding): float
    {
        $embedding = $this->embedding;

        if (count($embedding) !== count($otherEmbedding)) {
            throw new \InvalidArgumentException('Embedding dimensions must match');
        }

        $sum = 0.0;
        for ($i = 0; $i < count($embedding); $i++) {
            $diff = $embedding[$i] - $otherEmbedding[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Check if the embedding needs to be regenerated.
     */
    public function needsRegeneration(string $currentContentHash): bool
    {
        return $this->content_hash !== $currentContentHash;
    }

    /**
     * Find jobs with similar embeddings.
     */
    public static function findSimilar(array $queryEmbedding, int $limit = 20, float $minSimilarity = 0.7): array
    {
        $embeddings = static::with('jobListing')->get();
        $results = [];

        foreach ($embeddings as $jobEmbedding) {
            $similarity = $jobEmbedding->cosineSimilarity($queryEmbedding);

            if ($similarity >= $minSimilarity) {
                $results[] = [
                    'job_listing_id' => $jobEmbedding->job_listing_id,
                    'job_listing' => $jobEmbedding->jobListing,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity descending
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $limit);
    }

    /**
     * Generate content hash for a job listing.
     */
    public static function generateContentHash(JobListing $job): string
    {
        $content = implode('|', [
            $job->title,
            $job->description ?? '',
            $job->requirements ?? '',
            implode(',', $job->skills ?? []),
        ]);

        return hash('sha256', $content);
    }
}
