<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use App\Events\JobApplied;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApplicationController extends Controller
{
    use AuthorizesRequests;
    
    protected $applicationService;
    
    public function __construct(ApplicationService $applicationService)
    {
        $this->middleware('auth');
        $this->applicationService = $applicationService;
    }
    
    /**
     * Display all applications for the user
     */
    public function index(Request $request)
    {
        $query = Application::where('user_id', Auth::id())
            ->with(['job.company'])
            ->latest();
        
        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Search by job title or company
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('job', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $applications = $query->paginate(20);
        
        // Get status counts
        $statusCounts = Application::where('user_id', Auth::id())
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
        
        return view('applications.index', [
            'applications' => $applications,
            'statusCounts' => $statusCounts,
        ]);
    }
    
    /**
     * Show single application details
     */
    public function show(Application $application)
    {
        $this->authorize('view', $application);
        
        $application->load(['job.company', 'user']);
        
        return view('applications.show', [
            'application' => $application,
            'timeline' => $application->timeline ?? [],
        ]);
    }
    
    /**
     * Show application form
     */
    public function create(Job $job)
    {
        // Check for existing application
        if ($this->applicationService->hasPreviousApplication(Auth::user(), $job)) {
            return redirect()
                ->route('jobs.show', $job->slug)
                ->with('error', 'You have already applied to this job');
        }
        
        // Check if job is still active
        if ($job->status !== 'active') {
            return redirect()
                ->route('jobs.show', $job->slug)
                ->with('error', 'This job is no longer accepting applications');
        }
        
        // Check subscription limits
        if (!Auth::user()->hasFeature('unlimited_applications')) {
            $remaining = Auth::user()->getRemainingApplications();
            if ($remaining <= 0) {
                return redirect()
                    ->route('pricing')
                    ->with('error', 'Application limit reached. Please upgrade your plan.');
            }
        }
        
        $job->load('company');
        
        // Check for saved draft
        $draft = Application::where('user_id', Auth::id())
            ->where('job_id', $job->id)
            ->where('status', 'draft')
            ->first();
        
        return view('applications.create', [
            'job' => $job,
            'draft' => $draft,
            'profile' => Auth::user()->profile,
        ]);
    }
    
    /**
     * Store new application
     */
    public function store(Request $request, Job $job)
    {
        $validated = $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'cover_letter' => 'nullable|string|max:5000',
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string|max:2000',
        ]);
        
        try {
            // Check for duplicate
            if ($this->applicationService->hasPreviousApplication(Auth::user(), $job)) {
                return back()->with('error', 'You have already applied to this job');
            }
            
            // Store resume file
            $resumePath = $request->file('resume')->store('resumes/' . Auth::id(), 'private');
            
            // Create application
            $application = Application::create([
                'job_id' => $job->id,
                'user_id' => Auth::id(),
                'application_number' => $this->applicationService->generateApplicationNumber(),
                'resume_file' => $resumePath,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'answers' => $validated['answers'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            
            // Calculate match score
            $matchScore = $this->applicationService->calculateMatch($application);
            
            $application->update([
                'match_score' => $matchScore['score'],
                'match_analysis' => $matchScore['analysis'],
            ]);
            
            // Update user's application count
            if (Auth::user()->subscription) {
                Auth::user()->subscription->increment('applications_used_this_month');
            }
            
            // Dispatch event — listeners handle employer & applicant notifications
            JobApplied::dispatch(Auth::user(), $job, $application);

            return redirect()
                ->route('applications.show', $application)
                ->with('success', 'Application submitted successfully!');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
    
    /**
     * Quick apply with profile data
     */
    public function quickApply(Job $job)
    {
        try {
            $application = $this->applicationService->quickApply(Auth::user(), $job);
            
            return redirect()
                ->route('applications.show', $application)
                ->with('success', 'Application submitted successfully using your profile!');
                
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Save application as draft
     */
    public function saveDraft(Request $request, Job $job)
    {
        $validated = $request->validate([
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'cover_letter' => 'nullable|string|max:5000',
            'answers' => 'nullable|array',
        ]);
        
        $data = $validated;
        
        // Handle resume upload if provided
        if ($request->hasFile('resume')) {
            $data['resume_file'] = $request->file('resume')->store('resumes/' . Auth::id(), 'private');
        }
        
        $draft = $this->applicationService->saveDraft(Auth::user(), $job, $data);
        
        return response()->json([
            'message' => 'Draft saved successfully',
            'draft_id' => $draft->id,
        ]);
    }
    
    /**
     * Submit a draft application
     */
    public function submitDraft(Application $application)
    {
        $this->authorize('update', $application);
        
        try {
            $this->applicationService->submitDraft($application);
            
            return redirect()
                ->route('applications.show', $application)
                ->with('success', 'Application submitted successfully!');
                
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Withdraw application
     */
    public function withdraw(Request $request, Application $application)
    {
        $this->authorize('update', $application);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->applicationService->withdraw($application, $validated['reason'] ?? null);
            
            return back()->with('success', 'Application withdrawn successfully');
            
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * Download resume
     */
    public function downloadResume(Application $application)
    {
        $this->authorize('view', $application);
        
        if (!$application->resume_file) {
            return back()->with('error', 'No resume file found');
        }
        
        $filePath = storage_path('app/private/' . $application->resume_file);
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'Resume file not found');
        }
        
        return response()->download(
            $filePath,
            'resume-' . $application->application_number . '.pdf'
        );
    }
    
    /**
     * Get application statistics
     */
    public function statistics()
    {
        $userId = Auth::id();
        
        $stats = [
            'total' => Application::where('user_id', $userId)->count(),
            'submitted' => Application::where('user_id', $userId)->where('status', 'submitted')->count(),
            'viewed' => Application::where('user_id', $userId)->where('status', 'viewed')->count(),
            'shortlisted' => Application::where('user_id', $userId)->where('status', 'shortlisted')->count(),
            'interviewed' => Application::where('user_id', $userId)->where('status', 'interviewed')->count(),
            'offered' => Application::where('user_id', $userId)->where('status', 'offered')->count(),
            'rejected' => Application::where('user_id', $userId)->where('status', 'rejected')->count(),
        ];
        
        $stats['response_rate'] = $stats['total'] > 0 
            ? round((($stats['viewed'] + $stats['shortlisted'] + $stats['interviewed'] + $stats['offered']) / $stats['total']) * 100, 1)
            : 0;
        
        $stats['success_rate'] = $stats['total'] > 0
            ? round(($stats['offered'] / $stats['total']) * 100, 1)
            : 0;
        
        // Average match score
        $stats['avg_match_score'] = Application::where('user_id', $userId)
            ->whereNotNull('match_score')
            ->avg('match_score');
        
        return response()->json($stats);
    }
}
