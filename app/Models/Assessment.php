<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Assessment Model (S.C.O.U.T. Adaptive Assessments)
 * 
 * Represents a dynamic adaptive assessment for a job application.
 * Questions adjust difficulty based on candidate performance.
 * 
 * NOTE: This is for S.C.O.U.T. employer assessments. For skill gap assessments, see SkillAssessment model.
 * 
 * @property int $id
 * @property int $application_id
 * @property int $job_id
 * @property string $type comprehensive|technical|behavioral|case_study
 * @property string $status pending|in_progress|completed|expired
 * @property int $total_questions
 * @property int $questions_answered
 * @property string $current_difficulty easy|medium|hard|expert
 * @property bool $adaptive_enabled
 * @property int $time_limit_minutes
 * @property float $final_score
 * @property array $performance_summary
 * @property array $metadata
 * @property \Carbon\Carbon $started_at
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @property-read Application $application
 * @property-read Job $job
 * @property-read \Illuminate\Database\Eloquent\Collection|AssessmentQuestion[] $questions
 * @property-read \Illuminate\Database\Eloquent\Collection|AssessmentResponse[] $responses
 */
class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'scout_assessments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'application_id',
        'job_id',
        'type',
        'status',
        'total_questions',
        'questions_answered',
        'current_difficulty',
        'adaptive_enabled',
        'time_limit_minutes',
        'final_score',
        'performance_summary',
        'metadata',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'adaptive_enabled' => 'boolean',
        'performance_summary' => 'array',
        'metadata' => 'array',
        'final_score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the application this assessment belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the job this assessment is for.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get all questions for this assessment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('question_number');
    }

    /**
     * Get all responses for this assessment.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    /**
     * Scope: Get pending assessments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get in-progress assessments.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Get completed assessments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Get expired assessments.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope: Get assessments of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get adaptive assessments.
     */
    public function scopeAdaptive($query)
    {
        return $query->where('adaptive_enabled', true);
    }

    /**
     * Accessor: Check if assessment is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        if (!$this->started_at) {
            return false;
        }

        $timeLimit = $this->time_limit_minutes * 60; // Convert to seconds
        $elapsed = now()->diffInSeconds($this->started_at);

        return $elapsed > $timeLimit;
    }

    /**
     * Accessor: Get progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }

        return round(($this->questions_answered / $this->total_questions) * 100, 2);
    }

    /**
     * Accessor: Get time remaining in seconds.
     */
    public function getTimeRemainingAttribute(): int
    {
        if (!$this->started_at) {
            return $this->time_limit_minutes * 60;
        }

        $timeLimit = $this->time_limit_minutes * 60;
        $elapsed = now()->diffInSeconds($this->started_at);
        $remaining = $timeLimit - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Accessor: Get proficiency level based on final score.
     */
    public function getProficiencyLevelAttribute(): ?string
    {
        if (!$this->final_score) {
            return null;
        }

        if ($this->final_score >= 90) return 'Expert';
        if ($this->final_score >= 75) return 'Advanced';
        if ($this->final_score >= 60) return 'Intermediate';
        if ($this->final_score >= 45) return 'Basic';
        return 'Beginner';
    }

    /**
     * Accessor: Get hiring recommendation based on final score.
     */
    public function getRecommendationAttribute(): ?string
    {
        if (!$this->final_score) {
            return null;
        }

        if ($this->final_score >= 85) return 'STRONG HIRE';
        if ($this->final_score >= 70) return 'RECOMMEND';
        if ($this->final_score >= 55) return 'CONSIDER';
        return 'NOT RECOMMENDED';
    }

    /**
     * Mark assessment as started.
     */
    public function markAsStarted(): void
    {
        if (!$this->started_at) {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    /**
     * Mark assessment as completed.
     */
    public function markAsCompleted(float $finalScore, array $performanceSummary): void
    {
        $this->update([
            'status' => 'completed',
            'final_score' => $finalScore,
            'performance_summary' => $performanceSummary,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark assessment as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Increment questions answered counter.
     */
    public function incrementQuestionsAnswered(): void
    {
        $this->increment('questions_answered');
    }

    /**
     * Update current difficulty level.
     */
    public function updateDifficulty(string $difficulty): void
    {
        $this->update(['current_difficulty' => $difficulty]);
    }

    /**
     * Check if assessment is complete.
     */
    public function isComplete(): bool
    {
        return $this->questions_answered >= $this->total_questions;
    }

    /**
     * Check if assessment can be taken (not expired, not completed).
     */
    public function canBeTaken(): bool
    {
        return $this->status === 'pending' || $this->status === 'in_progress' && !$this->is_expired;
    }
}
