<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AgentAuditLog;
use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Models\JobListing;
use App\Models\JobMatch;
use App\Models\User;
use App\Services\Agent\AgentAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AgentAuditService $auditService;
    protected User $user;
    protected AgentConfiguration $agentConfig;
    protected JobListing $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = app(AgentAuditService::class);
        $this->user = User::factory()->create();
        $this->agentConfig = AgentConfiguration::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $this->job = JobListing::factory()->create();
    }

    public function test_log_job_discovered(): void
    {
        $log = $this->auditService->logJobDiscovered($this->user, $this->job, [
            'source' => 'linkedin',
            'match_score' => 85.5,
        ]);

        $this->assertInstanceOf(AgentAuditLog::class, $log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals(AgentAuditLog::ACTION_JOB_DISCOVERED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_DISCOVERY, $log->action_type);
        $this->assertEquals($this->job->id, $log->target_job_id);
        $this->assertEquals('linkedin', $log->metadata['source']);
    }

    public function test_log_application_submitted(): void
    {
        $application = AutoApplication::factory()->create([
            'user_id' => $this->user->id,
            'job_listing_id' => $this->job->id,
        ]);

        $log = $this->auditService->logApplicationSubmitted($this->user, $application, [
            'resume_customized' => true,
            'cover_letter_generated' => true,
        ]);

        $this->assertEquals(AgentAuditLog::ACTION_APPLICATION_SUBMITTED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_APPLICATION, $log->action_type);
        $this->assertEquals($application->id, $log->auto_application_id);
        $this->assertTrue($log->metadata['resume_customized']);
    }

    public function test_log_emergency_stopped(): void
    {
        $admin = User::factory()->create();

        $log = $this->auditService->logEmergencyStopped($this->user, $admin->id, 'Suspicious activity detected');

        $this->assertEquals(AgentAuditLog::ACTION_EMERGENCY_STOPPED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_SAFETY, $log->action_type);
        $this->assertEquals('Suspicious activity detected', $log->error_message);
        $this->assertEquals($admin->id, $log->metadata['stopped_by']);
    }

    public function test_log_approval_granted(): void
    {
        $approver = User::factory()->create();

        $log = $this->auditService->logApprovalGranted($this->user, $this->job, $approver->id);

        $this->assertEquals(AgentAuditLog::ACTION_APPROVAL_GRANTED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_SAFETY, $log->action_type);
        $this->assertEquals($this->job->id, $log->target_job_id);
        $this->assertEquals($approver->id, $log->metadata['approved_by']);
    }

    public function test_log_approval_rejected(): void
    {
        $rejecter = User::factory()->create();

        $log = $this->auditService->logApprovalRejected($this->user, $this->job, $rejecter->id, 'Low match score');

        $this->assertEquals(AgentAuditLog::ACTION_APPROVAL_REJECTED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_SAFETY, $log->action_type);
        $this->assertEquals('Low match score', $log->metadata['reason']);
    }

    public function test_log_match_calculated(): void
    {
        $log = $this->auditService->logMatchCalculated($this->user, $this->job, 85.5, [
            'skill_match' => 90,
            'experience_match' => 80,
        ]);

        $this->assertEquals(AgentAuditLog::ACTION_MATCH_CALCULATED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_MATCHING, $log->action_type);
        $this->assertEquals(85.5, $log->metadata['match_score']);
    }

    public function test_log_config_changed(): void
    {
        $log = $this->auditService->logConfigChanged($this->user, [
            'old_settings' => ['auto_apply' => false],
            'new_settings' => ['auto_apply' => true],
        ]);

        $this->assertEquals(AgentAuditLog::ACTION_CONFIG_CHANGED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_CONFIGURATION, $log->action_type);
        $this->assertFalse($log->metadata['old_settings']['auto_apply']);
        $this->assertTrue($log->metadata['new_settings']['auto_apply']);
    }

    public function test_log_agent_activated(): void
    {
        $log = $this->auditService->logAgentActivated($this->user);

        $this->assertEquals(AgentAuditLog::ACTION_AGENT_ACTIVATED, $log->action);
        $this->assertEquals(AgentAuditLog::TYPE_CONFIGURATION, $log->action_type);
        $this->assertEquals('success', $log->status);
    }

    public function test_log_agent_deactivated(): void
    {
        $log = $this->auditService->logAgentDeactivated($this->user, 'User requested');

        $this->assertEquals(AgentAuditLog::ACTION_AGENT_DEACTIVATED, $log->action);
        $this->assertEquals('User requested', $log->metadata['reason']);
    }

    public function test_log_with_error(): void
    {
        $log = $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_APPLICATION_SUBMITTED,
            actionType: AgentAuditLog::TYPE_APPLICATION,
            status: 'failed',
            errorMessage: 'Connection timeout',
        );

        $this->assertEquals('failed', $log->status);
        $this->assertEquals('Connection timeout', $log->error_message);
    }

    public function test_log_with_duration(): void
    {
        $log = $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_MATCH_CALCULATED,
            actionType: AgentAuditLog::TYPE_MATCHING,
            durationMs: 250.5,
        );

        $this->assertEquals(250.5, $log->duration_ms);
    }

    public function test_log_captures_ip_and_user_agent(): void
    {
        $log = $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_CONFIG_CHANGED,
            actionType: AgentAuditLog::TYPE_CONFIGURATION,
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
        );

        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Mozilla/5.0', $log->user_agent);
    }

    public function test_get_user_logs(): void
    {
        // Create multiple logs
        $this->auditService->logAgentActivated($this->user);
        $this->auditService->logJobDiscovered($this->user, $this->job);
        $this->auditService->logMatchCalculated($this->user, $this->job, 85);

        $logs = $this->auditService->getUserLogs($this->user->id, limit: 10);

        $this->assertCount(3, $logs);
    }

    public function test_get_user_logs_filter_by_action(): void
    {
        $this->auditService->logAgentActivated($this->user);
        $this->auditService->logJobDiscovered($this->user, $this->job);
        $this->auditService->logJobDiscovered($this->user, $this->job);

        $logs = $this->auditService->getUserLogs(
            $this->user->id,
            action: AgentAuditLog::ACTION_JOB_DISCOVERED
        );

        $this->assertCount(2, $logs);
    }

    public function test_get_user_logs_filter_by_action_type(): void
    {
        $this->auditService->logAgentActivated($this->user);
        $this->auditService->logJobDiscovered($this->user, $this->job);
        $this->auditService->logEmergencyStopped($this->user, 1, 'Test');

        $logs = $this->auditService->getUserLogs(
            $this->user->id,
            actionType: AgentAuditLog::TYPE_SAFETY
        );

        $this->assertCount(1, $logs);
    }

    public function test_get_logs_for_date_range(): void
    {
        $this->auditService->logAgentActivated($this->user);

        $logs = $this->auditService->getUserLogs(
            $this->user->id,
            from: now()->subDay(),
            to: now()->addDay()
        );

        $this->assertCount(1, $logs);
    }

    public function test_get_logs_outside_date_range(): void
    {
        $this->auditService->logAgentActivated($this->user);

        $logs = $this->auditService->getUserLogs(
            $this->user->id,
            from: now()->addDays(1),
            to: now()->addDays(2)
        );

        $this->assertCount(0, $logs);
    }

    public function test_get_recent_safety_events(): void
    {
        $this->auditService->logEmergencyStopped($this->user, 1, 'Reason 1');
        $this->auditService->logApprovalGranted($this->user, $this->job, 1);
        $this->auditService->logJobDiscovered($this->user, $this->job);

        $safetyLogs = $this->auditService->getRecentSafetyEvents(limit: 10);

        $this->assertCount(2, $safetyLogs);
    }

    public function test_count_actions_by_type(): void
    {
        $this->auditService->logJobDiscovered($this->user, $this->job);
        $this->auditService->logJobDiscovered($this->user, $this->job);
        $this->auditService->logAgentActivated($this->user);

        $counts = $this->auditService->countActionsByType($this->user->id);

        $this->assertEquals(2, $counts[AgentAuditLog::TYPE_DISCOVERY] ?? 0);
        $this->assertEquals(1, $counts[AgentAuditLog::TYPE_CONFIGURATION] ?? 0);
    }

    public function test_get_failed_operations(): void
    {
        $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_APPLICATION_SUBMITTED,
            actionType: AgentAuditLog::TYPE_APPLICATION,
            status: 'failed',
            errorMessage: 'Failed once'
        );
        $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_APPLICATION_SUBMITTED,
            actionType: AgentAuditLog::TYPE_APPLICATION,
            status: 'success'
        );

        $failed = $this->auditService->getFailedOperations($this->user->id);

        $this->assertCount(1, $failed);
        $this->assertEquals('Failed once', $failed[0]->error_message);
    }

    public function test_log_with_agent_configuration(): void
    {
        $log = $this->auditService->log(
            user: $this->user,
            action: AgentAuditLog::ACTION_AGENT_ACTIVATED,
            actionType: AgentAuditLog::TYPE_CONFIGURATION,
            agentConfigurationId: $this->agentConfig->id,
        );

        $this->assertEquals($this->agentConfig->id, $log->agent_configuration_id);
    }
}
