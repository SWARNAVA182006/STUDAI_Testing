<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillValidation;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SkillValidatorService
{
    use InteractsWithAI;
    private const CACHE_TTL_VALIDATION = 2592000; // 30 days
    
    /**
     * Validate user's skills by analyzing work history, projects, and achievements
     */
    public function validateUserSkills(User $user, bool $forceRefresh = false): Collection
    {
        $cacheKey = "skill_validations_{$user->id}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Extract data from user profile
            $workHistory = $user->profile->experience ?? [];
            $education = $user->profile->education ?? [];
            $projects = $user->profile->projects ?? [];
            $achievements = $user->profile->achievements ?? [];
            
            // Analyze work history with AI
            $detectedSkills = $this->analyzeWorkHistory($workHistory, $education, $projects, $achievements);
            
            // Cross-reference with user's claimed skills
            $validations = $this->crossReferenceSkills($user, $detectedSkills);
            
            // Generate demonstration improvement suggestions
            $validations = $this->generateDemonstrationSuggestions($validations, $user);
            
            // Persist validations to database
            $this->persistValidations($user, $validations);
            
            $validationCollection = collect($validations);
            Cache::put($cacheKey, $validationCollection, self::CACHE_TTL_VALIDATION);
            
            return $validationCollection;
            
        } catch (\Exception $e) {
            Log::error('Skill validation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Cache::get($cacheKey) ?? collect([]);
        }
    }

    /**
     * Analyze work history to detect skills using AI
     */
    private function analyzeWorkHistory(array $workHistory, array $education, array $projects, array $achievements): array
    {
        try {
            $prompt = $this->buildWorkAnalysisPrompt($workHistory, $education, $projects, $achievements);
            
            $content = $this->ai(
                $prompt,
                'You are an expert resume analyst and skill assessor. Extract and validate professional skills from work history with high accuracy.',
                ['temperature' => 0.2]
            );
            
            return $this->parseDetectedSkills($content);
            
        } catch (\Exception $e) {
            Log::error('Work history analysis failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Build AI prompt for work history analysis
     */
    private function buildWorkAnalysisPrompt(array $workHistory, array $education, array $projects, array $achievements): string
    {
        $workStr = $this->formatWorkHistory($workHistory);
        $eduStr = $this->formatEducation($education);
        $projectsStr = $this->formatProjects($projects);
        $achievementsStr = $this->formatAchievements($achievements);
        
        return <<<PROMPT
Analyze this professional background and extract ALL skills with evidence-based validation.

**Work History:**
{$workStr}

**Education:**
{$eduStr}

**Projects:**
{$projectsStr}

**Key Achievements:**
{$achievementsStr}

For EACH skill you detect, provide:
1. **Skill Name** (be specific: "React" not "Frontend")
2. **Confidence Score** (0-100: how confident are you this person has this skill)
3. **Proficiency Level** (beginner, intermediate, advanced, expert)
4. **Years of Experience** (estimated based on work history)
5. **Evidence Source** (which job/project/education proves this)
6. **Key Evidence** (specific responsibilities, achievements, or projects)
7. **Validation Strength** (weak, moderate, strong, verified)

Include:
- Technical skills (languages, frameworks, tools)
- Soft skills (leadership, communication, problem-solving)
- Domain knowledge (industry-specific expertise)
- Certifications and credentials

Format as JSON:
{{
  "detected_skills": [
    {{
      "skill_name": "Python",
      "category": "Programming Language",
      "confidence_score": 95,
      "proficiency_level": "advanced",
      "years_of_experience": 5.5,
      "evidence_source": "work_history",
      "source_details": "Senior Developer at TechCorp (2019-2024)",
      "key_evidence": [
        "Built scalable APIs using Django and Flask",
        "Led migration from Python 2 to Python 3",
        "Mentored 3 junior developers in Python best practices"
      ],
      "validation_strength": "strong",
      "projects_count": 8,
      "ai_reasoning": "Consistent Python usage across 5+ years with increasing responsibility"
    }}
  ]
}}

Be thorough and evidence-based. Only include skills with clear proof.
PROMPT;
    }

    /**
     * Format work history for analysis
     */
    private function formatWorkHistory(array $workHistory): string
    {
        if (empty($workHistory)) return "No work history provided.";
        
        $formatted = [];
        foreach ($workHistory as $job) {
            $company = $job['company'] ?? 'Unknown Company';
            $title = $job['title'] ?? 'Unknown Title';
            $period = ($job['start_date'] ?? '?') . ' - ' . ($job['end_date'] ?? 'Present');
            $responsibilities = is_array($job['responsibilities'] ?? null) 
                ? implode("\n  - ", $job['responsibilities']) 
                : ($job['description'] ?? '');
            
            $formatted[] = "**{$title}** at {$company} ({$period})\n  - {$responsibilities}";
        }
        
        return implode("\n\n", $formatted);
    }

    /**
     * Format education for analysis
     */
    private function formatEducation(array $education): string
    {
        if (empty($education)) return "No formal education provided.";
        
        $formatted = [];
        foreach ($education as $edu) {
            $degree = $edu['degree'] ?? 'Degree';
            $field = $edu['field'] ?? 'Field of Study';
            $institution = $edu['institution'] ?? 'Institution';
            $year = $edu['graduation_year'] ?? 'Year';
            
            $formatted[] = "{$degree} in {$field} - {$institution} ({$year})";
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Format projects for analysis
     */
    private function formatProjects(array $projects): string
    {
        if (empty($projects)) return "No projects listed.";
        
        $formatted = [];
        foreach ($projects as $project) {
            $name = $project['name'] ?? 'Project';
            $description = $project['description'] ?? '';
            $technologies = isset($project['technologies']) && is_array($project['technologies'])
                ? implode(', ', $project['technologies'])
                : '';
            
            $formatted[] = "**{$name}**: {$description} | Tech: {$technologies}";
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Format achievements for analysis
     */
    private function formatAchievements(array $achievements): string
    {
        if (empty($achievements)) return "No achievements listed.";
        
        return is_array($achievements) ? implode("\n- ", $achievements) : $achievements;
    }

    /**
     * Parse AI-detected skills from response
     */
    private function parseDetectedSkills(string $response): array
    {
        try {
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($data['detected_skills'])) {
                    return $data['detected_skills'];
                }
            }
            
            throw new \Exception('Invalid JSON response');
            
        } catch (\Exception $e) {
            Log::error('Failed to parse detected skills', [
                'error' => $e->getMessage(),
                'response' => substr($response, 0, 500)
            ]);
            
            return [];
        }
    }

    /**
     * Cross-reference detected skills with user's claimed skills
     */
    private function crossReferenceSkills(User $user, array $detectedSkills): array
    {
        $userSkills = $user->skills;
        $validations = [];
        
        foreach ($detectedSkills as $detected) {
            $skillName = $detected['skill_name'];
            
            // Find matching user skill
            $userSkill = $userSkills->first(fn($s) => strtolower($s->skill_name) === strtolower($skillName));
            
            $validation = [
                'skill_name' => $skillName,
                'category' => $detected['category'] ?? 'General',
                'validation_source' => $detected['evidence_source'],
                'evidence_description' => $detected['source_details'] ?? '',
                'evidence_data' => [
                    'key_evidence' => $detected['key_evidence'] ?? [],
                    'projects_count' => $detected['projects_count'] ?? 0,
                ],
                'confidence_score' => $detected['confidence_score'],
                'proficiency_detected' => $detected['proficiency_level'],
                'years_of_experience' => $detected['years_of_experience'] ?? 0,
                'key_achievements' => $detected['key_evidence'] ?? [],
                'projects' => [],
                'ai_analysis' => [
                    'reasoning' => $detected['ai_reasoning'] ?? '',
                    'validation_strength' => $detected['validation_strength'] ?? 'moderate',
                ],
                'is_verified' => $detected['validation_strength'] === 'strong' && $detected['confidence_score'] >= 80,
            ];
            
            // If user already claimed this skill, compare proficiency
            if ($userSkill) {
                $validation['user_skill_id'] = $userSkill->id;
                $validation['claimed_proficiency'] = $userSkill->proficiency_level;
                $validation['proficiency_gap'] = $this->calculateProficiencyGap(
                    $userSkill->proficiency_level,
                    $detected['proficiency_level']
                );
            }
            
            $validations[] = $validation;
        }
        
        return $validations;
    }

    /**
     * Calculate gap between claimed and detected proficiency
     */
    private function calculateProficiencyGap(string $claimed, string $detected): string
    {
        $levels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        
        $claimedLevel = $levels[$claimed] ?? 2;
        $detectedLevel = $levels[$detected] ?? 2;
        
        $gap = $claimedLevel - $detectedLevel;
        
        if ($gap > 0) return 'overclaimed'; // User claimed higher than evidence shows
        if ($gap < 0) return 'underclaimed'; // User is more skilled than they claim
        return 'accurate'; // Claims match evidence
    }

    /**
     * Generate suggestions for better demonstrating skills
     */
    private function generateDemonstrationSuggestions(array $validations, User $user): array
    {
        foreach ($validations as &$validation) {
            $suggestions = [];
            
            $confidenceScore = $validation['confidence_score'];
            $evidenceCount = count($validation['key_achievements']);
            
            // Weak evidence suggestions
            if ($confidenceScore < 70 || $evidenceCount < 2) {
                $suggestions[] = "Add specific projects or achievements demonstrating {$validation['skill_name']}";
                $suggestions[] = "Quantify impact (e.g., 'Improved performance by 40%' instead of 'Optimized code')";
            }
            
            // Proficiency gap suggestions
            if (isset($validation['proficiency_gap'])) {
                if ($validation['proficiency_gap'] === 'overclaimed') {
                    $suggestions[] = "Build portfolio projects to back up your {$validation['claimed_proficiency']} level claim";
                    $suggestions[] = "Consider taking skill assessments to validate your expertise";
                } elseif ($validation['proficiency_gap'] === 'underclaimed') {
                    $suggestions[] = "You're underselling yourself! Update your skill level to {$validation['proficiency_detected']}";
                    $suggestions[] = "Highlight your {$validation['years_of_experience']} years of experience with this skill";
                }
            }
            
            // General improvement suggestions
            if ($evidenceCount < 3) {
                $suggestions[] = "Add certifications or courses for {$validation['skill_name']} to boost credibility";
            }
            
            if ($validation['years_of_experience'] >= 3 && !isset($validation['user_skill_id'])) {
                $suggestions[] = "Add {$validation['skill_name']} to your skills list - you have strong evidence for it!";
            }
            
            $validation['demonstration_suggestions'] = $suggestions;
        }
        
        return $validations;
    }

    /**
     * Persist skill validations to database
     */
    private function persistValidations(User $user, array $validations): void
    {
        foreach ($validations as $validationData) {
            // Check if validation already exists
            $existing = SkillValidation::where('user_id', $user->id)
                ->where('skill_name', $validationData['skill_name'])
                ->first();
            
            if ($existing) {
                // Update existing validation
                $existing->update([
                    'confidence_score' => $validationData['confidence_score'],
                    'proficiency_detected' => $validationData['proficiency_detected'],
                    'evidence_data' => $validationData['evidence_data'],
                    'key_achievements' => $validationData['key_achievements'],
                    'ai_analysis' => $validationData['ai_analysis'],
                    'demonstration_suggestions' => $validationData['demonstration_suggestions'],
                    'is_verified' => $validationData['is_verified'],
                    'verified_at' => $validationData['is_verified'] ? now() : null,
                ]);
            } else {
                // Create new validation
                SkillValidation::create([
                    'user_id' => $user->id,
                    'user_skill_id' => $validationData['user_skill_id'] ?? null,
                    'skill_name' => $validationData['skill_name'],
                    'validation_source' => $validationData['validation_source'],
                    'evidence_description' => $validationData['evidence_description'],
                    'evidence_data' => $validationData['evidence_data'],
                    'confidence_score' => $validationData['confidence_score'],
                    'proficiency_detected' => $validationData['proficiency_detected'],
                    'years_of_experience' => $validationData['years_of_experience'],
                    'key_achievements' => $validationData['key_achievements'],
                    'projects' => $validationData['projects'],
                    'ai_analysis' => $validationData['ai_analysis'],
                    'demonstration_suggestions' => $validationData['demonstration_suggestions'],
                    'is_verified' => $validationData['is_verified'],
                    'verified_at' => $validationData['is_verified'] ? now() : null,
                ]);
            }
            
            // Update user skill if exists and validation is strong
            if (isset($validationData['user_skill_id']) && $validationData['is_verified']) {
                $userSkill = UserSkill::find($validationData['user_skill_id']);
                if ($userSkill && !$userSkill->is_verified) {
                    $userSkill->markAsVerified('ai_validation');
                }
            }
        }
    }

    /**
     * Validate specific skill claim
     */
    public function validateSkillClaim(User $user, string $skillName, array $evidence = []): array
    {
        try {
            $prompt = <<<PROMPT
Validate if this person truly has "{$skillName}" skill based on provided evidence.

Evidence:
{$this->formatEvidence($evidence)}

Provide:
1. **Validation Result** (confirmed, likely, uncertain, unsubstantiated)
2. **Confidence Score** (0-100)
3. **Detected Proficiency** (beginner, intermediate, advanced, expert)
4. **Evidence Strength** (weak, moderate, strong)
5. **Gaps in Evidence** (what's missing to fully validate)
6. **Recommendations** (how to strengthen this claim)

Format as JSON.
PROMPT;

            $content = $this->ai(
                $prompt,
                'You are a skill validation expert. Be critical but fair.',
                ['temperature' => 0.3, 'model' => 'gpt-5-mini']
            );
            
            // Parse validation result
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonStr, true) ?? ['validation_result' => 'uncertain'];
            }
            
            return ['validation_result' => 'uncertain', 'confidence_score' => 50];
            
        } catch (\Exception $e) {
            Log::error('Skill claim validation failed', [
                'user_id' => $user->id,
                'skill' => $skillName,
                'error' => $e->getMessage()
            ]);
            
            return ['validation_result' => 'error', 'confidence_score' => 0];
        }
    }

    /**
     * Format evidence for AI analysis
     */
    private function formatEvidence(array $evidence): string
    {
        if (empty($evidence)) return "No evidence provided.";
        
        $formatted = [];
        foreach ($evidence as $key => $value) {
            if (is_array($value)) {
                $formatted[] = "{$key}: " . implode(', ', $value);
            } else {
                $formatted[] = "{$key}: {$value}";
            }
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Track AI usage for cost monitoring
     */
    private function trackAIUsage(int $totalTokens): void
    {
        Log::info('AI tokens used', [
            'service' => 'SkillValidator',
            'total_tokens' => $totalTokens,
        ]);
    }
}
