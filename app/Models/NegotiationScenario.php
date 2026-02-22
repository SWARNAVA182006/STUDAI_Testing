<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NegotiationScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'scenario_name',
        'scenario_order',
        'counter_offer_amount',
        'additional_requests',
        'counter_offer_justification',
        'predicted_response',
        'predicted_response_probability',
        'predicted_final_salary',
        'predicted_employer_counter',
        'risk_level',
        'risk_score',
        'risk_factors',
        'mitigation_strategies',
        'best_case_outcome',
        'expected_outcome',
        'worst_case_outcome',
        'success_indicators',
        'failure_indicators',
        'recommendation',
        'recommendation_rationale',
        'confidence_score',
    ];

    protected $casts = [
        'counter_offer_amount' => 'decimal:2',
        'predicted_response_probability' => 'decimal:2',
        'predicted_final_salary' => 'decimal:2',
        'risk_score' => 'decimal:2',
        'best_case_outcome' => 'decimal:2',
        'expected_outcome' => 'decimal:2',
        'worst_case_outcome' => 'decimal:2',
        'additional_requests' => 'array',
        'risk_factors' => 'array',
        'mitigation_strategies' => 'array',
        'success_indicators' => 'array',
        'failure_indicators' => 'array',
    ];

    // Relationships
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(NegotiationStrategy::class, 'strategy_id');
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(NegotiationScript::class, 'scenario_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NegotiationSession::class, 'scenario_id');
    }

    // Scopes
    public function scopeRecommended($query)
    {
        return $query->where('recommendation', 'recommended');
    }

    public function scopeViable($query)
    {
        return $query->whereIn('recommendation', ['recommended', 'viable']);
    }

    public function scopeLowRisk($query)
    {
        return $query->whereIn('risk_level', ['low', 'medium']);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('scenario_order');
    }

    public function scopeByConfidence($query)
    {
        return $query->orderBy('confidence_score', 'desc');
    }

    // Accessors
    public function getCounterOfferIncreaseAttribute(): float
    {
        $strategy = $this->strategy;
        if (!$strategy) {
            return 0;
        }

        return (float) ($this->counter_offer_amount - $strategy->offered_salary);
    }

    public function getCounterOfferIncreasePercentageAttribute(): float
    {
        $strategy = $this->strategy;
        if (!$strategy || $strategy->offered_salary <= 0) {
            return 0;
        }

        return ($this->counter_offer_increase / (float) $strategy->offered_salary) * 100;
    }

    public function getExpectedGainAttribute(): float
    {
        $strategy = $this->strategy;
        if (!$strategy) {
            return 0;
        }

        return (float) ($this->expected_outcome - $strategy->offered_salary);
    }

    public function getExpectedGainPercentageAttribute(): float
    {
        $strategy = $this->strategy;
        if (!$strategy || $strategy->offered_salary <= 0) {
            return 0;
        }

        return ($this->expected_gain / (float) $strategy->offered_salary) * 100;
    }

    public function getSuccessProbabilityAttribute(): float
    {
        // Calculate based on predicted response probability and risk
        $responseProbability = (float) $this->predicted_response_probability;
        $riskPenalty = $this->getRiskPenalty();

        return max(0, min(100, $responseProbability - $riskPenalty));
    }

    public function getRiskPenalty(): float
    {
        return match($this->risk_level) {
            'low' => 0,
            'medium' => 10,
            'high' => 25,
            'very_high' => 40,
            default => 0,
        };
    }

    public function getRiskLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'very_high' => 'red',
            default => 'gray',
        };
    }

    public function getRiskLevelLabelAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            'very_high' => 'Very High Risk',
            default => 'Unknown',
        };
    }

    public function getRecommendationColorAttribute(): string
    {
        return match($this->recommendation) {
            'recommended' => 'green',
            'viable' => 'blue',
            'risky' => 'orange',
            'not_recommended' => 'red',
            default => 'gray',
        };
    }

    public function getRecommendationLabelAttribute(): string
    {
        return match($this->recommendation) {
            'recommended' => 'Recommended',
            'viable' => 'Viable Option',
            'risky' => 'Risky',
            'not_recommended' => 'Not Recommended',
            default => 'Unknown',
        };
    }

    public function getRecommendationBadgeAttribute(): string
    {
        return match($this->recommendation) {
            'recommended' => '✓ Best Choice',
            'viable' => '○ Good Option',
            'risky' => '⚠ Proceed with Caution',
            'not_recommended' => '✗ Avoid',
            default => '',
        };
    }

    public function getPredictedResponseLabelAttribute(): string
    {
        return match($this->predicted_response) {
            'accept' => 'Likely to Accept',
            'counter' => 'Will Counter-Offer',
            'negotiate' => 'Will Negotiate',
            'reject' => 'May Reject',
            default => 'Unknown',
        };
    }

    public function getPredictedResponseColorAttribute(): string
    {
        return match($this->predicted_response) {
            'accept' => 'green',
            'counter' => 'blue',
            'negotiate' => 'yellow',
            'reject' => 'red',
            default => 'gray',
        };
    }

    public function getOutcomeRangeAttribute(): array
    {
        return [
            'best' => (float) $this->best_case_outcome,
            'expected' => (float) $this->expected_outcome,
            'worst' => (float) $this->worst_case_outcome,
            'range' => (float) $this->best_case_outcome - (float) $this->worst_case_outcome,
        ];
    }

    // Helper Methods
    public function isRecommended(): bool
    {
        return $this->recommendation === 'recommended';
    }

    public function isViable(): bool
    {
        return in_array($this->recommendation, ['recommended', 'viable']);
    }

    public function isLowRisk(): bool
    {
        return in_array($this->risk_level, ['low', 'medium']);
    }

    public function hasHighSuccessProbability(): bool
    {
        return $this->success_probability >= 70;
    }

    public function getRiskAssessment(): array
    {
        return [
            'level' => $this->risk_level,
            'score' => (float) $this->risk_score,
            'factors' => $this->risk_factors ?? [],
            'mitigation' => $this->mitigation_strategies ?? [],
            'penalty' => $this->getRiskPenalty(),
        ];
    }

    public function getScenarioSummary(): array
    {
        return [
            'name' => $this->scenario_name,
            'counter_offer' => (float) $this->counter_offer_amount,
            'increase' => $this->counter_offer_increase_percentage,
            'expected_outcome' => (float) $this->expected_outcome,
            'success_probability' => $this->success_probability,
            'risk_level' => $this->risk_level,
            'recommendation' => $this->recommendation,
            'confidence' => $this->confidence_score,
        ];
    }

    public function calculateRoi(): float
    {
        // ROI = (Expected Gain / Risk) * Success Probability
        $expectedGain = $this->expected_gain;
        $riskScore = (float) $this->risk_score;
        $successProbability = $this->success_probability;

        if ($riskScore <= 0) {
            return 0;
        }

        return ($expectedGain / $riskScore) * ($successProbability / 100);
    }

    public function compareToStrategy(): array
    {
        $strategy = $this->strategy;
        if (!$strategy) {
            return [];
        }

        return [
            'counter_vs_optimal' => [
                'counter' => (float) $this->counter_offer_amount,
                'optimal' => (float) $strategy->optimal_ask,
                'difference' => (float) $this->counter_offer_amount - (float) $strategy->optimal_ask,
                'is_higher' => $this->counter_offer_amount > $strategy->optimal_ask,
            ],
            'expected_vs_optimal' => [
                'expected' => (float) $this->expected_outcome,
                'optimal' => (float) $strategy->optimal_ask,
                'difference' => (float) $this->expected_outcome - (float) $strategy->optimal_ask,
                'achievement_rate' => $strategy->optimal_ask > 0 
                    ? ($this->expected_outcome / $strategy->optimal_ask) * 100 
                    : 0,
            ],
        ];
    }

    public function shouldProceed(): bool
    {
        // Don't proceed if not recommended and high/very high risk
        if (in_array($this->recommendation, ['risky', 'not_recommended']) && 
            in_array($this->risk_level, ['high', 'very_high'])) {
            return false;
        }

        // Don't proceed if success probability is too low
        if ($this->success_probability < 30) {
            return false;
        }

        // Don't proceed if expected outcome is worse than current offer
        $strategy = $this->strategy;
        if ($strategy && $this->expected_outcome < $strategy->offered_salary) {
            return false;
        }

        return true;
    }

    public function getAdditionalRequestsSummary(): string
    {
        if (empty($this->additional_requests)) {
            return 'None';
        }

        $requests = $this->additional_requests;
        $summary = [];

        foreach ($requests as $key => $value) {
            if (is_array($value)) {
                $summary[] = ucfirst($key) . ': ' . implode(', ', $value);
            } else {
                $summary[] = ucfirst($key) . ': ' . $value;
            }
        }

        return implode(' | ', $summary);
    }
}
