<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NegotiationStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'company_name',
        'location',
        'offered_salary',
        'current_salary',
        'years_experience',
        'market_median',
        'market_percentile_25',
        'market_percentile_75',
        'market_percentile_90',
        'offered_salary_percentile',
        'company_salary_data',
        'optimal_ask',
        'minimum_acceptable',
        'stretch_goal',
        'confidence_score',
        'strongest_points',
        'value_propositions',
        'risk_factors',
        'recommended_timing',
        'timing_rationale',
        'recommended_tone',
        'recommended_tactics',
        'benefits_to_negotiate',
        'total_comp_optimization',
        'company_culture_analysis',
        'hiring_manager_perspective',
        'company_negotiation_flexibility',
        'ai_summary',
        'ai_rationale',
        'ai_warnings',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'offered_salary' => 'decimal:2',
        'current_salary' => 'decimal:2',
        'market_median' => 'decimal:2',
        'market_percentile_25' => 'decimal:2',
        'market_percentile_75' => 'decimal:2',
        'market_percentile_90' => 'decimal:2',
        'offered_salary_percentile' => 'decimal:2',
        'optimal_ask' => 'decimal:2',
        'minimum_acceptable' => 'decimal:2',
        'stretch_goal' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'company_salary_data' => 'array',
        'strongest_points' => 'array',
        'value_propositions' => 'array',
        'risk_factors' => 'array',
        'recommended_tactics' => 'array',
        'benefits_to_negotiate' => 'array',
        'total_comp_optimization' => 'array',
        'company_culture_analysis' => 'array',
        'ai_warnings' => 'array',
        'generated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(NegotiationScenario::class, 'strategy_id');
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(NegotiationScript::class, 'strategy_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(NegotiationSession::class, 'strategy_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('generated_at', 'desc');
    }

    // Accessors
    public function getPotentialGainAttribute(): float
    {
        return (float) ($this->optimal_ask - $this->offered_salary);
    }

    public function getPotentialGainPercentageAttribute(): float
    {
        if ($this->offered_salary <= 0) {
            return 0;
        }
        return ($this->potential_gain / (float) $this->offered_salary) * 100;
    }

    public function getNegotiationRangeAttribute(): array
    {
        return [
            'minimum' => (float) $this->minimum_acceptable,
            'target' => (float) $this->optimal_ask,
            'stretch' => (float) $this->stretch_goal,
        ];
    }

    public function getOfferStrengthAttribute(): string
    {
        $percentile = (float) $this->offered_salary_percentile;

        if ($percentile >= 75) {
            return 'excellent';
        } elseif ($percentile >= 50) {
            return 'good';
        } elseif ($percentile >= 25) {
            return 'fair';
        } else {
            return 'below_market';
        }
    }

    public function getOfferStrengthColorAttribute(): string
    {
        return match($this->offer_strength) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'below_market' => 'red',
            default => 'gray',
        };
    }

    public function getOfferStrengthLabelAttribute(): string
    {
        return match($this->offer_strength) {
            'excellent' => 'Excellent Offer',
            'good' => 'Good Offer',
            'fair' => 'Fair Offer',
            'below_market' => 'Below Market',
            default => 'Unknown',
        };
    }

    public function getConfidenceLevelAttribute(): string
    {
        $score = (float) $this->confidence_score;

        if ($score >= 80) {
            return 'very_high';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    public function getConfidenceLevelColorAttribute(): string
    {
        return match($this->confidence_level) {
            'very_high' => 'green',
            'high' => 'blue',
            'medium' => 'yellow',
            'low' => 'red',
            default => 'gray',
        };
    }

    public function getTimingUrgencyAttribute(): string
    {
        return match($this->recommended_timing) {
            'immediate' => 'Respond within 24 hours',
            'within_24h' => 'Respond within 1-2 days',
            'within_48h' => 'Respond within 2-3 days',
            'within_week' => 'Respond within a week',
            default => 'At your convenience',
        };
    }

    public function hasHighNegotiationPotential(): bool
    {
        return $this->potential_gain_percentage >= 10;
    }

    public function hasAlternativeBenefits(): bool
    {
        return !empty($this->benefits_to_negotiate);
    }

    public function getRecommendedScenariosAttribute()
    {
        return $this->scenarios()
            ->where('recommendation', 'recommended')
            ->orderBy('confidence_score', 'desc')
            ->get();
    }

    public function getBestScenarioAttribute()
    {
        return $this->scenarios()
            ->where('recommendation', 'recommended')
            ->orderBy('confidence_score', 'desc')
            ->first();
    }

    public function getPreferredScriptsAttribute()
    {
        return $this->scripts()
            ->where('script_stage', 'initial_response')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Helper Methods
    public function calculateNegotiationLeverage(): array
    {
        $leverage = [
            'market_position' => 0,
            'experience' => 0,
            'skills' => 0,
            'alternatives' => 0,
            'total' => 0,
        ];

        // Market position leverage (based on percentile)
        $percentile = (float) $this->offered_salary_percentile;
        if ($percentile < 50) {
            $leverage['market_position'] = 25; // Strong leverage if offer is below market
        } elseif ($percentile < 75) {
            $leverage['market_position'] = 15;
        } else {
            $leverage['market_position'] = 5;
        }

        // Experience leverage
        if ($this->years_experience >= 10) {
            $leverage['experience'] = 25;
        } elseif ($this->years_experience >= 5) {
            $leverage['experience'] = 15;
        } else {
            $leverage['experience'] = 5;
        }

        // Skills leverage (based on strongest points count)
        $skillsCount = count($this->strongest_points ?? []);
        $leverage['skills'] = min($skillsCount * 5, 25);

        // Alternatives leverage (if user has other offers or current job)
        if ($this->current_salary > 0) {
            $leverage['alternatives'] = 25;
        }

        $leverage['total'] = array_sum(array_filter($leverage, 'is_numeric'));

        return $leverage;
    }

    public function getNegotiationPhaseRecommendation(): string
    {
        $leverage = $this->calculateNegotiationLeverage();
        $totalLeverage = $leverage['total'];

        if ($totalLeverage >= 75) {
            return 'You have strong negotiation leverage. Be confident and assertive.';
        } elseif ($totalLeverage >= 50) {
            return 'You have moderate leverage. Negotiate professionally with data-backed requests.';
        } elseif ($totalLeverage >= 25) {
            return 'Your leverage is limited. Focus on demonstrating value and flexibility.';
        } else {
            return 'Consider accepting the offer or negotiating alternative benefits rather than salary.';
        }
    }

    public function shouldNegotiate(): bool
    {
        // Don't negotiate if offer is already in top 25th percentile and above optimal ask
        if ($this->offered_salary_percentile >= 75 && $this->offered_salary >= $this->optimal_ask) {
            return false;
        }

        // Don't negotiate if potential gain is less than 5%
        if ($this->potential_gain_percentage < 5) {
            return false;
        }

        return true;
    }

    public function getMarketComparison(): array
    {
        return [
            'offered' => (float) $this->offered_salary,
            'market_median' => (float) $this->market_median,
            'difference' => (float) $this->offered_salary - (float) $this->market_median,
            'difference_percentage' => $this->market_median > 0 
                ? (($this->offered_salary - $this->market_median) / $this->market_median) * 100 
                : 0,
            'percentile' => (float) $this->offered_salary_percentile,
            'percentile_25' => (float) $this->market_percentile_25,
            'percentile_75' => (float) $this->market_percentile_75,
            'percentile_90' => (float) $this->market_percentile_90,
        ];
    }
}
