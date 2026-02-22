<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentPoolCandidate extends Model
{
    protected $table = 'talent_pool';
    
    protected $fillable = [
        'company_id',
        'user_id',
        'added_by',
        'source',
        'tags',
        'notes',
        'rating',
        'last_contacted_at',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'last_contacted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    public function scopeRecentlyContacted($query)
    {
        return $query->whereNotNull('last_contacted_at')
            ->orderByDesc('last_contacted_at');
    }
}
