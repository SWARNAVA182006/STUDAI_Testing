<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agent Audit Log Model
 *
 * Tracks all agent-related actions for security, debugging, and compliance.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $agent_configuration_id
 * @property string $action
 * @property string $action_type
 * @property string $status
 * @property int|null $target_job_id
 * @property int|null $auto_application_id
 * @property array|null $input_data
 * @property array|null $output_data
 * @property string|null $error_message
 * @property float|null $duration_ms
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AgentAuditLog extends Model
{
    // Action Types
    public const TYPE_DISCOVERY = 'discovery';
    public const TYPE_MATCHING = 'matching';
    public const TYPE_APPLICATION = 'application';
    public const TYPE_CONFIGURATION = 'configuration';
    public const TYPE_SAFETY = 'safety';

    // Actions
    public const ACTION_JOB_DISCOVERED = 'job_discovered';
    public const ACTION_JOB_MATCHED = 'job_matched';
    public const ACTION_JOB_REJECTED = 'job_rejected';
    public const ACTION_APPLICATION_STARTED = 'application_started';
    public const ACTION_APPLICATION_SUBMITTED = 'application_submitted';
    public const ACTION_APPLICATION_FAILED = 'application_failed';
    public const ACTION_RESUME_CUSTOMIZED = 'resume_customized';
    public const ACTION_COVER_LETTER_GENERATED = 'cover_letter_generated';
    public const ACTION_AGENT_ACTIVATED = 'agent_activated';
    public const ACTION_AGENT_DEACTIVATED = 'agent_deactivated';
    public const ACTION_AGENT_PAUSED = 'agent_paused';
    public const ACTION_AGENT_RESUMED = 'agent_resumed';
    public const ACTION_CONFIG_UPDATED = 'config_updated';
    public const ACTION_EMERGENCY_STOPPED = 'emergency_stopped';
    public const ACTION_EMERGENCY_CLEARED = 'emergency_cleared';
    public const ACTION_APPROVAL_REQUESTED = 'approval_requested';
    public const ACTION_APPROVAL_GRANTED = 'approval_granted';
    public const ACTION_APPROVAL_DENIED = 'approval_denied';

    // Statuses
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_BLOCKED = 'blocked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'agent_configuration_id',
        'action',
        'action_type',
        'status',
        'target_job_id',
        'auto_application_id',
        'input_data',
        'output_data',
        'error_message',
        'duration_ms',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'metadata' => 'array',
        'duration_ms' => 'float',
    ];

    /**
     * Get the user this log belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent configuration.
     */
    public function agentConfiguration(): BelongsTo
    {
        return $this->belongsTo(AgentConfiguration::class);
    }

    /**
     * Get the target job.
     */
    public function targetJob(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'target_job_id');
    }

    /**
     * Get the auto application.
     */
    public function autoApplication(): BelongsTo
    {
        return $this->belongsTo(AutoApplication::class);
    }

    // Scopes

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter successful logs.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to filter failed logs.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for logs within date range.
     */
    public function scopeInDateRange(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope for recent logs.
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper Methods

    /**
     * Check if this log represents a successful action.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if this log represents a failed action.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_ms === null) {
            return 'N/A';
        }

        if ($this->duration_ms >= 1000) {
            return round($this->duration_ms / 1000, 2) . 's';
        }

        return round($this->duration_ms, 2) . 'ms';
    }

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_JOB_DISCOVERED => 'Job Discovered',
            self::ACTION_JOB_MATCHED => 'Job Matched',
            self::ACTION_JOB_REJECTED => 'Job Rejected',
            self::ACTION_APPLICATION_STARTED => 'Application Started',
            self::ACTION_APPLICATION_SUBMITTED => 'Application Submitted',
            self::ACTION_APPLICATION_FAILED => 'Application Failed',
            self::ACTION_RESUME_CUSTOMIZED => 'Resume Customized',
            self::ACTION_COVER_LETTER_GENERATED => 'Cover Letter Generated',
            self::ACTION_AGENT_ACTIVATED => 'Agent Activated',
            self::ACTION_AGENT_DEACTIVATED => 'Agent Deactivated',
            self::ACTION_AGENT_PAUSED => 'Agent Paused',
            self::ACTION_AGENT_RESUMED => 'Agent Resumed',
            self::ACTION_CONFIG_UPDATED => 'Configuration Updated',
            self::ACTION_EMERGENCY_STOPPED => 'Emergency Stopped',
            self::ACTION_EMERGENCY_CLEARED => 'Emergency Cleared',
            self::ACTION_APPROVAL_REQUESTED => 'Approval Requested',
            self::ACTION_APPROVAL_GRANTED => 'Approval Granted',
            self::ACTION_APPROVAL_DENIED => 'Approval Denied',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get statistics for a user.
     */
    public static function getUserStats(int $userId, int $days = 30): array
    {
        $query = static::forUser($userId)
            ->where('created_at', '>=', now()->subDays($days));

        return [
            'total_actions' => $query->count(),
            'successful' => (clone $query)->successful()->count(),
            'failed' => (clone $query)->failed()->count(),
            'jobs_discovered' => (clone $query)->ofAction(self::ACTION_JOB_DISCOVERED)->count(),
            'applications_submitted' => (clone $query)->ofAction(self::ACTION_APPLICATION_SUBMITTED)->count(),
            'applications_failed' => (clone $query)->ofAction(self::ACTION_APPLICATION_FAILED)->count(),
        ];
    }

    /**
     * Get available action types.
     */
    public static function getActionTypes(): array
    {
        return [
            self::TYPE_DISCOVERY => 'Discovery',
            self::TYPE_MATCHING => 'Matching',
            self::TYPE_APPLICATION => 'Application',
            self::TYPE_CONFIGURATION => 'Configuration',
            self::TYPE_SAFETY => 'Safety',
        ];
    }
}
