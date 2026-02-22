<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NegotiationTactic extends Model
{
    use HasFactory;

    protected $fillable = [
        'tactic_name',
        'tactic_category',
        'description',
        'when_to_use',
        'how_to_execute',
        'example_phrases',
        'risk_level',
        'best_for_roles',
        'best_for_industries',
        'average_effectiveness',
        'times_recommended',
        'times_used',
        'times_successful',
        'is_active',
    ];

    protected $casts = [
        'example_phrases' => 'array',
        'best_for_roles' => 'array',
        'best_for_industries' => 'array',
        'average_effectiveness' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('tactic_category', $category);
    }

    public function scopeLowRisk($query)
    {
        return $query->whereIn('risk_level', ['low', 'medium']);
    }

    public function scopeHighEffectiveness($query)
    {
        return $query->where('average_effectiveness', '>=', 70);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where(function($q) use ($role) {
            $q->whereNull('best_for_roles')
              ->orWhereJsonContains('best_for_roles', $role);
        });
    }

    public function scopePopular($query)
    {
        return $query->orderBy('times_used', 'desc');
    }

    // Accessors
    public function getRiskLevelColorAttribute(): string
    {
        return match($this->risk_level) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray',
        };
    }

    public function getSuccessRateAttribute(): ?float
    {
        if ($this->times_used <= 0) {
            return null;
        }

        return ($this->times_successful / $this->times_used) * 100;
    }

    public function getUsageRateAttribute(): ?float
    {
        if ($this->times_recommended <= 0) {
            return null;
        }

        return ($this->times_used / $this->times_recommended) * 100;
    }

    // Helper Methods
    public function recordRecommendation(): void
    {
        $this->increment('times_recommended');
    }

    public function recordUsage(bool $successful = false): void
    {
        $this->increment('times_used');
        
        if ($successful) {
            $this->increment('times_successful');
        }

        $this->updateEffectiveness();
    }

    public function updateEffectiveness(): void
    {
        $successRate = $this->success_rate ?? 0;
        $this->update(['average_effectiveness' => $successRate]);
    }

    public function getTacticDetails(): array
    {
        return [
            'name' => $this->tactic_name,
            'category' => $this->tactic_category,
            'description' => $this->description,
            'when_to_use' => $this->when_to_use,
            'how_to_execute' => $this->how_to_execute,
            'example_phrases' => $this->example_phrases ?? [],
            'risk_level' => $this->risk_level,
            'effectiveness' => (float) $this->average_effectiveness,
            'success_rate' => $this->success_rate,
        ];
    }
}
