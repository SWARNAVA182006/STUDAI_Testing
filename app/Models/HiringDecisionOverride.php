<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringDecisionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'company_id',
        'job_id',
        'user_id',
        'scout_recommendation',
        'manager_decision',
        'override_type',
        'override_reason',
        'override_factors',
        'confidence_level',
        'outcome',
        'outcome_notes',
        'metadata'
    ];

    protected $casts = [
        'override_factors' => 'array',
        'outcome_notes' => 'array',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes

    public function scopeHireDespiteCaution($query)
    {
        return $query->where('override_type', 'hire_despite_caution');
    }

    public function scopeRejectDespiteRecommendation($query)
    {
        return $query->where('override_type', 'reject_despite_recommendation');
    }

    public function scopeValidated($query)
    {
        return $query->where('outcome', 'validated');
    }

    public function scopeRefuted($query)
    {
        return $query->where('outcome', 'refuted');
    }

    public function scopePending($query)
    {
        return $query->where('outcome', 'pending');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForManager($query, int $managerId)
    {
        return $query->where('user_id', $managerId);
    }

    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_level', 'high');
    }

    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    // Accessors

    public function getOverrideDirectionAttribute(): string
    {
        $scoutLevels = ['STRONG HIRE', 'RECOMMEND', 'CONSIDER', 'CAUTION', 'NOT RECOMMENDED'];
        $scoutIndex = array_search($this->scout_recommendation, $scoutLevels);
        $managerIndex = array_search($this->manager_decision, $scoutLevels);

        if ($scoutIndex === false || $managerIndex === false) {
            return 'unknown';
        }

        if ($managerIndex < $scoutIndex) {
            return 'more_positive'; // Manager more optimistic
        } elseif ($managerIndex > $scoutIndex) {
            return 'more_conservative'; // Manager more cautious
        }

        return 'agreement';
    }

    public function getOverrideMagnitudeAttribute(): int
    {
        $scoutLevels = ['STRONG HIRE', 'RECOMMEND', 'CONSIDER', 'CAUTION', 'NOT RECOMMENDED'];
        $scoutIndex = array_search($this->scout_recommendation, $scoutLevels);
        $managerIndex = array_search($this->manager_decision, $scoutLevels);

        if ($scoutIndex === false || $managerIndex === false) {
            return 0;
        }

        return abs($managerIndex - $scoutIndex);
    }

    public function getIsValidatedAttribute(): bool
    {
        return $this->outcome === 'validated';
    }

    public function getIsRefutedAttribute(): bool
    {
        return $this->outcome === 'refuted';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->outcome === 'pending';
    }

    public function getDaysSinceOverrideAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    public function getConfidenceScoreAttribute(): int
    {
        return match($this->confidence_level) {
            'high' => 90,
            'medium' => 70,
            'low' => 40,
            default => 50
        };
    }

    // Methods

    public function wasManagerMoreOptimistic(): bool
    {
        return $this->override_direction === 'more_positive';
    }

    public function wasManagerMoreConservative(): bool
    {
        return $this->override_direction === 'more_conservative';
    }

    public function isSignificantOverride(): bool
    {
        return $this->override_magnitude >= 2;
    }

    public function validate(array $notes = []): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        $this->update([
            'outcome' => 'validated',
            'outcome_notes' => $notes
        ]);

        return true;
    }

    public function refute(array $notes = []): bool
    {
        if (!$this->is_pending) {
            return false;
        }

        $this->update([
            'outcome' => 'refuted',
            'outcome_notes' => $notes
        ]);

        return true;
    }

    public function hasOutcome(): bool
    {
        return $this->outcome !== 'pending';
    }

    public function getOverrideSummary(): string
    {
        $direction = $this->override_direction === 'more_positive' ? 'hired' : 'rejected';
        $magnitude = $this->override_magnitude;
        
        return "Manager {$direction} candidate despite S.C.O.U.T.'s {$this->scout_recommendation} recommendation (magnitude: {$magnitude})";
    }

    public function getPrimaryOverrideFactor(): ?string
    {
        if (!is_array($this->override_factors) || empty($this->override_factors)) {
            return null;
        }

        return $this->override_factors[0] ?? null;
    }

    public function hasOverrideFactor(string $factor): bool
    {
        if (!is_array($this->override_factors)) {
            return false;
        }

        return in_array($factor, $this->override_factors);
    }
}
