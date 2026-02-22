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

class ResumeCustomizationService
{
    use LogsAiUsage;

    protected const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1
    protected const CACHE_TTL = 21600; // 6 hours

    /**
     * Build a customized resume tailored for a specific job posting.
     */
    public function customize(
        User $user,
        DiscoveredJob $job,
        string $baseResume,
        array $options = []
    ): array {
        $options = array_merge([
            'tone' => 'professional',
            'focus_skills' => [],
            'force_refresh' => false,
        ], $options);

        $cacheKey = sprintf(
            'resume_customization_%d_%d_%s',
            $user->id,
            $job->id,
            md5($baseResume . json_encode($options['focus_skills']))
        );

        if (!$options['force_refresh'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $userProfile = $this->buildUserProfile($user);
        $jobProfile = $this->buildJobProfile($job);

        $payload = [
            'user' => $userProfile,
            'job' => $jobProfile,
            'tone' => $options['tone'],
            'focus_skills' => $options['focus_skills'],
        ];

        $prompt = $this->buildPrompt($payload, $baseResume);

        $structured = null;
        $usage = null;

        try {
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a world-class technical resume optimizer who tailors resumes for ATS systems and human recruiters.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.35,
                'max_tokens' => 3500,
            ]);

            $usage = $response->usage ?? null;
            $structured = $this->parseResponse($response->choices[0]->message->content);
        } catch (\Throwable $exception) {
            Log::error('Resume customization failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if (!$structured) {
            $structured = $this->fallbackCustomization($baseResume, $jobProfile);
        }

        $renderedResume = $this->renderResume($structured);

        $result = [
            'structured_resume' => $structured,
            'rendered_resume' => $renderedResume,
            'ats_score' => (float) ($structured['ats_score'] ?? 70.0),
            'resume_changes' => $structured['resume_changes'] ?? [],
            'optimized_keywords' => $structured['optimized_keywords'] ?? [],
            'warnings' => $structured['warnings'] ?? [],
            'metadata' => [
                'tone' => $options['tone'],
                'focus_skills' => $options['focus_skills'],
                'job_id' => $job->id,
            ],
        ];

        $this->logAiUsage(
            $user->id,
            'resume_customization',
            self::MODEL,
            $usage,
            [
                'job_id' => $job->id,
                'ats_score' => $result['ats_score'],
            ]
        );

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Build the prompt fed to OpenAI
     */
    protected function buildPrompt(array $payload, string $baseResume): string
    {
        $user = $payload['user'];
        $job = $payload['job'];
        $tone = Str::title($payload['tone']);
        $focusSkills = empty($payload['focus_skills'])
            ? 'Use your expert judgement for prioritisation.'
            : 'Prioritise these skills: ' . implode(', ', $payload['focus_skills']);

        $jobDetails = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $userDetails = json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resumePreview = Str::limit($baseResume, 1200, '...');

        return <<<PROMPT
You are optimising a resume for ATS compatibility and recruiter impact.

USER PROFILE:
{$userDetails}

TARGET JOB DETAILS:
{$jobDetails}

CURRENT RESUME SNIPPET:
"""
{$resumePreview}
"""

{$focusSkills}
Maintain a {$tone} tone, quantify achievements, and ensure the resume reads naturally for North American recruiters.

Return ONLY valid JSON matching this schema:
{
  "headline": "string",
  "summary": "string",
  "core_competencies": ["..."],
  "experience_sections": [
    {
      "company": "string",
      "title": "string",
      "location": "string",
      "dates": "string",
      "bullets": ["achievement statements"],
      "keywords_used": ["..."],
      "metrics": ["..."],
      "priority": "high|medium|low"
    }
  ],
  "skills_section": {
      "headline": "string",
      "grouped_skills": {
        "Technical": ["..."],
        "Tools": ["..."],
        "Soft Skills": ["..."]
      }
  },
  "education_section": [
    {
      "institution": "string",
      "degree": "string",
      "year": "string",
      "highlights": ["..."]
    }
  ],
  "certifications": ["..."],
  "resume_changes": [
    {"section": "Summary", "change": "Updated opening summary", "reason": "Align with job"}
  ],
  "optimized_keywords": ["..."],
  "ats_score": 0-100,
  "warnings": ["..."],
  "formatting_tips": ["..."]
}
PROMPT;
    }

    /**
     * Parse OpenAI response safely
     */
    protected function parseResponse(string $content): ?array
    {
        if (preg_match('/\{[\s\S]*\}\s*$/', $content, $matches)) {
            $json = $matches[0];
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Build simple fallback when AI unavailable
     */
    protected function fallbackCustomization(string $baseResume, array $jobProfile): array
    {
        $keywords = array_slice($jobProfile['keywords'], 0, 8);

        return [
            'headline' => $jobProfile['title'] . ' Candidate',
            'summary' => 'Experienced professional seeking to contribute to ' . ($jobProfile['company'] ?? 'the organisation') . ' as a ' . $jobProfile['title'] . '.',
            'core_competencies' => $keywords,
            'experience_sections' => [
                [
                    'company' => 'Most Recent Company',
                    'title' => 'Most Recent Role',
                    'location' => 'Remote',
                    'dates' => '2019 - Present',
                    'bullets' => [
                        'Lead end-to-end delivery of key initiatives aligned with the job description.',
                        'Collaborate with cross-functional teams to improve outcomes.',
                        'Track metrics that demonstrate impact and alignment with employer goals.',
                    ],
                    'keywords_used' => $keywords,
                    'metrics' => ['Increased process efficiency by 15%'],
                    'priority' => 'medium',
                ],
            ],
            'skills_section' => [
                'headline' => 'Core Competencies',
                'grouped_skills' => [
                    'Technical' => $keywords,
                    'Tools' => [],
                    'Soft Skills' => ['Collaboration', 'Communication', 'Problem Solving'],
                ],
            ],
            'education_section' => [],
            'certifications' => [],
            'resume_changes' => [
                ['section' => 'Summary', 'change' => 'Aligned summary with job title', 'reason' => 'Ensure relevance'],
            ],
            'optimized_keywords' => $keywords,
            'ats_score' => 68.0,
            'warnings' => ['AI fallback used - refine manually for better alignment.'],
            'formatting_tips' => ['Ensure resume remains one or two pages.', 'Use consistent bullet formatting.'],
        ];
    }

    /**
     * Convert structured resume into formatted string
     */
    protected function renderResume(array $structured): string
    {
        $lines = [];
        $lines[] = strtoupper($structured['headline'] ?? 'Professional Summary');
        $lines[] = $structured['summary'] ?? '';
        $lines[] = '';

        if (!empty($structured['core_competencies'])) {
            $lines[] = 'CORE COMPETENCIES: ' . implode(' • ', $structured['core_competencies']);
            $lines[] = '';
        }

        foreach ($structured['experience_sections'] ?? [] as $experience) {
            $title = ($experience['title'] ?? 'Role') . ' | ' . ($experience['company'] ?? 'Company');
            $lines[] = strtoupper($title);
            $lines[] = $experience['dates'] ?? '';
            foreach ($experience['bullets'] ?? [] as $bullet) {
                $lines[] = '• ' . $bullet;
            }
            $lines[] = '';
        }

        if (!empty($structured['skills_section']['grouped_skills'] ?? [])) {
            $lines[] = strtoupper($structured['skills_section']['headline'] ?? 'Skills');
            foreach ($structured['skills_section']['grouped_skills'] as $group => $skills) {
                if (!empty($skills)) {
                    $lines[] = $group . ': ' . implode(', ', $skills);
                }
            }
            $lines[] = '';
        }

        foreach ($structured['education_section'] ?? [] as $education) {
            $lines[] = 'Education: ' . ($education['degree'] ?? '') . ' - ' . ($education['institution'] ?? '');
            if (!empty($education['highlights'])) {
                foreach ($education['highlights'] as $highlight) {
                    $lines[] = '• ' . $highlight;
                }
            }
            $lines[] = '';
        }

        if (!empty($structured['certifications'])) {
            $lines[] = 'Certifications: ' . implode(', ', $structured['certifications']);
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Build user profile context
     */
    protected function buildUserProfile(User $user): array
    {
        $profile = $user->profile;

        return [
            'name' => $user->name ?? 'Candidate',
            'current_role' => data_get($profile, 'current_role') ?? data_get($profile, 'headline'),
            'total_experience_years' => data_get($profile, 'years_of_experience'),
            'summary' => data_get($profile, 'summary'),
            'experience' => data_get($profile, 'experience', []),
            'skills' => data_get($profile, 'skills', []),
            'education' => data_get($profile, 'education', []),
            'certifications' => data_get($profile, 'certifications', []),
            'achievements' => data_get($profile, 'achievements', []),
        ];
    }

    /**
     * Build job profile context
     */
    protected function buildJobProfile(DiscoveredJob $job): array
    {
        $keywords = array_unique(array_merge(
            Arr::wrap($job->extracted_skills),
            $this->extractKeywordsFromDescription($job->description ?? '')
        ));

        return [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->company_name,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'experience_level' => $job->experience_level,
            'min_salary' => $job->salary_min,
            'max_salary' => $job->salary_max,
            'description' => Str::limit($job->description ?? '', 2000, '...'),
            'keywords' => array_values($keywords),
        ];
    }

    /**
     * Extract keywords heuristically from job description
     */
    protected function extractKeywordsFromDescription(string $description): array
    {
        $keywords = [];
        $matches = [];
        if (preg_match_all('/\b[A-Z][A-Za-z0-9+\/#&-]{2,}\b/', $description, $matches)) {
            $keywords = array_map('strtolower', $matches[0]);
        }

        return array_unique(array_map(fn($word) => Str::ucfirst($word), $keywords));
    }
}
