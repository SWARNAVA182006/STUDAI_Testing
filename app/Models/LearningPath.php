<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'skill_gap_id',
        'path_name',
        'description',
        'target_skill',
        'target_proficiency',
        'total_duration_hours',
        'total_resources',
        'completed_resources',
        'completion_percentage',
        'learning_style_preferences',
        'schedule_preferences',
        'steps',
        'prerequisites_completed',
        'difficulty_progression',
        'estimated_completion_weeks',
        'started_date',
        'target_completion_date',
        'completed_date',
        'status',
        'is_ai_generated',
        'ai_customizations',
    ];

    protected $casts = [
        'learning_style_preferences' => 'array',
        'schedule_preferences' => 'array',
        'steps' => 'array',
        'prerequisites_completed' => 'array',
        'ai_customizations' => 'array',
        'completion_percentage' => 'decimal:2',
        'started_date' => 'date',
        'target_completion_date' => 'date',
        'completed_date' => 'date',
        'is_ai_generated' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillGap(): BelongsTo
    {
        return $this->belongsTo(SkillGap::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LearningResource::class)->orderBy('step_order');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['active', 'paused'])
                     ->where('completion_percentage', '>', 0)
                     ->where('completion_percentage', '<', 100);
    }

    /**
     * Accessors
     */
    public function getProgressPercentageAttribute(): float
    {
        return (float) $this->completion_percentage;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'completed' => '✅ Completed',
            'active' => '🚀 Active',
            'paused' => '⏸️ Paused',
            'abandoned' => '❌ Abandoned',
            'draft' => '📝 Draft',
            default => '❓ Unknown'
        };
    }

    public function getDifficultyBadgeAttribute(): string
    {
        return match($this->difficulty_progression) {
            'steep' => '🔴 Steep',
            'moderate' => '🟡 Moderate',
            'gradual' => '🟢 Gradual',
            default => '⚪ Unknown'
        };
    }

    public function getRemainingHoursAttribute(): int
    {
        $completedHours = ($this->total_duration_hours * $this->completion_percentage) / 100;
        return max(0, $this->total_duration_hours - $completedHours);
    }

    public function getRemainingResourcesAttribute(): int
    {
        return max(0, $this->total_resources - $this->completed_resources);
    }

    public function getEstimatedCompletionDateAttribute(): ?string
    {
        if ($this->status === 'completed') return null;
        if (!$this->target_completion_date) return 'Not set';
        
        $daysRemaining = now()->diffInDays($this->target_completion_date, false);
        
        if ($daysRemaining < 0) return 'Overdue';
        if ($daysRemaining === 0) return 'Today';
        if ($daysRemaining === 1) return 'Tomorrow';
        if ($daysRemaining < 7) return "In {$daysRemaining} days";
        if ($daysRemaining < 30) return 'In ' . ceil($daysRemaining / 7) . ' weeks';
        return 'In ' . ceil($daysRemaining / 30) . ' months';
    }

    public function getDailyTimeCommitmentAttribute(): ?string
    {
        $preferences = $this->schedule_preferences;
        if (!$preferences || !isset($preferences['daily_minutes'])) return null;
        
        $minutes = $preferences['daily_minutes'];
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) return "{$hours}h {$mins}m";
        if ($hours > 0) return "{$hours} hour" . ($hours > 1 ? 's' : '');
        return "{$mins} minutes";
    }

    /**
     * Mutators
     */
    public function setTargetSkillAttribute($value)
    {
        $this->attributes['target_skill'] = ucwords(strtolower($value));
    }

    /**
     * Helper Methods
     */
    public function start(): void
    {
        $this->update([
            'status' => 'active',
            'started_date' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completion_percentage' => 100,
            'completed_resources' => $this->total_resources,
        ]);
    }

    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }

    public function updateProgress(int $completedResources): void
    {
        $percentage = $this->total_resources > 0 
            ? ($completedResources / $this->total_resources) * 100 
            : 0;
        
        $this->update([
            'completed_resources' => $completedResources,
            'completion_percentage' => min(100, $percentage),
        ]);
        
        if ($percentage >= 100) {
            $this->complete();
        }
    }

    public function addResource(LearningResource $resource): void
    {
        $this->increment('total_resources');
        $this->total_duration_hours += $resource->duration_hours ?? 0;
        $this->save();
    }

    public function removeResource(LearningResource $resource): void
    {
        $this->decrement('total_resources');
        $this->total_duration_hours -= $resource->duration_hours ?? 0;
        $this->save();
    }

    public function setSchedulePreferences(int $dailyMinutes, array $preferredDays = []): void
    {
        $this->update([
            'schedule_preferences' => [
                'daily_minutes' => $dailyMinutes,
                'preferred_days' => $preferredDays,
                'updated_at' => now()->toISOString(),
            ]
        ]);
    }

    public function setLearningStylePreferences(array $styles): void
    {
        $this->update([
            'learning_style_preferences' => $styles
        ]);
    }

    public function calculateTargetDate(): void
    {
        if (!$this->schedule_preferences || !isset($this->schedule_preferences['daily_minutes'])) {
            return;
        }
        
        $dailyMinutes = $this->schedule_preferences['daily_minutes'];
        $remainingMinutes = $this->remaining_hours * 60;
        $daysNeeded = ceil($remainingMinutes / $dailyMinutes);
        
        $this->update([
            'target_completion_date' => now()->addDays($daysNeeded),
        ]);
    }

    public function getNextResource(): ?LearningResource
    {
        return $this->resources()
            ->whereDoesntHave('progress', function($query) {
                $query->where('completion_percentage', 100);
            })
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Validation Rules
     */
    public static function validationRules(): array
    {
        return [
            'path_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_skill' => 'required|string|max:255',
            'target_proficiency' => 'required|in:beginner,intermediate,advanced,expert',
            'difficulty_progression' => 'nullable|in:gradual,moderate,steep',
            'estimated_completion_weeks' => 'nullable|integer|min:1',
            'target_completion_date' => 'nullable|date|after:today',
        ];
    }
}
