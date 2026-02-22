<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'interview_feedback';

    protected $fillable = [
        'interview_response_id',
        'feedback_type',
        'feedback_text',
        'strengths',
        'improvements',
        'suggestions',
        'example_answers',
        'is_positive',
        'focus_area',
        'priority',
    ];

    protected $casts = [
        'strengths' => 'array',
        'improvements' => 'array',
        'suggestions' => 'array',
        'example_answers' => 'array',
        'is_positive' => 'boolean',
    ];

    // Relationships
    public function interviewResponse(): BelongsTo
    {
        return $this->belongsTo(InterviewResponse::class);
    }

    // Scopes
    public function scopeRealTime($query)
    {
        return $query->where('feedback_type', 'real_time');
    }

    public function scopePostResponse($query)
    {
        return $query->where('feedback_type', 'post_response');
    }

    public function scopeSessionSummary($query)
    {
        return $query->where('feedback_type', 'session_summary');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 7);
    }

    public function scopeByFocusArea($query, string $area)
    {
        return $query->where('focus_area', $area);
    }

    // Business Logic
    public function isActionable(): bool
    {
        return !empty($this->suggestions);
    }

    public function hasExamples(): bool
    {
        return !empty($this->example_answers);
    }
}
