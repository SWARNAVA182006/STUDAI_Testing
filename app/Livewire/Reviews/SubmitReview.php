<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Models\Company;
use App\Services\CompanyReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SubmitReview extends Component
{
    public Company $company;

    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 4;

    // Step 1: Employment Info
    #[Validate('required|string|max:255')]
    public string $jobTitle = '';

    #[Validate('nullable|string|max:255')]
    public ?string $department = null;

    #[Validate('required|in:full_time,part_time,contract,internship,freelance')]
    public string $employmentStatus = 'full_time';

    #[Validate('boolean')]
    public bool $isCurrentEmployee = false;

    #[Validate('nullable|date')]
    public ?string $startDate = null;

    #[Validate('nullable|date')]
    public ?string $endDate = null;

    // Step 2: Ratings
    #[Validate('required|integer|min:1|max:5')]
    public int $overallRating = 3;

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $cultureRating = null;

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $compensationRating = null;

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $worklifeRating = null;

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $growthRating = null;

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $managementRating = null;

    #[Validate('nullable|boolean')]
    public ?bool $ceoApproval = null;

    #[Validate('nullable|boolean')]
    public ?bool $recommendToFriend = null;

    #[Validate('nullable|in:positive,neutral,negative')]
    public ?string $businessOutlook = null;

    // Step 3: Review Content
    #[Validate('nullable|string|max:255')]
    public ?string $reviewTitle = null;

    #[Validate('required|string|min:50|max:5000')]
    public string $pros = '';

    #[Validate('required|string|min:50|max:5000')]
    public string $cons = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $adviceToManagement = null;

    // Step 4: Privacy
    #[Validate('boolean')]
    public bool $isAnonymous = true;

    #[Validate('nullable|string|max:100')]
    public ?string $displayName = null;

    #[Validate('required|accepted')]
    public bool $agreeToTerms = false;

    public bool $isSubmitting = false;
    public bool $submitted = false;

    protected CompanyReviewService $reviewService;

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

    public function goToStep(int $step): void
    {
        // Only allow going back or to validated steps
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    protected function validateStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'jobTitle' => 'required|string|max:255',
                'employmentStatus' => 'required|in:full_time,part_time,contract,internship,freelance',
            ]),
            2 => $this->validate([
                'overallRating' => 'required|integer|min:1|max:5',
            ]),
            3 => $this->validate([
                'pros' => 'required|string|min:50|max:5000',
                'cons' => 'required|string|min:50|max:5000',
            ]),
            default => null,
        };
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
            $this->reviewService->submitReview(auth()->user(), $this->company, [
                'job_title' => $this->jobTitle,
                'department' => $this->department,
                'employment_status' => $this->employmentStatus,
                'is_current_employee' => $this->isCurrentEmployee,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'overall_rating' => $this->overallRating,
                'culture_rating' => $this->cultureRating,
                'compensation_rating' => $this->compensationRating,
                'worklife_rating' => $this->worklifeRating,
                'growth_rating' => $this->growthRating,
                'management_rating' => $this->managementRating,
                'ceo_approval' => $this->ceoApproval,
                'recommend_to_friend' => $this->recommendToFriend,
                'business_outlook' => $this->businessOutlook,
                'review_title' => $this->reviewTitle,
                'pros' => $this->pros,
                'cons' => $this->cons,
                'advice_to_management' => $this->adviceToManagement,
                'is_anonymous' => $this->isAnonymous,
                'display_name' => $this->displayName,
            ]);

            $this->submitted = true;
            $this->dispatch('review-submitted');

        } catch (\Exception $e) {
            $this->addError('submit', $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.reviews.submit-review');
    }
}
