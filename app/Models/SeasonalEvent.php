<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SeasonalEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'theme',
        'starts_at',
        'ends_at',
        'xp_multiplier',
        'points_multiplier',
        'exclusive_badges',
        'exclusive_challenges',
        'exclusive_rewards',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'xp_multiplier' => 'decimal:2',
            'points_multiplier' => 'decimal:2',
            'exclusive_badges' => 'array',
            'exclusive_challenges' => 'array',
            'exclusive_rewards' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function participations(): HasMany
    {
        return $this->hasMany(UserEventParticipation::class, 'event_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->active()
            ->where('starts_at', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('ends_at', '<', now());
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function isRunning(): bool
    {
        return $this->is_active && 
            $this->starts_at->isPast() && 
            $this->ends_at->isFuture();
    }

    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    public function getRemainingTimeAttribute(): string
    {
        if ($this->hasEnded()) {
            return 'Ended';
        }

        if ($this->isUpcoming()) {
            return 'Starts ' . $this->starts_at->diffForHumans();
        }

        return 'Ends ' . $this->ends_at->diffForHumans();
    }

    public function getDurationAttribute(): string
    {
        return $this->starts_at->diffForHumans($this->ends_at, ['parts' => 2]);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->isUpcoming()) {
            return 0;
        }

        if ($this->hasEnded()) {
            return 100;
        }

        $total = $this->starts_at->diffInSeconds($this->ends_at);
        $elapsed = $this->starts_at->diffInSeconds(now());

        return min(100, ($elapsed / max(1, $total)) * 100);
    }

    public function getParticipantCount(): int
    {
        return $this->participations()->count();
    }

    public function getUserParticipation(int $userId): ?UserEventParticipation
    {
        return $this->participations()
            ->where('user_id', $userId)
            ->first();
    }

    public function hasUserParticipated(int $userId): bool
    {
        return $this->participations()
            ->where('user_id', $userId)
            ->exists();
    }

    public static function getCurrentEvent(): ?self
    {
        return self::current()->first();
    }
}
