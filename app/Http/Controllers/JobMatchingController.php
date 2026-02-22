<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\SavedJob;
use App\Models\Application;
use App\Services\AI\JobMatchingService;
use App\Services\AI\ResumeAnalyzerService;
use App\Services\AI\CoverLetterGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobMatchingController extends Controller
{
    public function __construct(
        protected JobMatchingService $jobMatchingService,
        protected ResumeAnalyzerService $resumeAnalyzer,
        protected CoverLetterGeneratorService $coverLetterGenerator
    ) {}

    /**
     * Get AI-powered job recommendations for the authenticated user
     * 
     * GET /api/jobs/recommended?limit=20&min_score=50
     */
    public function recommended(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Please complete your profile to get personalized recommendations',
                'action' => 'complete_profile'
            ], 422);
        }

        $limit = min($request->input('limit', 20), 50);
        $minScore = $request->input('min_score', 40); // Minimum match percentage

        // Cache recommendations for 1 hour per user
        $cacheKey = "job_recommendations_{$user->id}_{$limit}_{$minScore}";
        
        $recommendations = Cache::remember($cacheKey, 3600, function () use ($profile, $limit, $minScore) {
            // Get active jobs
            $jobs = Job::with('company')->active()->get();
            
            $matchedJobs = [];
            
            foreach ($jobs as $job) {
                // Calculate match score using AI service
                $matchData = $this->jobMatchingService->calculateMatchScore($profile, $job);
                
                if ($matchData['overall_score'] >= $minScore) {
                    $matchedJobs[] = [
                        'job' => $job,
                        'match_score' => $matchData['overall_score'],
                        'match_analysis' => $matchData,
                    ];
                }
            }
            
            // Sort by match score descending
            usort($matchedJobs, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
            
            return array_slice($matchedJobs, 0, $limit);
        });

        return response()->json([
            'recommendations' => $recommendations,
            'total_count' => count($recommendations),
            'profile_completeness' => $profile->completeness_percentage,
        ]);
    }

    /**
     * Search jobs with semantic + keyword search
     * 
     * GET /api/jobs/search?q=frontend developer&location=remote&type=full-time&min_salary=80000
     */
    public function search(Request $request)
    {
        $query = Job::query()->with('company')->active();

        // Keyword search (full-text)
        if ($keywords = $request->input('q')) {
            $query->where(function ($q) use ($keywords) {
                $q->whereRaw('MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)', [$keywords])
                  ->orWhere('title', 'like', "%{$keywords}%")
                  ->orWhere('description', 'like', "%{$keywords}%");
            });
        }

        // Location filter
        if ($location = $request->input('location')) {
            if (strtolower($location) === 'remote') {
                $query->where('location_type', 'remote');
            } else {
                $query->where('location', 'like', "%{$location}%");
            }
        }

        // Location type filter
        if ($locationType = $request->input('location_type')) {
            $query->where('location_type', $locationType);
        }

        // Employment type filter
        if ($employmentType = $request->input('employment_type')) {
            $query->where('employment_type', $employmentType);
        }

        // Experience level filter
        if ($experienceLevel = $request->input('experience_level')) {
            $query->where('experience_level', $experienceLevel);
        }

        // Salary range filter
        if ($minSalary = $request->input('min_salary')) {
            $query->where(function ($q) use ($minSalary) {
                $q->where('salary_max', '>=', $minSalary)
                  ->orWhereNull('salary_max');
            });
        }

        // Skills filter (JSON contains)
        if ($skills = $request->input('skills')) {
            $skillsArray = is_array($skills) ? $skills : explode(',', $skills);
            foreach ($skillsArray as $skill) {
                $query->whereJsonContains('required_skills', trim($skill));
            }
        }

        // Featured jobs first
        if ($request->input('featured_first', false)) {
            $query->orderBy('is_featured', 'desc');
        }

        // Sorting
        $sortBy = $request->input('sort', 'relevance');
        switch ($sortBy) {
            case 'date':
                $query->orderBy('published_at', 'desc');
                break;
            case 'salary_high':
                $query->orderByRaw('COALESCE(salary_max, 0) DESC');
                break;
            case 'salary_low':
                $query->orderByRaw('COALESCE(salary_min, 999999) ASC');
                break;
            case 'applications':
                $query->orderBy('applications_count', 'desc');
                break;
            default: // relevance
                $query->orderBy('is_featured', 'desc')
                      ->orderBy('published_at', 'desc');
        }

        $jobs = $query->paginate($request->input('per_page', 20));

        // Calculate match scores for authenticated users
        if ($user = $request->user()) {
            $profile = $user->profile;
            if ($profile) {
                $jobs->getCollection()->transform(function ($job) use ($profile) {
                    $matchData = $this->jobMatchingService->calculateMatchScore($profile, $job);
                    $job->match_score = $matchData['overall_score'];
                    $job->match_analysis = $matchData;
                    return $job;
                });
            }
        }

        return response()->json($jobs);
    }

    /**
     * Save/bookmark a job for later
     * 
     * POST /api/jobs/{job}/save
     */
    public function save(Job $job, Request $request)
    {
        $user = $request->user();

        $savedJob = SavedJob::firstOrCreate(
            ['user_id' => $user->id, 'job_id' => $job->id],
            ['notes' => $request->input('notes')]
        );

        if ($savedJob->wasRecentlyCreated) {
            $job->increment('saves_count');
            
            return response()->json([
                'message' => 'Job saved successfully',
                'saved_job' => $savedJob
            ]);
        }

        return response()->json([
            'message' => 'Job already saved',
            'saved_job' => $savedJob
        ]);
    }

    /**
     * Remove a saved job
     * 
     * DELETE /api/jobs/{job}/unsave
     */
    public function unsave(Job $job, Request $request)
    {
        $user = $request->user();

        $deleted = SavedJob::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->delete();

        if ($deleted) {
            $job->decrement('saves_count');
            
            return response()->json([
                'message' => 'Job removed from saved jobs'
            ]);
        }

        return response()->json([
            'message' => 'Job was not saved'
        ], 404);
    }

    /**
     * Get user's saved jobs
     * 
     * GET /api/jobs/saved
     */
    public function saved(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $savedJobs = SavedJob::with(['job.company'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($request->input('per_page', 20));

        // Calculate fresh match scores
        if ($profile) {
            $savedJobs->getCollection()->transform(function ($savedJob) use ($profile) {
                if ($savedJob->job) {
                    $matchData = $this->jobMatchingService->calculateMatchScore($profile, $savedJob->job);
                    $savedJob->match_score = $matchData['overall_score'];
                    $savedJob->match_analysis = $matchData;
                }
                return $savedJob;
            });
        }

        return response()->json($savedJobs);
    }

    /**
     * One-click apply to a job with AI-generated resume and cover letter
     * 
     * POST /api/jobs/{job}/apply
     */
    public function apply(Job $job, Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Please complete your profile before applying',
                'action' => 'complete_profile'
            ], 422);
        }

        // Check if already applied
        $existingApplication = Application::where('job_id', $job->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingApplication) {
            return response()->json([
                'message' => 'You have already applied to this job',
                'application' => $existingApplication
            ], 409);
        }

        // Check subscription limits
        if (!$user->canApplyToJobs()) {
            return response()->json([
                'message' => 'You have reached your monthly application limit',
                'action' => 'upgrade_subscription',
                'limit' => $user->subscription->applications_limit_per_month ?? 0,
                'used' => $user->subscription->applications_used_this_month ?? 0
            ], 403);
        }

        // Check AI credits for custom resume/cover letter generation
        $useAI = $request->input('use_ai', true);
        if ($useAI && !$user->hasAICredits(2)) { // 1 for resume, 1 for cover letter
            return response()->json([
                'message' => 'Insufficient AI credits for custom resume and cover letter generation',
                'action' => 'upgrade_subscription',
                'credits_remaining' => $user->getRemainingAICredits()
            ], 403);
        }

        DB::beginTransaction();
        try {
            $customResume = null;
            $customCoverLetter = null;

            if ($useAI) {
                // Generate AI-optimized resume
                $customResume = $this->resumeAnalyzer->optimizeForJob($profile, $job);
                $user->deductAICredits(1);

                // Generate AI-powered cover letter
                $customCoverLetter = $this->coverLetterGenerator->generate($profile, $job);
                $user->deductAICredits(1);
            }

            // Create application
            $application = Application::create([
                'user_id' => $user->id,
                'job_id' => $job->id,
                'status' => 'submitted',
                'custom_resume' => $customResume,
                'custom_cover_letter' => $customCoverLetter,
                'applied_at' => now(),
            ]);

            // Increment counters
            $job->increment('applications_count');
            $user->subscription?->increment('applications_used_this_month');

            DB::commit();

            return response()->json([
                'message' => 'Application submitted successfully',
                'application' => $application->load('job.company'),
                'ai_generated' => $useAI,
                'applications_remaining' => $user->getRemainingApplications(),
                'ai_credits_remaining' => $user->getRemainingAICredits(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to submit application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed match analysis for a specific job
     * 
     * GET /api/jobs/{job}/match-analysis
     */
    public function matchAnalysis(Job $job, Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile required for match analysis',
                'action' => 'complete_profile'
            ], 422);
        }

        $analysis = $this->jobMatchingService->getDetailedMatchAnalysis($profile, $job);
        $skillGaps = $this->jobMatchingService->identifySkillGaps($profile, $job);

        return response()->json([
            'job' => $job->load('company'),
            'match_analysis' => $analysis,
            'skill_gaps' => $skillGaps,
            'recommendation' => $this->generateRecommendation($analysis)
        ]);
    }

    /**
     * Generate textual recommendation based on match analysis
     */
    protected function generateRecommendation(array $analysis): string
    {
        $score = $analysis['overall_score'];

        if ($score >= 80) {
            return "Excellent match! You meet most requirements and should definitely apply.";
        } elseif ($score >= 60) {
            return "Good match. You have relevant skills but may want to highlight specific experiences in your application.";
        } elseif ($score >= 40) {
            return "Moderate match. Consider applying if you're passionate about the role and willing to learn.";
        } else {
            return "This role may be challenging. Consider upskilling in the identified gap areas before applying.";
        }
    }
}

