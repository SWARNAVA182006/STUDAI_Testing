<?php

namespace App\Services;

use App\Models\Company;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompanyInsightsService
{
    protected $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Generate comprehensive company insights
     */
    public function generateInsights(Company $company)
    {
        $cacheKey = 'company_insights_' . $company->id;
        
        return Cache::remember($cacheKey, 86400, function () use ($company) {
            return [
                'overview' => $this->generateOverview($company),
                'culture_analysis' => $this->analyzeCulture($company),
                'growth_potential' => $this->assessGrowthPotential($company),
                'work_life_balance' => $this->estimateWorkLifeBalance($company),
                'career_opportunities' => $this->identifyCareerOpportunities($company),
                'technology_stack' => $this->analyzeTechStack($company),
                'competitive_advantages' => $this->identifyCompetitiveAdvantages($company),
                'employee_sentiment' => $this->calculateEmployeeSentiment($company),
            ];
        });
    }
    
    /**
     * Generate AI-powered company overview
     */
    protected function generateOverview(Company $company)
    {
        $prompt = "Analyze this company and provide a brief professional overview (2-3 sentences):\n\n";
        $prompt .= "Company: {$company->name}\n";
        $prompt .= "Industry: {$company->industry}\n";
        $prompt .= "Size: {$company->company_size} employees\n";
        $prompt .= "Founded: {$company->founded_year}\n";
        $prompt .= "Description: {$company->description}\n\n";
        $prompt .= "Focus on: company mission, market position, and what makes them unique.";
        
        $systemPrompt = "You are a company research analyst. Provide concise, professional insights.";
        
        try {
            return $this->aiService->generateText($prompt, $systemPrompt);
        } catch (\Exception $e) {
            return $company->description ?? 'No overview available.';
        }
    }
    
    /**
     * Analyze company culture
     */
    protected function analyzeCulture(Company $company)
    {
        if (!$company->description && !$company->benefits) {
            return [
                'score' => 50,
                'traits' => [],
                'summary' => 'Limited culture information available.',
            ];
        }
        
        $prompt = "Analyze this company's culture based on the following information:\n\n";
        $prompt .= "Company: {$company->name}\n";
        $prompt .= "Industry: {$company->industry}\n";
        $prompt .= "Description: {$company->description}\n";
        
        if ($company->benefits) {
            $prompt .= "Benefits: " . implode(', ', $company->benefits) . "\n";
        }
        
        $prompt .= "\nProvide as JSON:\n";
        $prompt .= "1. culture_score (0-100)\n";
        $prompt .= "2. traits (array of 3-5 culture traits like 'innovative', 'collaborative', etc.)\n";
        $prompt .= "3. summary (2 sentences about work environment)";
        
        $systemPrompt = "You are a workplace culture analyst. Return only valid JSON.";
        
        try {
            $response = $this->aiService->generateText($prompt, $systemPrompt);
            $analysis = json_decode($response, true);
            
            return [
                'score' => $analysis['culture_score'] ?? 70,
                'traits' => $analysis['traits'] ?? ['Professional', 'Growth-oriented'],
                'summary' => $analysis['summary'] ?? 'Dynamic workplace environment.',
            ];
        } catch (\Exception $e) {
            return [
                'score' => 70,
                'traits' => ['Professional', 'Growth-oriented'],
                'summary' => 'Active hiring company with growth opportunities.',
            ];
        }
    }
    
    /**
     * Assess growth potential
     */
    protected function assessGrowthPotential(Company $company)
    {
        $score = 50;
        $indicators = [];
        
        // Active job postings
        $activeJobs = $company->jobs()->where('status', 'active')->count();
        if ($activeJobs > 10) {
            $score += 20;
            $indicators[] = 'Actively hiring across multiple positions';
        } elseif ($activeJobs > 5) {
            $score += 10;
            $indicators[] = 'Growing team with multiple openings';
        }
        
        // Company age
        $age = now()->year - ($company->founded_year ?? now()->year);
        if ($age >= 2 && $age <= 5) {
            $score += 15;
            $indicators[] = 'Early-stage company with growth trajectory';
        } elseif ($age > 5) {
            $score += 10;
            $indicators[] = 'Established company with proven track record';
        }
        
        // Company size
        $sizeScores = [
            '1-10' => 10,
            '11-50' => 15,
            '51-200' => 20,
            '201-500' => 15,
            '501-1000' => 10,
            '1000+' => 5,
        ];
        $score += $sizeScores[$company->company_size] ?? 10;
        
        // Technology stack (indicates innovation)
        if ($company->tech_stack && count($company->tech_stack) > 5) {
            $score += 10;
            $indicators[] = 'Modern technology stack';
        }
        
        return [
            'score' => min(100, $score),
            'level' => $this->getGrowthLevel($score),
            'indicators' => $indicators,
        ];
    }
    
    /**
     * Get growth level label
     */
    protected function getGrowthLevel($score)
    {
        if ($score >= 80) return 'High Growth';
        if ($score >= 60) return 'Moderate Growth';
        if ($score >= 40) return 'Stable';
        return 'Early Stage';
    }
    
    /**
     * Estimate work-life balance
     */
    protected function estimateWorkLifeBalance(Company $company)
    {
        $score = 50;
        $factors = [];
        
        // Check benefits
        $benefits = $company->benefits ?? [];
        $positiveIndicators = [
            'flexible hours' => 10,
            'remote work' => 10,
            'work from home' => 10,
            'unlimited pto' => 15,
            'paid time off' => 10,
            'mental health' => 10,
            'wellness' => 5,
            'gym' => 5,
        ];
        
        foreach ($benefits as $benefit) {
            foreach ($positiveIndicators as $keyword => $points) {
                if (stripos($benefit, $keyword) !== false) {
                    $score += $points;
                    $factors[] = ucfirst($keyword);
                }
            }
        }
        
        // Check job postings for remote work
        $remoteJobs = $company->jobs()->where('work_mode', 'remote')->count();
        $totalJobs = $company->jobs()->count();
        
        if ($totalJobs > 0 && ($remoteJobs / $totalJobs) > 0.5) {
            $score += 15;
            $factors[] = 'Remote-friendly culture';
        }
        
        // Industry adjustments
        $balancedIndustries = ['tech', 'software', 'saas', 'startup'];
        foreach ($balancedIndustries as $industry) {
            if (stripos($company->industry ?? '', $industry) !== false) {
                $score += 5;
                break;
            }
        }
        
        return [
            'score' => min(100, $score),
            'rating' => $this->getBalanceRating($score),
            'factors' => array_unique($factors),
        ];
    }
    
    /**
     * Get balance rating
     */
    protected function getBalanceRating($score)
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Average';
        return 'Limited Information';
    }
    
    /**
     * Identify career opportunities
     */
    protected function identifyCareerOpportunities(Company $company)
    {
        $opportunities = [];
        
        // Analyze job diversity
        $jobsByLevel = DB::table('jobs')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->select('experience_level', DB::raw('count(*) as count'))
            ->groupBy('experience_level')
            ->pluck('count', 'experience_level');
        
        if ($jobsByLevel->count() > 2) {
            $opportunities[] = [
                'type' => 'Career Progression',
                'description' => 'Multiple career levels available within the company',
            ];
        }
        
        // Analyze departments
        $uniqueTitles = $company->jobs()
            ->where('status', 'active')
            ->distinct('title')
            ->count();
        
        if ($uniqueTitles > 10) {
            $opportunities[] = [
                'type' => 'Role Diversity',
                'description' => 'Wide variety of roles across different functions',
            ];
        }
        
        // Check for training/development benefits
        $benefits = $company->benefits ?? [];
        foreach ($benefits as $benefit) {
            if (preg_match('/(training|learning|development|education|course)/i', $benefit)) {
                $opportunities[] = [
                    'type' => 'Learning & Development',
                    'description' => 'Investment in employee skill development',
                ];
                break;
            }
        }
        
        // Company size factor
        if (in_array($company->company_size, ['51-200', '201-500', '501-1000'])) {
            $opportunities[] = [
                'type' => 'Growth Potential',
                'description' => 'Mid-size company with room for career advancement',
            ];
        }
        
        return $opportunities;
    }
    
    /**
     * Analyze technology stack
     */
    protected function analyzeTechStack(Company $company)
    {
        if (!$company->tech_stack) {
            return [
                'technologies' => [],
                'modernity_score' => 50,
                'categories' => [],
            ];
        }
        
        $techStack = $company->tech_stack;
        $categories = $this->categorizeTechnologies($techStack);
        $modernityScore = $this->calculateModernityScore($techStack);
        
        return [
            'technologies' => $techStack,
            'modernity_score' => $modernityScore,
            'categories' => $categories,
            'trending' => $this->identifyTrendingTech($techStack),
        ];
    }
    
    /**
     * Categorize technologies
     */
    protected function categorizeTechnologies($technologies)
    {
        $categories = [
            'frontend' => ['react', 'vue', 'angular', 'nextjs', 'svelte'],
            'backend' => ['laravel', 'django', 'express', 'spring', 'rails'],
            'mobile' => ['react native', 'flutter', 'swift', 'kotlin'],
            'database' => ['postgresql', 'mysql', 'mongodb', 'redis'],
            'cloud' => ['aws', 'azure', 'gcp', 'docker', 'kubernetes'],
            'ai/ml' => ['tensorflow', 'pytorch', 'scikit-learn', 'openai'],
        ];
        
        $result = [];
        
        foreach ($technologies as $tech) {
            $techLower = strtolower($tech);
            foreach ($categories as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($techLower, $keyword) !== false) {
                        if (!isset($result[$category])) {
                            $result[$category] = [];
                        }
                        $result[$category][] = $tech;
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate technology modernity score
     */
    protected function calculateModernityScore($technologies)
    {
        $modernTech = [
            'react', 'vue', 'nextjs', 'typescript', 'graphql',
            'docker', 'kubernetes', 'aws', 'serverless',
            'microservices', 'ai', 'ml', 'blockchain'
        ];
        
        $score = 50;
        foreach ($technologies as $tech) {
            foreach ($modernTech as $modern) {
                if (stripos($tech, $modern) !== false) {
                    $score += 5;
                }
            }
        }
        
        return min(100, $score);
    }
    
    /**
     * Identify trending technologies
     */
    protected function identifyTrendingTech($technologies)
    {
        $trending = ['ai', 'ml', 'nextjs', 'typescript', 'graphql', 'kubernetes'];
        $found = [];
        
        foreach ($technologies as $tech) {
            foreach ($trending as $trendingTech) {
                if (stripos($tech, $trendingTech) !== false) {
                    $found[] = $tech;
                }
            }
        }
        
        return array_unique($found);
    }
    
    /**
     * Identify competitive advantages
     */
    protected function identifyCompetitiveAdvantages(Company $company)
    {
        $advantages = [];
        
        // Verification status
        if ($company->is_verified) {
            $advantages[] = 'Verified employer';
        }
        
        // Featured status
        if ($company->is_featured) {
            $advantages[] = 'Premium employer partner';
        }
        
        // High culture rating
        if ($company->culture_rating && $company->culture_rating >= 4.0) {
            $advantages[] = "High employee satisfaction ({$company->culture_rating}/5.0)";
        }
        
        // Benefits analysis
        $benefits = $company->benefits ?? [];
        if (count($benefits) > 10) {
            $advantages[] = 'Comprehensive benefits package';
        }
        
        // Company size sweet spot
        if (in_array($company->company_size, ['51-200', '201-500'])) {
            $advantages[] = 'Optimal company size for growth and stability';
        }
        
        // Industry leadership
        $industryKeywords = ['leader', 'leading', 'top', 'pioneer', 'innovator'];
        foreach ($industryKeywords as $keyword) {
            if (stripos($company->description ?? '', $keyword) !== false) {
                $advantages[] = 'Industry leadership position';
                break;
            }
        }
        
        return $advantages;
    }
    
    /**
     * Calculate employee sentiment
     */
    protected function calculateEmployeeSentiment(Company $company)
    {
        // This would integrate with reviews if available
        // For now, we'll estimate based on available data
        
        $score = 70; // Default neutral-positive
        
        // Culture rating
        if ($company->culture_rating) {
            $score = ($company->culture_rating / 5.0) * 100;
        }
        
        // Application/view ratio (high ratio might indicate good reputation)
        $jobs = $company->jobs()->where('status', 'active')->get();
        if ($jobs->count() > 0) {
            $avgApplications = $jobs->avg('applications_count');
            $avgViews = $jobs->avg('views');
            
            if ($avgViews > 0) {
                $conversionRate = ($avgApplications / $avgViews) * 100;
                if ($conversionRate > 10) {
                    $score += 10;
                }
            }
        }
        
        return [
            'score' => min(100, round($score)),
            'sentiment' => $this->getSentimentLabel($score),
            'confidence' => $company->culture_rating ? 'High' : 'Estimated',
        ];
    }
    
    /**
     * Get sentiment label
     */
    protected function getSentimentLabel($score)
    {
        if ($score >= 80) return 'Very Positive';
        if ($score >= 60) return 'Positive';
        if ($score >= 40) return 'Neutral';
        if ($score >= 20) return 'Mixed';
        return 'Negative';
    }
    
    /**
     * Analyze employee sentiment from reviews
     */
    public function analyzeSentiment($reviews)
    {
        if ($reviews->isEmpty()) {
            return [
                'overall_sentiment' => 'neutral',
                'positive_count' => 0,
                'negative_count' => 0,
                'neutral_count' => 0,
                'top_positives' => [],
                'top_concerns' => [],
            ];
        }
        
        $sentiments = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
        ];
        
        foreach ($reviews as $review) {
            $rating = $review->rating ?? 3;
            if ($rating >= 4) {
                $sentiments['positive']++;
            } elseif ($rating <= 2) {
                $sentiments['negative']++;
            } else {
                $sentiments['neutral']++;
            }
        }
        
        return [
            'overall_sentiment' => $this->determineOverallSentiment($sentiments),
            'positive_count' => $sentiments['positive'],
            'negative_count' => $sentiments['negative'],
            'neutral_count' => $sentiments['neutral'],
            'top_positives' => $this->extractTopPositives($reviews),
            'top_concerns' => $this->extractTopConcerns($reviews),
        ];
    }
    
    /**
     * Determine overall sentiment
     */
    protected function determineOverallSentiment($sentiments)
    {
        $total = array_sum($sentiments);
        if ($total === 0) return 'neutral';
        
        $positiveRatio = $sentiments['positive'] / $total;
        
        if ($positiveRatio >= 0.6) return 'positive';
        if ($positiveRatio <= 0.3) return 'negative';
        return 'mixed';
    }
    
    /**
     * Extract top positives from reviews
     */
    protected function extractTopPositives($reviews)
    {
        // Extract common positive themes
        $positives = [];
        
        foreach ($reviews->where('rating', '>=', 4) as $review) {
            if ($review->pros) {
                $positives[] = $review->pros;
            }
        }
        
        return array_slice($positives, 0, 3);
    }
    
    /**
     * Extract top concerns from reviews
     */
    protected function extractTopConcerns($reviews)
    {
        $concerns = [];
        
        foreach ($reviews->where('rating', '<=', 2) as $review) {
            if ($review->cons) {
                $concerns[] = $review->cons;
            }
        }
        
        return array_slice($concerns, 0, 3);
    }
}
