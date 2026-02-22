<?php

namespace App\Services\AI;

use App\Models\DiscoveredJob;
use App\Models\User;
use App\Services\AI\Concerns\LogsAiUsage;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScreeningQuestionService
{
    use LogsAiUsage, InteractsWithAI;

    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 7200; // 2 hours

    /**
     * Generate intelligent answers for screening questions.
     */
    public function answerQuestions(
        User $user,
        DiscoveredJob $job,
        array $questions,
        array $options = []
    ): array {
        $questions = array_values(array_filter($questions));

        if (empty($questions)) {
            return [];
        }

        $options = array_merge([
            'tone' => 'professional',
            'force_refresh' => false,
        ], $options);

        $cacheKey = sprintf(
            'screening_answers_%d_%d_%s',
            $user->id,
            $job->id,
            md5(json_encode($questions) . $options['tone'])
        );

        if (!$options['force_refresh'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $userContext = $this->buildUserContext($user);
        $jobContext = $this->buildJobContext($job);

        $prompt = $this->buildPrompt($questions, $userContext, $jobContext, $options['tone']);

        $usage = null;
        $answers = null;

        try {
            $rawContent = $this->ai(
                $prompt,
                'You answer screening questions with concise, truthful responses grounded in the candidate profile.',
                ['temperature' => 0.35]
            );

            $answers = $this->parseResponse($rawContent);
        } catch (\Throwable $exception) {
            Log::error('Screening question generation failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$answers) {
            $answers = $this->fallbackAnswers($questions, $userContext, $jobContext);
        }

        $this->logAiUsage(
            $user->id,
            'screening_question_answers',
            self::MODEL,
            $usage,
            [
                'job_id' => $job->id,
                'questions_count' => count($questions),
            ]
        );

        Cache::put($cacheKey, $answers, self::CACHE_TTL);

        return $answers;
    }

    protected function buildPrompt(array $questions, array $user, array $job, string $tone): string
    {
        $questionsJson = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $userJson = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $jobJson = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $toneTitle = Str::title($tone);

        return <<<PROMPT
You answer screening questions authentically for job applications.

CANDIDATE PROFILE:
{$userJson}

JOB CONTEXT:
{$jobJson}

Screening Questions:
{$questionsJson}

Guidelines:
- Maintain a {$toneTitle} tone.
- Keep each answer under 120 words.
- Reference relevant achievements or metrics when possible.
- Never fabricate experience. If unsure, state willingness to learn.
- Provide optional bullet list of talking points the interviewer may explore.

Return ONLY JSON array, same order as questions:
[
  {
    "question": "string",
    "answer": "string",
    "confidence": 0-1,
    "talking_points": ["..."],
    "follow_up_readiness": "low|medium|high"
  }
]
PROMPT;
    }

    protected function parseResponse(string $content): ?array
    {
        if (preg_match('/\[[\s\S]*\]\s*$/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function fallbackAnswers(array $questions, array $user, array $job): array
    {
        return array_map(function ($question) use ($user, $job) {
            $answer = sprintf(
                'Based on my experience in %s, I have demonstrated adaptability and a strong learning mindset. I am confident I can align quickly with the expectations at %s.',
                $user['current_role'] ?? 'my recent roles',
                $job['company'] ?? 'your organisation'
            );

            return [
                'question' => $question,
                'answer' => $answer,
                'confidence' => 0.6,
                'talking_points' => ['Provide specific example from recent project', 'Reference collaboration with stakeholders'],
                'follow_up_readiness' => 'medium',
            ];
        }, $questions);
    }

    protected function buildUserContext(User $user): array
    {
        $profile = $user->profile;
        $experience = data_get($profile, 'experience', []);

        return [
            'name' => $user->name ?? 'Candidate',
            'current_role' => data_get($profile, 'current_role') ?? data_get($profile, 'headline'),
            'experience_years' => data_get($profile, 'years_of_experience'),
            'primary_skills' => $this->flattenSkills(data_get($profile, 'skills', [])),
            'achievements' => data_get($profile, 'achievements', []),
            'recent_projects' => array_slice($experience, 0, 3),
            'certifications' => data_get($profile, 'certifications', []),
        ];
    }

    protected function buildJobContext(DiscoveredJob $job): array
    {
        return [
            'title' => $job->title,
            'company' => $job->company_name,
            'keywords' => Arr::wrap($job->extracted_skills),
            'description' => Str::limit($job->description ?? '', 1400, '...'),
            'location' => $job->location,
        ];
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
