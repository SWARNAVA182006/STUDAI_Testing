<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'badge_id',
        'status',
        'verification_evidence',
        'verified_by',
        'assessment_score',
        'earned_at',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(SkillBadge::class, 'badge_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // Helpers
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function verify(string $verifiedBy = 'system'): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'earned_at' => now(),
        ]);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }
}
