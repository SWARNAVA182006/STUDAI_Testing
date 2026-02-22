<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ATS Provider - Represents an available ATS system.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $display_name
 * @property string|null $description
 * @property string|null $logo
 * @property string|null $website_url
 * @property string|null $documentation_url
 * @property string $auth_type
 * @property array|null $required_credentials
 * @property array|null $supported_features
 * @property array|null $webhook_events
 * @property array|null $rate_limits
 * @property bool $is_active
 * @property int $sort_order
 */
class AtsProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'logo',
        'website_url',
        'documentation_url',
        'auth_type',
        'required_credentials',
        'supported_features',
        'webhook_events',
        'rate_limits',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'required_credentials' => 'array',
        'supported_features' => 'array',
        'webhook_events' => 'array',
        'rate_limits' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all connections for this provider.
     */
    public function connections(): HasMany
    {
        return $this->hasMany(AtsConnection::class);
    }

    /**
     * Get default field mappings for this provider.
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(AtsFieldMapping::class)->whereNull('company_id');
    }

    /**
     * Check if provider supports a feature.
     */
    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->supported_features ?? []);
    }

    /**
     * Get active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get providers with OAuth authentication.
     */
    public function scopeOAuth($query)
    {
        return $query->where('auth_type', 'oauth2');
    }

    /**
     * Get providers with API key authentication.
     */
    public function scopeApiKey($query)
    {
        return $query->where('auth_type', 'api_key');
    }
}
