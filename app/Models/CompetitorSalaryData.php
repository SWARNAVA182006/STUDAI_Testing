<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CompetitorSalaryData extends Model
{
    use HasFactory;

    protected $table = 'competitor_salary_data';

    protected $fillable = [
        'job_title',
        'normalized_title',
        'industry',
        'location',
        'company_size',
        'company_type',
        'avg_salary',
        'median_salary',
        'percentile_25',
        'percentile_75',
        'market_rate',
        'deviation_from_market',
        'sample_size',
        'benefits_comparison',
        'equity_data',
        'currency',
        'data_date',
    ];

    protected $casts = [
        'avg_salary' => 'decimal:2',
        'median_salary' => 'decimal:2',
        'percentile_25' => 'decimal:2',
        'percentile_75' => 'decimal:2',
        'market_rate' => 'decimal:2',
        'deviation_from_market' => 'decimal:2',
        'benefits_comparison' => 'array',
        'equity_data' => 'array',
        'data_date' => 'date',
    ];

    /**
     * Scope for job title.
     */
    public function scopeForTitle(Builder $query, string $title): Builder
    {
        return $query->where('normalized_title', 'like', "%{$title}%");
    }

    /**
     * Scope for industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope for location.
     */
    public function scopeForLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope for company size.
     */
    public function scopeForCompanySize(Builder $query, string $size): Builder
    {
        return $query->where('company_size', $size);
    }

    /**
     * Get comparison data.
     */
    public function getComparisonData(): array
    {
        return [
            'job_title' => $this->job_title,
            'industry' => $this->industry,
            'location' => $this->location,
            'avg_salary' => $this->avg_salary,
            'median_salary' => $this->median_salary,
            'market_rate' => $this->market_rate,
            'deviation' => $this->deviation_from_market,
            'company_size' => $this->company_size,
            'percentile_range' => '$' . number_format($this->percentile_25) . ' - $' . number_format($this->percentile_75),
        ];
    }
}
