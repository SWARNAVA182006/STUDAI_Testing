<?php

namespace App\Services\AI\Scout;

use App\Models\Application;
use App\Models\CandidateFeedback;
use App\Models\CandidateInteraction;
use App\Models\Company;
use App\Models\EmployerBrandScore;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenAI\Laravel\Facades\OpenAI;

class CandidateExperienceService
{
    /**
     * OpenAI model for feedback generation
     */
    protected string $model = 'gpt-5-mini';

    /**
     * Record candidate interaction
     *
     * @param array $data
     * @return CandidateInteraction
     */
    public function recordInteraction(array $data): CandidateInteraction
    {
        $interaction = CandidateInteraction::recordInteraction([
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'application_id' => $data['application_id'] ?? null,
            'job_id' => $data['job_id'] ?? null,
            'interaction_type' => $data['interaction_type'],
            'interaction_summary' => $data['interaction_summary'],
            'interaction_details' => $data['interaction_details'] ?? null,
            'interaction_metadata' => $data['interaction_metadata'] ?? [],
            'conducted_by_user_id' => $data['conducted_by_user_id'] ?? null,
            'automated' => $data['automated'] ?? false,
            'candidate_sentiment' => $data['candidate_sentiment'] ?? 'neutral',
            'response_time_hours' => $data['response_time_hours'] ?? null,
        ]);

        Log::info('Candidate interaction recorded', [
            'interaction_id' => $interaction->id,
            'user_id' => $data['user_id'],
            'type' => $data['interaction_type'],
        ]);

        return $interaction;
    }

    /**
     * Send automated status update to candidate
     *
     * @param Application $application
     * @param string $newStatus
     * @param array $options
     * @return void
     */
    public function sendStatusUpdate(Application $application, string $newStatus, array $options = []): void
    {
        $user = $application->user;
        $job = $application->job;
        $company = $job->company;

        // Generate personalized message
        $message = $this->generateStatusUpdateMessage($application, $newStatus, $options);

        // Record interaction
        $this->recordInteraction([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'application_id' => $application->id,
            'job_id' => $job->id,
            'interaction_type' => 'status_update_sent',
            'interaction_summary' => "Application status updated to: {$newStatus}",
            'interaction_details' => $message,
            'interaction_metadata' => [
                'previous_status' => $application->status,
                'new_status' => $newStatus,
                'update_reason' => $options['reason'] ?? null,
            ],
            'automated' => true,
            'candidate_sentiment' => $this->inferSentimentFromStatus($newStatus),
        ]);

        // Send email notification (would integrate with mail system)
        // Mail::to($user->email)->send(new StatusUpdateMail($application, $message));

        Log::info('Status update sent to candidate', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'status' => $newStatus,
        ]);
    }

    /**
     * Generate personalized status update message
     *
     * @param Application $application
     * @param string $newStatus
     * @param array $options
     * @return string
     */
    protected function generateStatusUpdateMessage(
        Application $application,
        string $newStatus,
        array $options = []
    ): string {
        $candidateName = $application->user->name;
        $jobTitle = $application->job->title;
        $companyName = $application->job->company->name;

        return match($newStatus) {
            'under_review' => "Hi {$candidateName},\n\nThank you for your interest in the {$jobTitle} position at {$companyName}. We wanted to let you know that your application is currently under review by our hiring team.\n\nWe appreciate your patience and will update you on the next steps soon.\n\nBest regards,\n{$companyName} Talent Team",
            
            'shortlisted' => "Hi {$candidateName},\n\nGreat news! Your application for the {$jobTitle} position at {$companyName} has been shortlisted. We're impressed with your background and would like to move forward with the next steps in our hiring process.\n\nSomeone from our team will be in touch within the next 2-3 business days to schedule an interview.\n\nBest regards,\n{$companyName} Talent Team",
            
            'interview_scheduled' => "Hi {$candidateName},\n\nWe're excited to schedule an interview with you for the {$jobTitle} position at {$companyName}. You should receive a calendar invitation shortly with the interview details.\n\nPlease let us know if you have any questions or need to reschedule.\n\nLooking forward to speaking with you!\n\nBest regards,\n{$companyName} Talent Team",
            
            'rejected' => $this->generateRejectionMessage($application, $options),
            
            'offer_extended' => "Hi {$candidateName},\n\nCongratulations! We're thrilled to extend an offer for the {$jobTitle} position at {$companyName}. We believe you'll be a great addition to our team.\n\nYou'll receive a formal offer letter shortly with all the details. Please don't hesitate to reach out if you have any questions.\n\nWe're excited about the possibility of working together!\n\nBest regards,\n{$companyName} Talent Team",
            
            default => "Hi {$candidateName},\n\nWe wanted to update you on the status of your application for the {$jobTitle} position at {$companyName}.\n\nYour application is currently: {$newStatus}\n\nWe'll keep you informed as things progress. Thank you for your continued interest.\n\nBest regards,\n{$companyName} Talent Team",
        };
    }

    /**
     * Generate rejection message with constructive feedback
     *
     * @param Application $application
     * @param array $options
     * @return string
     */
    protected function generateRejectionMessage(Application $application, array $options = []): string
    {
        $candidateName = $application->user->name;
        $jobTitle = $application->job->title;
        $companyName = $application->job->company->name;

        $baseMessage = "Hi {$candidateName},\n\nThank you for taking the time to apply for the {$jobTitle} position at {$companyName} and for your interest in our company.\n\n";

        // Add constructive feedback if provided
        if ($options['include_feedback'] ?? false) {
            $feedback = $this->generateConstructiveFeedback($application, $options);
            $baseMessage .= $feedback . "\n\n";
        }

        $baseMessage .= "While we've decided to move forward with other candidates for this particular role, we were impressed with your profile. We encourage you to apply for future opportunities that match your skills and experience.\n\n";
        
        // Offer silver medalist status if eligible
        if ($options['silver_medalist'] ?? false) {
            $baseMessage .= "We'd like to keep your information on file for future opportunities that may be a better fit. We'll reach out if a relevant position opens up.\n\n";
        }

        $baseMessage .= "We wish you the best in your job search and future career.\n\nBest regards,\n{$companyName} Talent Team";

        return $baseMessage;
    }

    /**
     * Generate constructive feedback for candidate
     *
     * @param Application $application
     * @param array $context
     * @return string
     */
    public function generateConstructiveFeedback(Application $application, array $context = []): string
    {
        $cacheKey = "constructive_feedback_{$application->id}";
        
        return Cache::remember($cacheKey, 3600, function() use ($application, $context) {
            try {
                $prompt = $this->buildFeedbackPrompt($application, $context);
                
                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an empathetic HR professional providing constructive, encouraging feedback to job candidates. Focus on growth opportunities and positive reinforcement while being honest about areas for development. Keep feedback professional, specific, and actionable.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 300,
                ]);

                return $response->choices[0]->message->content;

            } catch (\Exception $e) {
                Log::error('Failed to generate constructive feedback', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->getFallbackFeedback($context);
            }
        });
    }

    /**
     * Build prompt for feedback generation
     *
     * @param Application $application
     * @param array $context
     * @return string
     */
    protected function buildFeedbackPrompt(Application $application, array $context): string
    {
        $rejectionReason = $context['rejection_reason'] ?? 'Not specified';
        $strengthsNoted = $context['strengths'] ?? [];
        $developmentAreas = $context['development_areas'] ?? [];

        $strengthsText = count($strengthsNoted) > 0 
            ? implode(', ', $strengthsNoted) 
            : 'General qualifications';
            
        $developmentText = count($developmentAreas) > 0 
            ? implode(', ', $developmentAreas) 
            : 'Experience level';

        return <<<PROMPT
Generate constructive, encouraging feedback for a candidate who was not selected for a position.

JOB: {$application->job->title}
REASON FOR NON-SELECTION: {$rejectionReason}
CANDIDATE STRENGTHS: {$strengthsText}
DEVELOPMENT AREAS: {$developmentAreas}

Provide feedback that:
1. Acknowledges their strengths
2. Explains areas for development in a positive way
3. Offers specific, actionable advice
4. Encourages future applications
5. Is empathetic and professional

Keep it brief (2-3 sentences) and constructive.
PROMPT;
    }

    /**
     * Get fallback feedback when AI fails
     *
     * @param array $context
     * @return string
     */
    protected function getFallbackFeedback(array $context): string
    {
        return "We appreciated learning about your background and experience. While we moved forward with candidates whose experience more closely matched our immediate needs, we were impressed with your qualifications. We encourage you to continue building your skills and applying for positions that align with your career goals.";
    }

    /**
     * Infer sentiment from application status
     *
     * @param string $status
     * @return string
     */
    protected function inferSentimentFromStatus(string $status): string
    {
        return match($status) {
            'shortlisted', 'interview_scheduled', 'offer_extended', 'hired' => 'positive',
            'rejected', 'withdrawn' => 'negative',
            default => 'neutral',
        };
    }

    /**
     * Request feedback from candidate
     *
     * @param Application $application
     * @return CandidateFeedback
     */
    public function requestFeedback(Application $application): CandidateFeedback
    {
        $feedback = CandidateFeedback::create([
            'company_id' => $application->job->company_id,
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'job_id' => $application->job_id,
            'feedback_stage' => $this->determineFeedbackStage($application),
            'feedback_requested_at' => now(),
        ]);

        // Record interaction
        $this->recordInteraction([
            'company_id' => $application->job->company_id,
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'job_id' => $application->job_id,
            'interaction_type' => 'feedback_provided',
            'interaction_summary' => 'Feedback request sent to candidate',
            'automated' => true,
            'candidate_sentiment' => 'neutral',
        ]);

        // Send feedback request email
        // Mail::to($application->user->email)->send(new FeedbackRequestMail($feedback));

        Log::info('Feedback requested from candidate', [
            'feedback_id' => $feedback->id,
            'application_id' => $application->id,
        ]);

        return $feedback;
    }

    /**
     * Determine feedback stage based on application progress
     *
     * @param Application $application
     * @return string
     */
    protected function determineFeedbackStage(Application $application): string
    {
        return match($application->status) {
            'rejected' => 'post_rejection',
            'withdrawn' => 'post_withdrawal',
            'hired' => 'post_hire',
            'offer_extended' => 'post_offer',
            'interview_scheduled', 'interviewing' => 'post_interview',
            default => 'application_process',
        };
    }

    /**
     * Submit candidate feedback
     *
     * @param CandidateFeedback $feedback
     * @param array $data
     * @return CandidateFeedback
     */
    public function submitFeedback(CandidateFeedback $feedback, array $data): CandidateFeedback
    {
        $feedback->submit([
            'communication_rating' => $data['communication_rating'] ?? null,
            'process_clarity_rating' => $data['process_clarity_rating'] ?? null,
            'interview_experience_rating' => $data['interview_experience_rating'] ?? null,
            'overall_experience_rating' => $data['overall_experience_rating'] ?? null,
            'likelihood_to_recommend' => $data['likelihood_to_recommend'] ?? null,
            'positive_aspects' => $data['positive_aspects'] ?? null,
            'improvement_suggestions' => $data['improvement_suggestions'] ?? null,
            'would_recommend' => $data['would_recommend'] ?? null,
        ]);

        // Record interaction
        $this->recordInteraction([
            'company_id' => $feedback->company_id,
            'user_id' => $feedback->user_id,
            'application_id' => $feedback->application_id,
            'job_id' => $feedback->job_id,
            'interaction_type' => 'feedback_provided',
            'interaction_summary' => 'Candidate provided feedback',
            'interaction_metadata' => [
                'overall_rating' => $data['overall_experience_rating'] ?? null,
                'nps_score' => $data['likelihood_to_recommend'] ?? null,
            ],
            'automated' => false,
            'candidate_sentiment' => $feedback->sentiment,
        ]);

        Log::info('Candidate feedback submitted', [
            'feedback_id' => $feedback->id,
            'nps_segment' => $feedback->nps_segment,
        ]);

        return $feedback;
    }

    /**
     * Calculate employer brand score
     *
     * @param Company $company
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return EmployerBrandScore
     */
    public function calculateEmployerBrandScore(
        Company $company,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): EmployerBrandScore {
        $startDate = $startDate ?? now()->subMonths(3);
        $endDate = $endDate ?? now();

        // Get all feedback in period
        $feedbacks = CandidateFeedback::where('company_id', $company->id)
            ->submitted()
            ->whereBetween('feedback_submitted_at', [$startDate, $endDate])
            ->get();

        if ($feedbacks->isEmpty()) {
            Log::warning('No feedback available for employer brand calculation', [
                'company_id' => $company->id,
            ]);
            
            return $this->createDefaultBrandScore($company, $startDate, $endDate);
        }

        // Calculate component scores
        $applicationExperience = $this->calculateAverageRating($feedbacks, 'process_clarity_rating');
        $communication = $this->calculateAverageRating($feedbacks, 'communication_rating');
        $interviewExperience = $this->calculateAverageRating($feedbacks, 'interview_experience_rating');
        $overallExperience = $this->calculateAverageRating($feedbacks, 'overall_experience_rating');

        // Feedback quality (based on response rate and completeness)
        $feedbackQuality = $this->calculateFeedbackQuality($company, $startDate, $endDate);

        // Transparency & respect (based on interaction analysis)
        $transparencyScore = $this->calculateTransparencyScore($company, $startDate, $endDate);
        $respectScore = $this->calculateRespectScore($company, $startDate, $endDate);

        // Calculate NPS
        $npsScore = CandidateFeedback::calculateNPS($company->id, $startDate, $endDate);

        // Get sentiment distribution
        $totalInteractions = CandidateInteraction::where('company_id', $company->id)
            ->whereBetween('interacted_at', [$startDate, $endDate])
            ->count();
            
        $positiveInteractions = CandidateInteraction::where('company_id', $company->id)
            ->whereBetween('interacted_at', [$startDate, $endDate])
            ->where('candidate_sentiment', 'positive')
            ->count();
            
        $negativeInteractions = CandidateInteraction::where('company_id', $company->id)
            ->whereBetween('interacted_at', [$startDate, $endDate])
            ->where('candidate_sentiment', 'negative')
            ->count();

        // Analyze themes
        $themes = $this->analyzeThemes($feedbacks);

        // Create brand score record
        $brandScore = EmployerBrandScore::create([
            'company_id' => $company->id,
            'measurement_period_start' => $startDate,
            'measurement_period_end' => $endDate,
            'application_experience_score' => $applicationExperience,
            'communication_score' => $communication,
            'interview_experience_score' => $interviewExperience,
            'feedback_quality_score' => $feedbackQuality,
            'transparency_score' => $transparencyScore,
            'respect_score' => $respectScore,
            'nps_score' => $npsScore,
            'positive_sentiment_count' => $positiveInteractions,
            'negative_sentiment_count' => $negativeInteractions,
            'total_interactions' => $totalInteractions,
            'feedback_response_count' => $feedbacks->count(),
            'key_themes' => $themes,
        ]);

        // Calculate overall score
        $brandScore->calculateOverallScore();

        // Determine trend
        $brandScore->determineTrend();

        Log::info('Employer brand score calculated', [
            'company_id' => $company->id,
            'overall_score' => $brandScore->overall_brand_score,
            'trend' => $brandScore->trend,
        ]);

        return $brandScore;
    }

    /**
     * Calculate average rating for a field
     *
     * @param Collection $feedbacks
     * @param string $field
     * @return float
     */
    protected function calculateAverageRating(Collection $feedbacks, string $field): float
    {
        $ratings = $feedbacks->pluck($field)->filter()->values();
        
        if ($ratings->isEmpty()) return 0;
        
        // Convert to 100-point scale (assuming 1-5 star ratings)
        return ($ratings->avg() / 5) * 100;
    }

    /**
     * Calculate feedback quality score
     *
     * @param Company $company
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateFeedbackQuality(Company $company, Carbon $startDate, Carbon $endDate): float
    {
        $requested = CandidateFeedback::where('company_id', $company->id)
            ->whereBetween('feedback_requested_at', [$startDate, $endDate])
            ->count();

        $submitted = CandidateFeedback::where('company_id', $company->id)
            ->submitted()
            ->whereBetween('feedback_submitted_at', [$startDate, $endDate])
            ->count();

        if ($requested === 0) return 50; // Default if no feedback requested

        $responseRate = ($submitted / $requested) * 100;
        
        // Good response rate = good feedback quality indicator
        return min(100, $responseRate);
    }

    /**
     * Calculate transparency score
     *
     * @param Company $company
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateTransparencyScore(Company $company, Carbon $startDate, Carbon $endDate): float
    {
        // Based on status update frequency and timeliness
        $statusUpdates = CandidateInteraction::where('company_id', $company->id)
            ->where('interaction_type', 'status_update_sent')
            ->whereBetween('interacted_at', [$startDate, $endDate])
            ->count();

        $totalApplications = Application::whereHas('job', function($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalApplications === 0) return 70; // Default

        $updateRate = ($statusUpdates / $totalApplications) * 100;
        
        // More updates = more transparent
        return min(100, $updateRate);
    }

    /**
     * Calculate respect score
     *
     * @param Company $company
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateRespectScore(Company $company, Carbon $startDate, Carbon $endDate): float
    {
        // Based on response times and personalized interactions
        $interactions = CandidateInteraction::where('company_id', $company->id)
            ->whereBetween('interacted_at', [$startDate, $endDate])
            ->whereNotNull('response_time_hours')
            ->get();

        if ($interactions->isEmpty()) return 70; // Default

        $avgResponseTime = $interactions->avg('response_time_hours');
        
        // Faster response = more respectful (inversely proportional)
        // 24 hours or less = 100, 168 hours (1 week) = 50
        $score = max(50, 100 - (($avgResponseTime / 24) * 10));
        
        return min(100, $score);
    }

    /**
     * Analyze feedback themes
     *
     * @param Collection $feedbacks
     * @return array
     */
    protected function analyzeThemes(Collection $feedbacks): array
    {
        $themes = [
            'positive' => [],
            'negative' => [],
        ];

        // Collect positive aspects
        $positiveAspects = $feedbacks->pluck('positive_aspects')->filter()->flatten()->toArray();
        $themes['positive'] = $this->extractCommonThemes($positiveAspects);

        // Collect improvement suggestions
        $improvements = $feedbacks->pluck('improvement_suggestions')->filter()->flatten()->toArray();
        $themes['negative'] = $this->extractCommonThemes($improvements);

        return $themes;
    }

    /**
     * Extract common themes from text
     *
     * @param array $texts
     * @return array
     */
    protected function extractCommonThemes(array $texts): array
    {
        // Simple keyword extraction (could be enhanced with NLP)
        $keywords = [
            'communication' => ['communication', 'updates', 'contact', 'response'],
            'process' => ['process', 'timeline', 'stages', 'steps'],
            'interview' => ['interview', 'questions', 'interviewer'],
            'feedback' => ['feedback', 'clarity', 'transparent'],
            'respect' => ['respect', 'professional', 'courteous'],
        ];

        $themes = [];
        $textsLower = array_map('strtolower', $texts);
        $allText = implode(' ', $textsLower);

        foreach ($keywords as $theme => $words) {
            foreach ($words as $word) {
                if (stripos($allText, $word) !== false) {
                    $themes[] = $theme;
                    break;
                }
            }
        }

        return array_unique($themes);
    }

    /**
     * Create default brand score when no data available
     *
     * @param Company $company
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return EmployerBrandScore
     */
    protected function createDefaultBrandScore(Company $company, Carbon $startDate, Carbon $endDate): EmployerBrandScore
    {
        return EmployerBrandScore::create([
            'company_id' => $company->id,
            'measurement_period_start' => $startDate,
            'measurement_period_end' => $endDate,
            'application_experience_score' => 70,
            'communication_score' => 70,
            'interview_experience_score' => 70,
            'feedback_quality_score' => 70,
            'transparency_score' => 70,
            'respect_score' => 70,
            'overall_brand_score' => 70,
            'nps_score' => 0,
            'positive_sentiment_count' => 0,
            'negative_sentiment_count' => 0,
            'total_interactions' => 0,
            'feedback_response_count' => 0,
        ]);
    }

    /**
     * Get candidate journey timeline
     *
     * @param User $user
     * @param Company $company
     * @return array
     */
    public function getCandidateJourney(User $user, Company $company): array
    {
        $interactions = CandidateInteraction::where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->orderBy('interacted_at')
            ->get();

        return [
            'total_interactions' => $interactions->count(),
            'timeline' => $interactions->map(fn($i) => [
                'date' => $i->interacted_at->format('Y-m-d H:i:s'),
                'type' => $i->interaction_type,
                'summary' => $i->interaction_summary,
                'sentiment' => $i->candidate_sentiment,
                'automated' => $i->automated,
            ])->toArray(),
            'sentiment_breakdown' => [
                'positive' => $interactions->where('candidate_sentiment', 'positive')->count(),
                'neutral' => $interactions->where('candidate_sentiment', 'neutral')->count(),
                'negative' => $interactions->where('candidate_sentiment', 'negative')->count(),
            ],
            'response_time_avg' => $interactions->whereNotNull('response_time_hours')->avg('response_time_hours'),
        ];
    }

    /**
     * Identify brand risks
     *
     * @param Company $company
     * @return array
     */
    public function identifyBrandRisks(Company $company): array
    {
        $risks = [];

        // Get latest brand score
        $latestScore = EmployerBrandScore::where('company_id', $company->id)
            ->latest('measurement_period_end')
            ->first();

        if (!$latestScore) {
            return ['No brand score data available'];
        }

        // Check for declining trend
        if ($latestScore->trend === 'declining') {
            $risks[] = "Employer brand score is declining (currently {$latestScore->overall_brand_score})";
        }

        // Check for low component scores
        if ($latestScore->communication_score < 60) {
            $risks[] = 'Communication score is critically low - candidates may feel ignored';
        }

        if ($latestScore->transparency_score < 60) {
            $risks[] = 'Transparency score is low - increase frequency of status updates';
        }

        if ($latestScore->respect_score < 60) {
            $risks[] = 'Respect score is low - improve response times and personalization';
        }

        // Check NPS
        if ($latestScore->nps_score < 0) {
            $risks[] = 'Negative NPS - more detractors than promoters';
        }

        // Check sentiment ratio
        if ($latestScore->negative_sentiment_rate > 30) {
            $risks[] = "High negative sentiment ({$latestScore->negative_sentiment_rate}%) - review candidate interactions";
        }

        // Check feedback themes
        if (in_array('communication', $latestScore->key_themes['negative'] ?? [])) {
            $risks[] = 'Communication is a recurring negative theme in feedback';
        }

        return $risks;
    }

    /**
     * Get candidate experience metrics
     *
     * @param Company $company
     * @return array
     */
    public function getExperienceMetrics(Company $company): array
    {
        $latestScore = EmployerBrandScore::where('company_id', $company->id)
            ->latest('measurement_period_end')
            ->first();

        $feedbackCount = CandidateFeedback::where('company_id', $company->id)
            ->submitted()
            ->count();

        $npsScore = $latestScore->nps_score ?? 0;

        return [
            'overall_brand_score' => $latestScore->overall_brand_score ?? 70,
            'brand_health_status' => $latestScore->brand_health_status ?? 'fair',
            'trend' => $latestScore->trend ?? 'stable',
            'nps_score' => $npsScore,
            'total_feedback_submissions' => $feedbackCount,
            'component_scores' => [
                'application_experience' => $latestScore->application_experience_score ?? 70,
                'communication' => $latestScore->communication_score ?? 70,
                'interview_experience' => $latestScore->interview_experience_score ?? 70,
                'feedback_quality' => $latestScore->feedback_quality_score ?? 70,
                'transparency' => $latestScore->transparency_score ?? 70,
                'respect' => $latestScore->respect_score ?? 70,
            ],
            'sentiment_distribution' => [
                'positive_rate' => $latestScore->positive_sentiment_rate ?? 0,
                'negative_rate' => $latestScore->negative_sentiment_rate ?? 0,
            ],
            'identified_risks' => $this->identifyBrandRisks($company),
        ];
    }
}
