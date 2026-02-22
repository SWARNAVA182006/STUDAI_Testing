<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'rating',
        'name',
        'position',
        'company',
        'avatar',
        'verified',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'integer',
        'display_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }
}
