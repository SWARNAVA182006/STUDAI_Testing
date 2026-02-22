<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\PredictedCareerPath;
use App\Models\LearningRecommendation;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;

class SkillGapAnalyzer
{
    use InteractsWithAI;
    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 43200; // 12 hours
    
    /**
     * Analyze skill gaps between current skills and target role
     */
    public function analyzeGaps(User $user, string $targetRole, string $industry): array
    {
        $cacheKey = "skill_gaps_{$user->id}_{$targetRole}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $targetRole, $industry) {
            $currentSkills = $this->getUserSkills($user);
            $requiredSkills = $this->getRequiredSkills($targetRole, $industry);
            
            // Identify gaps
            $gaps = array_diff($requiredSkills, $currentSkills);
            
            // Get AI analysis for prioritization
            $analysis = $this->getAIAnalysis($currentSkills, $requiredSkills, $targetRole, $industry);
            
            return [
                'skill_gaps' => array_values($gaps),
                'prioritized_gaps' => $analysis['prioritized'] ?? [],
                'learning_time_estimate' => $analysis['time_estimate'] ?? null,
                'difficulty_assessment' => $analysis['difficulty'] ?? [],
                'recommended_order' => $analysis['order'] ?? [],
                'quick_wins' => $analysis['quick_wins'] ?? [],
                'long_term_investments' => $analysis['long_term'] ?? [],
            ];
        });
    }
    
    /**
     * Prioritize skills by importance and attainability
     */
    public function prioritizeSkills(array $skillGaps, User $user, string $targetRole): array
    {
        $yearsOfExperience = $user->profile->years_of_experience ?? 0;
        $formattedGaps = $this->formatSkillGaps($skillGaps);
        $formattedSkills = $this->formatUserSkills($user);
        $formattedEducation = $this->formatUserEducation($user);
        
        $prompt = <<<PROMPT
Prioritize these skill gaps for a professional transitioning to {$targetRole}:

Current Gaps: {$formattedGaps}

User Background:
- Years of Experience: {$yearsOfExperience}
- Current Skills: {$formattedSkills}
- Education: {$formattedEducation}

For each skill, provide:
1. Priority score (1-10, 10 = most critical)
2. Estimated learning time (in months)
3. Difficulty level (easy/moderate/challenging/difficult)
4. Prerequisites (other skills needed first)
5. Impact on career transition (low/medium/high/critical)
6. Market demand (0-100)
7. Learning resources available (abundant/moderate/scarce)
8. Recommended learning path

Return JSON array sorted by priority:
[
  {
    "skill": "Python Programming",
    "priority": 10,
    "learning_time_months": 6,
    "difficulty": "moderate",
    "prerequisites": ["Basic Programming"],
    "impact": "critical",
    "market_demand": 95,
    "resource_availability": "abundant",
    "learning_path": "Start with fundamentals, then data structures, finally frameworks"
  }
]
PROMPT;

        try {
            $content = $this->ai(
                $prompt,
                'You are a career development expert specializing in skill gap analysis and learning path optimization.',
                ['temperature' => 0.6]
            );
            
            if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
                $prioritized = json_decode($matches[0], true);
                return $prioritized ?? $this->getDefaultPrioritization($skillGaps);
            }
        } catch (\Exception $e) {
            \Log::error('Skill prioritization failed: ' . $e->getMessage());
        }
        
        return $this->getDefaultPrioritization($skillGaps);
    }
    
    /**
     * Estimate total learning time for all gaps
     */
    public function estimateLearningTime(array $prioritizedSkills): array
    {
        $totalMonths = 0;
        $parallelMonths = 0;
        
        // Skills that can be learned in parallel
        $parallelizable = collect($prioritizedSkills)
            ->filter(fn($skill) => empty($skill['prerequisites']))
            ->sum('learning_time_months');
        
        // Skills requiring sequential learning
        $sequential = collect($prioritizedSkills)
            ->filter(fn($skill) => !empty($skill['prerequisites']))
            ->sum('learning_time_months');
        
        $totalMonths = $sequential + ($parallelizable / 2); // Assume some parallelization
        
        return [
            'total_months' => ceil($totalMonths),
            'minimum_months' => ceil($totalMonths * 0.7), // Intensive learning
            'maximum_months' => ceil($totalMonths * 1.5), // Part-time learning
            'full_time_estimate' => ceil($totalMonths),
            'part_time_estimate' => ceil($totalMonths * 2),
            'breakdown' => [
                'quick_wins' => $this->getQuickWins($prioritizedSkills),
                'medium_term' => $this->getMediumTerm($prioritizedSkills),
                'long_term' => $this->getLongTerm($prioritizedSkills),
            ]
        ];
    }
    
    /**
     * Track user's skill acquisition progress
     */
    public function trackProgress(User $user, PredictedCareerPath $path): array
    {
        $originalGaps = $path->skill_gaps ?? [];
        $currentSkills = $this->getUserSkills($user);
        
        $remaining = array_diff($originalGaps, $currentSkills);
        $acquired = array_diff($originalGaps, $remaining);
        
        $progressPercent = count($originalGaps) > 0 
            ? (count($acquired) / count($originalGaps)) * 100 
            : 100;
        
        return [
            'original_gaps' => $originalGaps,
            'acquired_skills' => array_values($acquired),
            'remaining_gaps' => array_values($remaining),
            'progress_percent' => round($progressPercent, 1),
            'skills_acquired_count' => count($acquired),
            'skills_remaining_count' => count($remaining),
            'milestone_reached' => $this->checkMilestone($progressPercent),
            'next_recommended_skill' => $this->getNextSkill($path, $remaining),
        ];
    }
    
    /**
     * Get personalized learning recommendations
     */
    public function getLearningRecommendations(
        User $user, 
        PredictedCareerPath $path, 
        string $skill
    ): array {
        $cacheKey = "learning_rec_{$user->id}_{$skill}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $path, $skill) {
            // Get recommendations from multiple platforms
            $coursera = $this->searchCoursera($skill);
            $udemy = $this->searchUdemy($skill);
            $linkedin = $this->searchLinkedInLearning($skill);
            
            $allCourses = array_merge($coursera, $udemy, $linkedin);
            
            // Score and rank courses
            $rankedCourses = $this->rankCourses($allCourses, $user, $skill);
            
            // Save top recommendations to database
            $this->saveRecommendations($user, $path, $skill, $rankedCourses);
            
            return $rankedCourses;
        });
    }
    
    /**
     * Identify skills that align with market trends
     */
    public function identifyTrendingSkills(string $industry, string $role): array
    {
        $cacheKey = "trending_skills_{$industry}_{$role}";
        
        return Cache::remember($cacheKey, 86400, function () use ($industry, $role) {
            $prompt = <<<PROMPT
Identify the most in-demand and trending skills for {$role} in {$industry} for 2024-2025.

Focus on:
1. Emerging technologies being adopted
2. Skills with highest salary premiums
3. Skills mentioned in recent job postings
4. Future-proof skills with 5+ year relevance

For each skill, provide:
- Skill name
- Trend direction (rising/stable/declining)
- Market demand score (0-100)
- Salary impact (% premium)
- Adoption rate (low/medium/high)
- Future outlook (2-5 years)

Return JSON array sorted by demand.
PROMPT;

            try {
                $content = $this->ai(
                    $prompt,
                    'You are a market analyst tracking skill demand trends.',
                    ['temperature' => 0.5]
                );
                
                if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
                    return json_decode($matches[0], true) ?? [];
                }
            } catch (\Exception $e) {
                \Log::error('Trending skills identification failed: ' . $e->getMessage());
            }
            
            return [];
        });
    }
    
    /**
     * Compare user skills against industry benchmarks
     */
    public function benchmarkSkills(User $user, string $role, string $industry): array
    {
        $userSkills = $this->getUserSkills($user);
        $benchmarkSkills = $this->getIndustryBenchmark($role, $industry);
        
        $matched = array_intersect($userSkills, $benchmarkSkills['required']);
        $missing = array_diff($benchmarkSkills['required'], $userSkills);
        $bonus = array_diff($userSkills, $benchmarkSkills['required']);
        
        $matchPercent = count($benchmarkSkills['required']) > 0
            ? (count($matched) / count($benchmarkSkills['required'])) * 100
            : 0;
        
        return [
            'match_percentage' => round($matchPercent, 1),
            'matched_skills' => array_values($matched),
            'missing_skills' => array_values($missing),
            'bonus_skills' => array_values($bonus),
            'competitive_level' => $this->getCompetitiveLevel($matchPercent),
            'benchmark_data' => $benchmarkSkills,
            'recommendations' => $this->getBenchmarkRecommendations($matchPercent, $missing),
        ];
    }
    
    // Protected helper methods
    
    protected function getUserSkills(User $user): array
    {
        $profile = $user->profile;
        $skills = $profile->skills ?? [];
        
        // Flatten nested skill arrays
        $flat = [];
        foreach ($skills as $category => $skillList) {
            if (is_array($skillList)) {
                $flat = array_merge($flat, $skillList);
            } elseif (is_string($skillList)) {
                $flat = array_merge($flat, explode(',', $skillList));
            }
        }
        
        return array_map('trim', array_unique($flat));
    }
    
    protected function getRequiredSkills(string $role, string $industry): array
    {
        // In production, this would query a skills database or API
        // For now, return common skills
        return [
            'Communication',
            'Problem Solving',
            'Leadership',
            'Technical Skills',
            'Project Management',
        ];
    }
    
    protected function getAIAnalysis(array $current, array $required, string $role, string $industry): array
    {
        $gaps = array_diff($required, $current);
        
        return [
            'prioritized' => array_values($gaps),
            'time_estimate' => count($gaps) * 3, // 3 months per skill average
            'difficulty' => array_fill_keys($gaps, 'moderate'),
            'order' => array_values($gaps),
            'quick_wins' => array_slice($gaps, 0, 2),
            'long_term' => array_slice($gaps, 2),
        ];
    }
    
    protected function formatSkillGaps(array $gaps): string
    {
        return implode(', ', $gaps);
    }
    
    protected function formatUserSkills(User $user): string
    {
        return implode(', ', $this->getUserSkills($user));
    }
    
    protected function formatUserEducation(User $user): string
    {
        $education = $user->profile->education ?? [];
        return collect($education)->map(fn($e) => $e['degree'] ?? 'Unknown')->implode(', ');
    }
    
    protected function getDefaultPrioritization(array $gaps): array
    {
        return collect($gaps)->map(function ($gap, $index) {
            return [
                'skill' => $gap,
                'priority' => 10 - $index,
                'learning_time_months' => rand(3, 12),
                'difficulty' => ['easy', 'moderate', 'challenging'][array_rand(['easy', 'moderate', 'challenging'])],
                'prerequisites' => [],
                'impact' => 'high',
                'market_demand' => rand(60, 95),
                'resource_availability' => 'abundant',
                'learning_path' => 'Self-paced online courses recommended',
            ];
        })->values()->toArray();
    }
    
    protected function getQuickWins(array $skills): array
    {
        return collect($skills)
            ->filter(fn($s) => $s['learning_time_months'] <= 3)
            ->values()
            ->toArray();
    }
    
    protected function getMediumTerm(array $skills): array
    {
        return collect($skills)
            ->filter(fn($s) => $s['learning_time_months'] > 3 && $s['learning_time_months'] <= 6)
            ->values()
            ->toArray();
    }
    
    protected function getLongTerm(array $skills): array
    {
        return collect($skills)
            ->filter(fn($s) => $s['learning_time_months'] > 6)
            ->values()
            ->toArray();
    }
    
    protected function checkMilestone(float $percent): ?string
    {
        if ($percent >= 100) return 'complete';
        if ($percent >= 75) return '75_percent';
        if ($percent >= 50) return '50_percent';
        if ($percent >= 25) return '25_percent';
        return null;
    }
    
    protected function getNextSkill(PredictedCareerPath $path, array $remaining): ?string
    {
        $details = $path->skill_gap_details ?? [];
        
        foreach ($details as $detail) {
            if (in_array($detail['skill'], $remaining)) {
                return $detail['skill'];
            }
        }
        
        return $remaining[0] ?? null;
    }
    
    protected function searchCoursera(string $skill): array
    {
        // Placeholder - would integrate with Coursera API
        return [
            [
                'platform' => 'Coursera',
                'title' => "{$skill} Fundamentals",
                'url' => "https://coursera.org/search?query=" . urlencode($skill),
                'provider' => 'University Partner',
                'rating' => 4.5,
                'duration_hours' => 40,
                'price' => 49.00,
                'level' => 'intermediate',
            ]
        ];
    }
    
    protected function searchUdemy(string $skill): array
    {
        // Placeholder - would integrate with Udemy API
        return [
            [
                'platform' => 'Udemy',
                'title' => "Complete {$skill} Bootcamp",
                'url' => "https://udemy.com/topic/" . strtolower(str_replace(' ', '-', $skill)),
                'provider' => 'Expert Instructor',
                'rating' => 4.6,
                'duration_hours' => 30,
                'price' => 84.99,
                'level' => 'beginner',
            ]
        ];
    }
    
    protected function searchLinkedInLearning(string $skill): array
    {
        // Placeholder - would integrate with LinkedIn Learning API
        return [
            [
                'platform' => 'LinkedIn Learning',
                'title' => "{$skill} Essential Training",
                'url' => "https://linkedin.com/learning/search?keywords=" . urlencode($skill),
                'provider' => 'LinkedIn Expert',
                'rating' => 4.7,
                'duration_hours' => 20,
                'price' => 0.00, // Subscription-based
                'level' => 'intermediate',
            ]
        ];
    }
    
    protected function rankCourses(array $courses, User $user, string $skill): array
    {
        return collect($courses)->map(function ($course) use ($user, $skill) {
            $course['relevance_score'] = $this->calculateRelevance($course, $user, $skill);
            return $course;
        })->sortByDesc('relevance_score')->values()->toArray();
    }
    
    protected function calculateRelevance(array $course, User $user, string $skill): float
    {
        $score = 0;
        
        // Rating weight
        $score += ($course['rating'] / 5) * 40;
        
        // Price weight (lower is better for users)
        $score += ($course['price'] == 0 ? 20 : max(0, 20 - ($course['price'] / 100)));
        
        // Duration weight (moderate duration preferred)
        $idealDuration = 30;
        $score += max(0, 20 - abs($course['duration_hours'] - $idealDuration));
        
        // Platform reputation
        $platformScores = ['Coursera' => 20, 'LinkedIn Learning' => 18, 'Udemy' => 15];
        $score += $platformScores[$course['platform']] ?? 10;
        
        return min(100, $score);
    }
    
    protected function saveRecommendations(User $user, PredictedCareerPath $path, string $skill, array $courses): void
    {
        foreach (array_slice($courses, 0, 5) as $course) {
            LearningRecommendation::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'predicted_career_path_id' => $path->id,
                    'skill_name' => $skill,
                    'course_url' => $course['url'],
                ],
                [
                    'platform' => $course['platform'],
                    'course_title' => $course['title'],
                    'course_provider' => $course['provider'],
                    'course_rating' => $course['rating'],
                    'course_duration_hours' => $course['duration_hours'],
                    'course_price' => $course['price'],
                    'course_level' => $course['level'],
                    'relevance_score' => $course['relevance_score'],
                    'priority' => 5,
                    'recommendation_reason' => 'skill_gap',
                ]
            );
        }
    }
    
    protected function getIndustryBenchmark(string $role, string $industry): array
    {
        // Placeholder - would query benchmark database
        return [
            'required' => ['Communication', 'Leadership', 'Technical Skills'],
            'preferred' => ['Project Management', 'Strategic Planning'],
            'nice_to_have' => ['Public Speaking', 'Negotiation'],
        ];
    }
    
    protected function getCompetitiveLevel(float $percent): string
    {
        if ($percent >= 90) return 'highly_competitive';
        if ($percent >= 70) return 'competitive';
        if ($percent >= 50) return 'developing';
        return 'needs_improvement';
    }
    
    protected function getBenchmarkRecommendations(float $percent, array $missing): array
    {
        if ($percent >= 90) {
            return ['You exceed industry standards. Consider mentoring others.'];
        } elseif ($percent >= 70) {
            return ["Focus on acquiring: " . implode(', ', array_slice($missing, 0, 3))];
        } else {
            return [
                "Priority skills needed: " . implode(', ', array_slice($missing, 0, 5)),
                "Consider intensive training program",
            ];
        }
    }
}
