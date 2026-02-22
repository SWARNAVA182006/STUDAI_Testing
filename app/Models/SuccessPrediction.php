<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessPrediction extends Model
{
    protected $table = 'scout_success_predictions';

    protected $fillable = [
        'application_id',
        'job_id',
        'company_id',
        'user_id',
        'success_probability',
        'confidence_score',
        'success_category',
        'factor_scores',
        'key_strengths',
        'key_concerns',
        'ai_insights',
        'prediction_basis',
        'recommendation',
        'predicted_at',
        'actual_outcome_date',
        'actual_outcome',
    ];

    protected $casts = [
        'success_probability' => 'decimal:4',
        'confidence_score' => 'decimal:4',
        'factor_scores' => 'array',
        'key_strengths' => 'array',
        'key_concerns' => 'array',
        'ai_insights' => 'array',
        'predicted_at' => 'datetime',
        'actual_outcome_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('success_category', $category);
    }

    public function scopeHighSuccess($query, float $threshold = 0.7)
    {
        return $query->where('success_probability', '>=', $threshold);
    }

    public function scopeLowSuccess($query, float $threshold = 0.5)
    {
        return $query->where('success_probability', '<', $threshold);
    }

    public function scopeWithOutcome($query)
    {
        return $query->whereNotNull('actual_outcome');
    }

    public function scopePendingValidation($query)
    {
        return $query->whereNull('actual_outcome')
                     ->where('predicted_at', '<=', now()->subMonths(3));
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('predicted_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getCategoryDisplayAttribute(): string
    {
        return match($this->success_category) {
            'very_high' => 'Very High Success Probability',
            'high' => 'High Success Probability',
            'moderate' => 'Moderate Success Probability',
            'low' => 'Low Success Probability',
            'very_low' => 'Very Low Success Probability',
            default => 'Unknown'
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->success_category) {
            'very_high' => 'green',
            'high' => 'blue',
            'moderate' => 'yellow',
            'low' => 'orange',
            'very_low' => 'red',
            default => 'gray'
        };
    }

    public function getSuccessPercentageAttribute(): float
    {
        return round($this->success_probability * 100, 2);
    }

    public function getConfidencePercentageAttribute(): float
    {
        return round($this->confidence_score * 100, 2);
    }

    public function getTopStrengthsAttribute(): array
    {
        return array_slice($this->key_strengths ?? [], 0, 3);
    }

    public function getTopConcernsAttribute(): array
    {
        return array_slice($this->key_concerns ?? [], 0, 3);
    }

    public function getWasAccurateAttribute(): ?bool
    {
        if (!$this->actual_outcome) {
            return null;
        }

        $successfulOutcomes = ['success', 'moderate_success'];
        $predictedSuccess = $this->success_probability >= 0.6;
        $actualSuccess = in_array($this->actual_outcome, $successfulOutcomes);

        return $predictedSuccess === $actualSuccess;
    }

    public function getOutcomeDisplayAttribute(): ?string
    {
        if (!$this->actual_outcome) {
            return null;
        }

        return match($this->actual_outcome) {
            'success' => 'Successful Hire',
            'moderate_success' => 'Moderately Successful',
            'underperformance' => 'Underperforming',
            'failure' => 'Failed Hire',
            default => 'Unknown'
        };
    }

    public function getDaysSincePredictionAttribute(): int
    {
        return $this->predicted_at->diffInDays(now());
    }

    /**
     * Methods
     */
    public function recordOutcome(string $outcome, $outcomeDate = null): bool
    {
        $this->actual_outcome = $outcome;
        $this->actual_outcome_date = $outcomeDate ?? now();
        return $this->save();
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence_score >= 0.8;
    }

    public function needsReview(): bool
    {
        return $this->success_probability < 0.6 && count($this->key_concerns ?? []) > 2;
    }

    public function getFactorScore(string $factor): ?float
    {
        return $this->factor_scores[$factor] ?? null;
    }

    public function getStrongestFactor(): array
    {
        $scores = $this->factor_scores;
        arsort($scores);
        $key = array_key_first($scores);
        
        return [
            'factor' => $key,
            'score' => $scores[$key]
        ];
    }

    public function getWeakestFactor(): array
    {
        $scores = $this->factor_scores;
        asort($scores);
        $key = array_key_first($scores);
        
        return [
            'factor' => $key,
            'score' => $scores[$key]
        ];
    }
}
