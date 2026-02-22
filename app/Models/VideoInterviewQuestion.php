<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VideoInterviewQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_interview_session_id',
        'order',
        'question_text',
        'question_context',
        'question_type',
        'prep_time_seconds',
        'max_response_time_seconds',
        'min_response_time_seconds',
        'max_retakes',
        'allow_skip',
        'expected_elements',
        'keywords_to_look_for',
        'ideal_answer_notes',
    ];

    protected $casts = [
        'order' => 'integer',
        'prep_time_seconds' => 'integer',
        'max_response_time_seconds' => 'integer',
        'min_response_time_seconds' => 'integer',
        'max_retakes' => 'integer',
        'allow_skip' => 'boolean',
        'expected_elements' => 'array',
        'keywords_to_look_for' => 'array',
    ];

    // Question types
    public const TYPE_BEHAVIORAL = 'behavioral';
    public const TYPE_TECHNICAL = 'technical';
    public const TYPE_SITUATIONAL = 'situational';
    public const TYPE_GENERAL = 'general';

    public const TYPES = [
        self::TYPE_BEHAVIORAL => 'Behavioral',
        self::TYPE_TECHNICAL => 'Technical',
        self::TYPE_SITUATIONAL => 'Situational',
        self::TYPE_GENERAL => 'General',
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewSession::class, 'video_interview_session_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(VideoInterviewRecording::class);
    }

    public function latestRecording(): HasOne
    {
        return $this->hasOne(VideoInterviewRecording::class)
            ->where('status', 'ready')
            ->latest('attempt_number');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(VideoInterviewAnalysis::class);
    }

    // Accessors
    public function getPrepTimeFormattedAttribute(): string
    {
        return gmdate('i:s', $this->prep_time_seconds);
    }

    public function getMaxTimeFormattedAttribute(): string
    {
        return gmdate('i:s', $this->max_response_time_seconds);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->question_type] ?? $this->question_type;
    }

    public function getHasResponseAttribute(): bool
    {
        return $this->recordings()->where('status', 'ready')->exists();
    }

    public function getAttemptCountAttribute(): int
    {
        return $this->recordings()->max('attempt_number') ?? 0;
    }

    public function getCanRetakeAttribute(): bool
    {
        return $this->attempt_count < $this->max_retakes;
    }

    public function getBestScoreAttribute(): ?float
    {
        return $this->analyses()
            ->where('status', 'completed')
            ->max('overall_score');
    }
}
