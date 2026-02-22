<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'name',
        'nickname',
        'avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_data',
        'last_login_at',
    ];

    protected $casts = [
        'profile_data' => 'array',
        'token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns this social account.
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
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Update tokens from OAuth response.
     */
    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null): void
    {
        $data = [
            'access_token' => $accessToken,
            'last_login_at' => now(),
        ];

        if ($refreshToken !== null) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($expiresIn !== null) {
            $data['token_expires_at'] = now()->addSeconds($expiresIn);
        }

        $this->update($data);
    }

    /**
     * Scope: By provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: By provider user ID.
     */
    public function scopeForProviderUser($query, string $provider, string $providerUserId)
    {
        return $query->where('provider', $provider)
            ->where('provider_user_id', $providerUserId);
    }
}
