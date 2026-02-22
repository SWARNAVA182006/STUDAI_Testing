<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'auto_application_id',
        'discovered_job_id',
        'action_type',
        'description',
        'metadata',
        'severity',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function autoApplication()
    {
        return $this->belongsTo(AutoApplication::class);
    }

    public function discoveredJob()
    {
        return $this->belongsTo(DiscoveredJob::class);
    }

    public static function log(
        int $userId,
        string $actionType,
        string $description,
        ?int $applicationId = null,
        ?int $jobId = null,
        array $metadata = [],
        string $severity = 'info'
    ): void {
        static::create([
            'user_id' => $userId,
            'auto_application_id' => $applicationId,
            'discovered_job_id' => $jobId,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata,
            'severity' => $severity,
            'created_at' => now(),
        ]);
    }

    public function getIcon(): string
    {
        return match ($this->action_type) {
            'job_discovered' => '🔍',
            'job_matched' => '✅',
            'application_created' => '📝',
            'application_submitted' => '📤',
            'resume_optimized' => '⚡',
            'cover_letter_generated' => '✍️',
            'follow_up_sent' => '📧',
            'status_updated' => '🔄',
            'response_received' => '📩',
            'interview_scheduled' => '📅',
            'offer_received' => '🎉',
            'application_rejected' => '❌',
            'error' => '⚠️',
            default => '📌',
        };
    }

    public function getColor(): string
    {
        return match ($this->severity) {
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            default => 'blue',
        };
    }
}
