<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AgentConfiguration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agent Kill Switch Middleware
 *
 * Blocks all agent-related API operations when the global kill switch is active.
 * This is a critical safety mechanism to immediately halt all autonomous agent
 * activity across the platform.
 *
 * Usage in routes:
 *   Route::middleware(['agent.killswitch'])->group(function () {
 *       // Agent routes
 *   });
 */
class AgentKillSwitchMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if global kill switch is active
        if (AgentConfiguration::isGlobalKillSwitchActive()) {
            $killSwitchInfo = AgentConfiguration::getGlobalKillSwitchInfo();

            Log::warning('Agent request blocked by kill switch', [
                'user_id' => $request->user()?->id,
                'path' => $request->path(),
                'method' => $request->method(),
                'kill_switch_info' => $killSwitchInfo,
            ]);

            return response()->json([
                'error' => 'Agent Service Unavailable',
                'message' => 'All agent operations have been temporarily suspended.',
                'reason' => $killSwitchInfo['reason'] ?? 'Emergency maintenance',
                'retry_after' => 3600, // 1 hour
            ], 503, [
                'Retry-After' => '3600',
                'X-Agent-Kill-Switch' => 'active',
            ]);
        }

        // Check if user's specific agent is emergency stopped
        if ($request->user()) {
            $agentConfig = AgentConfiguration::where('user_id', $request->user()->id)->first();

            if ($agentConfig && $agentConfig->isEmergencyStopped()) {
                Log::warning('Agent request blocked - emergency stopped', [
                    'user_id' => $request->user()->id,
                    'path' => $request->path(),
                    'stopped_at' => $agentConfig->emergency_stopped_at,
                    'reason' => $agentConfig->emergency_stop_reason,
                ]);

                return response()->json([
                    'error' => 'Agent Stopped',
                    'message' => 'Your agent has been emergency stopped.',
                    'reason' => $agentConfig->emergency_stop_reason,
                    'stopped_at' => $agentConfig->emergency_stopped_at?->toIso8601String(),
                    'contact_support' => true,
                ], 403, [
                    'X-Agent-Emergency-Stopped' => 'true',
                ]);
            }
        }

        return $next($request);
    }
}
