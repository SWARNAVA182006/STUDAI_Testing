<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Agent Administration Controller
 *
 * Provides emergency controls for the autonomous agent system.
 */
class AgentAdminController extends Controller
{
    /**
     * Activate global kill switch - stops ALL agents immediately.
     *
     * POST /admin/agent/kill-all
     */
    public function killAll(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $adminId = $request->user()->id;
        $reason = $request->input('reason');

        $count = AgentConfiguration::activateGlobalKillSwitch($adminId, $reason);

        Log::critical('Global kill switch activated by admin', [
            'admin_id' => $adminId,
            'admin_email' => $request->user()->email,
            'reason' => $reason,
            'agents_stopped' => $count,
        ]);

        return response()->json([
            'message' => 'Global kill switch activated',
            'agents_stopped' => $count,
            'reason' => $reason,
            'activated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Deactivate global kill switch.
     *
     * POST /admin/agent/resume-all
     */
    public function resumeAll(Request $request): JsonResponse
    {
        $adminId = $request->user()->id;

        // Check if kill switch is even active
        if (!AgentConfiguration::isGlobalKillSwitchActive()) {
            return response()->json([
                'message' => 'Global kill switch is not active',
            ], 400);
        }

        AgentConfiguration::deactivateGlobalKillSwitch();

        Log::info('Global kill switch deactivated by admin', [
            'admin_id' => $adminId,
            'admin_email' => $request->user()->email,
        ]);

        return response()->json([
            'message' => 'Global kill switch deactivated',
            'info' => 'Individual agents must be manually reactivated by users',
        ]);
    }

    /**
     * Get global kill switch status.
     *
     * GET /admin/agent/status
     */
    public function status(): JsonResponse
    {
        $isActive = AgentConfiguration::isGlobalKillSwitchActive();
        $info = AgentConfiguration::getGlobalKillSwitchInfo();

        $activeCount = AgentConfiguration::where('is_active', true)->count();
        $stoppedCount = AgentConfiguration::where('is_globally_stopped', true)->count();
        $emergencyStoppedCount = AgentConfiguration::whereNotNull('emergency_stopped_at')->count();
        $totalCount = AgentConfiguration::count();

        return response()->json([
            'global_kill_switch' => [
                'active' => $isActive,
                'info' => $info,
            ],
            'statistics' => [
                'total_agents' => $totalCount,
                'active_agents' => $activeCount,
                'globally_stopped' => $stoppedCount,
                'emergency_stopped' => $emergencyStoppedCount,
            ],
        ]);
    }

    /**
     * Emergency stop a specific user's agent.
     *
     * POST /admin/agent/{userId}/stop
     */
    public function stopAgent(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $agent = AgentConfiguration::where('user_id', $userId)->first();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent configuration not found for user',
            ], 404);
        }

        if ($agent->isEmergencyStopped()) {
            return response()->json([
                'message' => 'Agent is already emergency stopped',
            ], 400);
        }

        $agent->emergencyStop($request->user()->id, $request->input('reason'));

        return response()->json([
            'message' => 'Agent emergency stopped',
            'user_id' => $userId,
            'reason' => $request->input('reason'),
        ]);
    }

    /**
     * Clear emergency stop for a specific user's agent.
     *
     * POST /admin/agent/{userId}/resume
     */
    public function resumeAgent(Request $request, int $userId): JsonResponse
    {
        $agent = AgentConfiguration::where('user_id', $userId)->first();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent configuration not found for user',
            ], 404);
        }

        if (!$agent->isEmergencyStopped()) {
            return response()->json([
                'message' => 'Agent is not emergency stopped',
            ], 400);
        }

        $agent->clearEmergencyStop();

        Log::info('Agent emergency stop cleared by admin', [
            'admin_id' => $request->user()->id,
            'user_id' => $userId,
        ]);

        return response()->json([
            'message' => 'Agent emergency stop cleared',
            'user_id' => $userId,
            'info' => 'Agent must still be manually activated by the user',
        ]);
    }

    /**
     * List all agents with their status.
     *
     * GET /admin/agent/list
     */
    public function list(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);

        $agents = AgentConfiguration::with('user:id,name,email')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json($agents);
    }
}
