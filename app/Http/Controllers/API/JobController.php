<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * List jobs
     */
    public function index(Request $request)
    {
        $company = $request->input('api_company');
        
        $query = Job::where('company_id', $company->id)
            ->with(['company:id,name,logo,location']);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }
        
        if ($request->filled('work_mode')) {
            $query->where('location_type', $request->work_mode);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $jobs = $query->orderByDesc('published_at')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }
    
    /**
     * Get single job
     */
    public function show(Request $request, Job $job)
    {
        $company = $request->input('api_company');
        
        if ($job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This job does not belong to your company',
            ], 403);
        }
        
        $job->load(['company:id,name,logo,location,industry,website']);
        
        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }
    
    /**
     * Create job
     */
    public function store(Request $request)
    {
        $company = $request->input('api_company');
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'location' => 'nullable|string',
            'work_mode' => 'required|in:onsite,remote,hybrid',
            'employment_type' => 'required|in:full-time,part-time,contract,internship',
            'experience_level' => 'nullable|in:entry,mid,senior,lead,executive',
            'min_experience' => 'nullable|integer|min:0',
            'preferred_experience' => 'nullable|integer|min:0',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'salary_period' => 'nullable|in:hourly,monthly,yearly',
            'required_skills' => 'nullable|array',
            'preferred_skills' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'application_method' => 'required|in:internal,email,external',
            'application_email' => 'nullable|email',
            'external_url' => 'nullable|url',
            'expires_in_days' => 'nullable|integer|min:7|max:90',
        ]);
        
        $job = Job::create([
            'company_id' => $company->id,
            'posted_by' => auth()->id() ?? $company->id, // Use auth user if available
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays($validated['expires_in_days'] ?? 30),
            'location_type' => $validated['work_mode'],
            ...$validated,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Job created successfully',
            'data' => $job,
        ], 201);
    }
    
    /**
     * Update job
     */
    public function update(Request $request, Job $job)
    {
        $company = $request->input('api_company');
        
        if ($job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This job does not belong to your company',
            ], 403);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category' => 'sometimes|string',
            'location' => 'nullable|string',
            'work_mode' => 'sometimes|in:onsite,remote,hybrid',
            'employment_type' => 'sometimes|in:full-time,part-time,contract,internship',
            'experience_level' => 'nullable|in:entry,mid,senior,lead,executive',
            'status' => 'sometimes|in:draft,published,closed',
            'required_skills' => 'nullable|array',
            'preferred_skills' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
        ]);

        if (isset($validated['work_mode'])) {
            $validated['location_type'] = $validated['work_mode'];
        }
        
        $job->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully',
            'data' => $job->fresh(),
        ]);
    }
    
    /**
     * Delete job
     */
    public function destroy(Request $request, Job $job)
    {
        $company = $request->input('api_company');
        
        if ($job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This job does not belong to your company',
            ], 403);
        }
        
        $job->update(['status' => 'closed']);
        
        return response()->json([
            'success' => true,
            'message' => 'Job closed successfully',
        ]);
    }
    
    /**
     * Get job statistics
     */
    public function statistics(Request $request, Job $job)
    {
        $company = $request->input('api_company');
        
        if ($job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This job does not belong to your company',
            ], 403);
        }
        
        $stats = [
            'total_applications' => $job->applications()->count(),
            'new_applications' => $job->applications()->where('status', 'received')->count(),
            'shortlisted' => $job->applications()->where('status', 'shortlisted')->count(),
            'interviewing' => $job->applications()->whereIn('status', ['interview_scheduled', 'interview_completed'])->count(),
            'hired' => $job->applications()->where('status', 'hired')->count(),
            'rejected' => $job->applications()->where('status', 'rejected')->count(),
            'total_views' => $job->jobViews()->count(),
            'unique_viewers' => $job->jobViews()->distinct('user_id')->count('user_id'),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
