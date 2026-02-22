<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamDynamic extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'team_name', 'department', 'team_size', 'role_distribution', 'skill_diversity', 'avg_team_tenure_months',
        'collaboration_frequency_score', 'cross_team_collaboration_score', 'meeting_hours_per_week', 'async_communication_score',
        'communication_channels_usage', 'response_time_patterns', 'preferred_collaboration_times', 'communication_style',
        'team_performance_score', 'velocity_score', 'quality_score', 'innovation_score',
        'psychological_safety_score', 'trust_level', 'openness_to_feedback_score', 'has_healthy_conflict',
        'leadership_approach', 'autonomy_level', 'decision_making_speed', 'leadership_effectiveness_metrics',
        'team_values', 'working_agreements', 'celebration_rituals', 'knowledge_sharing_practices',
        'avg_onboarding_time_days', 'new_hire_integration_score', 'onboarding_best_practices',
        'ideal_new_hire_traits', 'personality_balance_needed', 'skill_gaps_to_fill', 'cultural_additions_needed',
        'team_strengths', 'team_growth_areas', 'compatibility_patterns', 'ai_team_summary',
        'last_analyzed_at', 'data_points_analyzed',
    ];

    protected $casts = [
        'role_distribution' => 'array', 'skill_diversity' => 'array', 'communication_channels_usage' => 'array',
        'response_time_patterns' => 'array', 'preferred_collaboration_times' => 'array', 'leadership_effectiveness_metrics' => 'array',
        'team_values' => 'array', 'working_agreements' => 'array', 'celebration_rituals' => 'array', 'knowledge_sharing_practices' => 'array',
        'onboarding_best_practices' => 'array', 'ideal_new_hire_traits' => 'array', 'personality_balance_needed' => 'array',
        'skill_gaps_to_fill' => 'array', 'cultural_additions_needed' => 'array', 'team_strengths' => 'array', 'team_growth_areas' => 'array',
        'compatibility_patterns' => 'array',
        'avg_team_tenure_months' => 'decimal:1', 'meeting_hours_per_week' => 'decimal:1', 'avg_onboarding_time_days' => 'decimal:1',
        'has_healthy_conflict' => 'boolean', 'last_analyzed_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function scopeHighPerformance($query) { return $query->where('team_performance_score', '>=', 80); }
    public function scopePsychologicallySafe($query) { return $query->where('psychological_safety_score', '>=', 75); }
    public function scopeByDepartment($query, string $dept) { return $query->where('department', $dept); }

    public function getTeamHealthScoreAttribute(): int
    {
        return (int) (
            ($this->team_performance_score * 0.25) +
            ($this->psychological_safety_score * 0.25) +
            ($this->collaboration_frequency_score * 0.20) +
            ($this->innovation_score * 0.15) +
            ($this->new_hire_integration_score * 0.15)
        );
    }

    public function getCollaborationEffectivenessAttribute(): string
    {
        $score = ($this->collaboration_frequency_score + $this->cross_team_collaboration_score) / 2;
        if ($score >= 80) return 'Highly Collaborative';
        if ($score >= 60) return 'Moderately Collaborative';
        return 'Needs Improvement';
    }

    public function getLeadershipQualityAttribute(): string
    {
        $metrics = $this->leadership_effectiveness_metrics ?? [];
        $avgScore = !empty($metrics) ? array_sum($metrics) / count($metrics) : 50;
        if ($avgScore >= 85) return 'Exceptional Leadership';
        if ($avgScore >= 70) return 'Strong Leadership';
        if ($avgScore >= 55) return 'Developing Leadership';
        return 'Leadership Gap';
    }

    public function getOnboardingSuccessRateAttribute(): string
    {
        $score = $this->new_hire_integration_score;
        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 55) return 'Fair';
        return 'Challenging';
    }

    public function isPsychologicallySafe(): bool { return $this->psychological_safety_score >= 75; }
    public function isHighPerforming(): bool { return $this->team_performance_score >= 80; }

    public function assessCandidateCompatibility(array $candidateProfile): array
    {
        $score = 50; // Base compatibility
        $strengths = [];
        $concerns = [];

        // Skill gap fit
        $candidateSkills = $candidateProfile['skills'] ?? [];
        $neededSkills = $this->skill_gaps_to_fill ?? [];
        $skillMatch = count(array_intersect($candidateSkills, $neededSkills));
        if ($skillMatch > 0) {
            $score += ($skillMatch * 10);
            $strengths[] = "Fills {$skillMatch} critical skill gap(s)";
        }

        // Cultural additions
        $candidateTraits = $candidateProfile['traits'] ?? [];
        $neededTraits = $this->cultural_additions_needed ?? [];
        $traitMatch = count(array_intersect($candidateTraits, $neededTraits));
        if ($traitMatch > 0) {
            $score += ($traitMatch * 8);
            $strengths[] = "Brings {$traitMatch} desired cultural trait(s)";
        }

        // Work style alignment
        $idealTraits = $this->ideal_new_hire_traits ?? [];
        $alignmentCount = 0;
        foreach ($idealTraits as $trait => $required) {
            if (isset($candidateProfile[$trait]) && $candidateProfile[$trait] == $required) {
                $alignmentCount++;
            }
        }
        $score += ($alignmentCount * 5);

        // Psychological safety consideration
        if ($this->isPsychologicallySafe() && isset($candidateProfile['collaboration_comfort']) && $candidateProfile['collaboration_comfort'] >= 70) {
            $score += 10;
            $strengths[] = 'Strong fit for psychologically safe environment';
        }

        return [
            'compatibility_score' => min(100, $score),
            'fit_level' => $this->getFitLevel(min(100, $score)),
            'strengths' => $strengths,
            'concerns' => $concerns,
            'integration_prediction' => $this->predictIntegrationSuccess($candidateProfile),
        ];
    }

    private function getFitLevel(int $score): string
    {
        if ($score >= 85) return 'Excellent Fit';
        if ($score >= 70) return 'Strong Fit';
        if ($score >= 55) return 'Moderate Fit';
        return 'Potential Challenges';
    }

    public function predictIntegrationSuccess(array $candidateProfile): array
    {
        $onboardingTime = $this->avg_onboarding_time_days;
        $integrationScore = $this->new_hire_integration_score;

        $prediction = [
            'estimated_onboarding_days' => $onboardingTime,
            'integration_confidence' => $integrationScore,
            'recommended_support' => [],
        ];

        if ($integrationScore < 70) {
            $prediction['recommended_support'][] = 'Assign dedicated mentor';
            $prediction['recommended_support'][] = 'Schedule weekly check-ins';
        }

        if ($this->async_communication_score >= 70 && !isset($candidateProfile['remote_experience'])) {
            $prediction['recommended_support'][] = 'Async communication training';
        }

        return $prediction;
    }

    public function getIdealCandidateProfile(): array
    {
        return [
            'required_traits' => $this->ideal_new_hire_traits ?? [],
            'skill_needs' => $this->skill_gaps_to_fill ?? [],
            'cultural_needs' => $this->cultural_additions_needed ?? [],
            'personality_balance' => $this->personality_balance_needed ?? [],
            'team_values' => $this->team_values ?? [],
            'work_style' => [
                'collaboration_level' => $this->collaboration_frequency_score >= 70 ? 'High' : 'Moderate',
                'autonomy_level' => $this->autonomy_level,
                'meeting_culture' => $this->meeting_hours_per_week >= 15 ? 'Meeting-Heavy' : 'Focused Work',
            ],
        ];
    }
}
