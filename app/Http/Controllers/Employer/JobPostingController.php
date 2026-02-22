<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Str;

class JobPostingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'employer']);
    }

    public function index(Request $request)
    {
        $company = auth()->user()->company;
        
        $query = Job::where('company_id', $company->id)
            ->withCount('applications');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by expiry
        if ($request->filled('expiry')) {
            if ($request->expiry === 'active') {
                $query->where('expires_at', '>', now());
            } elseif ($request->expiry === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->latest()->paginate(20);

        // Get counts for filter badges
        $statusCounts = [
            'all' => Job::where('company_id', $company->id)->count(),
            'published' => Job::where('company_id', $company->id)->where('status', 'published')->count(),
            'draft' => Job::where('company_id', $company->id)->where('status', 'draft')->count(),
            'closed' => Job::where('company_id', $company->id)->where('status', 'closed')->count(),
        ];

        return view('employer.jobs.index', compact('jobs', 'statusCounts'));
    }

    public function create()
    {
        return view('employer.jobs.create');
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'in:full-time,part-time,contract,internship,remote'],
            'experience_level' => ['required', 'in:entry,mid,senior,lead'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:100'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:today'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $validated['employment_type'] = $validated['job_type'];
        unset($validated['job_type']);

        $validated['company_id'] = $company->id;
        $validated['company_name'] = $company->name;
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(8);

        $job = Job::create($validated);

        return redirect()
            ->route('employer.jobs.show', $job->id)
            ->with('success', 'Job posted successfully!');
    }

    public function show($id)
    {
        $company = auth()->user()->company;
        
        $job = Job::where('company_id', $company->id)
            ->withCount([
                'applications',
                'applications as pending_count' => fn($q) => $q->where('status', 'pending'),
                'applications as reviewing_count' => fn($q) => $q->where('status', 'reviewing'),
                'applications as shortlisted_count' => fn($q) => $q->where('status', 'shortlisted'),
            ])
            ->findOrFail($id);

        return view('employer.jobs.show', compact('job'));
    }

    public function edit($id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        return view('employer.jobs.edit', compact('job'));
    }

    public function update(Request $request, $id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'in:full-time,part-time,contract,internship,remote'],
            'experience_level' => ['required', 'in:entry,mid,senior,lead'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'gte:salary_min'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:100'],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:today'],
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        $validated['employment_type'] = $validated['job_type'];
        unset($validated['job_type']);

        $job->update($validated);

        return redirect()
            ->route('employer.jobs.show', $job->id)
            ->with('success', 'Job updated successfully!');
    }

    public function destroy($id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        // Don't allow deletion if there are applications
        if ($job->applications()->count() > 0) {
            return back()->with('error', 'Cannot delete job with existing applications. Close it instead.');
        }

        $job->delete();

        return redirect()
            ->route('employer.jobs.index')
            ->with('success', 'Job deleted successfully!');
    }

    public function close($id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        $job->update(['status' => 'closed']);

        return back()->with('success', 'Job closed successfully!');
    }

    public function reopen($id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        // Check if job is expired
        if ($job->expires_at < now()) {
            return back()->with('error', 'Cannot reopen expired job. Please update the expiry date first.');
        }

        $job->update(['status' => 'published']);

        return back()->with('success', 'Job reopened successfully!');
    }

    public function duplicate($id)
    {
        $company = auth()->user()->company;
        $job = Job::where('company_id', $company->id)->findOrFail($id);

        $newJob = $job->replicate();
        $newJob->status = 'draft';
        $newJob->title = $job->title . ' (Copy)';
        $newJob->expires_at = now()->addDays(30);
        $newJob->save();

        return redirect()
            ->route('employer.jobs.edit', $newJob->id)
            ->with('success', 'Job duplicated successfully!');
    }
}
