<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationReferralBonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'milestone',
        'points_awarded',
        'xp_awarded',
        'is_claimed',
        'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'points_awarded' => 'integer',
            'xp_awarded' => 'integer',
            'is_claimed' => 'boolean',
            'claimed_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const MILESTONES = [
        'signup' => ['name' => 'Sign Up', 'points' => 50, 'xp' => 100],
        'profile_complete' => ['name' => 'Profile Complete', 'points' => 100, 'xp' => 200],
        'first_application' => ['name' => 'First Application', 'points' => 75, 'xp' => 150],
        'first_interview' => ['name' => 'First Interview', 'points' => 150, 'xp' => 300],
        'hired' => ['name' => 'Got Hired', 'points' => 500, 'xp' => 1000],
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForReferrer($query, int $userId)
    {
        return $query->where('referrer_id', $userId);
    }

    public function scopeForReferred($query, int $userId)
    {
        return $query->where('referred_id', $userId);
    }

    public function scopeUnclaimed($query)
    {
        return $query->where('is_claimed', false);
    }

    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }

    public function scopeByMilestone($query, string $milestone)
    {
        return $query->where('milestone', $milestone);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    public function getMilestoneDataAttribute(): array
    {
        return self::MILESTONES[$this->milestone] ?? ['name' => $this->milestone, 'points' => 0, 'xp' => 0];
    }

    public function getMilestoneNameAttribute(): string
    {
        return $this->milestone_data['name'];
    }

    public function claim(): bool
    {
        if ($this->is_claimed) {
            return false;
        }

        $this->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);

        return true;
    }

    public static function hasReferralBonus(int $referrerId, int $referredId, string $milestone): bool
    {
        return self::where('referrer_id', $referrerId)
            ->where('referred_id', $referredId)
            ->where('milestone', $milestone)
            ->exists();
    }

    public static function createBonus(int $referrerId, int $referredId, string $milestone): ?self
    {
        if (self::hasReferralBonus($referrerId, $referredId, $milestone)) {
            return null;
        }

        $milestoneData = self::MILESTONES[$milestone] ?? null;
        if (!$milestoneData) {
            return null;
        }

        return self::create([
            'referrer_id' => $referrerId,
            'referred_id' => $referredId,
            'milestone' => $milestone,
            'points_awarded' => $milestoneData['points'],
            'xp_awarded' => $milestoneData['xp'],
        ]);
    }
}
