<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;

class ApplicationService
{
    protected $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Calculate match score between applicant and job
     */
    public function calculateMatch(Application $application)
    {
        $cacheKey = "match_score_{$application->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($application) {
            $user = $application->user;
            $job = $application->job;
            $profile = $user->profile;
            
            if (!$profile) {
                return [
                    'score' => 0,
                    'analysis' => 'Complete your profile for accurate matching',
                ];
            }
            
            $score = 0;
            $analysis = [];
            
            // Skills match (40 points)
            $skillsScore = $this->calculateSkillsMatch($profile, $job);
            $score += $skillsScore['score'];
            $analysis['skills'] = $skillsScore['analysis'];
            
            // Experience match (25 points)
            $experienceScore = $this->calculateExperienceMatch($profile, $job);
            $score += $experienceScore['score'];
            $analysis['experience'] = $experienceScore['analysis'];
            
            // Education match (15 points)
            $educationScore = $this->calculateEducationMatch($profile, $job);
            $score += $educationScore['score'];
            $analysis['education'] = $educationScore['analysis'];
            
            // Location match (10 points)
            $locationScore = $this->calculateLocationMatch($profile, $job);
            $score += $locationScore['score'];
            $analysis['location'] = $locationScore['analysis'];
            
            // Salary match (10 points)
            $salaryScore = $this->calculateSalaryMatch($profile, $job);
            $score += $salaryScore['score'];
            $analysis['salary'] = $salaryScore['analysis'];
            
            return [
                'score' => min(100, round($score)),
                'analysis' => $analysis,
            ];
        });
    }
    
    /**
     * Calculate skills match
     */
    protected function calculateSkillsMatch($profile, Job $job)
    {
        $profileSkills = collect($profile->skills ?? []);
        $requiredSkills = collect($job->extracted_skills ?? []);
        
        if ($requiredSkills->isEmpty()) {
            return [
                'score' => 20,
                'analysis' => 'No specific skills required',
            ];
        }
        
        $matchedSkills = [];
        $missingSkills = [];
        
        foreach ($requiredSkills as $required) {
            $found = false;
            foreach ($profileSkills as $profileSkill) {
                if (stripos($profileSkill, $required) !== false || 
                    stripos($required, $profileSkill) !== false) {
                    $matchedSkills[] = $required;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missingSkills[] = $required;
            }
        }
        
        $matchPercentage = $requiredSkills->count() > 0 
            ? (count($matchedSkills) / $requiredSkills->count()) * 100
            : 0;
        
        $score = ($matchPercentage / 100) * 40;
        
        return [
            'score' => $score,
            'analysis' => [
                'matched' => $matchedSkills,
                'missing' => $missingSkills,
                'percentage' => round($matchPercentage),
            ],
        ];
    }
    
    /**
     * Calculate experience match
     */
    protected function calculateExperienceMatch($profile, Job $job)
    {
        $experiences = $profile->experience ?? [];
        
        if (empty($experiences)) {
            return [
                'score' => 0,
                'analysis' => 'No experience data available',
            ];
        }
        
        // Calculate total years of experience
        $totalYears = 0;
        foreach ($experiences as $exp) {
            if (isset($exp['start_date'])) {
                $start = \Carbon\Carbon::parse($exp['start_date']);
                $end = isset($exp['end_date']) && $exp['end_date'] 
                    ? \Carbon\Carbon::parse($exp['end_date']) 
                    : now();
                $totalYears += $start->diffInYears($end);
            }
        }
        
        // Match against job experience level
        $requiredYears = match($job->experience_level) {
            'entry' => 0,
            'mid' => 3,
            'senior' => 7,
            'executive' => 12,
            default => 0,
        };
        
        $score = 0;
        $analysis = '';
        
        if ($totalYears >= $requiredYears) {
            $score = 25;
            $analysis = "{$totalYears} years matches {$job->experience_level} level requirement";
        } elseif ($totalYears >= ($requiredYears - 1)) {
            $score = 20;
            $analysis = "{$totalYears} years is close to {$job->experience_level} level requirement";
        } elseif ($totalYears >= ($requiredYears - 2)) {
            $score = 15;
            $analysis = "{$totalYears} years is below {$job->experience_level} level requirement";
        } else {
            $score = 5;
            $analysis = "{$totalYears} years is significantly below {$job->experience_level} level requirement";
        }
        
        return [
            'score' => $score,
            'analysis' => $analysis,
        ];
    }
    
    /**
     * Calculate education match
     */
    protected function calculateEducationMatch($profile, Job $job)
    {
        $education = $profile->education ?? [];
        
        if (empty($education)) {
            return [
                'score' => 5,
                'analysis' => 'No education data provided',
            ];
        }
        
        // Check for relevant degrees
        $hasRelevantDegree = false;
        $highestDegree = '';
        
        foreach ($education as $edu) {
            $degree = strtolower($edu['degree'] ?? '');
            
            if (stripos($degree, 'master') !== false || stripos($degree, 'phd') !== false) {
                $highestDegree = 'Advanced';
                $hasRelevantDegree = true;
            } elseif (stripos($degree, 'bachelor') !== false) {
                if ($highestDegree !== 'Advanced') {
                    $highestDegree = 'Bachelor';
                }
                $hasRelevantDegree = true;
            }
        }
        
        $score = $hasRelevantDegree ? 15 : 5;
        $analysis = $hasRelevantDegree 
            ? "{$highestDegree} degree found"
            : "Education background provided";
        
        return [
            'score' => $score,
            'analysis' => $analysis,
        ];
    }
    
    /**
     * Calculate location match
     */
    protected function calculateLocationMatch($profile, Job $job)
    {
        // Remote jobs always match
        if ($job->work_mode === 'remote') {
            return [
                'score' => 10,
                'analysis' => 'Remote position - location flexible',
            ];
        }
        
        $preferredLocations = $profile->preferred_locations ?? [];
        $jobLocation = $job->location;
        
        if (empty($preferredLocations)) {
            return [
                'score' => 5,
                'analysis' => 'No location preference specified',
            ];
        }
        
        foreach ($preferredLocations as $preferred) {
            if (stripos($jobLocation, $preferred) !== false || 
                stripos($preferred, $jobLocation) !== false) {
                return [
                    'score' => 10,
                    'analysis' => 'Location matches preference',
                ];
            }
        }
        
        return [
            'score' => 3,
            'analysis' => 'Location does not match preference',
        ];
    }
    
    /**
     * Calculate salary match
     */
    protected function calculateSalaryMatch($profile, Job $job)
    {
        $expectedMin = $profile->expected_salary_min;
        $jobMax = $job->salary_max;
        
        if (!$expectedMin || !$jobMax) {
            return [
                'score' => 5,
                'analysis' => 'Salary information not available',
            ];
        }
        
        if ($jobMax >= $expectedMin) {
            $diff = (($jobMax - $expectedMin) / $expectedMin) * 100;
            if ($diff >= 20) {
                return [
                    'score' => 10,
                    'analysis' => 'Salary exceeds expectations',
                ];
            } else {
                return [
                    'score' => 8,
                    'analysis' => 'Salary meets expectations',
                ];
            }
        } else {
            $diff = (($expectedMin - $jobMax) / $expectedMin) * 100;
            if ($diff <= 10) {
                return [
                    'score' => 5,
                    'analysis' => 'Salary slightly below expectations',
                ];
            } else {
                return [
                    'score' => 2,
                    'analysis' => 'Salary below expectations',
                ];
            }
        }
    }
    
    /**
     * Generate application number
     */
    public function generateApplicationNumber()
    {
        $prefix = 'APP';
        $timestamp = now()->format('ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$timestamp}-{$random}";
    }
    
    /**
     * Check for duplicate application
     */
    public function hasPreviousApplication(User $user, Job $job)
    {
        return Application::where('user_id', $user->id)
            ->where('job_id', $job->id)
            ->exists();
    }
    
    /**
     * Quick apply with profile data
     */
    public function quickApply(User $user, Job $job, $additionalData = [])
    {
        // Check for duplicate
        if ($this->hasPreviousApplication($user, $job)) {
            throw new \Exception('You have already applied to this job');
        }
        
        // Check subscription limits
        if (!$user->hasFeature('unlimited_applications')) {
            $remaining = $user->getRemainingApplications();
            if ($remaining <= 0) {
                throw new \Exception('Application limit reached. Please upgrade your plan.');
            }
        }
        
        $profile = $user->profile;
        
        if (!$profile) {
            throw new \Exception('Please complete your profile before applying');
        }
        
        // Create application with profile data
        $application = Application::create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'application_number' => $this->generateApplicationNumber(),
            'resume_file' => $profile->resume_file ?? null,
            'cover_letter' => $additionalData['cover_letter'] ?? null,
            'answers' => $additionalData['answers'] ?? null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        
        // Calculate match score
        $matchScore = $this->calculateMatch($application);
        
        $application->update([
            'match_score' => $matchScore['score'],
            'match_analysis' => $matchScore['analysis'],
        ]);
        
        // Update user's application count
        if ($user->subscription) {
            $user->subscription->increment('applications_used_this_month');
        }
        
        return $application;
    }
    
    /**
     * Withdraw application
     */
    public function withdraw(Application $application, $reason = null)
    {
        if (!in_array($application->status, ['submitted', 'viewed', 'shortlisted'])) {
            throw new \Exception('Cannot withdraw application in current status');
        }
        
        $application->update([
            'status' => 'withdrawn',
            'notes' => $reason,
        ]);
        
        // Update timeline
        $timeline = $application->timeline ?? [];
        $timeline[] = [
            'status' => 'withdrawn',
            'timestamp' => now()->toISOString(),
            'notes' => $reason,
        ];
        
        $application->update(['timeline' => $timeline]);
        
        return $application;
    }
    
    /**
     * Save application as draft
     */
    public function saveDraft(User $user, Job $job, $data)
    {
        return Application::updateOrCreate(
            [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'status' => 'draft',
            ],
            [
                'application_number' => $this->generateApplicationNumber(),
                'resume_file' => $data['resume_file'] ?? null,
                'cover_letter' => $data['cover_letter'] ?? null,
                'answers' => $data['answers'] ?? null,
            ]
        );
    }
    
    /**
     * Submit draft application
     */
    public function submitDraft(Application $application)
    {
        if ($application->status !== 'draft') {
            throw new \Exception('Application is not a draft');
        }
        
        $application->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        
        // Calculate match score
        $matchScore = $this->calculateMatch($application);
        
        $application->update([
            'match_score' => $matchScore['score'],
            'match_analysis' => $matchScore['analysis'],
        ]);
        
        return $application;
    }
}
