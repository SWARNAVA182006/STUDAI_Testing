<?php

namespace App\Services\AI\Scout;

use App\Models\CompanyDNAProfile;
use App\Models\CultureAnalysis;
use App\Models\HiringPattern;
use App\Models\SuccessIndicator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class SuccessPredictorService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const MODEL = 'gpt-5.1'; // Azure OpenAI GPT-5.1

    public function predictCandidateSuccess(int $companyId, array $candidateProfile): array
    {
        try {
            // Load company DNA profile
            $dnaProfile = CompanyDNAProfile::with(['cultureAnalysis'])
                ->where('company_id', $companyId)
                ->first();

            if (!$dnaProfile || !$dnaProfile->canGenerateJobRequirements()) {
                return [
                    'success' => false,
                    'message' => 'Company DNA profile incomplete or insufficient confidence',
                ];
            }

            // Calculate multi-dimensional fit scores
            $culturalFit = $this->calculateCulturalFit($dnaProfile, $candidateProfile);
            $skillFit = $this->calculateSkillFit($companyId, $candidateProfile);
            $workStyleFit = $this->calculateWorkStyleFit($dnaProfile, $candidateProfile);
            $performancePrediction = $this->predictPerformance($companyId, $candidateProfile);

            // AI-powered holistic assessment
            $aiAssessment = $this->performAIAssessment($dnaProfile, $candidateProfile, [
                'cultural_fit' => $culturalFit,
                'skill_fit' => $skillFit,
                'work_style_fit' => $workStyleFit,
                'performance_prediction' => $performancePrediction,
            ]);

            // Calculate overall success score
            $overallScore = $this->calculateOverallSuccessScore([
                'cultural_fit' => $culturalFit['score'],
                'skill_fit' => $skillFit['score'],
                'work_style_fit' => $workStyleFit['score'],
                'performance_prediction' => $performancePrediction['score'],
            ]);

            return [
                'success' => true,
                'overall_success_score' => $overallScore,
                'recommendation' => $this->getRecommendation($overallScore),
                'cultural_fit' => $culturalFit,
                'skill_fit' => $skillFit,
                'work_style_fit' => $workStyleFit,
                'performance_prediction' => $performancePrediction,
                'ai_assessment' => $aiAssessment,
                'strengths' => $this->identifyStrengths($culturalFit, $skillFit, $workStyleFit),
                'concerns' => $this->identifyConcerns($culturalFit, $skillFit, $workStyleFit),
            ];

        } catch (\Exception $e) {
            Log::error('Success Prediction Failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Prediction failed: ' . $e->getMessage(),
            ];
        }
    }

    private function calculateCulturalFit(CompanyDNAProfile $dnaProfile, array $candidate): array
    {
        $score = 50; // Base score
        $details = [];

        // Values alignment
        $companyValues = $dnaProfile->core_values ?? [];
        $candidateValues = $candidate['values'] ?? [];
        $valueMatch = count(array_intersect($companyValues, $candidateValues));
        
        if (!empty($companyValues)) {
            $valueScore = min(30, ($valueMatch / count($companyValues)) * 30);
            $score += $valueScore;
            $details['values_alignment'] = round(($valueMatch / count($companyValues)) * 100);
        }

        // Cultural DNA traits match
        $culturalDNA = $dnaProfile->cultural_dna ?? [];
        $candidateTraits = $candidate['traits'] ?? [];
        
        $dnaMatch = 0;
        foreach ($culturalDNA as $dna) {
            $trait = $dna['trait'] ?? '';
            if (in_array($trait, $candidateTraits)) {
                $dnaMatch += ($dna['score'] ?? 0) / 100 * 20;
            }
        }
        $score += min(20, $dnaMatch);
        $details['cultural_dna_match'] = round($dnaMatch / 20 * 100);

        // Culture analysis fit (if available)
        if ($dnaProfile->cultureAnalysis) {
            $cultureAnalysis = $dnaProfile->cultureAnalysis;
            
            // Innovation mindset
            if ($cultureAnalysis->isInnovationDriven() && ($candidate['innovation_mindset'] ?? false)) {
                $score += 10;
                $details['innovation_fit'] = true;
            }

            // Learning culture
            if ($cultureAnalysis->hasStrongLearningCulture() && ($candidate['learning_agility'] ?? 0) >= 70) {
                $score += 10;
                $details['learning_culture_fit'] = true;
            }

            // Work environment
            $candidateWorkPref = $candidate['work_environment_preference'] ?? '';
            if (stripos($cultureAnalysis->workEnvironmentType, $candidateWorkPref) !== false) {
                $score += 10;
                $details['work_environment_fit'] = true;
            }
        }

        return [
            'score' => min(100, (int) $score),
            'level' => $this->getFitLevel(min(100, $score)),
            'details' => $details,
        ];
    }

    private function calculateSkillFit(int $companyId, array $candidate): array
    {
        $score = 50;
        $details = [];

        // Get top performer skill patterns
        $topPerformers = SuccessIndicator::where('company_id', $companyId)
            ->where('employee_type', 'top_performer')
            ->get();

        if ($topPerformers->isEmpty()) {
            return [
                'score' => 50,
                'level' => 'Insufficient Data',
                'details' => ['message' => 'No top performer data available'],
            ];
        }

        // Extract common skills from top performers
        $topPerformerSkills = [];
        foreach ($topPerformers as $performer) {
            $skills = array_merge(
                $performer->technical_skills ?? [],
                $performer->soft_skills ?? []
            );
            foreach ($skills as $skill) {
                $topPerformerSkills[$skill] = ($topPerformerSkills[$skill] ?? 0) + 1;
            }
        }

        arsort($topPerformerSkills);
        $criticalSkills = array_slice(array_keys($topPerformerSkills), 0, 10);

        // Calculate candidate skill match
        $candidateSkills = $candidate['skills'] ?? [];
        $matchedSkills = array_intersect($candidateSkills, $criticalSkills);
        $matchCount = count($matchedSkills);

        if (!empty($criticalSkills)) {
            $skillMatchScore = ($matchCount / count($criticalSkills)) * 50;
            $score += $skillMatchScore;
            $details['critical_skills_matched'] = $matchCount;
            $details['total_critical_skills'] = count($criticalSkills);
            $details['match_percentage'] = round(($matchCount / count($criticalSkills)) * 100);
        }

        return [
            'score' => min(100, (int) $score),
            'level' => $this->getFitLevel(min(100, $score)),
            'details' => $details,
            'matched_skills' => $matchedSkills,
            'missing_skills' => array_diff($criticalSkills, $candidateSkills),
        ];
    }

    private function calculateWorkStyleFit(CompanyDNAProfile $dnaProfile, array $candidate): array
    {
        $score = 50;
        $details = [];

        $companyWorkStyle = $dnaProfile->work_style_preferences ?? [];
        $candidateWorkStyle = $candidate['work_style'] ?? [];

        if (empty($companyWorkStyle)) {
            return [
                'score' => 50,
                'level' => 'Insufficient Data',
                'details' => ['message' => 'No company work style data'],
            ];
        }

        // Work style preference match
        $matches = 0;
        foreach ($companyWorkStyle as $style) {
            if (in_array($style, $candidateWorkStyle)) {
                $matches++;
            }
        }

        if (!empty($companyWorkStyle)) {
            $matchScore = ($matches / count($companyWorkStyle)) * 40;
            $score += $matchScore;
            $details['style_match_count'] = $matches;
            $details['match_percentage'] = round(($matches / count($companyWorkStyle)) * 100);
        }

        // Communication pattern match
        $companyCommunication = $dnaProfile->communication_patterns ?? [];
        $candidateCommunication = $candidate['communication_style'] ?? [];
        
        $commMatches = count(array_intersect($companyCommunication, $candidateCommunication));
        if (!empty($companyCommunication)) {
            $score += ($commMatches / count($companyCommunication)) * 10;
            $details['communication_match'] = round(($commMatches / count($companyCommunication)) * 100);
        }

        return [
            'score' => min(100, (int) $score),
            'level' => $this->getFitLevel(min(100, $score)),
            'details' => $details,
        ];
    }

    private function predictPerformance(int $companyId, array $candidate): array
    {
        // Get hiring patterns
        $hiringPattern = HiringPattern::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$hiringPattern) {
            return [
                'score' => 50,
                'level' => 'Insufficient Data',
                'details' => ['message' => 'No hiring pattern data'],
            ];
        }

        $score = 50;
        $details = [];

        // Check against successful hire characteristics
        $successCharacteristics = $hiringPattern->successful_hire_characteristics ?? [];
        $matches = 0;
        
        foreach ($successCharacteristics as $characteristic) {
            // Simple keyword matching in candidate profile
            $candidateText = json_encode($candidate);
            if (stripos($candidateText, $characteristic) !== false) {
                $matches++;
            }
        }

        if (!empty($successCharacteristics)) {
            $score += ($matches / count($successCharacteristics)) * 30;
            $details['success_characteristics_matched'] = $matches;
        }

        // Check against failure patterns
        $failurePatterns = $hiringPattern->unsuccessful_hire_patterns ?? [];
        $redFlags = 0;
        
        foreach ($failurePatterns as $pattern) {
            $candidateText = json_encode($candidate);
            if (stripos($candidateText, $pattern) !== false) {
                $redFlags++;
            }
        }

        if ($redFlags > 0) {
            $score -= ($redFlags * 10);
            $details['red_flags_detected'] = $redFlags;
        }

        // Experience level fit
        $optimalExperience = $hiringPattern->optimal_experience_ranges['optimal'] ?? null;
        $candidateExp = $candidate['years_experience'] ?? 0;
        
        if ($optimalExperience && $candidateExp >= $optimalExperience['min'] && $candidateExp <= $optimalExperience['max']) {
            $score += 20;
            $details['experience_fit'] = 'Optimal';
        }

        return [
            'score' => min(100, max(0, (int) $score)),
            'level' => $this->getPerformanceLevel(min(100, max(0, $score))),
            'details' => $details,
        ];
    }

    private function performAIAssessment(CompanyDNAProfile $dnaProfile, array $candidate, array $scores): array
    {
        // Pre-format complex expressions for heredoc
        $coreValues = $this->formatArray($dnaProfile->core_values ?? []);
        $culturalDna = $this->formatCulturalDNA($dnaProfile->cultural_dna ?? []);
        $successTraits = $this->formatSuccessTraits($dnaProfile->success_traits ?? []);
        $candidateSkills = $this->formatArray($candidate['skills'] ?? []);
        $yearsExp = $candidate['years_experience'] ?? 0;
        $candidateValues = $this->formatArray($candidate['values'] ?? []);
        $candidateWorkStyle = $this->formatArray($candidate['work_style'] ?? []);
        
        $prompt = <<<PROMPT
Provide a holistic assessment of this candidate's fit and success potential.

**Company DNA:**
- Mission: {$dnaProfile->mission_statement}
- Core Values: {$coreValues}
- Cultural DNA: {$culturalDna}
- Success Traits: {$successTraits}

**Candidate Profile:**
- Skills: {$candidateSkills}
- Experience: {$yearsExp} years
- Values: {$candidateValues}
- Work Style: {$candidateWorkStyle}

**Quantitative Scores:**
- Cultural Fit: {$scores['cultural_fit']['score']}/100
- Skill Fit: {$scores['skill_fit']['score']}/100
- Work Style Fit: {$scores['work_style_fit']['score']}/100
- Performance Prediction: {$scores['performance_prediction']['score']}/100

Provide assessment in JSON:
{
  "overall_assessment": "2-3 sentence summary of fit and potential",
  "key_strengths": ["Strength 1", "Strength 2", "Strength 3"],
  "potential_challenges": ["Challenge 1", "Challenge 2"],
  "success_probability": "High" | "Medium" | "Low",
  "recommendation": "Strong Hire" | "Hire with Conditions" | "Pass" | "Further Evaluation",
  "onboarding_focus": ["Area 1 to focus during onboarding", "Area 2"]
}
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => self::MODEL,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert hiring decision consultant providing holistic candidate assessments.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response->choices[0]->message->content, true) ?? [];
    }

    private function calculateOverallSuccessScore(array $scores): int
    {
        return (int) round(
            ($scores['cultural_fit'] * 0.35) +
            ($scores['skill_fit'] * 0.30) +
            ($scores['work_style_fit'] * 0.20) +
            ($scores['performance_prediction'] * 0.15)
        );
    }

    private function getRecommendation(int $score): string
    {
        if ($score >= 85) return 'Strong Hire - Excellent Fit';
        if ($score >= 70) return 'Hire - Good Fit';
        if ($score >= 55) return 'Conditional Hire - Monitor Closely';
        if ($score >= 40) return 'Further Evaluation Needed';
        return 'Pass - Poor Fit';
    }

    private function getFitLevel(int $score): string
    {
        if ($score >= 85) return 'Excellent Fit';
        if ($score >= 70) return 'Strong Fit';
        if ($score >= 55) return 'Moderate Fit';
        return 'Weak Fit';
    }

    private function getPerformanceLevel(int $score): string
    {
        if ($score >= 80) return 'High Performance Likely';
        if ($score >= 60) return 'Average Performance Expected';
        if ($score >= 40) return 'Performance Risk';
        return 'High Performance Risk';
    }

    private function identifyStrengths(array $culturalFit, array $skillFit, array $workStyleFit): array
    {
        $strengths = [];

        if ($culturalFit['score'] >= 75) {
            $strengths[] = 'Strong cultural alignment with company values';
        }

        if ($skillFit['score'] >= 75) {
            $strengths[] = 'Possesses critical skills for success';
        }

        if ($workStyleFit['score'] >= 75) {
            $strengths[] = 'Work style matches company preferences';
        }

        if (isset($skillFit['matched_skills']) && count($skillFit['matched_skills']) > 0) {
            $strengths[] = 'Matched ' . count($skillFit['matched_skills']) . ' critical skills';
        }

        return $strengths;
    }

    private function identifyConcerns(array $culturalFit, array $skillFit, array $workStyleFit): array
    {
        $concerns = [];

        if ($culturalFit['score'] < 55) {
            $concerns[] = 'Cultural fit concerns - may struggle with company values';
        }

        if ($skillFit['score'] < 55) {
            $concerns[] = 'Skill gaps in critical areas';
        }

        if ($workStyleFit['score'] < 55) {
            $concerns[] = 'Work style mismatch with company preferences';
        }

        if (isset($skillFit['missing_skills']) && count($skillFit['missing_skills']) > 3) {
            $concerns[] = 'Missing ' . count($skillFit['missing_skills']) . ' critical skills';
        }

        return $concerns;
    }

    // Formatting helpers
    private function formatArray(array $arr): string
    {
        return empty($arr) ? 'None' : implode(', ', $arr);
    }

    private function formatCulturalDNA(array $dna): string
    {
        if (empty($dna)) return 'No data';
        
        $traits = array_slice($dna, 0, 5);
        $formatted = [];
        foreach ($traits as $item) {
            $trait = $item['trait'] ?? 'Unknown';
            $score = $item['score'] ?? 0;
            $formatted[] = "{$trait} ({$score})";
        }
        return implode(', ', $formatted);
    }

    private function formatSuccessTraits(array $traits): string
    {
        if (empty($traits)) return 'No data';
        
        $top5 = array_slice($traits, 0, 5);
        $formatted = [];
        foreach ($top5 as $item) {
            $trait = $item['trait'] ?? 'Unknown';
            $formatted[] = $trait;
        }
        return implode(', ', $formatted);
    }
}
