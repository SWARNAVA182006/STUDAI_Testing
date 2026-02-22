<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ApplicationFunnel extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'job_id',
        'industry',
        'job_category',
        'views_count',
        'applications_count',
        'screening_count',
        'interview_count',
        'offer_count',
        'hired_count',
        'rejected_count',
        'withdrawn_count',
        'view_to_apply_rate',
        'apply_to_screen_rate',
        'screen_to_interview_rate',
        'interview_to_offer_rate',
        'offer_to_hire_rate',
        'overall_conversion_rate',
        'period_date',
        'period_type',
    ];

    protected $casts = [
        'view_to_apply_rate' => 'decimal:2',
        'apply_to_screen_rate' => 'decimal:2',
        'screen_to_interview_rate' => 'decimal:2',
        'interview_to_offer_rate' => 'decimal:2',
        'offer_to_hire_rate' => 'decimal:2',
        'overall_conversion_rate' => 'decimal:2',
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
     * Scope for job.
     */
    public function scopeForJob(Builder $query, int $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope for period.
     */
    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('period_date', [$start, $end]);
    }

    /**
     * Get funnel data for visualization.
     */
    public function getFunnelData(): array
    {
        return [
            ['stage' => 'Views', 'count' => $this->views_count, 'rate' => 100],
            ['stage' => 'Applications', 'count' => $this->applications_count, 'rate' => $this->view_to_apply_rate],
            ['stage' => 'Screening', 'count' => $this->screening_count, 'rate' => $this->apply_to_screen_rate],
            ['stage' => 'Interview', 'count' => $this->interview_count, 'rate' => $this->screen_to_interview_rate],
            ['stage' => 'Offer', 'count' => $this->offer_count, 'rate' => $this->interview_to_offer_rate],
            ['stage' => 'Hired', 'count' => $this->hired_count, 'rate' => $this->offer_to_hire_rate],
        ];
    }
}
