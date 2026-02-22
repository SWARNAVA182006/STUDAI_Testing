<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use App\Services\ResumeCustomizerService;
use App\Services\CoverLetterGeneratorService;
use App\Services\ApplicationTrackerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationAssistantController extends Controller
{
    protected $resumeCustomizer;
    protected $coverLetterGenerator;
    protected $applicationTracker;
    
    public function __construct(
        ResumeCustomizerService $resumeCustomizer,
        CoverLetterGeneratorService $coverLetterGenerator,
        ApplicationTrackerService $applicationTracker
    ) {
        $this->middleware('auth');
        $this->resumeCustomizer = $resumeCustomizer;
        $this->coverLetterGenerator = $coverLetterGenerator;
        $this->applicationTracker = $applicationTracker;
    }
    
    /**
     * Show application assistant dashboard
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get pipeline statistics
        $stats = $this->applicationTracker->getPipelineStats($user);
        
        // Get pending follow-ups
        $followUps = $this->applicationTracker->getPendingFollowUps($user);
        
        // Get application trends
        $trends = $this->applicationTracker->getApplicationTrends($user, 6);
        
        // Get success insights
        $insights = $this->applicationTracker->getSuccessInsights($user);
        
        return view('applications.assistant.index', compact('stats', 'followUps', 'trends', 'insights'));
    }
    
    /**
     * Show resume customizer for a specific job
     */
    public function customizeResume(Request $request, Job $job)
    {
        $user = auth()->user();
        
        // Get user's resume content
        $resumeContent = $this->getUserResumeContent($user);
        
        if (!$resumeContent) {
            return redirect()->back()->with('error', 'Please upload your resume first.');
        }
        
        // Customize resume for job
        $result = $this->resumeCustomizer->customizeForJob($resumeContent, $job);
        
        return view('applications.assistant.resume-customizer', [
            'job' => $job,
            'original_resume' => $resumeContent,
            'optimized_resume' => $result['optimized_resume'],
            'ats_score' => $result['ats_score'],
            'keywords_matched' => $result['keywords_matched'],
            'keywords_missing' => $result['keywords_missing'],
            'suggestions' => $result['suggestions'],
            'strength_score' => $result['strength_score'],
        ]);
    }
    
    /**
     * Generate cover letter for a job
     */
    public function generateCoverLetter(Request $request, Job $job)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'tone' => 'nullable|in:professional,enthusiastic,confident,conversational,creative',
            'template' => 'nullable|in:standard,problem_solution,storytelling,achievements_focused,t_format',
        ]);
        
        $options = [
            'tone' => $validated['tone'] ?? 'professional',
            'template' => $validated['template'] ?? 'standard',
        ];
        
        // Generate cover letter
        $result = $this->coverLetterGenerator->generateForJob($user, $job, $options);
        
        return view('applications.assistant.cover-letter-generator', [
            'job' => $job,
            'cover_letter' => $result['content'],
            'alternatives' => $result['alternatives'],
            'tone' => $result['tone'],
            'template' => $result['template'],
            'word_count' => $result['word_count'],
            'personalization_score' => $result['personalization_score'],
            'available_tones' => $this->coverLetterGenerator->getAvailableTones(),
            'available_templates' => $this->coverLetterGenerator->getAvailableTemplates(),
            'suggestions' => $this->coverLetterGenerator->getSuggestions($result['content'], $job),
        ]);
    }
    
    /**
     * Save customized resume
     */
    public function saveCustomizedResume(Request $request, Job $job)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'content' => 'required|string',
        ]);
        
        // Export optimized resume
        $result = $this->resumeCustomizer->exportOptimizedResume(
            $validated['content'],
            $user,
            $job
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Resume saved successfully',
            'file_path' => $result['path'],
            'download_url' => $result['url'],
        ]);
    }
    
    /**
     * Save cover letter draft
     */
    public function saveCoverLetterDraft(Request $request, Job $job)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'tone' => 'nullable|string',
            'template' => 'nullable|string',
        ]);
        
        $draft = $this->coverLetterGenerator->saveDraft(
            $user,
            $job,
            $validated['content'],
            [
                'tone' => $validated['tone'] ?? 'professional',
                'template' => $validated['template'] ?? 'standard',
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Cover letter draft saved',
            'draft_id' => $draft->id,
        ]);
    }
    
    /**
     * Show application tracker
     */
    public function tracker()
    {
        $user = auth()->user();
        
        // Get all user applications with jobs
        $applications = Application::where('user_id', $user->id)
            ->with(['job.company'])
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);
        
        // Get statistics
        $stats = $this->applicationTracker->getPipelineStats($user);
        
        // Get timeline
        $timeline = $this->applicationTracker->getApplicationTimeline($user);
        
        // Get response rates by company
        $responseRates = $this->applicationTracker->getResponseRateByCompany($user);
        
        return view('applications.assistant.tracker', compact(
            'applications',
            'stats',
            'timeline',
            'responseRates'
        ));
    }
    
    /**
     * Show follow-up reminders
     */
    public function followUps()
    {
        $user = auth()->user();
        
        $followUps = $this->applicationTracker->getPendingFollowUps($user);
        
        return view('applications.assistant.follow-ups', compact('followUps'));
    }
    
    /**
     * Generate follow-up email
     */
    public function generateFollowUpEmail(Application $application)
    {
        $this->authorize('view', $application);
        
        $emailContent = $this->applicationTracker->generateFollowUpEmail($application);
        
        return response()->json([
            'success' => true,
            'email_content' => $emailContent,
        ]);
    }
    
    /**
     * Update application status
     */
    public function updateStatus(Request $request, Application $application)
    {
        $this->authorize('update', $application);
        
        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,viewed,shortlisted,interview_scheduled,interviewed,offered,accepted,rejected,withdrawn',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $this->applicationTracker->updateApplicationStatus(
            $application,
            $validated['status'],
            $validated['notes'] ?? null
        );
        
        return redirect()->back()->with('success', 'Application status updated');
    }
    
    /**
     * Show analytics dashboard
     */
    public function analytics()
    {
        $user = auth()->user();
        
        // Get comprehensive statistics
        $stats = $this->applicationTracker->getPipelineStats($user);
        $trends = $this->applicationTracker->getApplicationTrends($user, 12);
        $responseRates = $this->applicationTracker->getResponseRateByCompany($user);
        $insights = $this->applicationTracker->getSuccessInsights($user);
        $interviewSchedule = $this->applicationTracker->getInterviewSchedule($user);
        
        return view('applications.assistant.analytics', compact(
            'stats',
            'trends',
            'responseRates',
            'insights',
            'interviewSchedule'
        ));
    }
    
    /**
     * Show interview schedule
     */
    public function interviewSchedule()
    {
        $user = auth()->user();
        
        $schedule = $this->applicationTracker->getInterviewSchedule($user);
        
        return view('applications.assistant.interview-schedule', compact('schedule'));
    }
    
    /**
     * Get user's resume content
     */
    protected function getUserResumeContent($user)
    {
        // Try to get from profile
        if ($user->profile && $user->profile->resume_file) {
            $resumePath = $user->profile->resume_file;
            
            if (Storage::exists($resumePath)) {
                // For text-based resumes
                if (pathinfo($resumePath, PATHINFO_EXTENSION) === 'txt') {
                    return Storage::get($resumePath);
                }
                
                // For PDF/DOCX, would need parsing service (not implemented here)
                // Return profile text instead
                return $this->generateResumeFromProfile($user->profile);
            }
        }
        
        // Fallback to generating from profile data
        if ($user->profile) {
            return $this->generateResumeFromProfile($user->profile);
        }
        
        return null;
    }
    
    /**
     * Generate resume text from profile data
     */
    protected function generateResumeFromProfile($profile)
    {
        $resume = "{$profile->user->name}\n";
        $resume .= "{$profile->user->email}\n";
        
        if ($profile->headline) {
            $resume .= "{$profile->headline}\n";
        }
        
        $resume .= "\n";
        
        if ($profile->summary) {
            $resume .= "PROFESSIONAL SUMMARY\n";
            $resume .= "{$profile->summary}\n\n";
        }
        
        if (!empty($profile->experience)) {
            $resume .= "EXPERIENCE\n";
            foreach ($profile->experience as $exp) {
                $resume .= "{$exp['title']} at {$exp['company']}\n";
                $resume .= "{$exp['duration']}\n";
                $resume .= "{$exp['description']}\n\n";
            }
        }
        
        if (!empty($profile->education)) {
            $resume .= "EDUCATION\n";
            foreach ($profile->education as $edu) {
                $resume .= "{$edu['degree']} - {$edu['institution']}\n";
                $resume .= "{$edu['year']}\n\n";
            }
        }
        
        if (!empty($profile->skills)) {
            $resume .= "SKILLS\n";
            $resume .= implode(', ', $profile->skills) . "\n";
        }
        
        return $resume;
    }
}
