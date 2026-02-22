<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * AI Prompt Model
 *
 * Manages versioned AI prompts for all platform AI features.
 * Supports version control, A/B testing, and performance tracking.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property int $version
 * @property string|null $system_prompt
 * @property string $template
 * @property string|null $description
 * @property bool $is_active
 * @property array|null $variables
 * @property array|null $metadata
 * @property string|null $model_hint
 * @property int|null $max_tokens
 * @property float|null $temperature
 * @property int $usage_count
 * @property float|null $avg_latency_ms
 * @property float|null $success_rate
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AIPrompt extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'ai_prompts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'category',
        'version',
        'system_prompt',
        'template',
        'description',
        'is_active',
        'variables',
        'metadata',
        'model_hint',
        'max_tokens',
        'temperature',
        'usage_count',
        'avg_latency_ms',
        'success_rate',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
        'metadata' => 'array',
        'version' => 'integer',
        'max_tokens' => 'integer',
        'temperature' => 'float',
        'usage_count' => 'integer',
        'avg_latency_ms' => 'float',
        'success_rate' => 'float',
    ];

    /**
     * Prompt categories.
     */
    public const CATEGORIES = [
        'resume' => 'Resume Analysis & Generation',
        'interview' => 'Interview Preparation',
        'job_matching' => 'Job Matching & Search',
        'cover_letter' => 'Cover Letter Generation',
        'skill_analysis' => 'Skill Gap Analysis',
        'negotiation' => 'Salary Negotiation',
        'career_advice' => 'Career Coaching',
        'scout' => 'S.C.O.U.T. Employer Features',
        'general' => 'General Purpose',
    ];

    /**
     * Cache prefix for prompts.
     */
    protected const CACHE_PREFIX = 'ai_prompt:';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the user who created this prompt.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this prompt.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active prompts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get prompts by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get the latest version of a prompt.
     */
    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    /**
     * Get the active prompt by name (latest active version).
     */
    public static function getActive(string $name): ?self
    {
        $cacheKey = self::CACHE_PREFIX . 'active:' . $name;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name) {
            return static::where('name', $name)
                ->active()
                ->latestVersion()
                ->first();
        });
    }

    /**
     * Get a specific version of a prompt.
     */
    public static function getVersion(string $name, int $version): ?self
    {
        $cacheKey = self::CACHE_PREFIX . "version:{$name}:{$version}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $version) {
            return static::where('name', $name)
                ->where('version', $version)
                ->first();
        });
    }

    /**
     * Get all versions of a prompt.
     */
    public static function getVersions(string $name): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('name', $name)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Create a new version of this prompt.
     */
    public function createNewVersion(array $changes = []): self
    {
        $nextVersion = static::where('name', $this->name)->max('version') + 1;

        $newPrompt = $this->replicate();
        $newPrompt->version = $nextVersion;
        $newPrompt->usage_count = 0;
        $newPrompt->avg_latency_ms = null;
        $newPrompt->success_rate = null;
        $newPrompt->created_at = now();
        $newPrompt->updated_at = now();

        foreach ($changes as $key => $value) {
            $newPrompt->{$key} = $value;
        }

        $newPrompt->save();

        // Clear cache for this prompt
        self::clearCache($this->name);

        return $newPrompt;
    }

    /**
     * Set this version as the active version.
     */
    public function setAsActive(): void
    {
        // Deactivate other versions
        static::where('name', $this->name)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        // Activate this version
        $this->update(['is_active' => true]);

        // Clear cache
        self::clearCache($this->name);
    }

    /**
     * Render the prompt template with variables.
     */
    public function render(array $variables = []): string
    {
        $rendered = $this->template;

        foreach ($variables as $key => $value) {
            $rendered = str_replace("{{$key}}", (string) $value, $rendered);
            $rendered = str_replace("{{ $key }}", (string) $value, $rendered);
        }

        return $rendered;
    }

    /**
     * Record usage of this prompt.
     */
    public function recordUsage(float $latencyMs, bool $success = true): void
    {
        $this->increment('usage_count');

        // Update rolling average latency
        if ($this->avg_latency_ms === null) {
            $this->avg_latency_ms = $latencyMs;
        } else {
            // Exponential moving average
            $alpha = 0.1;
            $this->avg_latency_ms = ($alpha * $latencyMs) + ((1 - $alpha) * $this->avg_latency_ms);
        }

        // Update success rate
        if ($this->success_rate === null) {
            $this->success_rate = $success ? 100.0 : 0.0;
        } else {
            $alpha = 0.05;
            $successValue = $success ? 100.0 : 0.0;
            $this->success_rate = ($alpha * $successValue) + ((1 - $alpha) * $this->success_rate);
        }

        $this->saveQuietly();
    }

    /**
     * Clear cache for a prompt.
     */
    public static function clearCache(string $name): void
    {
        Cache::forget(self::CACHE_PREFIX . 'active:' . $name);

        // Also clear version-specific caches
        $versions = static::where('name', $name)->pluck('version');
        foreach ($versions as $version) {
            Cache::forget(self::CACHE_PREFIX . "version:{$name}:{$version}");
        }
    }

    /**
     * Get available variable placeholders from the template.
     */
    public function getPlaceholders(): array
    {
        preg_match_all('/\{\{?\s*(\w+)\s*\}?\}/', $this->template, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate that all required variables are provided.
     */
    public function validateVariables(array $variables): array
    {
        $placeholders = $this->getPlaceholders();
        $missing = [];

        foreach ($placeholders as $placeholder) {
            if (!array_key_exists($placeholder, $variables)) {
                $missing[] = $placeholder;
            }
        }

        return $missing;
    }
}
