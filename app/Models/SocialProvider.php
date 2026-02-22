<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class SocialProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'client_id',
        'client_secret',
        'redirect_url',
        'scopes',
        'additional_config',
        'icon',
        'color',
        'is_enabled',
        'allow_login',
        'allow_register',
        'sort_order',
    ];

    protected $casts = [
        'scopes' => 'array',
        'additional_config' => 'array',
        'is_enabled' => 'boolean',
        'allow_login' => 'boolean',
        'allow_register' => 'boolean',
    ];

    /**
     * Encrypt client_secret when setting.
     */
    public function setClientSecretAttribute(?string $value): void
    {
        if ($value !== null && $value !== '') {
            // Only encrypt if it's not already encrypted
            try {
                Crypt::decryptString($value);
                // If we get here, it's already encrypted
                $this->attributes['client_secret'] = $value;
            } catch (\Exception $e) {
                // Not encrypted, so encrypt it
                $this->attributes['client_secret'] = Crypt::encryptString($value);
            }
        } else {
            $this->attributes['client_secret'] = null;
        }
    }

    /**
     * Decrypt client_secret when getting.
     */
    public function getClientSecretAttribute(?string $value): ?string
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
     * Get the decrypted client secret for use with Socialite.
     */
    public function getDecryptedClientSecret(): ?string
    {
        return $this->client_secret;
    }

    /**
     * Get linked social accounts for this provider.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'provider', 'slug');
    }

    /**
     * Get auth logs for this provider.
     */
    public function authLogs(): HasMany
    {
        return $this->hasMany(SocialAuthLog::class, 'provider', 'slug');
    }

    /**
     * Check if provider is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->client_id) && !empty($this->attributes['client_secret']);
    }

    /**
     * Check if provider can be used for authentication.
     */
    public function canAuthenticate(): bool
    {
        return $this->is_enabled && $this->isConfigured();
    }

    /**
     * Get the redirect URL, using default if not set.
     */
    public function getRedirectUrl(): string
    {
        if (!empty($this->redirect_url)) {
            return $this->redirect_url;
        }

        return url("/auth/social/{$this->slug}/callback");
    }

    /**
     * Get Socialite config array.
     */
    public function getSocialiteConfig(): array
    {
        $config = [
            'client_id' => $this->client_id,
            'client_secret' => $this->getDecryptedClientSecret(),
            'redirect' => $this->getRedirectUrl(),
        ];

        // Add provider-specific config
        if (!empty($this->additional_config)) {
            $config = array_merge($config, $this->additional_config);
        }

        return $config;
    }

    /**
     * Get scopes array.
     */
    public function getScopesArray(): array
    {
        return $this->scopes ?? [];
    }

    /**
     * Scope: Only enabled providers.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Configured providers (have credentials).
     */
    public function scopeConfigured($query)
    {
        return $query->whereNotNull('client_id')
            ->whereNotNull('client_secret');
    }

    /**
     * Scope: Active providers (enabled and configured).
     */
    public function scopeActive($query)
    {
        return $query->enabled()->configured();
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get provider icon SVG.
     */
    public function getIconSvg(): string
    {
        return match ($this->slug) {
            'google' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
            'apple' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>',
            'microsoft' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="#F25022" d="M1 1h10v10H1z"/><path fill="#00A4EF" d="M1 13h10v10H1z"/><path fill="#7FBA00" d="M13 1h10v10H13z"/><path fill="#FFB900" d="M13 13h10v10H13z"/></svg>',
            'facebook' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'twitter' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'github' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
            default => '<svg viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
        };
    }
}
