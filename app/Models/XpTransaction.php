<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'xp_earned',
        'level_before',
        'level_after',
        'leveled_up',
        'source',
        'source_type',
        'source_id',
        'multiplier_applied',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'xp_earned' => 'integer',
            'level_before' => 'integer',
            'level_after' => 'integer',
            'leveled_up' => 'boolean',
            'source_id' => 'integer',
            'multiplier_applied' => 'decimal:2',
        ];
    }

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

    public function scopeLevelUps($query)
    {
        return $query->where('leveled_up', true);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeThisWeek($query)
    {
        return $query->where('created_at', '>=', now()->startOfWeek());
    }

    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getFormattedXpAttribute(): string
    {
        return '+' . number_format($this->xp_earned) . ' XP';
    }

    public function getLevelChangeAttribute(): string
    {
        if (!$this->leveled_up) {
            return '';
        }

        return "Level {$this->level_before} → {$this->level_after}";
    }

    public function hadMultiplier(): bool
    {
        return $this->multiplier_applied > 1.0;
    }
}
