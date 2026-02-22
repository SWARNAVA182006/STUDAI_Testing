<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEventParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'event_points',
        'event_xp',
        'tasks_completed',
        'rewards_claimed',
    ];

    protected function casts(): array
    {
        return [
            'event_points' => 'integer',
            'event_xp' => 'integer',
            'tasks_completed' => 'integer',
            'rewards_claimed' => 'array',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(SeasonalEvent::class, 'event_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeTopParticipants($query, int $limit = 100)
    {
        return $query->orderByDesc('event_points')
            ->limit($limit);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function addPoints(int $points): void
    {
        $this->increment('event_points', $points);
    }

    public function addXp(int $xp): void
    {
        $this->increment('event_xp', $xp);
    }

    public function completeTask(): void
    {
        $this->increment('tasks_completed');
    }

    public function claimReward(string $rewardId): void
    {
        $claimed = $this->rewards_claimed ?? [];
        $claimed[] = $rewardId;
        $this->update(['rewards_claimed' => array_unique($claimed)]);
    }

    public function hasClaimedReward(string $rewardId): bool
    {
        return in_array($rewardId, $this->rewards_claimed ?? []);
    }
}
