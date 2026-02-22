<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobMatchingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\API\ApplicationController as APIApplicationController;
use App\Http\Controllers\API\CompanyController as APICompanyController;
use App\Http\Controllers\API\JobController as APIJobController;
use App\Http\Controllers\API\AgentController;
use App\Http\Controllers\API\InterviewSessionController;
use App\Http\Controllers\API\MarketIntelligenceController;
use App\Http\Controllers\API\NegotiationController;
use App\Http\Controllers\API\SkillAnalyzerController;
use App\Http\Controllers\Api\AIFormAssistantController;
use App\Http\Middleware\ApiAbilityCheck;
use App\Http\Middleware\ApiRateLimiting;
use App\Http\Middleware\ApiTokenAuthentication;

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\GDPRController;

Route::get('/user', [UserController::class, 'me'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Skill Gap Analyzer API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('skills')->middleware('auth:sanctum')->group(function () {
    // Gap Analysis
    Route::post('/analyze', [SkillAnalyzerController::class, 'analyzeSkillGaps'])->name('api.skills.analyze');
    Route::get('/gaps', [SkillAnalyzerController::class, 'listSkillGaps'])->name('api.skills.gaps');
    
    // Learning Paths
    Route::post('/learning-path/{gapId}', [SkillAnalyzerController::class, 'generateLearningPath'])->name('api.skills.learning-path.generate');
    Route::get('/learning-path/{id}', [SkillAnalyzerController::class, 'getLearningPath'])->name('api.skills.learning-path.show');
    Route::patch('/progress', [SkillAnalyzerController::class, 'updateProgress'])->name('api.skills.progress.update');
    
    // Daily Recommendations
    Route::get('/daily-recommendations', [SkillAnalyzerController::class, 'getDailyRecommendations'])->name('api.skills.daily-recommendations');
    
    // Skill Validation
    Route::post('/validate', [SkillAnalyzerController::class, 'validateSkills'])->name('api.skills.validate');
    
    // Assessments
    Route::post('/assessment/{skillId}', [SkillAnalyzerController::class, 'generateAssessment'])->name('api.skills.assessment.generate');
    Route::post('/assessment/{id}/submit', [SkillAnalyzerController::class, 'submitAssessment'])->name('api.skills.assessment.submit');
    Route::get('/assessment/{id}/results', [SkillAnalyzerController::class, 'getAssessmentResults'])->name('api.skills.assessment.results');
    
    // Industry Trends
    Route::get('/trends', [SkillAnalyzerController::class, 'getIndustryTrends'])->name('api.skills.trends');
});

// Public certificate verification (no auth required)
Route::get('/skills/certificate/{hash}', [SkillAnalyzerController::class, 'getCertificate'])->name('api.skills.certificate');

/*
|--------------------------------------------------------------------------
| Public API Routes (v1) - Third-Party Integrations
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware([
    ApiTokenAuthentication::class,
    ApiRateLimiting::class,
])->group(function () {
    
    // Company endpoints
    Route::prefix('company')->middleware(ApiAbilityCheck::class . ':company.read')->group(function () {
        Route::get('/', [APICompanyController::class, 'show']);
        Route::get('/statistics', [APICompanyController::class, 'statistics']);
    });
    
    Route::put('company', [APICompanyController::class, 'update'])
        ->middleware(ApiAbilityCheck::class . ':company.write');
    
    // Job endpoints
    Route::prefix('jobs')->middleware(ApiAbilityCheck::class . ':jobs.read')->group(function () {
        Route::get('/', [APIJobController::class, 'index']);
        Route::get('/{job}', [APIJobController::class, 'show']);
        Route::get('/{job}/statistics', [APIJobController::class, 'statistics']);
    });
    
    Route::middleware(ApiAbilityCheck::class . ':jobs.write')->group(function () {
        Route::post('jobs', [APIJobController::class, 'store']);
        Route::put('jobs/{job}', [APIJobController::class, 'update']);
        Route::delete('jobs/{job}', [APIJobController::class, 'destroy']);
    });
    
    // Application endpoints
    Route::prefix('applications')->middleware(ApiAbilityCheck::class . ':applications.read')->group(function () {
        Route::get('/', [APIApplicationController::class, 'index']);
        Route::get('/{application}', [APIApplicationController::class, 'show']);
    });
    
    Route::middleware(ApiAbilityCheck::class . ':applications.write')->group(function () {
        Route::put('applications/{application}/status', [APIApplicationController::class, 'updateStatus']);
        Route::post('applications/bulk-status', [APIApplicationController::class, 'bulkUpdateStatus']);
    });
});

// API health check (no authentication required)
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Job Matching & Search Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Job Recommendations & Search
    Route::get('/jobs/recommended', [JobMatchingController::class, 'recommended']);
    Route::get('/jobs/search', [JobMatchingController::class, 'search']);
    Route::get('/jobs/{job}/match-analysis', [JobMatchingController::class, 'matchAnalysis']);
    
    // Saved Jobs
    Route::get('/jobs/saved', [JobMatchingController::class, 'saved']);
    Route::post('/jobs/{job}/save', [JobMatchingController::class, 'save']);
    Route::delete('/jobs/{job}/unsave', [JobMatchingController::class, 'unsave']);
    
    // One-Click Apply
    Route::post('/jobs/{job}/apply', [JobMatchingController::class, 'apply']);
    
    // Payment & Subscription Routes (with idempotency for POST/PUT)
    Route::prefix('payment')->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiate'])->middleware('idempotent');
        Route::post('/razorpay/callback', [PaymentController::class, 'razorpayCallback']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::get('/transaction/{transaction}', [PaymentController::class, 'transaction']);
        Route::post('/refund/{transaction}', [PaymentController::class, 'requestRefund'])->middleware('idempotent');
    });
    
    // Profile Management (from Phase 2.2)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/', [ProfileController::class, 'store']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::delete('/', [ProfileController::class, 'destroy']);
        
        // Profile sections
        Route::post('/experience', [ProfileController::class, 'addExperience']);
        Route::put('/experience/{index}', [ProfileController::class, 'updateExperience']);
        Route::delete('/experience/{index}', [ProfileController::class, 'removeExperience']);
        
        Route::post('/education', [ProfileController::class, 'addEducation']);
        Route::put('/education/{index}', [ProfileController::class, 'updateEducation']);
        Route::delete('/education/{index}', [ProfileController::class, 'removeEducation']);
        
        Route::post('/skills', [ProfileController::class, 'addSkills']);
        Route::put('/skills/{index}', [ProfileController::class, 'updateSkill']);
        Route::delete('/skills/{index}', [ProfileController::class, 'removeSkill']);
        
        Route::get('/completeness', [ProfileController::class, 'checkCompleteness']);
    });
    
    // AI Interview Intelligence System Routes
    Route::prefix('interview')->group(function () {
        // Session management
        Route::post('/sessions', [InterviewSessionController::class, 'start']);
        Route::get('/sessions/{sessionId}', [InterviewSessionController::class, 'show']);
        Route::post('/sessions/{sessionId}/abandon', [InterviewSessionController::class, 'abandon']);
        Route::get('/sessions/user/history', [InterviewSessionController::class, 'history']);
        
        // Question flow
        Route::get('/sessions/{sessionId}/next-question', [InterviewSessionController::class, 'getNextQuestion']);
        Route::post('/sessions/{sessionId}/answer', [InterviewSessionController::class, 'submitAnswer']);
        
        // Feedback & Reports
        Route::get('/sessions/{sessionId}/questions/{questionId}/feedback', [InterviewSessionController::class, 'getFeedback']);
        Route::get('/sessions/{sessionId}/report', [InterviewSessionController::class, 'getReport']);
    });
    
    // Autonomous Agent Routes
    Route::prefix('agent')->group(function () {
        // Configuration
        Route::get('/config', [AgentController::class, 'getConfig']);
        Route::post('/configure', [AgentController::class, 'configure']);

        // Control
        Route::post('/activate', [AgentController::class, 'activate']);
        Route::post('/pause', [AgentController::class, 'pause']);
        Route::post('/resume', [AgentController::class, 'resume']);
        Route::post('/deactivate', [AgentController::class, 'deactivate']);

        // Monitoring
        Route::get('/status', [AgentController::class, 'status']);
        Route::get('/applications', [AgentController::class, 'applications']);
        Route::get('/metrics', [AgentController::class, 'metrics']);
        Route::get('/learning', [AgentController::class, 'learning']);

        // Management
        Route::post('/blacklist', [AgentController::class, 'blacklistCompany']);
        Route::delete('/unblacklist', [AgentController::class, 'unblacklistCompany']);
        Route::post('/discover', [AgentController::class, 'discover']); // Manual discovery for testing

        // Human-in-the-Loop Approval System (P0-7)
        Route::prefix('approvals')->group(function () {
            Route::get('/', [AgentController::class, 'getPendingApprovals'])
                ->middleware('throttle:60,1')
                ->name('api.agent.approvals.index');
            Route::get('/{matchId}', [AgentController::class, 'getApprovalDetails'])
                ->middleware('throttle:60,1')
                ->name('api.agent.approvals.show');
            Route::post('/{matchId}/approve', [AgentController::class, 'approveApplication'])
                ->middleware('throttle:30,1')
                ->name('api.agent.approvals.approve');
            Route::post('/{matchId}/reject', [AgentController::class, 'rejectApplication'])
                ->middleware('throttle:30,1')
                ->name('api.agent.approvals.reject');
            Route::post('/bulk-approve', [AgentController::class, 'bulkApprove'])
                ->middleware('throttle:10,1')
                ->name('api.agent.approvals.bulk-approve');
            Route::post('/bulk-reject', [AgentController::class, 'bulkReject'])
                ->middleware('throttle:10,1')
                ->name('api.agent.approvals.bulk-reject');
        });
    });
});

// Market Intelligence Routes (auth:sanctum required)
Route::middleware('auth:sanctum')->prefix('market')->group(function () {
    Route::get('/overview', [MarketIntelligenceController::class, 'overview']);
    Route::get('/user-position', [MarketIntelligenceController::class, 'userPosition']);
    Route::get('/salary-insights', [MarketIntelligenceController::class, 'salaryInsights']);
    Route::post('/negotiation-insights', [MarketIntelligenceController::class, 'negotiationInsights']);
    Route::get('/skill-trends', [MarketIntelligenceController::class, 'skillTrends']);
    Route::get('/skill-combinations', [MarketIntelligenceController::class, 'skillCombinations']);
    Route::get('/upskilling-roadmap', [MarketIntelligenceController::class, 'upskillingRoadmap']);
    Route::get('/role-predictions', [MarketIntelligenceController::class, 'rolePredictions']);
    Route::get('/competitive-analysis', [MarketIntelligenceController::class, 'competitiveAnalysis']);
    Route::get('/recommendations', [MarketIntelligenceController::class, 'recommendations']);
});

/*
|--------------------------------------------------------------------------
| AI Form Assistant Routes (auth:sanctum required)
|--------------------------------------------------------------------------
| Provides AI-powered text generation and enhancement for form fields
*/

Route::middleware('auth:sanctum')->prefix('ai')->group(function () {
    // Text Generation & Enhancement
    Route::post('/generate-text', [AIFormAssistantController::class, 'generateText'])
        ->middleware('throttle:10,1')
        ->name('api.ai.generate-text');
    
    Route::post('/enhance-text', [AIFormAssistantController::class, 'enhanceText'])
        ->middleware('throttle:15,1')
        ->name('api.ai.enhance-text');
    
    Route::post('/suggestions', [AIFormAssistantController::class, 'getSuggestions'])
        ->middleware('throttle:20,1')
        ->name('api.ai.suggestions');
    
    Route::post('/autocomplete', [AIFormAssistantController::class, 'autocomplete'])
        ->middleware('throttle:30,1')
        ->name('api.ai.autocomplete');
    
    // Experience & Skills Endpoints
    Route::post('/generate-experience-description', [AIFormAssistantController::class, 'generateExperienceDescription'])
        ->middleware('throttle:10,1')
        ->name('api.ai.experience-description');
    
    Route::post('/suggest-achievements', [AIFormAssistantController::class, 'suggestAchievements'])
        ->middleware('throttle:10,1')
        ->name('api.ai.suggest-achievements');
    
    Route::post('/suggest-skills', [AIFormAssistantController::class, 'suggestSkills'])
        ->middleware('throttle:10,1')
        ->name('api.ai.suggest-skills');
});

// AI Negotiation Strategist Routes (auth:sanctum required)
Route::middleware('auth:sanctum')->prefix('negotiation')->group(function () {
    // Strategy Management
    Route::post('/strategy', [NegotiationController::class, 'generateStrategy']);
    Route::get('/strategy/{id}', [NegotiationController::class, 'getStrategy']);
    
    // Scenarios & Scripts
    Route::get('/scenarios/{strategyId}', [NegotiationController::class, 'getScenarios']);
    Route::get('/scripts/{strategyId}', [NegotiationController::class, 'getScripts']);
    Route::post('/scenarios/{scenarioId}/scripts', [NegotiationController::class, 'generateScriptsForScenario']);
    
    // Real-Time Coaching
    Route::post('/session', [NegotiationController::class, 'startSession']);
    Route::post('/session/{sessionId}/message', [NegotiationController::class, 'addMessage']);
    Route::put('/session/{sessionId}/stage', [NegotiationController::class, 'updateSessionStage']);
    Route::put('/session/{sessionId}/outcome', [NegotiationController::class, 'recordOutcome']);
    
    // Tactics Library
    Route::get('/tactics', [NegotiationController::class, 'getTactics']);
});

/*
|--------------------------------------------------------------------------
| GDPR Compliance API Routes
|--------------------------------------------------------------------------
| Endpoints for data export, deletion, anonymization, and consent management.
| All routes require authentication and are rate-limited for security.
*/

Route::middleware('auth:sanctum')->prefix('gdpr')->group(function () {
    // Data Export (Right to Access & Portability)
    Route::post('/export', [GDPRController::class, 'export'])
        ->middleware('throttle:5,60') // Max 5 exports per hour
        ->name('api.gdpr.export');

    Route::get('/export/preview', [GDPRController::class, 'previewExport'])
        ->middleware('throttle:30,1') // Max 30 previews per minute
        ->name('api.gdpr.export.preview');

    // Data Deletion (Right to Erasure)
    Route::post('/delete', [GDPRController::class, 'requestDeletion'])
        ->middleware('throttle:3,60') // Max 3 deletion requests per hour
        ->name('api.gdpr.delete');

    Route::post('/delete/cancel', [GDPRController::class, 'cancelDeletion'])
        ->middleware('throttle:10,60') // Max 10 cancellations per hour
        ->name('api.gdpr.delete.cancel');

    // Data Anonymization (Right to Restrict Processing)
    Route::post('/anonymize', [GDPRController::class, 'anonymize'])
        ->middleware('throttle:3,60') // Max 3 anonymization requests per hour
        ->name('api.gdpr.anonymize');

    // Consent Management (Right to Object)
    Route::get('/consent', [GDPRController::class, 'getConsent'])
        ->middleware('throttle:60,1') // Max 60 reads per minute
        ->name('api.gdpr.consent.show');

    Route::put('/consent', [GDPRController::class, 'updateConsent'])
        ->middleware('throttle:20,1') // Max 20 updates per minute
        ->name('api.gdpr.consent.update');

    // GDPR Information
    Route::get('/rights', [GDPRController::class, 'rights'])
        ->middleware('throttle:60,1') // Max 60 reads per minute
        ->name('api.gdpr.rights');

    // Processing Restriction
    Route::post('/restrict', [GDPRController::class, 'restrictProcessing'])
        ->middleware('throttle:10,60') // Max 10 restrictions per hour
        ->name('api.gdpr.restrict');
});

/*
|--------------------------------------------------------------------------
| S.C.O.U.T. - AI Hiring System API Routes (Employer-Only)
|--------------------------------------------------------------------------
*/

Route::prefix('scout')->middleware(['auth:sanctum', 'employer'])->group(function () {
    // DNA Analysis
    Route::post('/analyze-dna', [App\Http\Controllers\ScoutController::class, 'analyzeDNA'])
        ->middleware('throttle:10,1') // Max 10 DNA analyses per minute (expensive operation)
        ->name('api.scout.analyze-dna');
    
    Route::get('/dna-profile', [App\Http\Controllers\ScoutController::class, 'getDNAProfile'])
        ->name('api.scout.dna-profile');
    
    // Hiring Pattern Analysis
    Route::post('/analyze-hiring-patterns', [App\Http\Controllers\ScoutController::class, 'analyzeHiringPatterns'])
        ->middleware('throttle:20,1')
        ->name('api.scout.analyze-hiring-patterns');
    
    // Candidate Success Prediction
    Route::post('/predict-candidate-success', [App\Http\Controllers\ScoutController::class, 'predictCandidateSuccess'])
        ->middleware('throttle:60,1')
        ->name('api.scout.predict-candidate-success');
    
    Route::get('/candidate-match/{candidateId}', [App\Http\Controllers\ScoutController::class, 'getCandidateMatch'])
        ->name('api.scout.candidate-match');
    
    // Team Compatibility
    Route::post('/team-compatibility', [App\Http\Controllers\ScoutController::class, 'assessTeamCompatibility'])
        ->middleware('throttle:60,1')
        ->name('api.scout.team-compatibility');
    
    // Culture Fit & Job Requirements
    Route::get('/culture-fit-criteria', [App\Http\Controllers\ScoutController::class, 'getCultureFitCriteria'])
        ->name('api.scout.culture-fit-criteria');
    
    // Hiring Insights Dashboard
    Route::get('/hiring-insights', [App\Http\Controllers\ScoutController::class, 'getHiringInsights'])
        ->name('api.scout.hiring-insights');
    
    // Intelligent Resume Analysis
    Route::post('/analyze-resume', [App\Http\Controllers\ScoutController::class, 'analyzeResume'])
        ->middleware('throttle:30,1') // Max 30 resume analyses per minute
        ->name('api.scout.analyze-resume');
    
    // Multi-Stage Automated Shortlisting
    Route::post('/shortlist', [App\Http\Controllers\ScoutController::class, 'executeShortlisting'])
        ->middleware('throttle:20,1') // Max 20 shortlisting runs per minute
        ->name('api.scout.shortlist');
    
    // Dynamic Adaptive Assessment System
    Route::post('/assessment/generate', [App\Http\Controllers\ScoutController::class, 'generateAssessment'])
        ->middleware('throttle:20,1') // Max 20 assessment generations per minute
        ->name('api.scout.assessment.generate');
    
    Route::post('/assessment/generate-async', [App\Http\Controllers\ScoutController::class, 'generateAssessmentAsync'])
        ->middleware('throttle:20,1') // Max 20 async assessment generations per minute
        ->name('api.scout.assessment.generate-async');
    
    Route::get('/assessment/progress/{applicationId}/{jobId}', [App\Http\Controllers\ScoutController::class, 'checkAssessmentProgress'])
        ->middleware('throttle:60,1') // Max 60 progress checks per minute (polling)
        ->name('api.scout.assessment.progress');
    
    Route::post('/assessment/{assessmentId}/submit', [App\Http\Controllers\ScoutController::class, 'submitAssessmentAnswer'])
        ->middleware('throttle:60,1') // Max 60 answer submissions per minute (higher for taking tests)
        ->name('api.scout.assessment.submit');
    
    Route::get('/assessment/{assessmentId}/results', [App\Http\Controllers\ScoutController::class, 'getAssessmentResults'])
        ->middleware('throttle:30,1') // Max 30 results retrievals per minute
        ->name('api.scout.assessment.results');
    
    // Behavioral and Situational Intelligence
    Route::post('/behavioral/generate', [App\Http\Controllers\ScoutController::class, 'generateBehavioralAssessment'])
        ->middleware('throttle:20,1') // Max 20 behavioral assessment generations per minute
        ->name('api.scout.behavioral.generate');
    
    Route::post('/behavioral/{assessmentId}/submit', [App\Http\Controllers\ScoutController::class, 'submitScenarioResponse'])
        ->middleware('throttle:60,1') // Max 60 scenario responses per minute
        ->name('api.scout.behavioral.submit');
    
    Route::get('/behavioral/{assessmentId}/results', [App\Http\Controllers\ScoutController::class, 'getBehavioralAssessmentResults'])
        ->middleware('throttle:30,1') // Max 30 results retrievals per minute
        ->name('api.scout.behavioral.results');
    
    // Continuous Learning & Optimization Routes
    Route::post('/learning/performance/track', [App\Http\Controllers\ScoutController::class, 'trackHirePerformance'])
        ->middleware('throttle:50,1') // Max 50 performance tracking updates per minute
        ->name('api.scout.learning.performance.track');
    
    Route::post('/learning/decisions/override', [App\Http\Controllers\ScoutController::class, 'recordHiringOverride'])
        ->middleware('throttle:30,1') // Max 30 decision overrides per minute
        ->name('api.scout.learning.decisions.override');
    
    Route::get('/learning/insights', [App\Http\Controllers\ScoutController::class, 'getLearningInsights'])
        ->middleware('throttle:60,1') // Max 60 insights retrievals per minute
        ->name('api.scout.learning.insights');
    
    Route::get('/learning/predictions', [App\Http\Controllers\ScoutController::class, 'getTalentPredictions'])
        ->middleware('throttle:40,1') // Max 40 prediction retrievals per minute
        ->name('api.scout.learning.predictions');
    
    Route::post('/learning/refine', [App\Http\Controllers\ScoutController::class, 'triggerModelRefinement'])
        ->middleware('throttle:10,1') // Max 10 refinement triggers per minute (expensive operation)
        ->name('api.scout.learning.refine');

    // Predictive Analytics Routes (New)
    Route::post('/predictive/success', [App\Http\Controllers\ScoutController::class, 'predictSuccessProbability'])
        ->middleware('throttle:30,1') // Max 30 predictions per minute
        ->name('api.scout.predictive.success');
    
    Route::post('/predictive/tenure', [App\Http\Controllers\ScoutController::class, 'forecastTenure'])
        ->middleware('throttle:30,1') // Max 30 forecasts per minute
        ->name('api.scout.predictive.tenure');
    
    Route::post('/predictive/productivity', [App\Http\Controllers\ScoutController::class, 'estimateProductivity'])
        ->middleware('throttle:30,1') // Max 30 estimates per minute
        ->name('api.scout.predictive.productivity');
    
    Route::post('/predictive/flight-risk', [App\Http\Controllers\ScoutController::class, 'assessFlightRisk'])
        ->middleware('throttle:20,1') // Max 20 assessments per minute
        ->name('api.scout.predictive.flight_risk');
    
    Route::post('/predictive/development', [App\Http\Controllers\ScoutController::class, 'generateDevelopmentPlan'])
        ->middleware('throttle:20,1') // Max 20 plans per minute
        ->name('api.scout.predictive.development');
    
    Route::post('/predictive/onboarding', [App\Http\Controllers\ScoutController::class, 'createOnboardingPlan'])
        ->middleware('throttle:20,1') // Max 20 onboarding plans per minute
        ->name('api.scout.predictive.onboarding');
    
    Route::post('/predictive/career-path', [App\Http\Controllers\ScoutController::class, 'predictCareerPath'])
        ->middleware('throttle:20,1') // Max 20 career path predictions per minute
        ->name('api.scout.predictive.career_path');
    
    Route::get('/predictive/report/{application}', [App\Http\Controllers\ScoutController::class, 'getComprehensiveReport'])
        ->middleware('throttle:10,1') // Max 10 comprehensive reports per minute (expensive operation)
        ->name('api.scout.predictive.report');
    
    Route::post('/predictive/update', [App\Http\Controllers\ScoutController::class, 'triggerPredictionUpdate'])
        ->middleware('throttle:10,1') // Max 10 update triggers per minute
        ->name('api.scout.predictive.update');
    
    Route::get('/predictive/progress/{application}', [App\Http\Controllers\ScoutController::class, 'getPredictionProgress'])
        ->middleware('throttle:60,1') // Max 60 progress checks per minute
        ->name('api.scout.predictive.progress');

    // Bias Elimination & Ethical AI Routes
    Route::post('/bias/anonymize', [App\Http\Controllers\ScoutController::class, 'anonymizeCandidate'])
        ->middleware('throttle:100,1') // Max 100 anonymizations per minute
        ->name('api.scout.bias.anonymize');
    
    Route::post('/bias/audit', [App\Http\Controllers\ScoutController::class, 'conductBiasAudit'])
        ->middleware('throttle:10,1') // Max 10 audits per minute (expensive operation)
        ->name('api.scout.bias.audit');
    
    Route::get('/bias/explanation/{application}', [App\Http\Controllers\ScoutController::class, 'getDecisionExplanation'])
        ->middleware('throttle:60,1') // Max 60 explanation retrievals per minute
        ->name('api.scout.bias.explanation');
    
    Route::get('/bias/diversity', [App\Http\Controllers\ScoutController::class, 'getDiversityAnalytics'])
        ->middleware('throttle:30,1') // Max 30 diversity analytics per minute
        ->name('api.scout.bias.diversity');
    
    Route::get('/bias/alerts', [App\Http\Controllers\ScoutController::class, 'getProxyDiscriminationAlerts'])
        ->middleware('throttle:60,1') // Max 60 alert retrievals per minute
        ->name('api.scout.bias.alerts');
    
    Route::post('/bias/alerts/{alert}/resolve', [App\Http\Controllers\ScoutController::class, 'resolveProxyAlert'])
        ->middleware('throttle:30,1') // Max 30 alert resolutions per minute
        ->name('api.scout.bias.alerts.resolve');
    
    Route::get('/bias/metrics', [App\Http\Controllers\ScoutController::class, 'getFairnessMetrics'])
        ->middleware('throttle:60,1') // Max 60 metrics retrievals per minute
        ->name('api.scout.bias.metrics');

    // Predictive Performance Analytics Routes
    Route::post('/predictive/success', [App\Http\Controllers\ScoutController::class, 'predictSuccessProbability'])
        ->middleware('throttle:30,1') // Max 30 success predictions per minute
        ->name('api.scout.predictive.success');
    
    Route::post('/predictive/tenure', [App\Http\Controllers\ScoutController::class, 'forecastTenure'])
        ->middleware('throttle:30,1') // Max 30 tenure forecasts per minute
        ->name('api.scout.predictive.tenure');
    
    Route::post('/predictive/productivity', [App\Http\Controllers\ScoutController::class, 'estimateProductivity'])
        ->middleware('throttle:30,1') // Max 30 productivity estimates per minute
        ->name('api.scout.predictive.productivity');
    
    Route::post('/predictive/flight-risk', [App\Http\Controllers\ScoutController::class, 'assessFlightRisk'])
        ->middleware('throttle:20,1') // Max 20 flight risk assessments per minute
        ->name('api.scout.predictive.flight-risk');
    
    Route::post('/predictive/development', [App\Http\Controllers\ScoutController::class, 'generateDevelopmentPlan'])
        ->middleware('throttle:20,1') // Max 20 development plans per minute
        ->name('api.scout.predictive.development');
    
    Route::post('/predictive/onboarding', [App\Http\Controllers\ScoutController::class, 'createOnboardingPlan'])
        ->middleware('throttle:20,1') // Max 20 onboarding plans per minute
        ->name('api.scout.predictive.onboarding');
    
    Route::post('/predictive/career-path', [App\Http\Controllers\ScoutController::class, 'predictCareerPath'])
        ->middleware('throttle:20,1') // Max 20 career path predictions per minute
        ->name('api.scout.predictive.career-path');
    
    Route::get('/predictive/report/{application}', [App\Http\Controllers\ScoutController::class, 'getComprehensiveReport'])
        ->middleware('throttle:10,1') // Max 10 comprehensive reports per minute (expensive operation)
        ->name('api.scout.predictive.report');

    // Talent Pipeline Management Routes
    Route::post('/pipeline/create', [App\Http\Controllers\ScoutController::class, 'createPipeline'])
        ->middleware('throttle:20,1') // Max 20 pipeline creations per minute
        ->name('api.scout.pipeline.create');
    
    Route::post('/pipeline/{pipeline}/add-candidate', [App\Http\Controllers\ScoutController::class, 'addCandidateToPipeline'])
        ->middleware('throttle:30,1') // Max 30 candidate additions per minute
        ->name('api.scout.pipeline.add-candidate');
    
    Route::get('/pipeline/{pipeline}', [App\Http\Controllers\ScoutController::class, 'getPipeline'])
        ->middleware('throttle:60,1') // Max 60 pipeline retrievals per minute
        ->name('api.scout.pipeline.show');
    
    Route::get('/pipelines', [App\Http\Controllers\ScoutController::class, 'getPipelines'])
        ->middleware('throttle:60,1') // Max 60 pipeline list retrievals per minute
        ->name('api.scout.pipelines.index');
    
    Route::get('/silver-medalists', [App\Http\Controllers\ScoutController::class, 'getSilverMedalists'])
        ->middleware('throttle:30,1') // Max 30 silver medalist retrievals per minute
        ->name('api.scout.silver-medalists.index');
    
    Route::post('/silver-medalist/create', [App\Http\Controllers\ScoutController::class, 'createSilverMedalist'])
        ->middleware('throttle:20,1') // Max 20 silver medalist creations per minute
        ->name('api.scout.silver-medalist.create');
    
    Route::post('/silver-medalist/{silverMedalist}/convert', [App\Http\Controllers\ScoutController::class, 'convertSilverMedalistToPipeline'])
        ->middleware('throttle:20,1') // Max 20 conversions per minute
        ->name('api.scout.silver-medalist.convert');
    
    Route::post('/passive-candidates/discover', [App\Http\Controllers\ScoutController::class, 'discoverPassiveCandidates'])
        ->middleware('throttle:10,1') // Max 10 discovery operations per minute (AI-intensive)
        ->name('api.scout.passive-candidates.discover');
    
    Route::get('/passive-candidates/ready', [App\Http\Controllers\ScoutController::class, 'getPassiveCandidatesReady'])
        ->middleware('throttle:30,1') // Max 30 retrievals per minute
        ->name('api.scout.passive-candidates.ready');
    
    Route::post('/passive-candidate/{profile}/engagement-strategy', [App\Http\Controllers\ScoutController::class, 'generateEngagementStrategy'])
        ->middleware('throttle:10,1') // Max 10 AI strategy generations per minute
        ->name('api.scout.passive-candidate.engagement-strategy');
    
    Route::post('/passive-candidate/{profile}/engage', [App\Http\Controllers\ScoutController::class, 'engagePassiveCandidate'])
        ->middleware('throttle:20,1') // Max 20 engagement initiations per minute
        ->name('api.scout.passive-candidate.engage');
    
    Route::get('/candidate-experience/{user}', [App\Http\Controllers\ScoutController::class, 'getCandidateExperience'])
        ->middleware('throttle:30,1') // Max 30 experience retrievals per minute
        ->name('api.scout.candidate-experience.show');
    
    Route::post('/candidate-feedback/request', [App\Http\Controllers\ScoutController::class, 'requestCandidateFeedback'])
        ->middleware('throttle:20,1') // Max 20 feedback requests per minute
        ->name('api.scout.candidate-feedback.request');
    
    Route::get('/employer-brand-score', [App\Http\Controllers\ScoutController::class, 'getEmployerBrandScore'])
        ->middleware('throttle:30,1') // Max 30 brand score retrievals per minute
        ->name('api.scout.employer-brand-score.show');
    
    Route::post('/employer-brand-score/calculate', [App\Http\Controllers\ScoutController::class, 'calculateEmployerBrandScore'])
        ->middleware('throttle:10,1') // Max 10 calculations per minute (expensive operation)
        ->name('api.scout.employer-brand-score.calculate');
    
    Route::put('/pipeline-candidate/{candidate}/advance', [App\Http\Controllers\ScoutController::class, 'advancePipelineCandidate'])
        ->middleware('throttle:30,1') // Max 30 stage advancements per minute
        ->name('api.scout.pipeline-candidate.advance');
    
    Route::post('/pipeline/match-to-job', [App\Http\Controllers\ScoutController::class, 'matchPipelineCandidatesToJob'])
        ->middleware('throttle:20,1') // Max 20 matching operations per minute
        ->name('api.scout.pipeline.match-to-job');
});

// Webhook Routes (no auth required, verified by signature)
Route::post('/webhooks/razorpay', [PaymentController::class, 'razorpayWebhook']);
Route::post('/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);
Route::post('/webhooks/payu', [\App\Http\Controllers\Webhooks\PayUWebhookController::class, 'handleNotification']);


