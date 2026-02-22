<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'reaction_type',
    ];

    // Reaction types (LinkedIn-style)
    public const REACTION_LIKE = 'like';
    public const REACTION_CELEBRATE = 'celebrate';
    public const REACTION_SUPPORT = 'support';
    public const REACTION_LOVE = 'love';
    public const REACTION_INSIGHTFUL = 'insightful';
    public const REACTION_CURIOUS = 'curious';

    public static function getReactionEmojis(): array
    {
        return [
            self::REACTION_LIKE => '👍',
            self::REACTION_CELEBRATE => '👏',
            self::REACTION_SUPPORT => '💪',
            self::REACTION_LOVE => '❤️',
            self::REACTION_INSIGHTFUL => '💡',
            self::REACTION_CURIOUS => '🤔',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(UserPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEmojiAttribute(): string
    {
        return self::getReactionEmojis()[$this->reaction_type] ?? '👍';
    }
}
