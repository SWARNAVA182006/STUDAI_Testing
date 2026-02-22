<?php

namespace App\Services\AI\Scout;

use App\Models\CompanyDNAProfile;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class AutomatedShortlistingService
{
    /**
     * Execute multi-stage shortlisting pipeline for applicants.
     *
     * @param int $jobId
     * @param array $applicationIds
     * @return array
     */
    public function executeShortlistingPipeline(int $jobId, array $applicationIds): array
    {
        try {
            Log::info('Starting automated shortlisting pipeline', [
                'job_id' => $jobId,
                'application_count' => count($applicationIds)
            ]);

            $job = Job::with(['company.dnaProfile'])->findOrFail($jobId);
            $companyId = $job->company_id;

            // Load all applications with candidate data
            $applications = Application::with(['user.profile'])
                ->whereIn('id', $applicationIds)
                ->get();

            $results = [
                'total_applications' => count($applications),
                'round_1_passed' => 0,
                'round_2_passed' => 0,
                'round_3_passed' => 0,
                'round_4_passed' => 0,
                'shortlisted' => [],
                'rejected_by_round' => [
                    'round_1' => [],
                    'round_2' => [],
                    'round_3' => [],
                    'round_4' => []
                ],
                'processing_time' => 0
            ];

            $startTime = microtime(true);

            foreach ($applications as $application) {
                $candidateResult = $this->evaluateCandidate($application, $job);
                
                // Track progression through rounds
                if ($candidateResult['round_1']['passed']) {
                    $results['round_1_passed']++;
                    
                    if ($candidateResult['round_2']['passed']) {
                        $results['round_2_passed']++;
                        
                        if ($candidateResult['round_3']['passed']) {
                            $results['round_3_passed']++;
                            
                            if ($candidateResult['round_4']['passed']) {
                                $results['round_4_passed']++;
                                $results['shortlisted'][] = [
                                    'application_id' => $application->id,
                                    'candidate_name' => $application->user->name,
                                    'overall_score' => $candidateResult['overall_score'],
                                    'recommendation' => $candidateResult['recommendation'],
                                    'round_scores' => [
                                        'round_1' => $candidateResult['round_1']['score'],
                                        'round_2' => $candidateResult['round_2']['score'],
                                        'round_3' => $candidateResult['round_3']['score'],
                                        'round_4' => $candidateResult['round_4']['score'],
                                    ],
                                    'strengths' => $candidateResult['strengths'],
                                    'concerns' => $candidateResult['concerns']
                                ];
                            } else {
                                $results['rejected_by_round']['round_4'][] = $this->formatRejection($application, $candidateResult, 4);
                            }
                        } else {
                            $results['rejected_by_round']['round_3'][] = $this->formatRejection($application, $candidateResult, 3);
                        }
                    } else {
                        $results['rejected_by_round']['round_2'][] = $this->formatRejection($application, $candidateResult, 2);
                    }
                } else {
                    $results['rejected_by_round']['round_1'][] = $this->formatRejection($application, $candidateResult, 1);
                }
            }

            $results['processing_time'] = round(microtime(true) - $startTime, 2);

            // Sort shortlisted by overall score
            usort($results['shortlisted'], fn($a, $b) => $b['overall_score'] <=> $a['overall_score']);

            Log::info('Shortlisting pipeline completed', [
                'job_id' => $jobId,
                'shortlisted_count' => count($results['shortlisted']),
                'processing_time' => $results['processing_time']
            ]);

            return [
                'success' => true,
                'data' => $results
            ];

        } catch (Exception $e) {
            Log::error('Automated shortlisting pipeline failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Shortlisting pipeline failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Evaluate single candidate through all 4 rounds.
     *
     * @param Application $application
     * @param Job $job
     * @return array
     */
    protected function evaluateCandidate(Application $application, Job $job): array
    {
        $candidateProfile = $this->buildCandidateProfile($application->user);
        
        // Round 1: Basic Qualification Screening
        $round1 = $this->executeRound1BasicQualification($candidateProfile, $job);
        
        if (!$round1['passed']) {
            return [
                'round_1' => $round1,
                'round_2' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 1'],
                'round_3' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 1'],
                'round_4' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 1'],
                'overall_score' => $round1['score'],
                'recommendation' => 'REJECT - Basic Qualifications',
                'strengths' => [],
                'concerns' => $round1['concerns']
            ];
        }

        // Round 2: Skills and Competency Matching
        $round2 = $this->executeRound2SkillsCompetency($candidateProfile, $job);
        
        if (!$round2['passed']) {
            return [
                'round_1' => $round1,
                'round_2' => $round2,
                'round_3' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 2'],
                'round_4' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 2'],
                'overall_score' => ($round1['score'] + $round2['score']) / 2,
                'recommendation' => 'REJECT - Skills Mismatch',
                'strengths' => $round1['strengths'],
                'concerns' => array_merge($round1['concerns'], $round2['concerns'])
            ];
        }

        // Round 3: Cultural Fit Assessment
        $round3 = $this->executeRound3CulturalFit($candidateProfile, $job, $application);
        
        if (!$round3['passed']) {
            return [
                'round_1' => $round1,
                'round_2' => $round2,
                'round_3' => $round3,
                'round_4' => ['passed' => false, 'score' => 0, 'reason' => 'Did not pass Round 3'],
                'overall_score' => ($round1['score'] + $round2['score'] + $round3['score']) / 3,
                'recommendation' => 'REJECT - Cultural Misalignment',
                'strengths' => array_merge($round1['strengths'], $round2['strengths']),
                'concerns' => array_merge($round1['concerns'], $round2['concerns'], $round3['concerns'])
            ];
        }

        // Round 4: Potential and Growth Analysis
        $round4 = $this->executeRound4PotentialGrowth($candidateProfile, $job);

        $overallScore = (
            $round1['score'] * 0.15 +  // 15% weight
            $round2['score'] * 0.35 +  // 35% weight
            $round3['score'] * 0.30 +  // 30% weight
            $round4['score'] * 0.20    // 20% weight
        );

        $recommendation = $this->determineRecommendation($overallScore, [
            'round_1' => $round1,
            'round_2' => $round2,
            'round_3' => $round3,
            'round_4' => $round4
        ]);

        return [
            'round_1' => $round1,
            'round_2' => $round2,
            'round_3' => $round3,
            'round_4' => $round4,
            'overall_score' => round($overallScore, 1),
            'recommendation' => $recommendation,
            'strengths' => array_merge(
                $round1['strengths'],
                $round2['strengths'],
                $round3['strengths'],
                $round4['strengths']
            ),
            'concerns' => array_merge(
                $round1['concerns'],
                $round2['concerns'],
                $round3['concerns'],
                $round4['concerns']
            )
        ];
    }

    /**
     * Round 1: Basic Qualification Screening
     *
     * Verifies essential requirements, education, minimum experience.
     * Ensures legal compliance and eliminates clearly unqualified candidates.
     *
     * @param array $candidate
     * @param Job $job
     * @return array
     */
    protected function executeRound1BasicQualification(array $candidate, Job $job): array
    {
        $score = 100; // Start perfect, deduct points for gaps
        $strengths = [];
        $concerns = [];
        $passed = true;

        // Check education requirement
        if (isset($job->minimum_education)) {
            $hasEducation = $this->meetsEducationRequirement(
                $candidate['education'] ?? [],
                $job->minimum_education
            );
            
            if (!$hasEducation) {
                $score -= 40;
                $concerns[] = "Does not meet minimum education requirement: {$job->minimum_education}";
                if ($job->education_strict ?? false) {
                    $passed = false;
                }
            } else {
                $strengths[] = "Meets education requirement";
            }
        }

        // Check minimum experience
        $yearsExp = $candidate['years_of_experience'] ?? 0;
        $requiredExp = $job->minimum_experience ?? 0;
        
        if ($yearsExp < $requiredExp) {
            $gap = $requiredExp - $yearsExp;
            $score -= min(30, $gap * 10); // Deduct 10 points per year gap, max 30
            $concerns[] = "Below minimum experience: {$yearsExp} years (requires {$requiredExp})";
            
            if ($gap >= 3) {
                $passed = false; // Hard fail if 3+ years short
            }
        } else {
            $strengths[] = "{$yearsExp} years of experience (exceeds minimum)";
        }

        // Check work authorization (if specified)
        if (isset($job->requires_work_authorization) && $job->requires_work_authorization) {
            if (!($candidate['work_authorized'] ?? false)) {
                $score -= 50;
                $concerns[] = "Work authorization not confirmed";
                $passed = false; // Legal requirement
            }
        }

        // Check location compatibility
        if (isset($job->location_requirement)) {
            $locationMatch = $this->checkLocationCompatibility(
                $candidate['location'] ?? null,
                $job->location_requirement,
                $job->remote_allowed ?? false
            );
            
            if (!$locationMatch) {
                $score -= 25;
                $concerns[] = "Location incompatible with job requirements";
                
                if (!($job->remote_allowed ?? false)) {
                    $passed = false;
                }
            }
        }

        // Check must-have certifications
        if (!empty($job->required_certifications)) {
            $candidateCerts = array_map('strtolower', $candidate['certifications'] ?? []);
            $requiredCerts = array_map('strtolower', $job->required_certifications);
            $missingCerts = array_diff($requiredCerts, $candidateCerts);
            
            if (!empty($missingCerts)) {
                $score -= count($missingCerts) * 15;
                $concerns[] = "Missing certifications: " . implode(', ', $missingCerts);
                
                if (count($missingCerts) > 2) {
                    $passed = false;
                }
            } else {
                $strengths[] = "Has all required certifications";
            }
        }

        // Ensure score doesn't go below 0
        $score = max(0, $score);

        // Pass threshold: 60 points
        if ($score < 60) {
            $passed = false;
        }

        return [
            'passed' => $passed,
            'score' => $score,
            'strengths' => $strengths,
            'concerns' => $concerns,
            'details' => [
                'education_met' => isset($hasEducation) ? $hasEducation : null,
                'experience_gap' => max(0, $requiredExp - $yearsExp),
                'certifications_complete' => empty($missingCerts ?? [])
            ]
        ];
    }

    /**
     * Round 2: Skills and Competency Matching
     *
     * Advanced AI evaluation of technical and soft skills.
     * Weights skills based on organizational priorities.
     *
     * @param array $candidate
     * @param Job $job
     * @return array
     */
    protected function executeRound2SkillsCompetency(array $candidate, Job $job): array
    {
        $candidateSkills = array_map('strtolower', $candidate['skills'] ?? []);
        $requiredSkills = array_map('strtolower', $job->required_skills ?? []);
        $preferredSkills = array_map('strtolower', $job->preferred_skills ?? []);
        
        // Get company success patterns for skill weighting
        $companyDNA = CompanyDNAProfile::where('company_id', $job->company_id)->first();
        $successTraits = $companyDNA ? ($companyDNA->success_traits ?? []) : [];

        $strengths = [];
        $concerns = [];
        
        // Required skills match (60% of score)
        $requiredMatches = array_intersect($candidateSkills, $requiredSkills);
        $requiredScore = empty($requiredSkills) ? 100 : (count($requiredMatches) / count($requiredSkills)) * 100;
        
        if ($requiredScore >= 80) {
            $strengths[] = "Strong match on required skills (" . count($requiredMatches) . "/" . count($requiredSkills) . ")";
        } elseif ($requiredScore < 60) {
            $missingSkills = array_diff($requiredSkills, $candidateSkills);
            $concerns[] = "Missing key skills: " . implode(', ', array_slice($missingSkills, 0, 5));
        }

        // Preferred skills match (20% of score)
        $preferredMatches = array_intersect($candidateSkills, $preferredSkills);
        $preferredScore = empty($preferredSkills) ? 100 : (count($preferredMatches) / count($preferredSkills)) * 100;
        
        if ($preferredScore >= 70) {
            $strengths[] = "Has " . count($preferredMatches) . " preferred skills";
        }

        // Success trait alignment (20% of score)
        $traitScore = $this->calculateTraitAlignment($candidate, $successTraits);
        
        if ($traitScore >= 75) {
            $strengths[] = "Strong alignment with company success traits";
        }

        // Calculate weighted competency score
        $competencyScore = (
            $requiredScore * 0.60 +
            $preferredScore * 0.20 +
            $traitScore * 0.20
        );

        // Soft skills evaluation
        $softSkillsScore = $this->evaluateSoftSkills($candidate, $job);
        
        // Overall Round 2 score
        $score = ($competencyScore * 0.70) + ($softSkillsScore * 0.30);

        // Pass threshold: 65 points
        $passed = $score >= 65;

        if (!$passed) {
            $concerns[] = "Overall skills competency below threshold";
        }

        return [
            'passed' => $passed,
            'score' => round($score, 1),
            'strengths' => $strengths,
            'concerns' => $concerns,
            'details' => [
                'required_skills_match' => round($requiredScore, 1),
                'preferred_skills_match' => round($preferredScore, 1),
                'trait_alignment' => round($traitScore, 1),
                'soft_skills_score' => round($softSkillsScore, 1),
                'matched_required' => $requiredMatches,
                'matched_preferred' => $preferredMatches
            ]
        ];
    }

    /**
     * Round 3: Cultural Fit Assessment
     *
     * Uses Corporate DNA to evaluate cultural alignment.
     * Analyzes communication style and team dynamics.
     *
     * @param array $candidate
     * @param Job $job
     * @param Application $application
     * @return array
     */
    protected function executeRound3CulturalFit(array $candidate, Job $job, Application $application): array
    {
        $companyDNA = CompanyDNAProfile::with('cultureAnalysis')
            ->where('company_id', $job->company_id)
            ->first();

        if (!$companyDNA) {
            // If no DNA profile, use basic assessment
            return [
                'passed' => true,
                'score' => 70,
                'strengths' => ['Cultural fit assessment pending DNA analysis'],
                'concerns' => [],
                'details' => ['dna_available' => false]
            ];
        }

        $strengths = [];
        $concerns = [];

        // Value alignment (40% of cultural score)
        $valueAlignment = $this->assessValueAlignment(
            $candidate['values'] ?? [],
            $companyDNA->core_values ?? []
        );

        if ($valueAlignment >= 75) {
            $strengths[] = "Strong alignment with company values";
        } elseif ($valueAlignment < 50) {
            $concerns[] = "Limited value alignment with company culture";
        }

        // Work style compatibility (30% of cultural score)
        $workStyleScore = $this->assessWorkStyleFit(
            $candidate['work_style'] ?? [],
            $companyDNA->work_style_preferences ?? []
        );

        if ($workStyleScore >= 75) {
            $strengths[] = "Work style matches company preferences";
        } elseif ($workStyleScore < 50) {
            $concerns[] = "Work style may not align with company culture";
        }

        // Communication style analysis (20% of cultural score)
        $communicationScore = $this->analyzeCommunicationStyle(
            $application->cover_letter ?? '',
            $companyDNA->communication_patterns ?? []
        );

        // Team dynamics prediction (10% of cultural score)
        $teamScore = $this->predictTeamDynamics($candidate, $job->department ?? 'General');

        // Calculate overall cultural fit score
        $culturalScore = (
            $valueAlignment * 0.40 +
            $workStyleScore * 0.30 +
            $communicationScore * 0.20 +
            $teamScore * 0.10
        );

        // Pass threshold: 60 points (cultural fit is important but flexible)
        $passed = $culturalScore >= 60;

        if (!$passed) {
            $concerns[] = "Cultural fit below acceptable threshold";
        } else if ($culturalScore >= 85) {
            $strengths[] = "Exceptional cultural fit - strong culture carrier potential";
        }

        return [
            'passed' => $passed,
            'score' => round($culturalScore, 1),
            'strengths' => $strengths,
            'concerns' => $concerns,
            'details' => [
                'value_alignment' => round($valueAlignment, 1),
                'work_style_fit' => round($workStyleScore, 1),
                'communication_style' => round($communicationScore, 1),
                'team_dynamics' => round($teamScore, 1)
            ]
        ];
    }

    /**
     * Round 4: Potential and Growth Analysis
     *
     * Evaluates learning agility, career trajectory, future potential.
     * Identifies candidates who can grow with the organization.
     *
     * @param array $candidate
     * @param Job $job
     * @return array
     */
    protected function executeRound4PotentialGrowth(array $candidate, Job $job): array
    {
        $strengths = [];
        $concerns = [];

        // Learning agility assessment (40% of growth score)
        $learningAgility = $this->assessLearningAgility($candidate);
        
        if ($learningAgility >= 80) {
            $strengths[] = "High learning agility - quick to adapt and grow";
        } elseif ($learningAgility < 50) {
            $concerns[] = "May require significant support for skill development";
        }

        // Career trajectory analysis (35% of growth score)
        $trajectoryScore = $this->analyzeCareerTrajectory($candidate['experience'] ?? []);
        
        if ($trajectoryScore >= 75) {
            $strengths[] = "Strong career progression and growth trajectory";
        } elseif ($trajectoryScore < 50) {
            $concerns[] = "Limited career progression evidence";
        }

        // Future potential evaluation (25% of growth score)
        $potentialScore = $this->evaluateFuturePotential($candidate, $job);
        
        if ($potentialScore >= 80) {
            $strengths[] = "High potential for advancement - succession planning candidate";
        }

        // Calculate overall growth score
        $growthScore = (
            $learningAgility * 0.40 +
            $trajectoryScore * 0.35 +
            $potentialScore * 0.25
        );

        // Pass threshold: 55 points (potential is bonus, not requirement)
        $passed = $growthScore >= 55;

        if (!$passed) {
            $concerns[] = "Limited growth potential for long-term value";
        }

        return [
            'passed' => $passed,
            'score' => round($growthScore, 1),
            'strengths' => $strengths,
            'concerns' => $concerns,
            'details' => [
                'learning_agility' => round($learningAgility, 1),
                'career_trajectory' => round($trajectoryScore, 1),
                'future_potential' => round($potentialScore, 1)
            ]
        ];
    }

    // Helper methods

    protected function buildCandidateProfile($user): array
    {
        $profile = $user->profile;
        
        return [
            'name' => $user->name,
            'email' => $user->email,
            'skills' => $profile->skills ?? [],
            'experience' => $profile->experience ?? [],
            'education' => $profile->education ?? [],
            'years_of_experience' => $profile->years_of_experience ?? 0,
            'values' => $profile->values ?? [],
            'work_style' => $profile->work_style_preferences ?? [],
            'certifications' => $profile->certifications ?? [],
            'location' => $profile->location ?? null,
            'work_authorized' => $profile->work_authorized ?? false,
            'achievements' => $profile->achievements ?? []
        ];
    }

    protected function meetsEducationRequirement(array $education, string $required): bool
    {
        $degreeHierarchy = [
            'high school' => 1,
            'associate' => 2,
            'bachelor' => 3,
            'master' => 4,
            'mba' => 4,
            'phd' => 5,
            'doctorate' => 5
        ];

        $requiredLevel = 0;
        foreach ($degreeHierarchy as $degree => $level) {
            if (str_contains(strtolower($required), $degree)) {
                $requiredLevel = $level;
                break;
            }
        }

        foreach ($education as $edu) {
            $candidateLevel = 0;
            foreach ($degreeHierarchy as $degree => $level) {
                if (str_contains(strtolower($edu['degree'] ?? ''), $degree)) {
                    $candidateLevel = max($candidateLevel, $level);
                }
            }
            
            if ($candidateLevel >= $requiredLevel) {
                return true;
            }
        }

        return false;
    }

    protected function checkLocationCompatibility($candidateLocation, $jobLocation, bool $remoteAllowed): bool
    {
        if ($remoteAllowed) {
            return true; // Remote positions are location-agnostic
        }

        if (!$candidateLocation || !$jobLocation) {
            return true; // Can't verify, assume compatible
        }

        // Simple city/state matching
        return str_contains(strtolower($candidateLocation), strtolower($jobLocation)) ||
               str_contains(strtolower($jobLocation), strtolower($candidateLocation));
    }

    protected function calculateTraitAlignment(array $candidate, array $successTraits): int
    {
        if (empty($successTraits)) {
            return 70; // Baseline if no traits defined
        }

        $candidateTraits = $candidate['traits'] ?? [];
        $matchCount = 0;

        foreach ($successTraits as $trait) {
            foreach ($candidateTraits as $candidateTrait) {
                if (str_contains(strtolower($candidateTrait), strtolower($trait['name'] ?? $trait))) {
                    $matchCount++;
                    break;
                }
            }
        }

        return min(100, ($matchCount / count($successTraits)) * 100 + 30); // Baseline 30 + match bonus
    }

    protected function evaluateSoftSkills(array $candidate, Job $job): int
    {
        $softSkillKeywords = [
            'leadership', 'communication', 'teamwork', 'collaboration',
            'problem-solving', 'critical thinking', 'adaptability', 'creativity'
        ];

        $candidateSkills = array_map('strtolower', $candidate['skills'] ?? []);
        $softSkillCount = 0;

        foreach ($softSkillKeywords as $soft) {
            foreach ($candidateSkills as $skill) {
                if (str_contains($skill, $soft)) {
                    $softSkillCount++;
                    break;
                }
            }
        }

        return min(100, ($softSkillCount / count($softSkillKeywords)) * 100 + 40);
    }

    protected function assessValueAlignment(array $candidateValues, array $companyValues): int
    {
        if (empty($companyValues)) {
            return 70;
        }

        $matchCount = 0;
        foreach ($companyValues as $companyValue) {
            foreach ($candidateValues as $candidateValue) {
                $similarity = similar_text(
                    strtolower($companyValue['name'] ?? $companyValue),
                    strtolower($candidateValue),
                    $percent
                );
                
                if ($percent > 60) {
                    $matchCount++;
                    break;
                }
            }
        }

        return min(100, ($matchCount / count($companyValues)) * 100 + 20);
    }

    protected function assessWorkStyleFit(array $candidateStyle, array $companyStyle): int
    {
        // Placeholder - would use more sophisticated matching
        $matches = array_intersect(
            array_map('strtolower', $candidateStyle),
            array_map('strtolower', $companyStyle)
        );

        return empty($companyStyle) ? 70 : min(100, (count($matches) / count($companyStyle)) * 100 + 30);
    }

    protected function analyzeCommunicationStyle(string $coverLetter, array $companyPatterns): int
    {
        // Analyze communication from cover letter
        // For now, baseline score
        return !empty($coverLetter) ? 75 : 60;
    }

    protected function predictTeamDynamics(array $candidate, string $department): int
    {
        // Would integrate with TeamDynamicsAnalyzerService
        // For now, baseline score
        return 70;
    }

    protected function assessLearningAgility(array $candidate): int
    {
        $score = 50; // Baseline

        // Evidence of continuous learning
        $recentEducation = collect($candidate['education'] ?? [])->filter(function ($edu) {
            $year = $edu['year'] ?? 0;
            return $year >= (date('Y') - 3); // Within last 3 years
        })->count();

        $score += min(20, $recentEducation * 10);

        // Diverse experience
        $experienceCount = count($candidate['experience'] ?? []);
        $score += min(15, $experienceCount * 3);

        // Certifications
        $certCount = count($candidate['certifications'] ?? []);
        $score += min(15, $certCount * 5);

        return min(100, $score);
    }

    protected function analyzeCareerTrajectory(array $experience): int
    {
        if (empty($experience)) {
            return 40;
        }

        $score = 50;
        $titleProgression = 0;

        // Check for title progression
        for ($i = 1; $i < count($experience); $i++) {
            $prevTitle = strtolower($experience[$i]['title'] ?? '');
            $currentTitle = strtolower($experience[$i - 1]['title'] ?? '');

            if (str_contains($currentTitle, 'senior') && !str_contains($prevTitle, 'senior')) {
                $titleProgression += 15;
            } elseif (str_contains($currentTitle, 'lead') || str_contains($currentTitle, 'manager')) {
                $titleProgression += 20;
            }
        }

        $score += min(30, $titleProgression);

        return min(100, $score);
    }

    protected function evaluateFuturePotential(array $candidate, Job $job): int
    {
        $score = 60; // Baseline

        // Young professionals with strong education
        $yearsExp = $candidate['years_of_experience'] ?? 0;
        if ($yearsExp <= 5) {
            $hasAdvancedDegree = collect($candidate['education'] ?? [])->contains(function ($edu) {
                return str_contains(strtolower($edu['degree'] ?? ''), 'master') ||
                       str_contains(strtolower($edu['degree'] ?? ''), 'phd');
            });

            if ($hasAdvancedDegree) {
                $score += 20;
            }
        }

        // High achievers
        if (count($candidate['achievements'] ?? []) >= 3) {
            $score += 20;
        }

        return min(100, $score);
    }

    protected function determineRecommendation(float $overallScore, array $rounds): string
    {
        if ($overallScore >= 85) {
            return 'STRONG HIRE - Top Candidate';
        } elseif ($overallScore >= 75) {
            return 'RECOMMEND - Good Fit';
        } elseif ($overallScore >= 65) {
            return 'CONSIDER - Acceptable Candidate';
        } else {
            return 'DO NOT SHORTLIST';
        }
    }

    protected function formatRejection(Application $application, array $result, int $round): array
    {
        return [
            'application_id' => $application->id,
            'candidate_name' => $application->user->name,
            'rejected_at_round' => $round,
            'reason' => $result["round_{$round}"]['concerns'] ?? ['Did not meet criteria'],
            'score' => $result["round_{$round}"]['score'] ?? 0
        ];
    }
}
