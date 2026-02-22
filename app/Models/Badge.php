<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'category',
        'tier',
        'criteria',
        'is_active',
    ];
    
    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get users who have earned this badge
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'badge_user')
            ->withPivot(['certificate_id', 'earned_at', 'is_visible', 'display_order'])
            ->withTimestamps();
    }
    
    /**
     * Scope: Active badges only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
    
    /**
     * Scope: Filter by tier
     */
    public function scopeTier($query, $tier)
    {
        return $query->where('tier', $tier);
    }
    
    /**
     * Get tier label with icon
     */
    public function getTierLabelAttribute(): array
    {
        return match($this->tier) {
            'bronze' => ['label' => 'Bronze', 'icon' => '🥉', 'color' => '#CD7F32'],
            'silver' => ['label' => 'Silver', 'icon' => '🥈', 'color' => '#C0C0C0'],
            'gold' => ['label' => 'Gold', 'icon' => '🥇', 'color' => '#FFD700'],
            'platinum' => ['label' => 'Platinum', 'icon' => '💎', 'color' => '#E5E4E2'],
            default => ['label' => 'Unknown', 'icon' => '⭐', 'color' => '#808080'],
        };
    }
    
    /**
     * Check if user has earned this badge
     */
    public function isEarnedBy(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }
    
    /**
     * Award badge to user
     */
    public function awardTo(User $user, ?Certificate $certificate = null): void
    {
        if ($this->isEarnedBy($user)) return; // Already earned
        
        $this->users()->attach($user->id, [
            'certificate_id' => $certificate?->id,
            'earned_at' => now(),
            'is_visible' => true,
            'display_order' => $user->badges()->count(),
        ]);
    }
}
