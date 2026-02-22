<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class NegotiationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'strategy_id',
        'scenario_id',
        'session_type',
        'communication_mode',
        'session_start',
        'session_end',
        'duration_minutes',
        'session_goal',
        'current_stage',
        'session_context',
        'key_points_discussed',
        'employer_signals',
        'user_performance',
        'ai_interventions',
        'outcome',
        'final_agreed_salary',
        'final_agreed_terms',
        'outcome_notes',
        'what_worked_well',
        'what_to_improve',
        'lessons_learned',
        'user_satisfaction',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'final_agreed_salary' => 'decimal:2',
        'session_context' => 'array',
        'key_points_discussed' => 'array',
        'employer_signals' => 'array',
        'user_performance' => 'array',
        'ai_interventions' => 'array',
        'final_agreed_terms' => 'array',
        'what_worked_well' => 'array',
        'what_to_improve' => 'array',
        'lessons_learned' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(NegotiationStrategy::class, 'strategy_id');
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(NegotiationScenario::class, 'scenario_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(NegotiationMessage::class, 'session_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('session_end');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('session_end');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('outcome', 'successful');
    }

    public function scopeLiveCoaching($query)
    {
        return $query->where('session_type', 'live_coaching');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('session_start', 'desc');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return is_null($this->session_end);
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->session_end) {
            return null;
        }

        return $this->session_start->diffInMinutes($this->session_end);
    }

    public function getSessionTypeLabelAttribute(): string
    {
        return match($this->session_type) {
            'preparation' => 'Preparation Session',
            'live_coaching' => 'Live Coaching',
            'post_mortem' => 'Post-Mortem Analysis',
            default => 'Unknown',
        };
    }

    public function getCommunicationModeLabelAttribute(): string
    {
        return match($this->communication_mode) {
            'email' => 'Email Exchange',
            'phone' => 'Phone Call',
            'in_person' => 'In-Person Meeting',
            'video_call' => 'Video Call',
            default => 'Unknown',
        };
    }

    public function getCommunicationModeIconAttribute(): string
    {
        return match($this->communication_mode) {
            'email' => '✉️',
            'phone' => '📞',
            'in_person' => '🤝',
            'video_call' => '💻',
            default => '📝',
        };
    }

    public function getCurrentStageLabelAttribute(): string
    {
        return match($this->current_stage) {
            'initial_offer' => 'Reviewing Initial Offer',
            'counter_offer' => 'Counter Offer Made',
            'negotiation' => 'Active Negotiation',
            'closing' => 'Finalizing Terms',
            'completed' => 'Completed',
            default => 'Unknown',
        };
    }

    public function getCurrentStageProgressAttribute(): int
    {
        return match($this->current_stage) {
            'initial_offer' => 20,
            'counter_offer' => 40,
            'negotiation' => 60,
            'closing' => 80,
            'completed' => 100,
            default => 0,
        };
    }

    public function getOutcomeLabelAttribute(): ?string
    {
        if (!$this->outcome) {
            return null;
        }

        return match($this->outcome) {
            'successful' => 'Successfully Negotiated',
            'pending' => 'Pending Response',
            'needs_follow_up' => 'Needs Follow-Up',
            'unsuccessful' => 'Unsuccessful',
            'user_withdrew' => 'User Withdrew',
            default => 'Unknown',
        };
    }

    public function getOutcomeColorAttribute(): ?string
    {
        if (!$this->outcome) {
            return null;
        }

        return match($this->outcome) {
            'successful' => 'green',
            'pending' => 'blue',
            'needs_follow_up' => 'yellow',
            'unsuccessful' => 'red',
            'user_withdrew' => 'gray',
            default => 'gray',
        };
    }

    public function getSatisfactionLabelAttribute(): ?string
    {
        if (!$this->user_satisfaction) {
            return null;
        }

        return match(true) {
            $this->user_satisfaction >= 4 => 'Very Satisfied',
            $this->user_satisfaction >= 3 => 'Satisfied',
            $this->user_satisfaction >= 2 => 'Neutral',
            default => 'Dissatisfied',
        };
    }

    public function getSalaryGainAttribute(): ?float
    {
        if (!$this->final_agreed_salary || !$this->strategy) {
            return null;
        }

        return (float) ($this->final_agreed_salary - $this->strategy->offered_salary);
    }

    public function getSalaryGainPercentageAttribute(): ?float
    {
        if (!$this->salary_gain || !$this->strategy || $this->strategy->offered_salary <= 0) {
            return null;
        }

        return ($this->salary_gain / (float) $this->strategy->offered_salary) * 100;
    }

    // Helper Methods
    public function startSession(): void
    {
        $this->update([
            'session_start' => now(),
            'session_end' => null,
        ]);
    }

    public function endSession(string $outcome = null): void
    {
        $updateData = [
            'session_end' => now(),
            'duration_minutes' => $this->session_start ? 
                $this->session_start->diffInMinutes(now()) : null,
        ];

        if ($outcome) {
            $updateData['outcome'] = $outcome;
        }

        $this->update($updateData);
    }

    public function addKeyPoint(string $point): void
    {
        $points = $this->key_points_discussed ?? [];
        $points[] = [
            'point' => $point,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['key_points_discussed' => $points]);
    }

    public function recordEmployerSignal(string $signal, string $interpretation = null): void
    {
        $signals = $this->employer_signals ?? [];
        $signals[] = [
            'signal' => $signal,
            'interpretation' => $interpretation,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['employer_signals' => $signals]);
    }

    public function recordAiIntervention(string $intervention, string $reason = null): void
    {
        $interventions = $this->ai_interventions ?? [];
        $interventions[] = [
            'intervention' => $intervention,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['ai_interventions' => $interventions]);
    }

    public function updateStage(string $stage): void
    {
        $this->update(['current_stage' => $stage]);
    }

    public function recordOutcome(array $data): void
    {
        $this->update([
            'outcome' => $data['outcome'] ?? null,
            'final_agreed_salary' => $data['salary'] ?? null,
            'final_agreed_terms' => $data['terms'] ?? null,
            'outcome_notes' => $data['notes'] ?? null,
        ]);

        $this->endSession($data['outcome'] ?? null);
    }

    public function addFeedback(array $feedback): void
    {
        $this->update([
            'what_worked_well' => $feedback['worked_well'] ?? null,
            'what_to_improve' => $feedback['to_improve'] ?? null,
            'lessons_learned' => $feedback['lessons'] ?? null,
            'user_satisfaction' => $feedback['satisfaction'] ?? null,
        ]);
    }

    public function getSessionSummary(): array
    {
        return [
            'type' => $this->session_type,
            'mode' => $this->communication_mode,
            'start' => $this->session_start?->format('Y-m-d H:i:s'),
            'end' => $this->session_end?->format('Y-m-d H:i:s'),
            'duration' => $this->duration_minutes,
            'stage' => $this->current_stage,
            'outcome' => $this->outcome,
            'is_active' => $this->is_active,
        ];
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'key_points_count' => count($this->key_points_discussed ?? []),
            'employer_signals_count' => count($this->employer_signals ?? []),
            'ai_interventions_count' => count($this->ai_interventions ?? []),
            'salary_gain' => $this->salary_gain,
            'salary_gain_percentage' => $this->salary_gain_percentage,
            'user_satisfaction' => $this->user_satisfaction,
        ];
    }

    public function getEmployerSignalAnalysis(): array
    {
        $signals = $this->employer_signals ?? [];
        
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($signals as $signal) {
            $interpretation = $signal['interpretation'] ?? '';
            
            if (stripos($interpretation, 'positive') !== false || 
                stripos($interpretation, 'good') !== false ||
                stripos($interpretation, 'receptive') !== false) {
                $positive++;
            } elseif (stripos($interpretation, 'negative') !== false || 
                      stripos($interpretation, 'resistant') !== false ||
                      stripos($interpretation, 'concern') !== false) {
                $negative++;
            } else {
                $neutral++;
            }
        }

        return [
            'total' => count($signals),
            'positive' => $positive,
            'negative' => $negative,
            'neutral' => $neutral,
            'sentiment' => $positive > $negative ? 'positive' : ($negative > $positive ? 'negative' : 'neutral'),
        ];
    }

    public function wasSuccessful(): bool
    {
        return $this->outcome === 'successful';
    }

    public function needsFollowUp(): bool
    {
        return in_array($this->outcome, ['pending', 'needs_follow_up']);
    }

    public function getRecommendedNextSteps(): array
    {
        $steps = [];

        if ($this->is_active) {
            $steps[] = 'Continue with current negotiation session';
        }

        if ($this->needsFollowUp()) {
            $steps[] = 'Send follow-up communication within 24-48 hours';
        }

        if ($this->current_stage === 'counter_offer') {
            $steps[] = 'Wait for employer response (typically 2-5 business days)';
        }

        if ($this->current_stage === 'negotiation') {
            $steps[] = 'Consider your walk-away point and alternatives';
        }

        if ($this->current_stage === 'closing') {
            $steps[] = 'Review final terms carefully before accepting';
        }

        return $steps;
    }

    public function getInterventionInsights(): array
    {
        $interventions = $this->ai_interventions ?? [];
        
        $categories = [];
        foreach ($interventions as $intervention) {
            $reason = $intervention['reason'] ?? 'general';
            $categories[$reason] = ($categories[$reason] ?? 0) + 1;
        }

        arsort($categories);

        return [
            'total_interventions' => count($interventions),
            'categories' => $categories,
            'most_common' => array_key_first($categories) ?? null,
        ];
    }
}
