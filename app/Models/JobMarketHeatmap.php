<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class JobMarketHeatmap extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'location_type',
        'latitude',
        'longitude',
        'industry',
        'job_category',
        'job_count',
        'application_count',
        'avg_salary',
        'median_salary',
        'competition_score',
        'demand_score',
        'growth_rate',
        'period_date',
        'period_type',
        'top_skills',
        'top_companies',
        'salary_ranges',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'avg_salary' => 'decimal:2',
        'median_salary' => 'decimal:2',
        'competition_score' => 'decimal:2',
        'demand_score' => 'decimal:2',
        'growth_rate' => 'decimal:2',
        'period_date' => 'date',
        'top_skills' => 'array',
        'top_companies' => 'array',
        'salary_ranges' => 'array',
    ];

    /**
     * Scope to filter by location.
     */
    public function scopeForLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope to filter by industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope to filter by job category.
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('job_category', $category);
    }

    /**
     * Scope for date range.
     */
    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('period_date', [$start, $end]);
    }

    /**
     * Scope for period type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('period_type', $type);
    }

    /**
     * Get locations with high demand.
     */
    public function scopeHighDemand(Builder $query, float $threshold = 70): Builder
    {
        return $query->where('demand_score', '>=', $threshold);
    }

    /**
     * Get locations with low competition.
     */
    public function scopeLowCompetition(Builder $query, float $threshold = 40): Builder
    {
        return $query->where('competition_score', '<=', $threshold);
    }

    /**
     * Format for map display.
     */
    public function toMapData(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'location' => $this->location,
            'jobCount' => $this->job_count,
            'avgSalary' => $this->avg_salary,
            'demandScore' => $this->demand_score,
            'competitionScore' => $this->competition_score,
            'growthRate' => $this->growth_rate,
        ];
    }
}
