<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'badge_id',
        'earned_at',
        'is_featured',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
            'is_featured' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(GamificationBadge::class, 'badge_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderByDesc('earned_at');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function toggleFeatured(): void
    {
        $this->update(['is_featured' => !$this->is_featured]);
    }

    public function setDisplayOrder(int $order): void
    {
        $this->update(['display_order' => $order]);
    }
}
