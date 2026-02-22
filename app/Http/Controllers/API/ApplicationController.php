<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    /**
     * List applications
     */
    public function index(Request $request)
    {
        $company = $request->input('api_company');
        
        $query = Application::whereHas('job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->with(['user.profile', 'job:id,title,category']);
        
        // Filters
        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        
        if ($request->filled('match_score_min')) {
            $query->where('match_score', '>=', $request->match_score_min);
        }
        
        if ($request->filled('from_date')) {
            $query->whereDate('applied_at', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('applied_at', '<=', $request->to_date);
        }
        
        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $applications = $query->orderByDesc('applied_at')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $applications->items(),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }
    
    /**
     * Get single application
     */
    public function show(Request $request, Application $application)
    {
        $company = $request->input('api_company');
        
        if ($application->job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This application does not belong to your company',
            ], 403);
        }
        
        $application->load([
            'user.profile',
            'job:id,title,category,employment_type',
            'statusHistory',
            'interviews',
            'notes',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }
    
    /**
     * Update application status
     */
    public function updateStatus(Request $request, Application $application)
    {
        $company = $request->input('api_company');
        
        if ($application->job->company_id !== $company->id) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This application does not belong to your company',
            ], 403);
        }
        
        $validated = $request->validate([
            'status' => 'required|in:received,screening,shortlisted,interview_scheduled,interview_completed,offered,hired,rejected,withdrawn',
            'notes' => 'nullable|string',
        ]);
        
        $oldStatus = $application->status;
        $application->update(['status' => $validated['status']]);
        
        // Log status change
        $application->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status' => $validated['status'],
            'changed_by' => null, // API change
            'notes' => $validated['notes'] ?? 'Status updated via API',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Application status updated',
            'data' => $application->fresh(),
        ]);
    }
    
    /**
     * Bulk status update
     */
    public function bulkUpdateStatus(Request $request)
    {
        $company = $request->input('api_company');
        
        $validated = $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
            'status' => 'required|in:received,screening,shortlisted,interview_scheduled,interview_completed,offered,hired,rejected,withdrawn',
            'notes' => 'nullable|string',
        ]);
        
        $applications = Application::whereIn('id', $validated['application_ids'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->get();
        
        if ($applications->count() !== count($validated['application_ids'])) {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Some applications do not belong to your company',
            ], 400);
        }
        
        $updated = 0;
        foreach ($applications as $application) {
            $oldStatus = $application->status;
            $application->update(['status' => $validated['status']]);
            
            $application->statusHistory()->create([
                'from_status' => $oldStatus,
                'to_status' => $validated['status'],
                'changed_by' => null,
                'notes' => $validated['notes'] ?? 'Bulk status update via API',
            ]);
            
            $updated++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "{$updated} applications updated",
        ]);
    }
}
