<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TimeToHireMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'job_id',
        'industry',
        'job_category',
        'experience_level',
        'avg_days_to_first_application',
        'avg_days_to_first_interview',
        'avg_days_to_offer',
        'avg_days_to_hire',
        'median_days_to_hire',
        'min_days_to_hire',
        'max_days_to_hire',
        'sample_size',
        'stage_breakdown',
        'period_date',
        'period_type',
    ];

    protected $casts = [
        'avg_days_to_first_application' => 'decimal:2',
        'avg_days_to_first_interview' => 'decimal:2',
        'avg_days_to_offer' => 'decimal:2',
        'avg_days_to_hire' => 'decimal:2',
        'median_days_to_hire' => 'decimal:2',
        'min_days_to_hire' => 'decimal:2',
        'max_days_to_hire' => 'decimal:2',
        'stage_breakdown' => 'array',
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
     * Get the job.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Scope for employer.
     */
    public function scopeForEmployer(Builder $query, int $employerId): Builder
    {
        return $query->where('employer_id', $employerId);
    }

    /**
     * Scope for industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Get metrics summary.
     */
    public function getSummary(): array
    {
        return [
            'avg_days_to_hire' => $this->avg_days_to_hire,
            'median_days_to_hire' => $this->median_days_to_hire,
            'range' => $this->min_days_to_hire . ' - ' . $this->max_days_to_hire . ' days',
            'sample_size' => $this->sample_size,
            'breakdown' => $this->stage_breakdown,
        ];
    }
}
