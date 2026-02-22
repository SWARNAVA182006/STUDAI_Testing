<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SkillGap;
use App\Models\LearningPath;
use App\Models\SkillAssessment;
use App\Models\User;
use App\Services\AI\SkillGapAnalyzerService;
use App\Services\AI\LearningPathCuratorService;
use App\Services\AI\SkillTrendPredictorService;
use App\Services\AI\SkillValidatorService;
use App\Services\AI\SkillAssessmentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SkillAnalyzerController extends Controller
{
    public function __construct(
        private SkillGapAnalyzerService $gapAnalyzer,
        private LearningPathCuratorService $pathCurator,
        private SkillTrendPredictorService $trendPredictor,
        private SkillValidatorService $skillValidator,
        private SkillAssessmentGeneratorService $assessmentGenerator
    ) {}

    /**
     * POST /api/skills/analyze
     * Trigger skill gap analysis for authenticated user
     */
    public function analyzeSkillGaps(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $forceRefresh = $request->boolean('force_refresh', false);
            
            // Check if user has required profile data
            if (empty($user->profile->career_goals['target_roles'] ?? [])) {
                return response()->json([
                    'error' => 'Please set your career goals in your profile before analyzing skill gaps.',
                    'message' => 'Missing target roles in profile.',
                ], 422);
            }
            
            // Run analysis
            $gaps = $this->gapAnalyzer->analyzeUserSkillGaps($user, $forceRefresh);
            
            return response()->json([
                'success' => true,
                'message' => 'Skill gap analysis completed successfully.',
                'data' => [
                    'total_gaps' => $gaps->count(),
                    'critical_gaps' => $gaps->where('gap_severity', 'critical')->count(),
                    'high_priority_gaps' => $gaps->filter(fn($g) => $g['priority_score'] >= 80)->count(),
                    'emerging_skills_missing' => $gaps->where('is_emerging_skill', true)->count(),
                    'gaps' => $gaps->take(10)->values(), // Top 10 priority gaps
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Skill gap analysis failed.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/skills/gaps
     * List all skill gaps for authenticated user, ranked by priority
     */
    public function listSkillGaps(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get filters from request
            $severity = $request->input('severity'); // critical, high, medium, low
            $status = $request->input('status'); // identified, learning, completed, deferred
            $emerging = $request->boolean('emerging_only', false);
            $limit = $request->integer('limit', 50);
            
            // Build query
            $query = $user->skillGaps()->with('learningPath')->rankedByPriority();
            
            if ($severity) {
                $query->bySeverity($severity);
            }
            
            if ($status) {
                $query->where('status', $status);
            }
            
            if ($emerging) {
                $query->emergingSkills();
            }
            
            $gaps = $query->limit($limit)->get();
            
            // Add computed attributes
            $gaps = $gaps->map(function($gap) {
                return [
                    'id' => $gap->id,
                    'skill_name' => $gap->skill_name,
                    'category' => $gap->category,
                    'gap_type' => $gap->gap_type,
                    'gap_severity' => $gap->gap_severity,
                    'severity_badge' => $gap->severityBadge,
                    'difficulty_badge' => $gap->difficultyBadge,
                    'priority_score' => $gap->priorityScore,
                    'impact_score' => $gap->impact_score,
                    'market_demand_score' => $gap->market_demand_score,
                    'salary_impact' => $gap->salaryImpactFormatted,
                    'trend_direction' => $gap->trend_direction,
                    'trend_indicator' => $gap->trendIndicator,
                    'is_emerging_skill' => $gap->is_emerging_skill,
                    'estimated_time' => $gap->estimatedTime,
                    'target_completion_date' => $gap->target_completion_date,
                    'is_overdue' => $gap->isOverdue,
                    'status' => $gap->status,
                    'has_learning_path' => !is_null($gap->learningPath),
                    'learning_path_id' => $gap->learning_path_id,
                    'ai_reasoning' => $gap->ai_reasoning,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $gaps->count(),
                    'gaps' => $gaps,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve skill gaps.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * POST /api/skills/learning-path/{gapId}
     * Generate personalized learning path for a specific skill gap
     */
    public function generateLearningPath(Request $request, int $gapId): JsonResponse
    {
        try {
            $user = Auth::user();
            $gap = SkillGap::findOrFail($gapId);
            
            // Verify gap belongs to user
            if ($gap->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Unauthorized access to skill gap.',
                ], 403);
            }
            
            // Check if path already exists
            if ($gap->learningPath && !$request->boolean('regenerate', false)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Learning path already exists.',
                    'data' => [
                        'learning_path' => $this->formatLearningPath($gap->learningPath),
                    ],
                ]);
            }
            
            // Generate new path
            $forceRefresh = $request->boolean('regenerate', false);
            $path = $this->pathCurator->generateLearningPath($gap, $user, $forceRefresh);
            
            // Update gap status
            $gap->markAsLearning();
            
            return response()->json([
                'success' => true,
                'message' => 'Learning path generated successfully.',
                'data' => [
                    'learning_path' => $this->formatLearningPath($path),
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Learning path generation failed.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/skills/learning-path/{id}
     * Get learning path with all resources
     */
    public function getLearningPath(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $path = LearningPath::with('resources', 'progress')->findOrFail($id);
            
            // Verify path belongs to user
            if ($path->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Unauthorized access to learning path.',
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'learning_path' => $this->formatLearningPath($path),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning path.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * PATCH /api/skills/progress
     * Update learning progress for a resource
     */
    public function updateProgress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'learning_path_id' => 'required|exists:learning_paths,id',
            'learning_resource_id' => 'required|exists:learning_resources,id',
            'time_spent_minutes' => 'required|integer|min:1',
            'completion_percentage' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $pathId = $request->input('learning_path_id');
            $resourceId = $request->input('learning_resource_id');
            $timeSpent = $request->input('time_spent_minutes');
            $completion = $request->input('completion_percentage');
            
            // Verify ownership
            $path = LearningPath::findOrFail($pathId);
            if ($path->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            
            // Record progress
            $progress = \App\Models\LearningProgress::recordProgress(
                $user->id,
                $resourceId,
                $timeSpent,
                $completion
            );
            
            // If resource completed (100%), mark it in the learning path
            if ($completion >= 100) {
                $resource = \App\Models\LearningResource::findOrFail($resourceId);
                $resource->markAsCompleted($user->id);
            }
            
            // Reload path to get updated progress
            $path->refresh();
            
            return response()->json([
                'success' => true,
                'message' => 'Progress updated successfully.',
                'data' => [
                    'progress' => $progress,
                    'path_completion' => $path->completion_percentage,
                    'remaining_resources' => $path->remainingResources,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update progress.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/skills/daily-recommendations
     * Get personalized daily learning recommendations
     */
    public function getDailyRecommendations(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Get user's schedule preferences
            $dailyTimeMinutes = $user->profile->learning_preferences['daily_time_commitment'] ?? 30;
            
            // Find active learning paths
            $activePaths = $user->learningPaths()->active()->with('resources')->get();
            
            $recommendations = [];
            
            foreach ($activePaths as $path) {
                $nextResource = $path->getNextResource();
                if ($nextResource) {
                    $recommendations[] = [
                        'path_id' => $path->id,
                        'path_title' => $path->title,
                        'skill_name' => $path->skillGap->skill_name ?? 'N/A',
                        'resource' => [
                            'id' => $nextResource->id,
                            'title' => $nextResource->title,
                            'type' => $nextResource->resource_type,
                            'type_badge' => $nextResource->typeBadge,
                            'url' => $nextResource->url,
                            'duration_minutes' => $nextResource->duration_minutes,
                            'difficulty' => $nextResource->difficulty,
                        ],
                        'fits_schedule' => $nextResource->duration_minutes <= $dailyTimeMinutes,
                    ];
                }
            }
            
            // Sort by fit with schedule and priority
            usort($recommendations, function($a, $b) {
                if ($a['fits_schedule'] && !$b['fits_schedule']) return -1;
                if (!$a['fits_schedule'] && $b['fits_schedule']) return 1;
                return 0;
            });
            
            // Limit to 3 recommendations for "bite-sized" daily learning
            $recommendations = array_slice($recommendations, 0, 3);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'today' => now()->toDateString(),
                    'daily_time_commitment' => $dailyTimeMinutes,
                    'recommendations' => $recommendations,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate recommendations.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * POST /api/skills/validate
     * Trigger work history validation for user's skills
     */
    public function validateSkills(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $forceRefresh = $request->boolean('force_refresh', false);
            
            // Run validation
            $validations = $this->skillValidator->validateUserSkills($user, $forceRefresh);
            
            // Group by confidence level
            $highConfidence = $validations->filter(fn($v) => $v['confidence_score'] >= 80)->count();
            $verified = $validations->filter(fn($v) => $v['is_verified'])->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Skill validation completed successfully.',
                'data' => [
                    'total_validations' => $validations->count(),
                    'verified_skills' => $verified,
                    'high_confidence_skills' => $highConfidence,
                    'validations' => $validations->take(20), // Top 20
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Skill validation failed.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * POST /api/skills/assessment/{skillId}
     * Generate skill assessment test for a user skill
     */
    public function generateAssessment(Request $request, int $skillId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assessment_type' => 'required|in:multiple_choice,coding,scenario_based,project,mixed',
            'difficulty' => 'required|in:beginner,easy,moderate,intermediate,challenging,advanced',
            'question_count' => 'integer|min:5|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $userSkill = $user->skills()->findOrFail($skillId);
            
            $assessmentType = $request->input('assessment_type');
            $difficulty = $request->input('difficulty');
            $questionCount = $request->integer('question_count', 20);
            
            // Generate assessment
            $assessment = $this->assessmentGenerator->generateAssessment(
                $userSkill,
                $assessmentType,
                $difficulty,
                $questionCount
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Assessment generated successfully.',
                'data' => [
                    'assessment' => [
                        'id' => $assessment->id,
                        'skill_name' => $assessment->skill_name,
                        'assessment_type' => $assessment->assessment_type,
                        'difficulty' => $assessment->difficulty,
                        'total_questions' => $assessment->total_questions,
                        'passing_score' => $assessment->passing_score,
                        'time_limit_minutes' => $assessment->time_limit_minutes,
                        'status' => $assessment->status,
                        'questions' => $assessment->questions, // Include questions for taking test
                    ],
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Assessment generation failed.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * POST /api/skills/assessment/{id}/submit
     * Submit answers to an assessment
     */
    public function submitAssessment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $assessment = SkillAssessment::findOrFail($id);
            
            // Verify ownership
            if ($assessment->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            
            // Check if already submitted
            if ($assessment->status === 'submitted' || $assessment->status === 'graded') {
                return response()->json([
                    'error' => 'Assessment already submitted.',
                ], 422);
            }
            
            $answers = $request->input('answers');
            
            // Submit answers
            $assessment->submit($answers);
            
            // Grade assessment
            $gradingResults = $this->assessmentGenerator->gradeAssessment($assessment, $answers);
            
            // Save grading results
            $assessment->grade($gradingResults['total_score'], $gradingResults['question_results']);
            
            return response()->json([
                'success' => true,
                'message' => 'Assessment submitted and graded successfully.',
                'data' => [
                    'results' => $gradingResults,
                    'assessment' => [
                        'id' => $assessment->id,
                        'score' => $assessment->score,
                        'passed' => $assessment->passed,
                        'grade' => $assessment->grade,
                        'proficiency_awarded' => $assessment->proficiency_awarded,
                        'certificate_url' => $assessment->certificate_url,
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Assessment submission failed.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/skills/assessment/{id}/results
     * Get detailed results of a graded assessment
     */
    public function getAssessmentResults(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $assessment = SkillAssessment::findOrFail($id);
            
            // Verify ownership
            if ($assessment->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized.'], 403);
            }
            
            // Check if graded
            if ($assessment->status !== 'graded') {
                return response()->json([
                    'error' => 'Assessment not yet graded.',
                ], 422);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'assessment' => [
                        'id' => $assessment->id,
                        'skill_name' => $assessment->skill_name,
                        'assessment_type' => $assessment->assessment_type,
                        'difficulty' => $assessment->difficulty,
                        'total_questions' => $assessment->total_questions,
                        'score' => $assessment->score,
                        'score_percentage' => $assessment->scorePercentage,
                        'grade' => $assessment->grade,
                        'passing_score' => $assessment->passing_score,
                        'passed' => $assessment->passed,
                        'proficiency_awarded' => $assessment->proficiency_awarded,
                        'grading_results' => $assessment->grading_results,
                        'certificate_url' => $assessment->certificate_url,
                        'certificate_hash' => $assessment->certificate_hash,
                        'is_shareable' => $assessment->is_shareable,
                        'submitted_at' => $assessment->submitted_at,
                        'graded_at' => $assessment->graded_at,
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve assessment results.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/skills/certificate/{hash}
     * Public endpoint to view/verify a certificate
     */
    public function getCertificate(string $hash): JsonResponse
    {
        try {
            $assessment = SkillAssessment::where('certificate_hash', $hash)->firstOrFail();
            
            // Check if shareable
            if (!$assessment->is_shareable) {
                return response()->json([
                    'error' => 'This certificate is not publicly shareable.',
                ], 403);
            }
            
            // Check if expired
            if ($assessment->certificate_expires_at && $assessment->certificate_expires_at->isPast()) {
                return response()->json([
                    'error' => 'This certificate has expired.',
                    'expired_at' => $assessment->certificate_expires_at,
                ], 410);
            }
            
            $user = User::find($assessment->user_id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'certificate' => [
                        'skill_name' => $assessment->skill_name,
                        'proficiency_level' => $assessment->proficiency_awarded,
                        'score' => $assessment->score,
                        'grade' => $assessment->grade,
                        'issued_to' => $user->name,
                        'issued_at' => $assessment->graded_at,
                        'expires_at' => $assessment->certificate_expires_at,
                        'certificate_hash' => $assessment->certificate_hash,
                        'verification_url' => route('api.skills.certificate', ['hash' => $hash]),
                    ],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Certificate not found or invalid.',
            ], 404);
        }
    }

    /**
     * GET /api/skills/trends
     * Get industry skill trends (2-5 year predictions)
     */
    public function getIndustryTrends(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $industry = $request->input('industry', $user->profile->preferences['industry'] ?? 'Technology');
            $yearsAhead = $request->integer('years_ahead', 5);
            
            // Get trend predictions
            $trends = $this->trendPredictor->predictSkillTrends($industry, $yearsAhead);
            
            // Get emerging skills
            $emergingSkills = $this->trendPredictor->identifyEmergingSkills($industry, 75);
            
            // Get portfolio comparison if user has skills
            $portfolio = null;
            if ($user->skills->count() > 0) {
                $userSkills = $user->skills->map(fn($s) => [
                    'skill_name' => $s->skill_name,
                    'proficiency_score' => $s->proficiency_score,
                ])->toArray();
                
                $portfolio = $this->trendPredictor->compareSkillPortfolio($userSkills, $industry);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'industry' => $industry,
                    'forecast_year' => date('Y') + $yearsAhead,
                    'trends' => $trends,
                    'emerging_skills' => $emergingSkills,
                    'portfolio_analysis' => $portfolio,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve industry trends.',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
            ], 500);
        }
    }

    /**
     * Helper: Format learning path for API response
     */
    private function formatLearningPath(LearningPath $path): array
    {
        return [
            'id' => $path->id,
            'title' => $path->title,
            'description' => $path->description,
            'difficulty' => $path->difficulty,
            'difficulty_badge' => $path->difficultyBadge,
            'status' => $path->status,
            'status_badge' => $path->statusBadge,
            'total_duration_minutes' => $path->total_duration_minutes,
            'total_resources' => $path->total_resources,
            'completion_percentage' => $path->completion_percentage,
            'progress_percentage' => $path->progressPercentage,
            'remaining_hours' => $path->remainingHours,
            'remaining_resources' => $path->remainingResources,
            'estimated_completion_date' => $path->estimatedCompletionDate,
            'daily_time_commitment' => $path->dailyTimeCommitment,
            'steps' => $path->steps,
            'is_ai_generated' => $path->is_ai_generated,
            'created_at' => $path->created_at,
            'resources' => $path->resources->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'url' => $r->url,
                'resource_type' => $r->resource_type,
                'type_badge' => $r->typeBadge,
                'provider' => $r->provider,
                'provider_badge' => $r->providerBadge,
                'difficulty' => $r->difficulty,
                'difficulty_badge' => $r->difficultyBadge,
                'duration_minutes' => $r->duration_minutes,
                'duration_formatted' => $r->durationFormatted,
                'is_free' => $r->is_free,
                'cost_formatted' => $r->costFormatted,
                'rating' => $r->rating,
                'rating_stars' => $r->ratingStars,
                'ai_relevance_score' => $r->ai_relevance_score,
                'step_order' => $r->step_order,
                'has_certificate' => $r->has_certificate,
            ])->values(),
        ];
    }
}
