<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\InterviewExperience;
use App\Models\SalaryReport;
use App\Services\CompanyReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyReviewController extends Controller
{
    public function __construct(
        protected CompanyReviewService $reviewService
    ) {}

    /**
     * Show company directory/listing
     */
    public function index(Request $request): View
    {
        $query = Company::query()
            ->withCount(['reviews', 'jobs'])
            ->where('total_reviews', '>', 0)
            ->orWhere('is_featured', true);

        // Search filter
        if ($search = $request->get('search')) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Industry filter
        if ($industry = $request->get('industry')) {
            $query->where('industry', $industry);
        }

        // Rating filter
        if ($minRating = $request->get('min_rating')) {
            $query->where('avg_rating', '>=', $minRating);
        }

        // Sorting
        $sortBy = $request->get('sort', 'reviews');
        match ($sortBy) {
            'rating' => $query->orderByDesc('avg_rating'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('total_reviews'),
        };

        $companies = $query->paginate(20);

        // Get top industries for filter
        $industries = Company::select('industry')
            ->distinct()
            ->whereNotNull('industry')
            ->orderBy('industry')
            ->pluck('industry');

        return view('companies.index', [
            'companies' => $companies,
            'industries' => $industries,
            'filters' => $request->only(['search', 'industry', 'min_rating', 'sort']),
        ]);
    }

    /**
     * Show single company overview
     */
    public function show(Company $company): View
    {
        $company->load(['jobs' => fn($q) => $q->where('status', 'active')->latest()->take(5)]);

        $ratingSummary = $this->reviewService->getCompanyRatingSummary($company);
        $salaryStats = $this->reviewService->getSalaryStats($company->id);
        $interviewStats = $this->reviewService->getInterviewStats($company->id);

        $recentReviews = $company->reviews()
            ->approved()
            ->with('user:id,name,avatar')
            ->latest()
            ->take(3)
            ->get();

        return view('companies.show', [
            'company' => $company,
            'ratingSummary' => $ratingSummary,
            'salaryStats' => $salaryStats,
            'interviewStats' => $interviewStats,
            'recentReviews' => $recentReviews,
        ]);
    }

    /**
     * Show company reviews
     */
    public function reviews(Company $company): View
    {
        return view('companies.reviews', [
            'company' => $company,
        ]);
    }

    /**
     * Show company salaries
     */
    public function salaries(Company $company): View
    {
        $salaryStats = $this->reviewService->getSalaryStats($company->id);

        $salaryReports = SalaryReport::forCompany($company->id)
            ->approved()
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get salary by job title
        $salaryByTitle = SalaryReport::forCompany($company->id)
            ->approved()
            ->selectRaw('job_title, AVG(total_compensation) as avg_comp, COUNT(*) as count, MIN(total_compensation) as min_comp, MAX(total_compensation) as max_comp')
            ->groupBy('job_title')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        return view('companies.salaries', [
            'company' => $company,
            'salaryStats' => $salaryStats,
            'salaryReports' => $salaryReports,
            'salaryByTitle' => $salaryByTitle,
        ]);
    }

    /**
     * Show company interview experiences
     */
    public function interviews(Company $company): View
    {
        $interviewStats = $this->reviewService->getInterviewStats($company->id);
        $topQuestions = $this->reviewService->getTopInterviewQuestions($company->id);

        $interviews = InterviewExperience::forCompany($company->id)
            ->approved()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('companies.interviews', [
            'company' => $company,
            'interviewStats' => $interviewStats,
            'topQuestions' => $topQuestions,
            'interviews' => $interviews,
        ]);
    }

    /**
     * Show company jobs
     */
    public function jobs(Company $company): View
    {
        $jobs = $company->jobs()
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return view('companies.jobs', [
            'company' => $company,
            'jobs' => $jobs,
        ]);
    }

    /**
     * Show create review form
     */
    public function createReview(Company $company): View
    {
        return view('companies.create-review', [
            'company' => $company,
        ]);
    }

    /**
     * Show create salary form
     */
    public function createSalary(Company $company): View
    {
        return view('companies.create-salary', [
            'company' => $company,
        ]);
    }

    /**
     * Show create interview experience form
     */
    public function createInterview(Company $company): View
    {
        return view('companies.create-interview', [
            'company' => $company,
        ]);
    }

    /**
     * Toggle follow company
     */
    public function toggleFollow(Company $company): RedirectResponse
    {
        $user = auth()->user();

        if ($company->isFollowedBy($user)) {
            $company->followers()->detach($user->id);
            $company->decrement('follower_count');
            $message = "You unfollowed {$company->name}";
        } else {
            $company->followers()->attach($user->id);
            $company->increment('follower_count');
            $message = "You are now following {$company->name}";
        }

        return back()->with('success', $message);
    }
}
