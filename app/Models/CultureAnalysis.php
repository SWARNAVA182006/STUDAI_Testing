<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultureAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_dna_profile_id',
        'power_distance_score',
        'individualism_score',
        'uncertainty_avoidance_score',
        'long_term_orientation_score',
        'indulgence_score',
        'office_culture',
        'meeting_culture',
        'feedback_culture',
        'recognition_patterns',
        'collaboration_tools',
        'avg_team_size',
        'cross_functional_score',
        'autonomy_score',
        'innovation_index',
        'learning_culture_score',
        'professional_development',
        'has_mentorship_program',
        'diversity_metrics',
        'inclusion_score',
        'dei_initiatives',
        'culture_strengths',
        'culture_challenges',
        'culture_archetypes',
        'ai_culture_summary',
    ];

    protected $casts = [
        'office_culture' => 'array',
        'meeting_culture' => 'array',
        'feedback_culture' => 'array',
        'recognition_patterns' => 'array',
        'collaboration_tools' => 'array',
        'professional_development' => 'array',
        'diversity_metrics' => 'array',
        'dei_initiatives' => 'array',
        'culture_strengths' => 'array',
        'culture_challenges' => 'array',
        'culture_archetypes' => 'array',
        'avg_team_size' => 'decimal:1',
        'has_mentorship_program' => 'boolean',
    ];

    // Relationships
    public function companyDnaProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyDNAProfile::class);
    }

    // Scopes
    public function scopeHighInnovation($query)
    {
        return $query->where('innovation_index', '>=', 70);
    }

    public function scopeStrongLearningCulture($query)
    {
        return $query->where('learning_culture_score', '>=', 70);
    }

    public function scopeHighAutonomy($query)
    {
        return $query->where('autonomy_score', '>=', 70);
    }

    // Accessors
    public function getCultureTypeAttribute(): string
    {
        $power = $this->power_distance_score ?? 50;
        $individual = $this->individualism_score ?? 50;

        if ($power < 40 && $individual >= 60) return 'Egalitarian & Independent';
        if ($power < 40 && $individual < 40) return 'Flat & Collaborative';
        if ($power >= 60 && $individual >= 60) return 'Hierarchical & Merit-Based';
        if ($power >= 60 && $individual < 40) return 'Traditional & Team-Oriented';

        return 'Balanced';
    }

    public function getWorkEnvironmentTypeAttribute(): string
    {
        $office = $this->office_culture ?? [];
        
        if (in_array('remote_first', $office)) return 'Remote-First';
        if (in_array('hybrid_flexible', $office)) return 'Hybrid Flexible';
        if (in_array('collaborative_spaces', $office)) return 'Collaborative Office';
        if (in_array('quiet_zones', $office)) return 'Focus-Oriented Office';

        return 'Traditional Office';
    }

    public function getRiskToleranceAttribute(): string
    {
        $score = $this->uncertainty_avoidance_score ?? 50;
        
        if ($score < 30) return 'High Risk Tolerance';
        if ($score < 50) return 'Moderate Risk Tolerance';
        if ($score < 70) return 'Risk Aware';
        return 'Risk Averse';
    }

    public function getWorkLifeBalanceScoreAttribute(): int
    {
        return $this->indulgence_score ?? 50;
    }

    public function getMeetingCultureTypeAttribute(): string
    {
        $culture = $this->meeting_culture ?? [];
        $frequency = $culture['frequency'] ?? 'moderate';

        if ($frequency === 'minimal') return 'Async-First';
        if ($frequency === 'moderate') return 'Balanced';
        if ($frequency === 'frequent') return 'Meeting-Heavy';

        return 'Unknown';
    }

    public function getFeedbackFrequencyAttribute(): string
    {
        $culture = $this->feedback_culture ?? [];
        
        if (in_array('continuous', $culture)) return 'Continuous';
        if (in_array('quarterly', $culture)) return 'Quarterly';
        if (in_array('annual', $culture)) return 'Annual';

        return 'As-Needed';
    }

    public function getDiversityScoreAttribute(): int
    {
        $metrics = $this->diversity_metrics ?? [];
        
        $score = 0;
        $count = 0;

        if (isset($metrics['gender_diversity'])) {
            $score += $metrics['gender_diversity'];
            $count++;
        }
        if (isset($metrics['age_diversity'])) {
            $score += $metrics['age_diversity'];
            $count++;
        }
        if (isset($metrics['background_diversity'])) {
            $score += $metrics['background_diversity'];
            $count++;
        }

        return $count > 0 ? (int) ($score / $count) : 0;
    }

    public function getCultureHealthScoreAttribute(): int
    {
        return (int) (
            ($this->innovation_index * 0.25) +
            ($this->learning_culture_score * 0.25) +
            ($this->autonomy_score * 0.20) +
            ($this->inclusion_score * 0.15) +
            ($this->cross_functional_score * 0.15)
        );
    }

    public function getTopStrengthsAttribute(): array
    {
        return array_slice($this->culture_strengths ?? [], 0, 3);
    }

    public function getTopChallengesAttribute(): array
    {
        return array_slice($this->culture_challenges ?? [], 0, 3);
    }

    // Helper Methods
    public function isInnovationDriven(): bool
    {
        return $this->innovation_index >= 70;
    }

    public function hasStrongLearningCulture(): bool
    {
        return $this->learning_culture_score >= 70;
    }

    public function isCrossFunctional(): bool
    {
        return $this->cross_functional_score >= 60;
    }

    public function supportsRemoteWork(): bool
    {
        $office = $this->office_culture ?? [];
        return in_array('remote_first', $office) || in_array('hybrid_flexible', $office);
    }

    public function getCandidateFitCriteria(): array
    {
        return [
            'innovation_mindset_required' => $this->isInnovationDriven(),
            'learning_agility_required' => $this->hasStrongLearningCulture(),
            'collaboration_skills_required' => $this->isCrossFunctional(),
            'remote_work_readiness_required' => $this->supportsRemoteWork(),
            'autonomy_comfort_required' => $this->autonomy_score >= 60,
            'preferred_work_style' => $this->getWorkEnvironmentTypeAttribute(),
            'cultural_values' => $this->culture_archetypes ?? [],
        ];
    }
}
