<?php

namespace App\Services\AI\Scout;

use App\Models\Company;
use App\Models\CompanyDNAProfile;
use App\Models\CultureAnalysis;
use App\Models\SuccessIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class CorporateDNADecoderService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1

    public function analyzeCompanyDNA(int $companyId): array
    {
        $cacheKey = "company_dna_analysis_{$companyId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            $company = Company::with(['users', 'jobs', 'applications'])->findOrFail($companyId);
            
            // Gather organizational data
            $organizationalData = $this->gatherOrganizationalData($company);
            
            // Skip if insufficient data
            if ($organizationalData['employee_count'] < 5) {
                return [
                    'success' => false,
                    'message' => 'Insufficient data: Minimum 5 employees required for DNA analysis',
                    'data_completeness' => 0,
                ];
            }

            try {
                // Analyze with GPT-4
                $dnaAnalysis = $this->performDNAAnalysis($organizationalData);
                $culturalDNA = $this->extractCulturalDNA($organizationalData);
                $successTraits = $this->identifySuccessTraits($organizationalData);
                
                // Calculate scores
                $completionScore = $this->calculateCompletionScore($organizationalData);
                $confidenceScore = $this->calculateConfidenceScore($organizationalData);

                return [
                    'success' => true,
                    'cultural_dna' => $culturalDNA,
                    'success_traits' => $successTraits,
                    'work_style_preferences' => $dnaAnalysis['work_style_preferences'],
                    'communication_patterns' => $dnaAnalysis['communication_patterns'],
                    'decision_making_style' => $dnaAnalysis['decision_making_style'],
                    'dna_completeness_score' => $completionScore,
                    'analysis_confidence' => $confidenceScore,
                    'ai_summary' => $dnaAnalysis['summary'],
                    'total_employees_analyzed' => $organizationalData['employee_count'],
                    'total_hires_analyzed' => $organizationalData['hire_count'],
                ];

            } catch (\Exception $e) {
                Log::error('Corporate DNA Analysis Failed', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Analysis failed: ' . $e->getMessage(),
                ];
            }
        });
    }

    private function gatherOrganizationalData(Company $company): array
    {
        $employees = $company->users()->where('account_type', 'job_seeker')->get();
        $topPerformers = SuccessIndicator::where('company_id', $company->id)
            ->where('employee_type', 'top_performer')
            ->get();

        return [
            'company_name' => $company->name,
            'mission_statement' => $company->mission_statement ?? 'Not provided',
            'vision_statement' => $company->vision_statement ?? 'Not provided',
            'core_values' => $company->core_values ?? [],
            'industry' => $company->industry ?? 'Unknown',
            'company_size' => $company->company_size ?? 'Unknown',
            'employee_count' => $employees->count(),
            'hire_count' => $company->applications()->where('status', 'hired')->count(),
            'top_performer_count' => $topPerformers->count(),
            'avg_tenure' => $this->calculateAverageTenure($employees),
            'retention_rate' => $this->calculateRetentionRate($company),
            'promotion_data' => $this->gatherPromotionData($topPerformers),
            'skill_patterns' => $this->extractSkillPatterns($topPerformers),
            'work_style_data' => $this->extractWorkStyleData($topPerformers),
        ];
    }

    private function performDNAAnalysis(array $orgData): array
    {
        $prompt = $this->buildDNAAnalysisPrompt($orgData);

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert organizational psychologist and HR analytics specialist. Analyze company data to decode organizational DNA, cultural patterns, and success factors.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]);

        $analysis = json_decode($response->choices[0]->message->content, true);

        return [
            'work_style_preferences' => $analysis['work_style_preferences'] ?? [],
            'communication_patterns' => $analysis['communication_patterns'] ?? [],
            'decision_making_style' => $analysis['decision_making_style'] ?? 'Unknown',
            'summary' => $analysis['summary'] ?? 'Analysis completed',
        ];
    }

    private function buildDNAAnalysisPrompt(array $orgData): string
    {
        return <<<PROMPT
Analyze this organization's DNA and provide structured insights in JSON format.

**Organization Profile:**
- Company: {$orgData['company_name']}
- Industry: {$orgData['industry']}
- Size: {$orgData['company_size']} ({$orgData['employee_count']} employees analyzed)
- Mission: {$orgData['mission_statement']}
- Vision: {$orgData['vision_statement']}
- Core Values: {$this->formatArray($orgData['core_values'])}

**Performance Metrics:**
- Top Performers: {$orgData['top_performer_count']}
- Average Tenure: {$orgData['avg_tenure']} months
- Retention Rate: {$orgData['retention_rate']}%

**Skill Patterns in Top Performers:**
{$this->formatSkillPatterns($orgData['skill_patterns'])}

**Work Style Observations:**
{$this->formatWorkStyleData($orgData['work_style_data'])}

Return JSON with:
{
  "work_style_preferences": ["autonomous", "collaborative", "structured", etc.],
  "communication_patterns": ["async-first", "meeting-heavy", "documentation-focused", etc.],
  "decision_making_style": "consensus-driven" | "data-driven" | "hierarchical" | "agile",
  "summary": "2-3 sentence organizational DNA summary"
}
PROMPT;
    }

    private function extractCulturalDNA(array $orgData): array
    {
        $prompt = <<<PROMPT
Based on this organizational data, identify 5-8 core cultural DNA traits that define this company's identity.

**Data:**
- Mission: {$orgData['mission_statement']}
- Values: {$this->formatArray($orgData['core_values'])}
- Top Performer Count: {$orgData['top_performer_count']}
- Retention Rate: {$orgData['retention_rate']}%
- Work Styles: {$this->formatWorkStyleData($orgData['work_style_data'])}

Return JSON array of cultural DNA traits with scores 0-100:
[
  {"trait": "Innovation-Driven", "score": 85, "evidence": "High experimentation in top performers"},
  {"trait": "People-First", "score": 92, "evidence": "95% retention, strong collaboration"},
  ...
]
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a cultural anthropologist specializing in organizational culture.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
            'max_tokens' => 800,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function identifySuccessTraits(array $orgData): array
    {
        if ($orgData['top_performer_count'] < 3) {
            return [
                ['trait' => 'Insufficient Data', 'score' => 0, 'prevalence' => '0%'],
            ];
        }

        $prompt = <<<PROMPT
Analyze top performer data to identify key success traits for this organization.

**Top Performer Data:**
- Count: {$orgData['top_performer_count']}
- Skills: {$this->formatSkillPatterns($orgData['skill_patterns'])}
- Work Styles: {$this->formatWorkStyleData($orgData['work_style_data'])}
- Promotion Data: {$this->formatPromotionData($orgData['promotion_data'])}

Return JSON array of 5-10 success traits ranked by importance:
[
  {"trait": "Technical Excellence", "score": 95, "prevalence": "90% of top performers"},
  {"trait": "Cross-Functional Collaboration", "score": 88, "prevalence": "80% of top performers"},
  ...
]
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a talent analytics expert specializing in success pattern identification.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function calculateCompletionScore(array $orgData): int
    {
        $score = 0;

        // Mission/Vision (20 points)
        if ($orgData['mission_statement'] !== 'Not provided') $score += 10;
        if ($orgData['vision_statement'] !== 'Not provided') $score += 10;

        // Core values (10 points)
        if (!empty($orgData['core_values'])) $score += 10;

        // Employee data (30 points)
        if ($orgData['employee_count'] >= 10) $score += 15;
        elseif ($orgData['employee_count'] >= 5) $score += 10;
        
        if ($orgData['top_performer_count'] >= 5) $score += 15;
        elseif ($orgData['top_performer_count'] >= 2) $score += 10;

        // Retention/Tenure (20 points)
        if ($orgData['retention_rate'] > 0) $score += 10;
        if ($orgData['avg_tenure'] > 0) $score += 10;

        // Skill/Work style data (20 points)
        if (!empty($orgData['skill_patterns'])) $score += 10;
        if (!empty($orgData['work_style_data'])) $score += 10;

        return min(100, $score);
    }

    private function calculateConfidenceScore(array $orgData): int
    {
        $confidence = 50; // Base confidence

        // More employees = higher confidence
        if ($orgData['employee_count'] >= 50) $confidence += 20;
        elseif ($orgData['employee_count'] >= 20) $confidence += 15;
        elseif ($orgData['employee_count'] >= 10) $confidence += 10;

        // Top performer data
        if ($orgData['top_performer_count'] >= 10) $confidence += 15;
        elseif ($orgData['top_performer_count'] >= 5) $confidence += 10;

        // Historical data
        if ($orgData['hire_count'] >= 20) $confidence += 10;
        elseif ($orgData['hire_count'] >= 10) $confidence += 5;

        // Data quality
        if ($orgData['retention_rate'] > 0 && $orgData['avg_tenure'] > 0) $confidence += 5;

        return min(100, $confidence);
    }

    // Helper methods
    private function calculateAverageTenure($employees): float
    {
        if ($employees->isEmpty()) return 0;
        
        $totalMonths = $employees->sum(function ($employee) {
            return $employee->created_at ? now()->diffInMonths($employee->created_at) : 0;
        });

        return round($totalMonths / $employees->count(), 1);
    }

    private function calculateRetentionRate(Company $company): float
    {
        $totalHires = $company->applications()->where('status', 'hired')->count();
        if ($totalHires === 0) return 0;

        $activeEmployees = $company->users()->where('account_type', 'job_seeker')->count();
        
        return round(($activeEmployees / max(1, $totalHires)) * 100, 2);
    }

    private function gatherPromotionData($topPerformers): array
    {
        return $topPerformers->map(function ($performer) {
            return [
                'promotions_count' => $performer->promotions_count ?? 0,
                'tenure_months' => $performer->tenure_months ?? 0,
                'promotion_path' => $performer->promotion_path ?? [],
            ];
        })->toArray();
    }

    private function extractSkillPatterns($topPerformers): array
    {
        $allSkills = [];
        foreach ($topPerformers as $performer) {
            $skills = array_merge(
                $performer->technical_skills ?? [],
                $performer->soft_skills ?? []
            );
            foreach ($skills as $skill) {
                $allSkills[$skill] = ($allSkills[$skill] ?? 0) + 1;
            }
        }
        arsort($allSkills);
        return array_slice($allSkills, 0, 20, true);
    }

    private function extractWorkStyleData($topPerformers): array
    {
        return $topPerformers->map(function ($performer) {
            return [
                'work_preferences' => $performer->work_preferences ?? [],
                'communication_style' => $performer->communication_style ?? [],
                'collaboration_score' => $performer->team_collaboration_score ?? 0,
            ];
        })->toArray();
    }

    private function formatArray(array $arr): string
    {
        return empty($arr) ? 'None provided' : implode(', ', $arr);
    }

    private function formatSkillPatterns(array $patterns): string
    {
        if (empty($patterns)) return 'No data';
        
        $formatted = [];
        foreach (array_slice($patterns, 0, 10) as $skill => $count) {
            $formatted[] = "$skill ($count)";
        }
        return implode(', ', $formatted);
    }

    private function formatWorkStyleData(array $data): string
    {
        if (empty($data)) return 'No data';
        
        $collaborationScores = array_column($data, 'collaboration_score');
        $avgCollaboration = !empty($collaborationScores) ? round(array_sum($collaborationScores) / count($collaborationScores)) : 0;
        
        return "Average Collaboration Score: {$avgCollaboration}/100";
    }

    private function formatPromotionData(array $data): string
    {
        if (empty($data)) return 'No data';
        
        $totalPromotions = array_sum(array_column($data, 'promotions_count'));
        $avgPromotions = round($totalPromotions / count($data), 1);
        
        return "Average promotions per top performer: {$avgPromotions}";
    }
}
