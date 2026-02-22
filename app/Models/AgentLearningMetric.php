<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLearningMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'successful_job_patterns',
        'unsuccessful_job_patterns',
        'keyword_performance',
        'company_type_performance',
        'average_match_score_applied',
        'average_response_rate',
        'average_interview_rate',
        'best_application_times',
        'resume_optimization_effectiveness',
        'cover_letter_templates_performance',
        'total_applications',
        'total_responses',
        'total_interviews',
        'total_offers',
        'last_learning_cycle_at',
    ];

    protected $casts = [
        'successful_job_patterns' => 'array',
        'unsuccessful_job_patterns' => 'array',
        'keyword_performance' => 'array',
        'company_type_performance' => 'array',
        'average_match_score_applied' => 'float',
        'average_response_rate' => 'float',
        'average_interview_rate' => 'float',
        'best_application_times' => 'array',
        'resume_optimization_effectiveness' => 'array',
        'cover_letter_templates_performance' => 'array',
        'total_applications' => 'integer',
        'total_responses' => 'integer',
        'total_interviews' => 'integer',
        'total_offers' => 'integer',
        'last_learning_cycle_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getResponseRate(): float
    {
        if ($this->total_applications === 0) return 0;
        return ($this->total_responses / $this->total_applications) * 100;
    }

    public function getInterviewRate(): float
    {
        if ($this->total_applications === 0) return 0;
        return ($this->total_interviews / $this->total_applications) * 100;
    }

    public function getOfferRate(): float
    {
        if ($this->total_applications === 0) return 0;
        return ($this->total_offers / $this->total_applications) * 100;
    }

    public function runLearningCycle(): void
    {
        // Analyze recent applications to extract patterns
        $recentApplications = AutoApplication::where('user_id', $this->user_id)
            ->with('discoveredJob')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $this->analyzeSuccessPatterns($recentApplications);
        $this->analyzeKeywordPerformance($recentApplications);
        $this->analyzeBestTimings($recentApplications);
        
        $this->last_learning_cycle_at = now();
        $this->save();
    }

    protected function analyzeSuccessPatterns($applications): void
    {
        $successful = $applications->filter(fn($app) => $app->isSuccessful());
        $unsuccessful = $applications->filter(fn($app) => !$app->isSuccessful());

        // Extract common patterns from successful applications
        $successPatterns = [];
        foreach ($successful as $app) {
            // Analyze job characteristics
            $job = $app->discoveredJob;
            if ($job) {
                $successPatterns[] = [
                    'company_size' => $job->getCompanySize(),
                    'remote' => $job->is_remote,
                    'experience_level' => $job->experience_level,
                ];
            }
        }

        $this->successful_job_patterns = $successPatterns;
        $this->save();
    }

    protected function analyzeKeywordPerformance($applications): void
    {
        // Track which keywords led to success
        $keywordStats = [];
        
        foreach ($applications as $app) {
            foreach ($app->keywords_optimized ?? [] as $keyword) {
                if (!isset($keywordStats[$keyword])) {
                    $keywordStats[$keyword] = ['total' => 0, 'successful' => 0];
                }
                $keywordStats[$keyword]['total']++;
                if ($app->isSuccessful()) {
                    $keywordStats[$keyword]['successful']++;
                }
            }
        }

        $this->keyword_performance = $keywordStats;
        $this->save();
    }

    protected function analyzeBestTimings($applications): void
    {
        // Find best days/times for applications
        $timingStats = [];
        
        foreach ($applications->where('got_response', true) as $app) {
            $dayOfWeek = $app->submitted_at->dayOfWeek;
            $hour = $app->submitted_at->hour;
            
            $key = "{$dayOfWeek}_{$hour}";
            if (!isset($timingStats[$key])) {
                $timingStats[$key] = 0;
            }
            $timingStats[$key]++;
        }

        arsort($timingStats);
        $this->best_application_times = array_slice($timingStats, 0, 10, true);
        $this->save();
    }
}
