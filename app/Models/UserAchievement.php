<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'progress',
        'target',
        'is_completed',
        'is_claimed',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'progress' => 'integer',
            'target' => 'integer',
            'is_completed' => 'boolean',
            'is_claimed' => 'boolean',
            'claimed_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeUnclaimed($query)
    {
        return $query->where('is_completed', true)->where('is_claimed', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target <= 0) {
            return 100;
        }

        return min(100, ($this->progress / $this->target) * 100);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->target - $this->progress);
    }

    public function canClaim(): bool
    {
        return $this->is_completed && !$this->is_claimed;
    }

    public function markCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'unlocked_at' => now(),
            'progress' => $this->target,
        ]);
    }

    public function claim(): bool
    {
        if (!$this->canClaim()) {
            return false;
        }

        $this->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);

        return true;
    }

    public function incrementProgress(int $amount = 1): void
    {
        $newProgress = min($this->target, $this->progress + $amount);
        
        $this->update(['progress' => $newProgress]);

        if ($newProgress >= $this->target && !$this->is_completed) {
            $this->markCompleted();
        }
    }
}
