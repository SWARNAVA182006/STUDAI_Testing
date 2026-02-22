<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRefinement extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'refinement_type',
        'data_points_analyzed',
        'time_period_start',
        'time_period_end',
        'previous_criteria',
        'refined_criteria',
        'previous_weights',
        'refined_weights',
        'correlation_analysis',
        'performance_improvement_estimate',
        'confidence_score',
        'ai_insights',
        'applied_at',
        'metadata'
    ];

    protected $casts = [
        'time_period_start' => 'date',
        'time_period_end' => 'date',
        'data_points_analyzed' => 'integer',
        'previous_criteria' => 'array',
        'refined_criteria' => 'array',
        'previous_weights' => 'array',
        'refined_weights' => 'array',
        'correlation_analysis' => 'array',
        'performance_improvement_estimate' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'applied_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes

    public function scopeApplied($query)
    {
        return $query->whereNotNull('applied_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('applied_at');
    }

    public function scopeHighConfidence($query, float $threshold = 75.0)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('refinement_type', $type);
    }

    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    // Accessors

    public function getIsAppliedAttribute(): bool
    {
        return $this->applied_at !== null;
    }

    public function getConfidenceLevelAttribute(): string
    {
        $score = $this->confidence_score;

        if ($score >= 85) return 'Very High';
        if ($score >= 75) return 'High';
        if ($score >= 60) return 'Medium';
        if ($score >= 40) return 'Low';
        return 'Very Low';
    }

    public function getEstimatedImpactAttribute(): string
    {
        $estimate = $this->performance_improvement_estimate;

        if ($estimate >= 20) return 'Significant';
        if ($estimate >= 10) return 'Moderate';
        if ($estimate >= 5) return 'Minor';
        return 'Minimal';
    }

    public function getAnalysisPeriodDaysAttribute(): int
    {
        return $this->time_period_start->diffInDays($this->time_period_end);
    }

    public function getDaysSinceRefinementAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getWeightChangesAttribute(): array
    {
        if (!$this->previous_weights || !$this->refined_weights) {
            return [];
        }

        $changes = [];
        foreach ($this->refined_weights as $factor => $newWeight) {
            $oldWeight = $this->previous_weights[$factor] ?? 0;
            $change = $newWeight - $oldWeight;
            
            if (abs($change) > 0.01) { // Significant change threshold
                $changes[$factor] = [
                    'old' => $oldWeight,
                    'new' => $newWeight,
                    'change' => $change,
                    'change_percent' => $oldWeight > 0 ? ($change / $oldWeight) * 100 : 0
                ];
            }
        }

        return $changes;
    }

    public function getMostImprovedFactorAttribute(): ?string
    {
        $changes = $this->weight_changes;
        
        if (empty($changes)) {
            return null;
        }

        uasort($changes, fn($a, $b) => $b['change'] <=> $a['change']);
        return array_key_first($changes);
    }

    public function getLeastImprovedFactorAttribute(): ?string
    {
        $changes = $this->weight_changes;
        
        if (empty($changes)) {
            return null;
        }

        uasort($changes, fn($a, $b) => $a['change'] <=> $b['change']);
        return array_key_first($changes);
    }

    public function getTopCorrelationsAttribute(): array
    {
        if (!isset($this->correlation_analysis['correlations'])) {
            return [];
        }

        $correlations = $this->correlation_analysis['correlations'];
        arsort($correlations);
        
        return array_slice($correlations, 0, 3, true);
    }

    // Methods

    public function apply(): bool
    {
        if ($this->is_applied) {
            return false;
        }

        $this->update(['applied_at' => now()]);
        
        return true;
    }

    public function hasHighConfidence(float $threshold = 75.0): bool
    {
        return $this->confidence_score >= $threshold;
    }

    public function hasSignificantImpact(float $threshold = 10.0): bool
    {
        return $this->performance_improvement_estimate >= $threshold;
    }

    public function isRecent(int $days = 90): bool
    {
        return $this->created_at >= now()->subDays($days);
    }

    public function getFactorWeight(string $factor): ?float
    {
        return $this->refined_weights[$factor] ?? null;
    }

    public function getPreviousFactorWeight(string $factor): ?float
    {
        return $this->previous_weights[$factor] ?? null;
    }

    public function getFactorWeightChange(string $factor): ?float
    {
        $old = $this->getPreviousFactorWeight($factor);
        $new = $this->getFactorWeight($factor);

        if ($old === null || $new === null) {
            return null;
        }

        return $new - $old;
    }

    public function getCorrelation(string $factor): ?float
    {
        return $this->correlation_analysis['correlations'][$factor] ?? null;
    }

    public function getStrongestPredictor(): ?string
    {
        return $this->correlation_analysis['strongest_predictor'] ?? null;
    }

    public function getWeakestPredictor(): ?string
    {
        return $this->correlation_analysis['weakest_predictor'] ?? null;
    }
}
