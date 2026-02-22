<?php

namespace App\Services\AI\Scout;

use App\Models\CandidateInteraction;
use App\Models\Company;
use App\Models\PassiveCandidateProfile;
use App\Models\TalentPipeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class PassiveCandidateScoutService
{
    /**
     * OpenAI model for discovery analysis
     */
    protected string $model = 'gpt-5-mini';

    /**
     * Create passive candidate profile
     *
     * @param Company $company
     * @param User $user
     * @param array $data
     * @return PassiveCandidateProfile
     */
    public function createPassiveCandidateProfile(
        Company $company,
        User $user,
        array $data
    ): PassiveCandidateProfile {
        $dnaScore = $this->calculateCompanyDnaMatch($company, $user);

        $profile = PassiveCandidateProfile::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'discovery_method' => $data['discovery_method'] ?? 'platform_activity',
            'discovery_source' => $data['discovery_source'] ?? null,
            'discovery_notes' => $data['discovery_notes'] ?? null,
            'dna_match_score' => $dnaScore,
            'engagement_readiness' => 'monitor',
            'engagement_signals' => [],
            'last_monitored_at' => now(),
        ]);

        // Assess initial engagement readiness
        $profile->assessEngagementReadiness();

        Log::info('Passive candidate profile created', [
            'profile_id' => $profile->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'dna_score' => $dnaScore,
        ]);

        return $profile;
    }

    /**
     * Calculate company DNA match score
     *
     * @param Company $company
     * @param User $user
     * @return float
     */
    public function calculateCompanyDnaMatch(Company $company, User $user): float
    {
        $cacheKey = "dna_match_{$company->id}_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function() use ($company, $user) {
            $dnaProfile = $company->dnaProfile;
            if (!$dnaProfile) return 50;

            $scores = [];

            // Skills alignment (30% weight)
            if ($dnaProfile->required_skills && $user->profile) {
                $candidateSkills = $user->profile->skills ?? [];
                $companySkills = $dnaProfile->required_skills;
                
                if (count($companySkills) > 0) {
                    $matchingSkills = array_intersect(
                        array_map('strtolower', $candidateSkills),
                        array_map('strtolower', $companySkills)
                    );
                    $scores['skills'] = (count($matchingSkills) / count($companySkills)) * 100;
                }
            }

            // Values alignment (25% weight)
            if ($dnaProfile->core_values && $user->profile) {
                $candidateValues = $user->profile->values ?? [];
                $companyValues = $dnaProfile->core_values;
                
                if (count($companyValues) > 0) {
                    $matchingValues = array_intersect($candidateValues, $companyValues);
                    $scores['values'] = (count($matchingValues) / count($companyValues)) * 100;
                }
            }

            // Work style compatibility (20% weight)
            if ($dnaProfile->work_style && $user->profile) {
                $candidateWorkStyle = $user->profile->work_style ?? null;
                $companyWorkStyle = $dnaProfile->work_style;
                
                $scores['work_style'] = $candidateWorkStyle === $companyWorkStyle ? 100 : 60;
            }

            // Experience level match (15% weight)
            if ($dnaProfile->experience_level && $user->profile) {
                $candidateYears = $user->profile->years_of_experience ?? 0;
                $requiredYears = $dnaProfile->experience_level['years'] ?? 0;
                
                if ($requiredYears > 0) {
                    $experienceScore = min(100, ($candidateYears / $requiredYears) * 100);
                    $scores['experience'] = $experienceScore;
                }
            }

            // Career trajectory alignment (10% weight)
            if ($user->profile) {
                $scores['trajectory'] = $this->assessCareerTrajectory($user);
            }

            // Calculate weighted average
            $weights = [
                'skills' => 0.30,
                'values' => 0.25,
                'work_style' => 0.20,
                'experience' => 0.15,
                'trajectory' => 0.10,
            ];

            $totalScore = 0;
            $totalWeight = 0;

            foreach ($scores as $key => $score) {
                $totalScore += $score * $weights[$key];
                $totalWeight += $weights[$key];
            }

            return $totalWeight > 0 ? round($totalScore / $totalWeight, 2) : 50;
        });
    }

    /**
     * Assess candidate's career trajectory
     *
     * @param User $user
     * @return float
     */
    protected function assessCareerTrajectory(User $user): float
    {
        $experience = $user->profile->experience ?? [];
        if (count($experience) < 2) return 50;

        // Analyze progression pattern
        $progressionScore = 70; // Base score

        // Check for role advancement
        $titles = array_column($experience, 'title');
        $hasAdvancement = $this->detectCareerAdvancement($titles);
        if ($hasAdvancement) {
            $progressionScore += 15;
        }

        // Check for skill accumulation
        $recentRoles = array_slice($experience, 0, 3);
        $skillGrowth = count($recentRoles) > 1;
        if ($skillGrowth) {
            $progressionScore += 15;
        }

        return min(100, $progressionScore);
    }

    /**
     * Detect career advancement in job titles
     *
     * @param array $titles
     * @return bool
     */
    protected function detectCareerAdvancement(array $titles): bool
    {
        $seniorityKeywords = [
            'junior' => 1,
            'associate' => 2,
            'mid' => 3,
            'senior' => 4,
            'lead' => 5,
            'principal' => 6,
            'staff' => 6,
            'director' => 7,
            'vp' => 8,
            'head' => 8,
            'chief' => 9,
        ];

        $levels = [];
        foreach ($titles as $title) {
            $titleLower = strtolower($title);
            foreach ($seniorityKeywords as $keyword => $level) {
                if (stripos($titleLower, $keyword) !== false) {
                    $levels[] = $level;
                    break;
                }
            }
        }

        if (count($levels) < 2) return false;

        // Check if levels are generally increasing
        $lastLevel = $levels[0];
        $hasAdvancement = false;
        
        for ($i = 1; $i < count($levels); $i++) {
            if ($levels[$i] > $lastLevel) {
                $hasAdvancement = true;
                break;
            }
        }

        return $hasAdvancement;
    }

    /**
     * Monitor passive candidate for engagement signals
     *
     * @param PassiveCandidateProfile $profile
     * @return void
     */
    public function monitorEngagementSignals(PassiveCandidateProfile $profile): void
    {
        $signals = [];

        // Check profile activity
        $user = $profile->user;
        
        // Signal: Recent profile update
        if ($user->profile && $user->profile->updated_at >= now()->subDays(30)) {
            $signals[] = 'profile_updated';
        }

        // Signal: Recent login activity (if tracked)
        if ($user->last_login_at && $user->last_login_at >= now()->subDays(7)) {
            $signals[] = 'recent_activity';
        }

        // Signal: Job search behavior
        $recentApplications = $user->applications()
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();
            
        if ($recentApplications > 0) {
            $signals[] = 'job_search';
            
            if ($recentApplications >= 5) {
                $signals[] = 'active_job_search';
            }
        }

        // Signal: Career milestone timing (tenure analysis)
        if ($user->profile) {
            $experience = $user->profile->experience ?? [];
            if (!empty($experience)) {
                $currentRole = $experience[0] ?? null;
                if ($currentRole) {
                    $tenure = $this->calculateTenure($currentRole);
                    
                    // 2-year mark is common job change time
                    if ($tenure >= 18 && $tenure <= 30) {
                        $signals[] = 'tenure_milestone';
                    }
                    
                    // Long tenure might indicate readiness for change
                    if ($tenure >= 48) {
                        $signals[] = 'long_tenure';
                    }
                }
            }
        }

        // Signal: LinkedIn/social activity (placeholder for future integration)
        // This would integrate with LinkedIn API or similar
        
        // Update profile with new signals
        $profile->updateMonitoring($signals);

        Log::info('Passive candidate monitored', [
            'profile_id' => $profile->id,
            'signals_detected' => count($signals),
            'engagement_readiness' => $profile->engagement_readiness,
        ]);
    }

    /**
     * Calculate tenure in months from role data
     *
     * @param array $role
     * @return int
     */
    protected function calculateTenure(array $role): int
    {
        $startDate = isset($role['start_date']) ? Carbon::parse($role['start_date']) : null;
        $endDate = isset($role['end_date']) ? Carbon::parse($role['end_date']) : now();
        
        if (!$startDate) return 0;
        
        return $startDate->diffInMonths($endDate);
    }

    /**
     * Generate AI-powered engagement strategy
     *
     * @param PassiveCandidateProfile $profile
     * @return array
     */
    public function generateEngagementStrategy(PassiveCandidateProfile $profile): array
    {
        $cacheKey = "engagement_strategy_{$profile->id}";
        
        return Cache::remember($cacheKey, 3600, function() use ($profile) {
            $user = $profile->user;
            $company = $profile->company;

            try {
                $prompt = $this->buildEngagementPrompt($profile, $user, $company);
                
                $response = OpenAI::chat()->create([
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert talent acquisition strategist specializing in passive candidate engagement. Provide specific, actionable strategies for reaching out to passive candidates based on their profile and engagement signals.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

                $strategy = $response->choices[0]->message->content;

                return [
                    'strategy' => $strategy,
                    'recommended_timing' => $profile->optimal_engagement_date,
                    'engagement_channels' => $this->recommendChannels($profile),
                    'talking_points' => $this->generateTalkingPoints($profile, $company),
                    'risk_factors' => $this->identifyRiskFactors($profile),
                ];

            } catch (\Exception $e) {
                Log::error('Failed to generate engagement strategy', [
                    'profile_id' => $profile->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->getFallbackStrategy($profile);
            }
        });
    }

    /**
     * Build prompt for engagement strategy
     *
     * @param PassiveCandidateProfile $profile
     * @param User $user
     * @param Company $company
     * @return string
     */
    protected function buildEngagementPrompt(
        PassiveCandidateProfile $profile,
        User $user,
        Company $company
    ): string {
        $signals = implode(', ', $profile->engagement_signals ?? []);
        $yearsExp = $user->profile->years_of_experience ?? 'Unknown';
        $currentRole = $user->profile->current_position ?? 'Unknown';
        $formattedSkills = $this->formatSkills($user);
        $companyIndustry = $company->industry ?? 'Unknown';
        $formattedDna = $this->formatCompanyDna($company);
        
        return <<<PROMPT
Analyze this passive candidate and recommend an engagement strategy:

CANDIDATE PROFILE:
- Years of Experience: {$yearsExp}
- Current Role: {$currentRole}
- Skills: {$formattedSkills}
- DNA Match Score: {$profile->dna_match_score}%

ENGAGEMENT SIGNALS:
{$signals}

COMPANY CONTEXT:
- Company: {$company->name}
- Industry: {$companyIndustry}
- Company DNA: {$formattedDna}

ENGAGEMENT READINESS: {$profile->engagement_readiness}
DAYS SINCE DISCOVERY: {$profile->days_since_discovery}

Provide:
1. Best approach for initial contact
2. Key value propositions to emphasize
3. Optimal messaging tone
4. Potential objections and how to address them
5. Timeline for follow-up
PROMPT;
    }

    /**
     * Format skills for prompt
     *
     * @param User $user
     * @return string
     */
    protected function formatSkills(User $user): string
    {
        $skills = $user->profile->skills ?? [];
        return count($skills) > 0 ? implode(', ', array_slice($skills, 0, 10)) : 'Not specified';
    }

    /**
     * Format company DNA for prompt
     *
     * @param Company $company
     * @return string
     */
    protected function formatCompanyDna(Company $company): string
    {
        $dnaProfile = $company->dnaProfile;
        if (!$dnaProfile) return 'Not available';

        $values = $dnaProfile->core_values ?? [];
        return count($values) > 0 ? implode(', ', $values) : 'Not specified';
    }

    /**
     * Recommend engagement channels
     *
     * @param PassiveCandidateProfile $profile
     * @return array
     */
    protected function recommendChannels(PassiveCandidateProfile $profile): array
    {
        $channels = ['email']; // Always include email

        // Add LinkedIn for high-DNA matches
        if ($profile->dna_match_score >= 75) {
            $channels[] = 'linkedin';
        }

        // Add phone for urgent opportunities
        if ($profile->engagement_readiness === 'urgent') {
            $channels[] = 'phone';
        }

        // Add referral path if available
        if ($profile->discovery_method === 'employee_referral') {
            $channels[] = 'referral_introduction';
        }

        return $channels;
    }

    /**
     * Generate talking points for engagement
     *
     * @param PassiveCandidateProfile $profile
     * @param Company $company
     * @return array
     */
    protected function generateTalkingPoints(PassiveCandidateProfile $profile, Company $company): array
    {
        $points = [];

        // High DNA match
        if ($profile->dna_match_score >= 80) {
            $points[] = "Strong cultural alignment with {$company->name}'s values and work style";
        }

        // Career growth
        $points[] = "Opportunity for career advancement and skill development";

        // Company strengths
        if ($company->dnaProfile) {
            $points[] = "Work with cutting-edge technologies and innovative teams";
        }

        // Work-life balance
        $points[] = "Flexible work arrangements and comprehensive benefits";

        // Impact
        $points[] = "High-impact role with visibility to leadership";

        return $points;
    }

    /**
     * Identify risk factors for engagement
     *
     * @param PassiveCandidateProfile $profile
     * @return array
     */
    protected function identifyRiskFactors(PassiveCandidateProfile $profile): array
    {
        $risks = [];

        // Low DNA match
        if ($profile->dna_match_score < 60) {
            $risks[] = 'Lower cultural fit score may indicate values misalignment';
        }

        // No recent signals
        if (count($profile->engagement_signals ?? []) === 0) {
            $risks[] = 'No engagement signals detected - may not be open to opportunities';
        }

        // Long time since discovery
        if ($profile->days_since_discovery > 180) {
            $risks[] = 'Profile data may be outdated - verify current situation';
        }

        // Previous failed engagement
        if ($profile->engagement_initiated && $profile->engagement_outcome === 'not_interested') {
            $risks[] = 'Previously expressed disinterest - approach with caution';
        }

        return $risks;
    }

    /**
     * Get fallback strategy when AI fails
     *
     * @param PassiveCandidateProfile $profile
     * @return array
     */
    protected function getFallbackStrategy(PassiveCandidateProfile $profile): array
    {
        return [
            'strategy' => $this->getBasicStrategy($profile->engagement_readiness),
            'recommended_timing' => $profile->optimal_engagement_date,
            'engagement_channels' => ['email'],
            'talking_points' => [
                'Career growth opportunity',
                'Competitive compensation',
                'Great team culture',
            ],
            'risk_factors' => [],
        ];
    }

    /**
     * Get basic engagement strategy based on readiness
     *
     * @param string $readiness
     * @return string
     */
    protected function getBasicStrategy(string $readiness): string
    {
        return match($readiness) {
            'urgent' => 'Immediate personalized outreach via LinkedIn and email. Highlight urgent opportunity and strong cultural fit. Offer phone call within 48 hours.',
            'ready' => 'Warm email introduction within 1 week. Focus on career growth opportunities and company culture. Include specific role details.',
            'monitor' => 'Add to nurture campaign. Send monthly industry insights and company updates. Build relationship before direct approach.',
            default => 'Continue monitoring for engagement signals. No direct contact at this time.',
        };
    }

    /**
     * Initiate engagement with passive candidate
     *
     * @param PassiveCandidateProfile $profile
     * @param array $options
     * @return void
     */
    public function initiateEngagement(PassiveCandidateProfile $profile, array $options = []): void
    {
        $profile->initiateEngagement();

        // Extract options with defaults
        $engagementMethod = $options['method'] ?? 'email';
        $messageSent = $options['message_sent'] ?? false;
        $isAutomated = $options['automated'] ?? true;

        // Record interaction
        CandidateInteraction::recordInteraction([
            'company_id' => $profile->company_id,
            'user_id' => $profile->user_id,
            'interaction_type' => 'pipeline_engagement',
            'interaction_summary' => "Passive candidate engagement initiated: {$engagementMethod}",
            'interaction_metadata' => [
                'engagement_method' => $engagementMethod,
                'message_sent' => $messageSent,
                'dna_match_score' => $profile->dna_match_score,
            ],
            'automated' => $isAutomated,
            'candidate_sentiment' => 'neutral',
        ]);

        Log::info('Passive candidate engagement initiated', [
            'profile_id' => $profile->id,
            'user_id' => $profile->user_id,
            'method' => $engagementMethod,
        ]);
    }

    /**
     * Discover passive candidates for a pipeline
     *
     * @param TalentPipeline $pipeline
     * @param int $limit
     * @return Collection
     */
    public function discoverCandidatesForPipeline(TalentPipeline $pipeline, int $limit = 20): Collection
    {
        // Get existing users who might be good passive candidates
        $existingCandidateIds = PassiveCandidateProfile::where('company_id', $pipeline->company_id)
            ->pluck('user_id')
            ->toArray();

        $candidates = User::where('account_type', 'job_seeker')
            ->whereNotIn('id', $existingCandidateIds)
            ->whereHas('profile')
            ->with('profile')
            ->get();

        // Score and filter candidates
        $scoredCandidates = $candidates->map(function($candidate) use ($pipeline) {
            $score = $this->scoreCandidateForPipeline($candidate, $pipeline);
            
            return [
                'user' => $candidate,
                'score' => $score,
                'dna_match' => $this->calculateCompanyDnaMatch($pipeline->company, $candidate),
            ];
        })
        ->filter(fn($item) => $item['score'] >= 60) // Only good matches
        ->sortByDesc('score')
        ->take($limit);

        return $scoredCandidates;
    }

    /**
     * Score candidate for specific pipeline
     *
     * @param User $candidate
     * @param TalentPipeline $pipeline
     * @return float
     */
    protected function scoreCandidateForPipeline(User $candidate, TalentPipeline $pipeline): float
    {
        $scores = [];

        // Skills match
        if ($pipeline->required_skills && $candidate->profile) {
            $candidateSkills = $candidate->profile->skills ?? [];
            $requiredSkills = $pipeline->required_skills;
            
            if (count($requiredSkills) > 0) {
                $matchingSkills = array_intersect(
                    array_map('strtolower', $candidateSkills),
                    array_map('strtolower', $requiredSkills)
                );
                $scores['skills'] = (count($matchingSkills) / count($requiredSkills)) * 100;
            }
        }

        // Experience match
        if ($pipeline->preferred_experience && $candidate->profile) {
            $candidateYears = $candidate->profile->years_of_experience ?? 0;
            $requiredYears = $pipeline->preferred_experience['years'] ?? 0;
            
            if ($requiredYears > 0) {
                $experienceScore = min(100, ($candidateYears / $requiredYears) * 100);
                $scores['experience'] = $experienceScore;
            }
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    }

    /**
     * Get passive candidates ready for engagement
     *
     * @param Company $company
     * @return Collection
     */
    public function getCandidatesReadyForEngagement(Company $company): Collection
    {
        return PassiveCandidateProfile::where('company_id', $company->id)
            ->readyForEngagement()
            ->highDnaMatch()
            ->notEngaged()
            ->with(['user'])
            ->orderBy('engagement_readiness', 'desc')
            ->orderBy('dna_match_score', 'desc')
            ->get();
    }

    /**
     * Get passive candidate engagement metrics
     *
     * @param Company $company
     * @return array
     */
    public function getEngagementMetrics(Company $company): array
    {
        $profiles = PassiveCandidateProfile::where('company_id', $company->id)->get();

        return [
            'total_passive_candidates' => $profiles->count(),
            'high_dna_matches' => $profiles->where('dna_match_score', '>=', 75)->count(),
            'ready_for_engagement' => $profiles->whereIn('engagement_readiness', ['ready', 'urgent'])->count(),
            'urgent_opportunities' => $profiles->where('engagement_readiness', 'urgent')->count(),
            'engagement_initiated' => $profiles->where('engagement_initiated', true)->count(),
            'successful_conversions' => $profiles->where('engagement_outcome', 'converted_to_applicant')->count(),
            'average_dna_score' => round($profiles->avg('dna_match_score'), 2),
            'by_readiness' => [
                'urgent' => $profiles->where('engagement_readiness', 'urgent')->count(),
                'ready' => $profiles->where('engagement_readiness', 'ready')->count(),
                'monitor' => $profiles->where('engagement_readiness', 'monitor')->count(),
                'not_ready' => $profiles->where('engagement_readiness', 'not_ready')->count(),
            ],
            'conversion_rate' => $profiles->where('engagement_initiated', true)->count() > 0
                ? round(($profiles->where('engagement_outcome', 'converted_to_applicant')->count() / 
                    $profiles->where('engagement_initiated', true)->count()) * 100, 2)
                : 0,
        ];
    }
}
