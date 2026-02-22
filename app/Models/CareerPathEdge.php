<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerPathEdge extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_node_id',
        'to_node_id',
        'transition_count',
        'avg_transition_years',
        'salary_increase_percentage',
        'success_rate',
        'required_skills_gap',
        'recommended_certifications',
    ];

    protected $casts = [
        'avg_transition_years' => 'decimal:2',
        'salary_increase_percentage' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'required_skills_gap' => 'array',
        'recommended_certifications' => 'array',
    ];

    /**
     * Get the source node.
     */
    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(CareerPathNode::class, 'from_node_id');
    }

    /**
     * Get the destination node.
     */
    public function toNode(): BelongsTo
    {
        return $this->belongsTo(CareerPathNode::class, 'to_node_id');
    }

    /**
     * Format for graph visualization.
     */
    public function toGraphEdge(): array
    {
        return [
            'from' => $this->from_node_id,
            'to' => $this->to_node_id,
            'value' => $this->transition_count,
            'label' => $this->avg_transition_years . ' years',
            'salaryIncrease' => $this->salary_increase_percentage,
        ];
    }
}
