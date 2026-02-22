<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoInterviewRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_interview_session_id',
        'room_id',
        'room_name',
        'status',
        'ice_servers',
        'room_config',
        'max_participants',
        'current_participants',
        'participant_list',
        'chat_enabled',
        'screen_share_enabled',
        'recording_enabled',
        'whiteboard_enabled',
        'is_recording',
        'recording_started_at',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'ice_servers' => 'array',
        'room_config' => 'array',
        'participant_list' => 'array',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'chat_enabled' => 'boolean',
        'screen_share_enabled' => 'boolean',
        'recording_enabled' => 'boolean',
        'whiteboard_enabled' => 'boolean',
        'is_recording' => 'boolean',
        'recording_started_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Statuses
    public const STATUS_CREATED = 'created';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewSession::class, 'video_interview_session_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(VideoInterviewParticipant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsFullAttribute(): bool
    {
        return $this->current_participants >= $this->max_participants;
    }

    public function getCanJoinAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_CREATED, self::STATUS_WAITING, self::STATUS_ACTIVE])
            && !$this->is_full;
    }

    // Methods
    public function open(): void
    {
        $this->update([
            'status' => self::STATUS_WAITING,
            'opened_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_ENDED,
            'closed_at' => now(),
            'is_recording' => false,
        ]);

        // Update all participants
        $this->participants()
            ->whereNull('left_at')
            ->update([
                'status' => 'left',
                'left_at' => now(),
            ]);
    }

    public function startRecording(): void
    {
        $this->update([
            'is_recording' => true,
            'recording_started_at' => now(),
        ]);
    }

    public function stopRecording(): void
    {
        $this->update([
            'is_recording' => false,
        ]);
    }

    public function incrementParticipants(): void
    {
        $this->increment('current_participants');
    }

    public function decrementParticipants(): void
    {
        $this->decrement('current_participants');
    }

    public function getDefaultIceServers(): array
    {
        return [
            ['urls' => 'stun:stun.l.google.com:19302'],
            ['urls' => 'stun:stun1.l.google.com:19302'],
            ['urls' => 'stun:stun2.l.google.com:19302'],
        ];
    }
}
