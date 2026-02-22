<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AI\NegotiationStrategistService;
use App\Services\AI\NegotiationScenarioService;
use App\Services\AI\NegotiationScriptService;
use App\Services\AI\NegotiationCoachingService;
use App\Models\NegotiationStrategy;
use App\Models\NegotiationScenario;
use App\Models\NegotiationSession;
use App\Models\NegotiationTactic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NegotiationController extends Controller
{
    protected $strategistService;
    protected $scenarioService;
    protected $scriptService;
    protected $coachingService;

    public function __construct(
        NegotiationStrategistService $strategistService,
        NegotiationScenarioService $scenarioService,
        NegotiationScriptService $scriptService,
        NegotiationCoachingService $coachingService
    ) {
        $this->strategistService = $strategistService;
        $this->scenarioService = $scenarioService;
        $this->scriptService = $scriptService;
        $this->coachingService = $coachingService;
    }

    /**
     * Generate negotiation strategy from offer data
     */
    public function generateStrategy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'offered_salary' => 'required|numeric|min:0',
            'current_salary' => 'nullable|numeric|min:0',
            'experience_years' => 'required|integer|min:0|max:50',
            'skills' => 'nullable|array',
            'education_level' => 'nullable|string|in:high_school,associate,bachelor,master,phd,mba',
            'has_other_offers' => 'nullable|boolean',
            'is_currently_employed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $data = $request->all();
            
            // Generate comprehensive strategy
            $strategy = $this->strategistService->generateStrategy($user, $data);

            // Generate scenarios
            $scenarios = $this->scenarioService->generateScenarios($strategy);

            // Generate scripts for recommended scenario
            $recommendedScenario = $this->scenarioService->getRecommendedScenario($strategy);
            $scripts = [];
            if ($recommendedScenario) {
                $scripts = $this->scriptService->generateScripts($strategy, $recommendedScenario);
            }

            return response()->json([
                'success' => true,
                'strategy' => $strategy->load(['scenarios', 'scripts']),
                'recommended_scenario' => $recommendedScenario,
                'readiness_score' => $this->calculateReadinessScore($strategy),
                'next_steps' => $this->getNextSteps($strategy),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate strategy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing strategy
     */
    public function getStrategy($id)
    {
        try {
            $strategy = NegotiationStrategy::with([
                'scenarios' => function($query) {
                    $query->orderBy('scenario_order');
                },
                'scripts',
                'sessions'
            ])->findOrFail($id);

            // Verify ownership
            if ($strategy->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'strategy' => $strategy,
                'readiness_score' => $this->calculateReadinessScore($strategy),
                'market_comparison' => $strategy->getMarketComparison(),
                'leverage_analysis' => $strategy->calculateNegotiationLeverage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Strategy not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get scenarios for a strategy
     */
    public function getScenarios($strategyId)
    {
        try {
            $strategy = NegotiationStrategy::findOrFail($strategyId);

            if ($strategy->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $scenarios = $strategy->scenarios()
                ->orderBy('scenario_order')
                ->get();

            $comparison = $this->scenarioService->compareScenarios($scenarios->toArray());

            return response()->json([
                'success' => true,
                'scenarios' => $scenarios,
                'comparison' => $comparison,
                'recommended' => $this->scenarioService->getRecommendedScenario($strategy),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load scenarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scripts for a strategy
     */
    public function getScripts($strategyId)
    {
        try {
            $strategy = NegotiationStrategy::findOrFail($strategyId);

            if ($strategy->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $scripts = $strategy->scripts()
                ->with('scenario')
                ->get()
                ->groupBy('script_type');

            return response()->json([
                'success' => true,
                'scripts' => $scripts,
                'script_types' => ['email', 'phone', 'in_person', 'video_call'],
                'script_stages' => ['initial_response', 'counter_offer', 'follow_up', 'closing'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load scripts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate scripts for specific scenario
     */
    public function generateScriptsForScenario(Request $request, $scenarioId)
    {
        $validator = Validator::make($request->all(), [
            'script_types' => 'nullable|array',
            'script_types.*' => 'string|in:email,phone,in_person,video_call',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $scenario = NegotiationScenario::with('strategy')->findOrFail($scenarioId);

            if ($scenario->strategy->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $scripts = $this->scriptService->generateScripts($scenario->strategy, $scenario);

            return response()->json([
                'success' => true,
                'scripts' => $scripts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate scripts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start coaching session
     */
    public function startSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'strategy_id' => 'required|exists:negotiation_strategies,id',
            'scenario_id' => 'nullable|exists:negotiation_scenarios,id',
            'session_type' => 'nullable|string|in:preparation,live_coaching,post_mortem',
            'communication_mode' => 'nullable|string|in:email,phone,in_person,video_call',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $strategy = NegotiationStrategy::findOrFail($request->strategy_id);

            if ($strategy->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $session = $this->coachingService->startSession(
                Auth::id(),
                $strategy,
                $request->scenario_id,
                $request->session_type ?? 'live_coaching',
                $request->communication_mode ?? 'email'
            );

            return response()->json([
                'success' => true,
                'session' => $session->load('messages'),
                'initial_guidance' => $session->messages()->latest()->first(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add message to coaching session
     */
    public function addMessage(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'message_type' => 'required|string|in:employer_response,user_input',
            'selected_suggestion_id' => 'nullable|exists:negotiation_messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $session = NegotiationSession::findOrFail($sessionId);

            if ($session->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($request->message_type === 'employer_response') {
                // Analyze employer message and get coaching
                $analysis = $this->coachingService->analyzeEmployerMessage($session, $request->message);

                return response()->json([
                    'success' => true,
                    'analysis' => $analysis,
                    'messages' => $session->messages()->latest()->take(10)->get(),
                ]);
            } else {
                // Record user's response
                $this->coachingService->recordUserResponse(
                    $session,
                    $request->message,
                    $request->selected_suggestion_id
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Response recorded',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update session stage
     */
    public function updateSessionStage(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'stage' => 'required|string|in:initial_outreach,counter_offer,negotiation,benefits_discussion,closing,accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $session = NegotiationSession::findOrFail($sessionId);

            if ($session->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $this->coachingService->updateSessionStage($session, $request->stage);

            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'stage_guidance' => $session->messages()->where('message_type', 'ai_analysis')->latest()->first(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record session outcome
     */
    public function recordOutcome(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'outcome' => 'required|string|in:accepted,declined,offer_improved,offer_withdrawn,negotiating',
            'final_salary' => 'nullable|numeric|min:0',
            'final_benefits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $session = NegotiationSession::findOrFail($sessionId);

            if ($session->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $this->coachingService->endSession(
                $session,
                $request->outcome,
                $request->final_salary,
                $request->final_benefits
            );

            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'summary' => $session->messages()->where('message_type', 'system_note')->latest()->first(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record outcome',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get negotiation tactics library
     */
    public function getTactics(Request $request)
    {
        try {
            $query = NegotiationTactic::query();

            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('risk_level')) {
                $query->where('risk_level', $request->risk_level);
            }

            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('tactic_name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            $tactics = $query->orderBy('average_effectiveness', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'tactics' => $tactics,
                'categories' => NegotiationTactic::select('tactic_category')->distinct()->pluck('tactic_category'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tactics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Calculate readiness score
     */
    protected function calculateReadinessScore(NegotiationStrategy $strategy): array
    {
        $score = 0;
        $maxScore = 100;
        $factors = [];

        // Market research complete (25 points)
        if ($strategy->market_median && $strategy->market_75th_percentile) {
            $score += 25;
            $factors[] = ['factor' => 'Market Research', 'points' => 25, 'status' => 'complete'];
        } else {
            $factors[] = ['factor' => 'Market Research', 'points' => 0, 'status' => 'incomplete'];
        }

        // Strong negotiation position (25 points)
        $leverageScore = count($strategy->strongest_points) * 5;
        $leveragePoints = min($leverageScore, 25);
        $score += $leveragePoints;
        $factors[] = ['factor' => 'Negotiation Leverage', 'points' => $leveragePoints, 'status' => $leveragePoints >= 15 ? 'strong' : 'needs_work'];

        // Scenarios prepared (20 points)
        $scenarioCount = $strategy->scenarios()->count();
        $scenarioPoints = min($scenarioCount * 7, 20);
        $score += $scenarioPoints;
        $factors[] = ['factor' => 'Scenarios Prepared', 'points' => $scenarioPoints, 'status' => $scenarioCount >= 3 ? 'complete' : 'incomplete'];

        // Scripts ready (20 points)
        $scriptCount = $strategy->scripts()->count();
        $scriptPoints = min($scriptCount * 7, 20);
        $score += $scriptPoints;
        $factors[] = ['factor' => 'Scripts Ready', 'points' => $scriptPoints, 'status' => $scriptCount >= 3 ? 'complete' : 'incomplete'];

        // Company intelligence (10 points)
        if ($strategy->company_culture_analysis && $strategy->company_negotiation_flexibility) {
            $score += 10;
            $factors[] = ['factor' => 'Company Intelligence', 'points' => 10, 'status' => 'complete'];
        } else {
            $factors[] = ['factor' => 'Company Intelligence', 'points' => 0, 'status' => 'incomplete'];
        }

        return [
            'total_score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'level' => $this->getReadinessLevel($score),
            'factors' => $factors,
        ];
    }

    /**
     * Helper: Get readiness level
     */
    protected function getReadinessLevel(int $score): string
    {
        if ($score >= 80) return 'ready';
        if ($score >= 60) return 'mostly_ready';
        if ($score >= 40) return 'needs_preparation';
        return 'not_ready';
    }

    /**
     * Helper: Get next steps
     */
    protected function getNextSteps(NegotiationStrategy $strategy): array
    {
        $steps = [];

        if ($strategy->scenarios()->count() === 0) {
            $steps[] = [
                'action' => 'Review Scenarios',
                'description' => 'Explore different counter-offer approaches and their predicted outcomes',
                'priority' => 'high',
            ];
        }

        if ($strategy->scripts()->count() === 0) {
            $steps[] = [
                'action' => 'Prepare Scripts',
                'description' => 'Generate professional scripts for email, phone, and in-person negotiations',
                'priority' => 'high',
            ];
        }

        if ($strategy->sessions()->count() === 0) {
            $steps[] = [
                'action' => 'Practice with Coach',
                'description' => 'Start a practice session to rehearse your negotiation approach',
                'priority' => 'medium',
            ];
        }

        if (empty($steps)) {
            $steps[] = [
                'action' => 'Start Negotiation',
                'description' => 'You\'re ready! Begin your negotiation with confidence',
                'priority' => 'high',
            ];
        }

        return $steps;
    }
}
