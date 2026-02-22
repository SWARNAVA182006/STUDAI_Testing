<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'discovered_job_id',
        'company_name',
        'role_title',
        'interview_type',
        'status',
        'total_questions',
        'questions_answered',
        'duration_minutes',
        'overall_score',
        'performance_metrics',
        'ai_insights',
        'interviewer_style',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'performance_metrics' => 'array',
        'ai_insights' => 'array',
        'interviewer_style' => 'array',
        'overall_score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discoveredJob(): BelongsTo
    {
        return $this->belongsTo(DiscoveredJob::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class)->orderBy('question_order');
    }

    public function performanceReport(): HasMany
    {
        return $this->hasMany(InterviewPerformanceReport::class);
    }

    public function coachingTips(): HasMany
    {
        return $this->hasMany(InterviewCoachingTip::class);
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForCompany($query, string $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    public function scopeForRole($query, string $roleTitle)
    {
        return $query->where('role_title', $roleTitle);
    }

    // Business Logic
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $duration = now()->diffInMinutes($this->started_at);
        
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_minutes' => $duration,
        ]);
    }

    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }

    public function incrementAnsweredQuestions(): void
    {
        $this->increment('questions_answered');
    }

    public function calculateOverallScore(): float
    {
        $responses = $this->questions()
            ->with('response')
            ->get()
            ->pluck('response')
            ->filter();

        if ($responses->isEmpty()) {
            return 0.0;
        }

        $averageScore = $responses->avg('overall_score');
        
        $this->update(['overall_score' => round($averageScore, 2)]);

        return round($averageScore, 2);
    }

    public function getProgressPercentage(): int
    {
        if ($this->total_questions === 0) {
            return 0;
        }

        return (int) round(($this->questions_answered / $this->total_questions) * 100);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function canContinue(): bool
    {
        return $this->status === 'in_progress' 
            && $this->questions_answered < $this->total_questions;
    }

    public function getNextQuestion(): ?InterviewQuestion
    {
        return $this->questions()
            ->whereDoesntHave('response')
            ->orderBy('question_order')
            ->first();
    }

    public function updateAIInsights(array $insights): void
    {
        $currentInsights = $this->ai_insights ?? [];
        
        $this->update([
            'ai_insights' => array_merge($currentInsights, $insights),
        ]);
    }

    public function updatePerformanceMetrics(array $metrics): void
    {
        $currentMetrics = $this->performance_metrics ?? [];
        
        $this->update([
            'performance_metrics' => array_merge($currentMetrics, $metrics),
        ]);
    }
}
