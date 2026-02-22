<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NegotiationScript extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'scenario_id',
        'script_type',
        'script_stage',
        'script_name',
        'subject_line',
        'opening',
        'body',
        'closing',
        'full_script',
        'key_talking_points',
        'phrases_to_use',
        'phrases_to_avoid',
        'transition_phrases',
        'tone',
        'cultural_adaptations',
        'personality_notes',
        'anchoring_tactics',
        'framing_strategies',
        'reciprocity_elements',
        'includes_deadline',
        'includes_alternatives',
        'effectiveness_rating',
        'was_used',
        'used_at',
    ];

    protected $casts = [
        'key_talking_points' => 'array',
        'phrases_to_use' => 'array',
        'phrases_to_avoid' => 'array',
        'transition_phrases' => 'array',
        'cultural_adaptations' => 'array',
        'anchoring_tactics' => 'array',
        'framing_strategies' => 'array',
        'reciprocity_elements' => 'array',
        'includes_deadline' => 'boolean',
        'includes_alternatives' => 'boolean',
        'was_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(NegotiationStrategy::class, 'strategy_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(NegotiationScenario::class, 'scenario_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('script_type', $type);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('script_stage', $stage);
    }

    public function scopeForEmail($query)
    {
        return $query->where('script_type', 'email');
    }

    public function scopeForPhone($query)
    {
        return $query->where('script_type', 'phone');
    }

    public function scopeForInPerson($query)
    {
        return $query->where('script_type', 'in_person');
    }

    public function scopeInitialResponse($query)
    {
        return $query->where('script_stage', 'initial_response');
    }

    public function scopeUsed($query)
    {
        return $query->where('was_used', true);
    }

    public function scopeUnused($query)
    {
        return $query->where('was_used', false);
    }

    public function scopeHighlyRated($query)
    {
        return $query->where('effectiveness_rating', '>=', 4);
    }

    // Accessors
    public function getScriptTypeLabelAttribute(): string
    {
        return match($this->script_type) {
            'email' => 'Email',
            'phone' => 'Phone Call',
            'in_person' => 'In-Person Meeting',
            'video_call' => 'Video Call',
            default => 'Unknown',
        };
    }

    public function getScriptTypeIconAttribute(): string
    {
        return match($this->script_type) {
            'email' => '✉️',
            'phone' => '📞',
            'in_person' => '🤝',
            'video_call' => '💻',
            default => '📝',
        };
    }

    public function getScriptStageLabelAttribute(): string
    {
        return match($this->script_stage) {
            'initial_response' => 'Initial Response',
            'counter_offer' => 'Counter Offer',
            'follow_up' => 'Follow Up',
            'closing' => 'Closing',
            default => 'Unknown',
        };
    }

    public function getToneLabelAttribute(): string
    {
        return match($this->tone) {
            'professional' => 'Professional',
            'enthusiastic' => 'Enthusiastic',
            'collaborative' => 'Collaborative',
            'confident' => 'Confident',
            'grateful' => 'Grateful',
            default => 'Neutral',
        };
    }

    public function getToneColorAttribute(): string
    {
        return match($this->tone) {
            'professional' => 'blue',
            'enthusiastic' => 'green',
            'collaborative' => 'purple',
            'confident' => 'orange',
            'grateful' => 'pink',
            default => 'gray',
        };
    }

    public function getEffectivenessLabelAttribute(): ?string
    {
        if (!$this->effectiveness_rating) {
            return null;
        }

        return match(true) {
            $this->effectiveness_rating >= 4 => 'Highly Effective',
            $this->effectiveness_rating >= 3 => 'Effective',
            $this->effectiveness_rating >= 2 => 'Moderately Effective',
            default => 'Needs Improvement',
        };
    }

    public function getEffectivenessColorAttribute(): ?string
    {
        if (!$this->effectiveness_rating) {
            return null;
        }

        return match(true) {
            $this->effectiveness_rating >= 4 => 'green',
            $this->effectiveness_rating >= 3 => 'blue',
            $this->effectiveness_rating >= 2 => 'yellow',
            default => 'red',
        };
    }

    // Helper Methods
    public function markAsUsed(): void
    {
        $this->update([
            'was_used' => true,
            'used_at' => now(),
        ]);
    }

    public function rateEffectiveness(int $rating): void
    {
        $this->update([
            'effectiveness_rating' => max(1, min(5, $rating)),
        ]);
    }

    public function hasSubjectLine(): bool
    {
        return !empty($this->subject_line);
    }

    public function hasTacticalElements(): bool
    {
        return !empty($this->anchoring_tactics) || 
               !empty($this->framing_strategies) || 
               !empty($this->reciprocity_elements);
    }

    public function getTacticalSummary(): array
    {
        $summary = [];

        if (!empty($this->anchoring_tactics)) {
            $summary['anchoring'] = $this->anchoring_tactics;
        }

        if (!empty($this->framing_strategies)) {
            $summary['framing'] = $this->framing_strategies;
        }

        if (!empty($this->reciprocity_elements)) {
            $summary['reciprocity'] = $this->reciprocity_elements;
        }

        if ($this->includes_deadline) {
            $summary['deadline'] = 'Includes response deadline';
        }

        if ($this->includes_alternatives) {
            $summary['alternatives'] = 'Mentions alternative options';
        }

        return $summary;
    }

    public function getFormattedScript(): string
    {
        $formatted = '';

        if ($this->script_type === 'email' && $this->subject_line) {
            $formatted .= "Subject: {$this->subject_line}\n\n";
            $formatted .= str_repeat('-', 50) . "\n\n";
        }

        $formatted .= $this->full_script ?? $this->buildFullScript();

        return $formatted;
    }

    public function buildFullScript(): string
    {
        $parts = [];

        if ($this->opening) {
            $parts[] = $this->opening;
        }

        if ($this->body) {
            $parts[] = $this->body;
        }

        if ($this->closing) {
            $parts[] = $this->closing;
        }

        return implode("\n\n", $parts);
    }

    public function getKeyPointsSummary(): string
    {
        if (empty($this->key_talking_points)) {
            return 'No key points';
        }

        return implode(' • ', array_slice($this->key_talking_points, 0, 3));
    }

    public function getRecommendedPhrases(): array
    {
        return [
            'to_use' => $this->phrases_to_use ?? [],
            'to_avoid' => $this->phrases_to_avoid ?? [],
            'transitions' => $this->transition_phrases ?? [],
        ];
    }

    public function getCulturalGuidance(): ?string
    {
        if (empty($this->cultural_adaptations)) {
            return null;
        }

        $adaptations = $this->cultural_adaptations;
        
        if (is_array($adaptations)) {
            return implode(' ', $adaptations);
        }

        return $adaptations;
    }

    public function personalizeScript(array $userData): string
    {
        $script = $this->getFormattedScript();

        // Replace common placeholders
        $replacements = [
            '[Your Name]' => $userData['name'] ?? '[Your Name]',
            '[Role]' => $userData['role'] ?? '[Role]',
            '[Company]' => $userData['company'] ?? '[Company]',
            '[Hiring Manager]' => $userData['hiring_manager'] ?? '[Hiring Manager]',
            '[Counter Offer]' => $userData['counter_offer'] ?? '[Counter Offer]',
            '[Key Skill 1]' => $userData['key_skills'][0] ?? '[Key Skill]',
            '[Key Skill 2]' => $userData['key_skills'][1] ?? '[Another Skill]',
        ];

        foreach ($replacements as $placeholder => $value) {
            $script = str_replace($placeholder, $value, $script);
        }

        return $script;
    }

    public function getUsageStatistics(): array
    {
        return [
            'was_used' => $this->was_used,
            'used_at' => $this->used_at?->format('Y-m-d H:i:s'),
            'effectiveness_rating' => $this->effectiveness_rating,
            'effectiveness_label' => $this->effectiveness_label,
        ];
    }

    public function compareToOtherScripts(): array
    {
        // Get other scripts of same type and stage
        $similarScripts = self::where('strategy_id', $this->strategy_id)
            ->where('script_type', $this->script_type)
            ->where('script_stage', $this->script_stage)
            ->where('id', '!=', $this->id)
            ->get();

        $comparison = [
            'this_script' => [
                'name' => $this->script_name,
                'tone' => $this->tone,
                'was_used' => $this->was_used,
                'rating' => $this->effectiveness_rating,
            ],
            'alternatives' => [],
        ];

        foreach ($similarScripts as $script) {
            $comparison['alternatives'][] = [
                'id' => $script->id,
                'name' => $script->script_name,
                'tone' => $script->tone,
                'was_used' => $script->was_used,
                'rating' => $script->effectiveness_rating,
            ];
        }

        return $comparison;
    }
}
