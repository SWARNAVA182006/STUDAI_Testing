<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CalendarConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_email',
        'calendar_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'calendars',
        'sync_settings',
        'is_primary',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'calendars' => 'array',
        'sync_settings' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns this connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get synced events for this connection.
     */
    public function syncedEvents(): HasMany
    {
        return $this->hasMany(CalendarSyncEvent::class, 'connection_id');
    }

    /**
     * Encrypt access_token when setting.
     */
    public function setAccessTokenAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['access_token'] = null;
        }
    }

    /**
     * Decrypt access_token when getting.
     */
    public function getAccessTokenAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Encrypt refresh_token when setting.
     */
    public function setRefreshTokenAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        } else {
            $this->attributes['refresh_token'] = null;
        }
    }

    /**
     * Decrypt refresh_token when getting.
     */
    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Check if token is expired.
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if token needs refresh (expires within 5 minutes).
     */
    public function needsTokenRefresh(): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Update tokens.
     */
    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null): void
    {
        $data = ['access_token' => $accessToken];

        if ($refreshToken !== null) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($expiresIn !== null) {
            $data['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        $this->update($data);
    }

    /**
     * Get provider display name.
     */
    public function getProviderNameAttribute(): string
    {
        return match ($this->provider) {
            'google' => 'Google Calendar',
            'outlook' => 'Outlook Calendar',
            'apple' => 'Apple Calendar',
            default => ucfirst($this->provider),
        };
    }

    /**
     * Scope: Active connections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
