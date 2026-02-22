<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\InterviewExperience;
use App\Models\SalaryReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyReviewService
{
    /**
     * Get company reviews with filters and pagination
     */
    public function getReviews(
        int $companyId,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = CompanyReview::query()
            ->forCompany($companyId)
            ->approved()
            ->with(['user:id,name,avatar']);

        // Apply filters
        if (!empty($filters['rating'])) {
            $query->byRating($filters['rating']);
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (!empty($filters['is_current'])) {
            $query->where('is_current_employee', true);
        }

        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'recent';
        match ($sortBy) {
            'helpful' => $query->orderByDesc('helpful_count'),
            'rating_high' => $query->orderByDesc('overall_rating'),
            'rating_low' => $query->orderBy('overall_rating'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Submit a new review
     */
    public function submitReview(User $user, Company $company, array $data): CompanyReview
    {
        // Check if user already reviewed this company recently
        $existingReview = CompanyReview::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->first();

        if ($existingReview) {
            throw new \Exception('You have already submitted a review for this company in the last 6 months.');
        }

        return DB::transaction(function () use ($user, $company, $data) {
            $review = CompanyReview::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'job_title' => $data['job_title'],
                'department' => $data['department'] ?? null,
                'employment_status' => $data['employment_status'],
                'is_current_employee' => $data['is_current_employee'] ?? false,
                'employment_duration' => $data['employment_duration'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'overall_rating' => $data['overall_rating'],
                'culture_rating' => $data['culture_rating'] ?? null,
                'compensation_rating' => $data['compensation_rating'] ?? null,
                'worklife_rating' => $data['worklife_rating'] ?? null,
                'growth_rating' => $data['growth_rating'] ?? null,
                'management_rating' => $data['management_rating'] ?? null,
                'diversity_rating' => $data['diversity_rating'] ?? null,
                'ceo_approval' => $data['ceo_approval'] ?? null,
                'recommend_to_friend' => $data['recommend_to_friend'] ?? null,
                'business_outlook' => $data['business_outlook'] ?? null,
                'review_title' => $data['review_title'] ?? null,
                'pros' => $data['pros'],
                'cons' => $data['cons'],
                'advice_to_management' => $data['advice_to_management'] ?? null,
                'is_anonymous' => $data['is_anonymous'] ?? true,
                'display_name' => $data['display_name'] ?? null,
                'status' => 'pending',
            ]);

            Log::info('Review submitted', [
                'review_id' => $review->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
            ]);

            return $review;
        });
    }

    /**
     * Submit a salary report
     */
    public function submitSalaryReport(User $user, Company $company, array $data): SalaryReport
    {
        return DB::transaction(function () use ($user, $company, $data) {
            $report = SalaryReport::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'job_title' => $data['job_title'],
                'department' => $data['department'] ?? null,
                'job_level' => $data['job_level'],
                'employment_type' => $data['employment_type'],
                'location' => $data['location'],
                'country_code' => $data['country_code'] ?? null,
                'is_remote' => $data['is_remote'] ?? false,
                'currency' => $data['currency'] ?? 'USD',
                'base_salary' => $this->toLowestUnit($data['base_salary']),
                'bonus' => $this->toLowestUnit($data['bonus'] ?? 0),
                'stock_value' => $this->toLowestUnit($data['stock_value'] ?? 0),
                'commission' => $this->toLowestUnit($data['commission'] ?? 0),
                'other_compensation' => $this->toLowestUnit($data['other_compensation'] ?? 0),
                'pay_frequency' => $data['pay_frequency'] ?? 'yearly',
                'years_experience' => $data['years_experience'],
                'years_at_company' => $data['years_at_company'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'benefits' => $data['benefits'] ?? [],
                'satisfaction_rating' => $data['satisfaction_rating'] ?? null,
                'salary_date' => $data['salary_date'] ?? now(),
                'status' => 'pending',
            ]);

            Log::info('Salary report submitted', [
                'report_id' => $report->id,
                'company_id' => $company->id,
            ]);

            return $report;
        });
    }

    /**
     * Submit interview experience
     */
    public function submitInterviewExperience(User $user, Company $company, array $data): InterviewExperience
    {
        return DB::transaction(function () use ($user, $company, $data) {
            $experience = InterviewExperience::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'job_title' => $data['job_title'],
                'department' => $data['department'] ?? null,
                'location' => $data['location'] ?? null,
                'application_method' => $data['application_method'] ?? null,
                'interview_date' => $data['interview_date'] ?? null,
                'interview_duration' => $data['interview_duration'] ?? null,
                'interview_stages' => $data['interview_stages'] ?? [],
                'num_interviews' => $data['num_interviews'] ?? null,
                'experience' => $data['experience'],
                'difficulty' => $data['difficulty'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'accepted_offer' => $data['accepted_offer'] ?? null,
                'offered_salary' => $data['offered_salary'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'interview_process' => $data['interview_process'] ?? null,
                'interview_questions' => $data['interview_questions'] ?? [],
                'preparation_tips' => $data['preparation_tips'] ?? null,
                'advice_for_candidates' => $data['advice_for_candidates'] ?? null,
                'is_anonymous' => $data['is_anonymous'] ?? true,
                'status' => 'pending',
            ]);

            Log::info('Interview experience submitted', [
                'experience_id' => $experience->id,
                'company_id' => $company->id,
            ]);

            return $experience;
        });
    }

    /**
     * Recalculate company ratings
     */
    public function recalculateCompanyRatings(Company $company): void
    {
        $reviews = CompanyReview::forCompany($company->id)
            ->approved()
            ->get();

        if ($reviews->isEmpty()) {
            $company->update([
                'total_reviews' => 0,
                'avg_rating' => null,
                'recommend_percent' => null,
                'ceo_approval_percent' => null,
            ]);
            return;
        }

        $company->update([
            'total_reviews' => $reviews->count(),
            'avg_rating' => round($reviews->avg('rating') ?? $reviews->avg('overall_rating') ?? 0, 2),
            'recommend_percent' => $this->calculateApprovalRate($reviews, 'recommend_to_friend'),
            'ceo_approval_percent' => $this->calculateApprovalRate($reviews, 'ceo_approval'),
        ]);

        // Clear cache
        Cache::forget("company_ratings_{$company->id}");
    }

    /**
     * Get company rating summary
     */
    public function getCompanyRatingSummary(Company $company): array
    {
        return Cache::remember("company_ratings_{$company->id}", 3600, function () use ($company) {
            $reviews = CompanyReview::forCompany($company->id)->approved()->get();
            $salaries = SalaryReport::forCompany($company->id)->approved()->get();
            $interviews = InterviewExperience::forCompany($company->id)->approved()->get();

            return [
                'overall_rating' => $company->avg_rating ?? 0,
                'review_count' => $reviews->count(),
                'ratings_breakdown' => $this->getRatingsBreakdown($reviews),
                'category_ratings' => [
                    'culture' => $this->safeAvg($reviews, 'culture_rating'),
                    'compensation' => $this->safeAvg($reviews, 'compensation_rating'),
                    'work_life_balance' => $this->safeAvg($reviews, 'work_life_balance_rating'),
                    'career_growth' => $this->safeAvg($reviews, 'career_growth_rating'),
                    'management' => $this->safeAvg($reviews, 'management_rating'),
                ],
                'ceo_approval' => $company->ceo_approval_percent,
                'recommend_rate' => $company->recommend_percent,
                'salary_count' => $salaries->count(),
                'interview_count' => $interviews->count(),
                'interview_difficulty' => $this->calculateInterviewDifficulty($interviews),
                'interview_positive_rate' => $interviews->count() > 0
                    ? round($interviews->where('experience', 'positive')->count() / $interviews->count() * 100)
                    : null,
            ];
        });
    }

    /**
     * Calculate average interview difficulty from enum values
     */
    protected function calculateInterviewDifficulty($interviews): ?float
    {
        if ($interviews->isEmpty()) {
            return null;
        }

        $difficultyMap = [
            'easy' => 1,
            'average' => 2,
            'difficult' => 3,
            'very_difficult' => 4,
        ];

        $validInterviews = $interviews->filter(fn($i) => isset($difficultyMap[$i->difficulty]));

        if ($validInterviews->isEmpty()) {
            return null;
        }

        $total = $validInterviews->sum(fn($i) => $difficultyMap[$i->difficulty]);
        return round($total / $validInterviews->count(), 1);
    }

    /**
     * Safely calculate average, returning null if the collection is empty or avg is null
     */
    protected function safeAvg($collection, string $column): ?float
    {
        $filtered = $collection->whereNotNull($column);
        if ($filtered->isEmpty()) {
            return null;
        }
        $avg = $filtered->avg($column);
        return $avg !== null ? round($avg, 1) : null;
    }

    /**
     * Get salary statistics for a job title at a company
     */
    public function getSalaryStats(int $companyId, ?string $jobTitle = null): array
    {
        $query = SalaryReport::forCompany($companyId)->approved();

        if ($jobTitle) {
            $query->forJobTitle($jobTitle);
        }

        $salaries = $query->get();

        if ($salaries->isEmpty()) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'median' => null,
                'average' => null,
            ];
        }

        $totals = $salaries->pluck('total_compensation')->filter()->sort()->values();

        if ($totals->isEmpty()) {
            return [
                'count' => $salaries->count(),
                'min' => null,
                'max' => null,
                'median' => null,
                'average' => null,
                'currency' => $salaries->first()->currency ?? 'USD',
            ];
        }

        return [
            'count' => $salaries->count(),
            'min' => $totals->first(),
            'max' => $totals->last(),
            'median' => $totals->median(),
            'average' => $totals->average() !== null ? round($totals->average()) : null,
            'currency' => $salaries->first()->currency ?? 'USD',
        ];
    }

    /**
     * Get interview statistics for a company
     */
    public function getInterviewStats(int $companyId): array
    {
        $interviews = InterviewExperience::forCompany($companyId)->approved()->get();

        if ($interviews->isEmpty()) {
            return [
                'count' => 0,
                'avg_difficulty' => null,
                'positive_rate' => null,
                'offer_rate' => null,
            ];
        }

        return [
            'count' => $interviews->count(),
            'avg_difficulty' => $this->calculateInterviewDifficulty($interviews),
            'positive_rate' => round($interviews->where('experience', 'positive')->count() / $interviews->count() * 100),
            'neutral_rate' => round($interviews->where('experience', 'neutral')->count() / $interviews->count() * 100),
            'negative_rate' => round($interviews->where('experience', 'negative')->count() / $interviews->count() * 100),
            'offer_rate' => round($interviews->whereIn('outcome', ['got_offer', 'declined_offer'])->count() / $interviews->count() * 100),
        ];
    }

    /**
     * Get top interview questions for a company
     */
    public function getTopInterviewQuestions(int $companyId, int $limit = 10): array
    {
        $interviews = InterviewExperience::forCompany($companyId)
            ->approved()
            ->whereNotNull('interview_questions')
            ->get();

        $allQuestions = $interviews->flatMap(function ($interview) {
            return $interview->interview_questions ?? [];
        });

        // Count occurrences
        $questionCounts = $allQuestions->countBy()->sortDesc()->take($limit);

        return $questionCounts->map(function ($count, $question) {
            return [
                'question' => $question,
                'frequency' => $count,
            ];
        })->values()->all();
    }

    /**
     * Helper: Calculate approval rate
     */
    private function calculateApprovalRate(Collection $reviews, string $field): ?int
    {
        $votedReviews = $reviews->whereNotNull($field);
        if ($votedReviews->isEmpty()) {
            return null;
        }

        return (int) round($votedReviews->where($field, true)->count() / $votedReviews->count() * 100);
    }

    /**
     * Helper: Get ratings breakdown (1-5 stars distribution)
     */
    private function getRatingsBreakdown(Collection $reviews): array
    {
        $breakdown = [];
        $total = $reviews->count();

        for ($i = 5; $i >= 1; $i--) {
            $count = $reviews->where('overall_rating', $i)->count();
            $breakdown[$i] = [
                'count' => $count,
                'percentage' => $total > 0 ? round($count / $total * 100) : 0,
            ];
        }

        return $breakdown;
    }

    /**
     * Helper: Convert dollars to cents
     */
    private function toLowestUnit(float|int|null $amount): int
    {
        if ($amount === null) {
            return 0;
        }
        return (int) round($amount * 100);
    }
}
