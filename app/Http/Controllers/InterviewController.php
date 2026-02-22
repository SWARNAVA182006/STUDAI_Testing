<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Company;
use App\Models\InterviewSession;
use App\Services\MockInterviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class InterviewController extends Controller
{
    protected $mockInterviewService;
    
    public function __construct(MockInterviewService $mockInterviewService)
    {
        $this->middleware('auth');
        $this->mockInterviewService = $mockInterviewService;
    }
    
    /**
     * Show interview preparation dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's interview sessions
        $sessions = InterviewSession::where('user_id', $user->id)
            ->latest()
            ->get();
        
        // Get recent/upcoming interviews
        $upcomingInterviews = $user->applications()
            ->whereIn('status', ['shortlisted', 'interview_scheduled'])
            ->with('job.company')
            ->latest()
            ->take(5)
            ->get();
        
        // Get interview tips
        $tips = $this->mockInterviewService->getGenericTips();
        
        return view('interview.index', compact('sessions', 'upcomingInterviews', 'tips'));
    }
    
    /**
     * Show mock interview setup page
     */
    public function create(Request $request)
    {
        $jobId = $request->get('job_id');
        $job = null;
        $company = null;
        
        if ($jobId) {
            $job = Job::with('company')->find($jobId);
            $company = $job?->company;
        }
        
        return view('interview.create', compact('job', 'company'));
    }
    
    /**
     * Start a mock interview session
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'experience_level' => 'required|in:entry,mid,senior,executive',
            'company_id' => 'nullable|exists:companies,id',
            'question_count' => 'integer|min:5|max:20',
        ]);
        
        $company = null;
        if (!empty($validated['company_id'])) {
            $company = Company::find($validated['company_id']);
        }
        
        // Generate questions
        $questions = $this->mockInterviewService->generateQuestions(
            $validated['job_title'],
            $validated['experience_level'],
            $company,
            $validated['question_count'] ?? 10
        );
        
        // Store session in cache for 2 hours
        $sessionId = uniqid('interview_', true);
        Cache::put("interview_session_{$sessionId}", [
            'job_title' => $validated['job_title'],
            'experience_level' => $validated['experience_level'],
            'company' => $company?->name,
            'questions' => $questions,
            'answers' => [],
            'started_at' => now(),
        ], 7200);
        
        return redirect()->route('interview.session', ['session' => $sessionId]);
    }
    
    /**
     * Display interview session
     */
    public function session($sessionId)
    {
        $session = Cache::get("interview_session_{$sessionId}");
        
        if (!$session) {
            return redirect()->route('interview.index')
                ->with('error', 'Interview session expired or not found.');
        }
        
        return view('interview.session', [
            'sessionId' => $sessionId,
            'session' => $session,
        ]);
    }
    
    /**
     * Submit answer to a question
     */
    public function submitAnswer(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'question_index' => 'required|integer',
            'question' => 'required|string',
            'answer' => 'required|string|max:5000',
        ]);
        
        $session = Cache::get("interview_session_{$sessionId}");
        
        if (!$session) {
            return response()->json(['error' => 'Session expired'], 404);
        }
        
        // Evaluate the answer
        $evaluation = $this->mockInterviewService->evaluateAnswer(
            $validated['question'],
            $validated['answer'],
            [
                'job_title' => $session['job_title'],
                'experience_level' => $session['experience_level'],
            ]
        );
        
        // Store answer and evaluation
        $session['answers'][$validated['question_index']] = [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'evaluation' => $evaluation,
            'answered_at' => now()->toDateTimeString(),
        ];
        
        // Update session
        Cache::put("interview_session_{$sessionId}", $session, 7200);
        
        return response()->json([
            'success' => true,
            'evaluation' => $evaluation,
        ]);
    }
    
    /**
     * Get follow-up questions
     */
    public function getFollowUp(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);
        
        $followUps = $this->mockInterviewService->generateFollowUp(
            $validated['question'],
            $validated['answer']
        );
        
        return response()->json($followUps);
    }
    
    /**
     * Complete interview session and show results
     */
    public function complete($sessionId)
    {
        $session = Cache::get("interview_session_{$sessionId}");
        
        if (!$session) {
            return redirect()->route('interview.index')
                ->with('error', 'Interview session expired or not found.');
        }
        
        // Calculate overall performance
        $totalQuestions = count($session['answers']);
        $totalScore = 0;
        $categoryScores = [
            'behavioral' => ['total' => 0, 'count' => 0],
            'technical' => ['total' => 0, 'count' => 0],
            'situational' => ['total' => 0, 'count' => 0],
        ];
        
        foreach ($session['answers'] as $answer) {
            if (isset($answer['evaluation']['score'])) {
                $totalScore += $answer['evaluation']['score'];
            }
        }
        
        $averageScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions, 1) : 0;
        
        // Performance grade
        $grade = $this->getPerformanceGrade($averageScore);
        
        return view('interview.complete', [
            'sessionId' => $sessionId,
            'session' => $session,
            'averageScore' => $averageScore,
            'grade' => $grade,
            'totalQuestions' => $totalQuestions,
        ]);
    }
    
    /**
     * Show common questions for a role
     */
    public function commonQuestions(Request $request)
    {
        $jobTitle = $request->get('job_title', 'Software Developer');
        $questions = $this->mockInterviewService->getCommonQuestions($jobTitle);
        
        return view('interview.common-questions', compact('jobTitle', 'questions'));
    }
    
    /**
     * Show STAR method guide
     */
    public function starGuide()
    {
        return view('interview.star-guide');
    }
    
    /**
     * Format answer with STAR method
     */
    public function formatStar(Request $request)
    {
        $validated = $request->validate([
            'answer' => 'required|string|max:5000',
        ]);
        
        $formatted = $this->mockInterviewService->formatWithSTAR($validated['answer']);
        
        return response()->json($formatted);
    }
    
    /**
     * Show salary negotiation guide
     */
    public function salaryNegotiation()
    {
        $user = Auth::user();
        
        return view('interview.salary-negotiation', [
            'user' => $user,
        ]);
    }
    
    /**
     * Get personalized negotiation guide
     */
    public function getNegotiationGuide(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string',
            'current_salary' => 'required|numeric|min:0',
            'target_salary' => 'required|numeric|min:0',
            'years_experience' => 'nullable|integer',
            'unique_skills' => 'nullable|array',
        ]);
        
        $guide = $this->mockInterviewService->getSalaryNegotiationGuide(
            $validated['job_title'],
            $validated['current_salary'],
            $validated['target_salary'],
            [
                'years_experience' => $validated['years_experience'] ?? 0,
                'unique_skills' => $validated['unique_skills'] ?? [],
            ]
        );
        
        return response()->json($guide);
    }
    
    /**
     * Interview tips for specific job
     */
    public function tips(Request $request)
    {
        $jobId = $request->get('job_id');
        $job = null;
        $company = null;
        
        if ($jobId) {
            $job = Job::with('company')->find($jobId);
            $company = $job?->company;
        }
        
        $tips = $this->mockInterviewService->getInterviewTips(
            $job?->title ?? 'General Position',
            $company
        );
        
        return view('interview.tips', compact('job', 'company', 'tips'));
    }
    
    /**
     * Find interview coaches (placeholder for future LMS integration)
     */
    public function findCoaches(Request $request)
    {
        $jobTitle = $request->get('job_title', '');
        
        // For now, redirect to Google search
        // TODO: Integrate with LMS API when available
        $searchQuery = urlencode("interview coach for {$jobTitle}");
        $searchUrl = "https://www.google.com/search?q={$searchQuery}";
        
        // Store preference for future notifications
        if (Auth::check()) {
            $user = Auth::user();
            $preferences = $user->preferences ?? [];
            $preferences['coaching_interest'] = [
                'job_title' => $jobTitle,
                'interested_at' => now()->toDateTimeString(),
            ];
            $user->update(['preferences' => $preferences]);
        }
        
        return redirect()->away($searchUrl);
    }
    
    /**
     * Save practice recording metadata
     */
    public function saveRecording(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'duration' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Store recording metadata in user preferences
        $user = Auth::user();
        $preferences = $user->preferences ?? [];
        $recordings = $preferences['interview_recordings'] ?? [];
        
        $recordings[] = [
            'question' => $validated['question'],
            'duration' => $validated['duration'],
            'notes' => $validated['notes'] ?? '',
            'recorded_at' => now()->toDateTimeString(),
        ];
        
        // Keep only last 20 recordings
        if (count($recordings) > 20) {
            $recordings = array_slice($recordings, -20);
        }
        
        $preferences['interview_recordings'] = $recordings;
        $user->update(['preferences' => $preferences]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get performance grade based on score
     */
    protected function getPerformanceGrade($score)
    {
        if ($score >= 90) {
            return ['grade' => 'A+', 'label' => 'Excellent', 'color' => 'green'];
        } elseif ($score >= 80) {
            return ['grade' => 'A', 'label' => 'Very Good', 'color' => 'green'];
        } elseif ($score >= 70) {
            return ['grade' => 'B', 'label' => 'Good', 'color' => 'blue'];
        } elseif ($score >= 60) {
            return ['grade' => 'C', 'label' => 'Fair', 'color' => 'yellow'];
        } else {
            return ['grade' => 'D', 'label' => 'Needs Improvement', 'color' => 'red'];
        }
    }
}
