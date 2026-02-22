<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * ATS Connection - An employer's connection to their ATS system.
 * 
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property int $ats_provider_id
 * @property int $connected_by
 * @property string|null $connection_name
 * @property string|null $api_key
 * @property string|null $api_secret
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Carbon\Carbon|null $token_expires_at
 * @property string|null $webhook_secret
 * @property string|null $webhook_url
 * @property array|null $credentials
 * @property array|null $settings
 * @property array|null $field_mappings
 * @property string $sync_direction
 * @property bool $auto_sync_jobs
 * @property bool $auto_sync_candidates
 * @property bool $auto_sync_applications
 * @property int $sync_interval_minutes
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string $status
 * @property string|null $last_error
 */
class AtsConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'ats_provider_id',
        'connected_by',
        'connection_name',
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'webhook_secret',
        'webhook_url',
        'credentials',
        'settings',
        'field_mappings',
        'sync_direction',
        'auto_sync_jobs',
        'auto_sync_candidates',
        'auto_sync_applications',
        'sync_interval_minutes',
        'last_synced_at',
        'status',
        'last_error',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'field_mappings' => 'array',
        'auto_sync_jobs' => 'boolean',
        'auto_sync_candidates' => 'boolean',
        'auto_sync_applications' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'access_token',
        'refresh_token',
        'webhook_secret',
        'credentials',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $connection) {
            if (empty($connection->uuid)) {
                $connection->uuid = (string) Str::uuid();
            }
            if (empty($connection->webhook_secret)) {
                $connection->webhook_secret = Str::random(64);
            }
        });
    }

    /**
     * Get the ATS provider.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AtsProvider::class, 'ats_provider_id');
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the connection.
     */
    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    /**
     * Get synced jobs.
     */
    public function syncedJobs(): HasMany
    {
        return $this->hasMany(AtsSyncedJob::class);
    }

    /**
     * Get synced candidates.
     */
    public function syncedCandidates(): HasMany
    {
        return $this->hasMany(AtsSyncedCandidate::class);
    }

    /**
     * Get synced applications.
     */
    public function syncedApplications(): HasMany
    {
        return $this->hasMany(AtsSyncedApplication::class);
    }

    /**
     * Get sync logs.
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(AtsSyncLog::class);
    }

    /**
     * Get webhooks.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(AtsWebhook::class);
    }

    /**
     * Check if token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Check if connection is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if sync is due.
     */
    public function isSyncDue(): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }
        return $this->last_synced_at->addMinutes($this->sync_interval_minutes)->isPast();
    }

    /**
     * Get the webhook endpoint URL.
     */
    public function getWebhookEndpointAttribute(): string
    {
        return route('api.ats.webhook', ['uuid' => $this->uuid]);
    }

    /**
     * Scope active connections.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope connections due for sync.
     */
    public function scopeDueForSync($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhereRaw('last_synced_at < DATE_SUB(NOW(), INTERVAL sync_interval_minutes MINUTE)');
            });
    }

    /**
     * Get decrypted API key.
     */
    public function getDecryptedApiKey(): ?string
    {
        return $this->api_key ? decrypt($this->api_key) : null;
    }

    /**
     * Set encrypted API key.
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? encrypt($value) : null;
    }

    /**
     * Set encrypted access token.
     */
    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? encrypt($value) : null;
    }

    /**
     * Get decrypted access token.
     */
    public function getDecryptedAccessToken(): ?string
    {
        return $this->access_token ? decrypt($this->access_token) : null;
    }

    /**
     * Set encrypted refresh token.
     */
    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value ? encrypt($value) : null;
    }

    /**
     * Get decrypted refresh token.
     */
    public function getDecryptedRefreshToken(): ?string
    {
        return $this->refresh_token ? decrypt($this->refresh_token) : null;
    }
}
