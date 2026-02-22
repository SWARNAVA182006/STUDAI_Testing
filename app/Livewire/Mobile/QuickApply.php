<?php

declare(strict_types=1);

namespace App\Livewire\Mobile;

use App\Models\Job;
use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class QuickApply extends Component
{
    public ?int $jobId = null;
    public ?Job $job = null;
    public bool $isOpen = false;
    public int $step = 1;
    
    // Application data
    public ?int $selectedResumeId = null;
    public string $coverLetter = '';
    public bool $useAiCoverLetter = true;
    public array $answers = [];
    public string $salaryExpectation = '';
    public string $availability = 'immediately';
    public bool $willingToRelocate = false;
    
    // State
    public bool $isSubmitting = false;
    public bool $isGeneratingCoverLetter = false;
    public ?string $error = null;
    public ?string $success = null;
    
    protected $listeners = [
        'open-quick-apply' => 'openForJob',
        'close-quick-apply' => 'close',
    ];
    
    public function mount(?int $jobId = null): void
    {
        if ($jobId) {
            $this->openForJob($jobId);
        }
    }
    
    public function openForJob(int $jobId): void
    {
        $this->reset(['step', 'error', 'success', 'coverLetter', 'answers']);
        
        $this->jobId = $jobId;
        $this->job = Job::with(['company', 'screeningQuestions'])->find($jobId);
        
        if (!$this->job) {
            $this->error = 'Job not found';
            return;
        }
        
        // Check if already applied
        if ($this->hasAlreadyApplied()) {
            $this->error = 'You have already applied to this job';
            return;
        }
        
        // Pre-select primary resume
        $user = Auth::user();
        $primaryResume = $user->resumes()->where('is_primary', true)->first();
        $this->selectedResumeId = $primaryResume?->id;
        
        $this->isOpen = true;
        $this->step = 1;
    }
    
    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['job', 'jobId', 'step', 'error', 'success']);
    }
    
    public function nextStep(): void
    {
        $this->validateCurrentStep();
        
        if ($this->error) {
            return;
        }
        
        $totalSteps = $this->getTotalSteps();
        
        if ($this->step < $totalSteps) {
            $this->step++;
        }
    }
    
    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }
    
    public function selectResume(int $resumeId): void
    {
        $this->selectedResumeId = $resumeId;
    }
    
    public function generateAiCoverLetter(): void
    {
        if (!$this->job || !$this->selectedResumeId) {
            return;
        }
        
        $this->isGeneratingCoverLetter = true;
        
        try {
            $resume = Resume::find($this->selectedResumeId);
            
            // Use AI service to generate cover letter
            $aiService = app(\App\Services\AI\AIService::class);
            
            $prompt = $this->buildCoverLetterPrompt($resume);
            $this->coverLetter = $aiService->generateText($prompt, 'cover_letter');
            
        } catch (\Exception $e) {
            Log::error('Failed to generate AI cover letter', [
                'error' => $e->getMessage(),
                'job_id' => $this->jobId,
            ]);
            
            $this->error = 'Failed to generate cover letter. Please write one manually.';
        } finally {
            $this->isGeneratingCoverLetter = false;
        }
    }
    
    public function submit(): void
    {
        $this->validateCurrentStep();
        
        if ($this->error) {
            return;
        }
        
        $this->isSubmitting = true;
        $this->error = null;
        
        try {
            $user = Auth::user();
            
            // Create application
            $application = Application::create([
                'user_id' => $user->id,
                'job_id' => $this->jobId,
                'resume_id' => $this->selectedResumeId,
                'cover_letter' => $this->coverLetter,
                'status' => 'submitted',
                'applied_at' => now(),
                'source' => 'quick_apply',
                'metadata' => [
                    'salary_expectation' => $this->salaryExpectation,
                    'availability' => $this->availability,
                    'willing_to_relocate' => $this->willingToRelocate,
                    'screening_answers' => $this->answers,
                    'applied_via' => 'mobile_pwa',
                ],
            ]);
            
            // Save screening question answers
            if (!empty($this->answers)) {
                foreach ($this->answers as $questionId => $answer) {
                    $application->screeningAnswers()->create([
                        'screening_question_id' => $questionId,
                        'answer' => $answer,
                    ]);
                }
            }
            
            // Dispatch events
            event(new \App\Events\ApplicationSubmitted($application));
            
            $this->success = 'Application submitted successfully!';
            
            // Close after delay
            $this->dispatch('application-submitted', jobId: $this->jobId);
            
        } catch (\Exception $e) {
            Log::error('Quick apply failed', [
                'error' => $e->getMessage(),
                'job_id' => $this->jobId,
                'user_id' => Auth::id(),
            ]);
            
            $this->error = 'Failed to submit application. Please try again.';
        } finally {
            $this->isSubmitting = false;
        }
    }
    
    public function hasAlreadyApplied(): bool
    {
        return Application::where('user_id', Auth::id())
            ->where('job_id', $this->jobId)
            ->exists();
    }
    
    public function getTotalSteps(): int
    {
        $steps = 2; // Resume + Review
        
        if ($this->job?->requires_cover_letter) {
            $steps++;
        }
        
        if ($this->job?->screeningQuestions->count() > 0) {
            $steps++;
        }
        
        return $steps;
    }
    
    public function getCurrentStepName(): string
    {
        $stepNames = ['Resume', 'Cover Letter', 'Questions', 'Review'];
        
        $steps = ['Resume'];
        
        if ($this->job?->requires_cover_letter) {
            $steps[] = 'Cover Letter';
        }
        
        if ($this->job?->screeningQuestions->count() > 0) {
            $steps[] = 'Questions';
        }
        
        $steps[] = 'Review';
        
        return $steps[$this->step - 1] ?? 'Unknown';
    }
    
    protected function validateCurrentStep(): void
    {
        $this->error = null;
        
        switch ($this->getCurrentStepName()) {
            case 'Resume':
                if (!$this->selectedResumeId) {
                    $this->error = 'Please select a resume';
                }
                break;
                
            case 'Cover Letter':
                if ($this->job?->requires_cover_letter && empty($this->coverLetter)) {
                    $this->error = 'Please provide a cover letter';
                }
                break;
                
            case 'Questions':
                foreach ($this->job->screeningQuestions as $question) {
                    if ($question->is_required && empty($this->answers[$question->id])) {
                        $this->error = 'Please answer all required questions';
                        break;
                    }
                }
                break;
        }
    }
    
    protected function buildCoverLetterPrompt(Resume $resume): string
    {
        $jobTitle = $this->job->title;
        $company = $this->job->company->name;
        $description = $this->job->description;
        $skills = $resume->extracted_skills ?? [];
        $experience = $resume->work_experience ?? [];
        
        return <<<PROMPT
Generate a professional, personalized cover letter for the following job application:

Job Title: {$jobTitle}
Company: {$company}
Job Description: {$description}

Candidate Skills: {implode(', ', $skills)}
Recent Experience: {json_encode($experience)}

Requirements:
- Keep it concise (250-350 words)
- Highlight relevant skills and experience
- Show enthusiasm for the role and company
- Include a strong opening and closing
- Be professional but personable
- Do not include placeholder text
PROMPT;
    }
    
    public function getResumes(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->resumes()->orderByDesc('is_primary')->get();
    }
    
    public function render()
    {
        return view('livewire.mobile.quick-apply', [
            'resumes' => $this->getResumes(),
            'totalSteps' => $this->getTotalSteps(),
            'currentStepName' => $this->getCurrentStepName(),
        ]);
    }
}
