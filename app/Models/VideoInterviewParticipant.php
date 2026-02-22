<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoInterviewParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_interview_room_id',
        'user_id',
        'role',
        'status',
        'display_name',
        'audio_enabled',
        'video_enabled',
        'screen_sharing',
        'connection_id',
        'device_info',
        'ip_address',
        'joined_at',
        'left_at',
        'total_duration_seconds',
    ];

    protected $casts = [
        'audio_enabled' => 'boolean',
        'video_enabled' => 'boolean',
        'screen_sharing' => 'boolean',
        'device_info' => 'array',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'total_duration_seconds' => 'integer',
    ];

    // Roles
    public const ROLE_INTERVIEWER = 'interviewer';
    public const ROLE_CANDIDATE = 'candidate';
    public const ROLE_OBSERVER = 'observer';

    public const ROLES = [
        self::ROLE_INTERVIEWER => 'Interviewer',
        self::ROLE_CANDIDATE => 'Candidate',
        self::ROLE_OBSERVER => 'Observer',
    ];

    // Statuses
    public const STATUS_INVITED = 'invited';
    public const STATUS_JOINED = 'joined';
    public const STATUS_LEFT = 'left';
    public const STATUS_DISCONNECTED = 'disconnected';

    // Relationships
    public function room(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewRoom::class, 'video_interview_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_JOINED);
    }

    public function scopeInterviewers($query)
    {
        return $query->where('role', self::ROLE_INTERVIEWER);
    }

    public function scopeCandidates($query)
    {
        return $query->where('role', self::ROLE_CANDIDATE);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_JOINED;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->total_duration_seconds) {
            return '--:--';
        }

        $hours = floor($this->total_duration_seconds / 3600);
        $minutes = floor(($this->total_duration_seconds % 3600) / 60);
        $seconds = $this->total_duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // Methods
    public function join(): void
    {
        $this->update([
            'status' => self::STATUS_JOINED,
            'joined_at' => now(),
        ]);

        $this->room->incrementParticipants();
    }

    public function leave(): void
    {
        $duration = $this->joined_at ? now()->diffInSeconds($this->joined_at) : 0;
        
        $this->update([
            'status' => self::STATUS_LEFT,
            'left_at' => now(),
            'total_duration_seconds' => $duration,
        ]);

        $this->room->decrementParticipants();
    }

    public function disconnect(): void
    {
        $duration = $this->joined_at ? now()->diffInSeconds($this->joined_at) : 0;
        
        $this->update([
            'status' => self::STATUS_DISCONNECTED,
            'left_at' => now(),
            'total_duration_seconds' => $duration,
        ]);

        $this->room->decrementParticipants();
    }

    public function toggleAudio(): void
    {
        $this->update(['audio_enabled' => !$this->audio_enabled]);
    }

    public function toggleVideo(): void
    {
        $this->update(['video_enabled' => !$this->video_enabled]);
    }

    public function startScreenShare(): void
    {
        $this->update(['screen_sharing' => true]);
    }

    public function stopScreenShare(): void
    {
        $this->update(['screen_sharing' => false]);
    }
}
