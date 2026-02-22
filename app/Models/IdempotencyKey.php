<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IdempotencyKey Model
 *
 * Stores idempotency key data for preventing duplicate request processing.
 *
 * @property int $id
 * @property string $key
 * @property int|null $user_id
 * @property string $endpoint
 * @property string $method
 * @property int $response_status
 * @property string $response_body
 * @property array|null $response_headers
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User|null $user
 */
class IdempotencyKey extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'user_id',
        'endpoint',
        'method',
        'response_status',
        'response_body',
        'response_headers',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'response_headers' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns this idempotency key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find a valid (non-expired) idempotency record.
     */
    public static function findValid(string $key, ?int $userId, string $endpoint): ?self
    {
        return static::query()
            ->where('key', $key)
            ->where('user_id', $userId)
            ->where('endpoint', $endpoint)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Check if the idempotency key has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Scope to get expired records.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Clean up expired idempotency keys.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}
