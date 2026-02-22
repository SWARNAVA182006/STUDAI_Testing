<?php

namespace App\Jobs;

use App\Models\JobAlert;
use App\Models\Job;
use App\Mail\JobAlertMail;
use App\Services\Search\JobSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessJobAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $frequency;
    
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;
    
    /**
     * The number of seconds to wait before retrying.
     */
    public $backoff = 60;
    
    /**
     * Create a new job instance.
     */
    public function __construct($frequency = 'daily')
    {
        $this->frequency = $frequency;
    }

    /**
     * Execute the job.
     */
    public function handle(JobSearchService $searchService): void
    {
        Log::info("Processing {$this->frequency} job alerts");
        
        // Get alerts due for processing based on frequency
        $alerts = JobAlert::dueForProcessing($this->frequency)->get();
        
        Log::info("Found {$alerts->count()} alerts to process");
        
        foreach ($alerts as $alert) {
            try {
                $this->processAlert($alert, $searchService);
            } catch (\Exception $e) {
                Log::error("Failed to process alert {$alert->id}: " . $e->getMessage());
                // Continue processing other alerts even if one fails
            }
        }
        
        Log::info("Completed processing {$this->frequency} job alerts");
    }
    
    /**
     * Process a single job alert
     */
    protected function processAlert(JobAlert $alert, JobSearchService $searchService)
    {
        // Find matching jobs
        $matches = $this->findMatchingJobs($alert, $searchService);
        
        if ($matches->isEmpty()) {
            Log::info("No new matches for alert {$alert->id}");
            return;
        }
        
        Log::info("Found {$matches->count()} matches for alert {$alert->id}");
        
        // Rank matches by relevance
        $rankedMatches = $this->rankMatches($matches, $alert, $searchService);
        
        // Take top 10 for email
        $topMatches = $rankedMatches->take(10);
        
        // Send alert email
        try {
            Mail::to($alert->user)->send(
                new JobAlertMail($topMatches, $alert, $matches->count())
            );
            
            Log::info("Sent alert email to {$alert->user->email} for alert {$alert->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send email for alert {$alert->id}: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
        
        // Mark jobs as sent
        $alert->markJobsAsSent($matches->pluck('id')->toArray());
        
        // Update alert statistics
        $alert->update([
            'last_sent_at' => now(),
            'matches_count' => $alert->matches_count + $matches->count(),
            'new_matches_count' => $matches->count(),
        ]);
    }
    
    /**
     * Find matching jobs for an alert
     */
    protected function findMatchingJobs(JobAlert $alert, JobSearchService $searchService)
    {
        // Get jobs posted since last alert
        $sincDate = $alert->last_sent_at ?? now()->subMonth();
        
        $query = Job::query()
            ->where('status', 'active')
            ->where('created_at', '>', $sincDate);
        
        // Exclude jobs already sent
        $sentJobIds = $alert->sentJobs()->pluck('job_id')->toArray();
        if (!empty($sentJobIds)) {
            $query->whereNotIn('id', $sentJobIds);
        }
        
        // Apply alert criteria using search service for consistency
        $criteria = $alert->criteria ?? [];
        
        // Keywords search
        if (!empty($criteria['keywords'])) {
            $keywords = $criteria['keywords'];
            $query->where(function($q) use ($keywords) {
                $q->where('title', 'like', "%{$keywords}%")
                  ->orWhere('description', 'like', "%{$keywords}%")
                  ->orWhereRaw("JSON_SEARCH(extracted_skills, 'one', ?) IS NOT NULL", ["%{$keywords}%"]);
            });
        }
        
        // Location filter
        if (!empty($criteria['location'])) {
            $query->where('location', 'like', "%{$criteria['location']}%");
        }
        
        // Employment type filter
        if (!empty($criteria['employment_type'])) {
            $types = is_array($criteria['employment_type']) 
                ? $criteria['employment_type'] 
                : [$criteria['employment_type']];
            $query->whereIn('employment_type', $types);
        }
        
        // Experience level filter
        if (!empty($criteria['experience_level'])) {
            $levels = is_array($criteria['experience_level'])
                ? $criteria['experience_level']
                : [$criteria['experience_level']];
            $query->whereIn('experience_level', $levels);
        }
        
        // Work mode filter
        if (!empty($criteria['work_mode'])) {
            $modes = is_array($criteria['work_mode'])
                ? $criteria['work_mode']
                : [$criteria['work_mode']];
            $query->whereIn('work_mode', $modes);
        }
        
        // Salary range filter
        if (!empty($criteria['salary_min'])) {
            $query->where('salary_max', '>=', $criteria['salary_min']);
        }
        
        if (!empty($criteria['salary_max'])) {
            $query->where('salary_min', '<=', $criteria['salary_max']);
        }
        
        // Company filter
        if (!empty($criteria['company_id'])) {
            $companyIds = is_array($criteria['company_id'])
                ? $criteria['company_id']
                : [$criteria['company_id']];
            $query->whereIn('company_id', $companyIds);
        }
        
        // Skills filter
        if (!empty($criteria['required_skills'])) {
            $skills = is_array($criteria['required_skills'])
                ? $criteria['required_skills']
                : [$criteria['required_skills']];
            
            foreach ($skills as $skill) {
                $query->whereRaw("JSON_SEARCH(extracted_skills, 'one', ?) IS NOT NULL", ["%{$skill}%"]);
            }
        }
        
        return $query->with(['company:id,name,logo,is_verified'])
            ->latest()
            ->get();
    }
    
    /**
     * Rank matches by relevance to alert criteria
     */
    protected function rankMatches($matches, JobAlert $alert, JobSearchService $searchService)
    {
        $criteria = $alert->criteria ?? [];
        $keywords = $criteria['keywords'] ?? '';
        
        return $matches->map(function($job) use ($keywords) {
            // Calculate simple relevance score
            $score = 0;
            
            // Title match (30 points)
            if (stripos($job->title, $keywords) !== false) {
                $score += 30;
            }
            
            // Skills match (25 points)
            $jobSkills = $job->extracted_skills ?? [];
            foreach ($jobSkills as $skill) {
                if (stripos($skill, $keywords) !== false) {
                    $score += 5;
                    if ($score >= 55) break; // Max 25 points from skills
                }
            }
            
            // Description match (20 points)
            if (stripos($job->description, $keywords) !== false) {
                $score += 20;
            }
            
            // Quality score (15 points max)
            if ($job->quality_score) {
                $score += ($job->quality_score / 100) * 15;
            }
            
            // Recency (10 points max, decreases with age)
            $daysOld = now()->diffInDays($job->created_at);
            $recencyScore = max(0, 10 - ($daysOld * 0.5));
            $score += $recencyScore;
            
            $job->relevance_score = min(100, round($score));
            return $job;
        })->sortByDesc('relevance_score');
    }
    
    /**
     * Get recommended alerts for a user based on their profile
     */
    public static function generateRecommendedAlerts($user)
    {
        $profile = $user->profile;
        
        if (!$profile) {
            return [];
        }
        
        $recommendations = [];
        
        // Alert based on profile skills
        if (!empty($profile->skills)) {
            $topSkills = array_slice($profile->skills, 0, 3);
            $recommendations[] = [
                'name' => 'Jobs matching your top skills',
                'criteria' => [
                    'keywords' => implode(' ', $topSkills),
                    'experience_level' => static::estimateExperienceLevel($profile),
                ],
            ];
        }
        
        // Alert based on preferred location
        if (!empty($profile->preferred_locations)) {
            $recommendations[] = [
                'name' => 'Jobs in your preferred locations',
                'criteria' => [
                    'location' => $profile->preferred_locations[0],
                    'work_mode' => $profile->work_preference ?? 'remote',
                ],
            ];
        }
        
        // Alert based on salary expectations
        if ($profile->expected_salary_min) {
            $recommendations[] = [
                'name' => 'High-paying opportunities',
                'criteria' => [
                    'salary_min' => $profile->expected_salary_min,
                ],
            ];
        }
        
        // Remote jobs alert if user prefers remote
        if ($profile->work_preference === 'remote') {
            $recommendations[] = [
                'name' => 'Remote opportunities',
                'criteria' => [
                    'work_mode' => ['remote'],
                ],
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Estimate experience level from profile
     */
    protected static function estimateExperienceLevel($profile)
    {
        $experiences = $profile->experience ?? [];
        
        if (empty($experiences)) {
            return 'entry';
        }
        
        // Calculate total years of experience
        $totalYears = 0;
        foreach ($experiences as $exp) {
            if (isset($exp['start_date'])) {
                $start = \Carbon\Carbon::parse($exp['start_date']);
                $end = isset($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date']) : now();
                $totalYears += $start->diffInYears($end);
            }
        }
        
        if ($totalYears < 2) return 'entry';
        if ($totalYears < 5) return 'mid';
        if ($totalYears < 10) return 'senior';
        return 'executive';
    }
}
