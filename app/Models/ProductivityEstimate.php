<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivityEstimate extends Model
{
    protected $table = 'scout_productivity_estimates';

    protected $fillable = [
        'application_id',
        'job_id',
        'company_id',
        'user_id',
        'estimated_weeks_to_productivity',
        'productivity_category',
        'productivity_milestones',
        'learning_curve_factors',
        'experience_gap_analysis',
        'support_requirements',
        'ai_insights',
        'recommendation',
        'estimated_at',
        'actual_weeks_to_productivity',
        'productivity_achieved_date',
        'milestone_completion_dates',
    ];

    protected $casts = [
        'estimated_weeks_to_productivity' => 'integer',
        'productivity_milestones' => 'array',
        'learning_curve_factors' => 'array',
        'experience_gap_analysis' => 'array',
        'support_requirements' => 'array',
        'ai_insights' => 'array',
        'estimated_at' => 'datetime',
        'actual_weeks_to_productivity' => 'integer',
        'productivity_achieved_date' => 'datetime',
        'milestone_completion_dates' => 'array',
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

    public function scopeQuickRamp($query, int $weeks = 8)
    {
        return $query->where('estimated_weeks_to_productivity', '<', $weeks);
    }

    public function scopeAverageRamp($query, int $minWeeks = 8, int $maxWeeks = 12)
    {
        return $query->whereBetween('estimated_weeks_to_productivity', [$minWeeks, $maxWeeks]);
    }

    public function scopeSlowRamp($query, int $weeks = 12)
    {
        return $query->where('estimated_weeks_to_productivity', '>=', $weeks);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('productivity_category', $category);
    }

    public function scopeByComplexity($query, string $complexity)
    {
        return $query->whereJsonContains('learning_curve_factors->complexity', $complexity);
    }

    public function scopeNeedsHighSupport($query)
    {
        return $query->whereJsonContains('support_requirements->level', 'high');
    }

    public function scopeLowExperienceGap($query)
    {
        return $query->whereJsonContains('experience_gap_analysis->gap_level', 'low');
    }

    public function scopeHighLearningAgility($query)
    {
        return $query->whereJsonContains('learning_curve_factors->learning_agility', 'high');
    }

    public function scopeWithActualProductivity($query)
    {
        return $query->whereNotNull('actual_weeks_to_productivity');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('estimated_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getProductivityCategoryDisplayAttribute(): string
    {
        return match($this->productivity_category) {
            'quick_ramp' => 'Quick Ramp-Up',
            'average_ramp' => 'Average Ramp-Up',
            'slow_ramp' => 'Slow Ramp-Up',
            'extended_ramp' => 'Extended Ramp-Up',
            default => 'Unknown'
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->productivity_category) {
            'quick_ramp' => 'green',
            'average_ramp' => 'blue',
            'slow_ramp' => 'yellow',
            'extended_ramp' => 'orange',
            default => 'gray'
        };
    }

    public function getWeeksCategoryAttribute(): string
    {
        if ($this->estimated_weeks_to_productivity < 4) {
            return '< 1 month';
        } elseif ($this->estimated_weeks_to_productivity < 8) {
            return '1-2 months';
        } elseif ($this->estimated_weeks_to_productivity < 12) {
            return '2-3 months';
        } elseif ($this->estimated_weeks_to_productivity < 16) {
            return '3-4 months';
        } else {
            return '4+ months';
        }
    }

    public function getMonthsToProductivityAttribute(): float
    {
        return round($this->estimated_weeks_to_productivity / 4.33, 1);
    }

    public function getMilestoneProgressAttribute(): float
    {
        if (empty($this->milestone_completion_dates)) {
            return 0;
        }
        
        $milestones = $this->productivity_milestones ?? [];
        $completed = count(array_filter($this->milestone_completion_dates ?? []));
        $total = count($milestones);
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    public function getCurrentMilestoneAttribute(): ?array
    {
        $milestones = $this->productivity_milestones ?? [];
        $completionDates = $this->milestone_completion_dates ?? [];
        
        foreach ($milestones as $index => $milestone) {
            if (!isset($completionDates["milestone_$index"])) {
                return [
                    'index' => $index,
                    'milestone' => $milestone,
                ];
            }
        }
        
        return null; // All milestones completed
    }

    public function getLearningCurveDisplayAttribute(): string
    {
        $factors = $this->learning_curve_factors ?? [];
        
        if (empty($factors)) {
            return 'Not assessed';
        }
        
        $complexity = $factors['complexity'] ?? 'unknown';
        $agility = $factors['learning_agility'] ?? 'unknown';
        
        return ucfirst($complexity) . ' complexity, ' . ucfirst($agility) . ' learning agility';
    }

    public function getExperienceGapSummaryAttribute(): string
    {
        $analysis = $this->experience_gap_analysis ?? [];
        
        if (empty($analysis)) {
            return 'No gap analysis available';
        }
        
        $gapLevel = $analysis['gap_level'] ?? 'unknown';
        $criticalGaps = $analysis['critical_gaps'] ?? [];
        
        $summary = ucfirst($gapLevel) . ' experience gap';
        
        if (!empty($criticalGaps)) {
            $summary .= ' (' . count($criticalGaps) . ' critical areas)';
        }
        
        return $summary;
    }

    public function getSupportRequirementsSummaryAttribute(): string
    {
        $requirements = $this->support_requirements ?? [];
        
        if (empty($requirements)) {
            return 'Standard support';
        }
        
        $level = $requirements['level'] ?? 'standard';
        $types = $requirements['types'] ?? [];
        
        $summary = ucfirst($level) . ' support needed';
        
        if (!empty($types)) {
            $summary .= ' (' . implode(', ', array_slice($types, 0, 2)) . ')';
        }
        
        return $summary;
    }

    public function getEstimatedFullProductivityDateAttribute(): ?\Carbon\Carbon
    {
        if (!$this->application->hired_at) {
            return null;
        }
        
        return $this->application->hired_at->addWeeks($this->estimated_weeks_to_productivity);
    }

    public function getWasAccurateAttribute(): ?bool
    {
        if (!$this->actual_weeks_to_productivity) {
            return null;
        }
        
        $margin = max(2, $this->estimated_weeks_to_productivity * 0.2);
        $difference = abs($this->estimated_weeks_to_productivity - $this->actual_weeks_to_productivity);
        
        return $difference <= $margin;
    }

    public function getDaysSinceEstimateAttribute(): int
    {
        return $this->estimated_at->diffInDays(now());
    }

    /**
     * Methods
     */
    public function recordActualProductivity(int $weeks, $achievedDate = null): bool
    {
        $this->actual_weeks_to_productivity = $weeks;
        $this->productivity_achieved_date = $achievedDate ?? now();
        return $this->save();
    }

    public function updateMilestone(int $milestoneIndex, $completionDate = null): bool
    {
        $dates = $this->milestone_completion_dates ?? [];
        $dates["milestone_$milestoneIndex"] = $completionDate ?? now();
        $this->milestone_completion_dates = $dates;
        return $this->save();
    }

    public function isQuickRamp(): bool
    {
        return $this->estimated_weeks_to_productivity < 8;
    }

    public function isOnTrack(): bool
    {
        if (!$this->application->hired_at) {
            return true;
        }
        
        $weeksSinceHire = $this->application->hired_at->diffInWeeks(now());
        $expectedProgress = min(100, ($weeksSinceHire / $this->estimated_weeks_to_productivity) * 100);
        $actualProgress = $this->milestone_progress;
        
        return $actualProgress >= ($expectedProgress * 0.8); // Allow 20% margin
    }

    public function needsAdditionalSupport(): bool
    {
        $requirements = $this->support_requirements ?? [];
        $supportLevel = $requirements['level'] ?? 'standard';
        
        $isComplexRole = ($this->learning_curve_factors['complexity'] ?? 'medium') === 'high';
        $hasSignificantGaps = ($this->experience_gap_analysis['gap_level'] ?? 'low') === 'high';
        
        return $supportLevel === 'high' || $isComplexRole || $hasSignificantGaps;
    }

    public function getMilestonesByStatus(): array
    {
        $milestones = $this->productivity_milestones ?? [];
        $completionDates = $this->milestone_completion_dates ?? [];
        
        $completed = [];
        $pending = [];
        
        foreach ($milestones as $index => $milestone) {
            if (isset($completionDates["milestone_$index"])) {
                $completed[] = [
                    'milestone' => $milestone,
                    'completed_at' => $completionDates["milestone_$index"],
                ];
            } else {
                $pending[] = $milestone;
            }
        }
        
        return [
            'completed' => $completed,
            'pending' => $pending,
        ];
    }

    public function getNextMilestone(): ?array
    {
        return $this->current_milestone;
    }

    public function getCriticalGaps(): array
    {
        $analysis = $this->experience_gap_analysis ?? [];
        return $analysis['critical_gaps'] ?? [];
    }

    public function getRequiredSupport(): array
    {
        $requirements = $this->support_requirements ?? [];
        return $requirements['types'] ?? [];
    }
}
