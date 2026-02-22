<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoInterviewAnalysis extends Model
{
    use HasFactory;

    protected $table = 'video_interview_analyses';

    protected $fillable = [
        'video_interview_recording_id',
        'video_interview_question_id',
        'status',
        // Content Analysis
        'content_score',
        'clarity_score',
        'structure_score',
        'relevance_score',
        'key_points_mentioned',
        'missing_elements',
        'star_analysis',
        // Speech Analysis
        'speech_pace_wpm',
        'speech_pace_rating',
        'filler_words',
        'filler_word_count',
        'filler_word_percentage',
        'pause_analysis',
        'articulation_score',
        // Body Language
        'eye_contact_score',
        'posture_score',
        'gesture_score',
        'facial_expression_score',
        'body_language_breakdown',
        'eye_contact_timeline',
        // Confidence & Emotion
        'confidence_score',
        'enthusiasm_score',
        'nervousness_indicator',
        'emotion_timeline',
        'sentiment_analysis',
        // Overall
        'overall_score',
        'performance_grade',
        'strengths',
        'areas_for_improvement',
        'actionable_feedback',
        'ai_summary',
        'notable_moments',
        'improvement_timestamps',
        'analyzed_at',
    ];

    protected $casts = [
        'content_score' => 'decimal:2',
        'clarity_score' => 'decimal:2',
        'structure_score' => 'decimal:2',
        'relevance_score' => 'decimal:2',
        'key_points_mentioned' => 'array',
        'missing_elements' => 'array',
        'star_analysis' => 'array',
        'speech_pace_wpm' => 'decimal:2',
        'filler_words' => 'array',
        'filler_word_count' => 'integer',
        'filler_word_percentage' => 'decimal:2',
        'pause_analysis' => 'array',
        'articulation_score' => 'decimal:2',
        'eye_contact_score' => 'decimal:2',
        'posture_score' => 'decimal:2',
        'gesture_score' => 'decimal:2',
        'facial_expression_score' => 'decimal:2',
        'body_language_breakdown' => 'array',
        'eye_contact_timeline' => 'array',
        'confidence_score' => 'decimal:2',
        'enthusiasm_score' => 'decimal:2',
        'nervousness_indicator' => 'decimal:2',
        'emotion_timeline' => 'array',
        'sentiment_analysis' => 'array',
        'overall_score' => 'decimal:2',
        'strengths' => 'array',
        'areas_for_improvement' => 'array',
        'actionable_feedback' => 'array',
        'notable_moments' => 'array',
        'improvement_timestamps' => 'array',
        'analyzed_at' => 'datetime',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Speech Pace Ratings
    public const PACE_TOO_SLOW = 'too_slow';
    public const PACE_SLOW = 'slow';
    public const PACE_OPTIMAL = 'optimal';
    public const PACE_FAST = 'fast';
    public const PACE_TOO_FAST = 'too_fast';

    // Performance Grades
    public const GRADE_A_PLUS = 'A+';
    public const GRADE_A = 'A';
    public const GRADE_B_PLUS = 'B+';
    public const GRADE_B = 'B';
    public const GRADE_C_PLUS = 'C+';
    public const GRADE_C = 'C';
    public const GRADE_D = 'D';
    public const GRADE_F = 'F';

    // Relationships
    public function recording(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewRecording::class, 'video_interview_recording_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewQuestion::class, 'video_interview_question_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getSpeechPaceLabelAttribute(): string
    {
        return match ($this->speech_pace_rating) {
            self::PACE_TOO_SLOW => 'Too Slow (< 100 WPM)',
            self::PACE_SLOW => 'Slow (100-120 WPM)',
            self::PACE_OPTIMAL => 'Optimal (120-160 WPM)',
            self::PACE_FAST => 'Fast (160-180 WPM)',
            self::PACE_TOO_FAST => 'Too Fast (> 180 WPM)',
            default => 'Unknown',
        };
    }

    public function getSpeechPaceColorAttribute(): string
    {
        return match ($this->speech_pace_rating) {
            self::PACE_OPTIMAL => 'green',
            self::PACE_SLOW, self::PACE_FAST => 'yellow',
            self::PACE_TOO_SLOW, self::PACE_TOO_FAST => 'red',
            default => 'gray',
        };
    }

    public function getGradeColorAttribute(): string
    {
        return match ($this->performance_grade) {
            self::GRADE_A_PLUS, self::GRADE_A => 'green',
            self::GRADE_B_PLUS, self::GRADE_B => 'blue',
            self::GRADE_C_PLUS, self::GRADE_C => 'yellow',
            self::GRADE_D => 'orange',
            self::GRADE_F => 'red',
            default => 'gray',
        };
    }

    public function getBodyLanguageScoreAttribute(): ?float
    {
        $scores = array_filter([
            $this->eye_contact_score,
            $this->posture_score,
            $this->gesture_score,
            $this->facial_expression_score,
        ]);

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    public function getContentOverallScoreAttribute(): ?float
    {
        $scores = array_filter([
            $this->content_score,
            $this->clarity_score,
            $this->structure_score,
            $this->relevance_score,
        ]);

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    public function getTopStrengthsAttribute(): array
    {
        return array_slice($this->strengths ?? [], 0, 3);
    }

    public function getTopImprovementsAttribute(): array
    {
        return array_slice($this->areas_for_improvement ?? [], 0, 3);
    }

    // Methods
    public static function calculateGrade(float $score): string
    {
        return match (true) {
            $score >= 95 => self::GRADE_A_PLUS,
            $score >= 90 => self::GRADE_A,
            $score >= 85 => self::GRADE_B_PLUS,
            $score >= 80 => self::GRADE_B,
            $score >= 75 => self::GRADE_C_PLUS,
            $score >= 70 => self::GRADE_C,
            $score >= 60 => self::GRADE_D,
            default => self::GRADE_F,
        };
    }

    public static function calculateSpeechPaceRating(float $wpm): string
    {
        return match (true) {
            $wpm < 100 => self::PACE_TOO_SLOW,
            $wpm < 120 => self::PACE_SLOW,
            $wpm <= 160 => self::PACE_OPTIMAL,
            $wpm <= 180 => self::PACE_FAST,
            default => self::PACE_TOO_FAST,
        };
    }

    public function getScoreSummary(): array
    {
        return [
            'overall' => $this->overall_score,
            'grade' => $this->performance_grade,
            'content' => $this->content_overall_score,
            'body_language' => $this->body_language_score,
            'confidence' => $this->confidence_score,
            'speech' => [
                'pace_wpm' => $this->speech_pace_wpm,
                'pace_rating' => $this->speech_pace_rating,
                'filler_count' => $this->filler_word_count,
                'articulation' => $this->articulation_score,
            ],
        ];
    }
}
