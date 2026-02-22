<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    protected $insightsService;
    
    public function __construct(CompanyInsightsService $insightsService)
    {
        $this->insightsService = $insightsService;
    }
    
    /**
     * Display a listing of companies
     */
    public function index(Request $request)
    {
        $query = Company::query();
        
        // Search filter
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        // Industry filter
        if ($request->filled('industry')) {
            $query->where('industry', $request->industry);
        }
        
        // Size filter
        if ($request->filled('size')) {
            $query->where('company_size', $request->size);
        }
        
        // Featured/verified filters
        if ($request->boolean('verified')) {
            $query->where('is_verified', true);
        }
        
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        
        // Active hiring filter
        if ($request->boolean('hiring')) {
            $query->has('jobs', '>', 0);
        }
        
        // Sorting
        $sortBy = $request->input('sort', 'name');
        $sortOrder = $request->input('order', 'asc');
        
        switch ($sortBy) {
            case 'jobs':
                $query->withCount(['jobs as active_jobs_count' => function($q) {
                    $q->where('status', 'active');
                }])->orderBy('active_jobs_count', $sortOrder);
                break;
            case 'rating':
                $query->orderBy('culture_rating', $sortOrder);
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }
        
        $companies = $query->withCount([
            'jobs as active_jobs_count' => function($q) {
                $q->where('status', 'active');
            }
        ])->paginate(20);
        
        // Get available industries for filter
        $industries = Company::select('industry')
            ->distinct()
            ->whereNotNull('industry')
            ->orderBy('industry')
            ->pluck('industry');
        
        return view('companies.index', compact('companies', 'industries'));
    }
    
    /**
     * Display the specified company
     */
    public function show($slug)
    {
        $company = Company::where('slug', $slug)
            ->with(['jobs' => function($q) {
                $q->where('status', 'active')
                  ->latest()
                  ->limit(10);
            }])
            ->firstOrFail();
        
        // Get AI-generated insights (cached)
        $insights = $this->insightsService->generateInsights($company);
        
        // Get employee reviews if available
        $reviews = $company->reviews()
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(10);
        
        // Calculate sentiment from reviews
        $sentiment = $this->insightsService->analyzeSentiment($reviews);
        
        // Get active jobs
        $activeJobs = $company->jobs()
            ->where('status', 'active')
            ->with('company:id,name,logo')
            ->latest()
            ->get();
        
        // Get job statistics
        $jobStats = [
            'total' => $activeJobs->count(),
            'by_type' => $activeJobs->groupBy('employment_type')->map->count(),
            'by_level' => $activeJobs->groupBy('experience_level')->map->count(),
            'by_mode' => $activeJobs->groupBy('work_mode')->map->count(),
        ];
        
        // Get similar companies
        $similarCompanies = $this->getSimilarCompanies($company);
        
        // Check if user is following this company
        $isFollowing = auth()->check() 
            ? auth()->user()->followedCompanies()->where('company_id', $company->id)->exists()
            : false;
        
        // Get follower count
        $followersCount = $company->followers()->count();
        
        return view('companies.show', [
            'company' => $company,
            'insights' => $insights,
            'sentiment' => $sentiment,
            'activeJobs' => $activeJobs,
            'jobStats' => $jobStats,
            'benefits' => $company->benefits ?? [],
            'techStack' => $company->tech_stack ?? [],
            'reviews' => $reviews,
            'similarCompanies' => $similarCompanies,
            'isFollowing' => $isFollowing,
            'followersCount' => $followersCount,
        ]);
    }
    
    /**
     * Follow a company
     */
    public function follow(Company $company)
    {
        $user = auth()->user();
        
        if ($user->followedCompanies()->where('company_id', $company->id)->exists()) {
            return response()->json([
                'message' => 'Already following this company',
                'is_following' => true,
            ]);
        }
        
        $user->followedCompanies()->attach($company->id, [
            'created_at' => now(),
        ]);
        
        return response()->json([
            'message' => 'Successfully followed ' . $company->name,
            'is_following' => true,
            'followers_count' => $company->followers()->count(),
        ]);
    }
    
    /**
     * Unfollow a company
     */
    public function unfollow(Company $company)
    {
        $user = auth()->user();
        
        $user->followedCompanies()->detach($company->id);
        
        return response()->json([
            'message' => 'Unfollowed ' . $company->name,
            'is_following' => false,
            'followers_count' => $company->followers()->count(),
        ]);
    }
    
    /**
     * Get company reviews
     */
    public function reviews($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        
        $reviews = $company->reviews()
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(20);
        
        // Calculate rating breakdown
        $ratingBreakdown = $company->reviews()
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get()
            ->pluck('count', 'rating');
        
        // Average rating
        $averageRating = $company->reviews()->avg('rating');
        
        return view('companies.reviews', [
            'company' => $company,
            'reviews' => $reviews,
            'ratingBreakdown' => $ratingBreakdown,
            'averageRating' => round($averageRating, 1),
            'totalReviews' => $company->reviews()->count(),
        ]);
    }
    
    /**
     * Show review form
     */
    public function createReview($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        
        // Check if user already reviewed
        $existingReview = $company->reviews()
            ->where('user_id', auth()->id())
            ->first();
        
        if ($existingReview) {
            return redirect()
                ->route('companies.show', $company->slug)
                ->with('error', 'You have already reviewed this company');
        }
        
        return view('companies.review-form', compact('company'));
    }
    
    /**
     * Store a review
     */
    public function storeReview(Request $request, $slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|min:50|max:5000',
            'position' => 'required|string|max:255',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'pros' => 'nullable|string|max:2000',
            'cons' => 'nullable|string|max:2000',
            'advice_to_management' => 'nullable|string|max:2000',
        ]);
        
        // Check for existing review
        if ($company->reviews()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'You have already reviewed this company');
        }
        
        $review = $company->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'review_text' => $validated['review_text'],
            'position' => $validated['position'],
            'employment_type' => $validated['employment_type'],
            'pros' => $validated['pros'] ?? null,
            'cons' => $validated['cons'] ?? null,
            'advice_to_management' => $validated['advice_to_management'] ?? null,
            'is_verified' => false,
            'helpful_count' => 0,
        ]);
        
        // Update company's average rating
        $this->updateCompanyRating($company);
        
        return redirect()
            ->route('companies.show', $company->slug)
            ->with('success', 'Thank you for your review!');
    }
    
    /**
     * Mark review as helpful
     */
    public function markReviewHelpful($reviewId)
    {
        $review = \App\Models\CompanyReview::findOrFail($reviewId);
        
        // Check if user already marked as helpful
        $alreadyMarked = DB::table('review_helpful')
            ->where('review_id', $reviewId)
            ->where('user_id', auth()->id())
            ->exists();
        
        if ($alreadyMarked) {
            return response()->json([
                'message' => 'Already marked as helpful',
            ], 400);
        }
        
        // Mark as helpful
        DB::table('review_helpful')->insert([
            'review_id' => $reviewId,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
        
        $review->increment('helpful_count');
        
        return response()->json([
            'message' => 'Marked as helpful',
            'helpful_count' => $review->helpful_count,
        ]);
    }
    
    /**
     * Get salary insights for a company
     */
    public function salaryInsights($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        
        // Get salary data from active jobs
        $salaryData = $company->jobs()
            ->where('status', 'active')
            ->whereNotNull('salary_min')
            ->whereNotNull('salary_max')
            ->select('title', 'experience_level', 'salary_min', 'salary_max', 'employment_type')
            ->get();
        
        // Group by experience level
        $byLevel = $salaryData->groupBy('experience_level')->map(function($jobs) {
            return [
                'min' => $jobs->min('salary_min'),
                'max' => $jobs->max('salary_max'),
                'avg_min' => round($jobs->avg('salary_min')),
                'avg_max' => round($jobs->avg('salary_max')),
                'count' => $jobs->count(),
            ];
        });
        
        // Group by job title pattern
        $byRole = $salaryData->groupBy('title')->map(function($jobs, $title) {
            return [
                'title' => $title,
                'min' => $jobs->min('salary_min'),
                'max' => $jobs->max('salary_max'),
                'avg' => round(($jobs->avg('salary_min') + $jobs->avg('salary_max')) / 2),
                'count' => $jobs->count(),
            ];
        })->sortByDesc('avg')->take(10);
        
        return view('companies.salary-insights', [
            'company' => $company,
            'byLevel' => $byLevel,
            'byRole' => $byRole,
            'totalJobs' => $salaryData->count(),
        ]);
    }
    
    /**
     * Compare companies
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'companies' => 'required|array|min:2|max:3',
            'companies.*' => 'exists:companies,id',
        ]);
        
        $companies = Company::whereIn('id', $validated['companies'])
            ->withCount(['jobs as active_jobs_count' => function($q) {
                $q->where('status', 'active');
            }])
            ->get();
        
        if ($companies->count() < 2) {
            return back()->with('error', 'Please select at least 2 companies to compare');
        }
        
        // Get insights for each company
        $comparisons = $companies->map(function($company) {
            return [
                'company' => $company,
                'insights' => $this->insightsService->generateInsights($company),
                'stats' => $this->getCompanyStats($company),
            ];
        });
        
        return view('companies.compare', [
            'comparisons' => $comparisons,
        ]);
    }
    
    /**
     * Get similar companies
     */
    protected function getSimilarCompanies(Company $company, $limit = 6)
    {
        return Company::where('id', '!=', $company->id)
            ->where(function($q) use ($company) {
                $q->where('industry', $company->industry)
                  ->orWhere('company_size', $company->company_size);
            })
            ->where('is_verified', true)
            ->has('jobs', '>', 0)
            ->withCount(['jobs as active_jobs_count' => function($q) {
                $q->where('status', 'active');
            }])
            ->limit($limit)
            ->get();
    }
    
    /**
     * Update company's average rating
     */
    protected function updateCompanyRating(Company $company)
    {
        $averageRating = $company->reviews()->avg('rating');
        $company->update(['culture_rating' => round($averageRating, 2)]);
    }
    
    /**
     * Get company statistics
     */
    protected function getCompanyStats(Company $company)
    {
        return [
            'total_jobs' => $company->jobs()->count(),
            'active_jobs' => $company->jobs()->where('status', 'active')->count(),
            'total_applications' => DB::table('applications')
                ->whereIn('job_id', $company->jobs()->pluck('id'))
                ->count(),
            'avg_salary_min' => $company->jobs()
                ->where('status', 'active')
                ->whereNotNull('salary_min')
                ->avg('salary_min'),
            'avg_salary_max' => $company->jobs()
                ->where('status', 'active')
                ->whereNotNull('salary_max')
                ->avg('salary_max'),
            'remote_jobs_percentage' => $company->jobs()->where('status', 'active')->count() > 0
                ? round(($company->jobs()->where('work_mode', 'remote')->count() / $company->jobs()->where('status', 'active')->count()) * 100)
                : 0,
            'avg_response_time' => $this->calculateAvgResponseTime($company),
        ];
    }
    
    /**
     * Calculate average response time
     */
    protected function calculateAvgResponseTime(Company $company)
    {
        $applications = DB::table('applications')
            ->whereIn('job_id', $company->jobs()->pluck('id'))
            ->whereNotNull('viewed_at')
            ->whereNotNull('submitted_at')
            ->selectRaw('AVG(DATEDIFF(viewed_at, submitted_at)) as avg_days')
            ->first();
        
        return $applications ? round($applications->avg_days, 1) : null;
    }
}
