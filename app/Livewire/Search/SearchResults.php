<?php

namespace App\Livewire\Search;

use App\Models\Job;
use App\Services\Search\JobSearchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SearchResults extends Component
{
    use WithPagination;
    
    public $query = '';
    public $filters = [];
    public $viewMode = 'grid'; // grid or list
    public $sortBy = 'relevant';
    public $resultsCount = 0;
    public $processingTime = 0;
    public $page = 1;
    
    protected $listeners = ['filtersUpdated' => 'updateFilters'];
    
    protected $queryString = [
        'query' => ['except' => ''],
        'viewMode' => ['except' => 'grid'],
        'sortBy' => ['except' => 'relevant'],
    ];
    
    /**
     * Mount component
     */
    public function mount($q = '')
    {
        $this->query = $q;
    }
    
    /**
     * Update filters from FilterPanel
     */
    public function updateFilters($filters)
    {
        $this->filters = $filters;
        $this->resetPage();
    }
    
    /**
     * Change sort order
     */
    public function changeSort($sortBy)
    {
        $this->sortBy = $sortBy;
        $this->resetPage();
    }
    
    /**
     * Toggle view mode
     */
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }
    
    /**
     * Save job
     */
    public function saveJob($jobId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        
        // Toggle saved status
        if ($user->savedJobs()->where('job_id', $jobId)->exists()) {
            $user->savedJobs()->detach($jobId);
            $this->dispatch('jobUnsaved', $jobId);
        } else {
            $user->savedJobs()->attach($jobId, ['created_at' => now()]);
            $this->dispatch('jobSaved', $jobId);
        }
    }
    
    /**
     * Load more results (infinite scroll)
     */
    public function loadMore()
    {
        $this->page++;
    }
    
    /**
     * Get search results
     */
    public function getResults()
    {
        $startTime = microtime(true);
        
        $searchService = app(JobSearchService::class);
        
        // Merge query string filters with component filters
        $searchFilters = array_merge($this->filters, [
            'sort' => $this->sortBy,
            'limit' => 20,
            'offset' => ($this->page - 1) * 20,
        ]);
        
        try {
            $results = $searchService->search($this->query, $searchFilters);
            
            $this->resultsCount = $results['estimatedTotalHits'] ?? $results['nbHits'] ?? 0;
            $this->processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $results;
        } catch (\Exception $e) {
            \Log::error('Search error: ' . $e->getMessage());
            return [
                'hits' => [],
                'estimatedTotalHits' => 0,
                'processingTimeMs' => 0,
            ];
        }
    }
    
    /**
     * Check if job is saved by current user
     */
    public function isJobSaved($jobId)
    {
        if (!Auth::check()) {
            return false;
        }
        
        return Auth::user()->savedJobs()->where('job_id', $jobId)->exists();
    }
    
    /**
     * Get relevance badge class
     */
    public function getRelevanceBadgeClass($score)
    {
        if ($score >= 80) {
            return 'bg-green-100 text-green-800 border-green-200';
        } elseif ($score >= 60) {
            return 'bg-blue-100 text-blue-800 border-blue-200';
        } elseif ($score >= 40) {
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        } else {
            return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }
    
    /**
     * Get relevance label
     */
    public function getRelevanceLabel($score)
    {
        if ($score >= 80) return 'Excellent Match';
        if ($score >= 60) return 'Good Match';
        if ($score >= 40) return 'Fair Match';
        return 'Potential Match';
    }
    
    /**
     * Highlight search terms in text
     */
    public function highlightText($text, $query)
    {
        if (empty($query)) {
            return $text;
        }
        
        $keywords = explode(' ', $query);
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) < 2) continue;
            
            $text = preg_replace(
                '/(' . preg_quote($keyword, '/') . ')/i',
                '<mark class="bg-yellow-200">$1</mark>',
                $text
            );
        }
        
        return $text;
    }
    
    /**
     * Get sort options
     */
    public function getSortOptions()
    {
        return [
            'relevant' => 'Most Relevant',
            'recent' => 'Most Recent',
            'salary_high' => 'Salary: High to Low',
            'salary_low' => 'Salary: Low to High',
            'popular' => 'Most Popular',
        ];
    }
    
    /**
     * Format salary range
     */
    public function formatSalaryRange($job)
    {
        if (empty($job['salary_min']) || empty($job['salary_max'])) {
            return 'Not disclosed';
        }
        
        $min = $this->formatSalary($job['salary_min']);
        $max = $this->formatSalary($job['salary_max']);
        
        if ($min === $max) {
            return $min;
        }
        
        return "{$min} - {$max}";
    }
    
    /**
     * Format salary amount
     */
    protected function formatSalary($amount)
    {
        if ($amount >= 10000000) {
            return '₹' . round($amount / 10000000, 1) . 'Cr';
        } elseif ($amount >= 100000) {
            return '₹' . round($amount / 100000, 1) . 'L';
        } else {
            return '₹' . number_format($amount);
        }
    }
    
    /**
     * Format posted date
     */
    public function formatPostedDate($date)
    {
        $posted = \Carbon\Carbon::parse($date);
        $now = now();
        
        $diffInDays = $posted->diffInDays($now);
        
        if ($diffInDays === 0) {
            return 'Posted today';
        } elseif ($diffInDays === 1) {
            return 'Posted yesterday';
        } elseif ($diffInDays < 7) {
            return "Posted {$diffInDays} days ago";
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return "Posted {$weeks} " . ($weeks === 1 ? 'week' : 'weeks') . " ago";
        } else {
            return $posted->format('M d, Y');
        }
    }
    
    /**
     * Get employment type badge class
     */
    public function getEmploymentTypeBadgeClass($type)
    {
        return match($type) {
            'full_time' => 'bg-blue-100 text-blue-800',
            'part_time' => 'bg-purple-100 text-purple-800',
            'contract' => 'bg-orange-100 text-orange-800',
            'internship' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
    
    /**
     * Get work mode icon
     */
    public function getWorkModeIcon($mode)
    {
        return match($mode) {
            'remote' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM8 11a1 1 0 112 0 1 1 0 01-2 0zm1-5a1 1 0 011 1v3a1 1 0 11-2 0V7a1 1 0 011-1z"/></svg>',
            'hybrid' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>',
            'onsite' => '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>',
            default => '',
        };
    }
    
    public function render()
    {
        $results = $this->getResults();
        
        return view('livewire.search.search-results', [
            'jobs' => $results['hits'] ?? [],
            'totalResults' => $this->resultsCount,
            'processingTime' => $this->processingTime,
            'sortOptions' => $this->getSortOptions(),
            'hasMore' => ($this->page * 20) < $this->resultsCount,
        ]);
    }
}
