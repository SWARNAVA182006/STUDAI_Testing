<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketDataSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_type',
        'role',
        'location',
        'industry',
        'sample_size',
        'metrics',
        'salary_data',
        'skill_distribution',
        'trend_indicators',
        'ai_analysis',
        'predictions',
        'confidence_score',
        'snapshot_date',
        'analyzed_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'salary_data' => 'array',
        'skill_distribution' => 'array',
        'trend_indicators' => 'array',
        'predictions' => 'array',
        'confidence_score' => 'decimal:2',
        'snapshot_date' => 'date',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the latest snapshot for specific parameters
     */
    public static function getLatest(string $type, ?string $role = null, ?string $location = null): ?self
    {
        return static::where('snapshot_type', $type)
            ->when($role, fn($q) => $q->where('role', $role))
            ->when($location, fn($q) => $q->where('location', $location))
            ->latest('snapshot_date')
            ->first();
    }

    /**
     * Get historical snapshots for trend analysis
     */
    public static function getHistorical(string $type, int $days = 90, ?string $role = null): \Illuminate\Support\Collection
    {
        return static::where('snapshot_type', $type)
            ->when($role, fn($q) => $q->where('role', $role))
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date')
            ->get();
    }
}
