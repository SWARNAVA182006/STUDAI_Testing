<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI Golden Test Run Model
 *
 * Stores individual test run results for golden tests.
 *
 * @property int $id
 * @property int $golden_test_id
 * @property string $actual_output
 * @property float|null $similarity_score
 * @property bool $passed
 * @property array|null $evaluation_details
 * @property float $latency_ms
 * @property string|null $model_used
 * @property array|null $errors
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AIGoldenTestRun extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'golden_test_id',
        'actual_output',
        'similarity_score',
        'passed',
        'evaluation_details',
        'latency_ms',
        'model_used',
        'errors',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'similarity_score' => 'float',
        'passed' => 'boolean',
        'evaluation_details' => 'array',
        'latency_ms' => 'float',
        'errors' => 'array',
    ];

    /**
     * Get the golden test this run belongs to.
     */
    public function goldenTest(): BelongsTo
    {
        return $this->belongsTo(AIGoldenTest::class, 'golden_test_id');
    }

    /**
     * Check if this run passed.
     */
    public function isPassed(): bool
    {
        return $this->passed;
    }

    /**
     * Get formatted latency.
     */
    public function getFormattedLatencyAttribute(): string
    {
        if ($this->latency_ms >= 1000) {
            return round($this->latency_ms / 1000, 2) . 's';
        }

        return round($this->latency_ms, 2) . 'ms';
    }
}
