<?php

declare(strict_types=1);

namespace App\Livewire\Mobile;

use App\Models\Job;
use App\Models\SavedJob;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('layouts.mobile')]
class SwipeJobBrowser extends Component
{
    public array $jobs = [];
    public int $currentIndex = 0;
    public bool $showDetails = false;
    public ?array $currentJob = null;
    public array $swipeHistory = [];
    public bool $isLoading = false;
    public int $page = 1;
    public int $perPage = 10;
    public bool $hasMore = true;

    // Filters
    public string $search = '';
    public ?string $location = null;
    public ?string $jobType = null;
    public ?int $minSalary = null;

    public function mount(): void
    {
        $this->loadJobs();
    }

    public function loadJobs(): void
    {
        $this->isLoading = true;

        $query = Job::query()
            ->where('status', 'active')
            ->with(['company', 'skills'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->location, fn($q) => $q->where('location', 'like', "%{$this->location}%"))
            ->when($this->jobType, fn($q) => $q->where('job_type', $this->jobType))
            ->when($this->minSalary, fn($q) => $q->where('salary_min', '>=', $this->minSalary))
            ->orderBy('created_at', 'desc');

        // Exclude jobs user has already swiped on
        $swipedJobIds = array_column($this->swipeHistory, 'job_id');
        if (!empty($swipedJobIds)) {
            $query->whereNotIn('id', $swipedJobIds);
        }

        // Exclude already applied jobs
        $appliedJobIds = Application::where('user_id', Auth::id())
            ->pluck('job_id')
            ->toArray();
        if (!empty($appliedJobIds)) {
            $query->whereNotIn('id', $appliedJobIds);
        }

        $newJobs = $query->take($this->perPage)->get();

        $this->hasMore = $newJobs->count() === $this->perPage;

        foreach ($newJobs as $job) {
            $this->jobs[] = [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company?->name ?? 'Unknown Company',
                'company_logo' => $job->company?->logo_url ?? null,
                'location' => $job->location,
                'job_type' => $job->job_type,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_display' => $this->formatSalary($job->salary_min, $job->salary_max),
                'description' => $job->description,
                'short_description' => \Illuminate\Support\Str::limit(strip_tags($job->description), 150),
                'skills' => $job->skills->pluck('name')->toArray(),
                'posted_at' => $job->created_at->diffForHumans(),
                'is_remote' => $job->is_remote ?? false,
                'experience_level' => $job->experience_level,
            ];
        }

        if (!empty($this->jobs) && $this->currentJob === null) {
            $this->currentJob = $this->jobs[$this->currentIndex] ?? null;
        }

        $this->isLoading = false;
        $this->page++;
    }

    protected function formatSalary(?int $min, ?int $max): string
    {
        if (!$min && !$max) {
            return 'Salary not disclosed';
        }

        $formatNumber = fn($n) => $n >= 100000 ? number_format($n / 1000) . 'K' : number_format($n);

        if ($min && $max) {
            return '$' . $formatNumber($min) . ' - $' . $formatNumber($max);
        }

        return $min ? '$' . $formatNumber($min) . '+' : 'Up to $' . $formatNumber($max);
    }

    #[On('swipe-right')]
    public function handleSwipeRight(): void
    {
        $this->saveJob();
    }

    #[On('swipe-left')]
    public function handleSwipeLeft(): void
    {
        $this->skipJob();
    }

    #[On('swipe-up')]
    public function handleSwipeUp(): void
    {
        $this->quickApply();
    }

    public function saveJob(): void
    {
        if (!$this->currentJob) {
            return;
        }

        // Save to database
        SavedJob::firstOrCreate([
            'user_id' => Auth::id(),
            'job_id' => $this->currentJob['id'],
        ]);

        // Record in swipe history
        $this->swipeHistory[] = [
            'job_id' => $this->currentJob['id'],
            'action' => 'saved',
            'timestamp' => now()->toISOString(),
        ];

        // Save to localStorage for offline access
        $this->dispatch('save-job-offline', job: $this->currentJob);

        $this->dispatch('show-toast', message: 'Job saved!', type: 'success');
        $this->nextJob();
    }

    public function skipJob(): void
    {
        if (!$this->currentJob) {
            return;
        }

        $this->swipeHistory[] = [
            'job_id' => $this->currentJob['id'],
            'action' => 'skipped',
            'timestamp' => now()->toISOString(),
        ];

        $this->nextJob();
    }

    public function quickApply(): void
    {
        if (!$this->currentJob) {
            return;
        }

        // Check if user has a complete profile for quick apply
        $user = Auth::user();
        $hasResume = $user->resumes()->exists();

        if (!$hasResume) {
            $this->dispatch('show-toast', message: 'Please upload a resume first', type: 'warning');
            return;
        }

        // Create application
        Application::create([
            'user_id' => Auth::id(),
            'job_id' => $this->currentJob['id'],
            'status' => 'applied',
            'applied_at' => now(),
            'source' => 'swipe_browser',
        ]);

        $this->swipeHistory[] = [
            'job_id' => $this->currentJob['id'],
            'action' => 'applied',
            'timestamp' => now()->toISOString(),
        ];

        $this->dispatch('show-toast', message: 'Application submitted!', type: 'success');
        $this->nextJob();
    }

    public function nextJob(): void
    {
        $this->currentIndex++;

        // Load more jobs if we're running low
        if ($this->currentIndex >= count($this->jobs) - 3 && $this->hasMore) {
            $this->loadJobs();
        }

        if ($this->currentIndex < count($this->jobs)) {
            $this->currentJob = $this->jobs[$this->currentIndex];
        } else {
            $this->currentJob = null;
        }

        $this->showDetails = false;
    }

    public function previousJob(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->currentJob = $this->jobs[$this->currentIndex];

            // Remove last swipe history entry
            array_pop($this->swipeHistory);
        }
    }

    public function toggleDetails(): void
    {
        $this->showDetails = !$this->showDetails;
    }

    public function viewFullJob(): void
    {
        if ($this->currentJob) {
            $this->redirect(route('jobs.show', $this->currentJob['id']));
        }
    }

    public function render()
    {
        return view('livewire.mobile.swipe-job-browser', [
            'totalJobs' => count($this->jobs),
            'remainingJobs' => count($this->jobs) - $this->currentIndex,
        ]);
    }
}
