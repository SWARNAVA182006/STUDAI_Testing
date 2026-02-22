<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AssessmentResponse Model
 * 
 * Represents a candidate's answer to an assessment question.
 * Includes AI evaluation, scoring, and timing data.
 * 
 * @property int $id
 * @property int $assessment_id
 * @property int $question_id
 * @property string $answer
 * @property string $code_submission
 * @property bool $is_correct
 * @property float $score
 * @property float $max_score
 * @property int $time_taken_seconds
 * @property int $confidence_level
 * @property string $evaluation_feedback
 * @property array $evaluation_details
 * @property \Carbon\Carbon $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Assessment $assessment
 * @property-read AssessmentQuestion $question
 */
class AssessmentResponse extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'scout_assessment_responses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'assessment_id',
        'question_id',
        'answer',
        'code_submission',
        'is_correct',
        'score',
        'max_score',
        'time_taken_seconds',
        'confidence_level',
        'evaluation_feedback',
        'evaluation_details',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'evaluation_details' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the assessment this response belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the question this response answers.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }

    /**
     * Scope: Get correct responses.
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope: Get incorrect responses.
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope: Get responses with high confidence (4-5).
     */
    public function scopeHighConfidence($query)
    {
        return $query->whereIn('confidence_level', [4, 5]);
    }

    /**
     * Scope: Get responses with low confidence (1-2).
     */
    public function scopeLowConfidence($query)
    {
        return $query->whereIn('confidence_level', [1, 2]);
    }

    /**
     * Scope: Get responses that took longer than average.
     */
    public function scopeSlow($query, int $averageTime)
    {
        return $query->where('time_taken_seconds', '>', $averageTime);
    }

    /**
     * Scope: Get responses that were answered quickly.
     */
    public function scopeFast($query, int $averageTime)
    {
        return $query->where('time_taken_seconds', '<', $averageTime);
    }

    /**
     * Accessor: Get score percentage.
     */
    public function getScorePercentageAttribute(): float
    {
        if ($this->max_score == 0) {
            return 0;
        }

        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Accessor: Get time taken in minutes (formatted).
     */
    public function getTimeTakenMinutesAttribute(): float
    {
        return round($this->time_taken_seconds / 60, 1);
    }

    /**
     * Accessor: Get confidence level as text.
     */
    public function getConfidenceTextAttribute(): string
    {
        return match($this->confidence_level) {
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Moderate',
            4 => 'High',
            5 => 'Very High',
            default => 'Unknown',
        };
    }

    /**
     * Accessor: Check if response is partially correct.
     */
    public function getIsPartiallyCorrectAttribute(): bool
    {
        return !$this->is_correct && $this->score > 0;
    }

    /**
     * Check if response was submitted quickly (< 50% of time limit).
     */
    public function wasSubmittedQuickly(): bool
    {
        $timeLimit = $this->question->time_limit_seconds;
        $threshold = $timeLimit * 0.5;
        
        return $this->time_taken_seconds < $threshold;
    }

    /**
     * Check if candidate was confident (level 4-5).
     */
    public function wasConfident(): bool
    {
        return in_array($this->confidence_level, [4, 5]);
    }

    /**
     * Get the submitted content (answer or code).
     */
    public function getSubmittedContent(): string
    {
        return $this->code_submission ?? $this->answer ?? '';
    }
}
