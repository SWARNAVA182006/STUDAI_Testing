<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VideoInterviewRecording extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'video_interview_session_id',
        'video_interview_question_id',
        'user_id',
        'recording_type',
        'attempt_number',
        'status',
        'storage_disk',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'duration_seconds',
        'thumbnail_path',
        'thumbnail_sprites',
        'playback_url',
        'download_url',
        'url_expires_at',
        'transcription',
        'transcription_segments',
        'transcription_language',
        'transcription_status',
        'processing_metadata',
        'processed_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'file_size' => 'integer',
        'duration_seconds' => 'integer',
        'thumbnail_sprites' => 'array',
        'transcription_segments' => 'array',
        'processing_metadata' => 'array',
        'url_expires_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Recording types
    public const TYPE_RESPONSE = 'response';
    public const TYPE_FULL_SESSION = 'full_session';
    public const TYPE_SCREEN_SHARE = 'screen_share';

    // Statuses
    public const STATUS_UPLOADING = 'uploading';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DELETED = 'deleted';

    // Transcription statuses
    public const TRANSCRIPTION_PENDING = 'pending';
    public const TRANSCRIPTION_PROCESSING = 'processing';
    public const TRANSCRIPTION_COMPLETED = 'completed';
    public const TRANSCRIPTION_FAILED = 'failed';

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewSession::class, 'video_interview_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(VideoInterviewQuestion::class, 'video_interview_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(VideoInterviewAnalysis::class);
    }

    // Scopes
    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function scopeResponses($query)
    {
        return $query->where('recording_type', self::TYPE_RESPONSE);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('video_interview_session_id', $sessionId);
    }

    // Accessors
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '--:--';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getIsReadyAttribute(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function getHasTranscriptionAttribute(): bool
    {
        return $this->transcription_status === self::TRANSCRIPTION_COMPLETED
            && !empty($this->transcription);
    }

    public function getPlaybackUrlAttribute(): ?string
    {
        $url = $this->attributes['playback_url'] ?? null;

        // Check if URL has expired
        if ($url && $this->url_expires_at && $this->url_expires_at->isPast()) {
            // Generate new URL
            return $this->generatePlaybackUrl();
        }

        return $url;
    }

    // Methods
    public function generatePlaybackUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $disk = Storage::disk($this->storage_disk);
        
        if (method_exists($disk, 'temporaryUrl')) {
            $url = $disk->temporaryUrl($this->file_path, now()->addHours(2));
            
            $this->update([
                'playback_url' => $url,
                'url_expires_at' => now()->addHours(2),
            ]);

            return $url;
        }

        return $disk->url($this->file_path);
    }

    public function generateThumbnail(): ?string
    {
        // This would use FFmpeg to generate thumbnail
        // Placeholder for actual implementation
        return null;
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk($this->storage_disk)->exists($this->file_path)) {
            Storage::disk($this->storage_disk)->delete($this->file_path);
        }

        if ($this->thumbnail_path && Storage::disk($this->storage_disk)->exists($this->thumbnail_path)) {
            Storage::disk($this->storage_disk)->delete($this->thumbnail_path);
        }

        $this->update(['status' => self::STATUS_DELETED]);

        return true;
    }

    public function getWordCount(): int
    {
        if (!$this->transcription) {
            return 0;
        }

        return str_word_count($this->transcription);
    }

    public function getWordsPerMinute(): ?float
    {
        if (!$this->duration_seconds || !$this->transcription) {
            return null;
        }

        $wordCount = $this->getWordCount();
        $minutes = $this->duration_seconds / 60;

        return round($wordCount / $minutes, 1);
    }
}
