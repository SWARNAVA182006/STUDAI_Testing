<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'job_match_id',
        'discovered_job_id',
        'customized_resume_path',
        'customized_resume_content',
        'cover_letter',
        'screening_answers',
        'custom_fields',
        'resume_changes',
        'keywords_optimized',
        'ats_optimization_score',
        'submission_method',
        'status',
        'submission_response',
        'submitted_at',
        'application_status',
        'status_updated_at',
        'status_history',
        'follow_up_sent',
        'follow_up_at',
        'follow_up_count',
        'got_response',
        'got_interview',
        'got_offer',
        'rejection_reason',
        'feedback',
    ];

    protected $casts = [
        'screening_answers' => 'array',
        'custom_fields' => 'array',
        'resume_changes' => 'array',
        'keywords_optimized' => 'array',
        'ats_optimization_score' => 'float',
        'submitted_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'status_history' => 'array',
        'follow_up_sent' => 'boolean',
        'follow_up_at' => 'datetime',
        'follow_up_count' => 'integer',
        'got_response' => 'boolean',
        'got_interview' => 'boolean',
        'got_offer' => 'boolean',
        'feedback' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobMatch()
    {
        return $this->belongsTo(JobMatch::class);
    }

    public function discoveredJob()
    {
        return $this->belongsTo(DiscoveredJob::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ApplicationActivityLog::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeAwaitingResponse($query)
    {
        return $query->where('status', 'submitted')
            ->where('application_status', 'submitted')
            ->where('got_response', false);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('application_status', [
            'viewed',
            'screening',
            'interviewing'
        ]);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('application_status', ['offered', 'interviewing']);
    }

    public function scopeNeedingFollowUp($query)
    {
        return $query->where('follow_up_sent', false)
            ->where('status', 'submitted')
            ->where('application_status', 'submitted')
            ->where('follow_up_at', '<=', now());
    }

    // Business Logic
    public function submit(): bool
    {
        try {
            // Mark as submitted
            $this->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'application_status' => 'submitted',
                'status_updated_at' => now(),
            ]);

            $this->addToStatusHistory('submitted', 'Application submitted by autonomous agent');
            
            // Schedule follow-up if enabled
            if ($this->user->agentConfiguration?->auto_follow_up) {
                $this->scheduleFollowUp();
            }

            return true;

        } catch (\Exception $e) {
            $this->update([
                'status' => 'failed',
                'submission_response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updateStatus(string $newStatus, ?string $notes = null): void
    {
        $oldStatus = $this->application_status;

        $this->update([
            'application_status' => $newStatus,
            'status_updated_at' => now(),
        ]);

        $this->addToStatusHistory($newStatus, $notes);

        // Update learning flags
        match ($newStatus) {
            'viewed' => $this->update(['got_response' => true]),
            'interviewing' => $this->update(['got_interview' => true]),
            'offered' => $this->update(['got_offer' => true]),
            default => null,
        };

        // Dispatch status change event for notifications
        event(new \App\Events\ApplicationStatusChanged($this, $oldStatus, $newStatus));
    }

    public function addToStatusHistory(string $status, ?string $notes = null): void
    {
        $history = $this->status_history ?? [];
        
        $history[] = [
            'status' => $status,
            'notes' => $notes,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['status_history' => $history]);
    }

    public function scheduleFollowUp(): void
    {
        $days = $this->user->agentConfiguration?->follow_up_days ?? 7;
        
        $this->update([
            'follow_up_at' => now()->addDays($days),
        ]);
    }

    public function sendFollowUp(): bool
    {
        // Implementation would actually send follow-up email
        
        $this->update([
            'follow_up_sent' => true,
            'follow_up_count' => $this->follow_up_count + 1,
        ]);

        $this->addToStatusHistory('follow_up_sent', "Follow-up #{$this->follow_up_count} sent");

        return true;
    }

    public function getTimelineData(): array
    {
        return collect($this->status_history ?? [])->map(function ($entry) {
            return [
                'status' => $entry['status'],
                'notes' => $entry['notes'] ?? null,
                'timestamp' => $entry['timestamp'],
                'icon' => $this->getStatusIcon($entry['status']),
                'color' => $this->getStatusColor($entry['status']),
            ];
        })->toArray();
    }

    public function getDaysSinceSubmission(): int
    {
        if (!$this->submitted_at) {
            return 0;
        }

        return now()->diffInDays($this->submitted_at);
    }

    public function isGhosted(): bool
    {
        return $this->application_status === 'submitted' 
            && !$this->got_response 
            && $this->getDaysSinceSubmission() > 30;
    }

    public function isSuccessful(): bool
    {
        return in_array($this->application_status, ['offered', 'interviewing']);
    }

    public function getSuccessScore(): float
    {
        $score = 0;

        if ($this->got_response) $score += 25;
        if ($this->got_interview) $score += 50;
        if ($this->got_offer) $score += 100;

        return min(100, $score);
    }

    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'submitted' => '📤',
            'viewed' => '👀',
            'screening' => '📋',
            'interviewing' => '🎤',
            'offered' => '🎉',
            'rejected' => '❌',
            'withdrawn' => '↩️',
            'ghosted' => '👻',
            'follow_up_sent' => '📧',
            default => '📌',
        };
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'submitted' => 'blue',
            'viewed' => 'cyan',
            'screening' => 'yellow',
            'interviewing' => 'purple',
            'offered' => 'green',
            'rejected' => 'red',
            'withdrawn' => 'gray',
            'ghosted' => 'gray',
            default => 'gray',
        };
    }

    public function getOptimizationSummary(): string
    {
        $changes = count($this->resume_changes ?? []);
        $keywords = count($this->keywords_optimized ?? []);
        $score = $this->ats_optimization_score ?? 0;

        return "Resume: {$changes} changes | Keywords: {$keywords} added | ATS Score: {$score}%";
    }

    public function getResumePath(): ?string
    {
        return $this->customized_resume_path;
    }

    public function hasCustomizedResume(): bool
    {
        return !empty($this->customized_resume_path) || !empty($this->customized_resume_content);
    }

    public function hasCoverLetter(): bool
    {
        return !empty($this->cover_letter);
    }
}
