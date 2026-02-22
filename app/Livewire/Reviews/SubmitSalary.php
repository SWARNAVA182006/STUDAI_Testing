<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Models\Company;
use App\Services\CompanyReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SubmitSalary extends Component
{
    public Company $company;

    // Step tracking
    public int $currentStep = 1;
    public int $totalSteps = 3;

    // Step 1: Job Info
    #[Validate('required|string|max:255')]
    public string $jobTitle = '';

    #[Validate('nullable|string|max:255')]
    public ?string $department = null;

    #[Validate('required|in:intern,entry,mid,senior,lead,manager,director,vp,c_level')]
    public string $jobLevel = 'mid';

    #[Validate('required|in:full_time,part_time,contract,internship')]
    public string $employmentType = 'full_time';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('boolean')]
    public bool $isRemote = false;

    // Step 2: Compensation
    #[Validate('required|in:USD,EUR,GBP,INR,CAD,AUD,JPY')]
    public string $currency = 'USD';

    #[Validate('required|numeric|min:0')]
    public float $baseSalary = 0;

    #[Validate('nullable|numeric|min:0')]
    public ?float $bonus = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $stockValue = null;

    #[Validate('nullable|numeric|min:0')]
    public ?float $commission = null;

    #[Validate('required|in:yearly,monthly,hourly')]
    public string $payFrequency = 'yearly';

    // Step 3: Experience & Benefits
    #[Validate('required|integer|min:0|max:50')]
    public int $yearsExperience = 0;

    #[Validate('nullable|integer|min:0|max:50')]
    public ?int $yearsAtCompany = null;

    #[Validate('nullable|in:high_school,associate,bachelors,masters,phd,other')]
    public ?string $educationLevel = null;

    #[Validate('array')]
    public array $benefits = [];

    #[Validate('nullable|integer|min:1|max:5')]
    public ?int $satisfactionRating = null;

    public bool $isSubmitting = false;
    public bool $submitted = false;

    protected CompanyReviewService $reviewService;

    public const BENEFIT_OPTIONS = [
        'health_insurance' => 'Health Insurance',
        'dental' => 'Dental',
        'vision' => 'Vision',
        '401k' => '401k/Retirement',
        'stock_options' => 'Stock Options',
        'pto' => 'Paid Time Off',
        'remote_work' => 'Remote Work',
        'flexible_hours' => 'Flexible Hours',
        'gym' => 'Gym/Wellness',
        'education' => 'Education Assistance',
        'parental_leave' => 'Parental Leave',
        'bonus' => 'Performance Bonus',
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
                'jobLevel' => 'required',
                'employmentType' => 'required',
                'location' => 'required|string|max:255',
            ]),
            2 => $this->validate([
                'currency' => 'required',
                'baseSalary' => 'required|numeric|min:1',
            ]),
            default => null,
        };
    }

    public function toggleBenefit(string $benefit): void
    {
        if (in_array($benefit, $this->benefits)) {
            $this->benefits = array_diff($this->benefits, [$benefit]);
        } else {
            $this->benefits[] = $benefit;
        }
    }

    public function getTotalCompensation(): float
    {
        return $this->baseSalary + ($this->bonus ?? 0) + ($this->stockValue ?? 0) + ($this->commission ?? 0);
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
            $this->reviewService->submitSalaryReport(auth()->user(), $this->company, [
                'job_title' => $this->jobTitle,
                'department' => $this->department,
                'job_level' => $this->jobLevel,
                'employment_type' => $this->employmentType,
                'location' => $this->location,
                'is_remote' => $this->isRemote,
                'currency' => $this->currency,
                'base_salary' => $this->baseSalary,
                'bonus' => $this->bonus,
                'stock_value' => $this->stockValue,
                'commission' => $this->commission,
                'pay_frequency' => $this->payFrequency,
                'years_experience' => $this->yearsExperience,
                'years_at_company' => $this->yearsAtCompany,
                'education_level' => $this->educationLevel,
                'benefits' => $this->benefits,
                'satisfaction_rating' => $this->satisfactionRating,
            ]);

            $this->submitted = true;
            $this->dispatch('salary-submitted');

        } catch (\Exception $e) {
            $this->addError('submit', $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render(): View
    {
        return view('livewire.reviews.submit-salary');
    }
}
