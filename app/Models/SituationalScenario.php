<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SituationalScenario extends Model
{
    use HasFactory;

    protected $table = 'scout_situational_scenarios';

    protected $fillable = [
        'behavioral_assessment_id',
        'scenario_number',
        'title',
        'context',
        'situation',
        'category',
        'difficulty_level',
        'valid_approaches',
        'preferred_approach',
        'cultural_alignment_weights',
        'evaluates_dimensions',
        'metadata'
    ];

    protected $casts = [
        'valid_approaches' => 'array',
        'cultural_alignment_weights' => 'array',
        'evaluates_dimensions' => 'array',
        'metadata' => 'array',
        'scenario_number' => 'integer',
        'preferred_approach' => 'integer'
    ];

    /**
     * Get the behavioral assessment this scenario belongs to
     */
    public function behavioralAssessment(): BelongsTo
    {
        return $this->belongsTo(BehavioralAssessment::class);
    }

    /**
     * Get the response for this scenario (if answered)
     */
    public function response(): HasOne
    {
        return $this->hasOne(ScenarioResponse::class, 'situational_scenario_id');
    }

    /**
     * Scope: Get scenarios by difficulty level
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    /**
     * Scope: Get easy scenarios
     */
    public function scopeEasy($query)
    {
        return $query->where('difficulty_level', 'easy');
    }

    /**
     * Scope: Get medium scenarios
     */
    public function scopeMedium($query)
    {
        return $query->where('difficulty_level', 'medium');
    }

    /**
     * Scope: Get hard scenarios
     */
    public function scopeHard($query)
    {
        return $query->where('difficulty_level', 'hard');
    }

    /**
     * Scope: Get expert scenarios
     */
    public function scopeExpert($query)
    {
        return $query->where('difficulty_level', 'expert');
    }

    /**
     * Scope: Get scenarios by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get scenarios for a specific assessment
     */
    public function scopeForAssessment($query, int $assessmentId)
    {
        return $query->where('behavioral_assessment_id', $assessmentId);
    }

    /**
     * Scope: Get unanswered scenarios
     */
    public function scopeUnanswered($query)
    {
        return $query->doesntHave('response');
    }

    /**
     * Scope: Get answered scenarios
     */
    public function scopeAnswered($query)
    {
        return $query->has('response');
    }

    /**
     * Accessor: Check if scenario has been answered
     */
    public function getIsAnsweredAttribute(): bool
    {
        return $this->response()->exists();
    }

    /**
     * Accessor: Get difficulty weight (for scoring)
     */
    public function getDifficultyWeightAttribute(): float
    {
        return match($this->difficulty_level) {
            'easy' => 1.0,
            'medium' => 1.3,
            'hard' => 1.6,
            'expert' => 2.0,
            default => 1.0
        };
    }

    /**
     * Accessor: Get estimated time (minutes)
     */
    public function getEstimatedTimeAttribute(): int
    {
        return match($this->difficulty_level) {
            'easy' => 3,
            'medium' => 5,
            'hard' => 7,
            'expert' => 10,
            default => 5
        };
    }

    /**
     * Accessor: Get approach count
     */
    public function getApproachCountAttribute(): int
    {
        return count($this->valid_approaches ?? []);
    }

    /**
     * Accessor: Get preferred approach data
     */
    public function getPreferredApproachDataAttribute(): ?array
    {
        $approaches = $this->valid_approaches ?? [];
        $index = $this->preferred_approach;

        return $approaches[$index] ?? null;
    }

    /**
     * Accessor: Get all approach descriptions
     */
    public function getApproachDescriptionsAttribute(): array
    {
        $approaches = $this->valid_approaches ?? [];
        
        return array_map(function($approach, $index) {
            return [
                'index' => $index,
                'description' => $approach['approach_description'] ?? $approach['description'] ?? 'Approach ' . ($index + 1),
                'cultural_score' => $approach['cultural_alignment_score'] ?? 50
            ];
        }, $approaches, array_keys($approaches));
    }

    /**
     * Accessor: Check if scenario evaluates emotional intelligence
     */
    public function getEvaluatesEIAttribute(): bool
    {
        $dimensions = $this->evaluates_dimensions ?? [];
        $eiDimensions = ['self_awareness', 'self_regulation', 'empathy', 'social_skills', 'motivation'];
        
        return !empty(array_intersect($dimensions, $eiDimensions));
    }

    /**
     * Accessor: Check if scenario evaluates leadership
     */
    public function getEvaluatesLeadershipAttribute(): bool
    {
        $dimensions = $this->evaluates_dimensions ?? [];
        $leadershipDimensions = ['strategic_thinking', 'people_management', 'decision_making', 
                                  'conflict_resolution', 'vision_communication', 'change_management'];
        
        return !empty(array_intersect($dimensions, $leadershipDimensions));
    }

    /**
     * Accessor: Check if scenario evaluates communication
     */
    public function getEvaluatesCommunicationAttribute(): bool
    {
        $dimensions = $this->evaluates_dimensions ?? [];
        $communicationDimensions = ['clarity', 'diplomacy', 'assertiveness', 'active_listening', 'adaptability'];
        
        return !empty(array_intersect($dimensions, $communicationDimensions));
    }

    /**
     * Accessor: Get category display name
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->category));
    }

    /**
     * Accessor: Get difficulty display name
     */
    public function getDifficultyDisplayNameAttribute(): string
    {
        return ucfirst($this->difficulty_level);
    }

    /**
     * Accessor: Get difficulty color (for UI)
     */
    public function getDifficultyColorAttribute(): string
    {
        return match($this->difficulty_level) {
            'easy' => 'green',
            'medium' => 'blue',
            'hard' => 'orange',
            'expert' => 'purple',
            default => 'gray'
        };
    }

    /**
     * Get approach by index
     */
    public function getApproach(int $index): ?array
    {
        $approaches = $this->valid_approaches ?? [];
        return $approaches[$index] ?? null;
    }

    /**
     * Check if approach index is valid
     */
    public function isValidApproach(int $index): bool
    {
        $approaches = $this->valid_approaches ?? [];
        return isset($approaches[$index]);
    }

    /**
     * Get cultural alignment score for an approach
     */
    public function getCulturalAlignmentScore(int $approachIndex): float
    {
        $approach = $this->getApproach($approachIndex);
        return $approach['cultural_alignment_score'] ?? 50.0;
    }

    /**
     * Check if approach is the preferred one
     */
    public function isPreferredApproach(int $approachIndex): bool
    {
        return $approachIndex === $this->preferred_approach;
    }

    /**
     * Get strengths of an approach
     */
    public function getApproachStrengths(int $approachIndex): array
    {
        $approach = $this->getApproach($approachIndex);
        return $approach['strengths'] ?? [];
    }

    /**
     * Get concerns of an approach
     */
    public function getApproachConcerns(int $approachIndex): array
    {
        $approach = $this->getApproach($approachIndex);
        return $approach['potential_concerns'] ?? $approach['concerns'] ?? [];
    }

    /**
     * Get EI dimensions demonstrated by an approach
     */
    public function getApproachEIDimensions(int $approachIndex): array
    {
        $approach = $this->getApproach($approachIndex);
        return $approach['ei_dimensions_demonstrated'] ?? [];
    }

    /**
     * Get leadership competencies shown by an approach
     */
    public function getApproachLeadershipCompetencies(int $approachIndex): array
    {
        $approach = $this->getApproach($approachIndex);
        return $approach['leadership_competencies_shown'] ?? [];
    }

    /**
     * Get full scenario data for display
     */
    public function getDisplayDataAttribute(): array
    {
        return [
            'scenario_id' => $this->id,
            'scenario_number' => $this->scenario_number,
            'title' => $this->title,
            'context' => $this->context,
            'situation' => $this->situation,
            'category' => $this->category,
            'category_display' => $this->category_display_name,
            'difficulty_level' => $this->difficulty_level,
            'difficulty_display' => $this->difficulty_display_name,
            'difficulty_color' => $this->difficulty_color,
            'estimated_time' => $this->estimated_time,
            'valid_approaches' => $this->valid_approaches,
            'approach_count' => $this->approach_count,
            'evaluates_dimensions' => $this->evaluates_dimensions,
            'evaluates_ei' => $this->evaluates_ei,
            'evaluates_leadership' => $this->evaluates_leadership,
            'evaluates_communication' => $this->evaluates_communication
        ];
    }
}
