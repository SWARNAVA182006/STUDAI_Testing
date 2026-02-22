<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'media',
        'type',
        'visibility',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'original_post_id',
        'metadata',
        'is_pinned',
    ];

    protected $casts = [
        'media' => 'array',
        'metadata' => 'array',
        'is_pinned' => 'boolean',
    ];

    // Post types
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_JOB_UPDATE = 'job_update';
    public const TYPE_MILESTONE = 'milestone';
    public const TYPE_SKILL_ACHIEVEMENT = 'skill_achievement';

    // Visibility options
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_CONNECTIONS = 'connections';
    public const VISIBILITY_PRIVATE = 'private';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(UserPost::class, 'original_post_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(UserPost::class, 'original_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function rootComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'post_hashtag', 'post_id', 'hashtag_id');
    }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function getUserReaction(?User $user): ?string
    {
        if (!$user) {
            return null;
        }
        
        return $this->likes()->where('user_id', $user->id)->value('reaction_type');
    }

    public function like(User $user, string $reactionType = 'like'): void
    {
        $this->likes()->updateOrCreate(
            ['user_id' => $user->id],
            ['reaction_type' => $reactionType]
        );
        
        $this->increment('likes_count');
    }

    public function unlike(User $user): void
    {
        $deleted = $this->likes()->where('user_id', $user->id)->delete();
        
        if ($deleted > 0) {
            $this->decrement('likes_count');
        }
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function isShared(): bool
    {
        return $this->original_post_id !== null;
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // User's own posts
            $q->where('user_id', $user->id)
                // Or public posts
                ->orWhere('visibility', self::VISIBILITY_PUBLIC)
                // Or posts from connections
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('visibility', self::VISIBILITY_CONNECTIONS)
                        ->whereIn('user_id', $user->connectionIds());
                });
        });
    }

    /**
     * Extract and save hashtags from content
     */
    public function extractHashtags(): void
    {
        preg_match_all('/#(\w+)/', $this->content, $matches);
        
        if (empty($matches[1])) {
            return;
        }

        $hashtagIds = [];
        foreach ($matches[1] as $tag) {
            $hashtag = Hashtag::firstOrCreate(['name' => strtolower($tag)]);
            $hashtagIds[] = $hashtag->id;
        }

        $this->hashtags()->sync($hashtagIds);
        
        // Update hashtag counts
        Hashtag::whereIn('id', $hashtagIds)->increment('posts_count');
    }
}
