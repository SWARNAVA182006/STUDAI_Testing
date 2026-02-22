<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'leaderboard_id',
        'user_id',
        'rank',
        'score',
        'previous_rank',
        'rank_change',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'score' => 'integer',
            'previous_rank' => 'integer',
            'rank_change' => 'integer',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeTopRanked($query, int $limit = 100)
    {
        return $query->orderBy('rank')->limit($limit);
    }

    public function scopeImproved($query)
    {
        return $query->where('rank_change', '>', 0);
    }

    public function scopeDeclined($query)
    {
        return $query->where('rank_change', '<', 0);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getRankChangeStatusAttribute(): string
    {
        if ($this->rank_change > 0) {
            return 'up';
        } elseif ($this->rank_change < 0) {
            return 'down';
        }

        return 'same';
    }

    public function getRankChangeIconAttribute(): string
    {
        return match ($this->rank_change_status) {
            'up' => '↑',
            'down' => '↓',
            default => '−',
        };
    }

    public function getFormattedRankAttribute(): string
    {
        return match ($this->rank) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => '#' . $this->rank,
        };
    }

    public function updateRank(int $newRank, int $newScore): void
    {
        $this->update([
            'previous_rank' => $this->rank,
            'rank' => $newRank,
            'score' => $newScore,
            'rank_change' => $this->rank - $newRank, // Positive = moved up
        ]);
    }
}
