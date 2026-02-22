<?php

namespace App\Services\AI\Scout;

use App\Models\Company;
use App\Models\HiringPattern;
use App\Models\Application;
use App\Models\SuccessIndicator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class HiringPatternAnalyzerService
{
    private const CACHE_TTL = 43200; // 12 hours
    private const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    private const MIN_HIRES_FOR_ANALYSIS = 5;

    public function analyzeHiringPatterns(int $companyId, ?int $jobId = null): array
    {
        $cacheKey = "hiring_patterns_{$companyId}" . ($jobId ? "_{$jobId}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $jobId) {
            $company = Company::findOrFail($companyId);
            
            // Gather hiring data
            $hiringData = $this->gatherHiringData($company, $jobId);
            
            if ($hiringData['total_hires'] < self::MIN_HIRES_FOR_ANALYSIS) {
                return [
                    'success' => false,
                    'message' => "Insufficient data: Minimum " . self::MIN_HIRES_FOR_ANALYSIS . " hires required",
                ];
            }

            try {
                // Analyze patterns with GPT-4
                $sourceEffectiveness = $this->analyzeSourceEffectiveness($hiringData);
                $successPatterns = $this->identifySuccessPatterns($hiringData);
                $failurePatterns = $this->identifyFailurePatterns($hiringData);
                $retentionInsights = $this->analyzeRetentionPatterns($hiringData);
                $aiRecommendations = $this->generateHiringRecommendations($hiringData, $successPatterns, $failurePatterns);

                return [
                    'success' => true,
                    'source_effectiveness' => $sourceEffectiveness,
                    'successful_hire_characteristics' => $successPatterns['characteristics'],
                    'top_performer_traits' => $successPatterns['top_traits'],
                    'unsuccessful_hire_patterns' => $failurePatterns['patterns'],
                    'quick_departure_indicators' => $failurePatterns['departure_indicators'],
                    'retention_insights' => $retentionInsights,
                    'ai_recommendations' => $aiRecommendations,
                    'confidence_score' => $this->calculateConfidenceScore($hiringData),
                    'total_hires_analyzed' => $hiringData['total_hires'],
                ];

            } catch (\Exception $e) {
                Log::error('Hiring Pattern Analysis Failed', [
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

    private function gatherHiringData(Company $company, ?int $jobId): array
    {
        $query = Application::where('company_id', $company->id)
            ->where('status', 'hired')
            ->with(['user', 'job']);

        if ($jobId) {
            $query->where('job_id', $jobId);
        }

        $hires = $query->get();

        $successIndicators = SuccessIndicator::where('company_id', $company->id)
            ->get()
            ->keyBy('user_id');

        return [
            'company_name' => $company->name,
            'total_hires' => $hires->count(),
            'hires_by_source' => $this->groupBySource($hires),
            'timeline_metrics' => $this->calculateTimelineMetrics($hires),
            'performance_data' => $this->mapPerformanceData($hires, $successIndicators),
            'retention_data' => $this->calculateRetentionData($hires, $successIndicators),
            'skill_patterns' => $this->extractHireSkillPatterns($hires, $successIndicators),
        ];
    }

    private function analyzeSourceEffectiveness(array $hiringData): array
    {
        $prompt = <<<PROMPT
Analyze the effectiveness of different hiring sources based on this data.

**Hiring Sources:**
{$this->formatHiresBySource($hiringData['hires_by_source'])}

**Performance by Source:**
{$this->formatPerformanceBySource($hiringData['performance_data'])}

**Retention by Source:**
{$this->formatRetentionBySource($hiringData['retention_data'])}

Return JSON ranking sources by effectiveness (0-100 score):
{
  "sources": [
    {"source": "Employee Referrals", "score": 95, "reasoning": "Highest retention and performance"},
    {"source": "LinkedIn", "score": 75, "reasoning": "Good quality but longer hiring time"},
    ...
  ],
  "best_performing_source": "Employee Referrals"
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a recruitment analytics expert specializing in hiring source optimization.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1200,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function identifySuccessPatterns(array $hiringData): array
    {
        $topPerformers = collect($hiringData['performance_data'])
            ->filter(fn($p) => ($p['performance_rating'] ?? 0) >= 4.0)
            ->values()
            ->toArray();

        if (empty($topPerformers)) {
            return [
                'characteristics' => [],
                'top_traits' => [],
            ];
        }

        $prompt = <<<PROMPT
Analyze successful hires to identify common success patterns.

**Top Performer Data ({count($topPerformers)} employees):**
{$this->formatTopPerformerData($topPerformers)}

**Skill Patterns:**
{$this->formatSkillPatterns($hiringData['skill_patterns'], 'top_performers')}

Return JSON with success characteristics and top traits:
{
  "characteristics": [
    "Strong technical foundation in core skills",
    "Previous experience in similar industry",
    "High collaboration scores in interviews",
    ...
  ],
  "top_traits": [
    {"trait": "Problem-solving ability", "prevalence": "92%"},
    {"trait": "Self-directed learning", "prevalence": "85%"},
    ...
  ]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a talent assessment expert specializing in success pattern recognition.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? ['characteristics' => [], 'top_traits' => []];
    }

    private function identifyFailurePatterns(array $hiringData): array
    {
        $underperformers = collect($hiringData['performance_data'])
            ->filter(fn($p) => ($p['performance_rating'] ?? 0) < 3.0)
            ->values()
            ->toArray();

        $quickDepartures = collect($hiringData['retention_data'])
            ->filter(fn($r) => ($r['tenure_months'] ?? 12) < 6)
            ->values()
            ->toArray();

        if (empty($underperformers) && empty($quickDepartures)) {
            return [
                'patterns' => [],
                'departure_indicators' => [],
            ];
        }

        $prompt = <<<PROMPT
Analyze unsuccessful hires and early departures to identify warning patterns.

**Underperformers ({count($underperformers)}):**
{$this->formatUnderperformerData($underperformers)}

**Quick Departures ({count($quickDepartures)}):**
{$this->formatQuickDepartureData($quickDepartures)}

Return JSON with failure patterns and departure indicators:
{
  "patterns": [
    "Lack of relevant industry experience",
    "Weak cultural fit during interviews",
    "Overqualified for position",
    ...
  ],
  "departure_indicators": [
    {"indicator": "Hired from competitor with different culture", "frequency": "60%"},
    {"indicator": "Mismatch in work style expectations", "frequency": "45%"},
    ...
  ]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an HR analytics expert specializing in turnover and performance issues.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? ['patterns' => [], 'departure_indicators' => []];
    }

    private function analyzeRetentionPatterns(array $hiringData): array
    {
        $retentionBySource = [];
        foreach ($hiringData['retention_data'] as $retention) {
            $source = $retention['hire_source'] ?? 'Unknown';
            if (!isset($retentionBySource[$source])) {
                $retentionBySource[$source] = ['total' => 0, 'retained' => 0];
            }
            $retentionBySource[$source]['total']++;
            if (($retention['tenure_months'] ?? 0) >= 12) {
                $retentionBySource[$source]['retained']++;
            }
        }

        $insights = [];
        foreach ($retentionBySource as $source => $data) {
            $rate = $data['total'] > 0 ? round(($data['retained'] / $data['total']) * 100, 1) : 0;
            $insights[$source] = [
                'retention_rate' => $rate,
                'sample_size' => $data['total'],
            ];
        }

        return $insights;
    }

    private function generateHiringRecommendations(array $hiringData, array $successPatterns, array $failurePatterns): string
    {
        $prompt = <<<PROMPT
Generate specific, actionable hiring recommendations based on this analysis.

**Success Patterns:**
{$this->formatArray($successPatterns['characteristics'] ?? [])}

**Failure Patterns:**
{$this->formatArray($failurePatterns['patterns'] ?? [])}

**Current Metrics:**
- Total Hires Analyzed: {$hiringData['total_hires']}
- Top Sources: {$this->formatTopSources($hiringData['hires_by_source'])}

Provide 5-8 specific recommendations to improve hiring outcomes. Focus on:
1. Which hiring sources to prioritize
2. Red flags to watch for
3. Interview/assessment improvements
4. Candidate profile refinements

Return as a clear, bulleted text summary.
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a strategic HR consultant providing actionable hiring recommendations.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
            'max_tokens' => 800,
        ]);

        return $response->choices[0]->message->content ?? 'No recommendations generated.';
    }

    private function calculateConfidenceScore(array $hiringData): int
    {
        $score = 50;

        // More hires = higher confidence
        if ($hiringData['total_hires'] >= 50) $score += 30;
        elseif ($hiringData['total_hires'] >= 20) $score += 20;
        elseif ($hiringData['total_hires'] >= 10) $score += 10;

        // Performance data availability
        $withPerformance = collect($hiringData['performance_data'])->filter(fn($p) => isset($p['performance_rating']))->count();
        if ($withPerformance >= $hiringData['total_hires'] * 0.7) $score += 15;
        elseif ($withPerformance >= $hiringData['total_hires'] * 0.4) $score += 10;

        // Retention data
        $withRetention = collect($hiringData['retention_data'])->filter(fn($r) => isset($r['tenure_months']))->count();
        if ($withRetention >= $hiringData['total_hires'] * 0.8) $score += 5;

        return min(100, $score);
    }

    // Helper methods
    private function groupBySource($hires): array
    {
        return $hires->groupBy(fn($hire) => $hire->application_source ?? 'Direct Application')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    private function calculateTimelineMetrics($hires): array
    {
        $timelines = $hires->filter(fn($h) => isset($h->applied_at, $h->hired_at))
            ->map(fn($h) => $h->applied_at->diffInDays($h->hired_at));

        return [
            'avg_time_to_hire' => $timelines->isNotEmpty() ? round($timelines->average(), 1) : 0,
            'min_time' => $timelines->isNotEmpty() ? $timelines->min() : 0,
            'max_time' => $timelines->isNotEmpty() ? $timelines->max() : 0,
        ];
    }

    private function mapPerformanceData($hires, $successIndicators): array
    {
        return $hires->map(function ($hire) use ($successIndicators) {
            $indicator = $successIndicators->get($hire->user_id);
            return [
                'user_id' => $hire->user_id,
                'hire_source' => $hire->application_source ?? 'Direct',
                'performance_rating' => $indicator->performance_rating ?? null,
                'employee_type' => $indicator->employee_type ?? null,
                'skills' => array_merge(
                    $indicator->technical_skills ?? [],
                    $indicator->soft_skills ?? []
                ),
            ];
        })->toArray();
    }

    private function calculateRetentionData($hires, $successIndicators): array
    {
        return $hires->map(function ($hire) use ($successIndicators) {
            $indicator = $successIndicators->get($hire->user_id);
            return [
                'user_id' => $hire->user_id,
                'hire_source' => $hire->application_source ?? 'Direct',
                'tenure_months' => $indicator->tenure_months ?? 0,
                'still_employed' => $indicator ? true : false,
            ];
        })->toArray();
    }

    private function extractHireSkillPatterns($hires, $successIndicators): array
    {
        $topPerformerSkills = [];
        $averageSkills = [];

        foreach ($hires as $hire) {
            $indicator = $successIndicators->get($hire->user_id);
            if (!$indicator) continue;

            $skills = array_merge(
                $indicator->technical_skills ?? [],
                $indicator->soft_skills ?? []
            );

            if ($indicator->employee_type === 'top_performer') {
                foreach ($skills as $skill) {
                    $topPerformerSkills[$skill] = ($topPerformerSkills[$skill] ?? 0) + 1;
                }
            } else {
                foreach ($skills as $skill) {
                    $averageSkills[$skill] = ($averageSkills[$skill] ?? 0) + 1;
                }
            }
        }

        arsort($topPerformerSkills);
        arsort($averageSkills);

        return [
            'top_performers' => array_slice($topPerformerSkills, 0, 15, true),
            'average_performers' => array_slice($averageSkills, 0, 15, true),
        ];
    }

    // Formatting helpers
    private function formatHiresBySource(array $sources): string
    {
        $lines = [];
        foreach ($sources as $source => $count) {
            $lines[] = "- {$source}: {$count} hires";
        }
        return implode("\n", $lines);
    }

    private function formatPerformanceBySource(array $data): string
    {
        $bySource = [];
        foreach ($data as $item) {
            if (!isset($item['performance_rating'])) continue;
            $source = $item['hire_source'];
            if (!isset($bySource[$source])) {
                $bySource[$source] = ['total' => 0, 'sum' => 0];
            }
            $bySource[$source]['total']++;
            $bySource[$source]['sum'] += $item['performance_rating'];
        }

        $lines = [];
        foreach ($bySource as $source => $stats) {
            $avg = $stats['total'] > 0 ? round($stats['sum'] / $stats['total'], 2) : 0;
            $lines[] = "- {$source}: Avg Rating {$avg}/5.0";
        }
        return implode("\n", $lines) ?: 'No data';
    }

    private function formatRetentionBySource(array $data): string
    {
        $bySource = [];
        foreach ($data as $item) {
            $source = $item['hire_source'];
            if (!isset($bySource[$source])) {
                $bySource[$source] = ['total' => 0, 'retained' => 0];
            }
            $bySource[$source]['total']++;
            if (($item['tenure_months'] ?? 0) >= 12) {
                $bySource[$source]['retained']++;
            }
        }

        $lines = [];
        foreach ($bySource as $source => $stats) {
            $rate = $stats['total'] > 0 ? round(($stats['retained'] / $stats['total']) * 100, 1) : 0;
            $lines[] = "- {$source}: {$rate}% retention";
        }
        return implode("\n", $lines) ?: 'No data';
    }

    private function formatTopPerformerData(array $performers): string
    {
        return count($performers) . " top performers analyzed";
    }

    private function formatUnderperformerData(array $performers): string
    {
        return count($performers) . " underperformers analyzed";
    }

    private function formatQuickDepartureData(array $departures): string
    {
        return count($departures) . " employees left within 6 months";
    }

    private function formatSkillPatterns(array $patterns, string $type): string
    {
        $skills = $patterns[$type] ?? [];
        if (empty($skills)) return 'No data';

        $top5 = array_slice($skills, 0, 5, true);
        $lines = [];
        foreach ($top5 as $skill => $count) {
            $lines[] = "- {$skill} ({$count})";
        }
        return implode("\n", $lines);
    }

    private function formatArray(array $arr): string
    {
        return empty($arr) ? 'None' : "- " . implode("\n- ", $arr);
    }

    private function formatTopSources(array $sources): string
    {
        arsort($sources);
        $top3 = array_slice($sources, 0, 3, true);
        $formatted = [];
        foreach ($top3 as $source => $count) {
            $formatted[] = "{$source} ({$count})";
        }
        return implode(', ', $formatted);
    }
}
