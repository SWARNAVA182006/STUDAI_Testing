<?php

namespace App\Services\AI;

use App\Models\ApplicationTemplate;
use App\Models\DiscoveredJob;
use App\Models\User;
use App\Services\AI\Concerns\LogsAiUsage;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CoverLetterGeneratorService
{
    use LogsAiUsage, InteractsWithAI;

    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 14400; // 4 hours

    /**
     * Generate a tailored cover letter and supporting metadata
     */
    public function generate(
        User $user,
        DiscoveredJob $job,
        array $options = []
    ): array {
        $options = array_merge([
            'tone' => 'confident',
            'length' => 'medium',
            'use_template' => true,
            'force_refresh' => false,
        ], $options);

        $cacheKey = sprintf(
            'cover_letter_%d_%d_%s',
            $user->id,
            $job->id,
            md5(json_encode($options))
        );

        if (!$options['force_refresh'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $userContext = $this->buildUserContext($user);
        $jobContext = $this->buildJobContext($job);
        $template = $options['use_template'] ? $this->getBestTemplate($user, $job) : null;

        $prompt = $this->buildPrompt($userContext, $jobContext, $options, $template);

        $usage = null;
        $structured = null;

        try {
            $rawContent = $this->ai(
                $prompt,
                'You are an executive career coach who writes persuasive cover letters that balance professionalism with authenticity.',
                ['temperature' => 0.55]
            );

            $structured = $this->parseResponse($rawContent);
        } catch (\Throwable $exception) {
            Log::error('Cover letter generation failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$structured) {
            $structured = $this->fallbackLetter($userContext, $jobContext, $options['tone']);
        }

        $letter = $this->renderLetter($structured);

        $result = [
            'structured_letter' => $structured,
            'cover_letter' => $letter,
            'keywords_used' => $structured['keywords_highlighted'] ?? [],
            'confidence' => $structured['confidence_score'] ?? 0.75,
            'subject_line' => $structured['subject_line'] ?? $this->defaultSubject($jobContext),
            'metadata' => [
                'job_id' => $job->id,
                'tone' => $options['tone'],
                'length' => $options['length'],
                'template_id' => $template?->id,
            ],
        ];

        $this->logAiUsage(
            $user->id,
            'cover_letter_generation',
            self::MODEL,
            $usage,
            [
                'job_id' => $job->id,
                'tone' => $options['tone'],
                'template_id' => $template?->id,
            ]
        );

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    protected function buildPrompt(array $user, array $job, array $options, ?ApplicationTemplate $template): string
    {
        $templateSection = $template
            ? "BASE TEMPLATE:\n" . $template->content
            : 'No template supplied. Craft a fresh cover letter.';

        $tone = Str::title($options['tone']);

        $userJson = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $jobJson = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $lengthGuidance = match ($options['length']) {
            'short' => '2 paragraphs (~180 words).',
            'long' => '4 paragraphs (~350 words).',
            default => '3 paragraphs (~250 words).',
        };

        return <<<PROMPT
You are writing a {$tone} cover letter that balances credibility with enthusiasm.

USER PROFILE:
{$userJson}

JOB POSTING:
{$jobJson}

{$templateSection}

Instructions:
- Hook the reader in the opening sentence.
- Reference 2-3 quantifiable achievements that map to the role.
- Mirror the employer's language without direct copy.
- Include one sentence demonstrating cultural or mission alignment.
- Close with a confident call-to-action.
- Ensure {$lengthGuidance}
- Highlight keywords naturally (no keyword stuffing).

Return ONLY JSON:
{
  "subject_line": "string",
  "greeting": "string",
  "intro_paragraph": "string",
  "body_paragraphs": ["..."],
  "closing_paragraph": "string",
  "signature": "string",
  "postscript": "string|null",
  "keywords_highlighted": ["..."],
  "confidence_score": 0-1,
  "talking_points": ["..."],
  "tone_notes": "string"
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

    protected function fallbackLetter(array $user, array $job, string $tone): array
    {
        $intro = sprintf(
            'I am excited to submit my application for the %s role at %s.',
            $job['title'] ?? 'open position',
            $job['company'] ?? 'your organisation'
        );

        $body = sprintf(
            'With over %s years of experience in %s, I have delivered measurable outcomes such as %s.',
            $user['experience_years'] ?? 'several',
            $job['title'] ?? 'this field',
            Arr::first($user['notable_achievements'] ?? ['leading cross-functional initiatives'])
        );

        return [
            'subject_line' => $this->defaultSubject($job),
            'greeting' => 'Dear Hiring Manager,',
            'intro_paragraph' => $intro,
            'body_paragraphs' => [$body],
            'closing_paragraph' => 'I welcome the opportunity to discuss how my background can accelerate your roadmap.',
            'signature' => ($user['name'] ?? 'Sincerely') . "\n",
            'postscript' => null,
            'keywords_highlighted' => array_slice($job['keywords'] ?? [], 0, 5),
            'confidence_score' => 0.6,
            'talking_points' => ['Leadership', 'Results delivery'],
            'tone_notes' => "Fallback generated with {$tone} tone."
        ];
    }

    protected function renderLetter(array $structured): string
    {
        $segments = [
            $structured['greeting'] ?? 'Dear Hiring Manager,',
            '',
            $structured['intro_paragraph'] ?? '',
        ];

        foreach ($structured['body_paragraphs'] ?? [] as $paragraph) {
            $segments[] = $paragraph;
        }

        $segments[] = $structured['closing_paragraph'] ?? 'Thank you for your consideration.';
        $segments[] = '';
        $segments[] = $structured['signature'] ?? 'Sincerely,';

        if (!empty($structured['postscript'])) {
            $segments[] = '';
            $segments[] = 'P.S. ' . $structured['postscript'];
        }

        return implode("\n\n", array_filter($segments));
    }

    protected function defaultSubject(array $job): string
    {
        $title = $job['title'] ?? 'Position';
        $company = $job['company'] ?? 'Your Team';

        return sprintf('%s Application – %s', $title, $company);
    }

    protected function buildUserContext(User $user): array
    {
        $profile = $user->profile;

        return [
            'name' => $user->name ?? 'Candidate',
            'current_role' => data_get($profile, 'current_role') ?? data_get($profile, 'headline'),
            'experience_years' => data_get($profile, 'years_of_experience'),
            'top_skills' => array_slice($this->flattenSkills(data_get($profile, 'skills', [])), 0, 12),
            'industries' => data_get($profile, 'industries', []),
            'notable_achievements' => data_get($profile, 'achievements', []),
            'values' => data_get($profile, 'values', []),
            'education' => data_get($profile, 'education', []),
        ];
    }

    protected function buildJobContext(DiscoveredJob $job): array
    {
        return [
            'title' => $job->title,
            'company' => $job->company_name,
            'location' => $job->location,
            'description' => Str::limit($job->description ?? '', 1800, '...'),
            'keywords' => array_values(array_unique(array_merge(
                Arr::wrap($job->extracted_skills),
                $this->keywordSample($job->description ?? '')
            ))),
            'salary_range' => $job->getSalaryRange(),
        ];
    }

    protected function keywordSample(string $description): array
    {
        $matches = [];
        preg_match_all('/\b[A-Za-z][A-Za-z0-9+\/#&-]{3,}\b/', $description, $matches);

        return array_slice(array_unique(array_map('strtolower', $matches[0] ?? [])), 0, 12);
    }

    protected function flattenSkills(array $skills): array
    {
        $flat = [];
        foreach ($skills as $category => $values) {
            if (is_array($values)) {
                $flat = array_merge($flat, $values);
            } elseif (is_string($values)) {
                $flat = array_merge($flat, array_map('trim', explode(',', $values)));
            }
        }

        return array_unique(array_filter($flat));
    }

    protected function getBestTemplate(User $user, DiscoveredJob $job): ?ApplicationTemplate
    {
        return ApplicationTemplate::query()
            ->where('user_id', $user->id)
            ->coverLetters()
            ->orderByDesc('success_rate')
            ->orderByDesc('average_match_score')
            ->first();
    }
}
