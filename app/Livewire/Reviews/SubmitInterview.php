<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Models\Company;
use App\Services\CompanyReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SubmitInterview extends Component
{
    public Company $company;

    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 3;

    // Step 1: Basic Info
    #[Validate('required|string|max:255')]
    public string $jobTitle = '';

    #[Validate('nullable|string|max:255')]
    public ?string $department = null;

    #[Validate('nullable|string|max:255')]
    public ?string $location = null;

    #[Validate('required|in:company_website,job_board,recruiter,referral,campus,linkedin,other')]
    public string $applicationSource = 'job_board';

    #[Validate('nullable|date')]
    public ?string $interviewDate = null;

    // Step 2: Experience Details
    #[Validate('required|integer|min:1|max:5')]
    public int $difficultyRating = 3;

    #[Validate('required|in:positive,neutral,negative')]
    public string $experienceRating = 'neutral';

    #[Validate('nullable|boolean')]
    public ?bool $gotOffer = null;

    #[Validate('nullable|boolean')]
    public ?bool $acceptedOffer = null;

    #[Validate('nullable|integer|min:1|max:52')]
    public ?int $durationWeeks = null;

    #[Validate('array')]
    public array $interviewStages = [];

    // Step 3: Process Details
    #[Validate('required|string|min:100|max:5000')]
    public string $interviewProcess = '';

    #[Validate('array')]
    public array $interviewQuestions = [];

    public string $newQuestion = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $tipsForCandidates = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $whatWentWell = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $whatCouldImprove = null;

    #[Validate('boolean')]
    public bool $isAnonymous = true;

    public bool $isSubmitting = false;
    public bool $submitted = false;

    protected CompanyReviewService $reviewService;

    public const INTERVIEW_STAGES = [
        'phone_screen' => 'Phone Screen',
        'recruiter_call' => 'Recruiter Call',
        'technical_phone' => 'Technical Phone Interview',
        'coding_test' => 'Coding Test/Assessment',
        'take_home' => 'Take-Home Project',
        'onsite' => 'On-site Interview',
        'virtual_onsite' => 'Virtual On-site',
        'panel' => 'Panel Interview',
        'behavioral' => 'Behavioral Interview',
        'case_study' => 'Case Study',
        'presentation' => 'Presentation',
        'hiring_manager' => 'Hiring Manager Interview',
        'team_interview' => 'Team Interview',
        'executive' => 'Executive Interview',
        'offer_negotiation' => 'Offer/Negotiation',
    ];

    public function boot(CompanyReviewService $reviewService): void
    {
        $this->reviewService = $reviewService;
    }

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function nextStep(): void
    {
        $this->validateStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'jobTitle' => 'required|string|max:255',
                'applicationSource' => 'required',
            ]),
            2 => $this->validate([
                'difficultyRating' => 'required|integer|min:1|max:5',
                'experienceRating' => 'required|in:positive,neutral,negative',
            ]),
            default => null,
        };
    }

    public function toggleStage(string $stage): void
    {
        if (in_array($stage, $this->interviewStages)) {
            $this->interviewStages = array_values(array_diff($this->interviewStages, [$stage]));
        } else {
            $this->interviewStages[] = $stage;
        }
    }

    public function addQuestion(): void
    {
        $question = trim($this->newQuestion);
        if ($question && !in_array($question, $this->interviewQuestions)) {
            $this->interviewQuestions[] = $question;
            $this->newQuestion = '';
        }
    }

    public function removeQuestion(int $index): void
    {
        unset($this->interviewQuestions[$index]);
        $this->interviewQuestions = array_values($this->interviewQuestions);
    }

    public function submit(): void
    {
        if (!auth()->check()) {
            $this->dispatch('show-login-modal');
            return;
        }

        $this->validate();

        $this->isSubmitting = true;

        try {
            $this->reviewService->submitInterviewExperience(auth()->user(), $this->company, [
                'job_title' => $this->jobTitle,
                'department' => $this->department,
                'location' => $this->location,
                'application_source' => $this->applicationSource,
                'interview_date' => $this->interviewDate,
                'difficulty_rating' => $this->difficultyRating,
                'experience_rating' => $this->experienceRating,
                'got_offer' => $this->gotOffer,
                'accepted_offer' => $this->acceptedOffer,
                'duration_weeks' => $this->durationWeeks,
                'interview_stages' => $this->interviewStages,
                'interview_process' => $this->interviewProcess,
                'interview_questions' => $this->interviewQuestions,
                'tips_for_candidates' => $this->tipsForCandidates,
                'what_went_well' => $this->whatWentWell,
                'what_could_improve' => $this->whatCouldImprove,
                'is_anonymous' => $this->isAnonymous,
            ]);

            $this->submitted = true;
            $this->dispatch('interview-submitted');

        } catch (\Exception $e) {
            $this->addError('submit', $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.reviews.submit-interview');
    }
}
