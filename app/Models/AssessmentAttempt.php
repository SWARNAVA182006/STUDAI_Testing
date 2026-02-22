<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'answers',
        'score',
        'correct_answers',
        'total_questions',
        'passed',
        'status',
        'started_at',
        'completed_at',
        'expires_at',
        'time_spent_seconds',
    ];
    
    protected $casts = [
        'answers' => 'array',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    /**
     * Get the user who took this attempt
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the assessment for this attempt
     */
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
    
    /**
     * Get the certificate if issued
     */
    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'attempt_id');
    }
    
    /**
     * Scope: Completed attempts only
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    /**
     * Scope: Passed attempts only
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }
    
    /**
     * Check if attempt has expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || now()->greaterThan($this->expires_at);
    }
    
    /**
     * Check if attempt is still in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress' && !$this->isExpired();
    }
    
    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeAttribute(): int
    {
        if ($this->status !== 'in_progress') return 0;
        
        $remaining = $this->expires_at->diffInSeconds(now(), false);
        return max(0, abs($remaining));
    }
    
    /**
     * Get time spent as formatted string
     */
    public function getFormattedTimeSpentAttribute(): string
    {
        if (!$this->time_spent_seconds) return 'N/A';
        
        $minutes = floor($this->time_spent_seconds / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        return sprintf('%d min %d sec', $minutes, $seconds);
    }
    
    /**
     * Get accuracy percentage
     */
    public function getAccuracyAttribute(): float
    {
        if ($this->total_questions === 0) return 0.0;
        return round(($this->correct_answers / $this->total_questions) * 100, 1);
    }
    
    /**
     * Calculate and update score
     */
    public function calculateScore(): void
    {
        $assessment = $this->assessment;
        $questions = $assessment->questions;
        $userAnswers = $this->answers;
        
        $correctCount = 0;
        $totalPoints = 0;
        $earnedPoints = 0;
        
        foreach ($questions as $index => $question) {
            $points = $question['points'] ?? 1;
            $totalPoints += $points;
            
            $userAnswer = $userAnswers[$index] ?? null;
            
            if ($this->isAnswerCorrect($question, $userAnswer)) {
                $correctCount++;
                $earnedPoints += $points;
            }
        }
        
        $this->correct_answers = $correctCount;
        $this->total_questions = count($questions);
        $this->score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $this->passed = $this->score >= $assessment->passing_score;
        $this->save();
    }
    
    /**
     * Check if a single answer is correct
     */
    protected function isAnswerCorrect($question, $userAnswer): bool
    {
        if ($userAnswer === null) return false;
        
        $type = $question['type'] ?? 'mcq';
        
        switch ($type) {
            case 'mcq':
            case 'true_false':
                return $userAnswer === $question['correct_answer'];
                
            case 'multiple_choice':
                $correct = $question['correct_answers'] ?? [];
                sort($correct);
                $user = is_array($userAnswer) ? $userAnswer : [$userAnswer];
                sort($user);
                return $correct === $user;
                
            case 'fill_blank':
                $correct = strtolower(trim($question['correct_answer']));
                $user = strtolower(trim($userAnswer));
                return $correct === $user;
                
            case 'coding':
                // For coding questions, check against test cases
                // This would require code execution - placeholder for now
                return $this->checkCodingAnswer($question, $userAnswer);
                
            default:
                return false;
        }
    }
    
    /**
     * Check coding question answer (placeholder)
     */
    protected function checkCodingAnswer($question, $code): bool
    {
        // TODO: Integrate with Judge0 API or similar code execution service
        // For now, return false (manual grading required)
        return false;
    }
    
    /**
     * Mark attempt as completed
     */
    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->time_spent_seconds = $this->started_at->diffInSeconds(now());
        $this->save();
        
        $this->calculateScore();
        
        // Update assessment statistics
        $this->assessment->updateStatistics();
        
        // Issue certificate if passed
        if ($this->passed) {
            $this->issueCertificate();
        }
    }
    
    /**
     * Issue certificate for passed attempt
     */
    protected function issueCertificate(): void
    {
        if ($this->certificate) return; // Already issued
        
        Certificate::create([
            'user_id' => $this->user_id,
            'assessment_id' => $this->assessment_id,
            'attempt_id' => $this->id,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'verification_code' => Certificate::generateVerificationCode(),
            'score' => $this->score,
            'issued_at' => now(),
        ]);
    }
}
