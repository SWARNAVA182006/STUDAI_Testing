<?php

namespace App\Services\AI;

use App\Models\MarketDisruption;
use App\Models\User;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MarketAnalysisService
{
    use InteractsWithAI;
    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Monitor and detect market disruptions for an industry
     */
    public function monitorIndustry(string $industry): array
    {
        $cacheKey = "market_monitor_{$industry}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($industry) {
            // Gather market data from various sources
            $newsData = $this->fetchIndustryNews($industry);
            $jobMarketData = $this->fetchJobMarketTrends($industry);
            $technologyTrends = $this->fetchTechnologyTrends($industry);
            
            // Use AI to analyze and identify disruptions
            $disruptions = $this->analyzeForDisruptions($industry, [
                'news' => $newsData,
                'job_market' => $jobMarketData,
                'technology' => $technologyTrends,
            ]);
            
            // Save new disruptions to database
            $this->saveDisruptions($industry, $disruptions);
            
            return $disruptions;
        });
    }

    /**
     * Analyze market data to identify disruptions
     */
    protected function analyzeForDisruptions(string $industry, array $marketData): array
    {
        $prompt = <<<PROMPT
Analyze the following market data for the {$industry} industry and identify significant disruptions:

INDUSTRY NEWS:
{$this->formatNewsData($marketData['news'])}

JOB MARKET TRENDS:
{$this->formatJobMarketData($marketData['job_market'])}

TECHNOLOGY TRENDS:
{$this->formatTechnologyData($marketData['technology'])}

Identify disruptions in these categories:
1. Automation (roles being automated)
2. AI Adoption (AI replacing or augmenting roles)
3. Regulatory Changes (new regulations affecting industry)
4. Economic Shifts (market conditions changing demand)
5. Technology Disruption (new technologies creating/eliminating roles)

For each disruption, provide:
- Type (automation/ai_adoption/regulation/economic/technology)
- Title (brief descriptive name)
- Description (2-3 sentences explaining the disruption)
- Affected Roles (array of job titles being impacted)
- Emerging Roles (array of new job titles being created)
- Declining Roles (array of job titles becoming obsolete)
- Required Adaptations (array of changes professionals must make)
- Severity (low/medium/high/critical)
- Timeframe (immediate/short_term/medium_term/long_term)
- Impact Score (0-100, how significantly this affects the industry)

Return ONLY valid JSON array:
[
  {
    "type": "ai_adoption",
    "title": "AI-Powered Development Tools",
    "description": "GitHub Copilot and similar AI coding assistants are being widely adopted...",
    "affected_roles": ["Junior Developer", "QA Engineer"],
    "emerging_roles": ["AI-Assisted Developer", "Prompt Engineer"],
    "declining_roles": ["Manual Code Reviewer"],
    "required_adaptations": ["Learn AI tool integration", "Develop prompt engineering skills"],
    "severity": "high",
    "timeframe": "immediate",
    "impact_score": 85
  }
]
PROMPT;

        try {
            $content = $this->ai(
                $prompt,
                'You are an expert market analyst specializing in workforce disruption analysis.',
                ['temperature' => 0.5]
            );
            
            if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
                $disruptions = json_decode($matches[0], true);
                return $disruptions ?? [];
            }
        } catch (\Exception $e) {
            \Log::error('Market disruption analysis failed: ' . $e->getMessage());
        }
        
        return [];
    }

    /**
     * Fetch industry news from multiple sources
     */
    protected function fetchIndustryNews(string $industry): array
    {
        // Placeholder - would integrate with news APIs (NewsAPI, Google News, etc.)
        return [
            [
                'title' => 'AI Transformation Accelerates in ' . $industry,
                'source' => 'Industry News',
                'date' => now()->subDays(5)->toDateString(),
                'summary' => 'Companies rapidly adopting AI technologies...',
            ],
            [
                'title' => 'Skills Gap Widens in ' . $industry . ' Sector',
                'source' => 'Market Report',
                'date' => now()->subDays(10)->toDateString(),
                'summary' => 'Demand for specialized skills outpacing supply...',
            ],
        ];
    }

    /**
     * Fetch job market trends
     */
    protected function fetchJobMarketTrends(string $industry): array
    {
        // Placeholder - would integrate with job board APIs (LinkedIn, Indeed, Glassdoor)
        return [
            'total_postings' => 15420,
            'trend' => 'increasing',
            'top_skills_demanded' => ['Python', 'Cloud Computing', 'Machine Learning'],
            'average_salary' => 125000,
            'salary_trend' => 'increasing',
            'top_locations' => ['San Francisco', 'New York', 'Seattle'],
            'remote_percentage' => 65,
        ];
    }

    /**
     * Fetch technology adoption trends
     */
    protected function fetchTechnologyTrends(string $industry): array
    {
        // Placeholder - would integrate with tech trend APIs (Stack Overflow, GitHub)
        return [
            'emerging_technologies' => [
                ['name' => 'Large Language Models', 'adoption_rate' => 'rapid'],
                ['name' => 'Kubernetes', 'adoption_rate' => 'steady'],
            ],
            'declining_technologies' => [
                ['name' => 'Legacy Frameworks', 'decline_rate' => 'moderate'],
            ],
        ];
    }

    /**
     * Save disruptions to database
     */
    protected function saveDisruptions(string $industry, array $disruptions): void
    {
        foreach ($disruptions as $disruptionData) {
            // Check if similar disruption already exists
            $existing = MarketDisruption::where('industry', $industry)
                ->where('title', $disruptionData['title'])
                ->where('is_active', true)
                ->first();

            if ($existing) {
                // Update existing disruption
                $existing->update([
                    'description' => $disruptionData['description'],
                    'affected_roles' => $disruptionData['affected_roles'],
                    'emerging_roles' => $disruptionData['emerging_roles'],
                    'declining_roles' => $disruptionData['declining_roles'],
                    'required_adaptations' => $disruptionData['required_adaptations'],
                    'severity' => $disruptionData['severity'],
                    'timeframe' => $disruptionData['timeframe'],
                    'impact_score' => $disruptionData['impact_score'],
                ]);
            } else {
                // Create new disruption
                MarketDisruption::create([
                    'industry' => $industry,
                    'disruption_type' => $disruptionData['type'],
                    'title' => $disruptionData['title'],
                    'description' => $disruptionData['description'],
                    'affected_roles' => $disruptionData['affected_roles'],
                    'emerging_roles' => $disruptionData['emerging_roles'] ?? [],
                    'declining_roles' => $disruptionData['declining_roles'] ?? [],
                    'required_adaptations' => $disruptionData['required_adaptations'] ?? [],
                    'severity' => $disruptionData['severity'],
                    'timeframe' => $disruptionData['timeframe'],
                    'impact_score' => $disruptionData['impact_score'],
                    'detected_at' => now(),
                    'source' => 'AI Analysis',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Assess impact of disruption on specific user
     */
    public function assessUserImpact(User $user, MarketDisruption $disruption): array
    {
        $trajectory = $user->currentTrajectory;
        
        if (!$trajectory) {
            return ['impact_level' => 'unknown', 'recommendations' => []];
        }

        $currentRole = $trajectory->current_role;
        $predictedPaths = $trajectory->predictedPaths()->active()->get();

        $impactLevel = 'none';
        $affectedPaths = [];
        $recommendations = [];

        // Check if current role is affected
        if (in_array($currentRole, $disruption->affected_roles)) {
            $impactLevel = 'high';
            $recommendations[] = 'Your current role is directly impacted by this disruption.';
        }

        // Check if predicted paths are affected
        foreach ($predictedPaths as $path) {
            if (in_array($path->target_role, $disruption->affected_roles)) {
                $affectedPaths[] = $path->target_role;
                $impactLevel = $impactLevel === 'high' ? 'high' : 'medium';
            }
        }

        // Generate recommendations
        if (!empty($disruption->required_adaptations)) {
            $recommendations[] = 'Consider these adaptations: ' . implode(', ', $disruption->required_adaptations);
        }

        if (!empty($disruption->emerging_roles)) {
            $recommendations[] = 'Explore emerging opportunities: ' . implode(', ', $disruption->emerging_roles);
        }

        return [
            'impact_level' => $impactLevel,
            'affected_paths' => $affectedPaths,
            'recommendations' => $recommendations,
            'urgency' => $this->calculateUrgency($disruption),
        ];
    }

    /**
     * Calculate urgency score for disruption
     */
    protected function calculateUrgency(MarketDisruption $disruption): string
    {
        $severityScore = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $timeframeScore = ['long_term' => 1, 'medium_term' => 2, 'short_term' => 3, 'immediate' => 4];

        $severity = $severityScore[$disruption->severity] ?? 2;
        $timeframe = $timeframeScore[$disruption->timeframe] ?? 2;

        $urgencyScore = ($severity + $timeframe) / 2;

        if ($urgencyScore >= 3.5) return 'critical';
        if ($urgencyScore >= 2.5) return 'high';
        if ($urgencyScore >= 1.5) return 'medium';
        return 'low';
    }

    // Helper formatting methods

    protected function formatNewsData(array $news): string
    {
        return collect($news)->map(function ($item) {
            return "- {$item['title']} ({$item['date']}): {$item['summary']}";
        })->implode("\n");
    }

    protected function formatJobMarketData(array $data): string
    {
        $topSkills = implode(', ', $data['top_skills_demanded']);
        
        return <<<DATA
Total Job Postings: {$data['total_postings']}
Trend: {$data['trend']}
Top Skills: {$topSkills}
Average Salary: \${$data['average_salary']}
Remote Work: {$data['remote_percentage']}%
DATA;
    }

    protected function formatTechnologyData(array $data): string
    {
        $emerging = collect($data['emerging_technologies'] ?? [])->map(fn($t) => "{$t['name']} ({$t['adoption_rate']})")->implode(', ');
        $declining = collect($data['declining_technologies'] ?? [])->map(fn($t) => "{$t['name']} ({$t['decline_rate']})")->implode(', ');

        return "Emerging: {$emerging}\nDeclining: {$declining}";
    }
}
