<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Models\Job;
use App\Services\AI\ResumeAIService;
use App\Services\ResumeExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function __construct(
        private ResumeAIService $aiService,
        private ResumeExportService $exportService
    ) {}

    /**
     * Display list of user's resumes
     */
    public function index()
    {
        $resumes = auth()->user()->resumes()
            ->with('template')
            ->latest()
            ->paginate(12);

        return view('resume.index', compact('resumes'));
    }

    /**
     * Show resume builder
     */
    public function create(Request $request)
    {
        $templates = ResumeTemplate::active()
            ->get()
            ->filter(fn($template) => $template->canBeAccessedBy(auth()->user()));

        $targetJob = null;
        if ($request->has('job_id')) {
            $targetJob = Job::find($request->job_id);
        }

        return view('resume.create', compact('templates', 'targetJob'));
    }

    /**
     * Store new resume
     */
    public function store(Request $request, \App\Actions\Resume\CreateResumeAction $createResumeAction)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'template_id' => 'required|exists:resume_templates,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            'professional_summary' => 'nullable|string',
            'target_job_id' => 'nullable|exists:jobs,id',
        ]);

        $resume = $createResumeAction->execute($request->user(), $validated);

        return redirect()->route('resume.edit', $resume)
            ->with('success', 'Resume created successfully!');
    }

    /**
     * Show resume editor
     */
    public function edit(Resume $resume)
    {
        $this->authorize('update', $resume);

        $templates = ResumeTemplate::active()->get();
        $pendingSuggestions = $resume->pendingSuggestions()->get();
        
        return view('resume.edit', compact('resume', 'templates', 'pendingSuggestions'));
    }

    /**
     * Update resume
     */
    public function update(Request $request, Resume $resume)
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'template_id' => 'exists:resume_templates,id',
            'full_name' => 'string|max:255',
            'email' => 'email',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            'professional_summary' => 'nullable|string',
            'experience' => 'nullable|array',
            'education' => 'nullable|array',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'projects' => 'nullable|array',
            'achievements' => 'nullable|array',
            'languages' => 'nullable|array',
            'volunteer_work' => 'nullable|array',
            'section_order' => 'nullable|array',
            'visibility_settings' => 'nullable|array',
        ]);

        // Create version before updating
        if ($request->boolean('save_version')) {
            $resume->createVersion($request->input('version_description'));
        }

        $resume->update($validated);

        return back()->with('success', 'Resume updated successfully!');
    }

    /**
     * Delete resume
     */
    public function destroy(Resume $resume)
    {
        $this->authorize('delete', $resume);

        // Delete associated files
        if ($resume->pdf_path) {
            Storage::disk('public')->delete($resume->pdf_path);
        }
        if ($resume->docx_path) {
            Storage::disk('public')->delete($resume->docx_path);
        }

        $resume->delete();

        return redirect()->route('resume.index')
            ->with('success', 'Resume deleted successfully!');
    }

    /**
     * Generate AI summary
     */
    public function generateSummary(Resume $resume)
    {
        $this->authorize('update', $resume);

        $targetJob = $resume->target_job_id ? Job::find($resume->target_job_id) : null;
        $summary = $this->aiService->generateProfessionalSummary($resume, $targetJob);

        $resume->update([
            'professional_summary' => $summary,
            'summary_is_ai_generated' => true,
        ]);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Extract skills from experience
     */
    public function extractSkills(Resume $resume)
    {
        $this->authorize('update', $resume);

        $skills = $this->aiService->extractSkills($resume);

        // Merge with existing skills
        $existingSkills = $resume->skills ?? [];
        $mergedSkills = array_merge_recursive($existingSkills, $skills);

        $resume->update(['skills' => $mergedSkills]);

        return response()->json([
            'success' => true,
            'skills' => $mergedSkills,
        ]);
    }

    /**
     * Optimize for specific job
     */
    public function optimizeForJob(Request $request, Resume $resume)
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id',
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $suggestions = $this->aiService->customizeForJob($resume, $job);

        $resume->update([
            'target_job_id' => $job->id,
            'last_ai_optimized_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'message' => count($suggestions) . ' optimization suggestions generated',
        ]);
    }

    /**
     * Analyze ATS compatibility
     */
    public function analyzeATS(Resume $resume)
    {
        $this->authorize('view', $resume);

        $analysis = $this->aiService->analyzeATSCompatibility($resume);

        $resume->update([
            'ats_score' => $this->scoreToLevel($analysis['score']),
            'ats_analysis' => $analysis,
        ]);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
        ]);
    }

    /**
     * Export resume as PDF
     */
    public function exportPdf(Resume $resume)
    {
        $this->authorize('view', $resume);

        $pdfPath = $this->exportService->exportToPDF($resume);

        $resume->update(['pdf_path' => $pdfPath]);
        $resume->incrementDownloadCount();

        return response()->download(storage_path('app/public/' . $pdfPath));
    }

    /**
     * Export resume as DOCX
     */
    public function exportDocx(Resume $resume)
    {
        $this->authorize('view', $resume);

        $docxPath = $this->exportService->exportToDOCX($resume);

        $resume->update(['docx_path' => $docxPath]);
        $resume->incrementDownloadCount();

        return response()->download(storage_path('app/public/' . $docxPath));
    }

    /**
     * Preview resume
     */
    public function preview(Resume $resume)
    {
        $this->authorize('view', $resume);

        return view('resume.preview', compact('resume'));
    }

    /**
     * Public resume view (via share token)
     */
    public function publicView(string $shareToken)
    {
        $resume = Resume::where('share_token', $shareToken)
            ->where('is_public', true)
            ->firstOrFail();

        $resume->incrementViewCount();

        return view('resume.public', compact('resume'));
    }

    /**
     * Toggle public sharing
     */
    public function togglePublic(Resume $resume)
    {
        $this->authorize('update', $resume);

        $resume->update(['is_public' => !$resume->is_public]);

        return back()->with('success', 'Sharing settings updated!');
    }

    /**
     * Set as default resume
     */
    public function setDefault(Resume $resume)
    {
        $this->authorize('update', $resume);

        // Unset other default resumes
        auth()->user()->resumes()->update(['is_default' => false]);

        $resume->update(['is_default' => true]);

        return back()->with('success', 'Default resume updated!');
    }

    /**
     * Accept AI suggestion
     */
    public function acceptSuggestion(Resume $resume, int $suggestionId)
    {
        $this->authorize('update', $resume);

        $suggestion = $resume->suggestions()->findOrFail($suggestionId);
        $suggestion->accept();

        // Apply the suggestion based on section
        $this->applySuggestion($resume, $suggestion);

        return response()->json([
            'success' => true,
            'message' => 'Suggestion applied successfully',
        ]);
    }

    /**
     * Reject AI suggestion
     */
    public function rejectSuggestion(Resume $resume, int $suggestionId)
    {
        $this->authorize('update', $resume);

        $suggestion = $resume->suggestions()->findOrFail($suggestionId);
        $suggestion->reject();

        return response()->json([
            'success' => true,
            'message' => 'Suggestion rejected',
        ]);
    }

    /**
     * Duplicate resume
     */
    public function duplicate(Resume $resume)
    {
        $this->authorize('view', $resume);

        $newResume = $resume->replicate();
        $newResume->title = $resume->title . ' (Copy)';
        $newResume->slug = null;
        $newResume->share_token = null;
        $newResume->is_default = false;
        $newResume->is_public = false;
        $newResume->view_count = 0;
        $newResume->download_count = 0;
        $newResume->save();

        return redirect()->route('resume.edit', $newResume)
            ->with('success', 'Resume duplicated successfully!');
    }

    /**
     * Private helper methods
     */
    private function scoreToLevel(int $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }

    private function applySuggestion(Resume $resume, $suggestion): void
    {
        switch ($suggestion->section) {
            case 'summary':
                $resume->update(['professional_summary' => $suggestion->suggested_content]);
                break;
            
            case 'skills':
                $currentSkills = $resume->skills ?? [];
                // Add suggested skills (logic depends on metadata)
                $resume->update(['skills' => $currentSkills]);
                break;
            
            // Handle other sections as needed
        }
    }
}
