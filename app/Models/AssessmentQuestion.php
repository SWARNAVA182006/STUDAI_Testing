<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * AssessmentQuestion Model
 * 
 * Represents a question in an adaptive assessment.
 * Questions are AI-generated and unique to each candidate.
 * 
 * @property int $id
 * @property int $assessment_id
 * @property int $question_number
 * @property string $question_text
 * @property string $question_type multiple_choice|coding|essay|case_study
 * @property string $difficulty easy|medium|hard|expert
 * @property string $category technical|behavioral|problem_solving|system_design|leadership
 * @property string $expected_answer
 * @property array $evaluation_criteria
 * @property int $time_limit_seconds
 * @property int $points
 * @property array $options
 * @property string $code_template
 * @property string $context
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Assessment $assessment
 * @property-read AssessmentResponse $response
 */
class AssessmentQuestion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'scout_assessment_questions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'assessment_id',
        'question_number',
        'question_text',
        'question_type',
        'difficulty',
        'category',
        'expected_answer',
        'evaluation_criteria',
        'time_limit_seconds',
        'points',
        'options',
        'code_template',
        'context',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'evaluation_criteria' => 'array',
        'options' => 'array',
    ];

    /**
     * Get the assessment this question belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the response for this question.
     */
    public function response(): HasOne
    {
        return $this->hasOne(AssessmentResponse::class, 'question_id');
    }

    /**
     * Scope: Get questions by difficulty.
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope: Get questions by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get questions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('question_type', $type);
    }

    /**
     * Scope: Get easy questions.
     */
    public function scopeEasy($query)
    {
        return $query->where('difficulty', 'easy');
    }

    /**
     * Scope: Get medium questions.
     */
    public function scopeMedium($query)
    {
        return $query->where('difficulty', 'medium');
    }

    /**
     * Scope: Get hard questions.
     */
    public function scopeHard($query)
    {
        return $query->where('difficulty', 'hard');
    }

    /**
     * Scope: Get expert questions.
     */
    public function scopeExpert($query)
    {
        return $query->where('difficulty', 'expert');
    }

    /**
     * Scope: Get multiple choice questions.
     */
    public function scopeMultipleChoice($query)
    {
        return $query->where('question_type', 'multiple_choice');
    }

    /**
     * Scope: Get coding questions.
     */
    public function scopeCoding($query)
    {
        return $query->where('question_type', 'coding');
    }

    /**
     * Scope: Get essay questions.
     */
    public function scopeEssay($query)
    {
        return $query->where('question_type', 'essay');
    }

    /**
     * Scope: Get case study questions.
     */
    public function scopeCaseStudy($query)
    {
        return $query->where('question_type', 'case_study');
    }

    /**
     * Accessor: Check if question is multiple choice.
     */
    public function getIsMultipleChoiceAttribute(): bool
    {
        return $this->question_type === 'multiple_choice';
    }

    /**
     * Accessor: Check if question requires coding.
     */
    public function getRequiresCodingAttribute(): bool
    {
        return $this->question_type === 'coding';
    }

    /**
     * Accessor: Get difficulty weight for scoring.
     */
    public function getDifficultyWeightAttribute(): float
    {
        return match($this->difficulty) {
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
            'expert' => 2.5,
            default => 1.0,
        };
    }

    /**
     * Accessor: Get time limit in minutes (formatted).
     */
    public function getTimeLimitMinutesAttribute(): float
    {
        return round($this->time_limit_seconds / 60, 1);
    }

    /**
     * Check if this question has been answered.
     */
    public function isAnswered(): bool
    {
        return $this->response()->exists();
    }

    /**
     * Get the submitted answer if available.
     */
    public function getSubmittedAnswer(): ?string
    {
        return $this->response?->answer ?? $this->response?->code_submission;
    }
}
