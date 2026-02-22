<?php

declare(strict_types=1);

namespace App\Livewire\Search;

use App\Services\Search\JobSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SearchBar extends Component
{
    public string $query = '';
    public bool $showAutocomplete = false;
    public array $autocompleteResults = [];
    public array $recentSearches = [];
    public array $trendingSearches = [];
    public bool $isRecording = false;
    public int $selectedIndex = -1;
    
    protected JobSearchService $searchService;
    
    protected $listeners = ['voiceSearchResult' => 'handleVoiceSearch'];
    
    public function boot(JobSearchService $searchService)
    {
        $this->searchService = $searchService;
    }
    
    public function mount()
    {
        $this->loadRecentSearches();
        $this->loadTrendingSearches();
    }
    
    /**
     * Updated query - trigger autocomplete
     */
    /**
     * Debounce handled via wire:model.live.debounce.300ms in the Blade template.
     * This method fires only after the 300ms pause.
     */
    public function updatedQuery($value)
    {
        $value = trim($value);

        if (strlen($value) >= 2) {
            $this->showAutocomplete = true;
            $this->autocompleteResults = app(JobSearchService::class)->autocomplete($value, 10);
            $this->selectedIndex = -1;
        } else {
            $this->showAutocomplete = false;
            $this->autocompleteResults = [];
        }
    }
    
    /**
     * Handle search submission
     */
    public function search()
    {
        if (empty(trim($this->query))) {
            return;
        }
        
        // Save to search history if authenticated
        if (Auth::check()) {
            $this->saveToHistory($this->query);
        }
        
        // Redirect to search results page with query
        return redirect()->route('jobs.search', ['q' => $this->query]);
    }
    
    /**
     * Select autocomplete suggestion
     */
    public function selectSuggestion($index)
    {
        if (isset($this->autocompleteResults[$index])) {
            $result = $this->autocompleteResults[$index];
            $this->query = $result['title'];
            $this->showAutocomplete = false;
            $this->search();
        }
    }
    
    /**
     * Use trending search
     */
    public function useTrendingSearch($search)
    {
        $this->query = $search;
        $this->search();
    }
    
    /**
     * Use recent search
     */
    public function useRecentSearch($search)
    {
        $this->query = $search;
        $this->search();
    }
    
    /**
     * Clear recent searches
     */
    public function clearHistory()
    {
        if (Auth::check()) {
            DB::table('search_history')
                ->where('user_id', Auth::id())
                ->delete();
            
            $this->recentSearches = [];
        }
    }
    
    /**
     * Start voice search
     */
    public function startVoiceSearch()
    {
        $this->isRecording = true;
        $this->dispatch('startVoiceRecording');
    }
    
    /**
     * Stop voice search
     */
    public function stopVoiceSearch()
    {
        $this->isRecording = false;
        $this->dispatch('stopVoiceRecording');
    }
    
    /**
     * Handle voice search result from JavaScript
     */
    public function handleVoiceSearch($transcript)
    {
        $this->query = $transcript;
        $this->isRecording = false;
        $this->search();
    }
    
    /**
     * Keyboard navigation
     */
    public function navigateAutocomplete($direction)
    {
        $count = count($this->autocompleteResults);
        
        if ($count === 0) {
            return;
        }
        
        if ($direction === 'down') {
            $this->selectedIndex = ($this->selectedIndex + 1) % $count;
        } elseif ($direction === 'up') {
            $this->selectedIndex = $this->selectedIndex <= 0 ? $count - 1 : $this->selectedIndex - 1;
        }
        
        if ($this->selectedIndex >= 0 && isset($this->autocompleteResults[$this->selectedIndex])) {
            $this->query = $this->autocompleteResults[$this->selectedIndex]['title'];
        }
    }
    
    /**
     * Hide autocomplete
     */
    public function hideAutocomplete()
    {
        // Delay to allow click events to register
        $this->dispatch('hideAutocompleteDelayed');
    }
    
    /**
     * Load recent searches for authenticated user
     */
    protected function loadRecentSearches()
    {
        if (Auth::check()) {
            $this->recentSearches = DB::table('search_history')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->pluck('query')
                ->toArray();
        }
    }
    
    /**
     * Load trending searches
     */
    protected function loadTrendingSearches()
    {
        $this->trendingSearches = app(JobSearchService::class)->getTrendingSearches();
    }
    
    /**
     * Save search to history
     */
    protected function saveToHistory($query)
    {
        // Check if already exists
        $exists = DB::table('search_history')
            ->where('user_id', Auth::id())
            ->where('query', $query)
            ->exists();
        
        if ($exists) {
            // Update timestamp
            DB::table('search_history')
                ->where('user_id', Auth::id())
                ->where('query', $query)
                ->update(['created_at' => now()]);
        } else {
            // Insert new
            DB::table('search_history')->insert([
                'user_id' => Auth::id(),
                'query' => $query,
                'created_at' => now(),
            ]);
            
            // Keep only last 20 searches
            $this->pruneHistory();
        }
        
        $this->loadRecentSearches();
    }
    
    /**
     * Keep only last 20 searches per user
     */
    protected function pruneHistory()
    {
        $toKeep = DB::table('search_history')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->pluck('id');
        
        DB::table('search_history')
            ->where('user_id', Auth::id())
            ->whereNotIn('id', $toKeep)
            ->delete();
    }
    
    public function render()
    {
        return view('livewire.search.search-bar');
    }
}
