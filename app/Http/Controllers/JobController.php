<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Application;
use App\Services\AI\JobMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;

class JobController extends Controller
{
    protected $jobMatchingService;

    public function __construct(JobMatchingService $jobMatchingService)
    {
        $this->jobMatchingService = $jobMatchingService;
    }

    /**
     * Display job search page with filters
     */
    public function search(Request $request)
    {
        $query = Job::where('status', 'published')
            ->where('expires_at', '>', now());

        // Apply filters
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('company_name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        if ($request->filled('job_type')) {
            $query->whereIn('employment_type', (array)$request->job_type);
        }

        if ($request->filled('salary_min')) {
            $query->where('salary_max', '>=', $request->salary_min * 100000);
        }

        if ($request->filled('skills')) {
            $skills = explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->whereJsonContains('required_skills', trim($skill));
            }
        }

        // Work Mode
        $workModes = [];
        if ($request->filled('remote')) $workModes[] = 'remote';
        if ($request->filled('hybrid')) $workModes[] = 'hybrid';
        if ($request->filled('onsite')) $workModes[] = 'onsite';

        if (!empty($workModes)) {
            $query->whereIn('location_type', $workModes);
        }

        // Sorting
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'salary_high':
                $query->orderBy('salary_max', 'desc');
                break;
            case 'salary_low':
                $query->orderBy('salary_min', 'asc');
                break;
            case 'relevant':
                // AI-based relevance (if user is authenticated)
                if (Auth::check()) {
                    $query->orderBy('created_at', 'desc'); // Placeholder
                }
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
        
        $jobs = $query
            ->with('company')
            ->paginate(20);

        // Get filter options
        $locations = Job::where('status', 'published')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        $experienceLevels = ['entry', 'mid', 'senior', 'lead'];
        $jobTypes = ['full-time', 'part-time', 'contract', 'internship'];

        return view('jobs.search', compact(
            'jobs',
            'locations',
            'experienceLevels',
            'jobTypes'
        ));
    }

    /**
     * Display job details
     */
    public function show($id)
    {
        $job = Job::with('company')->findOrFail($id);
        
        // Check if user has already applied
        $hasApplied = false;
        if (Auth::check()) {
            $hasApplied = Application::where('user_id', Auth::id())
                ->where('job_id', $job->id)
                ->exists();
        }

        // Get similar jobs
        $similarJobs = Cache::remember(
            "similar_jobs_{$job->id}",
            3600,
            function () use ($job) {
                return Job::where('status', 'published')
                    ->where('expires_at', '>', now())
                    ->where('id', '!=', $job->id)
                    ->where(function ($query) use ($job) {
                        $query->where('location', $job->location)
                              ->orWhere('employment_type', $job->employment_type);
                    })
                    ->with('company')
                    ->take(4)
                    ->get();
            }
        );

        return view('jobs.show', compact('job', 'hasApplied', 'similarJobs'));
    }

    /**
     * Save/unsave a job
     */
    public function toggleSave(Request $request, $id)
    {
        $user = $request->user();
        $job = Job::findOrFail($id);

        if ($user->savedJobs()->where('job_id', $id)->exists()) {
            $user->savedJobs()->detach($id);
            return response()->json(['saved' => false, 'message' => 'Job removed from saved jobs']);
        } else {
            $user->savedJobs()->attach($id);
            return response()->json(['saved' => true, 'message' => 'Job saved successfully']);
        }
    }

    /**
     * Display saved jobs
     */
    public function saved(Request $request)
    {
        $user = $request->user();
        $jobs = $user->savedJobs()
            ->where('status', 'published')
            ->where('expires_at', '>', now())
            ->withPivot('notes', 'created_at')
            ->with('company')
            ->orderBy('saved_jobs.created_at', 'desc')
            ->paginate(20);

        return view('jobs.saved', compact('jobs'));
    }

    /**
     * Apply to a job
     */
    public function apply(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['success' => false, 'error' => 'Please login to apply'], 401);
            }
            
            $job = Job::find($id);
            
            if (!$job) {
                return response()->json(['success' => false, 'error' => 'Job not found'], 404);
            }

            // Check if already applied
            if (Application::where('user_id', $user->id)->where('job_id', $id)->exists()) {
                return response()->json(['success' => false, 'error' => 'You have already applied to this job'], 400);
            }

            // Check application limit (allow if no subscription - free tier with 5 apps/month)
            $subscription = $user->subscription;
            if ($subscription && !$user->canApplyToJobs()) {
                return response()->json(['success' => false, 'error' => 'You have reached your monthly application limit. Please upgrade your plan.'], 403);
            }

            $request->validate([
                'cover_letter' => 'nullable|string|max:2000',
                'resume_path' => 'nullable|string',
            ]);

            // Get resume path from profile if not provided
            $resumePath = $request->resume_path;
            if (!$resumePath && $user->profile) {
                $resumePath = $user->profile->resume_path;
            }

            // Create application
            $application = Application::create([
                'user_id' => $user->id,
                'job_id' => $job->id,
                'company_id' => $job->company_id,
                'cover_letter' => $request->cover_letter,
                'resume_path' => $resumePath,
                'status' => 'pending',
                'applied_at' => now(),
            ]);

            // Increment usage counter if subscription exists
            if ($subscription) {
                $subscription->increment('applications_used_this_month');
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application_id' => $application->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Job application error: ' . $e->getMessage(), [
                'job_id' => $id,
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'error' => 'Failed to submit application. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate AI cover letter for job application
     */
    public function generateCoverLetter(Request $request)
    {
        try {
            $user = $request->user();
            
            $request->validate([
                'job_title' => 'required|string',
                'company_name' => 'required|string',
                'job_description' => 'nullable|string',
            ]);

            $userName = $user->name;
            $userProfile = $user->profile;
            
            // Build user context
            $userSkills = '';
            $userExperience = '';
            
            if ($userProfile) {
                if ($userProfile->skills) {
                    $skills = is_array($userProfile->skills) ? $userProfile->skills : json_decode($userProfile->skills, true);
                    $userSkills = $skills ? implode(', ', array_slice($skills, 0, 10)) : '';
                }
                if ($userProfile->experience_years) {
                    $userExperience = $userProfile->experience_years . ' years of experience';
                }
                if ($userProfile->professional_summary) {
                    $userExperience .= '. ' . substr($userProfile->professional_summary, 0, 200);
                }
            }

            $prompt = "Write a professional cover letter for a job application with the following details:

Job Title: {$request->job_title}
Company: {$request->company_name}
Applicant Name: {$userName}
Applicant Skills: {$userSkills}
Applicant Background: {$userExperience}

Job Description Summary: " . substr($request->job_description ?? '', 0, 500) . "

Requirements:
1. Keep it concise (250-350 words)
2. Be professional but personable
3. Highlight relevant skills for the role
4. Show enthusiasm for the company
5. Include a strong opening and closing
6. Do NOT use placeholder text like [Your Name] - use the actual applicant name provided
7. Format with proper paragraphs

Write only the cover letter body, starting with 'Dear Hiring Manager,' and ending with the applicant's name.";

            // Try to use OpenAI
            try {
                $response = OpenAI::chat()->create([
                    'model' => config('ai.default_model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a professional career advisor helping job seekers write compelling cover letters. Write natural, human-sounding letters that are tailored to the specific job and company.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 800,
                    'temperature' => 0.7,
                ]);

                $coverLetter = $response->choices[0]->message->content;

                return response()->json([
                    'success' => true,
                    'cover_letter' => trim($coverLetter),
                ]);
            } catch (\Exception $aiError) {
                \Log::warning('AI cover letter generation failed, using template', [
                    'error' => $aiError->getMessage()
                ]);
                
                // Fallback to a template
                $coverLetter = "Dear Hiring Manager,

I am writing to express my strong interest in the {$request->job_title} position at {$request->company_name}. With my background" . ($userSkills ? " in {$userSkills}" : "") . ($userExperience ? " and {$userExperience}" : "") . ", I am confident that I would be a valuable addition to your team.

I am particularly drawn to this opportunity because of {$request->company_name}'s reputation in the industry and the exciting challenges this role presents. I believe my skills and experience align well with your requirements, and I am eager to contribute to your team's continued success.

Throughout my career, I have developed strong problem-solving abilities and a commitment to delivering high-quality work. I am a quick learner who thrives in collaborative environments and consistently seeks opportunities to grow and take on new challenges.

I would welcome the opportunity to discuss how my background and skills can benefit {$request->company_name}. Thank you for considering my application. I look forward to the possibility of contributing to your team.

Best regards,
{$userName}";

                return response()->json([
                    'success' => true,
                    'cover_letter' => $coverLetter,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Cover letter generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate cover letter'
            ], 500);
        }
    }
}
