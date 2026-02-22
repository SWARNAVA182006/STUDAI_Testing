<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitiveBenchmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'benchmark_category',
        'user_data',
        'user_score',
        'market_average',
        'market_top_10',
        'market_top_25',
        'gaps_identified',
        'strengths_identified',
        'gap_severity',
        'improvement_actions',
        'estimated_improvement_time',
        'potential_salary_impact',
        'benchmarked_at',
    ];

    protected $casts = [
        'user_data' => 'array',
        'user_score' => 'decimal:2',
        'market_average' => 'array',
        'market_top_10' => 'array',
        'market_top_25' => 'array',
        'gaps_identified' => 'array',
        'strengths_identified' => 'array',
        'gap_severity' => 'integer',
        'improvement_actions' => 'array',
        'estimated_improvement_time' => 'integer',
        'potential_salary_impact' => 'decimal:2',
        'benchmarked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the benchmark
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get latest benchmark for user/category
     */
    public static function getLatest(int $userId, string $category): ?self
    {
        return static::where('user_id', $userId)
            ->where('benchmark_category', $category)
            ->latest('benchmarked_at')
            ->first();
    }

    /**
     * Get all benchmarks for user
     */
    public static function getUserBenchmarks(int $userId): \Illuminate\Support\Collection
    {
        return static::where('user_id', $userId)
            ->latest('benchmarked_at')
            ->get()
            ->groupBy('benchmark_category')
            ->map->first(); // Get latest of each category
    }

    /**
     * Get gap severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        if ($this->gap_severity >= 80) return 'red';
        if ($this->gap_severity >= 60) return 'orange';
        if ($this->gap_severity >= 40) return 'yellow';
        return 'green';
    }

    /**
     * Get gap severity label
     */
    public function getSeverityLabelAttribute(): string
    {
        if ($this->gap_severity >= 80) return 'Critical';
        if ($this->gap_severity >= 60) return 'High';
        if ($this->gap_severity >= 40) return 'Medium';
        return 'Low';
    }
}
