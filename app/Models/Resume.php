<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Resume extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'title',
        'slug',
        'is_default',
        'full_name',
        'email',
        'phone',
        'location',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'profile_photo',
        'professional_summary',
        'summary_is_ai_generated',
        'experience',
        'education',
        'skills',
        'certifications',
        'projects',
        'achievements',
        'languages',
        'volunteer_work',
        'publications',
        'custom_sections',
        'target_job_id',
        'target_role_description',
        'ai_optimization_data',
        'last_ai_optimized_at',
        'color_overrides',
        'section_order',
        'visibility_settings',
        'pdf_path',
        'docx_path',
        'share_token',
        'is_public',
        'view_count',
        'download_count',
        'ats_score',
        'ats_analysis',
        'last_exported_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'email' => 'encrypted',
        'phone' => 'encrypted',
        'location' => 'encrypted',
        'linkedin_url' => 'encrypted',
        'github_url' => 'encrypted',
        'portfolio_url' => 'encrypted',
        'summary_is_ai_generated' => 'boolean',
        'experience' => 'array',
        'education' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'projects' => 'array',
        'achievements' => 'array',
        'languages' => 'array',
        'volunteer_work' => 'array',
        'publications' => 'array',
        'custom_sections' => 'array',
        'ai_optimization_data' => 'array',
        'color_overrides' => 'array',
        'section_order' => 'array',
        'visibility_settings' => 'array',
        'ats_analysis' => 'array',
        'is_public' => 'boolean',
        'view_count' => 'integer',
        'download_count' => 'integer',
        'last_ai_optimized_at' => 'datetime',
        'last_exported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($resume) {
            if (empty($resume->slug)) {
                $resume->slug = Str::slug($resume->title . '-' . Str::random(6));
            }
            if (empty($resume->share_token)) {
                $resume->share_token = Str::random(32);
            }
        });
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ResumeTemplate::class, 'template_id');
    }

    public function targetJob(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'target_job_id');
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(ResumeAISuggestion::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ResumeVersion::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(ResumeAnalytic::class);
    }

    /**
     * Get pending AI suggestions
     */
    public function pendingSuggestions(): HasMany
    {
        return $this->suggestions()->where('status', 'pending');
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForJob($query, $jobId)
    {
        return $query->where('target_job_id', $jobId);
    }

    /**
     * Helper Methods
     */
    public function getShareUrl(): string
    {
        return route('resume.public', $this->share_token);
    }

    public function getPdfUrl(): ?string
    {
        return $this->pdf_path ? asset('storage/' . $this->pdf_path) : null;
    }

    public function getDocxUrl(): ?string
    {
        return $this->docx_path ? asset('storage/' . $this->docx_path) : null;
    }

    /**
     * Calculate ATS compatibility score
     */
    public function calculateAtsScore(): string
    {
        $score = 0;
        $maxScore = 100;

        // Check for contact information (20 points)
        if ($this->email) $score += 5;
        if ($this->phone) $score += 5;
        if ($this->location) $score += 5;
        if ($this->linkedin_url) $score += 5;

        // Check for key sections (30 points)
        if (!empty($this->experience)) $score += 10;
        if (!empty($this->education)) $score += 10;
        if (!empty($this->skills)) $score += 10;

        // Check for professional summary (10 points)
        if ($this->professional_summary) $score += 10;

        // Check for quantified achievements (15 points)
        $hasNumbers = $this->hasQuantifiedAchievements();
        $score += $hasNumbers ? 15 : 0;

        // Check for keywords match (15 points)
        $keywordScore = $this->calculateKeywordScore();
        $score += $keywordScore;

        // ATS-friendly template (10 points)
        if ($this->template && $this->template->is_ats_friendly) {
            $score += 10;
        }

        $percentage = ($score / $maxScore) * 100;

        if ($percentage >= 80) return 'excellent';
        if ($percentage >= 60) return 'good';
        if ($percentage >= 40) return 'fair';
        return 'poor';
    }

    /**
     * Check if resume has quantified achievements
     */
    private function hasQuantifiedAchievements(): bool
    {
        $content = json_encode($this->experience) . json_encode($this->achievements);
        return preg_match('/\d+%|\d+\+|\$\d+|increased|decreased|improved|reduced/', $content) > 0;
    }

    /**
     * Calculate keyword matching score
     */
    private function calculateKeywordScore(): int
    {
        if (!$this->target_job_id) {
            return 5; // Default score if no target job
        }

        $job = $this->targetJob;
        if (!$job) return 5;

        // Extract keywords from job description
        $jobKeywords = $this->extractKeywords($job->description . ' ' . $job->requirements);
        
        // Extract keywords from resume
        $resumeContent = $this->professional_summary . ' ' . 
                        json_encode($this->experience) . ' ' . 
                        json_encode($this->skills);
        $resumeKeywords = $this->extractKeywords($resumeContent);

        // Calculate match percentage
        $matchedKeywords = array_intersect($jobKeywords, $resumeKeywords);
        $matchPercentage = count($jobKeywords) > 0 
            ? (count($matchedKeywords) / count($jobKeywords)) * 100 
            : 0;

        return min(15, intval($matchPercentage / 100 * 15));
    }

    /**
     * Extract keywords from text
     */
    private function extractKeywords(string $text): array
    {
        // Simple keyword extraction (can be enhanced with NLP)
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = explode(' ', $text);
        
        // Filter common words and short words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be'];
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        return array_unique(array_values($keywords));
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $totalSections = 10;
        $completedSections = 0;

        if ($this->full_name) $completedSections++;
        if ($this->email) $completedSections++;
        if ($this->phone) $completedSections++;
        if ($this->professional_summary) $completedSections++;
        if (!empty($this->experience)) $completedSections++;
        if (!empty($this->education)) $completedSections++;
        if (!empty($this->skills)) $completedSections++;
        if ($this->linkedin_url || $this->github_url || $this->portfolio_url) $completedSections++;
        if (!empty($this->projects) || !empty($this->certifications)) $completedSections++;
        if ($this->location) $completedSections++;

        return intval(($completedSections / $totalSections) * 100);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        
        // Track analytics
        $this->analytics()->create([
            'event_type' => 'viewed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_exported_at' => now()]);
        
        // Track analytics
        $this->analytics()->create([
            'event_type' => 'exported',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Create a new version
     */
    public function createVersion(string $changeDescription = null): ResumeVersion
    {
        $latestVersion = $this->versions()->latest('version_number')->first();
        $versionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        return $this->versions()->create([
            'version_number' => $versionNumber,
            'resume_data' => $this->toArray(),
            'change_description' => $changeDescription,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Check if needs AI optimization
     */
    public function needsAIOptimization(): bool
    {
        if (!$this->last_ai_optimized_at) {
            return true;
        }

        // Re-optimize if updated after last optimization
        return $this->updated_at > $this->last_ai_optimized_at;
    }

    /**
     * Get total word count
     */
    public function getWordCount(): int
    {
        $content = $this->professional_summary . ' ' .
                  json_encode($this->experience) . ' ' .
                  json_encode($this->education) . ' ' .
                  json_encode($this->projects) . ' ' .
                  json_encode($this->achievements);

        return str_word_count(strip_tags($content));
    }

    /**
     * Check if resume is tailored to a job
     */
    public function isTailoredToJob(): bool
    {
        return $this->target_job_id !== null || $this->target_role_description !== null;
    }
}
