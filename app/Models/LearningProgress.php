<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'learning_path_id', 'learning_resource_id', 'progress_date',
        'time_spent_minutes', 'completion_percentage', 'activity_type',
        'notes', 'achievements', 'streak_days', 'daily_goal_met', 'metadata',
    ];

    protected $casts = [
        'progress_date' => 'date',
        'completion_percentage' => 'decimal:2',
        'achievements' => 'array',
        'metadata' => 'array',
        'daily_goal_met' => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function learningPath(): BelongsTo { return $this->belongsTo(LearningPath::class); }
    public function learningResource(): BelongsTo { return $this->belongsTo(LearningResource::class); }

    public function scopeToday($query) { return $query->where('progress_date', today()); }
    public function scopeThisWeek($query) { return $query->whereBetween('progress_date', [now()->startOfWeek(), now()->endOfWeek()]); }
    public function scopeThisMonth($query) { return $query->whereMonth('progress_date', now()->month); }
    public function scopeGoalMet($query) { return $query->where('daily_goal_met', true); }

    public function getActivityBadgeAttribute(): string {
        return match($this->activity_type) {
            'watching' => '🎥 Watching',
            'reading' => '📖 Reading',
            'coding' => '💻 Coding',
            'quiz' => '✅ Quiz',
            'project' => '🎯 Project',
            'practice' => '🛠️ Practice',
            default => '📚 Learning'
        };
    }

    public function getTimeFormattedAttribute(): string {
        if ($this->time_spent_minutes < 60) return $this->time_spent_minutes . ' min';
        $hours = floor($this->time_spent_minutes / 60);
        $minutes = $this->time_spent_minutes % 60;
        return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
    }

    public static function recordProgress(int $userId, int $resourceId, int $minutes, float $completion): void {
        self::create([
            'user_id' => $userId,
            'learning_resource_id' => $resourceId,
            'progress_date' => today(),
            'time_spent_minutes' => $minutes,
            'completion_percentage' => $completion,
            'activity_type' => 'reading',
        ]);
    }

    public static function validationRules(): array {
        return [
            'progress_date' => 'required|date',
            'time_spent_minutes' => 'required|integer|min:0',
            'completion_percentage' => 'required|numeric|min:0|max:100',
            'activity_type' => 'required|in:watching,reading,coding,quiz,project,practice',
        ];
    }
}
