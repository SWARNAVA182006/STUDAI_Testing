<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Application;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Services\AI\JobMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected $jobMatchingService;

    public function __construct(JobMatchingService $jobMatchingService)
    {
        $this->jobMatchingService = $jobMatchingService;
    }

    /**
     * Display the user dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get or create user subscription
        $freePlan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'description' => 'Free plan',
                'price' => 0,
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'features' => [],
                'is_active' => true,
            ]
        );

        $subscription = UserSubscription::firstOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_plan_id' => $freePlan->id,
                'status' => 'active',
                'starts_at' => now(),
            ]
        );

        // Get recent applications (last 5)
        $recentApplications = Application::where('user_id', $user->id)
            ->with(['job.company'])
            ->latest()
            ->take(5)
            ->get();

        // Get application statistics
        $applicationStats = [
            'total' => Application::where('user_id', $user->id)->count(),
            'pending' => Application::where('user_id', $user->id)->where('status', 'pending')->count(),
            'reviewing' => Application::where('user_id', $user->id)->where('status', 'reviewing')->count(),
            'shortlisted' => Application::where('user_id', $user->id)->where('status', 'shortlisted')->count(),
            'rejected' => Application::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        // Get profile completion percentage
        $profileCompletion = $this->calculateProfileCompletion($user);

        // Get job recommendations (cached for 1 hour)
        $recommendedJobs = Cache::remember(
            "job_recommendations_{$user->id}",
            3600,
            function () use ($user) {
                try {
                    $recommendations = $this->jobMatchingService->getRecommendations($user, [], 6);
                    return collect($recommendations)->pluck('job');
                } catch (\Exception $e) {
                    // Fallback to recent jobs if AI matching fails
                    return Job::where('status', 'published')
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->take(6)
                        ->get();
                }
            }
        );

        // Get saved jobs count
        $savedJobsCount = $user->savedJobs()->count();

        // Get subscription usage stats
        $subscriptionStats = [
            'applications_remaining' => $user->getRemainingApplications(),
            'ai_credits_remaining' => $user->getRemainingAICredits(),
            'plan_name' => $subscription->subscriptionPlan->name ?? 'Free',
            'billing_period' => $subscription->subscriptionPlan->billing_period ?? 'monthly',
            'next_billing_date' => $subscription->next_billing_date,
        ];

        return view('dashboard.index', compact(
            'user',
            'subscription',
            'recentApplications',
            'applicationStats',
            'profileCompletion',
            'recommendedJobs',
            'savedJobsCount',
            'subscriptionStats'
        ));
    }

    /**
     * Calculate profile completion percentage
     */
    private function calculateProfileCompletion($user): int
    {
        $profile = $user->profile;
        if (!$profile) {
            return 0;
        }

        $completionFactors = [
            'basic_info' => !empty($user->name) && !empty($user->email) ? 10 : 0,
            'bio' => !empty($profile->bio) ? 10 : 0,
            'phone' => !empty($profile->phone) ? 5 : 0,
            'location' => !empty($profile->location) ? 5 : 0,
            'education' => !empty($profile->education) ? 20 : 0,
            'experience' => !empty($profile->experience) ? 20 : 0,
            'skills' => !empty($profile->skills) ? 15 : 0,
            'resume' => !empty($profile->resume_path) ? 10 : 0,
            'preferences' => !empty($profile->job_preferences) ? 5 : 0,
        ];

        return array_sum($completionFactors);
    }

    /**
     * Display application tracking page
     */
    public function applications(Request $request)
    {
        $user = $request->user();
        
        // Get filter parameters
        $status = $request->get('status');
        $search = $request->get('search');

        // Build query
        $query = Application::where('user_id', $user->id)
            ->with(['job.company', 'job']);

        // Apply status filter
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->whereHas('job', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $applications = $query->latest()->paginate(20);

        // Get status counts for filter badges
        $statusCounts = [
            'all' => Application::where('user_id', $user->id)->count(),
            'pending' => Application::where('user_id', $user->id)->where('status', 'pending')->count(),
            'reviewing' => Application::where('user_id', $user->id)->where('status', 'reviewing')->count(),
            'shortlisted' => Application::where('user_id', $user->id)->where('status', 'shortlisted')->count(),
            'rejected' => Application::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        return view('dashboard.applications', compact('applications', 'statusCounts', 'status', 'search'));
    }
}
