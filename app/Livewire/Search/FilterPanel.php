<?php

namespace App\Livewire\Search;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FilterPanel extends Component
{
    // Filter values
    public $location = '';
    public $salaryMin = 0;
    public $salaryMax = 10000000;
    public $employmentTypes = [];
    public $experienceLevels = [];
    public $workModes = [];
    public $companySize = '';
    public $postedDate = 'all';
    public $companies = [];
    
    // UI state
    public $showFilters = true;
    public $activeFiltersCount = 0;
    
    // Available options
    public $availableLocations = [];
    public $availableCompanies = [];
    
    protected $queryString = [
        'location' => ['except' => ''],
        'salaryMin' => ['except' => 0],
        'salaryMax' => ['except' => 10000000],
        'employmentTypes' => ['except' => []],
        'experienceLevels' => ['except' => []],
        'workModes' => ['except' => []],
        'companySize' => ['except' => ''],
        'postedDate' => ['except' => 'all'],
        'companies' => ['except' => []],
    ];
    
    public function mount()
    {
        $this->loadAvailableLocations();
        $this->loadAvailableCompanies();
        $this->calculateActiveFilters();
    }
    
    /**
     * Apply filters
     */
    public function applyFilters()
    {
        $this->calculateActiveFilters();
        $this->dispatch('filtersUpdated', $this->getFilters());
    }
    
    /**
     * Clear all filters
     */
    public function clearAll()
    {
        $this->location = '';
        $this->salaryMin = 0;
        $this->salaryMax = 10000000;
        $this->employmentTypes = [];
        $this->experienceLevels = [];
        $this->workModes = [];
        $this->companySize = '';
        $this->postedDate = 'all';
        $this->companies = [];
        
        $this->calculateActiveFilters();
        $this->dispatch('filtersUpdated', $this->getFilters());
    }
    
    /**
     * Remove specific filter
     */
    public function removeFilter($filterType, $value = null)
    {
        switch ($filterType) {
            case 'location':
                $this->location = '';
                break;
            case 'salary':
                $this->salaryMin = 0;
                $this->salaryMax = 10000000;
                break;
            case 'employmentType':
                $this->employmentTypes = array_diff($this->employmentTypes, [$value]);
                break;
            case 'experienceLevel':
                $this->experienceLevels = array_diff($this->experienceLevels, [$value]);
                break;
            case 'workMode':
                $this->workModes = array_diff($this->workModes, [$value]);
                break;
            case 'companySize':
                $this->companySize = '';
                break;
            case 'postedDate':
                $this->postedDate = 'all';
                break;
            case 'company':
                $this->companies = array_diff($this->companies, [$value]);
                break;
        }
        
        $this->applyFilters();
    }
    
    /**
     * Toggle filter panel visibility
     */
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }
    
    /**
     * Get all active filters as array
     */
    public function getFilters()
    {
        $filters = [];
        
        if (!empty($this->location)) {
            $filters['location'] = $this->location;
        }
        
        if ($this->salaryMin > 0 || $this->salaryMax < 10000000) {
            $filters['salary_min'] = $this->salaryMin;
            $filters['salary_max'] = $this->salaryMax;
        }
        
        if (!empty($this->employmentTypes)) {
            $filters['employment_type'] = $this->employmentTypes;
        }
        
        if (!empty($this->experienceLevels)) {
            $filters['experience_level'] = $this->experienceLevels;
        }
        
        if (!empty($this->workModes)) {
            $filters['work_mode'] = $this->workModes;
        }
        
        if (!empty($this->companySize)) {
            $filters['company_size'] = $this->companySize;
        }
        
        if ($this->postedDate !== 'all') {
            $filters['posted_since'] = $this->getPostedSinceDate();
        }
        
        if (!empty($this->companies)) {
            $filters['company_id'] = $this->companies;
        }
        
        return $filters;
    }
    
    /**
     * Calculate number of active filters
     */
    protected function calculateActiveFilters()
    {
        $count = 0;
        
        if (!empty($this->location)) $count++;
        if ($this->salaryMin > 0 || $this->salaryMax < 10000000) $count++;
        if (!empty($this->employmentTypes)) $count += count($this->employmentTypes);
        if (!empty($this->experienceLevels)) $count += count($this->experienceLevels);
        if (!empty($this->workModes)) $count += count($this->workModes);
        if (!empty($this->companySize)) $count++;
        if ($this->postedDate !== 'all') $count++;
        if (!empty($this->companies)) $count += count($this->companies);
        
        $this->activeFiltersCount = $count;
    }
    
    /**
     * Get date for posted_since filter
     */
    protected function getPostedSinceDate()
    {
        return match($this->postedDate) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => null,
        };
    }
    
    /**
     * Load available locations from database
     */
    protected function loadAvailableLocations()
    {
        $this->availableLocations = DB::table('jobs')
            ->where('status', 'active')
            ->whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->limit(50)
            ->pluck('location')
            ->toArray();
    }
    
    /**
     * Load available companies
     */
    protected function loadAvailableCompanies()
    {
        $this->availableCompanies = Company::query()
            ->where('is_verified', true)
            ->has('jobs', '>', 0)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'logo'])
            ->toArray();
    }
    
    /**
     * Format salary for display
     */
    public function getFormattedSalaryMin()
    {
        return $this->formatSalary($this->salaryMin);
    }
    
    public function getFormattedSalaryMax()
    {
        return $this->formatSalary($this->salaryMax);
    }
    
    protected function formatSalary($amount)
    {
        if ($amount >= 10000000) {
            return '₹1Cr+';
        } elseif ($amount >= 100000) {
            return '₹' . round($amount / 100000, 1) . 'L';
        } else {
            return '₹' . number_format($amount);
        }
    }
    
    /**
     * Get active filter badges
     */
    public function getActiveFilterBadges()
    {
        $badges = [];
        
        if (!empty($this->location)) {
            $badges[] = [
                'type' => 'location',
                'label' => $this->location,
                'value' => null,
            ];
        }
        
        if ($this->salaryMin > 0 || $this->salaryMax < 10000000) {
            $badges[] = [
                'type' => 'salary',
                'label' => $this->getFormattedSalaryMin() . ' - ' . $this->getFormattedSalaryMax(),
                'value' => null,
            ];
        }
        
        foreach ($this->employmentTypes as $type) {
            $badges[] = [
                'type' => 'employmentType',
                'label' => $this->getEmploymentTypeLabel($type),
                'value' => $type,
            ];
        }
        
        foreach ($this->experienceLevels as $level) {
            $badges[] = [
                'type' => 'experienceLevel',
                'label' => $this->getExperienceLevelLabel($level),
                'value' => $level,
            ];
        }
        
        foreach ($this->workModes as $mode) {
            $badges[] = [
                'type' => 'workMode',
                'label' => $this->getWorkModeLabel($mode),
                'value' => $mode,
            ];
        }
        
        if (!empty($this->companySize)) {
            $badges[] = [
                'type' => 'companySize',
                'label' => $this->companySize . ' employees',
                'value' => null,
            ];
        }
        
        if ($this->postedDate !== 'all') {
            $badges[] = [
                'type' => 'postedDate',
                'label' => $this->getPostedDateLabel($this->postedDate),
                'value' => null,
            ];
        }
        
        foreach ($this->companies as $companyId) {
            $company = collect($this->availableCompanies)->firstWhere('id', $companyId);
            if ($company) {
                $badges[] = [
                    'type' => 'company',
                    'label' => $company['name'],
                    'value' => $companyId,
                ];
            }
        }
        
        return $badges;
    }
    
    protected function getEmploymentTypeLabel($type)
    {
        return match($type) {
            'full_time' => 'Full-time',
            'part_time' => 'Part-time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            default => ucfirst($type),
        };
    }
    
    protected function getExperienceLevelLabel($level)
    {
        return match($level) {
            'entry' => 'Entry Level',
            'mid' => 'Mid Level',
            'senior' => 'Senior',
            'executive' => 'Executive',
            default => ucfirst($level),
        };
    }
    
    protected function getWorkModeLabel($mode)
    {
        return match($mode) {
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
            'onsite' => 'On-site',
            default => ucfirst($mode),
        };
    }
    
    protected function getPostedDateLabel($date)
    {
        return match($date) {
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            default => 'All time',
        };
    }
    
    public function render()
    {
        return view('livewire.search.filter-panel', [
            'activeBadges' => $this->getActiveFilterBadges(),
        ]);
    }
}
