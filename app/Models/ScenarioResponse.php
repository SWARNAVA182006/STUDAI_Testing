<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioResponse extends Model
{
    use HasFactory;

    protected $table = 'scout_scenario_responses';

    protected $fillable = [
        'behavioral_assessment_id',
        'situational_scenario_id',
        'selected_approach',
        'reasoning',
        'time_taken',
        'cultural_alignment_score',
        'approach_quality_score',
        'reasoning_quality_score',
        'overall_score',
        'ei_dimensions_demonstrated',
        'leadership_competencies_shown',
        'communication_patterns_detected',
        'strengths_identified',
        'areas_for_improvement',
        'ai_feedback',
        'metadata'
    ];

    protected $casts = [
        'ei_dimensions_demonstrated' => 'array',
        'leadership_competencies_shown' => 'array',
        'communication_patterns_detected' => 'array',
        'strengths_identified' => 'array',
        'areas_for_improvement' => 'array',
        'metadata' => 'array',
        'selected_approach' => 'integer',
        'time_taken' => 'integer',
        'cultural_alignment_score' => 'float',
        'approach_quality_score' => 'float',
        'reasoning_quality_score' => 'float',
        'overall_score' => 'float'
    ];

    /**
     * Get the behavioral assessment this response belongs to
     */
    public function behavioralAssessment(): BelongsTo
    {
        return $this->belongsTo(BehavioralAssessment::class);
    }

    /**
     * Get the scenario this response is for
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(SituationalScenario::class, 'situational_scenario_id');
    }

    /**
     * Scope: Get correct responses (high alignment)
     */
    public function scopeCorrect($query)
    {
        return $query->where('cultural_alignment_score', '>=', 70);
    }

    /**
     * Scope: Get incorrect responses (low alignment)
     */
    public function scopeIncorrect($query)
    {
        return $query->where('cultural_alignment_score', '<', 50);
    }

    /**
     * Scope: Get responses with high confidence (quality reasoning)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('reasoning_quality_score', '>=', 75);
    }

    /**
     * Scope: Get responses with low confidence
     */
    public function scopeLowConfidence($query)
    {
        return $query->where('reasoning_quality_score', '<', 50);
    }

    /**
     * Scope: Get responses for a specific assessment
     */
    public function scopeForAssessment($query, int $assessmentId)
    {
        return $query->where('behavioral_assessment_id', $assessmentId);
    }

    /**
     * Scope: Get responses by scenario category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->whereHas('scenario', function($q) use ($category) {
            $q->where('category', $category);
        });
    }

    /**
     * Scope: Get responses that demonstrated EI
     */
    public function scopeDemonstratedEI($query)
    {
        return $query->whereNotNull('ei_dimensions_demonstrated')
                     ->whereRaw('JSON_LENGTH(ei_dimensions_demonstrated) > 0');
    }

    /**
     * Scope: Get responses that showed leadership
     */
    public function scopeShowedLeadership($query)
    {
        return $query->whereNotNull('leadership_competencies_shown')
                     ->whereRaw('JSON_LENGTH(leadership_competencies_shown) > 0');
    }

    /**
     * Accessor: Get score percentage (0-100)
     */
    public function getScorePercentageAttribute(): float
    {
        return round($this->overall_score ?? 0, 1);
    }

    /**
     * Accessor: Get time taken in minutes
     */
    public function getTimeTakenMinutesAttribute(): float
    {
        return round($this->time_taken / 60, 1);
    }

    /**
     * Accessor: Check if response is correct (culturally aligned)
     */
    public function getIsCorrectAttribute(): bool
    {
        return ($this->cultural_alignment_score ?? 0) >= 70;
    }

    /**
     * Accessor: Check if response is partially correct
     */
    public function getIsPartiallyCorrectAttribute(): bool
    {
        $score = $this->cultural_alignment_score ?? 0;
        return $score >= 50 && $score < 70;
    }

    /**
     * Accessor: Check if response is incorrect
     */
    public function getIsIncorrectAttribute(): bool
    {
        return ($this->cultural_alignment_score ?? 0) < 50;
    }

    /**
     * Accessor: Get correctness status (text)
     */
    public function getCorrectnessStatusAttribute(): string
    {
        $score = $this->cultural_alignment_score ?? 0;

        return match(true) {
            $score >= 85 => 'Excellent Alignment',
            $score >= 70 => 'Good Alignment',
            $score >= 55 => 'Moderate Alignment',
            $score >= 40 => 'Weak Alignment',
            default => 'Poor Alignment'
        };
    }

    /**
     * Accessor: Get reasoning quality level
     */
    public function getReasoningQualityLevelAttribute(): string
    {
        $score = $this->reasoning_quality_score ?? 0;

        return match(true) {
            $score >= 85 => 'Exceptional',
            $score >= 70 => 'Strong',
            $score >= 55 => 'Adequate',
            $score >= 40 => 'Weak',
            default => 'Poor'
        };
    }

    /**
     * Accessor: Get overall performance level
     */
    public function getPerformanceLevelAttribute(): string
    {
        $score = $this->overall_score ?? 0;

        return match(true) {
            $score >= 85 => 'Outstanding',
            $score >= 70 => 'Proficient',
            $score >= 55 => 'Competent',
            $score >= 40 => 'Developing',
            default => 'Needs Improvement'
        };
    }

    /**
     * Accessor: Get EI dimension count
     */
    public function getEiDimensionCountAttribute(): int
    {
        return count($this->ei_dimensions_demonstrated ?? []);
    }

    /**
     * Accessor: Get leadership competency count
     */
    public function getLeadershipCompetencyCountAttribute(): int
    {
        return count($this->leadership_competencies_shown ?? []);
    }

    /**
     * Accessor: Get communication pattern count
     */
    public function getCommunicationPatternCountAttribute(): int
    {
        return count($this->communication_patterns_detected ?? []);
    }

    /**
     * Accessor: Get strength count
     */
    public function getStrengthCountAttribute(): int
    {
        return count($this->strengths_identified ?? []);
    }

    /**
     * Accessor: Get improvement area count
     */
    public function getImprovementAreaCountAttribute(): int
    {
        return count($this->areas_for_improvement ?? []);
    }

    /**
     * Accessor: Check if response was submitted quickly (< 2 min)
     */
    public function getWasSubmittedQuicklyAttribute(): bool
    {
        return $this->time_taken < 120; // Less than 2 minutes
    }

    /**
     * Accessor: Check if response was thorough (> 5 min)
     */
    public function getWasThoroughAttribute(): bool
    {
        return $this->time_taken > 300; // More than 5 minutes
    }

    /**
     * Accessor: Get selected approach data from scenario
     */
    public function getSelectedApproachDataAttribute(): ?array
    {
        if (!$this->scenario) {
            return null;
        }

        return $this->scenario->getApproach($this->selected_approach);
    }

    /**
     * Accessor: Check if selected the preferred approach
     */
    public function getSelectedPreferredApproachAttribute(): bool
    {
        if (!$this->scenario) {
            return false;
        }

        return $this->scenario->isPreferredApproach($this->selected_approach);
    }

    /**
     * Accessor: Get score breakdown
     */
    public function getScoreBreakdownAttribute(): array
    {
        return [
            'cultural_alignment' => [
                'score' => $this->cultural_alignment_score,
                'weight' => 40,
                'weighted_score' => ($this->cultural_alignment_score ?? 0) * 0.4
            ],
            'approach_quality' => [
                'score' => $this->approach_quality_score,
                'weight' => 30,
                'weighted_score' => ($this->approach_quality_score ?? 0) * 0.3
            ],
            'reasoning_quality' => [
                'score' => $this->reasoning_quality_score,
                'weight' => 30,
                'weighted_score' => ($this->reasoning_quality_score ?? 0) * 0.3
            ],
            'overall' => $this->overall_score
        ];
    }

    /**
     * Accessor: Get competency summary
     */
    public function getCompetencySummaryAttribute(): array
    {
        return [
            'emotional_intelligence' => [
                'dimensions' => $this->ei_dimensions_demonstrated ?? [],
                'count' => $this->ei_dimension_count
            ],
            'leadership' => [
                'competencies' => $this->leadership_competencies_shown ?? [],
                'count' => $this->leadership_competency_count
            ],
            'communication' => [
                'patterns' => $this->communication_patterns_detected ?? [],
                'count' => $this->communication_pattern_count
            ]
        ];
    }

    /**
     * Check if demonstrated a specific EI dimension
     */
    public function demonstratedEIDimension(string $dimension): bool
    {
        return in_array($dimension, $this->ei_dimensions_demonstrated ?? []);
    }

    /**
     * Check if showed a specific leadership competency
     */
    public function showedLeadershipCompetency(string $competency): bool
    {
        return in_array($competency, $this->leadership_competencies_shown ?? []);
    }

    /**
     * Check if detected a specific communication pattern
     */
    public function detectedCommunicationPattern(string $pattern): bool
    {
        return in_array($pattern, $this->communication_patterns_detected ?? []);
    }

    /**
     * Get feedback summary (first 200 chars)
     */
    public function getFeedbackSummaryAttribute(): string
    {
        if (!$this->ai_feedback) {
            return 'No feedback available.';
        }

        return strlen($this->ai_feedback) > 200 
            ? substr($this->ai_feedback, 0, 197) . '...'
            : $this->ai_feedback;
    }

    /**
     * Check if has positive feedback
     */
    public function hasPositiveFeedback(): bool
    {
        return $this->overall_score >= 70;
    }

    /**
     * Get response quality indicator
     */
    public function getQualityIndicatorAttribute(): string
    {
        $culturalScore = $this->cultural_alignment_score ?? 0;
        $reasoningScore = $this->reasoning_quality_score ?? 0;

        if ($culturalScore >= 75 && $reasoningScore >= 75) {
            return 'high';
        } elseif ($culturalScore >= 55 && $reasoningScore >= 55) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get response data for display
     */
    public function getDisplayDataAttribute(): array
    {
        return [
            'response_id' => $this->id,
            'scenario_title' => $this->scenario->title ?? 'Unknown Scenario',
            'scenario_category' => $this->scenario->category ?? 'general',
            'selected_approach' => $this->selected_approach,
            'selected_approach_data' => $this->selected_approach_data,
            'selected_preferred' => $this->selected_preferred_approach,
            'reasoning' => $this->reasoning,
            'time_taken' => $this->time_taken,
            'time_taken_minutes' => $this->time_taken_minutes,
            'scores' => $this->score_breakdown,
            'correctness' => $this->correctness_status,
            'performance_level' => $this->performance_level,
            'competencies' => $this->competency_summary,
            'strengths' => $this->strengths_identified ?? [],
            'improvements' => $this->areas_for_improvement ?? [],
            'feedback' => $this->ai_feedback,
            'quality_indicator' => $this->quality_indicator
        ];
    }
}
