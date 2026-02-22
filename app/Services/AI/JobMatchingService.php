<?php

namespace App\Services\AI;

use App\Models\CompanyBlacklist;
use App\Models\Job;
use App\Models\User;
use App\Models\Profile;

class JobMatchingService extends AIService
{
    /**
     * Calculate match score between a user profile and a job
     *
     * @param Profile $profile
     * @param Job $job
     * @param bool $checkBlacklist If true, returns null for blacklisted companies
     * @return array|null Returns null if company is blacklisted
     */
    public function calculateMatchScore(Profile $profile, Job $job, bool $checkBlacklist = true): ?array
    {
        // Check if company is blacklisted for this user
        if ($checkBlacklist && $profile->user_id) {
            if ($this->isCompanyBlacklisted($profile->user_id, $job)) {
                return null;
            }
        }

        // Generate embeddings for semantic matching
        $profileEmbedding = $this->generateProfileEmbedding($profile);
        $jobEmbedding = $this->generateJobEmbedding($job);
        
        // Calculate semantic similarity
        $semanticScore = $this->cosineSimilarity($profileEmbedding, $jobEmbedding) * 100;
        
        // Get detailed match analysis from AI
        $analysis = $this->getDetailedMatchAnalysis($profile, $job);
        
        // Combine scores with weights
        $finalScore = $this->calculateWeightedScore([
            'semantic' => $semanticScore,
            'skills' => $analysis['skills_match_score'],
            'experience' => $analysis['experience_match_score'],
            'location' => $analysis['location_match_score'],
            'salary' => $analysis['salary_match_score'],
        ]);
        
        return [
            'overall_score' => round($finalScore, 1),
            'semantic_score' => round($semanticScore, 1),
            'breakdown' => $analysis,
            'match_level' => $this->getMatchLevel($finalScore),
            'recommendation' => $this->getRecommendation($finalScore, $analysis),
        ];
    }

    /**
     * Generate embedding for user profile
     */
    protected function generateProfileEmbedding(Profile $profile): array
    {
        $text = $this->profileToText($profile);
        return $this->generateEmbedding($text);
    }

    /**
     * Generate embedding for job posting
     */
    protected function generateJobEmbedding(Job $job): array
    {
        $text = $this->jobToText($job);
        return $this->generateEmbedding($text);
    }

    /**
     * Convert profile to text for embedding
     */
    protected function profileToText(Profile $profile): string
    {
        $parts = [];
        
        // Headline and summary
        if ($profile->headline) {
            $parts[] = "Professional headline: " . $profile->headline;
        }
        if ($profile->summary) {
            $parts[] = "Summary: " . $profile->summary;
        }
        
        // Skills
        if (!empty($profile->skills)) {
            $skillsList = collect($profile->skills)->pluck('name')->implode(', ');
            $parts[] = "Skills: " . $skillsList;
        }
        
        // Experience
        if (!empty($profile->experience)) {
            foreach ($profile->experience as $exp) {
                $parts[] = sprintf(
                    "Experience: %s at %s - %s",
                    $exp['title'] ?? '',
                    $exp['company'] ?? '',
                    $exp['description'] ?? ''
                );
            }
        }
        
        // Education
        if (!empty($profile->education)) {
            foreach ($profile->education as $edu) {
                $parts[] = sprintf(
                    "Education: %s in %s from %s",
                    $edu['degree'] ?? '',
                    $edu['field'] ?? '',
                    $edu['institution'] ?? ''
                );
            }
        }
        
        return implode("\n", $parts);
    }

    /**
     * Convert job to text for embedding
     */
    protected function jobToText(Job $job): string
    {
        $parts = [
            "Job title: " . $job->title,
            "Description: " . $job->description,
        ];
        
        if (!empty($job->requirements)) {
            $parts[] = "Requirements: " . json_encode($job->requirements);
        }
        
        if (!empty($job->extracted_skills)) {
            $parts[] = "Skills: " . implode(', ', $job->extracted_skills);
        }
        
        $parts[] = "Experience level: " . $job->experience_level;
        $parts[] = "Employment type: " . $job->employment_type;
        
        return implode("\n", $parts);
    }

    /**
     * Get detailed match analysis using AI
     */
    protected function getDetailedMatchAnalysis(Profile $profile, Job $job): array
    {
        $systemPrompt = "You are an expert job matching analyst. Analyze how well a candidate matches a job opportunity.";

        $profileData = [
            'headline' => $profile->headline,
            'summary' => $profile->summary,
            'skills' => $profile->skills,
            'experience' => $profile->experience,
            'education' => $profile->education,
            'years_experience' => $this->calculateYearsOfExperience($profile),
            'location' => $profile->current_location,
            'expected_salary' => $profile->expected_salary_min . '-' . $profile->expected_salary_max,
        ];

        $jobData = [
            'title' => $job->title,
            'description' => $job->description,
            'requirements' => $job->requirements,
            'skills' => $job->extracted_skills ?? [],
            'experience_level' => $job->experience_level,
            'location' => $job->location,
            'salary_range' => $job->salary_min . '-' . $job->salary_max,
        ];

        $profileJson = json_encode($profileData, JSON_PRETTY_PRINT);
        $jobJson = json_encode($jobData, JSON_PRETTY_PRINT);
        
        $prompt = <<<PROMPT
Analyze the match between this candidate and job. Return JSON:

{
  "skills_match_score": 85,
  "skills_analysis": {
    "matching": ["Skill 1", "Skill 2"],
    "missing": ["Skill 3"],
    "transferable": ["Skill 4 that's similar to required Skill 5"]
  },
  "experience_match_score": 75,
  "experience_analysis": {
    "years_required": "3-5",
    "years_candidate": "4",
    "level_match": "Good fit",
    "relevant_roles": ["Previous role that's relevant"]
  },
  "education_match_score": 90,
  "education_analysis": {
    "meets_requirements": true,
    "relevant_degrees": ["Degree that matches"]
  },
  "location_match_score": 100,
  "location_analysis": {
    "candidate_location": "Location",
    "job_location": "Location",
    "work_mode_compatible": true
  },
  "salary_match_score": 95,
  "salary_analysis": {
    "candidate_expectation": "Range",
    "job_offer": "Range",
    "alignment": "Well aligned"
  },
  "cultural_fit_score": 80,
  "cultural_fit_analysis": "Assessment based on profile",
  "growth_potential_score": 75,
  "growth_analysis": "How this role helps career growth",
  "strengths": ["Top 3 reasons candidate is a good fit"],
  "concerns": ["Top 3 potential concerns"],
  "interview_focus_areas": ["Topics to discuss in interview"]
}

Candidate Profile:
{$profileJson}

Job Posting:
{$jobJson}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Calculate weighted score from components
     */
    protected function calculateWeightedScore(array $scores): float
    {
        $weights = [
            'semantic' => 0.20,  // 20% - Overall semantic similarity
            'skills' => 0.40,     // 40% - Skills match (most important)
            'experience' => 0.20, // 20% - Experience level
            'location' => 0.10,   // 10% - Location compatibility
            'salary' => 0.10,     // 10% - Salary alignment
        ];
        
        $totalScore = 0;
        foreach ($scores as $component => $score) {
            $totalScore += ($score * $weights[$component]);
        }
        
        return $totalScore;
    }

    /**
     * Get match level based on score
     */
    protected function getMatchLevel(float $score): string
    {
        if ($score >= 85) return 'Excellent Match';
        if ($score >= 70) return 'Good Match';
        if ($score >= 60) return 'Moderate Match';
        if ($score >= 50) return 'Possible Match';
        return 'Low Match';
    }

    /**
     * Get recommendation based on match analysis
     */
    protected function getRecommendation(float $score, array $analysis): string
    {
        if ($score >= 80) {
            return "Highly recommended! This is an excellent match for your profile. You should definitely apply.";
        }
        
        if ($score >= 65) {
            $missing = implode(', ', array_slice($analysis['skills_analysis']['missing'] ?? [], 0, 3));
            return "Good match overall. Consider highlighting these skills if you have them: $missing";
        }
        
        if ($score >= 50) {
            return "Moderate match. Review the job requirements carefully and tailor your application to emphasize relevant experience.";
        }
        
        return "This role may be a stretch. Consider roles better aligned with your current skills and experience.";
    }

    /**
     * Find similar jobs based on a job
     */
    public function findSimilarJobs(Job $job, int $limit = 10): array
    {
        $jobEmbedding = $this->generateJobEmbedding($job);
        
        $similarJobs = Job::where('id', '!=', $job->id)
            ->where('status', 'active')
            ->get()
            ->map(function($otherJob) use ($jobEmbedding) {
                $otherEmbedding = $this->generateJobEmbedding($otherJob);
                $similarity = $this->cosineSimilarity($jobEmbedding, $otherEmbedding);
                
                return [
                    'job' => $otherJob,
                    'similarity_score' => round($similarity * 100, 1),
                ];
            })
            ->filter(fn($item) => $item['similarity_score'] >= 60)
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values()
            ->toArray();
        
        return $similarJobs;
    }

    /**
     * Get personalized job recommendations for a user
     */
    public function getRecommendations(User $user, array $filters = [], int $limit = 20): array
    {
        $profile = $user->profile;
        if (!$profile) {
            return [];
        }

        // Get active jobs with filters
        $query = Job::where('status', 'active');

        if (!empty($filters['location'])) {
            $query->where('location', 'LIKE', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['work_mode'])) {
            $query->where('work_mode', $filters['work_mode']);
        }

        if (!empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        $jobs = $query->get();

        // Filter out blacklisted companies
        $jobs = $this->filterBlacklistedJobs($user->id, $jobs);

        // Calculate match scores (skip blacklist check in calculateMatchScore since we already filtered)
        $recommendations = $jobs->map(function($job) use ($profile) {
            $matchData = $this->calculateMatchScore($profile, $job, checkBlacklist: false);

            if ($matchData === null) {
                return null;
            }

            return [
                'job' => $job,
                'match_score' => $matchData['overall_score'],
                'match_level' => $matchData['match_level'],
                'why_recommended' => $this->generateRecommendationReason($matchData),
                'breakdown' => $matchData['breakdown'],
            ];
        })
        ->filter(fn($item) => $item !== null && $item['match_score'] >= 50)
        ->sortByDesc('match_score')
        ->take($limit)
        ->values()
        ->toArray();

        return $recommendations;
    }

    /**
     * Generate reason for recommendation
     */
    protected function generateRecommendationReason(array $matchData): string
    {
        $strengths = $matchData['breakdown']['strengths'] ?? [];
        if (empty($strengths)) {
            return "This job matches your profile.";
        }
        
        return "Recommended because: " . implode(', ', array_slice($strengths, 0, 2));
    }

    /**
     * Calculate years of experience from profile
     */
    protected function calculateYearsOfExperience(Profile $profile): float
    {
        if (empty($profile->experience)) {
            return 0;
        }
        
        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $start = \Carbon\Carbon::parse($exp['start_date'] ?? 'now');
            $end = isset($exp['end_date']) && $exp['end_date'] !== 'Present' 
                ? \Carbon\Carbon::parse($exp['end_date'])
                : \Carbon\Carbon::now();
            
            $totalMonths += $start->diffInMonths($end);
        }
        
        return round($totalMonths / 12, 1);
    }

    /**
     * Identify skill gaps for a target role
     */
    public function identifySkillGaps(Profile $profile, Job $job): array
    {
        $systemPrompt = "You are a career development advisor specializing in skill gap analysis.";

        $skillsJson = json_encode($profile->skills);
        $requirementsJson = json_encode($job->requirements);
        
        $prompt = <<<PROMPT
Analyze the skill gap between the candidate and the job requirements:

Candidate Skills: {$skillsJson}
Job Requirements: {$requirementsJson}

Return JSON:
{
  "critical_gaps": ["Skills that are must-haves and missing"],
  "nice_to_have_gaps": ["Optional skills that would strengthen application"],
  "learning_path": [
    {
      "skill": "Skill name",
      "priority": "high/medium/low",
      "estimated_time": "X weeks/months",
      "resources": ["Resource 1", "Resource 2"]
    }
  ],
  "transferable_skills": ["Candidate's skills that can transfer"],
  "timeline_to_ready": "Realistic timeline to become job-ready"
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Check if a job's company is blacklisted by the user.
     *
     * @param int $userId
     * @param Job $job
     * @return bool
     */
    public function isCompanyBlacklisted(int $userId, Job $job): bool
    {
        // Get company name from job or related company
        $companyName = $job->company_name ?? $job->company?->name ?? null;

        if (empty($companyName)) {
            return false;
        }

        return CompanyBlacklist::isBlacklisted($userId, $companyName);
    }

    /**
     * Filter out blacklisted companies from a collection of jobs.
     *
     * @param int $userId
     * @param \Illuminate\Support\Collection $jobs
     * @return \Illuminate\Support\Collection
     */
    public function filterBlacklistedJobs(int $userId, $jobs)
    {
        // Get all blacklisted company names for this user
        $blacklistedCompanies = CompanyBlacklist::where('user_id', $userId)
            ->pluck('company_name')
            ->map(fn($name) => strtolower($name))
            ->toArray();

        if (empty($blacklistedCompanies)) {
            return $jobs;
        }

        return $jobs->filter(function ($job) use ($blacklistedCompanies) {
            $companyName = strtolower($job->company_name ?? $job->company?->name ?? '');

            if (empty($companyName)) {
                return true;
            }

            // Check if any blacklisted company name is a partial match
            foreach ($blacklistedCompanies as $blacklisted) {
                if (str_contains($companyName, $blacklisted) || str_contains($blacklisted, $companyName)) {
                    return false;
                }
            }

            return true;
        });
    }
}
