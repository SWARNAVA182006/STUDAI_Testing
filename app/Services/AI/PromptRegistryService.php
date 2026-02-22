<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AIPrompt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Prompt Registry Service
 *
 * Manages AI prompts with versioning, caching, and performance tracking.
 * Centralizes all prompt management to enable A/B testing and easy updates
 * without code deployments.
 *
 * Usage:
 *   $prompt = app(PromptRegistryService::class)->get('resume_analysis');
 *   $rendered = $prompt->render(['user_name' => 'John', 'job_title' => 'Developer']);
 */
class PromptRegistryService
{
    /**
     * Default prompts for seeding.
     */
    protected array $defaultPrompts = [];

    /**
     * Get the active prompt by name.
     */
    public function get(string $name): ?AIPrompt
    {
        $prompt = AIPrompt::getActive($name);

        if (!$prompt) {
            Log::warning('Prompt not found', ['name' => $name]);
            return null;
        }

        return $prompt;
    }

    /**
     * Get a specific version of a prompt.
     */
    public function getVersion(string $name, int $version): ?AIPrompt
    {
        return AIPrompt::getVersion($name, $version);
    }

    /**
     * Get and render a prompt with variables.
     */
    public function render(string $name, array $variables = []): ?string
    {
        $prompt = $this->get($name);

        if (!$prompt) {
            return null;
        }

        // Validate variables
        $missing = $prompt->validateVariables($variables);
        if (!empty($missing)) {
            Log::warning('Missing prompt variables', [
                'prompt' => $name,
                'missing' => $missing,
            ]);
        }

        return $prompt->render($variables);
    }

    /**
     * Get the system prompt for a named prompt.
     */
    public function getSystemPrompt(string $name): ?string
    {
        $prompt = $this->get($name);

        return $prompt?->system_prompt;
    }

    /**
     * Get prompt configuration (model hint, max tokens, temperature).
     */
    public function getConfig(string $name): array
    {
        $prompt = $this->get($name);

        if (!$prompt) {
            return [
                'model' => null,
                'max_tokens' => null,
                'temperature' => null,
            ];
        }

        return [
            'model' => $prompt->model_hint,
            'max_tokens' => $prompt->max_tokens,
            'temperature' => $prompt->temperature,
        ];
    }

    /**
     * Create a new prompt.
     */
    public function create(array $data): AIPrompt
    {
        $existingVersion = AIPrompt::where('name', $data['name'])->max('version') ?? 0;

        $prompt = AIPrompt::create([
            'name' => $data['name'],
            'category' => $data['category'] ?? 'general',
            'version' => $existingVersion + 1,
            'system_prompt' => $data['system_prompt'] ?? null,
            'template' => $data['template'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'variables' => $data['variables'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'model_hint' => $data['model_hint'] ?? null,
            'max_tokens' => $data['max_tokens'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // If this is the first version or marked as active, deactivate others
        if ($prompt->is_active && $existingVersion > 0) {
            AIPrompt::where('name', $data['name'])
                ->where('id', '!=', $prompt->id)
                ->update(['is_active' => false]);
        }

        AIPrompt::clearCache($data['name']);

        Log::info('Prompt created', [
            'name' => $prompt->name,
            'version' => $prompt->version,
        ]);

        return $prompt;
    }

    /**
     * Update an existing prompt (creates new version).
     */
    public function update(string $name, array $changes): AIPrompt
    {
        $currentPrompt = $this->get($name);

        if (!$currentPrompt) {
            throw new \InvalidArgumentException("Prompt '{$name}' not found");
        }

        $changes['updated_by'] = auth()->id();

        $newPrompt = $currentPrompt->createNewVersion($changes);

        Log::info('Prompt updated', [
            'name' => $name,
            'old_version' => $currentPrompt->version,
            'new_version' => $newPrompt->version,
        ]);

        return $newPrompt;
    }

    /**
     * Activate a specific version of a prompt.
     */
    public function activate(string $name, int $version): AIPrompt
    {
        $prompt = AIPrompt::getVersion($name, $version);

        if (!$prompt) {
            throw new \InvalidArgumentException("Prompt '{$name}' version {$version} not found");
        }

        $prompt->setAsActive();

        Log::info('Prompt version activated', [
            'name' => $name,
            'version' => $version,
        ]);

        return $prompt;
    }

    /**
     * Deactivate a prompt (all versions).
     */
    public function deactivate(string $name): void
    {
        AIPrompt::where('name', $name)->update(['is_active' => false]);
        AIPrompt::clearCache($name);

        Log::info('Prompt deactivated', ['name' => $name]);
    }

    /**
     * Record usage metrics for a prompt.
     */
    public function recordUsage(string $name, float $latencyMs, bool $success = true): void
    {
        $prompt = $this->get($name);

        if ($prompt) {
            $prompt->recordUsage($latencyMs, $success);
        }
    }

    /**
     * Get all prompts in a category.
     */
    public function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return AIPrompt::category($category)
            ->active()
            ->latestVersion()
            ->get()
            ->unique('name');
    }

    /**
     * Get all prompt names.
     */
    public function getAllNames(): array
    {
        return AIPrompt::active()
            ->distinct()
            ->pluck('name')
            ->toArray();
    }

    /**
     * Check if a prompt exists.
     */
    public function exists(string $name): bool
    {
        return AIPrompt::where('name', $name)->exists();
    }

    /**
     * Get prompt statistics.
     */
    public function getStats(string $name): array
    {
        $prompt = $this->get($name);

        if (!$prompt) {
            return [];
        }

        $versions = AIPrompt::getVersions($name);

        return [
            'name' => $name,
            'current_version' => $prompt->version,
            'total_versions' => $versions->count(),
            'usage_count' => $prompt->usage_count,
            'avg_latency_ms' => $prompt->avg_latency_ms,
            'success_rate' => $prompt->success_rate,
            'category' => $prompt->category,
            'created_at' => $prompt->created_at->toIso8601String(),
        ];
    }

    /**
     * Seed default prompts if they don't exist.
     */
    public function seedDefaults(): void
    {
        $defaults = $this->getDefaultPrompts();

        foreach ($defaults as $promptData) {
            if (!$this->exists($promptData['name'])) {
                $this->create($promptData);
            }
        }

        Log::info('Default prompts seeded', [
            'count' => count($defaults),
        ]);
    }

    /**
     * Get default prompts for seeding.
     */
    protected function getDefaultPrompts(): array
    {
        return [
            [
                'name' => 'resume_analysis',
                'category' => 'resume',
                'description' => 'Analyze a resume and extract key information',
                'system_prompt' => 'You are an expert resume analyst with deep knowledge of hiring practices across industries.',
                'template' => <<<'PROMPT'
Analyze the following resume and provide a comprehensive assessment:

Resume Content:
{resume_content}

Target Job Title (if specified): {target_job_title}

Please provide:
1. Skills extracted (technical and soft skills)
2. Years of experience
3. Education level
4. Key achievements
5. Industry fit assessment
6. ATS optimization score (1-100)
7. Improvement recommendations

Format your response as JSON.
PROMPT,
                'variables' => ['resume_content', 'target_job_title'],
                'model_hint' => 'gpt-5.1',
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ],
            [
                'name' => 'cover_letter_generation',
                'category' => 'cover_letter',
                'description' => 'Generate a personalized cover letter',
                'system_prompt' => 'You are an expert career coach who writes compelling, personalized cover letters.',
                'template' => <<<'PROMPT'
Write a professional cover letter for the following:

Candidate Name: {candidate_name}
Target Position: {job_title}
Company: {company_name}
Candidate Background: {background_summary}
Key Skills: {key_skills}
Tone: {tone}

The cover letter should:
- Be {length} in length
- Highlight relevant experience
- Show enthusiasm for the role
- Include a compelling opening
- End with a clear call to action
PROMPT,
                'variables' => ['candidate_name', 'job_title', 'company_name', 'background_summary', 'key_skills', 'tone', 'length'],
                'model_hint' => 'gpt-5.1',
                'max_tokens' => 1500,
                'temperature' => 0.7,
            ],
            [
                'name' => 'interview_question_generation',
                'category' => 'interview',
                'description' => 'Generate interview questions for practice',
                'system_prompt' => 'You are an experienced interviewer who creates insightful, role-specific interview questions.',
                'template' => <<<'PROMPT'
Generate {question_count} interview questions for the following position:

Job Title: {job_title}
Company: {company_name}
Required Skills: {required_skills}
Experience Level: {experience_level}
Interview Type: {interview_type}

Include a mix of:
- Behavioral questions (STAR method)
- Technical questions
- Situational questions
- Culture fit questions

For each question, also provide:
- What the interviewer is looking for
- Sample strong answer points
PROMPT,
                'variables' => ['question_count', 'job_title', 'company_name', 'required_skills', 'experience_level', 'interview_type'],
                'model_hint' => 'gpt-5.1',
                'max_tokens' => 3000,
                'temperature' => 0.6,
            ],
            [
                'name' => 'skill_gap_analysis',
                'category' => 'skill_analysis',
                'description' => 'Analyze skill gaps for career development',
                'system_prompt' => 'You are a career development expert who identifies skill gaps and creates actionable learning plans.',
                'template' => <<<'PROMPT'
Analyze the skill gap between current skills and target role:

Current Skills: {current_skills}
Target Role: {target_role}
Industry: {industry}
Years of Experience: {experience_years}
Career Goals: {career_goals}

Provide:
1. Critical skill gaps (must have)
2. Nice-to-have skill gaps
3. Emerging skills for future-proofing
4. Prioritized learning recommendations
5. Estimated time to bridge each gap
6. Resource suggestions (courses, certifications)
PROMPT,
                'variables' => ['current_skills', 'target_role', 'industry', 'experience_years', 'career_goals'],
                'model_hint' => 'gpt-5.1',
                'max_tokens' => 2500,
                'temperature' => 0.4,
            ],
            [
                'name' => 'job_match_scoring',
                'category' => 'job_matching',
                'description' => 'Score job-candidate fit',
                'system_prompt' => 'You are an AI recruiter who assesses candidate-job fit with precision.',
                'template' => <<<'PROMPT'
Evaluate the match between this candidate and job:

Candidate Profile:
{candidate_profile}

Job Requirements:
{job_requirements}

Score the match on these dimensions (0-100):
1. Skills match
2. Experience match
3. Education match
4. Location/remote fit
5. Salary expectations alignment
6. Culture fit indicators

Provide:
- Overall match score
- Top 3 matching strengths
- Top 3 gaps to address
- Recommendation (Strong Match / Good Match / Partial Match / Not Recommended)
PROMPT,
                'variables' => ['candidate_profile', 'job_requirements'],
                'model_hint' => 'gpt-5.1',
                'max_tokens' => 1500,
                'temperature' => 0.2,
            ],
        ];
    }
}
