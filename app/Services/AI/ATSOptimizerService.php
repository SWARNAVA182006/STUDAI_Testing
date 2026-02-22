<?php

namespace App\Services\AI;

use App\Models\DiscoveredJob;
use App\Services\AI\Concerns\LogsAiUsage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class ATSOptimizerService
{
    use LogsAiUsage;

    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1 (was gpt-5-mini)
    protected const CACHE_TTL = 10800; // 3 hours

    /**
     * Analyze resume content and provide ATS optimization guidance.
     */
    public function analyze(string $resumeContent, DiscoveredJob $job, array $options = []): array
    {
        $options = array_merge([
            'force_refresh' => false,
            'target_score' => 85,
        ], $options);

        $cacheKey = sprintf(
            'ats_optimizer_%d_%s',
            $job->id,
            md5($resumeContent)
        );

        if (!$options['force_refresh'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $jobContext = $this->buildJobContext($job);
        $keywordAnalysis = $this->analyzeKeywords($resumeContent, $jobContext['keywords']);
        $structureScore = $this->scoreStructure($resumeContent);

        $prompt = $this->buildPrompt($resumeContent, $jobContext, $keywordAnalysis, $structureScore);

        $usage = null;
        $structured = null;

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an Applicant Tracking System expert who evaluates resumes for alignment with job postings. Provide actionable, concise recommendations.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.25,
                'max_tokens' => 2200,
            ]);

            $usage = $response->usage ?? null;
            $structured = $this->parseResponse($response->choices[0]->message->content);
        } catch (\Throwable $exception) {
            Log::error('ATS optimization failed', [
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$structured) {
            $structured = $this->fallbackAnalysis($keywordAnalysis, $structureScore);
        }

        $result = [
            'ats_score' => $structured['score'] ?? $structureScore['baseline'],
            'section_scores' => $structured['section_scores'] ?? [],
            'keyword_match' => [
                'matched' => $keywordAnalysis['matched'],
                'missing' => $structured['missing_keywords'] ?? $keywordAnalysis['missing'],
                'coverage_percent' => $keywordAnalysis['coverage_percent'],
            ],
            'formatting_issues' => $structured['formatting_issues'] ?? $structureScore['issues'],
            'recommendations' => $structured['suggested_changes'] ?? [],
            'optimized_resume' => $structured['optimized_resume'] ?? null,
            'warnings' => $structured['warnings'] ?? [],
            'summary' => $structured['summary'] ?? 'Resume analyzed using heuristic scoring.',
        ];

        $this->logAiUsage(
            null,
            'ats_optimizer',
            self::MODEL,
            $usage,
            [
                'job_id' => $job->id,
                'coverage_percent' => $keywordAnalysis['coverage_percent'],
                'baseline_score' => $structureScore['baseline'],
            ]
        );

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    protected function buildPrompt(string $resume, array $job, array $keywordAnalysis, array $structureScore): string
    {
        $jobJson = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $keywordJson = json_encode($keywordAnalysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $structureJson = json_encode($structureScore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resumeExcerpt = Str::limit($resume, 2500, '...');

        return <<<PROMPT
Evaluate the following resume for ATS alignment with the job posting.

JOB CONTEXT:
{$jobJson}

KEYWORD ANALYSIS:
{$keywordJson}

STRUCTURE SCORING:
{$structureJson}

RESUME CONTENT:
"""
{$resumeExcerpt}
"""

Return ONLY JSON with the schema:
{
  "score": 0-100,
  "section_scores": {
    "summary": 0-100,
    "experience": 0-100,
    "skills": 0-100,
    "education": 0-100
  },
  "missing_keywords": ["..."],
  "suggested_changes": [
    {"priority": "high|medium|low", "change": "string", "benefit": "string"}
  ],
  "formatting_issues": ["..."],
  "optimized_resume": "string|null",
  "warnings": ["..."],
  "summary": "string"
}
PROMPT;
    }

    protected function parseResponse(string $content): ?array
    {
        if (preg_match('/\{[\s\S]*\}\s*$/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function fallbackAnalysis(array $keywordAnalysis, array $structureScore): array
    {
        return [
            'score' => round(($structureScore['baseline'] + $keywordAnalysis['coverage_percent']) / 2, 1),
            'section_scores' => $structureScore['section_breakdown'],
            'missing_keywords' => $keywordAnalysis['missing'],
            'suggested_changes' => [
                [
                    'priority' => 'high',
                    'change' => 'Add quantified accomplishments that mirror job requirements.',
                    'benefit' => 'Improves relevancy and keyword matching',
                ],
            ],
            'formatting_issues' => $structureScore['issues'],
            'optimized_resume' => null,
            'warnings' => ['AI fallback used.'],
            'summary' => 'Baseline ATS analysis completed without AI response.',
        ];
    }

    protected function analyzeKeywords(string $resume, array $jobKeywords): array
    {
        $resumeLower = strtolower($resume);
        $matched = [];
        $missing = [];

        foreach ($jobKeywords as $keyword) {
            $needle = strtolower($keyword);
            if (Str::contains($resumeLower, $needle)) {
                $matched[] = $keyword;
            } else {
                $missing[] = $keyword;
            }
        }

        $total = count($jobKeywords);
        $coverage = $total > 0 ? (count($matched) / $total) * 100 : 0;

        return [
            'matched' => array_values($matched),
            'missing' => array_slice($missing, 0, 15),
            'coverage_percent' => round($coverage, 1),
        ];
    }

    protected function scoreStructure(string $resume): array
    {
        $sections = [
            'summary' => '/summary|profile/i',
            'experience' => '/experience|employment/i',
            'skills' => '/skills|competencies/i',
            'education' => '/education|academic/i',
        ];

        $scores = [];
        $issues = [];

        foreach ($sections as $section => $pattern) {
            $scores[$section] = preg_match($pattern, $resume) ? 85 : 55;
            if ($scores[$section] < 60) {
                $issues[] = strtoupper($section) . ' section missing or unclear.';
            }
        }

        if (!preg_match('/\b\d{4}\b/', $resume)) {
            $issues[] = 'No dates detected – add timeline for roles.';
        }

        if (!preg_match('/%|percent|increase|reduced/', strtolower($resume))) {
            $issues[] = 'Add metrics (%, $, #) to quantify achievements.';
        }

        $baseline = round(array_sum($scores) / max(count($scores), 1), 1);

        return [
            'baseline' => $baseline,
            'section_breakdown' => $scores,
            'issues' => $issues,
        ];
    }

    protected function buildJobContext(DiscoveredJob $job): array
    {
        return [
            'title' => $job->title,
            'company' => $job->company_name,
            'keywords' => array_values(array_unique(array_merge(
                Arr::wrap($job->extracted_skills),
                $this->extractKeywords($job->description ?? '')
            ))),
            'description' => Str::limit($job->description ?? '', 1600, '...'),
        ];
    }

    protected function extractKeywords(string $text): array
    {
        $matches = [];
        preg_match_all('/\b[A-Za-z][A-Za-z0-9+\/#&-]{3,}\b/', $text, $matches);

        return array_slice(array_unique(array_map('strtolower', $matches[0] ?? [])), 0, 20);
    }
}
