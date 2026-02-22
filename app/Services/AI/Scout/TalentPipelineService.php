<?php

namespace App\Services\AI\Scout;

use App\Models\Application;
use App\Models\CandidateInteraction;
use App\Models\Company;
use App\Models\Job;
use App\Models\PipelineCandidate;
use App\Models\SilverMedalist;
use App\Models\TalentPipeline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TalentPipelineService
{
    /**
     * Create a new talent pipeline
     *
     * @param Company $company
     * @param array $data
     * @return TalentPipeline
     */
    public function createPipeline(Company $company, array $data): TalentPipeline
    {
        $pipeline = TalentPipeline::create([
            'company_id' => $company->id,
            'pipeline_name' => $data['pipeline_name'],
            'target_role' => $data['target_role'],
            'role_description' => $data['role_description'] ?? null,
            'pipeline_type' => $data['pipeline_type'] ?? 'recurring_role',
            'required_skills' => $data['required_skills'] ?? [],
            'preferred_experience' => $data['preferred_experience'] ?? [],
            'cultural_fit_criteria' => $data['cultural_fit_criteria'] ?? [],
            'target_pipeline_size' => $data['target_pipeline_size'] ?? 10,
            'hiring_frequency_days' => $data['hiring_frequency_days'] ?? null,
        ]);

        Log::info('Talent pipeline created', [
            'pipeline_id' => $pipeline->id,
            'company_id' => $company->id,
            'target_role' => $data['target_role'],
        ]);

        return $pipeline;
    }

    /**
     * Add a candidate to a talent pipeline
     *
     * @param TalentPipeline $pipeline
     * @param User $candidate
     * @param array $options
     * @return PipelineCandidate
     */
    public function addCandidateToPipeline(
        TalentPipeline $pipeline,
        User $candidate,
        array $options = []
    ): PipelineCandidate {
        // Calculate match score
        $matchScore = $this->calculatePipelineMatchScore($pipeline, $candidate);
        
        // Calculate DNA compatibility
        $dnaScore = $this->calculateDnaCompatibility($pipeline, $candidate);

        $pipelineCandidate = $pipeline->addCandidate($candidate, [
            'pipeline_stage' => $options['pipeline_stage'] ?? 'sourced',
            'match_score' => $matchScore,
            'dna_compatibility_score' => $dnaScore,
            'sourcing_notes' => $options['sourcing_notes'] ?? null,
            'availability_status' => $options['availability_status'] ?? 'passive',
            'expected_salary_min' => $options['expected_salary_min'] ?? null,
            'expected_salary_max' => $options['expected_salary_max'] ?? null,
        ]);

        // Record interaction
        CandidateInteraction::recordInteraction([
            'company_id' => $pipeline->company_id,
            'user_id' => $candidate->id,
            'interaction_type' => 'pipeline_addition',
            'interaction_summary' => "Added to {$pipeline->pipeline_name} talent pipeline",
            'automated' => $options['automated'] ?? true,
            'candidate_sentiment' => 'neutral',
        ]);

        Log::info('Candidate added to pipeline', [
            'pipeline_id' => $pipeline->id,
            'candidate_id' => $candidate->id,
            'match_score' => $matchScore,
        ]);

        return $pipelineCandidate;
    }

    /**
     * Calculate pipeline match score for a candidate
     *
     * @param TalentPipeline $pipeline
     * @param User $candidate
     * @return float
     */
    protected function calculatePipelineMatchScore(TalentPipeline $pipeline, User $candidate): float
    {
        $scores = [];

        // Skills match (40% weight)
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

        // Experience match (30% weight)
        if ($pipeline->preferred_experience && $candidate->profile) {
            $candidateYears = $candidate->profile->years_of_experience ?? 0;
            $requiredYears = $pipeline->preferred_experience['years'] ?? 0;
            
            if ($requiredYears > 0) {
                $experienceScore = min(100, ($candidateYears / $requiredYears) * 100);
                $scores['experience'] = $experienceScore;
            }
        }

        // Location match (15% weight)
        if ($candidate->profile && $pipeline->preferred_experience['location'] ?? null) {
            $candidateLocation = $candidate->profile->location ?? '';
            $preferredLocation = $pipeline->preferred_experience['location'];
            
            $scores['location'] = stripos($candidateLocation, $preferredLocation) !== false ? 100 : 50;
        }

        // Education match (15% weight)
        if ($candidate->profile && $pipeline->preferred_experience['education'] ?? null) {
            $candidateEducation = $candidate->profile->education ?? '';
            $requiredEducation = $pipeline->preferred_experience['education'];
            
            $scores['education'] = stripos($candidateEducation, $requiredEducation) !== false ? 100 : 60;
        }

        // Calculate weighted average
        $weights = [
            'skills' => 0.40,
            'experience' => 0.30,
            'location' => 0.15,
            'education' => 0.15,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($scores as $key => $score) {
            $totalScore += $score * $weights[$key];
            $totalWeight += $weights[$key];
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 2) : 50;
    }

    /**
     * Calculate DNA compatibility score
     *
     * @param TalentPipeline $pipeline
     * @param User $candidate
     * @return float
     */
    protected function calculateDnaCompatibility(TalentPipeline $pipeline, User $candidate): float
    {
        // Get company DNA profile
        $dnaProfile = $pipeline->company->dnaProfile;
        if (!$dnaProfile) return 50; // Default if no DNA profile

        $culturalCriteria = $pipeline->cultural_fit_criteria ?? [];
        if (empty($culturalCriteria)) return 50;

        $scores = [];

        // Work style compatibility
        if (isset($culturalCriteria['work_style'])) {
            $candidateWorkStyle = $candidate->profile->work_style ?? null;
            $requiredWorkStyle = $culturalCriteria['work_style'];
            
            $scores['work_style'] = $candidateWorkStyle === $requiredWorkStyle ? 100 : 60;
        }

        // Values alignment
        if (isset($culturalCriteria['core_values']) && $candidate->profile) {
            $candidateValues = $candidate->profile->values ?? [];
            $requiredValues = $culturalCriteria['core_values'];
            
            $matchingValues = array_intersect($candidateValues, $requiredValues);
            $scores['values'] = count($requiredValues) > 0 
                ? (count($matchingValues) / count($requiredValues)) * 100 
                : 50;
        }

        // Team dynamics
        if (isset($culturalCriteria['team_preference'])) {
            $scores['team_dynamics'] = 70; // Placeholder for more complex analysis
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 50;
    }

    /**
     * Convert application to silver medalist
     *
     * @param Application $application
     * @param string $reason
     * @param array $data
     * @return SilverMedalist
     */
    public function createSilverMedalist(
        Application $application,
        string $reason,
        array $data = []
    ): SilverMedalist {
        $silverMedalist = SilverMedalist::create([
            'company_id' => $application->job->company_id,
            'user_id' => $application->user_id,
            'job_id' => $application->job_id,
            'application_id' => $application->id,
            'silver_medal_reason' => $reason,
            'interview_score' => $data['interview_score'] ?? null,
            'skill_score' => $data['skill_score'] ?? null,
            'cultural_fit_score' => $data['cultural_fit_score'] ?? null,
            'strengths' => $data['strengths'] ?? [],
            'development_areas' => $data['development_areas'] ?? [],
            'interviewer_feedback' => $data['interviewer_feedback'] ?? null,
            'suitable_future_roles' => $data['suitable_future_roles'] ?? [$application->job->title],
            'silver_medal_date' => now(),
            'next_reach_out_date' => $this->calculateNextReachOutDate($reason),
        ]);

        // Generate AI recommendation
        $aiRecommendation = $this->generateSilverMedalistRecommendation($silverMedalist);
        $silverMedalist->update(['ai_recommendation' => $aiRecommendation]);

        // Record interaction
        CandidateInteraction::recordInteraction([
            'company_id' => $application->job->company_id,
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'job_id' => $application->job_id,
            'interaction_type' => 'silver_medal_notification',
            'interaction_summary' => "Designated as silver medalist: {$reason}",
            'automated' => true,
            'candidate_sentiment' => 'neutral',
        ]);

        Log::info('Silver medalist created', [
            'silver_medalist_id' => $silverMedalist->id,
            'user_id' => $application->user_id,
            'reason' => $reason,
        ]);

        return $silverMedalist;
    }

    /**
     * Calculate next reach out date based on silver medal reason
     *
     * @param string $reason
     * @return Carbon
     */
    protected function calculateNextReachOutDate(string $reason): Carbon
    {
        return match($reason) {
            'strong_second_choice' => now()->addMonths(1),
            'timing_mismatch' => now()->addMonths(2),
            'budget_constraints' => now()->addMonths(3),
            'overqualified' => now()->addMonths(6),
            'team_fit_preference' => now()->addMonths(3),
            'skill_mismatch_minor' => now()->addMonths(4),
            'cultural_potential' => now()->addMonths(2),
            default => now()->addMonths(3),
        };
    }

    /**
     * Generate AI recommendation for silver medalist
     *
     * @param SilverMedalist $silverMedalist
     * @return string
     */
    protected function generateSilverMedalistRecommendation(SilverMedalist $silverMedalist): string
    {
        $recommendation = "Recommended Actions:\n\n";

        // Based on reason
        switch ($silverMedalist->silver_medal_reason) {
            case 'strong_second_choice':
                $recommendation .= "• Priority re-engagement candidate\n";
                $recommendation .= "• Monitor for similar role openings\n";
                $recommendation .= "• Consider for leadership pipeline\n";
                break;
                
            case 'overqualified':
                $recommendation .= "• Ideal for senior/leadership positions\n";
                $recommendation .= "• Consider for mentorship roles\n";
                $recommendation .= "• Review for succession planning\n";
                break;
                
            case 'timing_mismatch':
                $recommendation .= "• High-priority follow-up required\n";
                $recommendation .= "• Check availability monthly\n";
                $recommendation .= "• Fast-track interview process when ready\n";
                break;
                
            case 'budget_constraints':
                $recommendation .= "• Re-engage when budget increases\n";
                $recommendation .= "• Consider for equity-heavy compensation\n";
                $recommendation .= "• Monitor salary expectations\n";
                break;
                
            default:
                $recommendation .= "• Add to relevant talent pipeline\n";
                $recommendation .= "• Periodic check-ins recommended\n";
                $recommendation .= "• Review for alternative roles\n";
        }

        // Based on scores
        if ($silverMedalist->overall_score >= 85) {
            $recommendation .= "\n⭐ Top-tier candidate - Maintain warm relationship";
        } elseif ($silverMedalist->overall_score >= 75) {
            $recommendation .= "\n✓ Strong candidate - Regular engagement recommended";
        }

        return $recommendation;
    }

    /**
     * Get silver medalists ready for re-engagement
     *
     * @param Company $company
     * @return Collection
     */
    public function getSilverMedalistsForReEngagement(Company $company): Collection
    {
        return SilverMedalist::where('company_id', $company->id)
            ->readyForReEngagement()
            ->highPotential()
            ->with(['user', 'job'])
            ->orderBy('next_reach_out_date')
            ->get();
    }

    /**
     * Identify candidates from applications who should be added to silver medalist pool
     *
     * @param Company $company
     * @param int $months
     * @return Collection
     */
    public function identifySilverMedalistCandidates(Company $company, int $months = 6): Collection
    {
        $applications = Application::whereHas('job', function($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->whereIn('status', ['rejected', 'withdrawn'])
            ->where('updated_at', '>=', now()->subMonths($months))
            ->whereDoesntHave('silverMedalist')
            ->with(['user', 'job'])
            ->get();

        return $applications->filter(function($application) {
            // Check if candidate reached interview stage (indicator of quality)
            $interactionCount = CandidateInteraction::where('application_id', $application->id)
                ->where('interaction_type', 'interview_completed')
                ->count();
                
            return $interactionCount > 0;
        });
    }

    /**
     * Update pipeline health scores for all pipelines
     *
     * @param Company $company
     * @return void
     */
    public function updatePipelineHealthScores(Company $company): void
    {
        $pipelines = TalentPipeline::where('company_id', $company->id)
            ->active()
            ->get();

        foreach ($pipelines as $pipeline) {
            $pipeline->updateHealthScore();
        }

        Log::info('Pipeline health scores updated', [
            'company_id' => $company->id,
            'pipeline_count' => $pipelines->count(),
        ]);
    }

    /**
     * Get pipeline health summary
     *
     * @param Company $company
     * @return array
     */
    public function getPipelineHealthSummary(Company $company): array
    {
        $pipelines = TalentPipeline::where('company_id', $company->id)
            ->active()
            ->get();

        return [
            'total_pipelines' => $pipelines->count(),
            'healthy_pipelines' => $pipelines->where('pipeline_health_score', '>=', 70)->count(),
            'pipelines_needing_attention' => $pipelines->where('pipeline_health_score', '<', 50)->count(),
            'total_candidates' => $pipelines->sum('current_pipeline_size'),
            'average_health_score' => round($pipelines->avg('pipeline_health_score'), 2),
            'pipelines_by_status' => [
                'excellent' => $pipelines->filter(fn($p) => $p->health_status === 'excellent')->count(),
                'good' => $pipelines->filter(fn($p) => $p->health_status === 'good')->count(),
                'fair' => $pipelines->filter(fn($p) => $p->health_status === 'fair')->count(),
                'needs_improvement' => $pipelines->filter(fn($p) => $p->health_status === 'needs_improvement')->count(),
            ],
            'warm_candidates' => PipelineCandidate::whereIn('talent_pipeline_id', $pipelines->pluck('id'))
                ->warm()
                ->count(),
            'candidates_needing_follow_up' => PipelineCandidate::whereIn('talent_pipeline_id', $pipelines->pluck('id'))
                ->needsFollowUp()
                ->count(),
        ];
    }

    /**
     * Get candidates needing follow-up across all pipelines
     *
     * @param Company $company
     * @return Collection
     */
    public function getCandidatesNeedingFollowUp(Company $company): Collection
    {
        $pipelineIds = TalentPipeline::where('company_id', $company->id)
            ->active()
            ->pluck('id');

        return PipelineCandidate::whereIn('talent_pipeline_id', $pipelineIds)
            ->needsFollowUp()
            ->with(['user', 'talentPipeline'])
            ->orderBy('next_follow_up_date')
            ->get();
    }

    /**
     * Advance candidate through pipeline stages
     *
     * @param PipelineCandidate $pipelineCandidate
     * @param string $newStage
     * @param array $notes
     * @return PipelineCandidate
     */
    public function advanceCandidateStage(
        PipelineCandidate $pipelineCandidate,
        string $newStage,
        array $notes = []
    ): PipelineCandidate {
        $oldStage = $pipelineCandidate->pipeline_stage;
        $pipelineCandidate->advanceStage($newStage);

        // Record interaction
        CandidateInteraction::recordInteraction([
            'company_id' => $pipelineCandidate->talentPipeline->company_id,
            'user_id' => $pipelineCandidate->user_id,
            'interaction_type' => 'pipeline_engagement',
            'interaction_summary' => "Pipeline stage: {$oldStage} → {$newStage}",
            'interaction_metadata' => $notes,
            'automated' => $notes['automated'] ?? false,
            'candidate_sentiment' => 'neutral',
        ]);

        // Update pipeline health
        $pipelineCandidate->talentPipeline->updateHealthScore();

        return $pipelineCandidate->fresh();
    }

    /**
     * Get top candidates from pipeline
     *
     * @param TalentPipeline $pipeline
     * @param int $limit
     * @return Collection
     */
    public function getTopPipelineCandidates(TalentPipeline $pipeline, int $limit = 10): Collection
    {
        return $pipeline->candidates()
            ->whereIn('pipeline_stage', ['warm', 'hot'])
            ->orderByDesc('match_score')
            ->orderByDesc('dna_compatibility_score')
            ->orderBy('last_engaged_at', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Match candidates from pipeline to a new job opening
     *
     * @param Job $job
     * @return Collection
     */
    public function matchPipelineCandidatesToJob(Job $job): Collection
    {
        // Find relevant pipelines
        $pipelines = TalentPipeline::where('company_id', $job->company_id)
            ->active()
            ->where('target_role', 'like', "%{$job->title}%")
            ->orWhere(function($query) use ($job) {
                // Match by skills
                $jobSkills = $job->required_skills ?? [];
                foreach ($jobSkills as $skill) {
                    $query->orWhereJsonContains('required_skills', $skill);
                }
            })
            ->get();

        if ($pipelines->isEmpty()) {
            return collect();
        }

        // Get warm candidates from these pipelines
        $candidates = PipelineCandidate::whereIn('talent_pipeline_id', $pipelines->pluck('id'))
            ->warm()
            ->where('match_score', '>=', 60)
            ->with(['user', 'talentPipeline'])
            ->orderByDesc('match_score')
            ->get();

        return $candidates->map(function($candidate) use ($job) {
            return [
                'candidate' => $candidate,
                'pipeline' => $candidate->talentPipeline,
                'recommended_action' => $this->getRecommendedAction($candidate, $job),
            ];
        });
    }

    /**
     * Get recommended action for candidate-job match
     *
     * @param PipelineCandidate $candidate
     * @param Job $job
     * @return string
     */
    protected function getRecommendedAction(PipelineCandidate $candidate, Job $job): string
    {
        if ($candidate->pipeline_stage === 'hot' && $candidate->match_score >= 80) {
            return 'immediate_contact';
        }
        
        if ($candidate->pipeline_stage === 'warm' && $candidate->match_score >= 70) {
            return 'priority_outreach';
        }
        
        if ($candidate->availability_status === 'immediately_available') {
            return 'fast_track';
        }
        
        return 'standard_outreach';
    }
}
