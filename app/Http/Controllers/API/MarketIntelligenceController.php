<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMarketPosition;
use App\Models\SalaryTrend;
use App\Models\SkillTrend;
use App\Models\RolePrediction;
use App\Models\CompetitiveBenchmark;
use App\Services\AI\MarketIntelligenceService;
use App\Services\AI\MarketPositioningService;
use App\Services\AI\SalaryIntelligenceService;
use App\Services\AI\SkillTrendAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Market Intelligence Controller
 * 
 * API endpoints for market intelligence, user positioning, salary insights,
 * skill trends, role predictions, and competitive analysis.
 */
class MarketIntelligenceController extends Controller
{
    public function __construct(
        protected MarketIntelligenceService $marketIntelligence,
        protected MarketPositioningService $marketPositioning,
        protected SalaryIntelligenceService $salaryIntelligence,
        protected SkillTrendAnalysisService $skillTrendAnalysis
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get market overview
     * 
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $overview = $this->marketIntelligence->getMarketOverview();
            
            return response()->json([
                'success' => true,
                'data' => $overview,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch market overview',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get user's market position
     * 
     * @return JsonResponse
     */
    public function userPosition(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            
            // Get latest position or calculate new one
            $position = UserMarketPosition::where('user_id', $user->id)
                ->latest('updated_at')
                ->first();
            
            if (!$position || $position->needsUpdate()) {
                $position = $this->marketPositioning->calculateMarketPosition($user);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'readiness_score' => $position->readiness_score,
                    'status' => $position->status,
                    'status_color' => $position->status_color,
                    'status_label' => $position->status_label,
                    'percentiles' => [
                        'overall' => $position->overall_percentile,
                        'experience' => $position->experience_percentile,
                        'skills' => $position->skills_percentile,
                        'compensation' => $position->compensation_percentile,
                    ],
                    'competitive_analysis' => [
                        'advantages' => $position->competitive_advantages ?? [],
                        'weaknesses' => $position->competitive_weaknesses ?? [],
                        'skill_gaps' => $position->skill_gaps ?? [],
                    ],
                    'role_fit' => [
                        'best_fit' => $position->best_fit_roles ?? [],
                        'trending' => $position->trending_opportunities ?? [],
                        'avoid' => $position->roles_to_avoid ?? [],
                    ],
                    'recommendations' => $position->recommendations ?? [],
                    'potential_salary_increase' => $position->potential_salary_increase,
                    'last_updated' => $position->updated_at->toIso8601String(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user position',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get salary insights
     * 
     * @return JsonResponse
     */
    public function salaryInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'cities' => 'nullable|array',
            'cities.*' => 'string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            /** @var User $user */
            $user = $request->user();
            
            $role = $request->input('role');
            $location = $request->input('location');
            $experienceYears = $request->input('experience_years');
            
            // If no role provided, use user's profile
            if (!$role && $user->profile) {
                $latestExp = collect($user->profile->experience ?? [])->sortByDesc('end_date')->first();
                $role = $latestExp['title'] ?? 'Software Engineer';
            }
            
            // Get salary trends
            $trends = $this->salaryIntelligence->analyzeSalaryTrends($role, $location, $experienceYears);
            
            // Get user's salary percentile
            $userPercentile = $this->salaryIntelligence->calculateUserSalaryPercentile($user);
            
            // Compare across cities if requested
            $cityComparisons = [];
            if ($request->has('cities')) {
                $cityComparisons = $this->salaryIntelligence->compareSalariesAcrossCities(
                    $role,
                    $request->input('cities')
                );
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'role' => $role,
                    'location' => $location ?? 'Global',
                    'trends' => $trends,
                    'user_percentile' => $userPercentile,
                    'city_comparisons' => $cityComparisons,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch salary insights',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get negotiation insights
     * 
     * @return JsonResponse
     */
    public function negotiationInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offered_salary' => 'required|numeric|min:0',
            'role' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            /** @var User $user */
            $user = $request->user();
            
            $insights = $this->salaryIntelligence->generateNegotiationInsights(
                $user,
                $request->input('offered_salary'),
                $request->input('role')
            );
            
            return response()->json([
                'success' => true,
                'data' => $insights,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate negotiation insights',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get skill trends
     * 
     * @return JsonResponse
     */
    public function skillTrends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'skill' => 'nullable|string|max:255',
            'status' => 'nullable|in:emerging,hot,stable,declining,obsolete',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $skill = $request->input('skill');
            $status = $request->input('status');
            $limit = $request->input('limit', 20);
            
            if ($skill) {
                // Get specific skill analysis
                $skillData = $this->skillTrendAnalysis->analyzeSkillDemand($skill);
                $evolution = $this->skillTrendAnalysis->trackSkillEvolution($skill, 12);
                $obsolescence = $this->skillTrendAnalysis->predictSkillObsolescence($skill);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'skill' => $skillData,
                        'evolution' => $evolution,
                        'obsolescence' => $obsolescence,
                    ],
                ]);
            }
            
            // Get trending skills by status
            if ($status) {
                $skills = SkillTrend::getTrending($status, $limit);
            } else {
                $skills = SkillTrend::getLatest($limit);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'skills' => $skills,
                    'total' => $skills->count(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skill trends',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get skill combinations
     * 
     * @return JsonResponse
     */
    public function skillCombinations(Request $request): JsonResponse
    {
        try {
            $combinations = $this->skillTrendAnalysis->identifySkillCombinations();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'combinations' => $combinations,
                    'total' => count($combinations),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skill combinations',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get upskilling roadmap
     * 
     * @return JsonResponse
     */
    public function upskillingRoadmap(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            
            $roadmap = $this->skillTrendAnalysis->generateUpskillingRoadmap($user);
            
            return response()->json([
                'success' => true,
                'data' => $roadmap,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upskilling roadmap',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get role predictions
     * 
     * @return JsonResponse
     */
    public function rolePredictions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'nullable|string|max:255',
            'status' => 'nullable|in:emerging,growing,stable,declining,obsolete',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $role = $request->input('role');
            $status = $request->input('status');
            $limit = $request->input('limit', 20);
            
            if ($role) {
                // Get specific role prediction
                $prediction = RolePrediction::getLatest($role);
                
                if (!$prediction) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No prediction data available for this role',
                    ], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $prediction,
                ]);
            }
            
            // Get predictions by status
            if ($status === 'emerging') {
                $predictions = RolePrediction::getEmerging($limit);
            } elseif ($status === 'declining') {
                $predictions = RolePrediction::getDeclining($limit);
            } else {
                $predictions = RolePrediction::getRecommended($limit);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'predictions' => $predictions,
                    'total' => $predictions->count(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role predictions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get competitive analysis
     * 
     * @return JsonResponse
     */
    public function competitiveAnalysis(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            
            // Get all benchmarks for user
            $benchmarks = CompetitiveBenchmark::getUserBenchmarks($user->id);
            
            // Get latest position for summary
            $position = UserMarketPosition::where('user_id', $user->id)
                ->latest('updated_at')
                ->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'benchmarks' => $benchmarks,
                    'summary' => [
                        'readiness_score' => $position?->readiness_score ?? 0,
                        'overall_percentile' => $position?->overall_percentile ?? 0,
                        'competitive_advantages' => $position?->competitive_advantages ?? [],
                        'competitive_weaknesses' => $position?->competitive_weaknesses ?? [],
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch competitive analysis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get personalized recommendations
     * 
     * @return JsonResponse
     */
    public function recommendations(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            
            // Get latest position
            $position = UserMarketPosition::where('user_id', $user->id)
                ->latest('updated_at')
                ->first();
            
            if (!$position) {
                // Calculate new position
                $position = $this->marketPositioning->calculateMarketPosition($user);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $position->recommendations ?? [],
                    'skill_gaps' => $position->skill_gaps ?? [],
                    'best_fit_roles' => $position->best_fit_roles ?? [],
                    'trending_opportunities' => $position->trending_opportunities ?? [],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recommendations',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
