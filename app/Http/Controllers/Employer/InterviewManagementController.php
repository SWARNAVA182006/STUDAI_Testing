<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InterviewManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'employer']);
    }

    /**
     * Display a listing of interviews
     */
    public function index(Request $request)
    {
        $company = Auth::user()->company;

        $query = Interview::with(['application.user', 'application.job', 'interviewers'])
            ->whereHas('application.job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('interview_type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'upcoming');
        switch ($sortBy) {
            case 'recent':
                $query->latest('scheduled_at');
                break;
            case 'oldest':
                $query->oldest('scheduled_at');
                break;
            default: // upcoming
                $query->where('scheduled_at', '>=', now())
                    ->orderBy('scheduled_at', 'asc');
        }

        $interviews = $query->paginate(20);

        // Get statistics
        $stats = [
            'total' => Interview::whereHas('application.job', fn($q) => $q->where('company_id', $company->id))->count(),
            'upcoming' => Interview::whereHas('application.job', fn($q) => $q->where('company_id', $company->id))
                ->where('scheduled_at', '>=', now())
                ->where('status', 'scheduled')
                ->count(),
            'completed' => Interview::whereHas('application.job', fn($q) => $q->where('company_id', $company->id))
                ->where('status', 'completed')
                ->count(),
            'canceled' => Interview::whereHas('application.job', fn($q) => $q->where('company_id', $company->id))
                ->where('status', 'canceled')
                ->count(),
        ];

        return view('employer.interviews.index', compact('interviews', 'stats'));
    }

    /**
     * Display the specified interview
     */
    public function show($id)
    {
        $company = Auth::user()->company;

        $interview = Interview::with([
            'application.user.profile',
            'application.job',
            'interviewers'
        ])
            ->whereHas('application.job', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            })
            ->findOrFail($id);

        return view('employer.interviews.show', compact('interview'));
    }

    /**
     * Store a newly created interview
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'interview_type' => 'required|in:phone,video,onsite,technical,behavioral,panel',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'location' => 'nullable|string|max:500',
            'meeting_link' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'exists:users,id',
        ]);

        $company = Auth::user()->company;

        // Verify application belongs to company
        $application = Application::whereHas('job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($validated['application_id']);

        DB::beginTransaction();
        try {
            // Create interview
            $interview = Interview::create([
                'application_id' => $application->id,
                'interview_type' => $validated['interview_type'],
                'scheduled_at' => $validated['scheduled_at'],
                'duration_minutes' => $validated['duration_minutes'],
                'location' => $validated['location'] ?? null,
                'meeting_link' => $validated['meeting_link'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'scheduled',
            ]);

            // Attach interviewers if provided
            if (!empty($validated['interviewers'])) {
                $interviewerData = collect($validated['interviewers'])->mapWithKeys(function ($userId, $index) {
                    return [$userId => ['is_lead' => $index === 0]];
                })->toArray();

                $interview->interviewers()->attach($interviewerData);
            }

            // Update application status
            $application->update([
                'status' => Application::STATUS_INTERVIEW_SCHEDULED,
                'interview_at' => $validated['scheduled_at'],
            ]);

            DB::commit();

            return redirect()
                ->route('employer.interviews.show', $interview->id)
                ->with('success', 'Interview scheduled successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to schedule interview. Please try again.');
        }
    }

    /**
     * Update the specified interview
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->company;

        $interview = Interview::whereHas('application.job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'interview_type' => 'sometimes|in:phone,video,onsite,technical,behavioral,panel',
            'scheduled_at' => 'sometimes|date|after:now',
            'duration_minutes' => 'sometimes|integer|min:15|max:240',
            'location' => 'nullable|string|max:500',
            'meeting_link' => 'nullable|url|max:500',
            'notes' => 'nullable|string|max:2000',
            'interviewers' => 'nullable|array',
            'interviewers.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $interview->update($validated);

            // Update interviewers if provided
            if (isset($validated['interviewers'])) {
                $interviewerData = collect($validated['interviewers'])->mapWithKeys(function ($userId, $index) {
                    return [$userId => ['is_lead' => $index === 0]];
                })->toArray();

                $interview->interviewers()->sync($interviewerData);
            }

            // If rescheduled, update application
            if (isset($validated['scheduled_at'])) {
                $interview->application->update([
                    'interview_at' => $validated['scheduled_at'],
                ]);
            }

            DB::commit();

            return back()->with('success', 'Interview updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update interview. Please try again.');
        }
    }

    /**
     * Mark interview as completed
     */
    public function complete(Request $request, $id)
    {
        $company = Auth::user()->company;

        $interview = Interview::whereHas('application.job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'feedback' => 'nullable|array',
            'feedback.strengths' => 'nullable|string|max:1000',
            'feedback.weaknesses' => 'nullable|string|max:1000',
            'feedback.recommendations' => 'nullable|string|max:1000',
            'interviewer_notes' => 'nullable|string|max:2000',
            'decision' => 'required|in:proceed,reject,need_more_info',
        ]);

        DB::beginTransaction();
        try {
            $interview->markAsCompleted(
                $validated['feedback'] ?? [],
                $validated['rating'] ?? null,
                $validated['interviewer_notes'] ?? null
            );

            // Update application status based on decision
            $newStatus = match($validated['decision']) {
                'proceed' => Application::STATUS_SHORTLISTED,
                'reject' => Application::STATUS_REJECTED,
                'need_more_info' => Application::STATUS_INTERVIEW_COMPLETED,
            };

            $interview->application->update(['status' => $newStatus]);

            DB::commit();

            return back()->with('success', 'Interview marked as completed!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete interview. Please try again.');
        }
    }

    /**
     * Cancel the specified interview
     */
    public function cancel(Request $request, $id)
    {
        $company = Auth::user()->company;

        $interview = Interview::whereHas('application.job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $interview->cancel($validated['reason'] ?? null);

            // Update application status back to reviewing
            $interview->application->update([
                'status' => Application::STATUS_VIEWED,
                'interview_at' => null,
            ]);

            DB::commit();

            return back()->with('success', 'Interview canceled successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel interview. Please try again.');
        }
    }

    /**
     * Get interviewer availability for calendar
     */
    public function availability(Request $request)
    {
        $company = Auth::user()->company;

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'interviewer_ids' => 'nullable|array',
            'interviewer_ids.*' => 'exists:users,id',
        ]);

        // Get scheduled interviews for the period
        $interviews = Interview::whereHas('application.job', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereBetween('scheduled_at', [$validated['date_from'], $validated['date_to']])
            ->where('status', 'scheduled')
            ->with('interviewers:id,name')
            ->get();

        // Format for calendar
        $events = $interviews->map(function ($interview) {
            return [
                'id' => $interview->id,
                'title' => $interview->interview_type . ' Interview',
                'start' => $interview->scheduled_at->toIso8601String(),
                'end' => $interview->end_time->toIso8601String(),
                'interviewers' => $interview->interviewers->pluck('name')->toArray(),
            ];
        });

        return response()->json($events);
    }
}
