<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'parent_id',
        'likes_count',
        'replies_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'replies_count' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(UserPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class, 'comment_id');
    }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function like(User $user): void
    {
        $this->likes()->firstOrCreate(['user_id' => $user->id]);
        $this->increment('likes_count');
    }

    public function unlike(User $user): void
    {
        $deleted = $this->likes()->where('user_id', $user->id)->delete();
        
        if ($deleted > 0) {
            $this->decrement('likes_count');
        }
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    protected static function booted(): void
    {
        static::created(function (PostComment $comment) {
            // Increment post comment count
            $comment->post->increment('comments_count');
            
            // Increment parent reply count if this is a reply
            if ($comment->parent_id) {
                $comment->parent->increment('replies_count');
            }
        });

        static::deleted(function (PostComment $comment) {
            // Decrement post comment count
            $comment->post->decrement('comments_count');
            
            // Decrement parent reply count if this is a reply
            if ($comment->parent_id) {
                $comment->parent->decrement('replies_count');
            }
        });
    }
}
