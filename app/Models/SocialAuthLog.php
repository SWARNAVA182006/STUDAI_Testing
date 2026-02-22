<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAuthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'action',
        'status',
        'ip_address',
        'user_agent',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user associated with this log entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider configuration.
     */
    public function providerConfig(): ?SocialProvider
    {
        return SocialProvider::where('slug', $this->provider)->first();
    }

    /**
     * Log a successful authentication.
     */
    public static function logSuccess(
        string $provider,
        string $action,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'provider' => $provider,
            'action' => $action,
            'status' => 'success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a failed authentication.
     */
    public static function logFailure(
        string $provider,
        string $action,
        string $errorMessage,
        ?int $userId = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'provider' => $provider,
            'action' => $action,
            'status' => 'failure',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope: By provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: By status.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: By status.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    /**
     * Scope: Recent logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
