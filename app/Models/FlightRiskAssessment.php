<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightRiskAssessment extends Model
{
    protected $table = 'scout_flight_risk_assessments';

    protected $fillable = [
        'application_id',
        'company_id',
        'user_id',
        'risk_score',
        'risk_level',
        'risk_category',
        'risk_factors',
        'mitigation_strategies',
        'ai_insights',
        'recommendation',
        'assessment_date',
        'reassessment_due',
        'status',
        'mitigation_actions_taken',
        'outcome',
        'outcome_date',
    ];

    protected $casts = [
        'risk_score' => 'decimal:4',
        'risk_factors' => 'array',
        'mitigation_strategies' => 'array',
        'ai_insights' => 'array',
        'assessment_date' => 'datetime',
        'reassessment_due' => 'datetime',
        'mitigation_actions_taken' => 'array',
        'outcome_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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

    public function scopeCritical($query, float $threshold = 0.8)
    {
        return $query->where('risk_score', '>=', $threshold);
    }

    public function scopeHigh($query, float $min = 0.6, float $max = 0.8)
    {
        return $query->whereBetween('risk_score', [$min, $max]);
    }

    public function scopeMedium($query, float $min = 0.4, float $max = 0.6)
    {
        return $query->whereBetween('risk_score', [$min, $max]);
    }

    public function scopeLow($query, float $threshold = 0.4)
    {
        return $query->where('risk_score', '<', $threshold);
    }

    public function scopeByRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeByRiskCategory($query, string $category)
    {
        return $query->where('risk_category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeMitigated($query)
    {
        return $query->where('status', 'mitigated');
    }

    public function scopeRealized($query)
    {
        return $query->where('status', 'realized');
    }

    public function scopeNeedsReassessment($query)
    {
        return $query->where('status', 'active')
                     ->where('reassessment_due', '<=', now());
    }

    public function scopeHighPriority($query)
    {
        return $query->where('status', 'active')
                     ->where('risk_score', '>=', 0.6);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('assessment_date', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getRiskCategoryDisplayAttribute(): string
    {
        return match($this->risk_category) {
            'immediate_flight' => 'Immediate Flight Risk',
            'short_term_flight' => 'Short-Term Flight Risk',
            'long_term_risk' => 'Long-Term Risk',
            'stable' => 'Stable Employee',
            default => 'Unknown'
        };
    }

    public function getRiskLevelDisplayAttribute(): string
    {
        return match($this->risk_level) {
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => 'Unknown'
        };
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }

    public function getPriorityScoreAttribute(): int
    {
        $baseScore = $this->risk_score * 100;
        
        $multiplier = match($this->status) {
            'active' => 1.0,
            'mitigated' => 0.5,
            'resolved' => 0.0,
            default => 1.0
        };
        
        $urgencyBonus = $this->isDueForReassessment() ? 20 : 0;
        
        return (int) min(100, ($baseScore * $multiplier) + $urgencyBonus);
    }

    public function getIsCriticalRiskAttribute(): bool
    {
        return $this->risk_score >= 0.8 || $this->risk_level === 'critical';
    }

    public function getIsHighPriorityAttribute(): bool
    {
        return $this->is_critical_risk || ($this->risk_score >= 0.6 && $this->status === 'active');
    }

    public function getRiskPercentageAttribute(): float
    {
        return round($this->risk_score * 100, 2);
    }

    public function getRiskFactorsSummaryAttribute(): string
    {
        $factors = $this->risk_factors ?? [];
        
        if (empty($factors)) {
            return 'No specific risk factors identified';
        }
        
        $topFactors = array_slice($factors, 0, 3);
        
        return implode(', ', array_map(function($factor) {
            if (is_array($factor)) {
                return $factor['name'] ?? 'Unknown factor';
            }
            return $factor;
        }, $topFactors));
    }

    public function getMitigationStrategiesSummaryAttribute(): string
    {
        $strategies = $this->mitigation_strategies ?? [];
        
        if (empty($strategies)) {
            return 'No mitigation strategies defined';
        }
        
        $topStrategies = array_slice($strategies, 0, 3);
        
        return implode(', ', array_map(function($strategy) {
            if (is_array($strategy)) {
                return $strategy['action'] ?? 'Unknown strategy';
            }
            return $strategy;
        }, $topStrategies));
    }

    public function getDaysUntilReassessmentAttribute(): ?int
    {
        if (!$this->reassessment_due) {
            return null;
        }
        
        return now()->diffInDays($this->reassessment_due, false);
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active - Monitoring',
            'mitigated' => 'Mitigated - Lower Risk',
            'resolved' => 'Resolved - No Longer at Risk',
            'realized' => 'Realized - Employee Left',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'orange',
            'mitigated' => 'blue',
            'resolved' => 'green',
            'realized' => 'red',
            default => 'gray'
        };
    }

    public function getMitigationEffectivenessAttribute(): ?float
    {
        if (empty($this->mitigation_actions_taken)) {
            return null;
        }
        
        $actions = $this->mitigation_actions_taken;
        $completed = count(array_filter($actions, function($action) {
            return ($action['status'] ?? '') === 'completed';
        }));
        
        $total = count($actions);
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    public function getDaysSinceAssessmentAttribute(): int
    {
        return $this->assessment_date->diffInDays(now());
    }

    /**
     * Methods
     */
    public function updateRiskScore(float $newScore): bool
    {
        $this->risk_score = $newScore;
        
        $this->risk_level = match(true) {
            $newScore >= 0.8 => 'critical',
            $newScore >= 0.6 => 'high',
            $newScore >= 0.4 => 'medium',
            default => 'low'
        };
        
        return $this->save();
    }

    public function addRiskFactor(array $factor): bool
    {
        $factors = $this->risk_factors ?? [];
        $factors[] = $factor;
        $this->risk_factors = $factors;
        return $this->save();
    }

    public function addMitigationStrategy(array $strategy): bool
    {
        $strategies = $this->mitigation_strategies ?? [];
        $strategies[] = $strategy;
        $this->mitigation_strategies = $strategies;
        return $this->save();
    }

    public function recordMitigationAction(array $action): bool
    {
        $actions = $this->mitigation_actions_taken ?? [];
        $actions[] = array_merge($action, ['recorded_at' => now()]);
        $this->mitigation_actions_taken = $actions;
        return $this->save();
    }

    public function markMitigated(): bool
    {
        $this->status = 'mitigated';
        return $this->save();
    }

    public function markResolved(): bool
    {
        $this->status = 'resolved';
        return $this->save();
    }

    public function markRealized(string $outcome = null, $outcomeDate = null): bool
    {
        $this->status = 'realized';
        $this->outcome = $outcome;
        $this->outcome_date = $outcomeDate ?? now();
        return $this->save();
    }

    public function scheduleReassessment(int $daysFromNow = 30): bool
    {
        $this->reassessment_due = now()->addDays($daysFromNow);
        return $this->save();
    }

    public function isUrgent(): bool
    {
        return $this->is_high_priority && $this->isDueForReassessment();
    }

    public function isDueForReassessment(): bool
    {
        return $this->reassessment_due && $this->reassessment_due <= now();
    }

    public function getPriorityActions(): array
    {
        $strategies = $this->mitigation_strategies ?? [];
        
        usort($strategies, function($a, $b) {
            $priorityA = is_array($a) ? ($a['priority'] ?? 0) : 0;
            $priorityB = is_array($b) ? ($b['priority'] ?? 0) : 0;
            return $priorityB <=> $priorityA;
        });
        
        return array_slice($strategies, 0, 3);
    }

    public function getTopRiskFactors(int $count = 5): array
    {
        $factors = $this->risk_factors ?? [];
        
        usort($factors, function($a, $b) {
            $weightA = is_array($a) ? ($a['weight'] ?? 0) : 0;
            $weightB = is_array($b) ? ($b['weight'] ?? 0) : 0;
            return $weightB <=> $weightA;
        });
        
        return array_slice($factors, 0, $count);
    }

    public function getCompletedMitigationActions(): array
    {
        $actions = $this->mitigation_actions_taken ?? [];
        
        return array_filter($actions, function($action) {
            return ($action['status'] ?? '') === 'completed';
        });
    }

    public function getPendingMitigationActions(): array
    {
        $actions = $this->mitigation_actions_taken ?? [];
        
        return array_filter($actions, function($action) {
            return ($action['status'] ?? '') !== 'completed';
        });
    }

    public function calculateRiskTrend(): ?string
    {
        // This would compare with previous assessments
        // For now, return null - implement when historical data available
        return null;
    }
}
