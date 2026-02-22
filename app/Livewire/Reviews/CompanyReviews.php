<?php

declare(strict_types=1);

namespace App\Livewire\Reviews;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Services\CompanyReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyReviews extends Component
{
    use WithPagination;

    public Company $company;

    #[Url]
    public string $sort = 'recent';

    #[Url]
    public ?int $rating = null;

    #[Url]
    public ?string $employmentStatus = null;

    #[Url]
    public bool $currentOnly = false;

    public ?string $department = null;

    protected CompanyReviewService $reviewService;

    public function boot(CompanyReviewService $reviewService): void
    {
        $this->reviewService = $reviewService;
    }

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    #[Computed]
    public function reviews()
    {
        return $this->reviewService->getReviews(
            $this->company->id,
            [
                'sort' => $this->sort,
                'rating' => $this->rating,
                'employment_status' => $this->employmentStatus,
                'is_current' => $this->currentOnly,
                'department' => $this->department,
            ],
            10
        );
    }

    #[Computed]
    public function ratingSummary(): array
    {
        return $this->reviewService->getCompanyRatingSummary($this->company);
    }

    #[Computed]
    public function departments(): array
    {
        return CompanyReview::forCompany($this->company->id)
            ->approved()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->toArray();
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
        $this->resetPage();
    }

    public function setRating(?int $rating): void
    {
        $this->rating = $this->rating === $rating ? null : $rating;
        $this->resetPage();
    }

    public function toggleCurrentOnly(): void
    {
        $this->currentOnly = !$this->currentOnly;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['rating', 'employmentStatus', 'currentOnly', 'department']);
        $this->resetPage();
    }

    public function markHelpful(int $reviewId): void
    {
        if (!auth()->check()) {
            $this->dispatch('show-login-modal');
            return;
        }

        $review = CompanyReview::findOrFail($reviewId);
        $review->markHelpful(auth()->user());

        $this->dispatch('review-voted');
    }

    public function markNotHelpful(int $reviewId): void
    {
        if (!auth()->check()) {
            $this->dispatch('show-login-modal');
            return;
        }

        $review = CompanyReview::findOrFail($reviewId);
        $review->markNotHelpful(auth()->user());

        $this->dispatch('review-voted');
    }

    public function render(): View
    {
        return view('livewire.reviews.company-reviews');
    }
}
