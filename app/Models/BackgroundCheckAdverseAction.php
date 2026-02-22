<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheckAdverseAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'background_check_id',
        'initiated_by',
        'pre_adverse_sent_at',
        'pre_adverse_reason',
        'pre_adverse_email_path',
        'waiting_period_days',
        'waiting_period_ends_at',
        'candidate_disputed',
        'dispute_reason',
        'dispute_received_at',
        'final_action_taken',
        'final_adverse_sent_at',
        'final_adverse_reason',
        'final_adverse_email_path',
        'outcome',
        'outcome_notes',
    ];

    protected $casts = [
        'pre_adverse_sent_at' => 'datetime',
        'waiting_period_ends_at' => 'datetime',
        'candidate_disputed' => 'boolean',
        'dispute_received_at' => 'datetime',
        'final_action_taken' => 'boolean',
        'final_adverse_sent_at' => 'datetime',
    ];

    // Relationships
    public function backgroundCheck(): BelongsTo
    {
        return $this->belongsTo(BackgroundCheck::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    // Status helpers
    public function isInWaitingPeriod(): bool
    {
        return $this->pre_adverse_sent_at && 
               $this->waiting_period_ends_at && 
               $this->waiting_period_ends_at->isFuture() &&
               !$this->final_action_taken;
    }

    public function waitingPeriodEnded(): bool
    {
        return $this->waiting_period_ends_at && $this->waiting_period_ends_at->isPast();
    }

    public function canSendFinalAction(): bool
    {
        return $this->pre_adverse_sent_at && 
               $this->waitingPeriodEnded() && 
               !$this->final_action_taken;
    }

    public function isCompleted(): bool
    {
        return $this->outcome !== null;
    }

    public function wasUpheld(): bool
    {
        return $this->outcome === 'upheld';
    }

    public function wasWithdrawn(): bool
    {
        return $this->outcome === 'withdrawn';
    }

    // Computed attributes
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->waiting_period_ends_at || $this->waiting_period_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->waiting_period_ends_at, false);
    }

    public function getStatusAttribute(): string
    {
        if ($this->isCompleted()) {
            return $this->outcome;
        }

        if ($this->final_action_taken) {
            return 'final_sent';
        }

        if ($this->candidate_disputed) {
            return 'disputed';
        }

        if ($this->isInWaitingPeriod()) {
            return 'waiting_period';
        }

        if ($this->canSendFinalAction()) {
            return 'ready_for_final';
        }

        return 'pre_adverse_sent';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pre_adverse_sent' => 'Pre-Adverse Notice Sent',
            'waiting_period' => 'In Waiting Period',
            'disputed' => 'Candidate Disputed',
            'ready_for_final' => 'Ready for Final Decision',
            'final_sent' => 'Final Notice Sent',
            'upheld' => 'Adverse Action Upheld',
            'withdrawn' => 'Adverse Action Withdrawn',
            default => 'Unknown',
        };
    }
}
