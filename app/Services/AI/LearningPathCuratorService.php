<?php

namespace App\Services\AI;

use App\Models\SkillGap;
use App\Models\LearningPath;
use App\Models\LearningResource;
use App\Models\User;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class LearningPathCuratorService
{
    use InteractsWithAI;

    private const CACHE_TTL_RESOURCES = 604800; // 1 week
    private const CACHE_TTL_PATH = 86400; // 24 hours
    
    // API endpoints for resource discovery
    private const YOUTUBE_API_BASE = 'https://www.googleapis.com/youtube/v3';
    private const UDEMY_API_BASE = 'https://www.udemy.com/api-2.0';
    
    /**
     * Generate a personalized learning path for a skill gap
     */
    public function generateLearningPath(SkillGap $gap, User $user, bool $forceRefresh = false): LearningPath
    {
        $cacheKey = "learning_path_{$gap->id}_{$user->id}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $pathId = Cache::get($cacheKey);
            return LearningPath::with('resources')->findOrFail($pathId);
        }

        try {
            // Get user's learning preferences
            $preferences = $user->profile->learning_preferences ?? [];
            $budget = $preferences['monthly_budget'] ?? 0;
            $learningStyle = $preferences['learning_style'] ?? 'mixed'; // visual, reading, hands-on, mixed
            $dailyTimeMinutes = $preferences['daily_time_commitment'] ?? 30;
            $preferredProviders = $preferences['preferred_providers'] ?? [];
            
            // Discover resources across platforms
            $allResources = $this->discoverResources($gap->skill_name, $gap->difficulty, $learningStyle, $preferredProviders);
            
            // Score resources by relevance using AI
            $scoredResources = $this->scoreResourceRelevance($allResources, $gap, $user);
            
            // Curate optimal learning sequence
            $curatedSequence = $this->curateOptimalSequence($scoredResources, $gap, $budget, $dailyTimeMinutes);
            
            // Generate AI-powered learning plan with milestones
            $learningPlan = $this->generateLearningPlan($gap, $curatedSequence, $dailyTimeMinutes);
            
            // Create learning path in database
            $path = $this->persistLearningPath($gap, $user, $learningPlan, $curatedSequence, $dailyTimeMinutes);
            
            Cache::put($cacheKey, $path->id, self::CACHE_TTL_PATH);
            
            return $path->load('resources');
            
        } catch (\Exception $e) {
            Log::error('Learning path generation failed', [
                'gap_id' => $gap->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return existing path if available, or create basic fallback
            return $gap->learningPath ?? $this->createFallbackPath($gap, $user);
        }
    }

    /**
     * Discover learning resources from multiple providers
     */
    private function discoverResources(string $skillName, string $difficulty, string $learningStyle, array $preferredProviders): array
    {
        $cacheKey = "resources_{$skillName}_{$difficulty}_{$learningStyle}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL_RESOURCES, function() use ($skillName, $difficulty, $learningStyle, $preferredProviders) {
            $resources = [];
            
            // Discover from YouTube (free video content)
            $resources = array_merge($resources, $this->discoverYouTubeResources($skillName, $difficulty));
            
            // Discover from Udemy (paid courses)
            if (empty($preferredProviders) || in_array('udemy', $preferredProviders)) {
                $resources = array_merge($resources, $this->discoverUdemyResources($skillName, $difficulty));
            }
            
            // Discover from Coursera (university courses)
            if (empty($preferredProviders) || in_array('coursera', $preferredProviders)) {
                $resources = array_merge($resources, $this->discoverCourseraResources($skillName, $difficulty));
            }
            
            // Discover from official documentation
            $resources = array_merge($resources, $this->discoverOfficialDocs($skillName));
            
            // Discover from GitHub (hands-on projects)
            if ($learningStyle === 'hands-on' || $learningStyle === 'mixed') {
                $resources = array_merge($resources, $this->discoverGitHubProjects($skillName, $difficulty));
            }
            
            // Discover from Medium/Dev.to (articles & tutorials)
            $resources = array_merge($resources, $this->discoverArticles($skillName, $difficulty));
            
            // Discover from FreeCodeCamp (free interactive courses)
            if (in_array('free_code_camp', $preferredProviders) || empty($preferredProviders)) {
                $resources = array_merge($resources, $this->discoverFreeCodeCampResources($skillName));
            }
            
            return $resources;
        });
    }

    /**
     * Discover YouTube video tutorials
     */
    private function discoverYouTubeResources(string $skillName, string $difficulty): array
    {
        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            Log::warning('YouTube API key not configured');
            return $this->getStaticYouTubeResources($skillName);
        }

        try {
            $query = "{$skillName} tutorial {$difficulty}";
            $response = Http::get(self::YOUTUBE_API_BASE . '/search', [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'video',
                'maxResults' => 10,
                'key' => $apiKey,
                'videoDuration' => 'medium', // 4-20 minutes
                'relevanceLanguage' => 'en',
            ]);

            if (!$response->successful()) {
                throw new \Exception('YouTube API request failed');
            }

            $items = $response->json('items', []);
            $resources = [];

            foreach ($items as $item) {
                $videoId = $item['id']['videoId'] ?? null;
                if (!$videoId) continue;

                // Get video statistics
                $stats = $this->getYouTubeVideoStats($videoId, $apiKey);

                $resources[] = [
                    'title' => $item['snippet']['title'],
                    'url' => "https://www.youtube.com/watch?v={$videoId}",
                    'resource_type' => 'video',
                    'provider' => 'youtube',
                    'description' => $item['snippet']['description'],
                    'thumbnail_url' => $item['snippet']['thumbnails']['medium']['url'] ?? null,
                    'author' => $item['snippet']['channelTitle'],
                    'duration_minutes' => $stats['duration'] ?? 15,
                    'rating' => $stats['rating'] ?? 4.0,
                    'reviews_count' => $stats['views'] ?? 0,
                    'is_free' => true,
                    'cost' => 0,
                    'published_date' => $item['snippet']['publishedAt'],
                    'tags' => [$skillName, 'tutorial', 'video'],
                ];
            }

            return $resources;

        } catch (\Exception $e) {
            Log::error('YouTube resource discovery failed', [
                'skill' => $skillName,
                'error' => $e->getMessage()
            ]);
            
            return $this->getStaticYouTubeResources($skillName);
        }
    }

    /**
     * Get YouTube video statistics
     */
    private function getYouTubeVideoStats(string $videoId, string $apiKey): array
    {
        try {
            $response = Http::get(self::YOUTUBE_API_BASE . '/videos', [
                'part' => 'statistics,contentDetails',
                'id' => $videoId,
                'key' => $apiKey,
            ]);

            if (!$response->successful()) return [];

            $item = $response->json('items.0');
            if (!$item) return [];

            $duration = $item['contentDetails']['duration'] ?? 'PT15M';
            $views = (int) ($item['statistics']['viewCount'] ?? 0);
            $likes = (int) ($item['statistics']['likeCount'] ?? 0);

            // Parse ISO 8601 duration to minutes
            preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches);
            $hours = isset($matches[1]) ? (int) rtrim($matches[1], 'H') : 0;
            $minutes = isset($matches[2]) ? (int) rtrim($matches[2], 'M') : 0;
            $totalMinutes = ($hours * 60) + $minutes;

            // Estimate rating from engagement
            $rating = min(5.0, 3.5 + ($likes / max(1, $views / 100)));

            return [
                'duration' => $totalMinutes,
                'views' => $views,
                'likes' => $likes,
                'rating' => round($rating, 1),
            ];

        } catch (\Exception $e) {
            return ['duration' => 15, 'rating' => 4.0];
        }
    }

    /**
     * Discover Udemy courses
     */
    private function discoverUdemyResources(string $skillName, string $difficulty): array
    {
        // Udemy API requires affiliate account
        // For now, return curated static list or use web scraping
        return $this->getStaticUdemyResources($skillName);
    }

    /**
     * Discover Coursera courses
     */
    private function discoverCourseraResources(string $skillName, string $difficulty): array
    {
        // Coursera API is limited
        // Return curated static list
        return $this->getStaticCourseraResources($skillName);
    }

    /**
     * Discover official documentation
     */
    private function discoverOfficialDocs(string $skillName): array
    {
        $docsUrls = [
            'Python' => 'https://docs.python.org/3/',
            'JavaScript' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript',
            'React' => 'https://react.dev/',
            'Laravel' => 'https://laravel.com/docs',
            'SQL' => 'https://www.postgresql.org/docs/',
            'Docker' => 'https://docs.docker.com/',
            'Kubernetes' => 'https://kubernetes.io/docs/',
        ];

        $url = $docsUrls[$skillName] ?? null;
        if (!$url) return [];

        return [[
            'title' => "{$skillName} Official Documentation",
            'url' => $url,
            'resource_type' => 'documentation',
            'provider' => 'official_docs',
            'description' => "Official comprehensive documentation for {$skillName}",
            'difficulty' => 'all',
            'is_free' => true,
            'cost' => 0,
            'rating' => 5.0,
            'tags' => [$skillName, 'documentation', 'reference'],
        ]];
    }

    /**
     * Discover GitHub learning projects
     */
    private function discoverGitHubProjects(string $skillName, string $difficulty): array
    {
        try {
            $query = "{$skillName} tutorial project";
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
            ])->get('https://api.github.com/search/repositories', [
                'q' => $query,
                'sort' => 'stars',
                'order' => 'desc',
                'per_page' => 5,
            ]);

            if (!$response->successful()) {
                throw new \Exception('GitHub API failed');
            }

            $items = $response->json('items', []);
            $resources = [];

            foreach ($items as $item) {
                $resources[] = [
                    'title' => $item['name'],
                    'url' => $item['html_url'],
                    'resource_type' => 'project',
                    'provider' => 'github',
                    'description' => $item['description'] ?? "GitHub project for {$skillName}",
                    'author' => $item['owner']['login'],
                    'is_free' => true,
                    'cost' => 0,
                    'rating' => min(5.0, 3 + ($item['stargazers_count'] / 1000)),
                    'reviews_count' => $item['stargazers_count'],
                    'tags' => [$skillName, 'project', 'hands-on'],
                ];
            }

            return $resources;

        } catch (\Exception $e) {
            Log::error('GitHub resource discovery failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Discover articles from Medium/Dev.to
     */
    private function discoverArticles(string $skillName, string $difficulty): array
    {
        // Use curated static list for now
        // Could integrate with Medium API or RSS feeds
        return [];
    }

    /**
     * Discover FreeCodeCamp resources
     */
    private function discoverFreeCodeCampResources(string $skillName): array
    {
        $fccCourses = [
            'JavaScript' => ['title' => 'JavaScript Algorithms and Data Structures', 'url' => 'https://www.freecodecamp.org/learn/javascript-algorithms-and-data-structures/'],
            'Python' => ['title' => 'Scientific Computing with Python', 'url' => 'https://www.freecodecamp.org/learn/scientific-computing-with-python/'],
            'React' => ['title' => 'Front End Development Libraries', 'url' => 'https://www.freecodecamp.org/learn/front-end-development-libraries/'],
        ];

        $course = $fccCourses[$skillName] ?? null;
        if (!$course) return [];

        return [[
            'title' => $course['title'],
            'url' => $course['url'],
            'resource_type' => 'interactive',
            'provider' => 'free_code_camp',
            'description' => "Interactive FreeCodeCamp course for {$skillName}",
            'is_free' => true,
            'cost' => 0,
            'rating' => 4.8,
            'difficulty' => 'beginner',
            'duration_minutes' => 1800, // ~30 hours
            'has_certificate' => true,
            'tags' => [$skillName, 'interactive', 'free'],
        ]];
    }

    /**
     * Score resources by relevance using AI
     */
    private function scoreResourceRelevance(array $resources, SkillGap $gap, User $user): array
    {
        if (empty($resources)) return [];

        try {
            $prompt = $this->buildRelevanceScoringPrompt($resources, $gap, $user);
            
            $content = $this->ai(
                $prompt,
                'You are an expert educational content curator. Score learning resources by relevance and quality.',
                ['temperature' => 0.3, 'model' => 'gpt-5-mini']
            );
            
            // Parse scoring response
            $scores = $this->parseResourceScores($content);
            
            // Apply scores to resources
            foreach ($resources as $index => &$resource) {
                $resource['ai_relevance_score'] = $scores[$index] ?? 50;
            }
            
            // Sort by relevance score
            usort($resources, fn($a, $b) => $b['ai_relevance_score'] <=> $a['ai_relevance_score']);
            
            return $resources;
            
        } catch (\Exception $e) {
            Log::error('Resource scoring failed', ['error' => $e->getMessage()]);
            
            // Fallback: simple scoring based on rating and type
            foreach ($resources as &$resource) {
                $resource['ai_relevance_score'] = ($resource['rating'] ?? 3.5) * 20;
            }
            
            return $resources;
        }
    }

    /**
     * Build prompt for AI resource scoring
     */
    private function buildRelevanceScoringPrompt(array $resources, SkillGap $gap, User $user): string
    {
        $resourceList = array_map(fn($r, $i) => "[{$i}] {$r['title']} ({$r['provider']}, {$r['resource_type']})", $resources, array_keys($resources));
        $resourceStr = implode("\n", array_slice($resourceList, 0, 20)); // Limit to 20 for token efficiency
        $learningStyle = $user->profile->learning_preferences['learning_style'] ?? 'mixed';
        
        return <<<PROMPT
Score these learning resources for: {$gap->skill_name} (Difficulty: {$gap->difficulty})

User profile:
- Current proficiency: {$gap->current_proficiency}/100
- Target proficiency: {$gap->required_proficiency}/100
- Learning style: {$learningStyle}

Resources:
{$resourceStr}

Score each resource 0-100 based on:
1. Relevance to skill (40%)
2. Quality/rating (30%)
3. Match to user's level (20%)
4. Fit with learning style (10%)

Respond ONLY with scores, one per line: [index] score
Example: [0] 85
PROMPT;
    }

    /**
     * Parse AI scoring response
     */
    private function parseResourceScores(string $response): array
    {
        $scores = [];
        $lines = explode("\n", $response);
        
        foreach ($lines as $line) {
            if (preg_match('/\[(\d+)\]\s*(\d+)/', $line, $matches)) {
                $index = (int) $matches[1];
                $score = (int) $matches[2];
                $scores[$index] = min(100, max(0, $score));
            }
        }
        
        return $scores;
    }

    /**
     * Curate optimal learning sequence
     */
    private function curateOptimalSequence(array $resources, SkillGap $gap, float $budget, int $dailyTimeMinutes): array
    {
        $sequence = [];
        $totalCost = 0;
        $currentDifficulty = 'beginner';
        
        // Difficulty progression
        $difficultyOrder = ['beginner' => 1, 'easy' => 1, 'moderate' => 2, 'intermediate' => 2, 'challenging' => 3, 'advanced' => 3];
        
        foreach ($resources as $resource) {
            // Skip if over budget
            if ($totalCost + $resource['cost'] > $budget && !$resource['is_free']) {
                continue;
            }
            
            // Check difficulty progression (don't jump too far ahead)
            $resourceLevel = $difficultyOrder[$resource['difficulty'] ?? 'moderate'] ?? 2;
            $currentLevel = $difficultyOrder[$currentDifficulty] ?? 1;
            
            if ($resourceLevel > $currentLevel + 1) {
                continue; // Too advanced for current stage
            }
            
            $sequence[] = $resource;
            $totalCost += $resource['cost'];
            $currentDifficulty = $resource['difficulty'] ?? $currentDifficulty;
            
            // Limit to 10-15 resources for focused learning
            if (count($sequence) >= 15) break;
        }
        
        return $sequence;
    }

    /**
     * Generate AI-powered learning plan with milestones
     */
    private function generateLearningPlan(SkillGap $gap, array $resources, int $dailyTimeMinutes): array
    {
        $totalDuration = array_sum(array_column($resources, 'duration_minutes'));
        $estimatedDays = ceil($totalDuration / $dailyTimeMinutes);
        
        return [
            'title' => "Master {$gap->skill_name}",
            'description' => "Structured learning path from {$gap->current_proficiency}% to {$gap->required_proficiency}% proficiency",
            'total_resources' => count($resources),
            'total_duration_minutes' => $totalDuration,
            'estimated_completion_days' => $estimatedDays,
            'difficulty_progression' => $gap->difficulty,
            'steps' => $this->generateMilestones($resources, $gap),
        ];
    }

    /**
     * Generate learning milestones
     */
    private function generateMilestones(array $resources, SkillGap $gap): array
    {
        $milestones = [
            ['name' => 'Foundation', 'resources' => [], 'target_proficiency' => 30],
            ['name' => 'Core Concepts', 'resources' => [], 'target_proficiency' => 60],
            ['name' => 'Practical Application', 'resources' => [], 'target_proficiency' => 80],
            ['name' => 'Mastery', 'resources' => [], 'target_proficiency' => 100],
        ];
        
        $resourcesPerMilestone = ceil(count($resources) / 4);
        $chunks = array_chunk($resources, $resourcesPerMilestone);
        
        foreach ($chunks as $index => $chunk) {
            if (isset($milestones[$index])) {
                $milestones[$index]['resources'] = array_map(fn($r) => $r['title'], $chunk);
            }
        }
        
        return $milestones;
    }

    /**
     * Persist learning path to database
     */
    private function persistLearningPath(SkillGap $gap, User $user, array $plan, array $resources, int $dailyTimeMinutes): LearningPath
    {
        $path = LearningPath::create([
            'user_id' => $user->id,
            'skill_gap_id' => $gap->id,
            'title' => $plan['title'],
            'description' => $plan['description'],
            'difficulty' => $gap->difficulty,
            'total_duration_minutes' => $plan['total_duration_minutes'],
            'total_resources' => $plan['total_resources'],
            'steps' => $plan['steps'],
            'schedule_preferences' => [
                'daily_minutes' => $dailyTimeMinutes,
                'preferred_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
            'is_ai_generated' => true,
            'status' => 'draft',
        ]);

        // Create resource records
        foreach ($resources as $index => $resourceData) {
            LearningResource::create([
                'learning_path_id' => $path->id,
                'title' => $resourceData['title'],
                'url' => $resourceData['url'],
                'resource_type' => $resourceData['resource_type'],
                'provider' => $resourceData['provider'],
                'description' => $resourceData['description'] ?? null,
                'difficulty' => $resourceData['difficulty'] ?? 'moderate',
                'duration_minutes' => $resourceData['duration_minutes'] ?? 60,
                'is_free' => $resourceData['is_free'],
                'cost' => $resourceData['cost'],
                'currency' => 'USD',
                'rating' => $resourceData['rating'] ?? null,
                'reviews_count' => $resourceData['reviews_count'] ?? 0,
                'author' => $resourceData['author'] ?? null,
                'published_date' => $resourceData['published_date'] ?? null,
                'thumbnail_url' => $resourceData['thumbnail_url'] ?? null,
                'has_certificate' => $resourceData['has_certificate'] ?? false,
                'ai_relevance_score' => $resourceData['ai_relevance_score'] ?? 50,
                'tags' => $resourceData['tags'] ?? [],
                'step_order' => $index + 1,
            ]);
        }

        return $path;
    }

    /**
     * Create fallback path when AI fails
     */
    private function createFallbackPath(SkillGap $gap, User $user): LearningPath
    {
        $path = LearningPath::create([
            'user_id' => $user->id,
            'skill_gap_id' => $gap->id,
            'title' => "Learn {$gap->skill_name}",
            'description' => "Basic learning path for {$gap->skill_name}",
            'difficulty' => $gap->difficulty,
            'total_duration_minutes' => $gap->estimated_learning_time_weeks * 7 * 60,
            'total_resources' => 0,
            'steps' => [],
            'is_ai_generated' => false,
            'status' => 'draft',
        ]);

        return $path;
    }

    // Static resource fallbacks
    private function getStaticYouTubeResources(string $skill): array { return []; }
    private function getStaticUdemyResources(string $skill): array { return []; }
    private function getStaticCourseraResources(string $skill): array { return []; }
}
