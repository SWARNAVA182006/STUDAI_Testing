<?php

namespace App\Services\AI\Scout;

use App\Models\CompanyDNAProfile;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class ResumeAnalyzerService
{
    /**
     * Perform intelligent resume analysis with semantic understanding.
     *
     * @param int $companyId
     * @param array $resumeData
     * @param int|null $jobId
     * @return array
     */
    public function analyzeResume(int $companyId, array $resumeData, ?int $jobId = null): array
    {
        try {
            Log::info('Starting intelligent resume analysis', [
                'company_id' => $companyId,
                'job_id' => $jobId
            ]);

            // Get company DNA for context-aware analysis
            $dnaProfile = CompanyDNAProfile::where('company_id', $companyId)
                ->with('cultureAnalysis')
                ->first();

            if (!$dnaProfile) {
                return [
                    'success' => false,
                    'message' => 'Company DNA profile not found. Please run DNA analysis first.'
                ];
            }

            // Build analysis prompt
            $prompt = $this->buildResumeAnalysisPrompt($resumeData, $dnaProfile, $jobId);

            // Check cache first
            $cacheKey = 'resume_analysis_' . md5($prompt);
            $cachedResult = Cache::get($cacheKey);

            if ($cachedResult) {
                Log::info('Resume analysis returned from cache');
                return [
                    'success' => true,
                    'data' => $cachedResult,
                    'cached' => true
                ];
            }

            // Call Azure OpenAI GPT-5 for semantic analysis
            $response = OpenAI::chat()->create([
                'model' => config('ai.azure.models.chat', 'gpt-5'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert HR analyst specializing in resume evaluation and candidate assessment. You understand context, nuance, and can identify transferable skills and potential beyond explicit keywords. You provide fair, unbiased analysis that considers diverse career paths.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2500,
                'response_format' => ['type' => 'json_object']
            ]);

            $analysisData = json_decode($response->choices[0]->message->content, true);

            // Enrich with additional analysis
            $enrichedAnalysis = $this->enrichAnalysis($analysisData, $resumeData, $dnaProfile);

            // Cache for 7 days
            Cache::put($cacheKey, $enrichedAnalysis, now()->addDays(7));

            Log::info('Resume analysis completed successfully', [
                'company_id' => $companyId,
                'overall_score' => $enrichedAnalysis['overall_match_score'] ?? null
            ]);

            return [
                'success' => true,
                'data' => $enrichedAnalysis,
                'cached' => false
            ];

        } catch (Exception $e) {
            Log::error('Resume analysis failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Resume analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build comprehensive resume analysis prompt.
     *
     * @param array $resumeData
     * @param CompanyDNAProfile $dnaProfile
     * @param int|null $jobId
     * @return string
     */
    protected function buildResumeAnalysisPrompt(array $resumeData, CompanyDNAProfile $dnaProfile, ?int $jobId): string
    {
        $companyContext = [
            'mission' => $dnaProfile->mission_statement,
            'vision' => $dnaProfile->vision_statement,
            'values' => $dnaProfile->core_values,
            'cultural_dna' => $dnaProfile->cultural_dna,
            'success_traits' => $dnaProfile->success_traits,
            'work_style' => $dnaProfile->work_style_preferences
        ];

        $jobContext = '';
        if ($jobId) {
            $job = \App\Models\Job::find($jobId);
            if ($job) {
                $jobContext = "\n\nTARGET POSITION:\n" .
                    "Title: {$job->title}\n" .
                    "Description: {$job->description}\n" .
                    "Required Skills: " . json_encode($job->required_skills ?? []) . "\n" .
                    "Experience Level: {$job->experience_level}";
            }
        }

        // Pre-format all complex expressions for heredoc
        $coreValues = $this->formatArray($companyContext['values']);
        $culturalDna = $this->formatArray($companyContext['cultural_dna']);
        $successTraits = $this->formatArray($companyContext['success_traits']);
        $workStyle = $this->formatArray($companyContext['work_style']);
        $candidateSummary = $resumeData['summary'] ?? 'Not provided';
        $experience = $this->formatExperience($resumeData['experience'] ?? []);
        $education = $this->formatEducation($resumeData['education'] ?? []);
        $skills = $this->formatArray($resumeData['skills'] ?? []);
        $achievements = $this->formatArray($resumeData['achievements'] ?? []);

        return <<<PROMPT
Perform an intelligent, semantic analysis of the following resume against our company's organizational DNA and requirements.

COMPANY CONTEXT:
Mission: {$companyContext['mission']}
Core Values: {$coreValues}
Cultural DNA Traits: {$culturalDna}
Success Traits We Look For: {$successTraits}
Work Style: {$workStyle}
{$jobContext}

CANDIDATE RESUME:
Name: {$resumeData['name']}
Summary: {$candidateSummary}

EXPERIENCE:
{$experience}

EDUCATION:
{$education}

SKILLS:
{$skills}

ACHIEVEMENTS:
{$achievements}

ANALYSIS REQUIRED:

1. **Semantic Skill Analysis**: Go beyond keyword matching. Identify:
   - Explicitly stated skills and their proficiency levels
   - Transferable skills not explicitly mentioned but evident from experience
   - Domain expertise and depth of knowledge
   - Skills that align with our success traits
   - Skill gaps that could be filled through training

2. **Career Progression Pattern**: Analyze trajectory:
   - Type: Ambitious Achiever / Steady Performer / Potential Leader / Expert Specialist
   - Growth velocity: titles, responsibilities, scope over time
   - Ambition indicators: promotions, expanded roles, leadership progression
   - Stability vs. mobility patterns and their implications
   - Career narrative and strategic choices

3. **Achievement Validation**: Cross-reference with industry benchmarks:
   - Quantified achievements and their significance
   - Impact scale: individual, team, department, company-wide
   - Exceptional performers (top 10% indicators)
   - Innovation and problem-solving examples
   - Leadership and influence beyond title

4. **Gap & Transition Analysis**: Context-aware evaluation:
   - Employment gaps with likely explanations (education, sabbatical, caregiving, etc.)
   - Industry or role transitions and transferable skills utilized
   - Non-traditional path strengths and unique perspectives
   - Career pivots and adaptability indicators
   - Fair assessment without penalizing diverse paths

5. **Red Flag Detection**: Balanced and context-aware:
   - Job hopping: pattern analysis (acceptable vs. concerning)
   - Short tenures with contextual understanding (startup failures, acquisitions, contracts)
   - Resume inconsistencies or timeline gaps requiring clarification
   - Skill inflation or misrepresentation indicators
   - Cultural fit concerns based on work style mismatches

6. **Cultural DNA Alignment**: Match to our organizational identity:
   - Value alignment score (0-100) with evidence
   - Work style compatibility (collaboration vs. independent, structured vs. flexible)
   - Communication style fit
   - Innovation/learning orientation match
   - Long-term potential based on career goals

7. **Transferable Skills Matrix**: Skills applicable beyond stated context:
   - Leadership skills demonstrated in non-management roles
   - Technical skills transferable across industries
   - Soft skills evidenced through achievements
   - Problem-solving approaches and methodologies
   - Cross-functional collaboration capabilities

8. **Overall Match Assessment**:
   - Overall Match Score (0-100)
   - Recommendation: STRONG HIRE / RECOMMENDED / CONDITIONAL / NOT RECOMMENDED
   - Top 3 Strengths for this role
   - Top 3 Concerns or development areas
   - Interview focus areas to validate or explore further
   - Onboarding support needed for success

Return analysis as JSON with this structure:
{
    "semantic_skills": {
        "explicit_skills": [{"skill": "", "proficiency": "", "evidence": ""}],
        "transferable_skills": [{"skill": "", "inferred_from": "", "relevance": ""}],
        "domain_expertise": {"areas": [], "depth_score": 0},
        "alignment_with_success_traits": {"score": 0, "matching_traits": []},
        "skill_gaps": [{"skill": "", "trainability": "", "priority": ""}]
    },
    "career_progression": {
        "pattern_type": "",
        "growth_velocity": "",
        "ambition_score": 0,
        "stability_score": 0,
        "career_narrative": "",
        "strategic_choices": []
    },
    "achievement_validation": {
        "quantified_achievements": [{"achievement": "", "impact_scale": "", "percentile_estimate": ""}],
        "exceptional_performer_indicators": [],
        "innovation_examples": [],
        "leadership_influence": ""
    },
    "gap_transition_analysis": {
        "employment_gaps": [{"period": "", "likely_reason": "", "concern_level": ""}],
        "transitions": [{"from": "", "to": "", "transferable_skills": [], "success_likelihood": ""}],
        "non_traditional_strengths": [],
        "adaptability_score": 0
    },
    "red_flags": {
        "job_hopping": {"pattern": "", "severity": "", "context": ""},
        "short_tenures": [{"company": "", "duration": "", "explanation": ""}],
        "inconsistencies": [],
        "skill_inflation_risk": "",
        "cultural_fit_concerns": []
    },
    "cultural_dna_alignment": {
        "value_alignment_score": 0,
        "evidence": [],
        "work_style_compatibility": 0,
        "communication_fit": "",
        "innovation_orientation": 0,
        "long_term_potential": ""
    },
    "transferable_skills_matrix": {
        "leadership_skills": [],
        "technical_transferable": [],
        "soft_skills": [],
        "problem_solving_approach": "",
        "collaboration_capabilities": []
    },
    "overall_assessment": {
        "overall_match_score": 0,
        "recommendation": "",
        "top_strengths": [],
        "top_concerns": [],
        "interview_focus_areas": [],
        "onboarding_support_needed": []
    }
}
PROMPT;
    }

    /**
     * Enrich AI analysis with additional computed metrics.
     *
     * @param array $analysisData
     * @param array $resumeData
     * @param CompanyDNAProfile $dnaProfile
     * @return array
     */
    protected function enrichAnalysis(array $analysisData, array $resumeData, CompanyDNAProfile $dnaProfile): array
    {
        // Calculate experience metrics
        $experienceMetrics = $this->calculateExperienceMetrics($resumeData['experience'] ?? []);
        
        // Calculate skill diversity
        $skillDiversity = $this->calculateSkillDiversity(
            $resumeData['skills'] ?? [],
            $analysisData['semantic_skills']['transferable_skills'] ?? []
        );

        // Calculate education quality score
        $educationScore = $this->calculateEducationScore($resumeData['education'] ?? []);

        // Determine candidate archetype
        $archetype = $this->determineArchetype($analysisData);

        // Add enriched data
        $analysisData['experience_metrics'] = $experienceMetrics;
        $analysisData['skill_diversity_score'] = $skillDiversity;
        $analysisData['education_quality_score'] = $educationScore;
        $analysisData['candidate_archetype'] = $archetype;
        
        // Add metadata
        $analysisData['analyzed_at'] = now()->toIso8601String();
        $analysisData['company_dna_health'] = $dnaProfile->dna_health_score;

        return $analysisData;
    }

    /**
     * Calculate experience metrics from work history.
     *
     * @param array $experience
     * @return array
     */
    protected function calculateExperienceMetrics(array $experience): array
    {
        if (empty($experience)) {
            return [
                'total_years' => 0,
                'average_tenure' => 0,
                'longest_tenure' => 0,
                'number_of_companies' => 0,
                'promotions_count' => 0
            ];
        }

        $totalMonths = 0;
        $tenures = [];
        $promotions = 0;
        $previousTitle = '';
        $previousCompany = '';

        foreach ($experience as $exp) {
            $startDate = new \DateTime($exp['start_date'] ?? 'now');
            $endDate = isset($exp['end_date']) ? new \DateTime($exp['end_date']) : new \DateTime();
            
            $months = $startDate->diff($endDate)->m + ($startDate->diff($endDate)->y * 12);
            $totalMonths += $months;
            $tenures[] = $months;

            // Detect promotions (same company, higher title)
            if ($exp['company'] === $previousCompany && 
                $this->isSeniorTitle($exp['title'], $previousTitle)) {
                $promotions++;
            }

            $previousTitle = $exp['title'];
            $previousCompany = $exp['company'];
        }

        return [
            'total_years' => round($totalMonths / 12, 1),
            'average_tenure' => round(array_sum($tenures) / count($tenures) / 12, 1),
            'longest_tenure' => round(max($tenures) / 12, 1),
            'number_of_companies' => count(array_unique(array_column($experience, 'company'))),
            'promotions_count' => $promotions
        ];
    }

    /**
     * Calculate skill diversity score.
     *
     * @param array $explicitSkills
     * @param array $transferableSkills
     * @return int
     */
    protected function calculateSkillDiversity(array $explicitSkills, array $transferableSkills): int
    {
        $categories = [
            'technical' => ['programming', 'software', 'database', 'cloud', 'api', 'code'],
            'leadership' => ['lead', 'manage', 'mentor', 'strategy', 'vision', 'team'],
            'communication' => ['present', 'write', 'communicate', 'collaborate', 'stakeholder'],
            'analytical' => ['data', 'analysis', 'metrics', 'research', 'problem-solving'],
            'creative' => ['design', 'creative', 'innovation', 'ux', 'ui', 'brand']
        ];

        $allSkills = array_merge(
            $explicitSkills,
            array_column($transferableSkills, 'skill')
        );

        $categoriesMatched = 0;
        foreach ($categories as $category => $keywords) {
            foreach ($allSkills as $skill) {
                $skillLower = strtolower($skill);
                foreach ($keywords as $keyword) {
                    if (str_contains($skillLower, $keyword)) {
                        $categoriesMatched++;
                        break 2; // Move to next category
                    }
                }
            }
        }

        // Score based on category coverage and total skills
        $categoryScore = ($categoriesMatched / count($categories)) * 60;
        $volumeScore = min(40, count($allSkills) * 2);

        return min(100, round($categoryScore + $volumeScore));
    }

    /**
     * Calculate education quality score.
     *
     * @param array $education
     * @return int
     */
    protected function calculateEducationScore(array $education): int
    {
        if (empty($education)) {
            return 50; // Baseline for no formal education listed
        }

        $score = 0;
        $degreeValues = [
            'phd' => 100,
            'doctorate' => 100,
            'master' => 85,
            'mba' => 85,
            'bachelor' => 70,
            'associate' => 60,
            'certificate' => 55
        ];

        foreach ($education as $edu) {
            $degree = strtolower($edu['degree'] ?? '');
            foreach ($degreeValues as $degreeType => $value) {
                if (str_contains($degree, $degreeType)) {
                    $score = max($score, $value);
                    break;
                }
            }

            // Bonus for relevant field
            if (isset($edu['field']) && !empty($edu['field'])) {
                $score += 5;
            }

            // Bonus for honors
            if (isset($edu['honors']) && !empty($edu['honors'])) {
                $score += 10;
            }
        }

        return min(100, $score);
    }

    /**
     * Determine candidate archetype from analysis.
     *
     * @param array $analysisData
     * @return string
     */
    protected function determineArchetype(array $analysisData): string
    {
        $progressionType = $analysisData['career_progression']['pattern_type'] ?? '';
        $ambition = $analysisData['career_progression']['ambition_score'] ?? 50;
        $cultural = $analysisData['cultural_dna_alignment']['value_alignment_score'] ?? 50;
        $innovation = $analysisData['cultural_dna_alignment']['innovation_orientation'] ?? 50;

        // Determine archetype based on patterns
        if ($ambition >= 80 && str_contains(strtolower($progressionType), 'leader')) {
            return 'Visionary Leader';
        } elseif ($innovation >= 80 && $cultural >= 70) {
            return 'Innovative Catalyst';
        } elseif ($ambition >= 75 && $cultural >= 75) {
            return 'Ambitious Achiever';
        } elseif (str_contains(strtolower($progressionType), 'specialist')) {
            return 'Domain Expert';
        } elseif ($cultural >= 80) {
            return 'Cultural Champion';
        } elseif (str_contains(strtolower($progressionType), 'steady')) {
            return 'Reliable Performer';
        } else {
            return 'Solid Contributor';
        }
    }

    /**
     * Check if title is more senior than previous.
     *
     * @param string $currentTitle
     * @param string $previousTitle
     * @return bool
     */
    protected function isSeniorTitle(string $currentTitle, string $previousTitle): bool
    {
        $seniority = [
            'intern' => 1, 'junior' => 2, 'associate' => 3,
            'mid' => 4, 'senior' => 5, 'staff' => 6,
            'principal' => 7, 'lead' => 8, 'manager' => 9,
            'director' => 10, 'vp' => 11, 'svp' => 12,
            'cto' => 13, 'ceo' => 14
        ];

        $currentLevel = 0;
        $previousLevel = 0;

        foreach ($seniority as $keyword => $level) {
            if (str_contains(strtolower($currentTitle), $keyword)) {
                $currentLevel = max($currentLevel, $level);
            }
            if (str_contains(strtolower($previousTitle), $keyword)) {
                $previousLevel = max($previousLevel, $level);
            }
        }

        return $currentLevel > $previousLevel;
    }

    /**
     * Helper: Format array for prompt.
     *
     * @param array $items
     * @return string
     */
    protected function formatArray(array $items): string
    {
        if (empty($items)) {
            return 'Not specified';
        }

        if (isset($items[0]) && is_array($items[0])) {
            // Array of objects with 'name' or 'trait' keys
            $names = array_map(fn($item) => $item['name'] ?? $item['trait'] ?? json_encode($item), $items);
            return implode(', ', $names);
        }

        return implode(', ', $items);
    }

    /**
     * Helper: Format experience for prompt.
     *
     * @param array $experience
     * @return string
     */
    protected function formatExperience(array $experience): string
    {
        if (empty($experience)) {
            return 'No experience listed';
        }

        $formatted = [];
        foreach ($experience as $exp) {
            $period = ($exp['start_date'] ?? 'Unknown') . ' - ' . ($exp['end_date'] ?? 'Present');
            $formatted[] = "{$exp['title']} at {$exp['company']} ({$period})\n" .
                "Responsibilities: " . ($exp['description'] ?? 'Not specified');
        }

        return implode("\n\n", $formatted);
    }

    /**
     * Helper: Format education for prompt.
     *
     * @param array $education
     * @return string
     */
    protected function formatEducation(array $education): string
    {
        if (empty($education)) {
            return 'No education listed';
        }

        $formatted = [];
        foreach ($education as $edu) {
            $year = $edu['year'] ?? 'Year not specified';
            $formatted[] = "{$edu['degree']} in {$edu['field']} from {$edu['institution']} ({$year})";
        }

        return implode("\n", $formatted);
    }
}
