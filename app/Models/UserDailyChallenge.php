<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'challenge_date',
        'progress',
        'target',
        'is_completed',
        'is_claimed',
        'completed_at',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'challenge_date' => 'date',
            'progress' => 'integer',
            'target' => 'integer',
            'is_completed' => 'boolean',
            'is_claimed' => 'boolean',
            'completed_at' => 'datetime',
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

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(DailyChallenge::class, 'challenge_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForToday($query)
    {
        return $query->where('challenge_date', today());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeUnclaimed($query)
    {
        return $query->where('is_completed', true)->where('is_claimed', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
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
        return $this->is_completed && !$this->is_claimed && $this->challenge_date->isToday();
    }

    public function incrementProgress(int $amount = 1): void
    {
        $newProgress = min($this->target, $this->progress + $amount);
        
        $this->update(['progress' => $newProgress]);

        if ($newProgress >= $this->target && !$this->is_completed) {
            $this->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }
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
}
