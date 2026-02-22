<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'application_id',
        'job_id',
        'feedback_type',
        'feedback_trigger',
        'overall_rating',
        'application_process_rating',
        'communication_rating',
        'interview_rating',
        'respect_rating',
        'transparency_rating',
        'positive_experience',
        'negative_experience',
        'improvement_suggestions',
        'general_comments',
        'would_recommend',
        'would_apply_again',
        'likelihood_to_recommend',
        'feedback_requested_at',
        'feedback_submitted_at',
        'response_time_days',
    ];

    protected $casts = [
        'would_recommend' => 'boolean',
        'would_apply_again' => 'boolean',
        'feedback_requested_at' => 'datetime',
        'feedback_submitted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('feedback_submitted_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('feedback_submitted_at')
            ->whereNotNull('feedback_requested_at');
    }

    public function scopePositive($query)
    {
        return $query->where('overall_rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('overall_rating', '<=', 2);
    }

    public function scopePromoters($query)
    {
        return $query->where('likelihood_to_recommend', '>=', 9);
    }

    public function scopeDetractors($query)
    {
        return $query->where('likelihood_to_recommend', '<=', 6);
    }

    public function scopePassives($query)
    {
        return $query->whereBetween('likelihood_to_recommend', [7, 8]);
    }

    /**
     * Accessors
     */
    public function getNpsSegmentAttribute(): ?string
    {
        if (!$this->likelihood_to_recommend) return null;
        
        if ($this->likelihood_to_recommend >= 9) return 'promoter';
        if ($this->likelihood_to_recommend >= 7) return 'passive';
        return 'detractor';
    }

    public function getSentimentAttribute(): string
    {
        if (!$this->overall_rating) return 'unknown';
        
        if ($this->overall_rating >= 4) return 'positive';
        if ($this->overall_rating >= 3) return 'neutral';
        return 'negative';
    }

    public function getIsPositiveAttribute(): bool
    {
        return $this->overall_rating >= 4;
    }

    /**
     * Helper methods
     */
    public function submit(array $data): void
    {
        $this->fill($data);
        $this->feedback_submitted_at = now();
        
        if ($this->feedback_requested_at) {
            $this->response_time_days = now()->diffInDays($this->feedback_requested_at);
        }
        
        $this->save();
    }

    public static function calculateNPS(int $companyId, $startDate = null, $endDate = null): float
    {
        $query = self::where('company_id', $companyId)
            ->whereNotNull('likelihood_to_recommend')
            ->whereNotNull('feedback_submitted_at');

        if ($startDate) {
            $query->where('feedback_submitted_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('feedback_submitted_at', '<=', $endDate);
        }

        $total = $query->count();
        if ($total === 0) return 0;

        $promoters = $query->clone()->where('likelihood_to_recommend', '>=', 9)->count();
        $detractors = $query->clone()->where('likelihood_to_recommend', '<=', 6)->count();

        return (($promoters - $detractors) / $total) * 100;
    }
}
