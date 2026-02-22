<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OfferLetterTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'content_html',
        'variables',
        'default_values',
        'type',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'default_values' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OfferLetterTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name) . '-' . Str::random(6);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function offerLetters(): HasMany
    {
        return $this->hasMany(OfferLetter::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('type', 'system');
    }

    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('type', 'system');
        });
    }

    public function parseVariables(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->content_html, $matches);
        return array_unique($matches[1] ?? []);
    }

    public function render(array $data): string
    {
        $content = $this->content_html;
        
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }
        
        return $content;
    }
}
