<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'level',
        'category',
        'requirements',
        'requires_assessment',
        'requires_verification',
        'is_active',
    ];

    protected $casts = [
        'requirements' => 'array',
        'requires_assessment' => 'boolean',
        'requires_verification' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserSkillBadge::class, 'badge_id');
    }

    public function verifiedUsers(): HasMany
    {
        return $this->hasMany(UserSkillBadge::class, 'badge_id')
            ->where('status', 'verified');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    // Helpers
    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert',
            default => ucfirst($this->level),
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'technical' => 'Technical Skill',
            'soft_skill' => 'Soft Skill',
            'certification' => 'Certification',
            'platform' => 'Platform Badge',
            'achievement' => 'Achievement',
            default => ucfirst($this->category),
        };
    }
}
