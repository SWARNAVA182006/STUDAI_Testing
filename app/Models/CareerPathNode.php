<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class CareerPathNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_title',
        'normalized_title',
        'industry',
        'level',
        'level_rank',
        'avg_salary',
        'avg_years_experience',
        'required_skills',
        'common_transitions_to',
        'common_transitions_from',
        'transition_count',
        'avg_transition_time',
        'certifications',
        'education_requirements',
    ];

    protected $casts = [
        'avg_salary' => 'decimal:2',
        'avg_years_experience' => 'decimal:2',
        'avg_transition_time' => 'decimal:2',
        'required_skills' => 'array',
        'common_transitions_to' => 'array',
        'common_transitions_from' => 'array',
        'certifications' => 'array',
        'education_requirements' => 'array',
    ];

    /**
     * Get outgoing edges (transitions to other roles).
     */
    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(CareerPathEdge::class, 'from_node_id');
    }

    /**
     * Get incoming edges (transitions from other roles).
     */
    public function incomingEdges(): HasMany
    {
        return $this->hasMany(CareerPathEdge::class, 'to_node_id');
    }

    /**
     * Scope by industry.
     */
    public function scopeForIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope by level.
     */
    public function scopeOfLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Get next possible roles.
     */
    public function getNextRoles(): array
    {
        return $this->outgoingEdges()
            ->with('toNode')
            ->orderByDesc('transition_count')
            ->get()
            ->map(function ($edge) {
                return [
                    'role' => $edge->toNode->job_title,
                    'level' => $edge->toNode->level,
                    'avg_salary' => $edge->toNode->avg_salary,
                    'transition_count' => $edge->transition_count,
                    'avg_years' => $edge->avg_transition_years,
                    'salary_increase' => $edge->salary_increase_percentage,
                    'skills_needed' => $edge->required_skills_gap,
                ];
            })
            ->toArray();
    }

    /**
     * Format for graph visualization.
     */
    public function toGraphNode(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->job_title,
            'level' => $this->level_rank,
            'salary' => $this->avg_salary,
            'experience' => $this->avg_years_experience,
            'industry' => $this->industry,
        ];
    }
}
