<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SkillDemandForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_name',
        'skill_category',
        'industry',
        'current_demand',
        'historical_demand_30d',
        'historical_demand_90d',
        'historical_demand_180d',
        'growth_rate_30d',
        'growth_rate_90d',
        'predicted_demand_30d',
        'predicted_demand_90d',
        'predicted_demand_180d',
        'confidence_score',
        'trend_direction',
        'avg_salary_premium',
        'related_skills',
        'complementary_skills',
        'competing_skills',
        'forecast_date',
    ];

    protected $casts = [
        'growth_rate_30d' => 'decimal:2',
        'growth_rate_90d' => 'decimal:2',
        'predicted_demand_30d' => 'decimal:2',
        'predicted_demand_90d' => 'decimal:2',
        'predicted_demand_180d' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'avg_salary_premium' => 'decimal:2',
        'related_skills' => 'array',
        'complementary_skills' => 'array',
        'competing_skills' => 'array',
        'forecast_date' => 'date',
    ];

    /**
     * Scope for rising skills.
     */
    public function scopeRising(Builder $query): Builder
    {
        return $query->where('trend_direction', 'rising');
    }

    /**
     * Scope for falling skills.
     */
    public function scopeFalling(Builder $query): Builder
    {
        return $query->where('trend_direction', 'falling');
    }

    /**
     * Scope for high confidence predictions.
     */
    public function scopeHighConfidence(Builder $query, float $threshold = 70): Builder
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    /**
     * Scope for industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope for skill category.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('skill_category', $category);
    }

    /**
     * Get the trend badge color.
     */
    public function getTrendColorAttribute(): string
    {
        return match ($this->trend_direction) {
            'rising' => 'green',
            'falling' => 'red',
            'volatile' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get forecast summary.
     */
    public function getForecastSummary(): array
    {
        return [
            'skill' => $this->skill_name,
            'current_demand' => $this->current_demand,
            'trend' => $this->trend_direction,
            'growth_30d' => $this->growth_rate_30d,
            'growth_90d' => $this->growth_rate_90d,
            'predicted_30d' => $this->predicted_demand_30d,
            'predicted_90d' => $this->predicted_demand_90d,
            'confidence' => $this->confidence_score,
            'salary_premium' => $this->avg_salary_premium,
        ];
    }
}
