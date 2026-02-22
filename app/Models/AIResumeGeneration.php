<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIResumeGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resume_id',
        'generation_type',
        'input_data',
        'prompt_used',
        'ai_response',
        'tokens_used',
        'cost',
        'generation_time',
        'model_used',
        'was_accepted',
        'feedback',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'cost' => 'float',
        'generation_time' => 'float',
        'was_accepted' => 'boolean',
        'feedback' => 'array',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    /**
     * Scopes
     */
    public function scopeGenerationType($query, string $type)
    {
        return $query->where('generation_type', $type);
    }

    public function scopeAccepted($query)
    {
        return $query->where('was_accepted', true);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get total cost for user
     */
    public static function getTotalCostForUser(int $userId, int $days = 30): float
    {
        return static::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('cost');
    }

    /**
     * Get total tokens for user
     */
    public static function getTotalTokensForUser(int $userId, int $days = 30): int
    {
        return static::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('tokens_used');
    }
}
