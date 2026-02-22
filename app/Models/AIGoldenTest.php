<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * AI Golden Test Model
 *
 * Stores golden test cases for AI quality regression testing.
 * Each test case contains an input, expected output, and evaluation criteria.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string|null $prompt_name
 * @property string $input
 * @property array|null $input_variables
 * @property string $expected_output
 * @property array|null $expected_json_schema
 * @property array|null $required_keywords
 * @property array|null $forbidden_keywords
 * @property float $min_similarity_score
 * @property string $evaluation_type
 * @property bool $is_active
 * @property string|null $description
 * @property array|null $metadata
 * @property int $run_count
 * @property int $pass_count
 * @property int $fail_count
 * @property float|null $avg_similarity_score
 * @property \Carbon\Carbon|null $last_run_at
 * @property string|null $last_run_status
 * @property string|null $last_run_output
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AIGoldenTest extends Model
{
    /**
     * Evaluation types.
     */
    public const EVAL_SIMILARITY = 'similarity';
    public const EVAL_EXACT = 'exact';
    public const EVAL_CONTAINS = 'contains';
    public const EVAL_JSON_SCHEMA = 'json_schema';
    public const EVAL_KEYWORDS = 'keywords';
    public const EVAL_COMPOSITE = 'composite';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'prompt_name',
        'input',
        'input_variables',
        'expected_output',
        'expected_json_schema',
        'required_keywords',
        'forbidden_keywords',
        'min_similarity_score',
        'evaluation_type',
        'is_active',
        'description',
        'metadata',
        'run_count',
        'pass_count',
        'fail_count',
        'avg_similarity_score',
        'last_run_at',
        'last_run_status',
        'last_run_output',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_variables' => 'array',
        'expected_json_schema' => 'array',
        'required_keywords' => 'array',
        'forbidden_keywords' => 'array',
        'min_similarity_score' => 'float',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'avg_similarity_score' => 'float',
        'last_run_at' => 'datetime',
    ];

    /**
     * Get the user who created this test.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all test runs for this golden test.
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AIGoldenTestRun::class, 'golden_test_id');
    }

    /**
     * Scope to get active tests.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by prompt name.
     */
    public function scopeForPrompt(Builder $query, string $promptName): Builder
    {
        return $query->where('prompt_name', $promptName);
    }

    /**
     * Record a test run result.
     */
    public function recordRun(string $actualOutput, float $similarityScore, bool $passed, array $details = [], float $latencyMs = 0, ?string $model = null): AIGoldenTestRun
    {
        $run = $this->runs()->create([
            'actual_output' => $actualOutput,
            'similarity_score' => $similarityScore,
            'passed' => $passed,
            'evaluation_details' => $details,
            'latency_ms' => $latencyMs,
            'model_used' => $model,
        ]);

        // Update aggregate stats
        $this->run_count++;
        if ($passed) {
            $this->pass_count++;
        } else {
            $this->fail_count++;
        }

        // Update average similarity score
        $totalSimilarity = ($this->avg_similarity_score ?? 0) * ($this->run_count - 1) + $similarityScore;
        $this->avg_similarity_score = $totalSimilarity / $this->run_count;

        $this->last_run_at = now();
        $this->last_run_status = $passed ? 'passed' : 'failed';
        $this->last_run_output = substr($actualOutput, 0, 1000);
        $this->save();

        return $run;
    }

    /**
     * Get the pass rate as a percentage.
     */
    public function getPassRateAttribute(): float
    {
        if ($this->run_count === 0) {
            return 0.0;
        }

        return round(($this->pass_count / $this->run_count) * 100, 2);
    }

    /**
     * Check if this test is currently failing.
     */
    public function isFailing(): bool
    {
        return $this->last_run_status === 'failed';
    }

    /**
     * Get recent runs.
     */
    public function recentRuns(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->runs()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return static::query()
            ->distinct()
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get all available evaluation types.
     */
    public static function getEvaluationTypes(): array
    {
        return [
            self::EVAL_SIMILARITY => 'Semantic Similarity',
            self::EVAL_EXACT => 'Exact Match',
            self::EVAL_CONTAINS => 'Contains Expected',
            self::EVAL_JSON_SCHEMA => 'JSON Schema Validation',
            self::EVAL_KEYWORDS => 'Keyword Matching',
            self::EVAL_COMPOSITE => 'Composite (All Methods)',
        ];
    }
}
