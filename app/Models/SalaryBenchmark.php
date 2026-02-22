<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SalaryBenchmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_title',
        'normalized_title',
        'location',
        'industry',
        'experience_level',
        'min_salary',
        'max_salary',
        'median_salary',
        'percentile_25',
        'percentile_75',
        'percentile_90',
        'sample_size',
        'yoy_change',
        'benefits_data',
        'bonus_data',
        'currency',
        'period_date',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'median_salary' => 'decimal:2',
        'percentile_25' => 'decimal:2',
        'percentile_75' => 'decimal:2',
        'percentile_90' => 'decimal:2',
        'yoy_change' => 'decimal:2',
        'benefits_data' => 'array',
        'bonus_data' => 'array',
        'period_date' => 'date',
    ];

    /**
     * Scope to filter by job title.
     */
    public function scopeForTitle(Builder $query, string $title): Builder
    {
        return $query->where('normalized_title', 'like', "%{$title}%")
            ->orWhere('job_title', 'like', "%{$title}%");
    }

    /**
     * Scope to filter by location.
     */
    public function scopeForLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope to filter by experience level.
     */
    public function scopeForExperience(Builder $query, string $level): Builder
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope for industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Get the salary range formatted.
     */
    public function getSalaryRangeAttribute(): string
    {
        return '$' . number_format($this->min_salary) . ' - $' . number_format($this->max_salary);
    }

    /**
     * Compare salary to benchmark.
     */
    public function compareToSalary(float $salary): array
    {
        $percentile = 50; // Default to median

        if ($salary <= $this->percentile_25) {
            $percentile = 25;
        } elseif ($salary <= $this->median_salary) {
            $percentile = 25 + (($salary - $this->percentile_25) / ($this->median_salary - $this->percentile_25)) * 25;
        } elseif ($salary <= $this->percentile_75) {
            $percentile = 50 + (($salary - $this->median_salary) / ($this->percentile_75 - $this->median_salary)) * 25;
        } elseif ($salary <= $this->percentile_90) {
            $percentile = 75 + (($salary - $this->percentile_75) / ($this->percentile_90 - $this->percentile_75)) * 15;
        } else {
            $percentile = 90 + min(10, (($salary - $this->percentile_90) / $this->percentile_90) * 100);
        }

        $deviation = (($salary - $this->median_salary) / $this->median_salary) * 100;

        return [
            'percentile' => round($percentile, 1),
            'deviation_from_median' => round($deviation, 1),
            'comparison' => $deviation > 10 ? 'above_market' : ($deviation < -10 ? 'below_market' : 'at_market'),
            'median_salary' => $this->median_salary,
            'sample_size' => $this->sample_size,
        ];
    }
}
