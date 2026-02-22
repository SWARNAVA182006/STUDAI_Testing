<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Models\JobMatch;
use App\Models\AgentLearningMetric;
use App\Services\Agent\JobDiscoveryService;
use App\Services\Agent\AgentLearningService;
use App\Services\Agent\AgentAuditService;
use App\Notifications\AgentApprovalRequestNotification;
use App\Jobs\Agent\DiscoverJobsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Agent Controller
 *
 * Manages the Autonomous Job Application Agent via API endpoints.
 * Provides configuration, control, monitoring, and analytics capabilities.
 *
 * @OA\Tag(
 *     name="Agent",
 *     description="Autonomous auto-apply agent — configure, activate, monitor"
 * )
 */
class AgentController extends Controller
{
    /**
     * Get the user's agent configuration
     *
     * @OA\Get(
     *     path="/api/agent/config",
     *     operationId="agentGetConfig",
     *     tags={"Agent"},
     *     summary="Get current agent configuration",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Agent configuration",
     *         @OA\JsonContent(
     *             @OA\Property(property="configured", type="boolean"),
     *             @OA\Property(property="config", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="No configuration found")
     * )
     */
    public function getConfig(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found. Please configure your agent first.',
                'configured' => false,
            ], 404);
        }

        return response()->json([
            'configured' => true,
            'config' => $config->makeVisible([
                'job_search_criteria',
                'preferences',
                'customization_rules',
                'active_hours',
                'blacklisted_companies',
            ]),
        ]);
    }

    /**
     * Create or update agent configuration
     */
    public function configure(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_search_criteria' => 'required|array',
            'job_search_criteria.keywords' => 'required|array|min:1',
            'job_search_criteria.locations' => 'nullable|array',
            'job_search_criteria.job_types' => 'nullable|array',
            'job_search_criteria.experience_levels' => 'nullable|array',
            'job_search_criteria.min_salary' => 'nullable|integer|min:0',
            'job_search_criteria.max_salary' => 'nullable|integer|min:0',
            'job_search_criteria.remote_preference' => 'nullable|in:required,preferred,no_preference,on_site_only',
            
            'preferences' => 'nullable|array',
            'preferences.match_threshold' => 'nullable|numeric|min:0|max:100',
            'preferences.apply_to_external_jobs' => 'nullable|boolean',
            'preferences.auto_customize_resume' => 'nullable|boolean',
            'preferences.generate_cover_letter' => 'nullable|boolean',
            
            'customization_rules' => 'nullable|array',
            'customization_rules.highlight_skills' => 'nullable|array',
            'customization_rules.emphasis_areas' => 'nullable|array',
            'customization_rules.tone' => 'nullable|in:professional,enthusiastic,creative,technical',
            
            'daily_application_limit' => 'nullable|integer|min:1|max:50',
            'require_approval' => 'nullable|boolean',
            'auto_follow_up' => 'nullable|boolean',
            'follow_up_days' => 'nullable|integer|min:1|max:30',
            
            'active_hours' => 'nullable|array',
            'active_hours.start' => 'nullable|integer|min:0|max:23',
            'active_hours.end' => 'nullable|integer|min:0|max:23',
            'active_hours.days' => 'nullable|array',
            
            'enable_learning' => 'nullable|boolean',
            'send_digest' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $user = $request->user();

        // Check subscription feature limits
        if (!$user->hasFeature('autonomous_agent')) {
            return response()->json([
                'message' => 'Your subscription plan does not include the Autonomous Agent feature.',
                'upgrade_required' => true,
            ], 403);
        }

        // Create or update configuration
        $config = AgentConfiguration::updateOrCreate(
            ['user_id' => $user->id],
            array_merge($data, [
                'is_active' => false, // Don't activate immediately, let user activate explicitly
            ])
        );

        Log::info('Agent configuration saved', [
            'user_id' => $user->id,
            'config_id' => $config->id,
        ]);

        return response()->json([
            'message' => 'Agent configuration saved successfully. You can now activate your agent.',
            'config' => $config->makeVisible([
                'job_search_criteria',
                'preferences',
                'customization_rules',
                'active_hours',
            ]),
        ], 201);
    }

    /**
     * Activate the agent
     *
     * @OA\Post(
     *     path="/api/agent/activate",
     *     operationId="agentActivate",
     *     tags={"Agent"},
     *     summary="Activate the autonomous agent",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Agent activated"),
     *     @OA\Response(response=404, description="Agent not configured"),
     *     @OA\Response(response=409, description="Agent already active")
     * )
     */
    public function activate(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found. Please configure your agent first.',
            ], 404);
        }

        if ($config->is_active) {
            return response()->json([
                'message' => 'Agent is already active.',
                'config' => $config,
            ]);
        }

        // Check subscription limits
        $user = $request->user();
        if ($user->getRemainingApplications() <= 0) {
            return response()->json([
                'message' => 'You have reached your monthly application limit. Please upgrade your plan.',
                'limit_reached' => true,
            ], 403);
        }

        $config->update([
            'is_active' => true,
            'is_paused' => false,
            'activated_at' => now(),
        ]);

        // Dispatch immediate discovery job
        DiscoverJobsJob::dispatch();

        Log::info('Agent activated', [
            'user_id' => $user->id,
            'config_id' => $config->id,
        ]);

        return response()->json([
            'message' => 'Agent activated successfully. It will start discovering and applying to jobs based on your criteria.',
            'config' => $config,
        ]);
    }

    /**
     * Pause the agent (can be resumed)
     */
    public function pause(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        if ($config->is_paused) {
            return response()->json([
                'message' => 'Agent is already paused.',
                'config' => $config,
            ]);
        }

        $config->update([
            'is_paused' => true,
        ]);

        Log::info('Agent paused', [
            'user_id' => $request->user()->id,
            'config_id' => $config->id,
        ]);

        return response()->json([
            'message' => 'Agent paused. It will not submit new applications until resumed.',
            'config' => $config,
        ]);
    }

    /**
     * Resume the agent (unpause)
     */
    public function resume(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        if (!$config->is_active) {
            return response()->json([
                'message' => 'Agent is not active. Please activate it first.',
            ], 400);
        }

        if (!$config->is_paused) {
            return response()->json([
                'message' => 'Agent is not paused.',
                'config' => $config,
            ]);
        }

        $config->update([
            'is_paused' => false,
        ]);

        Log::info('Agent resumed', [
            'user_id' => $request->user()->id,
            'config_id' => $config->id,
        ]);

        return response()->json([
            'message' => 'Agent resumed successfully.',
            'config' => $config,
        ]);
    }

    /**
     * Deactivate the agent (stops completely)
     */
    public function deactivate(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        if (!$config->is_active) {
            return response()->json([
                'message' => 'Agent is already inactive.',
                'config' => $config,
            ]);
        }

        $config->update([
            'is_active' => false,
            'is_paused' => false,
            'deactivated_at' => now(),
        ]);

        Log::info('Agent deactivated', [
            'user_id' => $request->user()->id,
            'config_id' => $config->id,
        ]);

        return response()->json([
            'message' => 'Agent deactivated successfully.',
            'config' => $config,
        ]);
    }

    /**
     * Get agent status and current activity
     *
     * @OA\Get(
     *     path="/api/agent/status",
     *     operationId="agentStatus",
     *     tags={"Agent"},
     *     summary="Get agent status, recent activity, and match statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Agent status with dashboard data"),
     *     @OA\Response(response=404, description="Agent not configured")
     * )
     */
    public function status(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'configured' => false,
                'message' => 'No agent configuration found.',
            ], 404);
        }

        $user = $request->user();

        // Get statistics
        $totalApplications = AutoApplication::where('agent_configuration_id', $config->id)->count();
        $todayApplications = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereDate('created_at', today())
            ->count();
        $pendingApplications = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNull('outcome')
            ->count();
        $successfulApplications = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereIn('outcome', ['interview_scheduled', 'offer_received', 'accepted'])
            ->count();

        // Get pending matches
        $pendingMatches = JobMatch::where('agent_configuration_id', $config->id)
            ->where('status', 'qualified')
            ->whereDoesntHave('autoApplications')
            ->count();

        // Calculate limits
        $dailyLimit = $config->daily_application_limit ?? 10;
        $dailyRemaining = max(0, $dailyLimit - $todayApplications);
        $monthlyRemaining = $user->getRemainingApplications();

        // Calculate success rate
        $totalWithOutcomes = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNotNull('outcome')
            ->count();
        $successRate = $totalWithOutcomes > 0
            ? round(($successfulApplications / $totalWithOutcomes) * 100, 1)
            : 0;

        return response()->json([
            'configured' => true,
            'status' => [
                'is_active' => $config->is_active,
                'is_paused' => $config->is_paused,
                'activated_at' => $config->activated_at,
                'last_run_at' => $config->last_run_at,
                'last_optimization_at' => $config->last_optimization_at,
            ],
            'statistics' => [
                'total_applications' => $totalApplications,
                'today_applications' => $todayApplications,
                'pending_applications' => $pendingApplications,
                'successful_applications' => $successfulApplications,
                'success_rate' => $successRate,
                'pending_matches' => $pendingMatches,
            ],
            'limits' => [
                'daily_limit' => $dailyLimit,
                'daily_remaining' => $dailyRemaining,
                'monthly_remaining' => $monthlyRemaining,
            ],
        ]);
    }

    /**
     * Get applications submitted by the agent
     */
    public function applications(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        $query = AutoApplication::where('agent_configuration_id', $config->id)
            ->with('job');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by outcome
        if ($request->has('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        // Date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->input('per_page', 20);
        $applications = $query->paginate($perPage);

        return response()->json($applications);
    }

    /**
     * Get performance metrics
     */
    public function metrics(Request $request)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        // Get learning metrics
        $metrics = AgentLearningMetric::where('agent_configuration_id', $config->id)
            ->orderBy('created_at', 'desc')
            ->take(30) // Last 30 data points
            ->get();

        // Calculate performance trends
        $applications = AutoApplication::where('agent_configuration_id', $config->id)
            ->whereNotNull('outcome')
            ->get();

        $performanceByCompany = $applications->groupBy('company_name')
            ->map(function ($companyApps) {
                $total = $companyApps->count();
                $successful = $companyApps->whereIn('outcome', ['interview_scheduled', 'offer_received', 'accepted'])->count();
                return [
                    'total_applications' => $total,
                    'successful_applications' => $successful,
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
                ];
            });

        $performanceByJobType = $applications->groupBy('job_type')
            ->map(function ($typeApps) {
                $total = $typeApps->count();
                $successful = $typeApps->whereIn('outcome', ['interview_scheduled', 'offer_received', 'accepted'])->count();
                return [
                    'total_applications' => $total,
                    'successful_applications' => $successful,
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
                ];
            });

        // Average match scores
        $avgMatchScore = $applications->avg('match_score');
        $successfulAvgScore = $applications->whereIn('outcome', ['interview_scheduled', 'offer_received', 'accepted'])
            ->avg('match_score');

        return response()->json([
            'learning_metrics' => $metrics,
            'performance_by_company' => $performanceByCompany,
            'performance_by_job_type' => $performanceByJobType,
            'average_scores' => [
                'all_applications' => $avgMatchScore ? round($avgMatchScore, 1) : null,
                'successful_applications' => $successfulAvgScore ? round($successfulAvgScore, 1) : null,
            ],
        ]);
    }

    /**
     * Get learning insights
     */
    public function learning(Request $request, AgentLearningService $learningService)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        if (!$config->enable_learning) {
            return response()->json([
                'message' => 'Learning is not enabled for this agent.',
                'enabled' => false,
            ]);
        }

        // Analyze patterns
        $analysis = $learningService->analyzePatterns($config);

        return response()->json([
            'enabled' => true,
            'last_optimization' => $config->last_optimization_at,
            'insights' => $analysis['insights'] ?? [],
            'recommendations' => $analysis['recommendations'] ?? [],
            'metrics_summary' => $analysis['metrics_summary'] ?? [],
        ]);
    }

    /**
     * Add company to blacklist
     */
    public function blacklistCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        $blacklist = $config->blacklisted_companies ?? [];
        
        // Check if already blacklisted
        $exists = collect($blacklist)->contains('company', $request->company_name);
        
        if ($exists) {
            return response()->json([
                'message' => 'Company is already blacklisted.',
            ], 400);
        }

        $blacklist[] = [
            'company' => $request->company_name,
            'reason' => $request->input('reason'),
            'added_at' => now()->toISOString(),
        ];

        $config->update([
            'blacklisted_companies' => $blacklist,
        ]);

        Log::info('Company blacklisted', [
            'user_id' => $request->user()->id,
            'company' => $request->company_name,
        ]);

        return response()->json([
            'message' => 'Company added to blacklist successfully.',
            'blacklist' => $blacklist,
        ]);
    }

    /**
     * Remove company from blacklist
     */
    public function unblacklistCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        $blacklist = collect($config->blacklisted_companies ?? [])
            ->reject(function ($item) use ($request) {
                return $item['company'] === $request->company_name;
            })
            ->values()
            ->toArray();

        $config->update([
            'blacklisted_companies' => $blacklist,
        ]);

        Log::info('Company removed from blacklist', [
            'user_id' => $request->user()->id,
            'company' => $request->company_name,
        ]);

        return response()->json([
            'message' => 'Company removed from blacklist successfully.',
            'blacklist' => $blacklist,
        ]);
    }

    /**
     * Trigger manual job discovery (for testing)
     */
    public function discover(Request $request, JobDiscoveryService $discoveryService)
    {
        $config = AgentConfiguration::where('user_id', $request->user()->id)->first();

        if (!$config) {
            return response()->json([
                'message' => 'No agent configuration found.',
            ], 404);
        }

        if (!$config->is_active) {
            return response()->json([
                'message' => 'Agent is not active. Please activate it first.',
            ], 400);
        }

        try {
            // Run discovery synchronously for immediate feedback
            $results = $discoveryService->discoverJobs($config);

            Log::info('Manual job discovery triggered', [
                'user_id' => $request->user()->id,
                'config_id' => $config->id,
                'jobs_found' => $results['total_jobs_found'] ?? 0,
                'matches_created' => $results['matches_created'] ?? 0,
            ]);

            return response()->json([
                'message' => 'Job discovery completed successfully.',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Manual job discovery failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Job discovery failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========== Human-in-the-Loop Approval Methods ==========

    /**
     * Get pending approvals for the user's agent
     */
    public function getPendingApprovals(Request $request)
    {
        $user = $request->user();

        $pendingMatches = JobMatch::where('user_id', $user->id)
            ->where('status', 'pending_approval')
            ->with(['discoveredJob.jobListing.company'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($pendingMatches);
    }

    /**
     * Get details of a specific approval request
     */
    public function getApprovalDetails(Request $request, int $matchId)
    {
        $user = $request->user();

        $match = JobMatch::where('id', $matchId)
            ->where('user_id', $user->id)
            ->with(['discoveredJob.jobListing.company', 'agentConfiguration'])
            ->first();

        if (!$match) {
            return response()->json([
                'message' => 'Approval request not found.',
            ], 404);
        }

        $job = $match->discoveredJob?->jobListing;

        return response()->json([
            'match' => $match,
            'job' => $job,
            'match_breakdown' => $match->score_breakdown,
            'can_approve' => $match->status === 'pending_approval',
            'expires_at' => $match->created_at->addHours(24)->toIso8601String(),
        ]);
    }

    /**
     * Approve an application request
     */
    public function approveApplication(Request $request, int $matchId, AgentAuditService $auditService)
    {
        $user = $request->user();

        $match = JobMatch::where('id', $matchId)
            ->where('user_id', $user->id)
            ->where('status', 'pending_approval')
            ->first();

        if (!$match) {
            return response()->json([
                'message' => 'Approval request not found or already processed.',
            ], 404);
        }

        // Check if expired (24 hour window)
        if ($match->created_at->addHours(24)->isPast()) {
            $match->update(['status' => 'expired']);
            return response()->json([
                'message' => 'This approval request has expired.',
            ], 410);
        }

        // Update status to approved
        $match->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        // Log the approval
        $job = $match->discoveredJob?->jobListing;
        if ($job) {
            $auditService->logApprovalGranted($user, $job, $user->id);
        }

        Log::info('Agent application approved', [
            'user_id' => $user->id,
            'match_id' => $matchId,
            'job_title' => $job?->title,
        ]);

        return response()->json([
            'message' => 'Application approved. The agent will submit it shortly.',
            'match' => $match->fresh(),
        ]);
    }

    /**
     * Reject an application request
     */
    public function rejectApplication(Request $request, int $matchId, AgentAuditService $auditService)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
            'blacklist_company' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $match = JobMatch::where('id', $matchId)
            ->where('user_id', $user->id)
            ->where('status', 'pending_approval')
            ->first();

        if (!$match) {
            return response()->json([
                'message' => 'Approval request not found or already processed.',
            ], 404);
        }

        $reason = $request->input('reason', 'Rejected by user');

        // Update status to rejected
        $match->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Log the rejection
        $job = $match->discoveredJob?->jobListing;
        if ($job) {
            $auditService->logApprovalDenied($user, $job, $user->id, $reason);
        }

        // Optionally blacklist the company
        if ($request->input('blacklist_company') && $job?->company) {
            $config = AgentConfiguration::where('user_id', $user->id)->first();
            if ($config) {
                $blacklist = $config->blacklisted_companies ?? [];
                $companyName = $job->company->name;

                if (!collect($blacklist)->contains('company', $companyName)) {
                    $blacklist[] = [
                        'company' => $companyName,
                        'reason' => 'Blacklisted after rejecting application',
                        'added_at' => now()->toISOString(),
                    ];
                    $config->update(['blacklisted_companies' => $blacklist]);
                }
            }
        }

        Log::info('Agent application rejected', [
            'user_id' => $user->id,
            'match_id' => $matchId,
            'reason' => $reason,
        ]);

        return response()->json([
            'message' => 'Application rejected.',
            'match' => $match->fresh(),
        ]);
    }

    /**
     * Bulk approve multiple applications
     */
    public function bulkApprove(Request $request, AgentAuditService $auditService)
    {
        $validator = Validator::make($request->all(), [
            'match_ids' => 'required|array|min:1|max:50',
            'match_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $matchIds = $request->input('match_ids');

        $matches = JobMatch::whereIn('id', $matchIds)
            ->where('user_id', $user->id)
            ->where('status', 'pending_approval')
            ->get();

        $approved = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            // Check if expired
            if ($match->created_at->addHours(24)->isPast()) {
                $match->update(['status' => 'expired']);
                $skipped++;
                continue;
            }

            $match->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);
            $approved++;
        }

        Log::info('Bulk agent applications approved', [
            'user_id' => $user->id,
            'approved' => $approved,
            'skipped' => $skipped,
        ]);

        return response()->json([
            'message' => "Approved {$approved} applications. {$skipped} were skipped (expired).",
            'approved' => $approved,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Bulk reject multiple applications
     */
    public function bulkReject(Request $request, AgentAuditService $auditService)
    {
        $validator = Validator::make($request->all(), [
            'match_ids' => 'required|array|min:1|max:50',
            'match_ids.*' => 'integer',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $matchIds = $request->input('match_ids');
        $reason = $request->input('reason', 'Bulk rejected by user');

        $rejected = JobMatch::whereIn('id', $matchIds)
            ->where('user_id', $user->id)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

        Log::info('Bulk agent applications rejected', [
            'user_id' => $user->id,
            'rejected' => $rejected,
        ]);

        return response()->json([
            'message' => "Rejected {$rejected} applications.",
            'rejected' => $rejected,
        ]);
    }
}
