<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicationMonitorController extends Controller
{
    /**
     * Display the application monitoring dashboard
     */
    public function index(Request $request)
    {
        $query = AutoApplication::with(['user', 'discoveredJob', 'jobMatch'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by application status
        if ($request->filled('application_status')) {
            $query->where('application_status', $request->application_status);
        }

        // Filter by submission method
        if ($request->filled('submission_method')) {
            $query->where('submission_method', $request->submission_method);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by job title or company
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('discoveredJob', function ($q) use ($searchTerm) {
                $q->where('job_title', 'like', "%{$searchTerm}%")
                    ->orWhere('company_name', 'like', "%{$searchTerm}%");
            });
        }

        $applications = $query->paginate(50);

        // Get summary statistics
        $stats = $this->getStatistics($request);

        return view('admin.applications.monitor', compact('applications', 'stats'));
    }

    /**
     * Show detailed view of a single application
     */
    public function show(AutoApplication $application)
    {
        $application->load([
            'user.profile',
            'discoveredJob.jobSource',
            'jobMatch',
            'activityLogs' => fn($query) => $query->orderBy('created_at', 'desc'),
        ]);

        return view('admin.applications.show', compact('application'));
    }

    /**
     * Get summary statistics for applications
     */
    protected function getStatistics(Request $request)
    {
        $query = AutoApplication::query();

        // Apply same filters as main query
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'requires_manual' => (clone $query)->where('status', 'requires_manual')->count(),
            
            'got_response' => (clone $query)->where('got_response', true)->count(),
            'got_interview' => (clone $query)->where('got_interview', true)->count(),
            'got_offer' => (clone $query)->where('got_offer', true)->count(),
            
            'by_status' => (clone $query)
                ->select('application_status', DB::raw('count(*) as count'))
                ->groupBy('application_status')
                ->pluck('count', 'application_status')
                ->toArray(),
            
            'by_method' => (clone $query)
                ->select('submission_method', DB::raw('count(*) as count'))
                ->groupBy('submission_method')
                ->pluck('count', 'submission_method')
                ->toArray(),
            
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            
            'avg_ats_score' => (clone $query)->avg('ats_optimization_score'),
            'response_rate' => $this->calculateResponseRate($query),
        ];
    }

    /**
     * Calculate response rate percentage
     */
    protected function calculateResponseRate($query)
    {
        $submitted = (clone $query)->where('status', 'submitted')->count();
        
        if ($submitted === 0) {
            return 0;
        }

        $responses = (clone $query)
            ->where('status', 'submitted')
            ->where('got_response', true)
            ->count();

        return round(($responses / $submitted) * 100, 2);
    }

    /**
     * Export applications data
     */
    public function export(Request $request)
    {
        $query = AutoApplication::with(['user', 'discoveredJob', 'jobMatch']);

        // Apply filters (same as index)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->get();

        $csvData = $this->generateCsvData($applications);

        $filename = 'applications_export_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Generate CSV data from applications
     */
    protected function generateCsvData($applications)
    {
        $csv = "ID,User,Job Title,Company,Status,Application Status,Submission Method,ATS Score,Submitted At,Got Response,Got Interview,Got Offer\n";

        foreach ($applications as $app) {
            $csv .= implode(',', [
                $app->id,
                $app->user->name ?? 'N/A',
                '"' . ($app->discoveredJob->job_title ?? 'N/A') . '"',
                '"' . ($app->discoveredJob->company_name ?? 'N/A') . '"',
                $app->status,
                $app->application_status,
                $app->submission_method ?? 'N/A',
                $app->ats_optimization_score ?? 'N/A',
                $app->submitted_at?->toDateTimeString() ?? 'N/A',
                $app->got_response ? 'Yes' : 'No',
                $app->got_interview ? 'Yes' : 'No',
                $app->got_offer ? 'Yes' : 'No',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Bulk update application statuses
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:auto_applications,id',
            'action' => 'required|in:update_status,mark_response,mark_interview,mark_offer',
            'status' => 'required_if:action,update_status|in:viewed,screening,interviewing,offered,rejected',
        ]);

        $applications = AutoApplication::whereIn('id', $request->application_ids)->get();

        foreach ($applications as $application) {
            switch ($request->action) {
                case 'update_status':
                    $application->updateStatus($request->status, 'Bulk update by admin');
                    break;
                
                case 'mark_response':
                    $application->update(['got_response' => true]);
                    break;
                
                case 'mark_interview':
                    $application->update(['got_interview' => true]);
                    break;
                
                case 'mark_offer':
                    $application->update(['got_offer' => true]);
                    break;
            }
        }

        return redirect()
            ->back()
            ->with('success', count($applications) . ' applications updated successfully');
    }
}
