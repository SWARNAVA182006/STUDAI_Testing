<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackgroundCheckPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'provider',
        'provider_package_id',
        'checks_included',
        'price',
        'estimated_days',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'checks_included' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function backgroundChecks(): HasMany
    {
        return $this->hasMany(BackgroundCheck::class, 'package_id');
    }

    // Helpers
    public function getProviderNameAttribute(): string
    {
        return match($this->provider) {
            'checkr' => 'Checkr',
            'sterling' => 'Sterling',
            'goodhire' => 'GoodHire',
            default => ucfirst($this->provider),
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->price ? '$' . number_format($this->price, 2) : 'Contact for pricing';
    }

    public function getChecksListAttribute(): string
    {
        $checkLabels = [
            'criminal' => 'Criminal Records',
            'employment' => 'Employment Verification',
            'education' => 'Education Verification',
            'credit' => 'Credit Check',
            'drug' => 'Drug Screening',
            'mvr' => 'Motor Vehicle Records',
            'identity' => 'Identity Verification',
            'ssn_trace' => 'SSN Trace',
            'sex_offender' => 'Sex Offender Registry',
            'global_watchlist' => 'Global Watchlist',
        ];

        $labels = array_map(
            fn($check) => $checkLabels[$check] ?? ucfirst(str_replace('_', ' ', $check)),
            $this->checks_included ?? []
        );

        return implode(', ', $labels);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id'); // Global packages
            if ($companyId) {
                $q->orWhere('company_id', $companyId); // Company-specific
            }
        });
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }
}
