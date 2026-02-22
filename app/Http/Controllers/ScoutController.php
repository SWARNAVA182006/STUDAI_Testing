<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDNAProfile;
use App\Models\CultureAnalysis;
use App\Models\HiringPattern;
use App\Models\User;
use App\Services\AI\Scout\CorporateDNADecoderService;
use App\Services\AI\Scout\HiringPatternAnalyzerService;
use App\Services\AI\Scout\TeamDynamicsAnalyzerService;
use App\Services\AI\Scout\SuccessPredictorService;
use App\Services\AI\Scout\ResumeAnalyzerService;
use App\Services\AI\Scout\AutomatedShortlistingService;
use App\Services\AI\Scout\DynamicAssessmentService;
use App\Services\AI\Scout\BehavioralIntelligenceService;
use App\Services\AI\Scout\ContinuousLearningService;
use App\Services\AI\Scout\BiasEliminationService;
use App\Services\AI\Scout\PredictiveAnalyticsService;
use App\Services\AI\Scout\TalentPipelineService;
use App\Services\AI\Scout\PassiveCandidateScoutService;
use App\Services\AI\Scout\CandidateExperienceService;
use App\Models\Assessment;
use App\Models\BehavioralAssessment;
use App\Models\SituationalScenario;
use App\Models\ScenarioResponse;
use App\Models\HirePerformance;
use App\Models\AssessmentRefinement;
use App\Models\HiringDecisionOverride;
use App\Models\TalentNeedPrediction;
use App\Models\AnonymizedScreening;
use App\Models\BiasAuditResult;
use App\Models\FairnessMetric;
use App\Models\ProxyDiscriminationAlert;
use App\Models\SuccessPrediction;
use App\Models\TenureForecast;
use App\Models\ProductivityEstimate;
use App\Models\FlightRiskAssessment;
use App\Models\TalentPipeline;
use App\Models\PipelineCandidate;
use App\Models\SilverMedalist;
use App\Models\PassiveCandidateProfile;
use App\Models\CandidateInteraction;
use App\Models\EmployerBrandScore;
use App\Models\CandidateFeedback;
use App\Models\Application;
use App\Models\Job;
use App\Jobs\GenerateAssessmentJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ScoutController extends Controller
{
    protected CorporateDNADecoderService $dnaDecoder;
    protected HiringPatternAnalyzerService $hiringAnalyzer;
    protected TeamDynamicsAnalyzerService $teamAnalyzer;
    protected SuccessPredictorService $successPredictor;
    protected PredictiveAnalyticsService $predictiveAnalytics;
    protected TalentPipelineService $talentPipeline;
    protected PassiveCandidateScoutService $passiveCandidateScout;
    protected CandidateExperienceService $candidateExperience;

    public function __construct(
        CorporateDNADecoderService $dnaDecoder,
        HiringPatternAnalyzerService $hiringAnalyzer,
        TeamDynamicsAnalyzerService $teamAnalyzer,
        SuccessPredictorService $successPredictor,
        PredictiveAnalyticsService $predictiveAnalytics,
        TalentPipelineService $talentPipeline,
        PassiveCandidateScoutService $passiveCandidateScout,
        CandidateExperienceService $candidateExperience
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('employer'); // Custom middleware to ensure employer-only access
        
        $this->dnaDecoder = $dnaDecoder;
        $this->hiringAnalyzer = $hiringAnalyzer;
        $this->teamAnalyzer = $teamAnalyzer;
        $this->successPredictor = $successPredictor;
        $this->predictiveAnalytics = $predictiveAnalytics;
        $this->talentPipeline = $talentPipeline;
        $this->passiveCandidateScout = $passiveCandidateScout;
        $this->candidateExperience = $candidateExperience;
    }

    /**
     * Trigger comprehensive company DNA analysis
     * POST /api/scout/analyze-dna
     */
    public function analyzeDNA(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'force_refresh' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');
        
        // Verify employer owns this company
        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not own this company',
            ], 403);
        }

        try {
            // Check if analysis needed
            $dnaProfile = CompanyDNAProfile::where('company_id', $companyId)->first();
            
            if ($dnaProfile && !$dnaProfile->needsAnalysis() && !$request->input('force_refresh', false)) {
                return response()->json([
                    'success' => true,
                    'message' => 'DNA profile is up-to-date',
                    'data' => $dnaProfile->load('cultureAnalysis'),
                    'analysis_age_days' => $dnaProfile->last_analyzed_at ? 
                        now()->diffInDays($dnaProfile->last_analyzed_at) : null,
                ]);
            }

            // Perform DNA analysis
            $analysis = $this->dnaDecoder->analyzeCompanyDNA($companyId);

            if (!$analysis['success']) {
                return response()->json($analysis, 400);
            }

            // Save or update DNA profile
            $dnaProfile = CompanyDNAProfile::updateOrCreate(
                ['company_id' => $companyId],
                [
                    'cultural_dna' => $analysis['cultural_dna'],
                    'success_traits' => $analysis['success_traits'],
                    'work_style_preferences' => $analysis['work_style_preferences'],
                    'communication_patterns' => $analysis['communication_patterns'],
                    'decision_making_style' => $analysis['decision_making_style'],
                    'dna_completeness_score' => $analysis['dna_completeness_score'],
                    'analysis_confidence' => $analysis['analysis_confidence'],
                    'ai_analysis_summary' => $analysis['ai_summary'],
                    'total_employees_analyzed' => $analysis['total_employees_analyzed'],
                    'total_hires_analyzed' => $analysis['total_hires_analyzed'],
                    'last_analyzed_at' => now(),
                ]
            );

            // Update completion score
            $dnaProfile->updateCompletionScore();

            return response()->json([
                'success' => true,
                'message' => 'DNA analysis completed successfully',
                'data' => $dnaProfile->fresh()->load('cultureAnalysis'),
            ]);

        } catch (\Exception $e) {
            Log::error('DNA Analysis Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get company DNA profile with all related data
     * GET /api/scout/dna-profile
     */
    public function getDNAProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $dnaProfile = CompanyDNAProfile::with([
            'cultureAnalysis',
            'hiringPatterns' => fn($q) => $q->latest()->limit(5),
            'successIndicators' => fn($q) => $q->where('employee_type', 'top_performer')->limit(10),
            'teamDynamics' => fn($q) => $q->latest()->limit(5),
        ])->where('company_id', $companyId)->first();

        if (!$dnaProfile) {
            return response()->json([
                'success' => false,
                'message' => 'DNA profile not found. Please run DNA analysis first.',
                'action_required' => 'analyze-dna',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'dna_profile' => $dnaProfile,
                'health_metrics' => [
                    'dna_health_score' => $dnaProfile->dnaHealthScore,
                    'completion_status' => $dnaProfile->completionStatus,
                    'confidence_level' => $dnaProfile->confidenceLevel,
                    'data_quality' => $dnaProfile->dataQualityBadge,
                ],
                'cultural_insights' => [
                    'archetypes' => $dnaProfile->culturalArchetypes,
                    'top_success_traits' => $dnaProfile->topSuccessTraits,
                ],
                'analysis_metadata' => [
                    'last_analyzed' => $dnaProfile->last_analyzed_at,
                    'needs_refresh' => $dnaProfile->needsAnalysis(),
                    'can_generate_requirements' => $dnaProfile->canGenerateJobRequirements(),
                ],
            ],
        ]);
    }

    /**
     * Analyze hiring patterns
     * POST /api/scout/analyze-hiring-patterns
     */
    public function analyzeHiringPatterns(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'job_id' => 'nullable|integer|exists:jobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');
        $jobId = $request->input('job_id');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $analysis = $this->hiringAnalyzer->analyzeHiringPatterns($companyId, $jobId);

            if (!$analysis['success']) {
                return response()->json($analysis, 400);
            }

            // Save hiring pattern
            $hiringPattern = HiringPattern::create([
                'company_id' => $companyId,
                'job_id' => $jobId,
                'source_effectiveness' => $analysis['source_effectiveness'],
                'successful_hire_characteristics' => $analysis['successful_hire_characteristics'],
                'top_performer_traits' => $analysis['top_performer_traits'],
                'unsuccessful_hire_patterns' => $analysis['unsuccessful_hire_patterns'],
                'quick_departure_indicators' => $analysis['quick_departure_indicators'],
                'retention_by_hire_source' => $analysis['retention_insights'],
                'ai_hiring_recommendations' => $analysis['ai_recommendations'],
                'confidence_score' => $analysis['confidence_score'],
                'total_hires_in_period' => $analysis['total_hires_analyzed'],
                'analysis_start_date' => now()->subYear(),
                'analysis_end_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hiring pattern analysis completed',
                'data' => $hiringPattern,
            ]);

        } catch (\Exception $e) {
            Log::error('Hiring Pattern Analysis Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Predict candidate success against company DNA
     * POST /api/scout/predict-candidate-success
     */
    public function predictCandidateSuccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'candidate' => 'required|array',
            'candidate.skills' => 'required|array',
            'candidate.years_experience' => 'required|integer|min:0',
            'candidate.values' => 'nullable|array',
            'candidate.work_style' => 'nullable|array',
            'candidate.traits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');
        $candidate = $request->input('candidate');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $prediction = $this->successPredictor->predictCandidateSuccess($companyId, $candidate);

            if (!$prediction['success']) {
                return response()->json($prediction, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $prediction,
            ]);

        } catch (\Exception $e) {
            Log::error('Candidate Success Prediction Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prediction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed candidate match report
     * GET /api/scout/candidate-match/{candidateId}
     */
    public function getCandidateMatch(Request $request, int $candidateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Get candidate (job seeker)
        $candidate = User::where('id', $candidateId)
            ->where('account_type', 'job_seeker')
            ->first();

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate not found',
            ], 404);
        }

        // Build candidate profile from user data
        $candidateProfile = [
            'skills' => $candidate->profile->skills ?? [],
            'years_experience' => $candidate->profile->total_experience_years ?? 0,
            'values' => $candidate->profile->values ?? [],
            'work_style' => $candidate->profile->work_preferences ?? [],
            'traits' => $candidate->profile->personality_traits ?? [],
            'education' => $candidate->profile->education ?? [],
            'work_environment_preference' => $candidate->profile->work_environment ?? '',
        ];

        try {
            // Get comprehensive match analysis
            $prediction = $this->successPredictor->predictCandidateSuccess($companyId, $candidateProfile);
            
            // Get team fit if team dynamics available
            $teamFit = $this->teamAnalyzer->assessCandidateTeamFit($companyId, $candidateProfile);

            return response()->json([
                'success' => true,
                'data' => [
                    'candidate' => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'email' => $candidate->email,
                        'profile_summary' => $candidateProfile,
                    ],
                    'success_prediction' => $prediction,
                    'team_fit' => $teamFit,
                    'match_timestamp' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Candidate Match Report Failed', [
                'candidate_id' => $candidateId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Match analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assess team compatibility for candidate
     * POST /api/scout/team-compatibility
     */
    public function assessTeamCompatibility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'department' => 'nullable|string|max:100',
            'candidate' => 'required|array',
            'candidate.skills' => 'required|array',
            'candidate.work_style' => 'nullable|array',
            'candidate.traits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');
        $department = $request->input('department');
        $candidate = $request->input('candidate');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $teamFit = $this->teamAnalyzer->assessCandidateTeamFit($companyId, $candidate, $department);

            return response()->json([
                'success' => true,
                'data' => $teamFit,
            ]);

        } catch (\Exception $e) {
            Log::error('Team Compatibility Assessment Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Assessment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get culture fit criteria for job postings
     * GET /api/scout/culture-fit-criteria
     */
    public function getCultureFitCriteria(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $dnaProfile = CompanyDNAProfile::with('cultureAnalysis')
            ->where('company_id', $companyId)
            ->first();

        if (!$dnaProfile || !$dnaProfile->canGenerateJobRequirements()) {
            return response()->json([
                'success' => false,
                'message' => 'DNA profile incomplete or confidence too low',
                'action_required' => 'analyze-dna',
            ], 400);
        }

        try {
            $criteria = $dnaProfile->getCulturalFitCriteria();
            
            // Add culture analysis criteria if available
            if ($dnaProfile->cultureAnalysis) {
                $criteria['culture_specific'] = $dnaProfile->cultureAnalysis->getCandidateFitCriteria();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cultural_fit_criteria' => $criteria,
                    'top_success_traits' => $dnaProfile->topSuccessTraits,
                    'cultural_archetypes' => $dnaProfile->culturalArchetypes,
                    'recommended_job_description_elements' => [
                        'values_to_highlight' => $dnaProfile->core_values ?? [],
                        'work_style_preferences' => $dnaProfile->work_style_preferences ?? [],
                        'communication_expectations' => $dnaProfile->communication_patterns ?? [],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Culture Fit Criteria Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate criteria: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hiring insights dashboard data
     * GET /api/scout/hiring-insights
     */
    public function getHiringInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyId = $request->input('company_id');

        if (!$this->userOwnsCompany($request->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $dnaProfile = CompanyDNAProfile::with(['cultureAnalysis', 'hiringPatterns', 'teamDynamics'])
                ->where('company_id', $companyId)
                ->first();

            $latestHiringPattern = HiringPattern::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->first();

            $insights = [
                'dna_health' => $dnaProfile ? [
                    'health_score' => $dnaProfile->dnaHealthScore,
                    'completion_score' => $dnaProfile->dna_completeness_score,
                    'confidence' => $dnaProfile->analysis_confidence,
                    'last_analyzed' => $dnaProfile->last_analyzed_at,
                ] : null,
                
                'hiring_effectiveness' => $latestHiringPattern ? [
                    'best_source' => $latestHiringPattern->best_performing_channel ?? 'N/A',
                    'source_rankings' => $latestHiringPattern->source_effectiveness ?? [],
                    'efficiency_score' => $latestHiringPattern->hiringEfficiencyScore,
                    'confidence' => $latestHiringPattern->confidence_score,
                ] : null,

                'success_patterns' => $latestHiringPattern ? [
                    'top_performer_traits' => $latestHiringPattern->top_performer_traits ?? [],
                    'success_characteristics' => $latestHiringPattern->successful_hire_characteristics ?? [],
                    'red_flags' => $latestHiringPattern->redFlags,
                ] : null,

                'team_health' => $dnaProfile && $dnaProfile->teamDynamics->isNotEmpty() ? [
                    'teams_analyzed' => $dnaProfile->teamDynamics->count(),
                    'avg_health_score' => $dnaProfile->teamDynamics->avg('team_performance_score'),
                    'avg_psychological_safety' => $dnaProfile->teamDynamics->avg('psychological_safety_score'),
                ] : null,

                'recommendations' => $latestHiringPattern ? 
                    $latestHiringPattern->ai_hiring_recommendations : 
                    'Run hiring pattern analysis to get recommendations',
            ];

            return response()->json([
                'success' => true,
                'data' => $insights,
            ]);

        } catch (\Exception $e) {
            Log::error('Hiring Insights Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve insights: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze resume with intelligent semantic understanding.
     *
     * POST /api/scout/analyze-resume
     *
     * @param Request $request
     * @param ResumeAnalyzerService $resumeAnalyzer
     * @return JsonResponse
     */
    public function analyzeResume(Request $request, ResumeAnalyzerService $resumeAnalyzer): JsonResponse
    {
        $validated = $request->validate([
            'resume_data' => 'required|array',
            'resume_data.name' => 'required|string',
            'resume_data.summary' => 'nullable|string',
            'resume_data.experience' => 'nullable|array',
            'resume_data.education' => 'nullable|array',
            'resume_data.skills' => 'nullable|array',
            'resume_data.achievements' => 'nullable|array',
            'job_id' => 'nullable|integer|exists:jobs,id'
        ]);

        $companyId = auth()->user()->company_id;

        // Verify company ownership
        if (!$this->userOwnsCompany(auth()->user(), $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to company data'
            ], 403);
        }

        try {
            $result = $resumeAnalyzer->analyzeResume(
                $companyId,
                $validated['resume_data'],
                $validated['job_id'] ?? null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'resume_analysis' => $result['data'],
                    'cached' => $result['cached'] ?? false
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Resume analysis endpoint failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Resume analysis failed'
            ], 500);
        }
    }

    /**
     * Execute multi-stage automated shortlisting pipeline
     * POST /api/scout/shortlist
     */
    public function executeShortlisting(Request $request, AutomatedShortlistingService $shortlistingService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer|exists:jobs,id',
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'integer|exists:applications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $jobId = $request->input('job_id');
        $applicationIds = $request->input('application_ids');

        try {
            // Verify job belongs to employer's company
            $job = \App\Models\Job::findOrFail($jobId);
            $user = auth()->user();

            if (!$this->userOwnsCompany($user, $job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this job'
                ], 403);
            }

            Log::info('Starting automated shortlisting', [
                'job_id' => $jobId,
                'application_count' => count($applicationIds),
                'company_id' => $job->company_id
            ]);

            // Execute the 4-round shortlisting pipeline
            $result = $shortlistingService->executeShortlistingPipeline($jobId, $applicationIds);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'shortlisting_results' => $result['data'],
                'summary' => [
                    'total_evaluated' => $result['data']['total_applications'],
                    'shortlisted' => count($result['data']['shortlisted']),
                    'funnel' => [
                        'round_1_passed' => $result['data']['round_1_passed'],
                        'round_2_passed' => $result['data']['round_2_passed'],
                        'round_3_passed' => $result['data']['round_3_passed'],
                        'round_4_passed' => $result['data']['round_4_passed'],
                    ],
                    'processing_time_seconds' => $result['data']['processing_time']
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Automated shortlisting failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Shortlisting pipeline failed'
            ], 500);
        }
    }

    /**
     * Generate adaptive assessment for a candidate
     * POST /api/scout/assessment/generate
     * 
     * Rate limit: 20 per minute
     */
    public function generateAssessment(Request $request, DynamicAssessmentService $assessmentService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:applications,id',
            'job_id' => 'required|integer|exists:jobs,id',
            'type' => 'sometimes|in:comprehensive,technical,behavioral,case_study',
            'initial_difficulty' => 'sometimes|in:easy,medium,hard,expert',
            'question_count' => 'sometimes|integer|min:3|max:20',
            'time_limit' => 'sometimes|integer|min:15|max:180', // 15 min to 3 hours
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $applicationId = $request->input('application_id');
            $jobId = $request->input('job_id');

            // Load application and job to verify ownership
            $application = Application::with('user')->findOrFail($applicationId);
            $job = Job::with('company')->findOrFail($jobId);

            // Verify company ownership
            $user = $request->user();
            if (!$this->userOwnsCompany($user, $job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not own this company'
                ], 403);
            }

            // Verify application is for this job
            if ($application->job_id !== $jobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application does not match job'
                ], 400);
            }

            // Prepare options
            $options = [
                'type' => $request->input('type', 'comprehensive'),
                'initial_difficulty' => $request->input('initial_difficulty', 'medium'),
                'question_count' => $request->input('question_count', 5),
                'time_limit' => $request->input('time_limit', 60),
            ];

            // Generate assessment
            $result = $assessmentService->generateAssessment($applicationId, $jobId, $options);

            Log::info('Assessment generated successfully', [
                'assessment_id' => $result['assessment_id'],
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'company_id' => $job->company_id,
                'type' => $options['type']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assessment generated successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Assessment generation failed', [
                'application_id' => $request->input('application_id'),
                'job_id' => $request->input('job_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Assessment generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit answer for assessment question
     * POST /api/scout/assessment/{assessmentId}/submit
     * 
     * Rate limit: 60 per minute
     */
    public function submitAssessmentAnswer(Request $request, int $assessmentId, DynamicAssessmentService $assessmentService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|integer|exists:scout_assessment_questions,id',
            'answer' => 'sometimes|string',
            'code_submission' => 'sometimes|string',
            'time_taken' => 'sometimes|integer|min:0', // Seconds
            'confidence_level' => 'sometimes|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Load assessment with job and application
            $assessment = Assessment::with(['job.company', 'application'])->findOrFail($assessmentId);

            // Verify company ownership
            $user = $request->user();
            if (!$this->userOwnsCompany($user, $assessment->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not own this company'
                ], 403);
            }

            // Check if assessment can still be taken
            if (!$assessment->canBeTaken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment cannot be taken (completed or expired)'
                ], 400);
            }

            // Prepare answer data
            $answer = $request->input('answer') ?? $request->input('code_submission');
            $timeTaken = $request->input('time_taken', null);
            $confidenceLevel = $request->input('confidence_level', null);

            if (!$answer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answer or code submission is required'
                ], 400);
            }

            // Submit answer
            $result = $assessmentService->submitAnswer(
                $assessmentId,
                $request->input('question_id'),
                $answer,
                $timeTaken,
                $confidenceLevel
            );

            Log::info('Assessment answer submitted', [
                'assessment_id' => $assessmentId,
                'question_id' => $request->input('question_id'),
                'is_correct' => $result['evaluation']['is_correct'],
                'score' => $result['evaluation']['score'],
                'completed' => $result['completed'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Answer submitted successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Assessment answer submission failed', [
                'assessment_id' => $assessmentId,
                'question_id' => $request->input('question_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Answer submission failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assessment results (current progress or final results)
     * GET /api/scout/assessment/{assessmentId}/results
     * 
     * Rate limit: 30 per minute
     */
    public function getAssessmentResults(int $assessmentId, DynamicAssessmentService $assessmentService): JsonResponse
    {
        try {
            // Load assessment with all relationships
            $assessment = Assessment::with([
                'job.company',
                'application.user',
                'questions',
                'responses.question'
            ])->findOrFail($assessmentId);

            // Verify company ownership
            $user = request()->user();
            if (!$this->userOwnsCompany($user, $assessment->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not own this company'
                ], 403);
            }

            // Prepare results data
            $results = [
                'assessment_id' => $assessment->id,
                'candidate_name' => $assessment->application->user->name,
                'job_title' => $assessment->job->title,
                'type' => $assessment->type,
                'status' => $assessment->status,
                'total_questions' => $assessment->total_questions,
                'questions_answered' => $assessment->questions_answered,
                'progress_percentage' => $assessment->progress_percentage,
                'started_at' => $assessment->started_at,
                'completed_at' => $assessment->completed_at,
            ];

            // If completed, include final results
            if ($assessment->status === 'completed') {
                $results['final_score'] = $assessment->final_score;
                $results['proficiency_level'] = $assessment->proficiency_level;
                $results['recommendation'] = $assessment->recommendation;
                $results['performance_summary'] = $assessment->performance_summary;
                
                // Include all questions with responses
                $results['questions'] = $assessment->questions->map(function($question) {
                    return [
                        'question_number' => $question->question_number,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'difficulty' => $question->difficulty,
                        'category' => $question->category,
                        'points' => $question->points,
                        'response' => $question->response ? [
                            'answer' => $question->response->answer ?? $question->response->code_submission,
                            'is_correct' => $question->response->is_correct,
                            'score' => $question->response->score,
                            'max_score' => $question->response->max_score,
                            'time_taken_seconds' => $question->response->time_taken_seconds,
                            'confidence_level' => $question->response->confidence_level,
                            'feedback' => $question->response->evaluation_feedback,
                        ] : null
                    ];
                });
                
            } else {
                // In progress - show current metrics
                $results['current_difficulty'] = $assessment->current_difficulty;
                $results['time_remaining'] = $assessment->time_remaining;
                
                // Calculate current performance if any answers submitted
                if ($assessment->questions_answered > 0) {
                    $metrics = $assessmentService->calculatePerformanceMetrics($assessment);
                    $results['current_performance'] = $metrics;
                }
            }

            Log::info('Assessment results retrieved', [
                'assessment_id' => $assessmentId,
                'status' => $assessment->status,
                'company_id' => $assessment->job->company_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);

        } catch (\Exception $e) {
            Log::error('Assessment results retrieval failed', [
                'assessment_id' => $assessmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assessment results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate assessment asynchronously using queue
     * POST /api/scout/assessment/generate-async
     * 
     * Rate limit: 20 per minute
     */
    public function generateAssessmentAsync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:applications,id',
            'job_id' => 'required|integer|exists:jobs,id',
            'type' => 'sometimes|in:comprehensive,technical,behavioral,case_study',
            'initial_difficulty' => 'sometimes|in:easy,medium,hard,expert',
            'question_count' => 'sometimes|integer|min:3|max:20',
            'time_limit' => 'sometimes|integer|min:15|max:180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $applicationId = $request->input('application_id');
            $jobId = $request->input('job_id');

            // Load application and job to verify ownership
            $application = Application::with('user')->findOrFail($applicationId);
            $job = Job::with('company')->findOrFail($jobId);

            // Verify company ownership
            $user = $request->user();
            if (!$this->userOwnsCompany($user, $job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not own this company'
                ], 403);
            }

            // Verify application is for this job
            if ($application->job_id !== $jobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application does not match job'
                ], 400);
            }

            // Prepare options
            $options = [
                'type' => $request->input('type', 'comprehensive'),
                'initial_difficulty' => $request->input('initial_difficulty', 'medium'),
                'question_count' => $request->input('question_count', 5),
                'time_limit' => $request->input('time_limit', 60),
            ];

            // Dispatch job
            GenerateAssessmentJob::dispatch($applicationId, $jobId, $options, $user->id);

            Log::info('Assessment generation job dispatched', [
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'company_id' => $job->company_id,
                'options' => $options,
                'requested_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assessment generation started',
                'data' => [
                    'application_id' => $applicationId,
                    'job_id' => $jobId,
                    'status' => 'queued',
                    'progress_url' => route('api.scout.assessment.progress', [
                        'applicationId' => $applicationId,
                        'jobId' => $jobId
                    ])
                ]
            ], 202); // 202 Accepted

        } catch (\Exception $e) {
            Log::error('Failed to dispatch assessment generation job', [
                'application_id' => $request->input('application_id'),
                'job_id' => $request->input('job_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start assessment generation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check assessment generation progress
     * GET /api/scout/assessment/progress/{applicationId}/{jobId}
     * 
     * Rate limit: 60 per minute (polling endpoint)
     */
    public function checkAssessmentProgress(int $applicationId, int $jobId): JsonResponse
    {
        try {
            // Verify ownership
            $application = Application::with('job.company')->findOrFail($applicationId);
            $user = request()->user();

            if (!$this->userOwnsCompany($user, $application->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not own this company'
                ], 403);
            }

            // Check progress
            $progress = GenerateAssessmentJob::checkProgress($applicationId, $jobId);

            if (!$progress) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'not_found',
                        'message' => 'No generation in progress'
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $progress
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to check assessment progress', [
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Check if user owns the company
     */
    private function userOwnsCompany(User $user, int $companyId): bool
    {
        // Assuming employer users have a company_id field or relationship
        // Adjust based on your actual schema
        return $user->account_type === 'employer' && 
               ($user->company_id === $companyId || $user->companies()->where('id', $companyId)->exists());
    }

    // ============================================================================
    // BEHAVIORAL AND SITUATIONAL INTELLIGENCE ENDPOINTS
    // ============================================================================

    /**
     * Generate behavioral assessment with situational judgment tests
     * 
     * POST /api/scout/behavioral/generate
     * Rate limit: 20 requests per minute
     *
     * @param Request $request
     * @param BehavioralIntelligenceService $behavioralService
     * @return JsonResponse
     */
    public function generateBehavioralAssessment(Request $request, BehavioralIntelligenceService $behavioralService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
                'job_id' => 'required|integer|exists:jobs,id',
                'scenario_count' => 'nullable|integer|min:3|max:10',
                'type' => 'nullable|string|in:comprehensive,cultural_fit_focus,leadership_focus',
                'focus_areas' => 'nullable|array',
                'focus_areas.*' => 'string|in:cultural_fit,emotional_intelligence,leadership,communication,problem_solving'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            $applicationId = $request->input('application_id');
            $jobId = $request->input('job_id');

            // Load application and job
            $application = Application::with('job.company')->findOrFail($applicationId);
            $job = Job::with('company')->findOrFail($jobId);

            // Verify application belongs to job
            if ($application->job_id !== $jobId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application does not belong to the specified job'
                ], 400);
            }

            // Verify company ownership
            $user = auth()->user();
            if (!$this->userOwnsCompany($user, $job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to assess this application'
                ], 403);
            }

            // Check if assessment already exists
            $existingAssessment = BehavioralAssessment::where('application_id', $applicationId)
                ->where('job_id', $jobId)
                ->where('status', '!=', 'expired')
                ->first();

            if ($existingAssessment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Behavioral assessment already exists for this application',
                    'assessment_id' => $existingAssessment->id
                ], 400);
            }

            // Prepare options
            $options = [
                'scenario_count' => $request->input('scenario_count', 6),
                'type' => $request->input('type', 'comprehensive'),
                'focus_areas' => $request->input('focus_areas', ['cultural_fit', 'emotional_intelligence', 'leadership'])
            ];

            // Generate behavioral assessment
            $result = $behavioralService->generateBehavioralAssessment($applicationId, $jobId, $options);

            Log::info('Behavioral assessment generated successfully', [
                'assessment_id' => $result['assessment_id'],
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Behavioral assessment generated successfully',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to generate behavioral assessment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate assessment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit response to a situational scenario
     * 
     * POST /api/scout/behavioral/{assessmentId}/submit
     * Rate limit: 60 requests per minute
     *
     * @param Request $request
     * @param int $assessmentId
     * @param BehavioralIntelligenceService $behavioralService
     * @return JsonResponse
     */
    public function submitScenarioResponse(Request $request, int $assessmentId, BehavioralIntelligenceService $behavioralService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'scenario_id' => 'required|integer|exists:scout_situational_scenarios,id',
                'selected_approach' => 'required|integer|min:0',
                'reasoning' => 'required|string|min:50|max:2000',
                'time_taken' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            // Load assessment
            $assessment = BehavioralAssessment::with('job.company')->findOrFail($assessmentId);

            // Verify company ownership
            $user = auth()->user();
            if (!$this->userOwnsCompany($user, $assessment->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to submit responses for this assessment'
                ], 403);
            }

            // Verify assessment can still be taken
            if (!$assessment->canBeTaken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assessment cannot be modified (status: ' . $assessment->status . ')'
                ], 400);
            }

            $scenarioId = $request->input('scenario_id');
            $selectedApproach = $request->input('selected_approach');
            $reasoning = $request->input('reasoning');
            $timeTaken = $request->input('time_taken', 0);

            // Prepare response data
            $responseData = [
                'selected_approach' => $selectedApproach,
                'reasoning' => $reasoning,
                'time_taken' => $timeTaken
            ];

            // Evaluate response
            $result = $behavioralService->evaluateScenarioResponse($assessmentId, $scenarioId, $responseData);

            Log::info('Scenario response submitted and evaluated', [
                'assessment_id' => $assessmentId,
                'scenario_id' => $scenarioId,
                'is_complete' => $result['is_complete'],
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Response evaluated successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to submit scenario response', [
                'assessment_id' => $assessmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit response: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get behavioral assessment results
     * 
     * GET /api/scout/behavioral/{assessmentId}/results
     * Rate limit: 30 requests per minute
     *
     * @param int $assessmentId
     * @param BehavioralIntelligenceService $behavioralService
     * @return JsonResponse
     */
    public function getBehavioralAssessmentResults(int $assessmentId, BehavioralIntelligenceService $behavioralService): JsonResponse
    {
        try {
            // Load assessment
            $assessment = BehavioralAssessment::with('job.company')->findOrFail($assessmentId);

            // Verify company ownership
            $user = auth()->user();
            if (!$this->userOwnsCompany($user, $assessment->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to view this assessment'
                ], 403);
            }

            // Get results
            $results = $behavioralService->getAssessmentResults($assessmentId);

            Log::info('Behavioral assessment results retrieved', [
                'assessment_id' => $assessmentId,
                'status' => $assessment->status,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'data' => $results
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get behavioral assessment results', [
                'assessment_id' => $assessmentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve results: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track hired candidate's performance
     */
    public function trackPerformance(Request $request, ContinuousLearningService $learningService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
                'hire_date' => 'required|date',
                'review_period' => 'required|string|in:probation,6_month,annual,18_month,24_month',
                'performance_rating' => 'required|numeric|min:1|max:5',
                'technical_skills_rating' => 'nullable|numeric|min:1|max:5',
                'soft_skills_rating' => 'nullable|numeric|min:1|max:5',
                'cultural_fit_rating' => 'nullable|numeric|min:1|max:5',
                'productivity_rating' => 'nullable|numeric|min:1|max:5',
                'team_collaboration_rating' => 'nullable|numeric|min:1|max:5',
                'leadership_rating' => 'nullable|numeric|min:1|max:5',
                'retention_status' => 'nullable|string|in:active,promoted,transferred,resigned_early,terminated,resigned_planned',
                'promotion_count' => 'nullable|integer|min:0',
                'manager_feedback' => 'nullable|string|max:5000',
                'peer_feedback' => 'nullable|string|max:5000',
                'achievements' => 'nullable|array',
                'challenges' => 'nullable|array',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $application = Application::with('job.company')->findOrFail($request->application_id);

            // Check ownership
            if (!$this->userOwnsCompany($application->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to track performance for this hire'
                ], 403);
            }

            $performance = $learningService->trackPerformance(
                $request->application_id,
                $request->all()
            );

            Log::info('Performance tracked', [
                'application_id' => $request->application_id,
                'performance_id' => $performance->id,
                'rating' => $request->performance_rating
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Performance tracked successfully',
                'data' => [
                    'performance_id' => $performance->id,
                    'performance_rating' => $performance->performance_rating,
                    'performance_level' => $performance->performance_level,
                    'prediction_accuracy' => $performance->prediction_accuracy,
                    'is_high_performer' => $performance->is_high_performer,
                    'tenure_months' => $performance->tenure_months
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track performance', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track performance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record hiring manager's override decision
     */
    public function recordOverride(Request $request, ContinuousLearningService $learningService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
                'scout_recommendation' => 'required|string|in:STRONG HIRE,RECOMMEND,CONSIDER,CAUTION,NOT RECOMMENDED',
                'manager_decision' => 'required|string|in:STRONG HIRE,RECOMMEND,CONSIDER,CAUTION,NOT RECOMMENDED',
                'reason' => 'nullable|string|max:5000',
                'factors' => 'nullable|array',
                'confidence_level' => 'nullable|string|in:high,medium,low',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $application = Application::with('job.company')->findOrFail($request->application_id);

            // Check ownership
            if (!$this->userOwnsCompany($application->job->company_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to record override for this application'
                ], 403);
            }

            $override = $learningService->recordOverride(
                $request->application_id,
                array_merge($request->all(), ['manager_id' => auth()->id()])
            );

            Log::info('Override recorded', [
                'application_id' => $request->application_id,
                'override_id' => $override->id,
                'type' => $override->override_type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Decision override recorded successfully',
                'data' => [
                    'override_id' => $override->id,
                    'override_type' => $override->override_type,
                    'override_direction' => $override->override_direction,
                    'override_magnitude' => $override->override_magnitude,
                    'learning_impact' => 'System will learn from this decision to improve future recommendations'
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to record override', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record override: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive learning insights
     */
    public function getLearningInsights(Request $request, ContinuousLearningService $learningService): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;

            if (!$companyId || !$this->userOwnsCompany($companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $insights = $learningService->getLearningInsights($companyId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $insights
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get learning insights', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get talent need predictions
     */
    public function getTalentPredictions(Request $request, ContinuousLearningService $learningService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'time_horizon_months' => 'nullable|integer|min:3|max:24',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $companyId = auth()->user()->company_id;

            if (!$companyId || !$this->userOwnsCompany($companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $prediction = $learningService->predictTalentNeeds($companyId, $request->all());

            Log::info('Talent predictions generated', [
                'company_id' => $companyId,
                'prediction_id' => $prediction->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Talent predictions generated successfully',
                'data' => [
                    'prediction_id' => $prediction->id,
                    'prediction_horizon_months' => $prediction->prediction_horizon_months,
                    'predicted_headcount' => $prediction->predicted_headcount,
                    'predicted_roles' => $prediction->predicted_roles,
                    'predicted_skills_demand' => $prediction->top_predicted_skills,
                    'growth_trend' => $prediction->growth_trend,
                    'confidence_score' => $prediction->confidence_score,
                    'confidence_level' => $prediction->confidence_level,
                    'recommendations' => $prediction->recommendations,
                    'ai_analysis' => $prediction->ai_analysis,
                    'prediction_summary' => $prediction->prediction_summary
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to generate talent predictions', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate predictions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger assessment criteria refinement
     */
    public function triggerRefinement(Request $request, ContinuousLearningService $learningService): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'refinement_type' => 'nullable|string|in:comprehensive,technical_focus,cultural_focus,role_specific',
                'period_months' => 'nullable|integer|min:3|max:24',
                'auto_apply' => 'nullable|boolean',
                'metadata' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $companyId = auth()->user()->company_id;

            if (!$companyId || !$this->userOwnsCompany($companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $refinement = $learningService->refineAssessmentCriteria($companyId, $request->all());

            Log::info('Assessment refinement triggered', [
                'company_id' => $companyId,
                'refinement_id' => $refinement->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assessment criteria refined successfully',
                'data' => [
                    'refinement_id' => $refinement->id,
                    'refinement_type' => $refinement->refinement_type,
                    'data_points_analyzed' => $refinement->data_points_analyzed,
                    'confidence_score' => $refinement->confidence_score,
                    'confidence_level' => $refinement->confidence_level,
                    'performance_improvement_estimate' => $refinement->performance_improvement_estimate,
                    'estimated_impact' => $refinement->estimated_impact,
                    'weight_changes' => $refinement->weight_changes,
                    'most_improved_factor' => $refinement->most_improved_factor,
                    'strongest_predictor' => $refinement->getStrongestPredictor(),
                    'ai_insights' => $refinement->ai_insights,
                    'is_applied' => $refinement->is_applied,
                    'applied_at' => $refinement->applied_at
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to refine assessment criteria', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refine criteria: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anonymize candidate for bias-free screening
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function anonymizeCandidate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
                'anonymization_level' => 'nullable|in:minimal,standard,strict',
                'force_reanonymize' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            // Verify application belongs to company
            $application = Application::with('job')->findOrFail($request->application_id);
            if ($application->job->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to application'
                ], 403);
            }

            $biasService = app(BiasEliminationService::class);
            
            $result = $biasService->anonymizeCandidate(
                $request->application_id,
                [
                    'level' => $request->input('anonymization_level', 'standard'),
                    'force_reanonymize' => $request->input('force_reanonymize', false)
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Candidate anonymized successfully',
                'data' => $result
            ], $result['already_existed'] ?? false ? 200 : 201);

        } catch (\Exception $e) {
            Log::error('Failed to anonymize candidate', [
                'application_id' => $request->application_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to anonymize candidate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Conduct bias audit
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function conductBiasAudit(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'timeframe' => 'nullable|in:1_month,3_months,6_months,12_months,24_months',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            $biasService = app(BiasEliminationService::class);
            
            $result = $biasService->auditForBias(
                $companyId,
                [
                    'timeframe' => $request->input('timeframe', '6_months')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Bias audit completed',
                'data' => $result
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to conduct bias audit', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to conduct audit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get decision explanation
     *
     * @param Request $request
     * @param int $applicationId
     * @return JsonResponse
     */
    public function getDecisionExplanation(Request $request, int $applicationId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'decision_type' => 'required|in:shortlist,reject,interview,offer,final_reject',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            // Verify application belongs to company
            $application = Application::with('job')->findOrFail($applicationId);
            if ($application->job->company_id !== $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to application'
                ], 403);
            }

            $biasService = app(BiasEliminationService::class);
            
            $result = $biasService->generateDecisionExplanation(
                $applicationId,
                $request->decision_type
            );

            return response()->json([
                'success' => true,
                'message' => 'Decision explanation generated',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to generate decision explanation', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate explanation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get diversity analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDiversityAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'timeframe' => 'nullable|in:1_month,3_months,6_months,12_months,24_months',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            $biasService = app(BiasEliminationService::class);
            
            $result = $biasService->getDiversityAnalytics(
                $companyId,
                [
                    'timeframe' => $request->input('timeframe', '12_months')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Diversity analytics retrieved',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get diversity analytics', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get proxy discrimination alerts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProxyDiscriminationAlerts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending_review,investigating,resolved,false_positive',
                'severity' => 'nullable|in:low,medium,high,critical',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            $query = ProxyDiscriminationAlert::forCompany($companyId);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('severity')) {
                $query->bySeverity($request->severity);
            }

            $alerts = $query->orderBy('detected_at', 'desc')
                           ->limit($request->input('limit', 50))
                           ->get();

            return response()->json([
                'success' => true,
                'message' => 'Proxy discrimination alerts retrieved',
                'data' => [
                    'alerts' => $alerts,
                    'total_count' => $alerts->count(),
                    'critical_count' => $alerts->where('severity', 'critical')->count(),
                    'unresolved_count' => $alerts->where('is_active', true)->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get proxy discrimination alerts', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve proxy discrimination alert
     *
     * @param Request $request
     * @param int $alertId
     * @return JsonResponse
     */
    public function resolveProxyAlert(Request $request, int $alertId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'resolution_type' => 'required|in:resolved,false_positive',
                'resolution_notes' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            $alert = ProxyDiscriminationAlert::forCompany($companyId)->findOrFail($alertId);

            if ($request->resolution_type === 'resolved') {
                $alert->resolve(auth()->id(), $request->resolution_notes);
            } else {
                $alert->markAsFalsePositive(auth()->id(), $request->resolution_notes);
            }

            return response()->json([
                'success' => true,
                'message' => 'Alert resolved successfully',
                'data' => [
                    'alert_id' => $alert->id,
                    'status' => $alert->status_display,
                    'resolved_at' => $alert->resolved_at
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to resolve proxy alert', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve alert: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fairness metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFairnessMetrics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'nullable|integer|exists:jobs,id',
                'metric_type' => 'nullable|string',
                'period_days' => 'nullable|integer|min:1|max:730',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;

            $query = FairnessMetric::forCompany($companyId);

            if ($request->has('job_id')) {
                $query->forJob($request->job_id);
            }

            if ($request->has('metric_type')) {
                $query->byType($request->metric_type);
            }

            if ($request->has('period_days')) {
                $query->inPeriod(now()->subDays($request->period_days));
            }

            $metrics = $query->orderBy('measured_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Fairness metrics retrieved',
                'data' => [
                    'metrics' => $metrics,
                    'total_count' => $metrics->count(),
                    'failing_count' => $metrics->where('passes_threshold', false)->count(),
                    'disparate_impact_count' => $metrics->where('status', 'disparate_impact')->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get fairness metrics', [
                'company_id' => auth()->user()->company_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Predict success probability for a candidate
     * POST /api/scout/predictive/success
     */
    public function predictSuccessProbability(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $prediction = $this->predictiveAnalytics->predictSuccessProbability($application);

            // Store prediction
            $storedPrediction = SuccessPrediction::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'job_id' => $application->job_id,
                    'company_id' => $companyId,
                ],
                [
                    'user_id' => $application->user_id,
                    'success_probability' => $prediction['success_probability'],
                    'confidence_score' => $prediction['confidence_score'],
                    'success_category' => $prediction['success_category'],
                    'factor_scores' => $prediction['factor_scores'],
                    'key_strengths' => $prediction['key_strengths'],
                    'key_concerns' => $prediction['key_concerns'],
                    'ai_insights' => $prediction['ai_insights'],
                    'prediction_basis' => $prediction['prediction_basis'],
                    'recommendation' => $prediction['recommendation'],
                    'predicted_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Success probability predicted',
                'data' => [
                    'prediction_id' => $storedPrediction->id,
                    'success_probability' => $storedPrediction->success_percentage,
                    'confidence' => $storedPrediction->confidence_percentage,
                    'category' => $storedPrediction->category_display,
                    'factor_scores' => $prediction['factor_scores'],
                    'top_strengths' => $storedPrediction->top_strengths,
                    'top_concerns' => $storedPrediction->top_concerns,
                    'recommendation' => $prediction['recommendation'],
                    'comparable_profiles' => $prediction['comparable_profiles'] ?? [],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to predict success probability', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to predict success: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forecast candidate tenure
     * POST /api/scout/predictive/tenure
     */
    public function forecastTenure(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $forecast = $this->predictiveAnalytics->forecastTenure($application);

            // Store forecast
            $storedForecast = TenureForecast::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'company_id' => $companyId,
                ],
                [
                    'user_id' => $application->user_id,
                    'predicted_tenure_months' => $forecast['predicted_tenure_months'],
                    'tenure_range_min' => $forecast['tenure_range']['min'],
                    'tenure_range_max' => $forecast['tenure_range']['max'],
                    'confidence_level' => $forecast['confidence_level'],
                    'flight_risk_score' => $forecast['flight_risk_score'],
                    'risk_category' => $forecast['risk_category'],
                    'retention_factors' => $forecast['retention_factors'],
                    'risk_indicators' => $forecast['risk_indicators'],
                    'ai_insights' => $forecast['ai_insights'],
                    'recommendation' => $forecast['recommendation'],
                    'forecast_date' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Tenure forecast generated',
                'data' => [
                    'forecast_id' => $storedForecast->id,
                    'predicted_tenure_months' => $storedForecast->predicted_tenure_months,
                    'predicted_tenure_years' => $storedForecast->tenure_years,
                    'tenure_range' => $storedForecast->tenure_range_display,
                    'tenure_category' => $storedForecast->tenure_category,
                    'flight_risk_score' => $storedForecast->flight_risk_percentage,
                    'risk_level' => $storedForecast->risk_level,
                    'is_flight_risk' => $storedForecast->is_flight_risk,
                    'confidence' => $storedForecast->confidence_display,
                    'retention_factors' => $forecast['retention_factors'],
                    'risk_indicators' => $forecast['risk_indicators'],
                    'recommendation' => $forecast['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to forecast tenure', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to forecast tenure: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estimate time to productivity
     * POST /api/scout/predictive/productivity
     */
    public function estimateProductivity(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $estimate = $this->predictiveAnalytics->estimateTimeToProductivity($application, $application->job);

            // Store estimate
            $storedEstimate = ProductivityEstimate::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'job_id' => $application->job_id,
                    'company_id' => $companyId,
                ],
                [
                    'user_id' => $application->user_id,
                    'estimated_weeks_to_productivity' => $estimate['estimated_weeks'],
                    'productivity_category' => $estimate['productivity_category'],
                    'productivity_milestones' => $estimate['productivity_milestones'],
                    'learning_curve_factors' => $estimate['learning_curve_factors'],
                    'experience_gap_analysis' => $estimate['experience_gap_analysis'],
                    'support_requirements' => $estimate['support_requirements'],
                    'ai_insights' => $estimate['ai_insights'],
                    'recommendation' => $estimate['recommendation'],
                    'estimated_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Productivity estimate generated',
                'data' => [
                    'estimate_id' => $storedEstimate->id,
                    'estimated_weeks' => $storedEstimate->estimated_weeks_to_productivity,
                    'estimated_months' => $storedEstimate->months_to_productivity,
                    'weeks_category' => $storedEstimate->weeks_category,
                    'productivity_category' => $storedEstimate->productivity_category_display,
                    'productivity_milestones' => $estimate['productivity_milestones'],
                    'learning_curve' => $storedEstimate->learning_curve_display,
                    'experience_gaps' => $storedEstimate->experience_gap_summary,
                    'support_needed' => $storedEstimate->support_requirements_summary,
                    'recommendation' => $estimate['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to estimate productivity', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to estimate productivity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assess flight risk
     * POST /api/scout/predictive/flight-risk
     */
    public function assessFlightRisk(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $assessment = $this->predictiveAnalytics->identifyFlightRisks($application);

            // Store assessment
            $storedAssessment = FlightRiskAssessment::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'company_id' => $companyId,
                ],
                [
                    'user_id' => $application->user_id,
                    'risk_score' => $assessment['risk_score'],
                    'risk_level' => $assessment['risk_level'],
                    'risk_category' => $assessment['risk_category'],
                    'risk_factors' => $assessment['risk_factors'],
                    'mitigation_strategies' => $assessment['mitigation_strategies'],
                    'ai_insights' => $assessment['ai_insights'],
                    'recommendation' => $assessment['recommendation'],
                    'assessment_date' => now(),
                    'reassessment_due' => now()->addDays(30),
                    'status' => 'active',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Flight risk assessment completed',
                'data' => [
                    'assessment_id' => $storedAssessment->id,
                    'risk_score' => $storedAssessment->risk_percentage,
                    'risk_level' => $storedAssessment->risk_level_display,
                    'risk_category' => $storedAssessment->risk_category_display,
                    'is_critical' => $storedAssessment->is_critical_risk,
                    'is_high_priority' => $storedAssessment->is_high_priority,
                    'priority_score' => $storedAssessment->priority_score,
                    'risk_factors' => $assessment['risk_factors'],
                    'top_risk_factors' => $storedAssessment->getTopRiskFactors(3),
                    'mitigation_strategies' => $assessment['mitigation_strategies'],
                    'priority_actions' => $storedAssessment->getPriorityActions(),
                    'reassessment_due' => $storedAssessment->reassessment_due,
                    'recommendation' => $assessment['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to assess flight risk', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assess flight risk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate development plan
     * POST /api/scout/predictive/development
     */
    public function generateDevelopmentPlan(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $plan = $this->predictiveAnalytics->predictDevelopmentNeeds($application, $application->job);

            return response()->json([
                'success' => true,
                'message' => 'Development plan generated',
                'data' => [
                    'skill_gaps' => $plan['skill_gaps'],
                    'training_recommendations' => $plan['training_recommendations'],
                    'development_timeline' => $plan['development_timeline'],
                    'resource_requirements' => $plan['resource_requirements'],
                    'success_metrics' => $plan['success_metrics'],
                    'recommendation' => $plan['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to generate development plan', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate development plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create personalized onboarding plan
     * POST /api/scout/predictive/onboarding
     */
    public function createOnboardingPlan(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $plan = $this->predictiveAnalytics->generateOnboardingPlan($application, $application->job);

            return response()->json([
                'success' => true,
                'message' => 'Onboarding plan created',
                'data' => [
                    'plan_phases' => $plan['plan_phases'],
                    'key_milestones' => $plan['key_milestones'],
                    'resource_assignments' => $plan['resource_assignments'],
                    'success_checkpoints' => $plan['success_checkpoints'],
                    'plan_duration_days' => $plan['plan_duration_days'],
                    'recommendation' => $plan['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to create onboarding plan', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create onboarding plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Predict career path
     * POST /api/scout/predictive/career-path
     */
    public function predictCareerPath(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'application_id' => 'required|integer|exists:applications,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $request->application_id)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $prediction = $this->predictiveAnalytics->predictCareerPath($application, $application->user);

            return response()->json([
                'success' => true,
                'message' => 'Career path predicted',
                'data' => [
                    'predicted_roles' => $prediction['predicted_roles'],
                    'career_trajectory' => $prediction['career_trajectory'],
                    'succession_potential' => $prediction['succession_potential'],
                    'development_requirements' => $prediction['development_requirements'],
                    'estimated_timeline_months' => $prediction['estimated_timeline_months'],
                    'recommendation' => $prediction['recommendation'],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to predict career path', [
                'application_id' => $request->application_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to predict career path: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive predictive report
     * GET /api/scout/predictive/report/{application}
     */
    public function getComprehensiveReport(int $applicationId): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $application = Application::with(['job', 'user'])
                ->where('id', $applicationId)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $report = $this->predictiveAnalytics->generatePredictiveReport($application);

            return response()->json([
                'success' => true,
                'message' => 'Comprehensive report generated',
                'data' => [
                    'report' => $report['comprehensive_report'],
                    'visualizations' => $report['visualizations_data'],
                    'action_items' => $report['action_items'],
                    'recommendations' => $report['recommendations_priority'],
                    'generated_at' => now(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to generate comprehensive report', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger prediction update job for an application
     * POST /api/scout/predictive/update
     */
    public function triggerPredictionUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:applications,id',
            'force_refresh' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $companyId = auth()->user()->company_id;
            $application = Application::with(['user', 'job.company'])
                ->where('id', $request->input('application_id'))
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $forceRefresh = $request->boolean('force_refresh', false);

            // Dispatch the job
            \App\Jobs\UpdatePredictionsJob::dispatch($application, $forceRefresh);

            return response()->json([
                'success' => true,
                'message' => 'Prediction update job dispatched successfully',
                'data' => [
                    'application_id' => $application->id,
                    'force_refresh' => $forceRefresh,
                    'status' => 'queued',
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch prediction update job', [
                'application_id' => $request->input('application_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch update job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progress of prediction update job
     * GET /api/scout/predictive/progress/{application}
     */
    public function getPredictionProgress(int $applicationId): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $application = Application::where('id', $applicationId)
                ->whereHas('job', function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->firstOrFail();

            $progress = \App\Jobs\UpdatePredictionsJob::getProgress($applicationId);
            $lastUpdated = \App\Jobs\UpdatePredictionsJob::getLastUpdated($applicationId);

            return response()->json([
                'success' => true,
                'data' => [
                    'application_id' => $applicationId,
                    'progress' => $progress,
                    'last_updated' => $lastUpdated?->toIso8601String(),
                    'status' => $progress ? ($progress['percentage'] === 100 ? 'completed' : 'in_progress') : 'not_started',
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get prediction progress', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================================================
    // TALENT PIPELINE MANAGEMENT ENDPOINTS
    // ===================================================================

    /**
     * Create a new talent pipeline
     * POST /api/scout/pipeline/create
     */
    public function createPipeline(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pipeline_name' => 'required|string|max:255',
            'target_role' => 'required|string|max:255',
            'role_description' => 'nullable|string',
            'pipeline_type' => 'nullable|in:recurring_role,seasonal,project_based,general',
            'required_skills' => 'nullable|array',
            'preferred_experience' => 'nullable|array',
            'cultural_fit_criteria' => 'nullable|array',
            'target_pipeline_size' => 'nullable|integer|min:1|max:1000',
            'hiring_frequency_days' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = $request->user()->company;
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                ], 404);
            }

            $pipeline = $this->talentPipeline->createPipeline($company, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Talent pipeline created successfully',
                'data' => $pipeline->load('candidates'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create talent pipeline', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create pipeline: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add candidate to talent pipeline
     * POST /api/scout/pipeline/{pipeline}/add-candidate
     */
    public function addCandidateToPipeline(Request $request, int $pipelineId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'pipeline_stage' => 'nullable|in:sourced,engaged,qualified,pre_screened,warm,hot,cool,archived',
            'sourcing_notes' => 'nullable|string',
            'availability_status' => 'nullable|in:immediately_available,available_2_weeks,available_1_month,passive,not_available',
            'expected_salary_min' => 'nullable|numeric|min:0',
            'expected_salary_max' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pipeline = TalentPipeline::where('id', $pipelineId)
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $candidate = User::findOrFail($request->input('user_id'));

            $pipelineCandidate = $this->talentPipeline->addCandidateToPipeline(
                $pipeline,
                $candidate,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Candidate added to pipeline successfully',
                'data' => $pipelineCandidate->load(['user', 'talentPipeline']),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to add candidate to pipeline', [
                'pipeline_id' => $pipelineId,
                'user_id' => $request->input('user_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add candidate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pipeline details with candidates
     * GET /api/scout/pipeline/{pipeline}
     */
    public function getPipeline(Request $request, int $pipelineId): JsonResponse
    {
        try {
            $pipeline = TalentPipeline::where('id', $pipelineId)
                ->where('company_id', $request->user()->company_id)
                ->with(['candidates.user'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'pipeline' => $pipeline,
                    'health_metrics' => [
                        'health_status' => $pipeline->health_status,
                        'fill_rate' => $pipeline->fill_rate,
                        'is_understaffed' => $pipeline->is_understaffed,
                        'warm_candidates' => $pipeline->candidates()->warm()->count(),
                        'hot_candidates' => $pipeline->candidates()->where('pipeline_stage', 'hot')->count(),
                        'candidates_needing_follow_up' => $pipeline->candidates()->needsFollowUp()->count(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get pipeline', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pipeline not found',
            ], 404);
        }
    }

    /**
     * Get all pipelines for company
     * GET /api/scout/pipelines
     */
    public function getPipelines(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $pipelines = TalentPipeline::where('company_id', $company->id)
                ->with(['candidates' => function($query) {
                    $query->limit(5)->orderByDesc('match_score');
                }])
                ->get();

            $summary = $this->talentPipeline->getPipelineHealthSummary($company);

            return response()->json([
                'success' => true,
                'data' => [
                    'pipelines' => $pipelines,
                    'summary' => $summary,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get pipelines', [
                'company_id' => $request->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pipelines: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get silver medalists for company
     * GET /api/scout/silver-medalists
     */
    public function getSilverMedalists(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $silverMedalists = $this->talentPipeline->getSilverMedalistsForReEngagement($company);

            return response()->json([
                'success' => true,
                'data' => [
                    'silver_medalists' => $silverMedalists,
                    'total_count' => $silverMedalists->count(),
                    'ready_for_engagement' => $silverMedalists->filter(fn($sm) => $sm->is_ready_for_re_engagement)->count(),
                    'high_potential' => $silverMedalists->filter(fn($sm) => $sm->overall_score >= 80)->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get silver medalists', [
                'company_id' => $request->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve silver medalists: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create silver medalist from application
     * POST /api/scout/silver-medalist/create
     */
    public function createSilverMedalist(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:applications,id',
            'reason' => 'required|in:strong_second_choice,overqualified,timing_mismatch,budget_constraints,team_fit_preference,skill_mismatch_minor,cultural_potential',
            'interview_score' => 'nullable|numeric|min:0|max:100',
            'skill_score' => 'nullable|numeric|min:0|max:100',
            'cultural_fit_score' => 'nullable|numeric|min:0|max:100',
            'strengths' => 'nullable|array',
            'development_areas' => 'nullable|array',
            'interviewer_feedback' => 'nullable|string',
            'suitable_future_roles' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $application = Application::where('id', $request->input('application_id'))
                ->whereHas('job', function($query) use ($request) {
                    $query->where('company_id', $request->user()->company_id);
                })
                ->firstOrFail();

            $silverMedalist = $this->talentPipeline->createSilverMedalist(
                $application,
                $request->input('reason'),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Silver medalist created successfully',
                'data' => $silverMedalist->load(['user', 'job']),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create silver medalist', [
                'application_id' => $request->input('application_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create silver medalist: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert silver medalist to pipeline candidate
     * POST /api/scout/silver-medalist/{silverMedalist}/convert
     */
    public function convertSilverMedalistToPipeline(Request $request, int $silverMedalistId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pipeline_id' => 'required|integer|exists:talent_pipelines,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $silverMedalist = SilverMedalist::where('id', $silverMedalistId)
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $pipeline = TalentPipeline::where('id', $request->input('pipeline_id'))
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $pipelineCandidate = $silverMedalist->addToTalentPipeline($pipeline);

            return response()->json([
                'success' => true,
                'message' => 'Silver medalist converted to pipeline candidate',
                'data' => $pipelineCandidate->load(['user', 'talentPipeline']),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to convert silver medalist', [
                'silver_medalist_id' => $silverMedalistId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to convert: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Discover passive candidates for pipeline
     * POST /api/scout/passive-candidates/discover
     */
    public function discoverPassiveCandidates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pipeline_id' => 'required|integer|exists:talent_pipelines,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pipeline = TalentPipeline::where('id', $request->input('pipeline_id'))
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $limit = $request->input('limit', 20);
            
            $candidates = $this->passiveCandidateScout->discoverCandidatesForPipeline($pipeline, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'candidates' => $candidates,
                    'total_discovered' => $candidates->count(),
                    'high_matches' => $candidates->filter(fn($c) => $c['score'] >= 80)->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to discover passive candidates', [
                'pipeline_id' => $request->input('pipeline_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to discover candidates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get passive candidates ready for engagement
     * GET /api/scout/passive-candidates/ready
     */
    public function getPassiveCandidatesReady(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $candidates = $this->passiveCandidateScout->getCandidatesReadyForEngagement($company);
            $metrics = $this->passiveCandidateScout->getEngagementMetrics($company);

            return response()->json([
                'success' => true,
                'data' => [
                    'candidates' => $candidates,
                    'metrics' => $metrics,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get passive candidates', [
                'company_id' => $request->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve candidates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate engagement strategy for passive candidate
     * POST /api/scout/passive-candidate/{profile}/engagement-strategy
     */
    public function generateEngagementStrategy(Request $request, int $profileId): JsonResponse
    {
        try {
            $profile = PassiveCandidateProfile::where('id', $profileId)
                ->where('company_id', $request->user()->company_id)
                ->with('user')
                ->firstOrFail();

            $strategy = $this->passiveCandidateScout->generateEngagementStrategy($profile);

            return response()->json([
                'success' => true,
                'data' => $strategy,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to generate engagement strategy', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate strategy: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate engagement with passive candidate
     * POST /api/scout/passive-candidate/{profile}/engage
     */
    public function engagePassiveCandidate(Request $request, int $profileId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'method' => 'required|in:email,linkedin,phone,referral_introduction',
            'message_sent' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $profile = PassiveCandidateProfile::where('id', $profileId)
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $this->passiveCandidateScout->initiateEngagement($profile, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Engagement initiated successfully',
                'data' => $profile->fresh(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to engage passive candidate', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate engagement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get candidate experience journey
     * GET /api/scout/candidate-experience/{user}
     */
    public function getCandidateExperience(Request $request, int $userId): JsonResponse
    {
        try {
            $company = $request->user()->company;
            $candidate = User::findOrFail($userId);

            $journey = $this->candidateExperience->getCandidateJourney($candidate, $company);

            return response()->json([
                'success' => true,
                'data' => $journey,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get candidate experience', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve candidate experience: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request feedback from candidate
     * POST /api/scout/candidate-feedback/request
     */
    public function requestCandidateFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_id' => 'required|integer|exists:applications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $application = Application::where('id', $request->input('application_id'))
                ->whereHas('job', function($query) use ($request) {
                    $query->where('company_id', $request->user()->company_id);
                })
                ->firstOrFail();

            $feedback = $this->candidateExperience->requestFeedback($application);

            return response()->json([
                'success' => true,
                'message' => 'Feedback request sent successfully',
                'data' => $feedback,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to request candidate feedback', [
                'application_id' => $request->input('application_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to request feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get employer brand score
     * GET /api/scout/employer-brand-score
     */
    public function getEmployerBrandScore(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $metrics = $this->candidateExperience->getExperienceMetrics($company);

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get employer brand score', [
                'company_id' => $request->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve brand score: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate employer brand score for period
     * POST /api/scout/employer-brand-score/calculate
     */
    public function calculateEmployerBrandScore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = $request->user()->company;
            $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : null;

            $brandScore = $this->candidateExperience->calculateEmployerBrandScore($company, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'message' => 'Employer brand score calculated successfully',
                'data' => $brandScore,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to calculate employer brand score', [
                'company_id' => $request->user()->company_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate brand score: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Advance pipeline candidate to new stage
     * PUT /api/scout/pipeline-candidate/{candidate}/advance
     */
    public function advancePipelineCandidate(Request $request, int $candidateId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_stage' => 'required|in:sourced,engaged,qualified,pre_screened,warm,hot,cool,archived',
            'notes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pipelineCandidate = PipelineCandidate::where('id', $candidateId)
                ->whereHas('talentPipeline', function($query) use ($request) {
                    $query->where('company_id', $request->user()->company_id);
                })
                ->firstOrFail();

            $updated = $this->talentPipeline->advanceCandidateStage(
                $pipelineCandidate,
                $request->input('new_stage'),
                $request->input('notes', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Candidate stage advanced successfully',
                'data' => $updated,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to advance pipeline candidate', [
                'candidate_id' => $candidateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to advance candidate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Match pipeline candidates to job opening
     * POST /api/scout/pipeline/match-to-job
     */
    public function matchPipelineCandidatesToJob(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|integer|exists:jobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $job = Job::where('id', $request->input('job_id'))
                ->where('company_id', $request->user()->company_id)
                ->firstOrFail();

            $matches = $this->talentPipeline->matchPipelineCandidatesToJob($job);

            return response()->json([
                'success' => true,
                'data' => [
                    'matches' => $matches,
                    'total_matches' => $matches->count(),
                    'immediate_contact' => $matches->filter(fn($m) => $m['recommended_action'] === 'immediate_contact')->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to match candidates to job', [
                'job_id' => $request->input('job_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to match candidates: ' . $e->getMessage(),
            ], 500);
        }
    }
}




