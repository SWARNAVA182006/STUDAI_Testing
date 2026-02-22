<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InterviewQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_session_id',
        'question_order',
        'question_type',
        'question_text',
        'question_context',
        'difficulty_level',
        'expected_elements',
        'star_components',
        'ideal_answer_outline',
        'follow_up_questions',
        'interviewer_notes',
        'is_company_specific',
        'company_context',
    ];

    protected $casts = [
        'question_context' => 'array',
        'expected_elements' => 'array',
        'star_components' => 'array',
        'follow_up_questions' => 'array',
        'interviewer_notes' => 'array',
        'is_company_specific' => 'boolean',
    ];

    // Relationships
    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(InterviewResponse::class);
    }

    // Scopes
    public function scopeTechnical($query)
    {
        return $query->where('question_type', 'technical');
    }

    public function scopeBehavioral($query)
    {
        return $query->where('question_type', 'behavioral');
    }

    public function scopeCompanySpecific($query)
    {
        return $query->where('is_company_specific', true);
    }

    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }

    // Business Logic
    public function isAnswered(): bool
    {
        return $this->response()->exists();
    }

    public function requiresSTARFormat(): bool
    {
        return $this->question_type === 'behavioral' 
            && !empty($this->star_components);
    }

    public function getExpectedDuration(): int
    {
        // Return expected answer duration in seconds
        return match($this->difficulty_level) {
            'easy' => 60,
            'medium' => 120,
            'hard' => 180,
            default => 90,
        };
    }

    public function generateFollowUp(string $userAnswer): ?string
    {
        // This would typically call an AI service to generate a relevant follow-up
        // For now, return a predefined follow-up if available
        $followUps = $this->follow_up_questions ?? [];
        
        if (empty($followUps)) {
            return null;
        }

        // Return a random follow-up (in real implementation, AI would choose based on answer)
        return $followUps[array_rand($followUps)];
    }
}
