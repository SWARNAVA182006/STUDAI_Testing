<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HirePerformance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hire_performance_tracking';

    protected $fillable = [
        'application_id',
        'company_id',
        'job_id',
        'user_id',
        'hire_date',
        'review_period',
        'performance_rating',
        'technical_skills_rating',
        'soft_skills_rating',
        'cultural_fit_rating',
        'productivity_rating',
        'team_collaboration_rating',
        'leadership_rating',
        'retention_status',
        'promotion_count',
        'manager_feedback',
        'peer_feedback',
        'achievements',
        'challenges',
        'actual_vs_predicted_performance',
        'metadata'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'performance_rating' => 'decimal:2',
        'technical_skills_rating' => 'decimal:2',
        'soft_skills_rating' => 'decimal:2',
        'cultural_fit_rating' => 'decimal:2',
        'productivity_rating' => 'decimal:2',
        'team_collaboration_rating' => 'decimal:2',
        'leadership_rating' => 'decimal:2',
        'promotion_count' => 'integer',
        'achievements' => 'array',
        'challenges' => 'array',
        'actual_vs_predicted_performance' => 'array',
        'metadata' => 'array'
    ];

    // Relationships

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // The hired candidate
    }

    // Scopes

    public function scopeHighPerformers($query)
    {
        return $query->where('performance_rating', '>=', 4.0);
    }

    public function scopeLowPerformers($query)
    {
        return $query->where('performance_rating', '<', 3.0);
    }

    public function scopeActive($query)
    {
        return $query->where('retention_status', 'active');
    }

    public function scopeRetained($query)
    {
        return $query->whereIn('retention_status', ['active', 'promoted', 'transferred']);
    }

    public function scopeAttrited($query)
    {
        return $query->whereIn('retention_status', ['resigned_early', 'terminated']);
    }

    public function scopePromoted($query)
    {
        return $query->where('promotion_count', '>', 0);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByReviewPeriod($query, string $period)
    {
        return $query->where('review_period', $period);
    }

    public function scopeRecentHires($query, int $months = 6)
    {
        return $query->where('hire_date', '>=', now()->subMonths($months));
    }

    // Accessors

    public function getIsHighPerformerAttribute(): bool
    {
        return $this->performance_rating >= 4.0;
    }

    public function getIsLowPerformerAttribute(): bool
    {
        return $this->performance_rating < 3.0;
    }

    public function getIsRetainedAttribute(): bool
    {
        return in_array($this->retention_status, ['active', 'promoted', 'transferred']);
    }

    public function getPerformanceLevelAttribute(): string
    {
        $rating = $this->performance_rating;

        if ($rating >= 4.5) return 'Exceptional';
        if ($rating >= 4.0) return 'High Performer';
        if ($rating >= 3.5) return 'Good';
        if ($rating >= 3.0) return 'Meets Expectations';
        if ($rating >= 2.0) return 'Below Expectations';
        return 'Needs Improvement';
    }

    public function getTenureMonthsAttribute(): int
    {
        return $this->hire_date->diffInMonths(now());
    }

    public function getWasPromotedAttribute(): bool
    {
        return $this->promotion_count > 0;
    }

    public function getPredictionAccuracyAttribute(): ?float
    {
        if (!$this->actual_vs_predicted_performance) {
            return null;
        }

        return $this->actual_vs_predicted_performance['accuracy'] ?? null;
    }

    public function getPredictionVarianceAttribute(): ?float
    {
        if (!$this->actual_vs_predicted_performance) {
            return null;
        }

        return $this->actual_vs_predicted_performance['variance'] ?? null;
    }

    public function getOverallFitScoreAttribute(): float
    {
        // Weighted average of all ratings
        $scores = array_filter([
            $this->technical_skills_rating,
            $this->soft_skills_rating,
            $this->cultural_fit_rating,
            $this->productivity_rating,
            $this->team_collaboration_rating,
            $this->leadership_rating
        ]);

        return empty($scores) ? 0.0 : array_sum($scores) / count($scores);
    }

    public function getAchievementCountAttribute(): int
    {
        return is_array($this->achievements) ? count($this->achievements) : 0;
    }

    public function getChallengeCountAttribute(): int
    {
        return is_array($this->challenges) ? count($this->challenges) : 0;
    }

    // Methods

    public function hasPositiveFeedback(): bool
    {
        return !empty($this->manager_feedback) || !empty($this->peer_feedback);
    }

    public function exceedsExpectations(): bool
    {
        return $this->performance_rating > 3.5;
    }

    public function isEarlyAttrition(): bool
    {
        return in_array($this->retention_status, ['resigned_early', 'terminated']) 
               && $this->tenure_months < 12;
    }

    public function predictionWasAccurate(float $threshold = 0.7): bool
    {
        return $this->prediction_accuracy !== null && $this->prediction_accuracy >= $threshold;
    }

    public function getRatingsByCategory(): array
    {
        return [
            'Technical Skills' => $this->technical_skills_rating,
            'Soft Skills' => $this->soft_skills_rating,
            'Cultural Fit' => $this->cultural_fit_rating,
            'Productivity' => $this->productivity_rating,
            'Team Collaboration' => $this->team_collaboration_rating,
            'Leadership' => $this->leadership_rating
        ];
    }

    public function getStrengths(): array
    {
        $ratings = $this->getRatingsByCategory();
        return array_filter($ratings, fn($rating) => $rating >= 4.0);
    }

    public function getWeaknesses(): array
    {
        $ratings = $this->getRatingsByCategory();
        return array_filter($ratings, fn($rating) => $rating < 3.0);
    }
}
