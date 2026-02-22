<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SourceAttribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'source_name',
        'source_category',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'views_count',
        'applications_count',
        'interviews_count',
        'hires_count',
        'cost_per_click',
        'cost_per_application',
        'cost_per_hire',
        'total_spend',
        'quality_score',
        'time_to_hire_avg',
        'period_date',
        'period_type',
    ];

    protected $casts = [
        'cost_per_click' => 'decimal:2',
        'cost_per_application' => 'decimal:2',
        'cost_per_hire' => 'decimal:2',
        'total_spend' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'time_to_hire_avg' => 'decimal:2',
        'period_date' => 'date',
    ];

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    /**
     * Scope for employer.
     */
    public function scopeForEmployer(Builder $query, int $employerId): Builder
    {
        return $query->where('employer_id', $employerId);
    }

    /**
     * Scope for source category.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('source_category', $category);
    }

    /**
     * Scope for period.
     */
    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('period_date', [$start, $end]);
    }

    /**
     * Get ROI metrics.
     */
    public function getRoiMetrics(): array
    {
        $roi = $this->total_spend > 0 ? ($this->hires_count * 50000 - $this->total_spend) / $this->total_spend * 100 : 0;
        
        return [
            'source' => $this->source_name,
            'category' => $this->source_category,
            'applications' => $this->applications_count,
            'hires' => $this->hires_count,
            'cost_per_hire' => $this->cost_per_hire,
            'quality_score' => $this->quality_score,
            'roi' => round($roi, 2),
        ];
    }
}
