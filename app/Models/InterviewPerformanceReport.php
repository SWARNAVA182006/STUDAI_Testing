<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewPerformanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_session_id',
        'user_id',
        'overall_score',
        'category_scores',
        'strengths',
        'weaknesses',
        'filler_word_analysis',
        'star_methodology_score',
        'company_fit_analysis',
        'actionable_improvements',
        'recommended_practice_areas',
        'comparison_metrics',
        'executive_summary',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'category_scores' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'filler_word_analysis' => 'array',
        'star_methodology_score' => 'array',
        'company_fit_analysis' => 'array',
        'actionable_improvements' => 'array',
        'recommended_practice_areas' => 'array',
        'comparison_metrics' => 'array',
    ];

    // Relationships
    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Business Logic
    public function getTopStrengths(int $limit = 3): array
    {
        return array_slice($this->strengths ?? [], 0, $limit);
    }

    public function getTopWeaknesses(int $limit = 3): array
    {
        return array_slice($this->weaknesses ?? [], 0, $limit);
    }

    public function getPrioritizedImprovements(int $limit = 5): array
    {
        return array_slice($this->actionable_improvements ?? [], 0, $limit);
    }

    public function getPerformanceGrade(): string
    {
        return match(true) {
            $this->overall_score >= 90 => 'A+',
            $this->overall_score >= 85 => 'A',
            $this->overall_score >= 80 => 'B+',
            $this->overall_score >= 75 => 'B',
            $this->overall_score >= 70 => 'C+',
            $this->overall_score >= 65 => 'C',
            default => 'D',
        };
    }

    public function needsMorePractice(): bool
    {
        return $this->overall_score < 75;
    }
}
