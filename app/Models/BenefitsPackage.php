<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitsPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'benefits',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(OfferLetter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getTotalValueAttribute(): float
    {
        $benefits = $this->benefits ?? [];
        $total = 0;

        foreach ($benefits as $benefit) {
            if (isset($benefit['annual_value'])) {
                $total += (float) $benefit['annual_value'];
            }
        }

        return $total;
    }

    public function getBenefitCategories(): array
    {
        $benefits = $this->benefits ?? [];
        $categories = [];

        foreach ($benefits as $benefit) {
            $category = $benefit['category'] ?? 'Other';
            if (!in_array($category, $categories)) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public function getFormattedBenefits(): array
    {
        $benefits = $this->benefits ?? [];
        $grouped = [];

        foreach ($benefits as $benefit) {
            $category = $benefit['category'] ?? 'Other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $benefit;
        }

        return $grouped;
    }
}
