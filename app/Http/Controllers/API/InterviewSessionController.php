<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\InterviewQuestion;
use App\Services\Interview\InterviewGenerationService;
use App\Services\Interview\AnswerAnalysisService;
use App\Services\Interview\PerformanceReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InterviewSessionController extends Controller
{
    public function __construct(
        protected InterviewGenerationService $generationService,
        protected AnswerAnalysisService $analysisService,
        protected PerformanceReportingService $reportingService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Start a new interview session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'role_title' => 'required|string|max:255',
            'interview_type' => 'required|in:technical,behavioral,mixed',
            'discovered_job_id' => 'nullable|exists:discovered_jobs,id',
            'question_count' => 'nullable|integer|min:3|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check subscription limits
        $user = Auth::user();
        
        if (!$this->checkAICredits($user)) {
            return response()->json([
                'message' => 'Insufficient AI credits. Please upgrade your subscription.',
                'remaining_credits' => $user->getRemainingAICredits(),
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Generate interview session
            $session = $this->generationService->generateInterviewSession(
                userId: $user->id,
                companyName: $request->company_name,
                roleTitle: $request->role_title,
                interviewType: $request->interview_type,
                discoveredJobId: $request->discovered_job_id,
                questionCount: $request->question_count ?? 10
            );

            // Start the session
            $session->start();

            // Deduct AI credits
            $this->deductAICredits($user, 50); // 50 credits per session

            DB::commit();

            return response()->json([
                'message' => 'Interview session started successfully',
                'session' => $this->formatSessionResponse($session),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to start interview session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get interview session details
     * 
     * @param int $sessionId
     * @return JsonResponse
     */
    public function show(int $sessionId): JsonResponse
    {
        $session = InterviewSession::with([
            'questions' => fn($q) => $q->orderBy('order'),
            'questions.response.feedback',
            'performanceReport',
            'coachingTips',
        ])->find($sessionId);

        if (!$session) {
            return response()->json([
                'message' => 'Interview session not found',
            ], 404);
        }

        // Authorization check
        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'session' => $this->formatSessionResponse($session),
        ]);
    }

    /**
     * Get next question in session
     * 
     * @param int $sessionId
     * @return JsonResponse
     */
    public function getNextQuestion(int $sessionId): JsonResponse
    {
        $session = InterviewSession::find($sessionId);

        if (!$session) {
            return response()->json([
                'message' => 'Interview session not found',
            ], 404);
        }

        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (!$session->canContinue()) {
            return response()->json([
                'message' => 'Interview session is completed or abandoned',
                'status' => $session->status,
            ], 400);
        }

        $nextQuestion = $session->getNextQuestion();

        if (!$nextQuestion) {
            return response()->json([
                'message' => 'No more questions available',
                'progress' => $session->getProgressPercentage(),
            ], 200);
        }

        return response()->json([
            'question' => $this->formatQuestionResponse($nextQuestion),
            'progress' => $session->getProgressPercentage(),
            'questions_remaining' => $session->total_questions - $session->questions_answered,
        ]);
    }

    /**
     * Submit answer to a question
     * 
     * @param Request $request
     * @param int $sessionId
     * @return JsonResponse
     */
    public function submitAnswer(Request $request, int $sessionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:interview_questions,id',
            'answer_text' => 'required|string|min:10',
            'response_time_seconds' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = InterviewSession::find($sessionId);

        if (!$session) {
            return response()->json([
                'message' => 'Interview session not found',
            ], 404);
        }

        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (!$session->canContinue()) {
            return response()->json([
                'message' => 'Interview session is not active',
            ], 400);
        }

        // Verify question belongs to this session
        $question = InterviewQuestion::where('id', $request->question_id)
            ->where('interview_session_id', $session->id)
            ->first();

        if (!$question) {
            return response()->json([
                'message' => 'Question not found in this session',
            ], 404);
        }

        if ($question->isAnswered()) {
            return response()->json([
                'message' => 'Question already answered',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Analyze the answer
            $response = $this->analysisService->analyzeAnswer(
                question: $question,
                answerText: $request->answer_text,
                responseTimeSeconds: $request->response_time_seconds,
                userId: Auth::id()
            );

            // Update session progress
            $session->incrementAnsweredQuestions();
            $session->calculateOverallScore();

            // Check if session is complete
            if ($session->questions_answered >= $session->total_questions) {
                $session->complete();
                
                // Generate performance report
                $report = $this->reportingService->generateReport($session);
            }

            DB::commit();

            return response()->json([
                'message' => 'Answer submitted successfully',
                'response' => $this->formatResponseAnalysis($response),
                'session_progress' => $session->getProgressPercentage(),
                'is_session_complete' => $session->status === 'completed',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to process answer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time feedback for a response
     * 
     * @param int $sessionId
     * @param int $questionId
     * @return JsonResponse
     */
    public function getFeedback(int $sessionId, int $questionId): JsonResponse
    {
        $question = InterviewQuestion::where('id', $questionId)
            ->where('interview_session_id', $sessionId)
            ->with(['response.feedback' => fn($q) => $q->orderBy('priority', 'desc')])
            ->first();

        if (!$question) {
            return response()->json([
                'message' => 'Question not found',
            ], 404);
        }

        // Authorization check
        $session = $question->interviewSession;
        
        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (!$question->response) {
            return response()->json([
                'message' => 'Question not yet answered',
            ], 400);
        }

        return response()->json([
            'feedback' => $question->response->feedback->map(function($feedback) {
                return [
                    'id' => $feedback->id,
                    'type' => $feedback->feedback_type,
                    'text' => $feedback->feedback_text,
                    'is_positive' => $feedback->is_positive,
                    'focus_area' => $feedback->focus_area,
                    'priority' => $feedback->priority,
                    'strengths' => $feedback->strengths,
                    'improvements' => $feedback->improvements,
                    'suggestions' => $feedback->suggestions,
                    'example_answers' => $feedback->example_answers,
                ];
            }),
            'scores' => [
                'overall' => $question->response->overall_score,
                'confidence' => $question->response->confidence_score,
                'clarity' => $question->response->clarity_score,
                'structure' => $question->response->structure_score,
                'content' => $question->response->content_score,
            ],
        ]);
    }

    /**
     * Get performance report for completed session
     * 
     * @param int $sessionId
     * @return JsonResponse
     */
    public function getReport(int $sessionId): JsonResponse
    {
        $session = InterviewSession::with([
            'performanceReport',
            'coachingTips',
        ])->find($sessionId);

        if (!$session) {
            return response()->json([
                'message' => 'Interview session not found',
            ], 404);
        }

        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        if ($session->status !== 'completed') {
            return response()->json([
                'message' => 'Session not yet completed',
                'status' => $session->status,
            ], 400);
        }

        if (!$session->performanceReport) {
            // Generate report if not exists
            try {
                $report = $this->reportingService->generateReport($session);
                $session->load('performanceReport', 'coachingTips');
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to generate performance report',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'report' => $this->formatPerformanceReport($session->performanceReport),
            'coaching_tips' => $this->formatCoachingTips($session->coachingTips),
            'session' => [
                'id' => $session->id,
                'company_name' => $session->company_name,
                'role_title' => $session->role_title,
                'completed_at' => $session->completed_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Abandon an active session
     * 
     * @param int $sessionId
     * @return JsonResponse
     */
    public function abandon(int $sessionId): JsonResponse
    {
        $session = InterviewSession::find($sessionId);

        if (!$session) {
            return response()->json([
                'message' => 'Interview session not found',
            ], 404);
        }

        if ($session->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        if ($session->status !== 'in_progress') {
            return response()->json([
                'message' => 'Can only abandon in-progress sessions',
            ], 400);
        }

        $session->abandon();

        return response()->json([
            'message' => 'Interview session abandoned',
        ]);
    }

    /**
     * Get user's interview session history
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $sessions = InterviewSession::where('user_id', $user->id)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->company, fn($q, $company) => $q->where('company_name', 'LIKE', "%{$company}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'sessions' => $sessions->map(fn($session) => $this->formatSessionSummary($session)),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'total_pages' => $sessions->lastPage(),
                'total_sessions' => $sessions->total(),
            ],
        ]);
    }

    /**
     * Helper: Check if user has sufficient AI credits
     */
    protected function checkAICredits($user): bool
    {
        return $user->getRemainingAICredits() >= 50;
    }

    /**
     * Helper: Deduct AI credits
     */
    protected function deductAICredits($user, int $credits): void
    {
        if ($subscription = $user->subscription) {
            $subscription->increment('ai_credits_used_this_month', $credits);
        }
    }

    /**
     * Format session response
     */
    protected function formatSessionResponse(InterviewSession $session): array
    {
        return [
            'id' => $session->id,
            'company_name' => $session->company_name,
            'role_title' => $session->role_title,
            'interview_type' => $session->interview_type,
            'status' => $session->status,
            'total_questions' => $session->total_questions,
            'questions_answered' => $session->questions_answered,
            'overall_score' => $session->overall_score,
            'progress_percentage' => $session->getProgressPercentage(),
            'started_at' => $session->started_at?->toISOString(),
            'completed_at' => $session->completed_at?->toISOString(),
            'performance_metrics' => $session->performance_metrics,
            'ai_insights' => $session->ai_insights,
            'interviewer_style' => $session->interviewer_style,
            'questions' => $session->questions->map(fn($q) => $this->formatQuestionResponse($q)),
        ];
    }

    /**
     * Format session summary
     */
    protected function formatSessionSummary(InterviewSession $session): array
    {
        return [
            'id' => $session->id,
            'company_name' => $session->company_name,
            'role_title' => $session->role_title,
            'status' => $session->status,
            'overall_score' => $session->overall_score,
            'progress_percentage' => $session->getProgressPercentage(),
            'created_at' => $session->created_at->toISOString(),
        ];
    }

    /**
     * Format question response
     */
    protected function formatQuestionResponse(InterviewQuestion $question): array
    {
        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'difficulty_level' => $question->difficulty_level,
            'order' => $question->order,
            'is_company_specific' => $question->is_company_specific,
            'company_context' => $question->company_context,
            'expected_elements' => $question->expected_elements,
            'expected_duration_seconds' => $question->getExpectedDuration(),
            'is_answered' => $question->isAnswered(),
            'requires_star_format' => $question->requiresSTARFormat(),
        ];
    }

    /**
     * Format response analysis
     */
    protected function formatResponseAnalysis($response): array
    {
        return [
            'id' => $response->id,
            'overall_score' => $response->overall_score,
            'confidence_score' => $response->confidence_score,
            'clarity_score' => $response->clarity_score,
            'structure_score' => $response->structure_score,
            'content_score' => $response->content_score,
            'word_count' => $response->calculateWordCount(),
            'filler_word_count' => $response->getFillerWordCount(),
            'filler_word_percentage' => $response->getFillerWordPercentage(),
            'filler_words' => $response->filler_words,
            'star_analysis' => $response->star_analysis,
            'has_star_components' => $response->hasSTARComponents(),
            'missing_star_components' => $response->getMissingSTARComponents(),
            'keywords_used' => $response->keywords_used,
            'missing_elements' => $response->missing_elements,
            'feedback' => $response->feedback->map(function($feedback) {
                return [
                    'type' => $feedback->feedback_type,
                    'text' => $feedback->feedback_text,
                    'is_positive' => $feedback->is_positive,
                    'suggestions' => $feedback->suggestions,
                ];
            }),
        ];
    }

    /**
     * Format performance report
     */
    protected function formatPerformanceReport($report): array
    {
        return [
            'overall_score' => $report->overall_score,
            'performance_grade' => $report->getPerformanceGrade(),
            'needs_more_practice' => $report->needsMorePractice(),
            'category_scores' => $report->category_scores,
            'top_strengths' => $report->getTopStrengths(),
            'top_weaknesses' => $report->getTopWeaknesses(),
            'prioritized_improvements' => $report->getPrioritizedImprovements(),
            'filler_word_analysis' => $report->filler_word_analysis,
            'star_methodology_score' => $report->star_methodology_score,
            'company_fit_analysis' => $report->company_fit_analysis,
            'recommended_practice_areas' => $report->recommended_practice_areas,
            'comparison_metrics' => $report->comparison_metrics,
        ];
    }

    /**
     * Format coaching tips
     */
    protected function formatCoachingTips($tips): array
    {
        if (!$tips) {
            return [];
        }

        return [
            'company_talking_points' => $tips->getTalkingPoints(),
            'role_specific_tips' => $tips->role_specific_tips,
            'interviewer_insights' => $tips->interviewer_insights,
            'cultural_alignment_points' => $tips->cultural_alignment_points,
            'technical_prep_areas' => $tips->technical_prep_areas,
            'common_mistakes' => $tips->getTopMistakes(),
            'success_strategies' => $tips->getSuccessStrategies(),
        ];
    }
}
