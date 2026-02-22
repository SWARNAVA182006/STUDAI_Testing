<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'points',
        'balance_after',
        'source',
        'source_type',
        'source_id',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_after' => 'integer',
            'source_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const TYPES = [
        'earned' => 'Points Earned',
        'spent' => 'Points Spent',
        'bonus' => 'Bonus Points',
        'refund' => 'Points Refunded',
        'expired' => 'Points Expired',
    ];

    public const SOURCES = [
        'achievement' => 'Achievement Unlocked',
        'challenge' => 'Daily Challenge',
        'reward' => 'Reward Redemption',
        'streak' => 'Streak Bonus',
        'referral' => 'Referral Bonus',
        'admin' => 'Admin Adjustment',
        'level_up' => 'Level Up Bonus',
        'event' => 'Seasonal Event',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

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

    public function scopeEarned($query)
    {
        return $query->where('points', '>', 0);
    }

    public function scopeSpent($query)
    {
        return $query->where('points', '<', 0);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getSourceNameAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getFormattedPointsAttribute(): string
    {
        $prefix = $this->points >= 0 ? '+' : '';
        return $prefix . number_format($this->points);
    }

    public function isEarning(): bool
    {
        return $this->points > 0;
    }

    public function isSpending(): bool
    {
        return $this->points < 0;
    }
}
