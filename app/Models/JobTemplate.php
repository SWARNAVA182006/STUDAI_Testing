<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'category',
        'title_template',
        'description_template',
        'requirements_template',
        'responsibilities_template',
        'default_skills',
        'is_public',
        'usage_count',
    ];

    protected $casts = [
        'default_skills' => 'array',
        'is_public' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get requirements as array
     */
    public function getRequirementsArray(): array
    {
        if (!$this->requirements_template) {
            return [];
        }
        return array_filter(explode("\n", $this->requirements_template));
    }

    /**
     * Get responsibilities as array
     */
    public function getResponsibilitiesArray(): array
    {
        if (!$this->responsibilities_template) {
            return [];
        }
        return array_filter(explode("\n", $this->responsibilities_template));
    }
}
