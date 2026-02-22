<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_question_id',
        'user_id',
        'response_type',
        'response_text',
        'audio_file_path',
        'video_file_path',
        'transcription',
        'response_time_seconds',
        'word_count',
        'confidence_score',
        'clarity_score',
        'structure_score',
        'content_score',
        'overall_score',
        'filler_words',
        'star_analysis',
        'keywords_used',
        'missing_elements',
        'answered_at',
    ];

    protected $casts = [
        'filler_words' => 'array',
        'star_analysis' => 'array',
        'keywords_used' => 'array',
        'missing_elements' => 'array',
        'confidence_score' => 'decimal:2',
        'clarity_score' => 'decimal:2',
        'structure_score' => 'decimal:2',
        'content_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'answered_at' => 'datetime',
    ];

    // Relationships
    public function interviewQuestion(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(InterviewFeedback::class);
    }

    // Business Logic
    public function calculateOverallScore(): float
    {
        $scores = array_filter([
            $this->confidence_score,
            $this->clarity_score,
            $this->structure_score,
            $this->content_score,
        ]);

        if (empty($scores)) {
            return 0.0;
        }

        $average = array_sum($scores) / count($scores);
        
        $this->update(['overall_score' => round($average, 2)]);

        return round($average, 2);
    }

    public function calculateWordCount(): int
    {
        $text = $this->response_text ?? $this->transcription ?? '';
        
        $wordCount = str_word_count($text);
        
        $this->update(['word_count' => $wordCount]);

        return $wordCount;
    }

    public function getFillerWordCount(): int
    {
        if (empty($this->filler_words)) {
            return 0;
        }

        return array_sum($this->filler_words);
    }

    public function getFillerWordPercentage(): float
    {
        if ($this->word_count === 0) {
            return 0.0;
        }

        $fillerCount = $this->getFillerWordCount();
        
        return round(($fillerCount / $this->word_count) * 100, 2);
    }

    public function hasSTARComponents(): bool
    {
        if (empty($this->star_analysis)) {
            return false;
        }

        $requiredComponents = ['situation', 'task', 'action', 'result'];
        
        foreach ($requiredComponents as $component) {
            if (!isset($this->star_analysis[$component]) || empty($this->star_analysis[$component])) {
                return false;
            }
        }

        return true;
    }

    public function getMissingSTARComponents(): array
    {
        if (empty($this->star_analysis)) {
            return ['situation', 'task', 'action', 'result'];
        }

        $components = ['situation', 'task', 'action', 'result'];
        $missing = [];

        foreach ($components as $component) {
            if (!isset($this->star_analysis[$component]) || empty($this->star_analysis[$component])) {
                $missing[] = $component;
            }
        }

        return $missing;
    }

    public function isWithinExpectedDuration(): bool
    {
        $expectedDuration = $this->interviewQuestion->getExpectedDuration();
        
        // Allow 50% variance
        $minDuration = $expectedDuration * 0.5;
        $maxDuration = $expectedDuration * 1.5;

        return $this->response_time_seconds >= $minDuration 
            && $this->response_time_seconds <= $maxDuration;
    }

    public function getScoreBreakdown(): array
    {
        return [
            'confidence' => $this->confidence_score ?? 0,
            'clarity' => $this->clarity_score ?? 0,
            'structure' => $this->structure_score ?? 0,
            'content' => $this->content_score ?? 0,
            'overall' => $this->overall_score ?? 0,
        ];
    }
}
