<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIConversation extends Model
{
    protected $fillable = [
        'user_id',
        'context',
        'messages',
        'tokens_used',
        'cost',
        'model',
    ];

    protected $casts = [
        'messages' => 'array',
        'tokens_used' => 'integer',
        'cost' => 'decimal:4',
    ];

    /**
     * Get the user that owns the conversation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total AI cost for a user
     */
    public static function getTotalCostForUser(int $userId): float
    {
        return static::where('user_id', $userId)->sum('cost');
    }

    /**
     * Get total tokens used by a user
     */
    public static function getTotalTokensForUser(int $userId): int
    {
        return static::where('user_id', $userId)->sum('tokens_used');
    }

    /**
     * Get conversations by context
     */
    public static function getByContext(string $context, int $limit = 10)
    {
        return static::where('context', $context)
            ->latest()
            ->limit($limit)
            ->get();
    }
}

