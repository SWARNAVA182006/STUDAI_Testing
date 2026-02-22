<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryTrend extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'location',
        'industry',
        'experience_years',
        'min_salary',
        'max_salary',
        'median_salary',
        'average_salary',
        'percentile_25',
        'percentile_75',
        'percentile_90',
        'month_over_month_change',
        'year_over_year_change',
        'trend_direction',
        'predicted_change_6m',
        'predicted_change_12m',
        'job_postings_count',
        'active_candidates',
        'supply_demand_ratio',
        'currency',
        'sample_size',
        'trend_date',
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        'median_salary' => 'decimal:2',
        'average_salary' => 'decimal:2',
        'percentile_25' => 'decimal:2',
        'percentile_75' => 'decimal:2',
        'percentile_90' => 'decimal:2',
        'month_over_month_change' => 'decimal:2',
        'year_over_year_change' => 'decimal:2',
        'predicted_change_6m' => 'decimal:2',
        'predicted_change_12m' => 'decimal:2',
        'job_postings_count' => 'integer',
        'active_candidates' => 'integer',
        'supply_demand_ratio' => 'decimal:2',
        'sample_size' => 'integer',
        'trend_date' => 'date',
    ];

    /**
     * Get latest salary trend for role/location
     */
    public static function getLatest(string $role, ?string $location = null): ?self
    {
        return static::where('role', $role)
            ->when($location, fn($q) => $q->where('location', $location))
            ->latest('trend_date')
            ->first();
    }

    /**
     * Get historical trends for charting
     */
    public static function getHistorical(string $role, int $months = 12): \Illuminate\Support\Collection
    {
        return static::where('role', $role)
            ->where('trend_date', '>=', now()->subMonths($months))
            ->orderBy('trend_date')
            ->get();
    }

    /**
     * Calculate user's salary percentile
     */
    public function getUserPercentile(float $userSalary): float
    {
        if ($userSalary <= $this->percentile_25) return 25;
        if ($userSalary <= $this->median_salary) {
            // Interpolate between 25th and 50th
            $range = $this->median_salary - $this->percentile_25;
            $position = $userSalary - $this->percentile_25;
            return 25 + (($position / $range) * 25);
        }
        if ($userSalary <= $this->percentile_75) {
            // Interpolate between 50th and 75th
            $range = $this->percentile_75 - $this->median_salary;
            $position = $userSalary - $this->median_salary;
            return 50 + (($position / $range) * 25);
        }
        if ($userSalary <= $this->percentile_90) {
            // Interpolate between 75th and 90th
            $range = $this->percentile_90 - $this->percentile_75;
            $position = $userSalary - $this->percentile_75;
            return 75 + (($position / $range) * 15);
        }
        // Above 90th percentile
        return min(99, 90 + (($userSalary - $this->percentile_90) / $this->percentile_90) * 10);
    }
}
