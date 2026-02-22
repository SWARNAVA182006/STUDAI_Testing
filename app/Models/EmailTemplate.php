<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'user_id',
        'company_id',
        'name',
        'slug',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'default_values',
        'type',
        'tone',
        'is_default',
        'is_public',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'variables' => 'array',
        'default_values' => 'array',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EmailTemplateCategory::class, 'category_id');
    }

    /**
     * Get the user who created the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get template versions.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class, 'template_id');
    }

    /**
     * Get sent emails using this template.
     */
    public function sends(): HasMany
    {
        return $this->hasMany(EmailSend::class, 'template_id');
    }

    /**
     * Get analytics for this template.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(EmailTemplateAnalytics::class, 'template_id');
    }

    /**
     * Get AI customizations for this template.
     */
    public function aiCustomizations(): HasMany
    {
        return $this->hasMany(EmailAiCustomization::class, 'template_id');
    }

    /**
     * Scope active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('type', 'system');
    }

    /**
     * Scope public templates.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope templates for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_public', true)
                ->orWhere('type', 'system');
        });
    }

    /**
     * Parse template with variables.
     */
    public function parse(array $data = []): array
    {
        $subject = $this->subject;
        $bodyHtml = $this->body_html;
        $bodyText = $this->body_text ?? strip_tags($this->body_html);

        // Merge default values with provided data
        $variables = array_merge($this->default_values ?? [], $data);

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, (string) $value, $subject);
            $bodyHtml = str_replace($placeholder, (string) $value, $bodyHtml);
            $bodyText = str_replace($placeholder, (string) $value, $bodyText);
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get available variables as formatted list.
     */
    public function getVariablesList(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Calculate open rate.
     */
    public function getOpenRate(): float
    {
        $totalSends = $this->sends()->whereIn('status', ['delivered', 'opened', 'clicked'])->count();
        if ($totalSends === 0) {
            return 0;
        }

        $opens = $this->sends()->whereIn('status', ['opened', 'clicked'])->count();
        return round(($opens / $totalSends) * 100, 2);
    }

    /**
     * Calculate click rate.
     */
    public function getClickRate(): float
    {
        $opens = $this->sends()->whereIn('status', ['opened', 'clicked'])->count();
        if ($opens === 0) {
            return 0;
        }

        $clicks = $this->sends()->where('status', 'clicked')->count();
        return round(($clicks / $opens) * 100, 2);
    }
}
