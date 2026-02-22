<?php

namespace App\Services\AI;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CandidateScreeningService
{
    protected AIService $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Analyze candidate for a specific application
     */
    public function analyzeCandidate(Application $application): array
    {
        $cacheKey = "candidate_screening_{$application->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($application) {
            $candidate = $application->user;
            $job = $application->job;
            $profile = $candidate->profile;
            
            $analysis = [
                'skill_match' => $this->analyzeSkillMatch($candidate, $job),
                'experience_match' => $this->analyzeExperienceMatch($candidate, $job),
                'culture_fit' => $this->analyzeCultureFit($candidate, $job),
                'strengths' => $this->identifyStrengths($candidate, $job),
                'concerns' => $this->identifyConcerns($candidate, $job),
                'recommendation' => $this->generateRecommendation($candidate, $job),
                'interview_questions' => $this->generateInterviewQuestions($candidate, $job),
            ];
            
            return $analysis;
        });
    }
    
    /**
     * Analyze skill match between candidate and job
     */
    protected function analyzeSkillMatch(User $candidate, Job $job): array
    {
        $profile = $candidate->profile;
        $candidateSkills = $profile->skills ?? [];
        $requiredSkills = $job->required_skills ?? [];
        $preferredSkills = $job->preferred_skills ?? [];
        
        $matchedRequired = array_intersect($candidateSkills, $requiredSkills);
        $matchedPreferred = array_intersect($candidateSkills, $preferredSkills);
        $missingRequired = array_diff($requiredSkills, $candidateSkills);
        
        $requiredScore = count($requiredSkills) > 0 
            ? (count($matchedRequired) / count($requiredSkills)) * 100 
            : 100;
        
        $preferredScore = count($preferredSkills) > 0 
            ? (count($matchedPreferred) / count($preferredSkills)) * 100 
            : 100;
        
        $overallScore = ($requiredScore * 0.7) + ($preferredScore * 0.3);
        
        return [
            'score' => round($overallScore, 1),
            'matched_required' => array_values($matchedRequired),
            'matched_preferred' => array_values($matchedPreferred),
            'missing_required' => array_values($missingRequired),
            'additional_skills' => array_values(array_diff($candidateSkills, array_merge($requiredSkills, $preferredSkills))),
        ];
    }
    
    /**
     * Analyze experience match
     */
    protected function analyzeExperienceMatch(User $candidate, Job $job): array
    {
        $profile = $candidate->profile;
        $candidateExperience = $profile->total_experience ?? 0;
        $requiredExperience = $job->min_experience ?? 0;
        $preferredExperience = $job->preferred_experience ?? $requiredExperience;
        
        $score = 0;
        $assessment = '';
        
        if ($candidateExperience >= $preferredExperience) {
            $score = 100;
            $assessment = 'Exceeds requirements';
        } elseif ($candidateExperience >= $requiredExperience) {
            $score = 70 + (($candidateExperience - $requiredExperience) / ($preferredExperience - $requiredExperience)) * 30;
            $assessment = 'Meets requirements';
        } else {
            $score = ($candidateExperience / $requiredExperience) * 70;
            $assessment = 'Below requirements';
        }
        
        // Analyze relevant experience
        $relevantExperience = $this->calculateRelevantExperience($profile->experience ?? [], $job);
        
        return [
            'score' => round($score, 1),
            'total_years' => $candidateExperience,
            'required_years' => $requiredExperience,
            'assessment' => $assessment,
            'relevant_experience_years' => $relevantExperience,
        ];
    }
    
    /**
     * Analyze culture fit using AI
     */
    protected function analyzeCultureFit(User $candidate, Job $job): array
    {
        try {
            $profile = $candidate->profile;
            $company = $job->company;
            
            $prompt = "Analyze culture fit between candidate and company:
            
Candidate Profile:
- Bio: {$profile->bio}
- Career Goals: {$profile->career_goals}
- Work Preferences: " . json_encode($profile->work_preferences ?? []) . "

Company Culture:
- Description: {$company->description}
- Values: " . json_encode($company->values ?? []) . "
- Work Environment: {$company->work_environment}

Provide a culture fit analysis with:
1. Alignment score (0-100)
2. Key alignment points
3. Potential mismatches
4. Overall assessment

Format as JSON: {\"score\": 0-100, \"alignment_points\": [], \"mismatches\": [], \"assessment\": \"\"}";
            
            // Use public wrapper with explicit model & temperature overrides
            $response = $this->aiService->generateText($prompt, null, [
                'model' => 'gpt-5',
                'temperature' => 0.3,
            ]);
            $analysis = json_decode($response, true);
            
            return $analysis ?? [
                'score' => 70,
                'alignment_points' => ['Unable to analyze - using default'],
                'mismatches' => [],
                'assessment' => 'Standard culture fit analysis',
            ];
            
        } catch (\Exception $e) {
            Log::error('Culture fit analysis failed', ['error' => $e->getMessage()]);
            return [
                'score' => 70,
                'alignment_points' => ['Analysis unavailable'],
                'mismatches' => [],
                'assessment' => 'Unable to complete analysis',
            ];
        }
    }
    
    /**
     * Identify candidate strengths
     */
    protected function identifyStrengths(User $candidate, Job $job): array
    {
        $strengths = [];
        $profile = $candidate->profile;
        
        // Skill-based strengths
        $skillMatch = $this->analyzeSkillMatch($candidate, $job);
        if ($skillMatch['score'] >= 80) {
            $strengths[] = 'Strong skill match with ' . count($skillMatch['matched_required']) . ' required skills';
        }
        
        // Experience-based strengths
        $expMatch = $this->analyzeExperienceMatch($candidate, $job);
        if ($expMatch['score'] >= 80) {
            $strengths[] = $expMatch['total_years'] . ' years of experience exceeds requirements';
        }
        
        // Education-based strengths
        if (!empty($profile->education)) {
            $highestDegree = $this->getHighestDegree($profile->education);
            if ($highestDegree) {
                $strengths[] = "Education: {$highestDegree['degree']} in {$highestDegree['field']}";
            }
        }
        
        // Career progression
        if ($this->hasStrongCareerProgression($profile->experience ?? [])) {
            $strengths[] = 'Demonstrated career progression';
        }
        
        // Additional skills
        if (count($skillMatch['additional_skills']) >= 3) {
            $strengths[] = 'Brings additional valuable skills: ' . implode(', ', array_slice($skillMatch['additional_skills'], 0, 3));
        }
        
        return $strengths;
    }
    
    /**
     * Identify potential concerns
     */
    protected function identifyConcerns(User $candidate, Job $job): array
    {
        $concerns = [];
        $profile = $candidate->profile;
        
        // Missing required skills
        $skillMatch = $this->analyzeSkillMatch($candidate, $job);
        if (!empty($skillMatch['missing_required'])) {
            $concerns[] = 'Missing required skills: ' . implode(', ', $skillMatch['missing_required']);
        }
        
        // Experience gap
        $expMatch = $this->analyzeExperienceMatch($candidate, $job);
        if ($expMatch['score'] < 70) {
            $concerns[] = "Experience below requirement ({$expMatch['total_years']} vs {$expMatch['required_years']} years)";
        }
        
        // Job hopping (average tenure < 1 year)
        if ($this->hasJobHoppingPattern($profile->experience ?? [])) {
            $concerns[] = 'Frequent job changes (potential retention risk)';
        }
        
        // Location mismatch
        if ($job->location && $profile->location && $job->location !== $profile->location && $job->work_mode === 'onsite') {
            $concerns[] = "Location mismatch (candidate in {$profile->location}, job in {$job->location})";
        }
        
        // Salary expectations
        if (isset($profile->expected_salary) && isset($job->max_salary) && $profile->expected_salary > $job->max_salary) {
            $concerns[] = 'Salary expectation exceeds budget';
        }
        
        return $concerns;
    }
    
    /**
     * Generate hiring recommendation
     */
    protected function generateRecommendation(User $candidate, Job $job): array
    {
        $skillMatch = $this->analyzeSkillMatch($candidate, $job);
        $expMatch = $this->analyzeExperienceMatch($candidate, $job);
        $strengths = $this->identifyStrengths($candidate, $job);
        $concerns = $this->identifyConcerns($candidate, $job);
        
        $overallScore = ($skillMatch['score'] * 0.5) + ($expMatch['score'] * 0.3) + (70 * 0.2); // 70 is culture fit placeholder
        
        $recommendation = '';
        $action = '';
        
        if ($overallScore >= 85) {
            $recommendation = 'Strong Match';
            $action = 'Move to interview immediately';
            $priority = 'high';
        } elseif ($overallScore >= 70) {
            $recommendation = 'Good Match';
            $action = 'Shortlist for review';
            $priority = 'medium';
        } elseif ($overallScore >= 50) {
            $recommendation = 'Potential Match';
            $action = 'Consider if other candidates unavailable';
            $priority = 'low';
        } else {
            $recommendation = 'Weak Match';
            $action = 'Not recommended';
            $priority = 'reject';
        }
        
        return [
            'overall_score' => round($overallScore, 1),
            'recommendation' => $recommendation,
            'action' => $action,
            'priority' => $priority,
            'summary' => $this->generateRecommendationSummary($overallScore, $strengths, $concerns),
        ];
    }
    
    /**
     * Generate interview questions based on candidate profile
     */
    protected function generateInterviewQuestions(User $candidate, Job $job): array
    {
        $profile = $candidate->profile;
        $skillMatch = $this->analyzeSkillMatch($candidate, $job);
        
        $questions = [];
        
        // Questions about missing skills
        foreach (array_slice($skillMatch['missing_required'], 0, 2) as $skill) {
            $questions[] = [
                'category' => 'Skills Gap',
                'question' => "How would you approach learning {$skill} if hired for this role?",
            ];
        }
        
        // Questions about experience
        if (!empty($profile->experience)) {
            $latestJob = $profile->experience[0] ?? null;
            if ($latestJob) {
                $questions[] = [
                    'category' => 'Experience',
                    'question' => "Can you describe your biggest achievement at {$latestJob['company']}?",
                ];
            }
        }
        
        // Questions about job changes (if applicable)
        if ($this->hasJobHoppingPattern($profile->experience ?? [])) {
            $questions[] = [
                'category' => 'Career Stability',
                'question' => "I noticed you've changed jobs frequently. Can you walk me through your career decisions?",
            ];
        }
        
        // Role-specific questions
        $questions[] = [
            'category' => 'Role Fit',
            'question' => "What interests you most about this {$job->title} position?",
        ];
        
        $questions[] = [
            'category' => 'Team Collaboration',
            'question' => "Describe your preferred working style in a team environment.",
        ];
        
        return $questions;
    }
    
    /**
     * Compare multiple candidates
     */
    public function compareCandidates(Collection $applications): array
    {
        $comparison = [
            'candidates' => [],
            'top_candidate' => null,
            'comparison_matrix' => [],
        ];
        
        $highestScore = 0;
        $topCandidate = null;
        
        foreach ($applications as $application) {
            $analysis = $this->analyzeCandidate($application);
            
            $candidateData = [
                'application_id' => $application->id,
                'name' => $application->user->name,
                'overall_score' => $analysis['recommendation']['overall_score'],
                'skill_match' => $analysis['skill_match']['score'],
                'experience_match' => $analysis['experience_match']['score'],
                'strengths_count' => count($analysis['strengths']),
                'concerns_count' => count($analysis['concerns']),
                'recommendation' => $analysis['recommendation']['recommendation'],
            ];
            
            $comparison['candidates'][] = $candidateData;
            
            if ($candidateData['overall_score'] > $highestScore) {
                $highestScore = $candidateData['overall_score'];
                $topCandidate = $candidateData;
            }
        }
        
        $comparison['top_candidate'] = $topCandidate;
        
        // Generate comparison matrix
        $metrics = ['skill_match', 'experience_match', 'overall_score'];
        foreach ($metrics as $metric) {
            $comparison['comparison_matrix'][$metric] = array_column($comparison['candidates'], $metric);
        }
        
        return $comparison;
    }
    
    // Helper methods
    
    protected function calculateRelevantExperience(array $experiences, Job $job): float
    {
        $relevantYears = 0;
        $jobSkills = array_merge($job->required_skills ?? [], $job->preferred_skills ?? []);
        
        foreach ($experiences as $exp) {
            $expSkills = $exp['skills'] ?? [];
            $overlap = count(array_intersect($expSkills, $jobSkills));
            
            if ($overlap > 0) {
                $startDate = new \DateTime($exp['start_date'] ?? 'now');
                $endDate = $exp['is_current'] ?? false ? new \DateTime() : new \DateTime($exp['end_date'] ?? 'now');
                $diff = $startDate->diff($endDate);
                $years = $diff->y + ($diff->m / 12);
                $relevantYears += $years * ($overlap / count($jobSkills));
            }
        }
        
        return round($relevantYears, 1);
    }
    
    protected function getHighestDegree(array $education): ?array
    {
        $degreeOrder = ['PhD' => 5, 'Masters' => 4, 'Bachelors' => 3, 'Associates' => 2, 'High School' => 1];
        
        $highest = null;
        $highestRank = 0;
        
        foreach ($education as $edu) {
            $degree = $edu['degree'] ?? '';
            foreach ($degreeOrder as $degreeType => $rank) {
                if (stripos($degree, $degreeType) !== false && $rank > $highestRank) {
                    $highestRank = $rank;
                    $highest = $edu;
                }
            }
        }
        
        return $highest;
    }
    
    protected function hasStrongCareerProgression(array $experiences): bool
    {
        if (count($experiences) < 2) {
            return false;
        }
        
        // Simple check: are more recent positions more senior?
        // This is a simplified version - could be enhanced with title parsing
        return true; // Placeholder
    }
    
    protected function hasJobHoppingPattern(array $experiences): bool
    {
        if (count($experiences) < 3) {
            return false;
        }
        
        $tenures = [];
        foreach ($experiences as $exp) {
            $startDate = new \DateTime($exp['start_date'] ?? 'now');
            $endDate = $exp['is_current'] ?? false ? new \DateTime() : new \DateTime($exp['end_date'] ?? 'now');
            $diff = $startDate->diff($endDate);
            $months = ($diff->y * 12) + $diff->m;
            $tenures[] = $months;
        }
        
        $avgTenure = array_sum($tenures) / count($tenures);
        return $avgTenure < 12; // Less than 1 year average
    }
    
    protected function generateRecommendationSummary(float $score, array $strengths, array $concerns): string
    {
        $summary = "Overall match score: {$score}%. ";
        
        if (count($strengths) > 0) {
            $summary .= "Key strengths: " . implode('; ', array_slice($strengths, 0, 2)) . ". ";
        }
        
        if (count($concerns) > 0) {
            $summary .= "Concerns: " . implode('; ', array_slice($concerns, 0, 2)) . ".";
        } else {
            $summary .= "No major concerns identified.";
        }
        
        return $summary;
    }
}
