<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantTrackingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'employer']);
    }

    public function index(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Application::with(['job', 'user.profile'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by job
        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Search by candidate name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->join('users', 'applications.user_id', '=', 'users.id')
                    ->orderBy('users.name');
                break;
            default:
                $query->latest();
        }

        $applications = $query->paginate(20);

        // Get jobs for filter dropdown
        $jobs = Job::where('company_id', $company->id)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        // Get status counts
        $statusCounts = [
            'all' => Application::whereHas('job', fn($q) => $q->where('company_id', $company->id))->count(),
            'pending' => Application::whereHas('job', fn($q) => $q->where('company_id', $company->id))->where('status', 'pending')->count(),
            'reviewing' => Application::whereHas('job', fn($q) => $q->where('company_id', $company->id))->where('status', 'reviewing')->count(),
            'shortlisted' => Application::whereHas('job', fn($q) => $q->where('company_id', $company->id))->where('status', 'shortlisted')->count(),
            'rejected' => Application::whereHas('job', fn($q) => $q->where('company_id', $company->id))->where('status', 'rejected')->count(),
        ];

        return view('employer.applicants.index', compact('applications', 'jobs', 'statusCounts'));
    }

    public function show($id)
    {
        $company = auth()->user()->company;
        
        $application = Application::with(['job', 'user.profile'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        return view('employer.applicants.show', compact('application'));
    }

    public function updateStatus(Request $request, $id)
    {
        $company = auth()->user()->company;
        
        $application = Application::whereHas('job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewing,shortlisted,rejected,hired'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully!',
            'status' => $application->status
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'application_ids' => ['required', 'array'],
            'application_ids.*' => ['integer', 'exists:applications,id'],
            'status' => ['required', 'in:pending,reviewing,shortlisted,rejected,hired'],
        ]);

        $updated = Application::whereIn('id', $validated['application_ids'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "$updated applications updated successfully!",
        ]);
    }

    public function addNote(Request $request, $id)
    {
        $company = auth()->user()->company;
        
        $application = Application::whereHas('job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $application->update(['notes' => $validated['notes']]);

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully!',
        ]);
    }

    public function kanban(Request $request)
    {
        $company = auth()->user()->company;
        
        $jobId = $request->get('job_id');
        
        $query = Application::with(['user.profile'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        if ($jobId) {
            $query->where('job_id', $jobId);
        }

        $applications = $query->get();

        // Group by status
        $kanbanData = [
            'pending' => $applications->where('status', 'pending'),
            'reviewing' => $applications->where('status', 'reviewing'),
            'shortlisted' => $applications->where('status', 'shortlisted'),
            'rejected' => $applications->where('status', 'rejected'),
            'hired' => $applications->where('status', 'hired'),
        ];

        // Get jobs for filter
        $jobs = Job::where('company_id', $company->id)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        return view('employer.applicants.kanban', compact('kanbanData', 'jobs', 'jobId'));
    }

    public function export(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Application::with(['job', 'user.profile'])
            ->whereHas('job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        $applications = $query->get();

        // Create CSV
        $csv = [];
        $csv[] = ['Name', 'Email', 'Job Title', 'Applied Date', 'Status', 'Experience', 'Skills'];

        foreach ($applications as $app) {
            $profile = $app->user->profile;
            
            $skills = [];
            $experienceText = 'N/A';
            
            if ($profile) {
                $skills = $profile->skills ?? [];
                if (is_string($skills)) {
                    $skills = json_decode($skills, true) ?? [];
                }
                
                $experience = $profile->experience ?? [];
                if (is_string($experience)) {
                    $experience = json_decode($experience, true) ?? [];
                }
                $experienceText = !empty($experience) ? count($experience) . ' positions' : 'N/A';
            }

            $csv[] = [
                $app->user->name,
                $app->user->email,
                $app->job->title,
                $app->created_at->format('Y-m-d'),
                $app->status,
                $experienceText,
                implode(', ', $skills),
            ];
        }

        $filename = 'applications_' . now()->format('Y-m-d') . '.csv';
        
        $handle = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
