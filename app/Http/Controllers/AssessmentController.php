<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class AssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * List all available assessments
     */
    public function index(Request $request)
    {
        $query = Assessment::active()->with('skill');
        
        // Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->difficulty($request->difficulty);
        }
        
        // Filter by skill
        if ($request->filled('skill_id')) {
            $query->where('skill_id', $request->skill_id);
        }
        
        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        // Sort
        $sort = $request->get('sort', 'featured');
        switch ($sort) {
            case 'popular':
                $query->orderByDesc('attempts_count');
                break;
            case 'easy':
                $query->orderByRaw("FIELD(difficulty, 'beginner', 'intermediate', 'advanced', 'expert')");
                break;
            case 'hard':
                $query->orderByRaw("FIELD(difficulty, 'expert', 'advanced', 'intermediate', 'beginner')");
                break;
            default:
                $query->orderByDesc('is_featured')->orderBy('title');
        }
        
        $assessments = $query->paginate(20);
        
        // Get user's completed assessments
        $completed = Auth::user()->assessmentAttempts()
            ->completed()
            ->pluck('assessment_id')
            ->toArray();
        
        return view('assessments.index', compact('assessments', 'completed'));
    }
    
    /**
     * Show assessment details
     */
    public function show(Assessment $assessment)
    {
        $assessment->load('skill');
        
        $user = Auth::user();
        
        // Get user's attempts for this assessment
        $attempts = $user->assessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->orderByDesc('created_at')
            ->get();
        
        $bestScore = $assessment->getUserBestScore($user);
        $hasPassed = $assessment->hasUserPassed($user);
        
        // Check if there's an in-progress attempt
        $inProgressAttempt = $user->assessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();
        
        return view('assessments.show', compact(
            'assessment',
            'attempts',
            'bestScore',
            'hasPassed',
            'inProgressAttempt'
        ));
    }
    
    /**
     * Start a new assessment attempt
     */
    public function start(Assessment $assessment)
    {
        $user = Auth::user();
        
        // Check if user has subscription/credits for assessments
        if (!$user->hasFeature('unlimited_assessments')) {
            // Check assessment attempt limits
            $monthlyAttempts = $user->assessmentAttempts()
                ->whereMonth('created_at', now()->month)
                ->count();
            
            $limit = $user->subscription?->subscriptionPlan->assessment_limit ?? 3;
            
            if ($monthlyAttempts >= $limit) {
                return back()->with('error', 'You have reached your monthly assessment limit. Please upgrade your plan.');
            }
        }
        
        // Check for existing in-progress attempt
        $existingAttempt = $user->assessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->where('status', 'in_progress')
            ->first();
        
        if ($existingAttempt && !$existingAttempt->isExpired()) {
            return redirect()->route('assessments.take', $existingAttempt->id);
        }
        
        // Create new attempt
        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'answers' => [],
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($assessment->duration_minutes),
            'total_questions' => count($assessment->questions),
        ]);
        
        return redirect()->route('assessments.take', $attempt->id);
    }
    
    /**
     * Take assessment (show questions)
     */
    public function take(AssessmentAttempt $attempt)
    {
        // Verify user owns this attempt
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }
        
        // Check if expired or completed
        if ($attempt->status === 'completed') {
            return redirect()->route('assessments.result', $attempt->id);
        }
        
        if ($attempt->isExpired()) {
            $attempt->update(['status' => 'expired']);
            return redirect()->route('assessments.result', $attempt->id)
                ->with('warning', 'Assessment time has expired.');
        }
        
        $assessment = $attempt->assessment;
        
        return view('assessments.take', compact('attempt', 'assessment'));
    }
    
    /**
     * Save answer (AJAX)
     */
    public function saveAnswer(Request $request, AssessmentAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if ($attempt->status !== 'in_progress' || $attempt->isExpired()) {
            return response()->json(['error' => 'Assessment expired'], 400);
        }
        
        $validated = $request->validate([
            'question_index' => 'required|integer',
            'answer' => 'required',
        ]);
        
        $answers = $attempt->answers;
        $answers[$validated['question_index']] = $validated['answer'];
        
        $attempt->update(['answers' => $answers]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Submit assessment
     */
    public function submit(AssessmentAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }
        
        if ($attempt->status === 'completed') {
            return redirect()->route('assessments.result', $attempt->id);
        }
        
        // Mark as completed and calculate score
        $attempt->markCompleted();
        
        return redirect()->route('assessments.result', $attempt->id)
            ->with('success', 'Assessment submitted successfully!');
    }
    
    /**
     * Show assessment results
     */
    public function result(AssessmentAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }
        
        $attempt->load(['assessment', 'certificate']);
        
        $assessment = $attempt->assessment;
        $questions = $assessment->questions;
        $userAnswers = $attempt->answers;
        
        // Build detailed results
        $results = [];
        foreach ($questions as $index => $question) {
            $userAnswer = $userAnswers[$index] ?? null;
            $isCorrect = $this->checkAnswer($question, $userAnswer);
            
            $results[] = [
                'question' => $question,
                'user_answer' => $userAnswer,
                'is_correct' => $isCorrect,
                'explanation' => $question['explanation'] ?? null,
            ];
        }
        
        return view('assessments.result', compact('attempt', 'assessment', 'results'));
    }
    
    /**
     * Download certificate PDF
     */
    public function downloadCertificate(Certificate $certificate)
    {
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }
        
        $certificate->load(['user', 'assessment']);
        
        $pdf = Pdf::loadView('certificates.pdf', compact('certificate'));
        
        $filename = 'certificate-' . $certificate->certificate_number . '.pdf';
        
        return $pdf->download($filename);
    }
    
    /**
     * Verify certificate by code
     */
    public function verify(Request $request)
    {
        $code = $request->get('code');
        
        if (!$code) {
            return view('certificates.verify', ['certificate' => null]);
        }
        
        $certificate = Certificate::verify($code);
        
        if ($certificate) {
            $certificate->load(['user', 'assessment', 'attempt']);
        }
        
        return view('certificates.verify', compact('certificate'));
    }
    
    /**
     * User's certificates
     */
    public function certificates()
    {
        $certificates = Auth::user()->certificates()
            ->with(['assessment', 'attempt'])
            ->orderByDesc('issued_at')
            ->paginate(20);
        
        return view('assessments.certificates', compact('certificates'));
    }
    
    /**
     * User's badges
     */
    public function badges()
    {
        $badges = Auth::user()->badges()
            ->wherePivot('is_visible', true)
            ->orderBy('badge_user.earned_at', 'desc')
            ->get();
        
        return view('assessments.badges', compact('badges'));
    }
    
    /**
     * Helper: Check if answer is correct
     */
    protected function checkAnswer($question, $userAnswer): bool
    {
        if ($userAnswer === null) return false;
        
        $type = $question['type'] ?? 'mcq';
        
        switch ($type) {
            case 'mcq':
            case 'true_false':
                return $userAnswer === $question['correct_answer'];
                
            case 'multiple_choice':
                $correct = $question['correct_answers'] ?? [];
                sort($correct);
                $user = is_array($userAnswer) ? $userAnswer : [$userAnswer];
                sort($user);
                return $correct === $user;
                
            case 'fill_blank':
                $correct = strtolower(trim($question['correct_answer']));
                $user = strtolower(trim($userAnswer));
                return $correct === $user;
                
            default:
                return false;
        }
    }
}
