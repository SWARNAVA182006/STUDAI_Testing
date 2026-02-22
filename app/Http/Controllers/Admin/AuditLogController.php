<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware(['auth', 'admin']); // Only admins can view audit logs
    }

    /**
     * Display audit logs.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->where('event', 'like', "%{$request->event}%");
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Filter by IP address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        // Filter by tags
        if ($request->filled('tags')) {
            $query->where('tags', 'like', "%{$request->tags}%");
        }

        $logs = $query->paginate(50);

        return view('admin.audit-logs', compact('logs'));
    }

    /**
     * Show audit log details.
     */
    public function show(AuditLog $log)
    {
        return view('admin.audit-log-detail', compact('log'));
    }

    /**
     * Get security events for current user.
     */
    public function userActivity()
    {
        $user = Auth::user();
        $events = $this->auditService->getSecurityEvents($user->id, 90);

        return view('settings.activity-log', compact('events'));
    }

    /**
     * Get suspicious activity (admin only).
     */
    public function suspicious()
    {
        $activity = $this->auditService->getSuspiciousActivity(24);

        return view('admin.suspicious-activity', compact('activity'));
    }

    /**
     * Export audit logs.
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
        ]);

        $logs = AuditLog::whereBetween('created_at', [$request->date_from, $request->date_to])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->format === 'csv') {
            return $this->exportCsv($logs);
        } else {
            return response()->json($logs);
        }
    }

    /**
     * Export logs as CSV.
     */
    private function exportCsv($logs)
    {
        $filename = 'audit-logs-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['ID', 'User', 'Event', 'Model', 'IP Address', 'Date/Time']);
            
            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user?->name ?? 'Unknown',
                    $log->event,
                    $log->auditable_type,
                    $log->ip_address,
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
