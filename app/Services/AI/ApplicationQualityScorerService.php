<?php

namespace App\Services\AI;

use App\Models\DiscoveredJob;
use App\Models\User;
use App\Services\AI\Concerns\LogsAiUsage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class ApplicationQualityScorerService
{
    use LogsAiUsage;

    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1 (was gpt-5-mini)
    protected const CACHE_TTL = 5400; // 90 minutes

    /**
     * Evaluate overall readiness of an application package.
     */
    public function evaluate(
        User $user,
        DiscoveredJob $job,
        array $artifacts,
        array $options = []
    ): array {
        $options = array_merge([
            'target_score' => 80,
            'force_refresh' => false,
        ], $options);

        $normalizedArtifacts = $this->normalizeArtifacts($artifacts);

        $cacheKey = sprintf(
            'application_quality_%d_%d_%s',
            $user->id,
            $job->id,
            md5(json_encode($normalizedArtifacts))
        );

        if (!$options['force_refresh'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $userContext = $this->buildUserContext($user);
        $jobContext = $this->buildJobContext($job);
        $heuristics = $this->buildHeuristics($normalizedArtifacts, $options['target_score']);

        $prompt = $this->buildPrompt($userContext, $jobContext, $normalizedArtifacts, $heuristics, $options['target_score']);

        $usage = null;
        $structured = null;

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an autonomous job application coach who evaluates submission readiness with evidence-backed reasoning.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            $usage = $response->usage ?? null;
            $structured = $this->parseResponse($response->choices[0]->message->content);
        } catch (\Throwable $exception) {
            Log::error('Application quality scoring failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$structured) {
            $structured = $this->fallbackDecision($heuristics, $options['target_score']);
        }

        $componentScores = Arr::get($structured, 'component_scores', []);
        $result = [
            'readiness_score' => (float) ($structured['readiness_score'] ?? $heuristics['overall']),
            'confidence' => (float) ($structured['confidence'] ?? 0.6),
            'component_scores' => [
                'resume' => (float) ($componentScores['resume'] ?? $heuristics['component_scores']['resume']),
                'cover_letter' => (float) ($componentScores['cover_letter'] ?? $heuristics['component_scores']['cover_letter']),
                'ats_alignment' => (float) ($componentScores['ats_alignment'] ?? $heuristics['component_scores']['ats_alignment']),
                'screening' => (float) ($componentScores['screening'] ?? $heuristics['component_scores']['screening']),
            ],
            'decision' => $structured['decision'] ?? $heuristics['decision'],
            'risk_flags' => array_values(array_unique(Arr::wrap($structured['risk_flags'] ?? $heuristics['risk_flags']))),
            'recommended_actions' => Arr::wrap($structured['recommended_actions'] ?? $heuristics['recommended_actions']),
            'summary' => $structured['summary'] ?? $heuristics['summary'],
            'next_steps' => Arr::wrap($structured['next_steps'] ?? $heuristics['next_steps']),
            'metadata' => [
                'target_score' => $options['target_score'],
                'job_id' => $job->id,
            ],
        ];

        $this->logAiUsage(
            $user->id,
            'application_quality_scorer',
            self::MODEL,
            $usage,
            [
                'job_id' => $job->id,
                'readiness_score' => $result['readiness_score'],
            ]
        );

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
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

    protected function normalizeArtifacts(array $artifacts): array
    {
        return [
            'resume' => $artifacts['resume'] ?? [],
            'cover_letter' => $artifacts['cover_letter'] ?? [],
            'ats' => $artifacts['ats'] ?? [],
            'screening_answers' => $artifacts['screening_answers'] ?? [],
        ];
    }

    protected function buildHeuristics(array $artifacts, float $targetScore): array
    {
        $resumeScore = $this->scoreResume($artifacts['resume'], $artifacts['ats']);
        $coverLetterScore = $this->scoreCoverLetter($artifacts['cover_letter']);
        $atsScore = $this->scoreAts($artifacts['ats'], $artifacts['resume']);
        $screeningScore = $this->scoreScreening($artifacts['screening_answers']);

        $overall = round((
            ($resumeScore * 0.35) +
            ($coverLetterScore * 0.2) +
            ($atsScore * 0.3) +
            ($screeningScore * 0.15)
        ), 1);

        $riskFlags = [];
        if ($overall < $targetScore) {
            $riskFlags[] = 'Overall readiness below target threshold.';
        }
        if ($resumeScore < 70) {
            $riskFlags[] = 'Resume tailoring score is below 70.';
        }
        if ($coverLetterScore < 65) {
            $riskFlags[] = 'Cover letter confidence is weak.';
        }
        if ($atsScore < 75) {
            $riskFlags[] = 'ATS alignment requires attention.';
        }
        if ($screeningScore > 0 && $screeningScore < 60) {
            $riskFlags[] = 'Screening responses have low confidence.';
        }
        if (empty($artifacts['screening_answers'])) {
            $riskFlags[] = 'Screening questions are unanswered.';
        }

        $recommendedActions = [];
        if ($resumeScore < $targetScore) {
            $recommendedActions[] = [
                'area' => 'resume',
                'urgency' => $resumeScore < 60 ? 'high' : 'medium',
                'action' => 'Review accomplishment bullets and integrate more role-specific keywords.',
            ];
        }
        if ($coverLetterScore < $targetScore) {
            $recommendedActions[] = [
                'area' => 'cover_letter',
                'urgency' => $coverLetterScore < 55 ? 'high' : 'medium',
                'action' => 'Tighten storytelling and add recent quantified wins that match the job priorities.',
            ];
        }
        if ($atsScore < $targetScore) {
            $recommendedActions[] = [
                'area' => 'ats_alignment',
                'urgency' => $atsScore < 65 ? 'high' : 'medium',
                'action' => 'Incorporate missing keywords and ensure section headers remain ATS friendly.',
            ];
        }
        if ($screeningScore > 0 && $screeningScore < $targetScore) {
            $recommendedActions[] = [
                'area' => 'screening_responses',
                'urgency' => $screeningScore < 55 ? 'high' : 'medium',
                'action' => 'Rehearse concise stories demonstrating impact and preparedness for follow-up questions.',
            ];
        }

        $decision = $overall >= $targetScore
            ? 'ready'
            : ($overall >= ($targetScore - 10) ? 'needs_improvement' : 'do_not_submit');

        return [
            'component_scores' => [
                'resume' => round($resumeScore, 1),
                'cover_letter' => round($coverLetterScore, 1),
                'ats_alignment' => round($atsScore, 1),
                'screening' => round($screeningScore, 1),
            ],
            'overall' => $overall,
            'risk_flags' => array_values(array_unique($riskFlags)),
            'recommended_actions' => $recommendedActions,
            'decision' => $decision,
            'summary' => $decision === 'ready'
                ? 'Application package meets or exceeds the target readiness threshold.'
                : 'Application package requires refinement before submission.',
            'next_steps' => $decision === 'ready'
                ? ['Verify submission details and proceed.', 'Schedule follow-up reminders after submission.']
                : ['Address high-urgency recommendations before submitting.'],
        ];
    }

    protected function buildPrompt(
        array $user,
        array $job,
        array $artifacts,
        array $heuristics,
        float $targetScore
    ): string {
        $resumeSummary = Str::limit((string) Arr::get($artifacts, 'resume.rendered_resume'), 800, '...');
        $coverLetter = Str::limit((string) Arr::get($artifacts, 'cover_letter.cover_letter'), 600, '...');
        $screeningSample = array_map(function ($answer) {
            $answer['answer'] = Str::limit((string) ($answer['answer'] ?? ''), 180, '...');
            return $answer;
        }, array_slice(Arr::get($artifacts, 'screening_answers', []), 0, 5));

        $context = [
            'user' => $user,
            'job' => $job,
            'resume' => [
                'ats_score' => Arr::get($artifacts, 'resume.ats_score'),
                'resume_changes' => array_slice(Arr::get($artifacts, 'resume.resume_changes', []), 0, 6),
                'optimized_keywords' => array_slice(Arr::get($artifacts, 'resume.optimized_keywords', []), 0, 10),
                'summary' => $resumeSummary,
            ],
            'cover_letter' => [
                'confidence' => Arr::get($artifacts, 'cover_letter.confidence'),
                'keywords' => array_slice(Arr::get($artifacts, 'cover_letter.keywords_used', []), 0, 10),
                'body_excerpt' => $coverLetter,
            ],
            'ats' => [
                'ats_score' => Arr::get($artifacts, 'ats.ats_score'),
                'section_scores' => Arr::get($artifacts, 'ats.section_scores'),
                'keyword_match' => Arr::get($artifacts, 'ats.keyword_match'),
            ],
            'screening' => $screeningSample,
            'heuristics' => $heuristics,
            'target_score' => $targetScore,
        ];

        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Assess whether this application package is ready for submission. Use the heuristics as guidance, but adjust if evidence warrants.

CONTEXT:
{$contextJson}

Respond ONLY with JSON using this schema:
{
  "readiness_score": 0-100,
  "confidence": 0-1,
  "component_scores": {
    "resume": 0-100,
    "cover_letter": 0-100,
    "ats_alignment": 0-100,
    "screening": 0-100
  },
  "decision": "ready|needs_improvement|do_not_submit",
  "risk_flags": ["..."],
  "recommended_actions": [
    {"area": "string", "urgency": "high|medium|low", "action": "string"}
  ],
  "summary": "string",
  "next_steps": ["..."],
  "notes": ["..."]
}
PROMPT;
    }

    protected function fallbackDecision(array $heuristics, float $targetScore): array
    {
        $confidence = $heuristics['overall'] >= $targetScore ? 0.7 : 0.55;

        return [
            'readiness_score' => $heuristics['overall'],
            'confidence' => $confidence,
            'component_scores' => $heuristics['component_scores'],
            'decision' => $heuristics['decision'],
            'risk_flags' => $heuristics['risk_flags'],
            'recommended_actions' => $heuristics['recommended_actions'],
            'summary' => $heuristics['summary'],
            'next_steps' => $heuristics['next_steps'],
            'notes' => ['Generated via heuristic fallback; no AI refinement applied.'],
        ];
    }

    protected function scoreResume(array $resume, array $ats): float
    {
        $base = (float) ($resume['ats_score'] ?? $ats['ats_score'] ?? 65);
        $changes = count($resume['resume_changes'] ?? []);
        $keywords = count($resume['optimized_keywords'] ?? []);

        $bonus = min($changes * 2, 12) + min($keywords * 1.5, 15);

        return max(40, min(100, $base + $bonus));
    }

    protected function scoreCoverLetter(array $coverLetter): float
    {
        $confidence = (float) ($coverLetter['confidence'] ?? 0.6);
        $base = $confidence * 100;
        $keywords = count($coverLetter['keywords_used'] ?? []);
        $talkingPoints = count($coverLetter['structured_letter']['talking_points'] ?? []);

        $bonus = min($keywords * 1.2, 10) + min($talkingPoints * 1.5, 9);

        return max(35, min(100, $base + $bonus));
    }

    protected function scoreAts(array $ats, array $resume): float
    {
        $score = (float) ($ats['ats_score'] ?? $resume['ats_score'] ?? 65);
        $coverage = (float) Arr::get($ats, 'keyword_match.coverage_percent', 60);
        $sections = Arr::get($ats, 'section_scores', []);

        $avgSection = empty($sections)
            ? $score
            : array_sum($sections) / max(count($sections), 1);

        $combined = ($score * 0.6) + ($coverage * 0.25) + ($avgSection * 0.15);

        return max(40, min(100, round($combined, 1)));
    }

    protected function scoreScreening(array $answers): float
    {
        if (empty($answers)) {
            return 0.0;
        }

        $confidenceScores = array_map(function ($answer) {
            return (float) ($answer['confidence'] ?? 0.5);
        }, $answers);

        $averageConfidence = array_sum($confidenceScores) / max(count($confidenceScores), 1);
        $followUpReadiness = collect($answers)->reduce(function ($carry, $answer) {
            $level = $answer['follow_up_readiness'] ?? 'medium';
            return $carry + match ($level) {
                'high' => 1.0,
                'low' => 0.4,
                default => 0.7,
            };
        }, 0.0) / max(count($answers), 1);

        $score = ($averageConfidence * 70) + ($followUpReadiness * 15) + 15;

        return max(30, min(100, round($score, 1)));
    }

    protected function buildUserContext(User $user): array
    {
        $profile = $user->profile;

        return [
            'name' => $user->name ?? 'Candidate',
            'current_role' => data_get($profile, 'current_role') ?? data_get($profile, 'headline'),
            'experience_years' => data_get($profile, 'years_of_experience'),
            'industries' => data_get($profile, 'industries', []),
            'top_skills' => array_slice($this->flattenSkills(data_get($profile, 'skills', [])), 0, 12),
            'recent_highlights' => array_slice(data_get($profile, 'achievements', []), 0, 5),
        ];
    }

    protected function buildJobContext(DiscoveredJob $job): array
    {
        return [
            'title' => $job->title,
            'company' => $job->company_name,
            'location' => $job->location,
            'experience_level' => $job->experience_level,
            'keywords' => array_slice(array_unique(array_merge(
                Arr::wrap($job->extracted_skills),
                $this->extractKeywords($job->description ?? '')
            )), 0, 15),
        ];
    }

    protected function extractKeywords(string $text): array
    {
        $matches = [];
        preg_match_all('/\b[A-Za-z][A-Za-z0-9+\/\#&-]{3,}\b/', $text, $matches);

        return array_map('strtolower', array_slice(array_unique($matches[0] ?? []), 0, 20));
    }

    protected function flattenSkills(array $skills): array
    {
        $flat = [];
        foreach ($skills as $values) {
            if (is_array($values)) {
                $flat = array_merge($flat, $values);
            } elseif (is_string($values)) {
                $flat = array_merge($flat, array_map('trim', explode(',', $values)));
            }
        }

        return array_values(array_unique(array_filter($flat)));
    }
}
