<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id',
        'user_id',
        'content',
        'media',
        'type',
        'likes_count',
        'comments_count',
        'is_pinned',
        'is_approved',
        'metadata',
    ];

    protected $casts = [
        'media' => 'array',
        'metadata' => 'array',
        'is_pinned' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // Post types
    public const TYPE_DISCUSSION = 'discussion';
    public const TYPE_QUESTION = 'question';
    public const TYPE_POLL = 'poll';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_JOB = 'job';
    public const TYPE_EVENT = 'event';

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    public function approve(): bool
    {
        return $this->update(['is_approved' => true]);
    }

    protected static function booted(): void
    {
        static::created(function (GroupPost $post) {
            if ($post->is_approved) {
                $post->group->increment('post_count');
            }
        });

        static::deleted(function (GroupPost $post) {
            if ($post->is_approved) {
                $post->group->decrement('post_count');
            }
        });
    }
}
