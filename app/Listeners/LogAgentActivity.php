<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AgentActivated;
use App\Events\AgentApplicationSubmitted;
use App\Events\AgentDeactivated;
use App\Events\AgentJobDiscovered;
use App\Events\AgentJobMatched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogAgentActivity implements ShouldQueue
{
    public string $queue = 'default';

    public function handleAgentActivated(AgentActivated $event): void
    {
        $this->logActivity($event->user->id, 'agent_activated', [
            'configuration_id' => $event->configuration->id,
        ]);

        Log::info('Agent activated for user', ['user_id' => $event->user->id]);
    }

    public function handleAgentDeactivated(AgentDeactivated $event): void
    {
        $this->logActivity($event->user->id, 'agent_deactivated', [
            'configuration_id' => $event->configuration->id,
            'reason' => $event->reason,
        ]);

        Log::info('Agent deactivated for user', [
            'user_id' => $event->user->id,
            'reason' => $event->reason,
        ]);
    }

    public function handleAgentJobDiscovered(AgentJobDiscovered $event): void
    {
        $this->logActivity($event->user->id, 'agent_job_discovered', [
            'discovered_job_id' => $event->discoveredJob->id,
            'source' => $event->source,
        ]);
    }

    public function handleAgentJobMatched(AgentJobMatched $event): void
    {
        $this->logActivity($event->user->id, 'agent_job_matched', [
            'job_id' => $event->job->id,
            'match_id' => $event->match->id,
            'match_score' => $event->matchScore,
            'requires_approval' => $event->requiresApproval,
        ]);
    }

    public function handleAgentApplicationSubmitted(AgentApplicationSubmitted $event): void
    {
        $this->logActivity($event->user->id, 'agent_application_submitted', [
            'application_id' => $event->application->id ?? null,
            'job_title' => $event->job->title ?? 'Unknown',
        ]);
    }

    protected function logActivity(int $userId, string $action, array $data): void
    {
        DB::table('agent_audit_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'result' => 'success',
            'metadata' => json_encode($data),
            'correlation_id' => \Illuminate\Support\Facades\Context::get('correlation_id', ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            AgentActivated::class => 'handleAgentActivated',
            AgentDeactivated::class => 'handleAgentDeactivated',
            AgentJobDiscovered::class => 'handleAgentJobDiscovered',
            AgentJobMatched::class => 'handleAgentJobMatched',
            AgentApplicationSubmitted::class => 'handleAgentApplicationSubmitted',
        ];
    }
}
