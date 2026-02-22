<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Models\AgentAuditLog;
use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Agent Audit Service
 *
 * Centralized service for logging all agent-related actions.
 * Provides consistent audit trail for security, compliance, and debugging.
 *
 * Usage:
 *   app(AgentAuditService::class)->logJobDiscovered($user, $job, $metadata);
 *   app(AgentAuditService::class)->logApplicationSubmitted($user, $application);
 */
class AgentAuditService
{
    /**
     * Current request instance.
     */
    protected ?Request $request;

    /**
     * Timer start time for duration tracking.
     */
    protected ?float $timerStart = null;

    /**
     * Create a new AgentAuditService instance.
     */
    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Start a timer for duration tracking.
     */
    public function startTimer(): self
    {
        $this->timerStart = microtime(true);
        return $this;
    }

    /**
     * Get elapsed time since timer start.
     */
    protected function getElapsedMs(): ?float
    {
        if ($this->timerStart === null) {
            return null;
        }

        return (microtime(true) - $this->timerStart) * 1000;
    }

    /**
     * Log a generic agent action.
     */
    public function log(
        User $user,
        string $action,
        string $actionType,
        string $status = AgentAuditLog::STATUS_SUCCESS,
        array $data = []
    ): AgentAuditLog {
        $agentConfig = AgentConfiguration::where('user_id', $user->id)->first();

        $log = AgentAuditLog::create([
            'user_id' => $user->id,
            'agent_configuration_id' => $agentConfig?->id,
            'action' => $action,
            'action_type' => $actionType,
            'status' => $status,
            'target_job_id' => $data['job_id'] ?? null,
            'auto_application_id' => $data['application_id'] ?? null,
            'input_data' => $data['input'] ?? null,
            'output_data' => $data['output'] ?? null,
            'error_message' => $data['error'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? $this->getElapsedMs(),
            'ip_address' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
            'metadata' => $data['metadata'] ?? null,
        ]);

        // Reset timer
        $this->timerStart = null;

        return $log;
    }

    // Discovery Actions

    /**
     * Log job discovery action.
     */
    public function logJobDiscovered(User $user, JobListing $job, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_JOB_DISCOVERED,
            AgentAuditLog::TYPE_DISCOVERY,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'output' => [
                    'job_title' => $job->title,
                    'company' => $job->company?->name,
                ],
                'metadata' => $metadata,
            ]
        );
    }

    // Matching Actions

    /**
     * Log job match action.
     */
    public function logJobMatched(User $user, JobListing $job, float $matchScore, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_JOB_MATCHED,
            AgentAuditLog::TYPE_MATCHING,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'output' => [
                    'match_score' => $matchScore,
                    'job_title' => $job->title,
                ],
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log job rejection action.
     */
    public function logJobRejected(User $user, JobListing $job, string $reason, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_JOB_REJECTED,
            AgentAuditLog::TYPE_MATCHING,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'output' => [
                    'rejection_reason' => $reason,
                    'job_title' => $job->title,
                ],
                'metadata' => $metadata,
            ]
        );
    }

    // Application Actions

    /**
     * Log application started.
     */
    public function logApplicationStarted(User $user, JobListing $job, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPLICATION_STARTED,
            AgentAuditLog::TYPE_APPLICATION,
            AgentAuditLog::STATUS_PENDING,
            [
                'job_id' => $job->id,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log successful application submission.
     */
    public function logApplicationSubmitted(User $user, AutoApplication $application, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPLICATION_SUBMITTED,
            AgentAuditLog::TYPE_APPLICATION,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $application->job_listing_id,
                'application_id' => $application->id,
                'output' => [
                    'application_id' => $application->id,
                ],
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log failed application.
     */
    public function logApplicationFailed(User $user, JobListing $job, string $error, array $metadata = []): AgentAuditLog
    {
        Log::warning('Agent application failed', [
            'user_id' => $user->id,
            'job_id' => $job->id,
            'error' => $error,
        ]);

        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPLICATION_FAILED,
            AgentAuditLog::TYPE_APPLICATION,
            AgentAuditLog::STATUS_FAILED,
            [
                'job_id' => $job->id,
                'error' => $error,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log resume customization.
     */
    public function logResumeCustomized(User $user, JobListing $job, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_RESUME_CUSTOMIZED,
            AgentAuditLog::TYPE_APPLICATION,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log cover letter generation.
     */
    public function logCoverLetterGenerated(User $user, JobListing $job, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_COVER_LETTER_GENERATED,
            AgentAuditLog::TYPE_APPLICATION,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'metadata' => $metadata,
            ]
        );
    }

    // Configuration Actions

    /**
     * Log agent activation.
     */
    public function logAgentActivated(User $user, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_AGENT_ACTIVATED,
            AgentAuditLog::TYPE_CONFIGURATION,
            AgentAuditLog::STATUS_SUCCESS,
            ['metadata' => $metadata]
        );
    }

    /**
     * Log agent deactivation.
     */
    public function logAgentDeactivated(User $user, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_AGENT_DEACTIVATED,
            AgentAuditLog::TYPE_CONFIGURATION,
            AgentAuditLog::STATUS_SUCCESS,
            ['metadata' => $metadata]
        );
    }

    /**
     * Log agent paused.
     */
    public function logAgentPaused(User $user, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_AGENT_PAUSED,
            AgentAuditLog::TYPE_CONFIGURATION,
            AgentAuditLog::STATUS_SUCCESS,
            ['metadata' => $metadata]
        );
    }

    /**
     * Log agent resumed.
     */
    public function logAgentResumed(User $user, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_AGENT_RESUMED,
            AgentAuditLog::TYPE_CONFIGURATION,
            AgentAuditLog::STATUS_SUCCESS,
            ['metadata' => $metadata]
        );
    }

    /**
     * Log configuration update.
     */
    public function logConfigUpdated(User $user, array $changes, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_CONFIG_UPDATED,
            AgentAuditLog::TYPE_CONFIGURATION,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'input' => $changes,
                'metadata' => $metadata,
            ]
        );
    }

    // Safety Actions

    /**
     * Log emergency stop.
     */
    public function logEmergencyStopped(User $user, int $stoppedBy, string $reason): AgentAuditLog
    {
        Log::critical('Agent emergency stopped', [
            'user_id' => $user->id,
            'stopped_by' => $stoppedBy,
            'reason' => $reason,
        ]);

        return $this->log(
            $user,
            AgentAuditLog::ACTION_EMERGENCY_STOPPED,
            AgentAuditLog::TYPE_SAFETY,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'metadata' => [
                    'stopped_by' => $stoppedBy,
                    'reason' => $reason,
                ],
            ]
        );
    }

    /**
     * Log emergency stop cleared.
     */
    public function logEmergencyCleared(User $user, int $clearedBy): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_EMERGENCY_CLEARED,
            AgentAuditLog::TYPE_SAFETY,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'metadata' => [
                    'cleared_by' => $clearedBy,
                ],
            ]
        );
    }

    /**
     * Log approval request.
     */
    public function logApprovalRequested(User $user, JobListing $job, array $metadata = []): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPROVAL_REQUESTED,
            AgentAuditLog::TYPE_SAFETY,
            AgentAuditLog::STATUS_PENDING,
            [
                'job_id' => $job->id,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Log approval granted.
     */
    public function logApprovalGranted(User $user, JobListing $job, int $approvedBy): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPROVAL_GRANTED,
            AgentAuditLog::TYPE_SAFETY,
            AgentAuditLog::STATUS_SUCCESS,
            [
                'job_id' => $job->id,
                'metadata' => [
                    'approved_by' => $approvedBy,
                ],
            ]
        );
    }

    /**
     * Log approval denied.
     */
    public function logApprovalDenied(User $user, JobListing $job, int $deniedBy, string $reason): AgentAuditLog
    {
        return $this->log(
            $user,
            AgentAuditLog::ACTION_APPROVAL_DENIED,
            AgentAuditLog::TYPE_SAFETY,
            AgentAuditLog::STATUS_BLOCKED,
            [
                'job_id' => $job->id,
                'metadata' => [
                    'denied_by' => $deniedBy,
                    'reason' => $reason,
                ],
            ]
        );
    }

    // Reporting Methods

    /**
     * Get user activity summary.
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        return AgentAuditLog::getUserStats($userId, $days);
    }

    /**
     * Get recent audit logs for a user.
     */
    public function getRecentLogs(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AgentAuditLog::forUser($userId)
            ->with(['targetJob', 'autoApplication'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed applications for review.
     */
    public function getFailedApplications(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return AgentAuditLog::forUser($userId)
            ->ofAction(AgentAuditLog::ACTION_APPLICATION_FAILED)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('targetJob')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get system-wide audit statistics.
     */
    public function getSystemStats(int $hours = 24): array
    {
        $query = AgentAuditLog::recent($hours);

        return [
            'total_actions' => (clone $query)->count(),
            'successful' => (clone $query)->successful()->count(),
            'failed' => (clone $query)->failed()->count(),
            'by_type' => (clone $query)
                ->selectRaw('action_type, count(*) as count')
                ->groupBy('action_type')
                ->pluck('count', 'action_type')
                ->toArray(),
            'by_action' => (clone $query)
                ->selectRaw('action, count(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'unique_users' => (clone $query)->distinct('user_id')->count('user_id'),
        ];
    }
}
