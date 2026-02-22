<?php

namespace App\Services\Search;

use MeiliSearch\Client;
use App\Models\Job;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JobSearchService
{
    private $client;
    private $index;
    private $aiService;
    private ?HybridSearchService $hybridSearch = null;

    public function __construct(AIService $aiService, ?HybridSearchService $hybridSearch = null)
    {
        $this->aiService = $aiService;
        $this->hybridSearch = $hybridSearch;
        $this->initializeMeilisearch();
    }
    
    /**
     * Initialize Meilisearch client and index
     */
    protected function initializeMeilisearch()
    {
        try {
            $this->client = new Client(
                config('meilisearch.host', 'http://127.0.0.1:7700'),
                config('meilisearch.key')
            );
            
            $this->index = $this->client->index('jobs');
            
            // Configure searchable attributes
            $this->index->updateSearchableAttributes([
                'title',
                'description',
                'requirements',
                'responsibilities',
                'company_name',
                'location',
                'extracted_skills',
                'employment_type',
                'experience_level',
            ]);
            
            // Configure ranking rules
            $this->index->updateRankingRules([
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
                'posted_at:desc',
                'quality_score:desc',
            ]);
            
            // Configure filterable attributes
            $this->index->updateFilterableAttributes([
                'status',
                'employment_type',
                'experience_level',
                'work_mode',
                'company_id',
                'salary_min',
                'salary_max',
                'is_featured',
                'is_urgent',
                'posted_at',
            ]);
            
            // Configure sortable attributes
            $this->index->updateSortableAttributes([
                'posted_at',
                'salary_min',
                'salary_max',
                'applications_count',
                'views',
                'quality_score',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Meilisearch initialization failed: ' . $e->getMessage());
            // Fallback to database search
        }
    }
    
    /**
     * Main search method with AI enhancement
     *
     * @param string $query Search query
     * @param array $filters Search filters and options
     * @return array Search results
     */
    public function search($query, $filters = [])
    {
        // Check if hybrid search is enabled and available
        $useHybridSearch = ($filters['use_hybrid'] ?? config('search.hybrid_enabled', false))
            && $this->hybridSearch !== null;

        if ($useHybridSearch) {
            return $this->hybridSearch($query, $filters);
        }

        // Process natural language query
        $processedQuery = $this->processNaturalLanguage($query);
        
        // Build search parameters
        $searchParams = [
            'q' => $processedQuery['keywords'],
            'filter' => $this->buildFilters($filters, $processedQuery['extracted_filters']),
            'limit' => $filters['limit'] ?? 20,
            'offset' => $filters['offset'] ?? 0,
            'sort' => $this->buildSort($filters),
        ];
        
        try {
            // Execute Meilisearch
            $results = $this->index->search(
                $searchParams['q'],
                array_filter($searchParams)
            );
            
            // Enhance results with AI insights
            return $this->enhanceResults($results, $query);
            
        } catch (\Exception $e) {
            Log::error('Meilisearch search failed: ' . $e->getMessage());
            // Fallback to database search
            return $this->fallbackDatabaseSearch($query, $filters);
        }
    }

    /**
     * Perform hybrid search combining keyword and semantic search.
     *
     * Uses HybridSearchService for Reciprocal Rank Fusion (RRF) of keyword
     * and semantic search results.
     *
     * @param string $query Search query
     * @param array $filters Search filters
     * @return array Hybrid search results
     */
    protected function hybridSearch(string $query, array $filters = []): array
    {
        try {
            $results = $this->hybridSearch->search($query, [
                'limit' => $filters['limit'] ?? 20,
                'filters' => $this->buildFiltersArray($filters),
                'keyword_weight' => $filters['keyword_weight'] ?? 0.7,
                'semantic_weight' => $filters['semantic_weight'] ?? 0.3,
            ]);

            return [
                'hits' => $results['results'] ?? [],
                'estimatedTotalHits' => $results['total'] ?? count($results['results'] ?? []),
                'processingTimeMs' => $results['processing_time_ms'] ?? 0,
                'query' => $query,
                'search_type' => 'hybrid',
                'metadata' => [
                    'keyword_weight' => $results['keyword_weight'] ?? 0.7,
                    'semantic_weight' => $results['semantic_weight'] ?? 0.3,
                ],
            ];
        } catch (\Exception $e) {
            Log::warning('Hybrid search failed, falling back to keyword search', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            // Fallback to standard keyword search
            return $this->search($query, array_merge($filters, ['use_hybrid' => false]));
        }
    }

    /**
     * Build filters array from search filters.
     */
    protected function buildFiltersArray(array $filters): array
    {
        $result = [];

        if (!empty($filters['location'])) {
            $result['location'] = $filters['location'];
        }
        if (!empty($filters['work_mode'])) {
            $result['work_mode'] = $filters['work_mode'];
        }
        if (!empty($filters['employment_type'])) {
            $result['employment_type'] = $filters['employment_type'];
        }
        if (!empty($filters['experience_level'])) {
            $result['experience_level'] = $filters['experience_level'];
        }

        return $result;
    }

    /**
     * Process natural language query using AI
     */
    protected function processNaturalLanguage($query)
    {
        if (empty($query)) {
            return ['keywords' => '', 'extracted_filters' => []];
        }
        
        $cacheKey = 'search_nlp_' . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query) {
            $prompt = "Analyze this job search query and extract structured information:\n\n";
            $prompt .= "Query: \"{$query}\"\n\n";
            $prompt .= "Extract and return as JSON:\n";
            $prompt .= "1. keywords: Main search keywords (job title, skills)\n";
            $prompt .= "2. location: Location preferences\n";
            $prompt .= "3. work_mode: remote/hybrid/onsite\n";
            $prompt .= "4. employment_type: full_time/part_time/contract/internship\n";
            $prompt .= "5. experience_level: entry_level/mid_level/senior_level/executive\n";
            $prompt .= "6. salary_expectation: low/medium/high/very_high\n";
            $prompt .= "7. company_type: startup/enterprise/agency/etc\n\n";
            $prompt .= "Examples:\n";
            $prompt .= "- 'remote Laravel developer with good salary' → {keywords: 'Laravel developer', work_mode: 'remote', salary_expectation: 'high'}\n";
            $prompt .= "- 'entry level frontend jobs in Bangalore' → {keywords: 'frontend', location: 'Bangalore', experience_level: 'entry_level'}\n";
            
            $systemPrompt = "You are a search query parser. Return only valid JSON without explanation.";
            
            try {
                $response = $this->aiService->generateText($prompt, $systemPrompt);
                $extracted = json_decode($response, true);
                
                if (!$extracted) {
                    throw new \Exception('Invalid JSON response');
                }
                
                return [
                    'keywords' => $extracted['keywords'] ?? $query,
                    'extracted_filters' => array_filter([
                        'location' => $extracted['location'] ?? null,
                        'work_mode' => $extracted['work_mode'] ?? null,
                        'employment_type' => $extracted['employment_type'] ?? null,
                        'experience_level' => $extracted['experience_level'] ?? null,
                        'salary_expectation' => $extracted['salary_expectation'] ?? null,
                    ]),
                ];
                
            } catch (\Exception $e) {
                // Fallback to simple keyword extraction
                return [
                    'keywords' => $query,
                    'extracted_filters' => $this->simpleFilterExtraction($query),
                ];
            }
        });
    }
    
    /**
     * Simple filter extraction without AI
     */
    protected function simpleFilterExtraction($query)
    {
        $filters = [];
        $queryLower = strtolower($query);
        
        // Work mode
        if (preg_match('/\b(remote|work from home|wfh)\b/i', $query)) {
            $filters['work_mode'] = 'remote';
        } elseif (preg_match('/\b(hybrid)\b/i', $query)) {
            $filters['work_mode'] = 'hybrid';
        } elseif (preg_match('/\b(onsite|office)\b/i', $query)) {
            $filters['work_mode'] = 'onsite';
        }
        
        // Experience level
        if (preg_match('/\b(entry|fresher|junior|beginner)\b/i', $query)) {
            $filters['experience_level'] = 'entry_level';
        } elseif (preg_match('/\b(senior|lead)\b/i', $query)) {
            $filters['experience_level'] = 'senior_level';
        } elseif (preg_match('/\b(executive|director|vp|cto|ceo)\b/i', $query)) {
            $filters['experience_level'] = 'executive';
        }
        
        // Employment type
        if (preg_match('/\b(part.?time)\b/i', $query)) {
            $filters['employment_type'] = 'part_time';
        } elseif (preg_match('/\b(contract|freelance)\b/i', $query)) {
            $filters['employment_type'] = 'contract';
        } elseif (preg_match('/\b(intern|internship)\b/i', $query)) {
            $filters['employment_type'] = 'internship';
        }
        
        return $filters;
    }
    
    /**
     * Build Meilisearch filter string
     */
    protected function buildFilters($userFilters, $extractedFilters = [])
    {
        $filters = [];
        
        // Merge user filters with AI-extracted filters (user filters take precedence)
        $allFilters = array_merge($extractedFilters, $userFilters);
        
        // Status filter (always active)
        $filters[] = "status = 'active'";
        
        // Employment type
        if (!empty($allFilters['employment_type'])) {
            $filters[] = "employment_type = '{$allFilters['employment_type']}'";
        }
        
        // Experience level
        if (!empty($allFilters['experience_level'])) {
            $filters[] = "experience_level = '{$allFilters['experience_level']}'";
        }
        
        // Work mode
        if (!empty($allFilters['work_mode'])) {
            $filters[] = "work_mode = '{$allFilters['work_mode']}'";
        }
        
        // Salary range
        if (!empty($allFilters['salary_min'])) {
            $filters[] = "salary_max >= {$allFilters['salary_min']}";
        }
        
        if (!empty($allFilters['salary_max'])) {
            $filters[] = "salary_min <= {$allFilters['salary_max']}";
        }
        
        // Salary expectation (from AI)
        if (!empty($allFilters['salary_expectation'])) {
            $salaryRanges = [
                'low' => ['min' => 0, 'max' => 500000],
                'medium' => ['min' => 500000, 'max' => 1000000],
                'high' => ['min' => 1000000, 'max' => 2000000],
                'very_high' => ['min' => 2000000, 'max' => 99999999],
            ];
            
            if (isset($salaryRanges[$allFilters['salary_expectation']])) {
                $range = $salaryRanges[$allFilters['salary_expectation']];
                $filters[] = "salary_max >= {$range['min']}";
            }
        }
        
        // Company
        if (!empty($allFilters['company_id'])) {
            $filters[] = "company_id = {$allFilters['company_id']}";
        }
        
        // Featured jobs
        if (!empty($allFilters['is_featured'])) {
            $filters[] = "is_featured = true";
        }
        
        // Posted date
        if (!empty($allFilters['posted_since'])) {
            $timestamp = strtotime($allFilters['posted_since']);
            $filters[] = "posted_at >= {$timestamp}";
        }
        
        return empty($filters) ? null : implode(' AND ', $filters);
    }
    
    /**
     * Build sort parameter
     */
    protected function buildSort($filters)
    {
        $sortOptions = [
            'recent' => ['posted_at:desc'],
            'salary_high' => ['salary_max:desc', 'posted_at:desc'],
            'salary_low' => ['salary_min:asc', 'posted_at:desc'],
            'popular' => ['applications_count:desc', 'posted_at:desc'],
            'relevant' => ['quality_score:desc', 'posted_at:desc'],
        ];
        
        $sortBy = $filters['sort'] ?? 'recent';
        
        return $sortOptions[$sortBy] ?? $sortOptions['recent'];
    }
    
    /**
     * Enhance search results with AI insights
     */
    protected function enhanceResults($results, $originalQuery)
    {
        $enhanced = [
            'hits' => [],
            'total' => $results['estimatedTotalHits'] ?? 0,
            'query' => $originalQuery,
            'processing_time' => $results['processingTimeMs'] ?? 0,
            'suggestions' => [],
        ];
        
        foreach ($results['hits'] as $hit) {
            $enhanced['hits'][] = [
                'id' => $hit['id'],
                'title' => $hit['title'],
                'company_name' => $hit['company_name'],
                'location' => $hit['location'],
                'salary_min' => $hit['salary_min'] ?? null,
                'salary_max' => $hit['salary_max'] ?? null,
                'employment_type' => $hit['employment_type'],
                'work_mode' => $hit['work_mode'],
                'posted_at' => $hit['posted_at'],
                'is_featured' => $hit['is_featured'] ?? false,
                'is_urgent' => $hit['is_urgent'] ?? false,
                'relevance_score' => $this->calculateRelevanceScore($hit, $originalQuery),
            ];
        }
        
        // Generate search suggestions if few results
        if ($enhanced['total'] < 5) {
            $enhanced['suggestions'] = $this->generateSearchSuggestions($originalQuery);
        }
        
        return $enhanced;
    }
    
    /**
     * Calculate relevance score
     */
    protected function calculateRelevanceScore($job, $query)
    {
        $score = 0;
        $queryLower = strtolower($query);
        
        // Title match (30 points)
        if (stripos($job['title'], $query) !== false) {
            $score += 30;
        }
        
        // Skills match (25 points)
        $skills = $job['extracted_skills'] ?? [];
        foreach ($skills as $skill) {
            if (stripos($queryLower, strtolower($skill)) !== false) {
                $score += 5;
                if ($score >= 25) break;
            }
        }
        
        // Description match (20 points)
        if (stripos($job['description'], $query) !== false) {
            $score += 20;
        }
        
        // Quality score bonus (15 points)
        $score += ($job['quality_score'] ?? 50) * 0.15;
        
        // Recency bonus (10 points)
        $daysOld = now()->diffInDays($job['posted_at']);
        $recencyScore = max(0, 10 - ($daysOld * 0.5));
        $score += $recencyScore;
        
        return min(100, round($score));
    }
    
    /**
     * Generate search suggestions
     */
    protected function generateSearchSuggestions($query)
    {
        $suggestions = [];
        
        // Remove specific filters
        $suggestions[] = "Try broadening your search by removing location or experience filters";
        
        // Similar job titles
        $suggestions[] = "Search for similar roles: " . $this->getSimilarJobTitles($query);
        
        // Trending searches
        $trending = $this->getTrendingSearches();
        if (!empty($trending)) {
            $suggestions[] = "Popular searches: " . implode(', ', array_slice($trending, 0, 3));
        }
        
        return $suggestions;
    }
    
    /**
     * Get similar job titles
     */
    protected function getSimilarJobTitles($query)
    {
        $synonyms = [
            'developer' => ['engineer', 'programmer', 'coder'],
            'designer' => ['UX designer', 'UI designer', 'graphic designer'],
            'manager' => ['lead', 'head', 'director'],
            'analyst' => ['specialist', 'consultant', 'advisor'],
        ];
        
        foreach ($synonyms as $key => $values) {
            if (stripos($query, $key) !== false) {
                return implode(', ', $values);
            }
        }
        
        return 'related positions';
    }
    
    /**
     * Get trending searches
     */
    public function getTrendingSearches()
    {
        return Cache::remember('trending_searches', 3600, function () {
            // Get most common search terms from last 7 days
            // This would require a search_logs table in production
            return [
                'Remote Laravel Developer',
                'Frontend React Jobs',
                'Data Analyst',
                'Product Manager',
                'DevOps Engineer',
            ];
        });
    }
    
    /**
     * Fallback to database search
     */
    protected function fallbackDatabaseSearch($query, $filters)
    {
        $jobsQuery = Job::query()
            ->where('status', 'active')
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQ) use ($query) {
                    $subQ->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%")
                        ->orWhere('company_name', 'LIKE', "%{$query}%");
                });
            })
            ->when($filters['employment_type'] ?? null, fn($q, $type) => $q->where('employment_type', $type))
            ->when($filters['work_mode'] ?? null, fn($q, $mode) => $q->where('work_mode', $mode))
            ->when($filters['experience_level'] ?? null, fn($q, $level) => $q->where('experience_level', $level))
            ->when($filters['salary_min'] ?? null, fn($q, $salary) => $q->where('salary_max', '>=', $salary))
            ->orderBy('posted_at', 'desc');
        
        $jobs = $jobsQuery->paginate($filters['limit'] ?? 20);
        
        return [
            'hits' => $jobs->items(),
            'total' => $jobs->total(),
            'query' => $query,
            'processing_time' => 0,
            'suggestions' => [],
        ];
    }
    
    /**
     * Index a job in Meilisearch
     */
    public function indexJob(Job $job)
    {
        try {
            $this->index->addDocuments([
                [
                    'id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'requirements' => $job->requirements,
                    'responsibilities' => $job->responsibilities,
                    'company_name' => $job->company_name,
                    'location' => $job->location,
                    'employment_type' => $job->employment_type,
                    'experience_level' => $job->experience_level,
                    'work_mode' => $job->work_mode,
                    'salary_min' => $job->salary_min,
                    'salary_max' => $job->salary_max,
                    'extracted_skills' => $job->extracted_skills ?? [],
                    'company_id' => $job->company_id,
                    'status' => $job->status,
                    'is_featured' => $job->is_featured,
                    'is_urgent' => $job->is_urgent,
                    'quality_score' => $job->quality_score ?? 50,
                    'posted_at' => $job->created_at->timestamp,
                    'applications_count' => $job->applications_count ?? 0,
                    'views' => $job->views ?? 0,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to index job: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove a job from search index
     */
    public function removeJob($jobId)
    {
        try {
            $this->index->deleteDocument($jobId);
        } catch (\Exception $e) {
            Log::error('Failed to remove job from index: ' . $e->getMessage());
        }
    }
    
    /**
     * Get autocomplete suggestions
     */
    public function autocomplete($query, $limit = 10)
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        try {
            $results = $this->index->search($query, [
                'limit' => $limit,
                'attributesToRetrieve' => ['title', 'company_name'],
            ]);
            
            $suggestions = [];
            foreach ($results['hits'] as $hit) {
                $suggestions[] = [
                    'text' => $hit['title'],
                    'company' => $hit['company_name'],
                ];
            }
            
            return $suggestions;
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
