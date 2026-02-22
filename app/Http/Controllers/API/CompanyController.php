<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Get company profile
     */
    public function show(Request $request)
    {
        $company = $request->input('api_company');
        
        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }
    
    /**
     * Update company profile
     */
    public function update(Request $request)
    {
        $company = $request->input('api_company');
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'industry' => 'nullable|string',
            'website' => 'nullable|url',
            'location' => 'nullable|string',
            'size' => 'nullable|string',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
        ]);
        
        $company->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Company profile updated',
            'data' => $company->fresh(),
        ]);
    }
    
    /**
     * Get company statistics
     */
    public function statistics(Request $request)
    {
        $company = $request->input('api_company');
        
        $stats = [
            'total_jobs' => $company->jobs()->count(),
            'active_jobs' => $company->jobs()->where('status', 'published')->count(),
            'total_applications' => $company->jobs()->withCount('applications')->get()->sum('applications_count'),
            'new_applications_today' => $company->jobs()
                ->join('applications', 'jobs.id', '=', 'applications.job_id')
                ->whereDate('applications.applied_at', today())
                ->count(),
            'total_hires' => $company->jobs()
                ->join('applications', 'jobs.id', '=', 'applications.job_id')
                ->where('applications.status', 'hired')
                ->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
