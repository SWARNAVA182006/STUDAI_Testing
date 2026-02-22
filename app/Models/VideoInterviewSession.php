<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class VideoInterviewSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'job_id',
        'company_id',
        'interview_session_id',
        'title',
        'description',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'expires_at',
        'max_duration_minutes',
        'actual_duration_seconds',
        'room_id',
        'room_token',
        'participants',
        'has_screen_share',
        'is_recording_enabled',
        'ai_analysis_summary',
        'overall_score',
        'performance_breakdown',
        'settings',
        'allow_retakes',
        'max_retakes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'participants' => 'array',
        'ai_analysis_summary' => 'array',
        'overall_score' => 'decimal:2',
        'performance_breakdown' => 'array',
        'settings' => 'array',
        'has_screen_share' => 'boolean',
        'is_recording_enabled' => 'boolean',
        'allow_retakes' => 'boolean',
        'max_duration_minutes' => 'integer',
        'actual_duration_seconds' => 'integer',
        'max_retakes' => 'integer',
    ];

    // Types
    public const TYPE_ASYNC = 'async';
    public const TYPE_LIVE = 'live';
    public const TYPE_MOCK = 'mock';

    public const TYPES = [
        self::TYPE_ASYNC => 'Asynchronous (Record & Submit)',
        self::TYPE_LIVE => 'Live Video Interview',
        self::TYPE_MOCK => 'Mock Practice',
    ];

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    // Boot
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $session) {
            if (empty($session->room_id) && $session->type === self::TYPE_LIVE) {
                $session->room_id = 'room_' . Str::uuid()->toString();
                $session->room_token = Str::random(64);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(VideoInterviewQuestion::class)->orderBy('order');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(VideoInterviewRecording::class);
    }

    public function room(): HasOne
    {
        return $this->hasOne(VideoInterviewRoom::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(VideoInterviewInvitation::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeAsync($query)
    {
        return $query->where('type', self::TYPE_ASYNC);
    }

    public function scopeLive($query)
    {
        return $query->where('type', self::TYPE_LIVE);
    }

    public function scopeMock($query)
    {
        return $query->where('type', self::TYPE_MOCK);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->type === self::TYPE_LIVE;
    }

    public function getIsAsyncAttribute(): bool
    {
        return $this->type === self::TYPE_ASYNC;
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        }

        $totalQuestions = $this->questions()->count();
        if ($totalQuestions === 0) {
            return 0;
        }

        $answeredQuestions = $this->recordings()
            ->whereNotNull('video_interview_question_id')
            ->where('status', 'ready')
            ->distinct('video_interview_question_id')
            ->count();

        return (int) round(($answeredQuestions / $totalQuestions) * 100);
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->actual_duration_seconds) {
            return '--:--';
        }

        $minutes = floor($this->actual_duration_seconds / 60);
        $seconds = $this->actual_duration_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getTypeLabellAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    // Methods
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : null;
        
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'actual_duration_seconds' => $duration,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->is_expired;
    }

    public function canRetake(): bool
    {
        if (!$this->allow_retakes) {
            return false;
        }

        $attempts = $this->recordings()
            ->where('recording_type', 'response')
            ->max('attempt_number') ?? 0;

        return $attempts < $this->max_retakes;
    }

    public function getAnalysisSummary(): array
    {
        if ($this->ai_analysis_summary) {
            return $this->ai_analysis_summary;
        }

        // Calculate from individual analyses
        $analyses = VideoInterviewAnalysis::whereHas('recording', function ($query) {
            $query->where('video_interview_session_id', $this->id);
        })->where('status', 'completed')->get();

        if ($analyses->isEmpty()) {
            return [];
        }

        return [
            'overall_score' => round($analyses->avg('overall_score'), 1),
            'content_score' => round($analyses->avg('content_score'), 1),
            'confidence_score' => round($analyses->avg('confidence_score'), 1),
            'speech_pace_wpm' => round($analyses->avg('speech_pace_wpm'), 0),
            'eye_contact_score' => round($analyses->avg('eye_contact_score'), 1),
            'total_filler_words' => $analyses->sum('filler_word_count'),
            'questions_analyzed' => $analyses->count(),
        ];
    }
}
