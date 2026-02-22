<?php

declare(strict_types=1);

namespace App\Services\AI\Scout;

use App\Models\Application;
use App\Models\CareerPathPrediction;
use App\Models\DecisionTrace;
use App\Models\DevelopmentNeed;
use App\Models\Job;
use App\Models\SuccessPrediction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class PredictiveAnalyticsService
{
    private const SUCCESS_FACTORS = [
        'skills_match' => 0.25,
        'experience_relevance' => 0.20,
        'cultural_fit' => 0.15,
        'team_compatibility' => 0.15,
        'behavioral_alignment' => 0.10,
        'learning_agility' => 0.10,
        'past_performance_indicators' => 0.05,
    ];

    private const DEFAULT_CACHE_TTL = 86400;

    private const MODEL_VERSION = 'scout_predictive_v1.0';

    public function predictSuccessProbability(Application $application, array $options = []): array
    {
        $cacheKey = "predictive_success_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application) {
            Log::info('Generating success probability prediction', [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
                'company_id' => $application->job->company_id ?? null,
            ]);

            $skillsMatch = $this->analyzeSkillsMatch($application);
            $experienceRelevance = $this->analyzeExperienceRelevance($application);
            $culturalFit = $this->analyzeCulturalFit($application);
            $teamCompatibility = $this->analyzeTeamCompatibility($application);
            $behavioralAlignment = $this->analyzeBehavioralAlignment($application);
            $learningAgility = $this->assessLearningAgility($application);
            $pastPerformance = $this->analyzePastPerformanceIndicators($application);

            $successProbability = (
                $skillsMatch * self::SUCCESS_FACTORS['skills_match'] +
                $experienceRelevance * self::SUCCESS_FACTORS['experience_relevance'] +
                $culturalFit * self::SUCCESS_FACTORS['cultural_fit'] +
                $teamCompatibility * self::SUCCESS_FACTORS['team_compatibility'] +
                $behavioralAlignment * self::SUCCESS_FACTORS['behavioral_alignment'] +
                $learningAgility * self::SUCCESS_FACTORS['learning_agility'] +
                $pastPerformance * self::SUCCESS_FACTORS['past_performance_indicators']
            );
            $successProbability = min(max($successProbability, 0), 1);

            $factorScores = [
                'skills_match' => round($skillsMatch, 4),
                'experience_relevance' => round($experienceRelevance, 4),
                'cultural_fit' => round($culturalFit, 4),
                'team_compatibility' => round($teamCompatibility, 4),
                'behavioral_alignment' => round($behavioralAlignment, 4),
                'learning_agility' => round($learningAgility, 4),
                'past_performance' => round($pastPerformance, 4),
            ];

            $confidenceScore = $this->calculateConfidenceScore($application);
            $aiInsights = $this->generateSuccessInsights($application, $successProbability, $factorScores);
            $strengths = $this->identifyStrengths($factorScores);
            $concerns = $this->identifyConcerns($factorScores);

            $explanation = $this->buildDecisionExplanation(
                'success_probability',
                [
                    'skills_match' => ['value' => $skillsMatch, 'weight' => self::SUCCESS_FACTORS['skills_match']],
                    'experience_relevance' => ['value' => $experienceRelevance, 'weight' => self::SUCCESS_FACTORS['experience_relevance']],
                    'cultural_fit' => ['value' => $culturalFit, 'weight' => self::SUCCESS_FACTORS['cultural_fit']],
                    'team_compatibility' => ['value' => $teamCompatibility, 'weight' => self::SUCCESS_FACTORS['team_compatibility']],
                    'behavioral_alignment' => ['value' => $behavioralAlignment, 'weight' => self::SUCCESS_FACTORS['behavioral_alignment']],
                    'learning_agility' => ['value' => $learningAgility, 'weight' => self::SUCCESS_FACTORS['learning_agility']],
                    'past_performance_indicators' => ['value' => $pastPerformance, 'weight' => self::SUCCESS_FACTORS['past_performance_indicators']],
                ],
                $factorScores,
                $successProbability
            );
            $this->persistDecisionTrace($application->id, 'success_probability', $explanation);

            return [
                'success_probability' => round($successProbability, 4),
                'confidence_score' => round($confidenceScore, 4),
                'success_category' => $this->categorizeSuccessProbability($successProbability),
                'factor_scores' => $factorScores,
                'key_strengths' => $strengths,
                'key_concerns' => $concerns,
                'ai_insights' => $aiInsights,
                'prediction_basis' => $this->getPredictionBasis(),
                'recommendation' => $this->generateRecommendation($successProbability, $concerns),
                'comparable_profiles' => $this->getComparableProfiles($application),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function forecastTenure(Application $application, array $options = []): array
    {
        $cacheKey = "predictive_tenure_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application) {
            Log::info('Generating tenure forecast', [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
            ]);

            $tenureHistory = $this->analyzeTenureHistory($application->user);
            $roleStability = $this->assessRoleStability($application);
            $growthOpportunities = $this->evaluateGrowthOpportunities($application);
            $marketAlignment = $this->assessMarketAlignment($application);

            $expectedTenure = $this->calculateExpectedTenure(
                $tenureHistory,
                $roleStability,
                $growthOpportunities,
                $marketAlignment
            );

            $tenureRange = $this->calculateTenureRange($expectedTenure);
            $playerType = $this->classifyPlayerType($expectedTenure);
            $confidenceScore = $this->calculateTenureConfidence($tenureHistory);
            $confidenceLevel = $this->mapConfidenceToLevel($confidenceScore);

            $flightRiskScore = $this->deriveFlightRiskScore($expectedTenure, $tenureHistory, $growthOpportunities);
            $riskCategory = $this->mapRiskScoreToCategory($flightRiskScore);
            $retentionFactors = $this->buildRetentionFactorsSummary($application, $tenureHistory, $growthOpportunities);
            $riskIndicators = $this->buildRiskIndicatorsSummary($application, $flightRiskScore);
            $aiInsights = $this->generateTenureInsights($expectedTenure, $tenureHistory, $roleStability, $growthOpportunities);
            $probabilityCurve = $this->buildTenureProbabilityCurve($expectedTenure, $tenureRange);

            $explanation = $this->buildDecisionExplanation(
                'tenure_forecast',
                [
                    'tenure_history_avg' => ['value' => (float) ($tenureHistory['average_months'] ?? 36), 'weight' => 0.40],
                    'role_stability' => ['value' => $roleStability, 'weight' => 0.25],
                    'growth_opportunities' => ['value' => $growthOpportunities, 'weight' => 0.20],
                    'market_alignment' => ['value' => $marketAlignment, 'weight' => 0.15],
                ],
                [
                    'tenure_history_avg' => round(min(($tenureHistory['average_months'] ?? 36) / 120, 1.0), 4),
                    'role_stability' => round($roleStability, 4),
                    'growth_opportunities' => round($growthOpportunities, 4),
                    'market_alignment' => round($marketAlignment, 4),
                ],
                $flightRiskScore
            );
            $this->persistDecisionTrace($application->id, 'tenure_forecast', $explanation);

            return [
                'predicted_tenure_months' => (int) $expectedTenure,
                'predicted_tenure_years' => round($expectedTenure / 12, 1),
                'tenure_range' => $tenureRange,
                'player_type' => $playerType,
                'player_type_display' => $this->getPlayerTypeDisplay($playerType),
                'confidence_level' => $confidenceLevel,
                'confidence_score' => round($confidenceScore, 4),
                'confidence_label' => $this->formatConfidenceLabel($confidenceLevel),
                'flight_risk_score' => round($flightRiskScore, 4),
                'risk_category' => $riskCategory,
                'retention_factors' => $retentionFactors,
                'risk_indicators' => $riskIndicators,
                'ai_insights' => $aiInsights,
                'recommendation' => $this->buildTenureRecommendation($riskCategory, $retentionFactors, $riskIndicators),
                'probability_curve' => $probabilityCurve,
                'is_flight_risk' => $flightRiskScore >= 0.6,
                'explanation_json' => $explanation,
            ];
        });
    }

    public function estimateTimeToProductivity(Application $application, Job $job = null, array $options = []): array
    {
        $job = $job ?? $application->job;
        $cacheKey = "predictive_productivity_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application, $job) {
            Log::info('Generating productivity estimate', [
                'application_id' => $application->id,
                'job_id' => $job?->id,
            ]);

            $skillsReadiness = $this->assessSkillsReadiness($application);
            $experienceLevel = $this->assessExperienceLevel($application);
            $domainKnowledge = $this->assessDomainKnowledge($application);
            $learningSpeed = $this->estimateLearningSpeed($application);
            $onboardingComplexity = $this->assessOnboardingComplexity($job);

            $timeToBasic = $this->calculateTimeToBasicProductivity($skillsReadiness, $experienceLevel, $onboardingComplexity);
            $timeToFull = $this->calculateTimeToFullProductivity($timeToBasic, $domainKnowledge, $learningSpeed);
            $timeToHigh = $this->calculateTimeToHighProductivity($timeToFull, $learningSpeed);

            $timeline = $this->generateProductivityTimeline($timeToBasic, $timeToFull, $timeToHigh);
            $milestones = $this->mapProductivityMilestones($timeline);
            $learningCurve = $this->summarizeLearningCurve($skillsReadiness, $learningSpeed, $onboardingComplexity);
            $experienceGap = $this->summarizeExperienceGaps($experienceLevel, $application);
            $supportRequirements = $this->deriveSupportRequirements($learningCurve, $experienceGap);
            $aiInsights = $this->generateProductivityInsights($timeline, $supportRequirements);

            $estimatedWeeks = max(1, (int) round($timeToFull / 7));
            $category = $this->determineProductivityCategory($estimatedWeeks);

            $explanation = $this->buildDecisionExplanation(
                'productivity_estimate',
                [
                    'skills_readiness' => ['value' => $skillsReadiness, 'weight' => 0.25],
                    'experience_level' => ['value' => $experienceLevel, 'weight' => 0.20],
                    'domain_knowledge' => ['value' => $domainKnowledge, 'weight' => 0.20],
                    'learning_speed' => ['value' => $learningSpeed, 'weight' => 0.20],
                    'onboarding_complexity' => ['value' => $onboardingComplexity, 'weight' => 0.15],
                ],
                [
                    'skills_readiness' => round($skillsReadiness, 4),
                    'experience_level' => round($experienceLevel, 4),
                    'domain_knowledge' => round($domainKnowledge, 4),
                    'learning_speed' => round($learningSpeed, 4),
                    'onboarding_complexity' => round($onboardingComplexity, 4),
                ],
                (float) $estimatedWeeks
            );
            $this->persistDecisionTrace($application->id, 'productivity_estimate', $explanation);

            return [
                'estimated_weeks' => $estimatedWeeks,
                'productivity_category' => $category,
                'productivity_milestones' => $milestones,
                'learning_curve_factors' => $learningCurve,
                'experience_gap_analysis' => $experienceGap,
                'support_requirements' => $supportRequirements,
                'ai_insights' => $aiInsights,
                'recommendation' => $this->buildProductivityRecommendation($category, $supportRequirements),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function identifyFlightRisks(Application $application, array $options = []): array
    {
        $cacheKey = "predictive_flight_risk_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application) {
            Log::info('Generating flight risk assessment', [
                'application_id' => $application->id,
                'job_id' => $application->job_id,
            ]);

            $riskFactors = [];
            $riskScore = 0.0;

            $jobHopping = $this->detectJobHoppingPattern($application->user);
            if ($jobHopping['is_pattern'] ?? false) {
                $riskFactors['job_hopping'] = $jobHopping;
                $riskScore += 0.25;
            }

            $shortTenure = $this->detectShortTenureHistory($application->user);
            if (($shortTenure['average_months'] ?? 36) < 24) {
                $riskFactors['short_tenure'] = $shortTenure;
                $riskScore += 0.20;
            }

            $overqualification = $this->assessOverqualification($application);
            if (($overqualification['score'] ?? 0) > 0.7) {
                $riskFactors['overqualification'] = $overqualification;
                $riskScore += 0.15;
            }

            $growthLimitations = $this->assessGrowthLimitations($application);
            if (($growthLimitations['score'] ?? 0) > 0.6) {
                $riskFactors['limited_growth'] = $growthLimitations;
                $riskScore += 0.20;
            }

            $compensationRisk = $this->assessCompensationRisk($application);
            if ($compensationRisk['below_market'] ?? false) {
                $riskFactors['compensation'] = $compensationRisk;
                $riskScore += 0.15;
            }

            $culturalRisk = $this->assessCulturalFitRisk($application);
            if (($culturalRisk['score'] ?? 1) < 0.6) {
                $riskFactors['cultural_fit'] = $culturalRisk;
                $riskScore += 0.05;
            }

            $riskScore = min(max($riskScore, 0), 1);
            $riskLevel = $this->determineRiskLevel($riskScore);
            $riskCategory = $this->mapRiskLevelToCategory($riskLevel);
            $mitigationStrategies = $this->generateRetentionStrategies($riskFactors, $riskLevel);
            $aiInsights = $this->generateFlightRiskInsights($riskScore, $riskFactors);

            $flightRiskWeights = [
                'job_hopping' => 0.25,
                'short_tenure' => 0.20,
                'overqualification' => 0.15,
                'limited_growth' => 0.20,
                'compensation' => 0.15,
                'cultural_fit' => 0.05,
            ];
            $flightFactors = [];
            $flightRawScores = [];
            foreach ($flightRiskWeights as $factorKey => $weight) {
                $factorValue = isset($riskFactors[$factorKey]) ? 1.0 : 0.0;
                $flightFactors[$factorKey] = ['value' => $factorValue, 'weight' => $weight];
                $flightRawScores[$factorKey] = round($factorValue, 4);
            }

            $explanation = $this->buildDecisionExplanation(
                'flight_risk',
                $flightFactors,
                $flightRawScores,
                $riskScore
            );
            $this->persistDecisionTrace($application->id, 'flight_risk', $explanation);

            return [
                'risk_score' => round($riskScore, 4),
                'risk_level' => $riskLevel,
                'risk_category' => $riskCategory,
                'risk_factors' => $riskFactors,
                'mitigation_strategies' => $mitigationStrategies,
                'ai_insights' => $aiInsights,
                'recommendation' => $this->buildFlightRiskRecommendation($riskLevel, $mitigationStrategies),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function predictDevelopmentNeeds(Application $application, Job $job = null, array $options = []): array
    {
        $job = $job ?? $application->job;
        $cacheKey = "predictive_development_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application, $job) {
            Log::info('Generating development needs plan', [
                'application_id' => $application->id,
                'job_id' => $job?->id,
            ]);

            $skillGaps = $this->identifySkillGaps($application);
            $knowledgeGaps = $this->identifyKnowledgeGaps($application);
            $behavioralNeeds = $this->identifyBehavioralNeeds($application);
            $leadershipNeeds = $this->assessLeadershipDevelopmentNeeds($application);
            $trainingRecommendations = $this->generateTrainingRecommendations($skillGaps, $knowledgeGaps, $behavioralNeeds);
            $developmentTimeline = $this->buildDevelopmentTimeline($skillGaps, $knowledgeGaps, $behavioralNeeds, $leadershipNeeds);
            $resourceRequirements = $this->buildResourceRequirements($trainingRecommendations, $developmentTimeline);
            $successMetrics = $this->buildDevelopmentSuccessMetrics($developmentTimeline);

            $this->persistDevelopmentNeeds($application, $skillGaps, $knowledgeGaps, $behavioralNeeds, $leadershipNeeds);

            $totalGaps = count($skillGaps) + count($knowledgeGaps) + count($behavioralNeeds) + count($leadershipNeeds);
            $developmentScore = $totalGaps > 0 ? min(1.0, (float) $totalGaps / 10.0) : 0.0;
            $explanation = $this->buildDecisionExplanation(
                'development_plan',
                [
                    'skill_gaps' => ['value' => (float) count($skillGaps), 'weight' => 0.35],
                    'knowledge_gaps' => ['value' => (float) count($knowledgeGaps), 'weight' => 0.25],
                    'behavioral_needs' => ['value' => (float) count($behavioralNeeds), 'weight' => 0.25],
                    'leadership_needs' => ['value' => (float) count($leadershipNeeds), 'weight' => 0.15],
                ],
                [
                    'skill_gaps' => round((float) count($skillGaps), 4),
                    'knowledge_gaps' => round((float) count($knowledgeGaps), 4),
                    'behavioral_needs' => round((float) count($behavioralNeeds), 4),
                    'leadership_needs' => round((float) count($leadershipNeeds), 4),
                ],
                $developmentScore
            );
            $this->persistDecisionTrace($application->id, 'development_plan', $explanation);

            return [
                'skill_gaps' => $skillGaps,
                'knowledge_gaps' => $knowledgeGaps,
                'behavioral_needs' => $behavioralNeeds,
                'leadership_needs' => $leadershipNeeds,
                'training_recommendations' => $trainingRecommendations,
                'development_timeline' => $developmentTimeline,
                'resource_requirements' => $resourceRequirements,
                'success_metrics' => $successMetrics,
                'recommendation' => $this->buildDevelopmentRecommendation($developmentTimeline, $trainingRecommendations),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function generateOnboardingPlan(Application $application, Job $job = null, array $options = []): array
    {
        $job = $job ?? $application->job;
        $cacheKey = "predictive_onboarding_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application, $job) {
            Log::info('Generating onboarding plan', [
                'application_id' => $application->id,
                'job_id' => $job?->id,
            ]);

            $planPhases = $this->generatePlanPhases($application, $job);
            $keyMilestones = $this->buildOnboardingMilestones();
            $resourceAssignments = $this->assignResourcesToPlan($application);
            $successCheckpoints = $this->buildSuccessCheckpoints();
            $durationDays = $this->calculatePlanDuration();

            $explanation = $this->buildDecisionExplanation(
                'onboarding_plan',
                [
                    'plan_phases' => ['value' => (float) count($planPhases), 'weight' => 0.30],
                    'key_milestones' => ['value' => (float) count($keyMilestones), 'weight' => 0.25],
                    'resource_assignments' => ['value' => (float) count($resourceAssignments), 'weight' => 0.20],
                    'success_checkpoints' => ['value' => (float) count($successCheckpoints), 'weight' => 0.15],
                    'plan_duration_days' => ['value' => (float) $durationDays, 'weight' => 0.10],
                ],
                [
                    'plan_phases' => round((float) count($planPhases), 4),
                    'key_milestones' => round((float) count($keyMilestones), 4),
                    'resource_assignments' => round((float) count($resourceAssignments), 4),
                    'success_checkpoints' => round((float) count($successCheckpoints), 4),
                    'plan_duration_days' => round((float) $durationDays, 4),
                ],
                (float) $durationDays
            );
            $this->persistDecisionTrace($application->id, 'onboarding_plan', $explanation);

            return [
                'plan_phases' => $planPhases,
                'key_milestones' => $keyMilestones,
                'resource_assignments' => $resourceAssignments,
                'success_checkpoints' => $successCheckpoints,
                'plan_duration_days' => $durationDays,
                'recommendation' => $this->buildOnboardingRecommendation($planPhases),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function predictCareerPath(Application $application, User $user = null, array $options = []): array
    {
        $user = $user ?? $application->user;
        $cacheKey = "predictive_career_path_{$application->id}";
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addSeconds(self::DEFAULT_CACHE_TTL), function () use ($application, $user) {
            Log::info('Generating career path prediction', [
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]);

            $potential = $this->analyzeCareerPotential($application);
            $progressionPaths = $this->identifyProgressionPaths($application, $potential);
            $pathTimelines = $this->predictPathTimelines($progressionPaths);
            $developmentRequirements = $this->assessPathRequirements($progressionPaths);
            $successionInsights = $this->generateSuccessionInsights($progressionPaths);
            $recommendedPath = $this->selectRecommendedPath($progressionPaths);

            $predictedRoles = array_map(function (array $path) {
                return [
                    'role' => $path['target_role'],
                    'probability' => round(($path['probability'] ?? 0) * 100, 1),
                    'timeline_months' => $path['timeline_months'] ?? null,
                    'path_type' => $path['path_type'] ?? 'vertical',
                    'milestones' => $path['milestones'] ?? [],
                    'required_skills' => $path['required_skills'] ?? [],
                ];
            }, $progressionPaths);

            if (!empty($progressionPaths)) {
                $this->persistCareerPathPredictions($application, $progressionPaths);
            }

            $careerPotentialScore = (float) ($potential['score'] ?? 0);
            $explanation = $this->buildDecisionExplanation(
                'career_path',
                [
                    'performance_signals' => ['value' => (float) ($application->user->profile['performance_trend_score'] ?? 0.78), 'weight' => 0.40],
                    'aspiration_alignment' => ['value' => (float) ($application->user->profile['career_aspiration_alignment'] ?? 0.8), 'weight' => 0.30],
                    'learning_agility' => ['value' => $careerPotentialScore, 'weight' => 0.30],
                ],
                [
                    'performance_signals' => round((float) ($application->user->profile['performance_trend_score'] ?? 0.78), 4),
                    'aspiration_alignment' => round((float) ($application->user->profile['career_aspiration_alignment'] ?? 0.8), 4),
                    'learning_agility' => round($careerPotentialScore, 4),
                ],
                $careerPotentialScore
            );
            $this->persistDecisionTrace($application->id, 'career_path', $explanation);

            return [
                'career_potential_score' => round(($potential['score'] ?? 0) * 100, 1),
                'potential_category' => $potential['category'] ?? 'emerging',
                'predicted_roles' => $predictedRoles,
                'career_trajectory' => $this->determineCareerTrajectory($progressionPaths),
                'succession_potential' => round($this->computeSuccessionPotential($successionInsights), 1),
                'succession_potential_label' => $successionInsights['summary'] ?? 'Potential not assessed',
                'development_requirements' => $developmentRequirements,
                'career_trajectory_map' => $pathTimelines,
                'recommended_primary_path' => $recommendedPath,
                'estimated_timeline_months' => $recommendedPath['timeline_months'] ?? null,
                'recommendation' => $this->buildCareerPathRecommendation($recommendedPath, $developmentRequirements),
                'explanation_json' => $explanation,
            ];
        });
    }

    public function generatePredictiveReport(Application $application, array $options = []): array
    {
        $forceRefresh = (bool) ($options['force_refresh'] ?? false);

        $success = $this->predictSuccessProbability($application, ['force_refresh' => $forceRefresh]);
        $tenure = $this->forecastTenure($application, ['force_refresh' => $forceRefresh]);
        $productivity = $this->estimateTimeToProductivity($application, $application->job, ['force_refresh' => $forceRefresh]);
        $flightRisk = $this->identifyFlightRisks($application, ['force_refresh' => $forceRefresh]);
        $development = $this->predictDevelopmentNeeds($application, $application->job, ['force_refresh' => $forceRefresh]);
        $onboarding = $this->generateOnboardingPlan($application, $application->job, ['force_refresh' => $forceRefresh]);
        $career = $this->predictCareerPath($application, $application->user, ['force_refresh' => $forceRefresh]);

        $actionItems = $this->compileActionItems($success, $tenure, $productivity, $flightRisk, $development, $onboarding, $career);
        $priorityRecommendations = $this->prioritizeRecommendations($success, $tenure, $productivity, $flightRisk, $development, $career);
        $visualizations = $this->buildVisualizationPayload($success, $tenure, $productivity, $flightRisk, $career);
        $reportBody = $this->composeComprehensiveReport($application, $success, $tenure, $productivity, $flightRisk, $development, $onboarding, $career, $actionItems);

        return [
            'comprehensive_report' => $reportBody,
            'visualizations_data' => $visualizations,
            'action_items' => $actionItems,
            'recommendations_priority' => $priorityRecommendations,
        ];
    }

    private function analyzeSkillsMatch(Application $application): float
    {
        $profile = $application->user->profile ?? [];
        $jobRequirements = $application->job->requirements ?? [];

        $candidateSkills = Arr::wrap($profile['skills'] ?? []);
        $requiredSkills = Arr::wrap($jobRequirements['skills'] ?? []);

        if (empty($requiredSkills)) {
            return 0.8;
        }

        $matchCount = 0;
        foreach ($requiredSkills as $required) {
            $requiredName = is_array($required) ? ($required['name'] ?? '') : $required;
            foreach ($candidateSkills as $skill) {
                $candidateName = is_array($skill) ? ($skill['name'] ?? '') : $skill;
                if ($requiredName && Str::contains(Str::lower($candidateName), Str::lower($requiredName))) {
                    $matchCount++;
                    break;
                }
            }
        }

        return min(max($matchCount / max(count($requiredSkills), 1), 0.1), 1.0);
    }

    private function analyzeExperienceRelevance(Application $application): float
    {
        $profile = $application->user->profile ?? [];
        $experience = Arr::wrap($profile['experience'] ?? []);
        $requiredYears = (float) ($application->job->requirements['min_experience_years'] ?? 0);

        $relevantExperienceMonths = 0;
        foreach ($experience as $role) {
            $months = $this->calculateDurationInMonths($role['start_date'] ?? null, $role['end_date'] ?? null);
            if ($this->isRelevantExperience($role, $application->job)) {
                $relevantExperienceMonths += $months;
            }
        }

        $relevantYears = $relevantExperienceMonths / 12;
        if ($requiredYears <= 0) {
            return min(max($relevantYears / 5, 0), 1);
        }

        return min(max($relevantYears / $requiredYears, 0), 1);
    }

    private function analyzeCulturalFit(Application $application): float
    {
        $companyProfile = $application->job->company->cultural_profile ?? [];
        $candidateValues = Arr::wrap($application->user->profile['values'] ?? []);

        if (empty($companyProfile) || empty($candidateValues)) {
            return 0.75;
        }

        $overlap = count(array_intersect($candidateValues, $companyProfile['core_values'] ?? []));
        $total = max(count($companyProfile['core_values'] ?? []), 1);

        return min(max($overlap / $total, 0.5), 1.0);
    }

    private function analyzeTeamCompatibility(Application $application): float
    {
        $teamCulture = $application->job->team_culture ?? [];
        $candidateWorkStyle = Arr::wrap($application->user->profile['work_style'] ?? []);

        if (empty($teamCulture) || empty($candidateWorkStyle)) {
            return 0.8;
        }

        $overlap = count(array_intersect($candidateWorkStyle, Arr::wrap($teamCulture['preferred_styles'] ?? [])));
        $total = max(count($candidateWorkStyle), 1);

        return min(max($overlap / $total, 0.6), 1.0);
    }

    private function analyzeBehavioralAlignment(Application $application): float
    {
        $behavioralScores = Arr::wrap($application->user->profile['behavioral_scores'] ?? []);
        if (empty($behavioralScores)) {
            return 0.78;
        }

        $alignment = array_sum($behavioralScores) / max(count($behavioralScores), 1);
        return min(max($alignment, 0.4), 1.0);
    }

    private function assessLearningAgility(Application $application): float
    {
        $education = Arr::wrap($application->user->profile['education'] ?? []);
        $certifications = Arr::wrap($application->user->profile['certifications'] ?? []);

        $score = count($education) * 0.18 + count($certifications) * 0.12;
        $score += ($application->user->profile['continuous_learning_score'] ?? 0.4) * 0.3;

        return min(max($score, 0.5), 1.0);
    }

    private function analyzePastPerformanceIndicators(Application $application): float
    {
        $achievements = Arr::wrap($application->user->profile['achievements'] ?? []);
        $promotions = Arr::wrap($application->user->profile['promotions'] ?? []);

        $score = 0.6 + min(count($achievements), 5) * 0.05 + min(count($promotions), 3) * 0.07;

        return min($score, 1.0);
    }

    private function generateSuccessInsights(Application $application, float $probability, array $factors): array
    {
        try {
            $prompt = "You are an expert talent analyst. Provide 3 concise insights (bullet list) about this candidate's likelihood of success. Include rationale referencing the scores provided.";
            $message = "Candidate Success Summary:\n" .
                "Success Probability: " . round($probability * 100, 1) . "%\n" .
                "Skills Match: " . round(($factors['skills_match'] ?? 0) * 100, 1) . "%\n" .
                "Experience Relevance: " . round(($factors['experience_relevance'] ?? 0) * 100, 1) . "%\n" .
                "Cultural Fit: " . round(($factors['cultural_fit'] ?? 0) * 100, 1) . "%\n" .
                "Team Compatibility: " . round(($factors['team_compatibility'] ?? 0) * 100, 1) . "%";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 350,
                'temperature' => 0.4,
            ]);

            return [
                'summary' => trim($response->choices[0]->message->content ?? ''),
                'generated_at' => Carbon::now()->toIso8601String(),
            ];
        } catch (Exception $exception) {
            Log::warning('OpenAI success insights generation failed', [
                'application_id' => $application->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'summary' => 'AI insights currently unavailable. Review factor scores and recommendations for guidance.',
                'generated_at' => Carbon::now()->toIso8601String(),
            ];
        }
    }

    private function identifyStrengths(array $scores): array
    {
        $strengths = [];
        if (($scores['skills_match'] ?? 0) >= 0.8) {
            $strengths[] = 'Skill alignment exceeds 80%; candidate can contribute immediately to core deliverables.';
        }
        if (($scores['experience_relevance'] ?? 0) >= 0.75) {
            $strengths[] = 'Experience closely mirrors the role requirements, reducing ramp-up time.';
        }
        if (($scores['cultural_fit'] ?? 0) >= 0.75) {
            $strengths[] = 'Shared values and cultural alignment indicate strong engagement potential.';
        }

        return $strengths;
    }

    private function identifyConcerns(array $scores): array
    {
        $concerns = [];
        if (($scores['skills_match'] ?? 1) < 0.6) {
            $concerns[] = 'Core technical competencies show notable gaps; plan skill augmentation within first 60 days.';
        }
        if (($scores['experience_relevance'] ?? 1) < 0.55) {
            $concerns[] = 'Past roles differ from current requirements; provide structured shadowing for domain acclimation.';
        }
        if (($scores['cultural_fit'] ?? 1) < 0.55) {
            $concerns[] = 'Value alignment is limited; reinforce company values during onboarding touchpoints.';
        }

        return $concerns;
    }

    private function calculateConfidenceScore(Application $application): float
    {
        $profile = $application->user->profile ?? [];
        $dataCompleteness = 0.4 + (empty($profile) ? 0 : 0.2);
        $experienceBreadth = min(count($profile['experience'] ?? []), 5) * 0.06;
        $assessmentSignals = ($profile['assessment_quality_score'] ?? 0.6) * 0.2;

        return min(max($dataCompleteness + $experienceBreadth + $assessmentSignals, 0.4), 0.95);
    }

    private function getPredictionBasis(): string
    {
        return 'Composite analysis of skills, historical performance, cultural alignment, behavioral signals, and learning agility.';
    }

    private function categorizeSuccessProbability(float $probability): string
    {
        return match (true) {
            $probability >= 0.85 => 'very_high',
            $probability >= 0.7 => 'high',
            $probability >= 0.55 => 'moderate',
            $probability >= 0.4 => 'low',
            default => 'very_low',
        };
    }

    private function generateRecommendation(float $probability, array $concerns): string
    {
        $hasHighConcerns = count($concerns) >= 2;
        return match (true) {
            $probability >= 0.85 && !$hasHighConcerns => 'Strongly recommend hiring; align with stretch opportunities to maintain engagement.',
            $probability >= 0.7 => 'Proceed with offer; create a targeted onboarding plan for the identified development areas.',
            $probability >= 0.55 => 'Consider conditional offer with structured development plan and early performance checkpoints.',
            $probability >= 0.4 => 'Hold for further assessment; mitigate highlighted concerns before proceeding.',
            default => 'Do not advance at this time; risk profile outweighs current strengths.',
        };
    }

    private function getComparableProfiles(Application $application): array
    {
        $matches = SuccessPrediction::query()
            ->where('job_id', $application->job_id)
            ->where('company_id', $application->job->company_id)
            ->where('user_id', '!=', $application->user_id)
            ->latest('predicted_at')
            ->limit(3)
            ->get();

        if ($matches->isEmpty()) {
            return [[
                'role' => $application->job->title,
                'department' => $application->job->department ?? 'Unknown department',
                'tenure' => '2.4 years avg',
                'success_score' => 78,
                'summary' => 'Historical hires with similar profiles reached full productivity within 9 weeks.',
            ]];
        }

        return $matches->map(function (SuccessPrediction $prediction) {
            $job = $prediction->job;
            $companyTenure = $job?->company?->average_tenure_months ? round($job->company->average_tenure_months / 12, 1) . ' years' : 'N/A';

            return [
                'role' => $job->title ?? 'Comparable Role',
                'department' => $job->department ?? $job->team_name ?? 'General',
                'tenure' => $companyTenure,
                'success_score' => round(($prediction->success_probability ?? 0) * 100, 1),
                'summary' => $prediction->recommendation ?? 'Comparable hire exhibited similar strengths and retained beyond 24 months.',
            ];
        })->all();
    }

    private function analyzeTenureHistory(User $user): array
    {
        $experience = Arr::wrap($user->profile['experience'] ?? []);
        $tenures = [];

        foreach ($experience as $role) {
            $tenures[] = $this->calculateDurationInMonths($role['start_date'] ?? null, $role['end_date'] ?? null);
        }

        $tenures = array_filter($tenures);
        if (empty($tenures)) {
            return [
                'average_months' => 36,
                'min_months' => 18,
                'max_months' => 60,
                'tenure_count' => 1,
            ];
        }

        return [
            'average_months' => round(array_sum($tenures) / count($tenures), 1),
            'min_months' => min($tenures),
            'max_months' => max($tenures),
            'tenure_count' => count($tenures),
        ];
    }

    private function assessRoleStability(Application $application): float
    {
        $teamAttrition = $application->job->team_attrition_rate ?? 0.12;
        return max(0.4, 1 - $teamAttrition);
    }

    private function evaluateGrowthOpportunities(Application $application): float
    {
        $internalMobility = $application->job->company->mobility_index ?? 0.6;
        $mentorAvailability = $application->job->team_support_index ?? 0.7;

        return min(max(($internalMobility * 0.6) + ($mentorAvailability * 0.4), 0.3), 1.0);
    }

    private function assessMarketAlignment(Application $application): float
    {
        $compBenchmark = $application->job->compensation_benchmark ?? 0.75;
        $locationStability = $application->user->profile['location_stability_score'] ?? 0.8;

        return min(max(($compBenchmark * 0.7) + ($locationStability * 0.3), 0.3), 1.0);
    }

    private function calculateExpectedTenure(array $history, float $stability, float $growth, float $market): float
    {
        $base = $history['average_months'] ?? 36;
        $adjustment = 1 + (($stability - 0.5) * 0.4) + (($growth - 0.5) * 0.35) + (($market - 0.5) * 0.25);

        return round(min(max($base * $adjustment, 12), 120));
    }

    private function calculateTenureRange(float $expected): array
    {
        return [
            'min' => max(6, (int) round($expected * 0.7)),
            'max' => min(144, (int) round($expected * 1.3)),
        ];
    }

    private function classifyPlayerType(float $tenure): string
    {
        return match (true) {
            $tenure >= 48 => 'long_term_player',
            $tenure >= 24 => 'stable_player',
            $tenure >= 12 => 'moderate_risk',
            default => 'flight_risk',
        };
    }

    private function calculateTenureConfidence(array $history): float
    {
        $tenureCount = $history['tenure_count'] ?? 0;
        return $tenureCount >= 4 ? 0.9 : ($tenureCount >= 2 ? 0.75 : 0.6);
    }

    private function generateTenureInsights(float $expectedTenure, array $history, float $stability, float $growth): array
    {
        try {
            $prompt = "Provide two succinct insights on tenure expectations and retention levers based on the provided data.";
            $message = "Expected Tenure: " . round($expectedTenure / 12, 1) . " years.\n" .
                "Average Historical Tenure: " . round(($history['average_months'] ?? 36) / 12, 1) . " years.\n" .
                "Role Stability Score: " . round($stability * 100, 0) . "%\n" .
                "Growth Opportunities Score: " . round($growth * 100, 0) . "%";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 280,
                'temperature' => 0.4,
            ]);

            return [
                'summary' => trim($response->choices[0]->message->content ?? ''),
                'generated_at' => Carbon::now()->toIso8601String(),
            ];
        } catch (Exception $exception) {
            Log::warning('OpenAI tenure insights generation failed', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'summary' => 'AI tenure insights unavailable; rely on retention factors and risk indicators listed.',
                'generated_at' => Carbon::now()->toIso8601String(),
            ];
        }
    }

    private function getPlayerTypeDisplay(string $type): string
    {
        return match ($type) {
            'long_term_player' => 'Long-Term Player (4+ years)',
            'stable_player' => 'Stable Contributor (2-4 years)',
            'moderate_risk' => 'Moderate Risk (12-24 months)',
            'flight_risk' => 'High Risk (<12 months)',
            default => 'Unknown',
        };
    }

    private function mapConfidenceToLevel(float $score): string
    {
        return match (true) {
            $score >= 0.85 => 'very_high',
            $score >= 0.7 => 'high',
            $score >= 0.55 => 'moderate',
            default => 'low',
        };
    }

    private function formatConfidenceLabel(string $level): string
    {
        return match ($level) {
            'very_high' => 'Very High Confidence',
            'high' => 'High Confidence',
            'moderate' => 'Moderate Confidence',
            'low' => 'Low Confidence',
            default => 'Unknown Confidence',
        };
    }

    private function deriveFlightRiskScore(float $expectedTenure, array $history, float $growthOpportunities): float
    {
        $baseline = $expectedTenure < 18 ? 0.75 : ($expectedTenure < 30 ? 0.55 : 0.35);
        $historicalVariance = max(0, 1 - ($history['average_months'] ?? 36) / max($history['max_months'] ?? 36, 1));
        $growthModifier = 1 - $growthOpportunities;

        $score = ($baseline * 0.6) + ($historicalVariance * 0.2) + ($growthModifier * 0.2);
        return min(max($score, 0), 1);
    }

    private function mapRiskScoreToCategory(float $score): string
    {
        return match (true) {
            $score >= 0.8 => 'critical',
            $score >= 0.6 => 'high',
            $score >= 0.4 => 'medium',
            $score >= 0.2 => 'low',
            default => 'very_low',
        };
    }

    private function buildRetentionFactorsSummary(Application $application, array $tenureHistory, float $growthOpportunities): array
    {
        return [
            [
                'name' => 'Career Mobility',
                'description' => 'Internal mobility programs aligned to aspirational paths.',
                'score' => round($growthOpportunities * 100, 1),
            ],
            [
                'name' => 'Historical Tenure Stability',
                'description' => 'Average past tenure of ' . round(($tenureHistory['average_months'] ?? 36) / 12, 1) . ' years.',
                'score' => round(min(($tenureHistory['average_months'] ?? 36) / 60, 1) * 100, 1),
            ],
            [
                'name' => 'Team Engagement',
                'description' => 'Existing team engagement index supports long-term retention.',
                'score' => round(($application->job->team_engagement_index ?? 0.72) * 100, 1),
            ],
        ];
    }

    private function buildRiskIndicatorsSummary(Application $application, float $flightRiskScore): array
    {
        $indicators = [];

        if ($flightRiskScore >= 0.6) {
            $indicators[] = [
                'name' => 'Elevated Flight Risk',
                'severity' => 9,
                'description' => 'Projection indicates potential attrition within the first 12 months without intervention.',
            ];
        }

        if (($application->job->critical_skills_gap ?? false)) {
            $indicators[] = [
                'name' => 'Limited Skill Redundancy',
                'severity' => 7,
                'description' => 'Losing this candidate would re-open a critical skills gap for the team.',
            ];
        }

        if (($application->user->profile['compensation_expectation_delta'] ?? 0) > 0.1) {
            $indicators[] = [
                'name' => 'Compensation Expectation Gap',
                'severity' => 6,
                'description' => 'Candidate expects >10% above current offer; adjust or set incentives.',
            ];
        }

        return $indicators;
    }

    private function buildTenureRecommendation(string $riskCategory, array $retentionFactors, array $riskIndicators): string
    {
        if (in_array($riskCategory, ['critical', 'high'], true)) {
            return 'Implement retention plan within first 45 days: align growth roadmap, assign mentor, and review compensation anchors.';
        }

        if ($riskCategory === 'medium') {
            return 'Monitor engagement monthly; reinforce progression milestones and track sentiment during lead touchpoints.';
        }

        return 'Tenure outlook is healthy; maintain standard engagement cadence and refresh milestones quarterly.';
    }

    private function buildTenureProbabilityCurve(int $expected, array $range): array
    {
        $min = max(6, $range['min']);
        $max = min(144, $range['max']);
        $points = [];
        $steps = 5;
        $interval = max(intval(($max - $min) / $steps), 6);

        for ($i = 0; $i <= $steps; $i++) {
            $month = $min + ($interval * $i);
            $month = min($month, $max);
            $density = exp(-pow(($month - $expected) / max($expected * 0.35, 6), 2));
            $points[] = round($density * 100, 1);
        }

        return $points;
    }

    private function assessSkillsReadiness(Application $application): float
    {
        return min(max(($application->user->profile['skills_readiness_score'] ?? 0.78), 0.4), 1.0);
    }

    private function assessExperienceLevel(Application $application): float
    {
        return min(max(($application->user->profile['experience_level_score'] ?? 0.74), 0.3), 1.0);
    }

    private function assessDomainKnowledge(Application $application): float
    {
        return min(max(($application->user->profile['domain_knowledge_score'] ?? 0.7), 0.25), 1.0);
    }

    private function estimateLearningSpeed(Application $application): float
    {
        return min(max(($application->user->profile['learning_agility_score'] ?? 0.82), 0.4), 1.0);
    }

    private function assessOnboardingComplexity(?Job $job): float
    {
        return min(max(($job->onboarding_complexity_index ?? 0.6), 0.2), 1.0);
    }

    private function calculateTimeToBasicProductivity(float $skills, float $experience, float $complexity): int
    {
        $base = 28;
        $modifier = (1 - $skills) * 12 + (1 - $experience) * 10 + $complexity * 8;
        return (int) round(max($base + $modifier, 14));
    }

    private function calculateTimeToFullProductivity(int $basicDays, float $domainKnowledge, float $learningSpeed): int
    {
        $additional = (1 - $domainKnowledge) * 20 + (1 - $learningSpeed) * 18;
        return (int) round(max($basicDays + $additional, $basicDays + 14));
    }

    private function calculateTimeToHighProductivity(int $fullDays, float $learningSpeed): int
    {
        $additional = max(0, (1 - $learningSpeed) * 25);
        return (int) round($fullDays + $additional + 20);
    }

    private function generateProductivityTimeline(int $basic, int $full, int $high): array
    {
        return [
            ['milestone' => 'Foundational Productivity', 'days' => $basic, 'target' => 45],
            ['milestone' => 'Full Ownership', 'days' => $full, 'target' => 75],
            ['milestone' => 'High Impact Contributor', 'days' => $high, 'target' => 95],
        ];
    }

    private function mapProductivityMilestones(array $timeline): array
    {
        $labels = ['Week 2-4', 'Week 6-8', 'Week 10-14'];

        return array_map(function ($item, $index) use ($labels) {
            return [
                'label' => $labels[$index] ?? $item['milestone'],
                'milestone' => $item['milestone'],
                'target' => $item['target'],
                'description' => match ($index) {
                    0 => 'Completes scoped tasks with guidance and adheres to delivery rituals.',
                    1 => 'Owns backlog items end-to-end and coordinates with cross-functional partners.',
                    default => 'Influences roadmap decisions and drives independent optimizations.',
                },
            ];
        }, $timeline, array_keys($timeline));
    }

    private function summarizeLearningCurve(float $skillsReadiness, float $learningSpeed, float $complexity): array
    {
        return [
            'complexity' => $complexity > 0.7 ? 'high' : ($complexity > 0.45 ? 'medium' : 'low'),
            'learning_agility' => $learningSpeed >= 0.8 ? 'high' : ($learningSpeed >= 0.6 ? 'medium' : 'low'),
            'baseline_skills' => $skillsReadiness >= 0.75 ? 'strong' : ($skillsReadiness >= 0.55 ? 'moderate' : 'limited'),
            'notes' => 'Learning plan should emphasize practical exposure and mentor pairing for complex modules.',
        ];
    }

    private function summarizeExperienceGaps(float $experienceLevel, Application $application): array
    {
        $criticalGaps = Arr::wrap($application->user->profile['critical_skill_gaps'] ?? []);
        $gapLevel = $experienceLevel >= 0.75 ? 'low' : ($experienceLevel >= 0.55 ? 'medium' : 'high');

        return [
            'gap_level' => $gapLevel,
            'critical_gaps' => array_slice($criticalGaps, 0, 5),
            'supporting_evidence' => 'Derived from experience inventory comparison against job competency matrix.',
        ];
    }

    private function deriveSupportRequirements(array $learningCurve, array $experienceGap): array
    {
        $level = 'standard';
        if (($learningCurve['complexity'] ?? '') === 'high' || ($experienceGap['gap_level'] ?? '') === 'high') {
            $level = 'high';
        } elseif (($learningCurve['complexity'] ?? '') === 'medium' || ($experienceGap['gap_level'] ?? '') === 'medium') {
            $level = 'medium';
        }

        $types = [
            'Functional mentor (weekly sync)',
            '30/60/90 alignment reviews',
        ];

        if ($level === 'high') {
            $types[] = 'Pair programming rotation';
            $types[] = 'Dedicated technical training budget';
        }

        return [
            'level' => $level,
            'types' => $types,
            'notes' => 'Align enablement resources prior to start date to compress time-to-productivity.',
        ];
    }

    private function generateProductivityInsights(array $timeline, array $supportRequirements): array
    {
        return [
            'summary' => sprintf(
                'Estimated full productivity in %d weeks. Ensure %s support and track milestone adherence bi-weekly.',
                max(1, (int) round(($timeline[1]['days'] ?? 60) / 7)),
                strtoupper($supportRequirements['level'] ?? 'standard')
            ),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function determineProductivityCategory(int $weeks): string
    {
        return match (true) {
            $weeks <= 6 => 'quick_ramp',
            $weeks <= 10 => 'average_ramp',
            $weeks <= 14 => 'slow_ramp',
            default => 'extended_ramp',
        };
    }

    private function buildProductivityRecommendation(string $category, array $supportRequirements): string
    {
        return match ($category) {
            'quick_ramp' => 'Leverage momentum by assigning early stretch deliverables and providing strategic mentorship.',
            'average_ramp' => 'Maintain steady enablement cadence and confirm milestone completion during weekly check-ins.',
            'slow_ramp' => 'Front-load training, increase mentorship touchpoints, and review blockers every 10 days.',
            default => 'Deploy intensive onboarding program with executive sponsor oversight and formal progress checkpoints.',
        };
    }

    private function detectJobHoppingPattern(User $user): array
    {
        $experience = Arr::wrap($user->profile['experience'] ?? []);
        $recentTenures = [];
        foreach (array_slice($experience, 0, 4) as $role) {
            $recentTenures[] = $this->calculateDurationInMonths($role['start_date'] ?? null, $role['end_date'] ?? null);
        }
        $recentTenures = array_filter($recentTenures);
        $average = empty($recentTenures) ? 36 : array_sum($recentTenures) / count($recentTenures);

        return [
            'is_pattern' => $average < 20,
            'avg_tenure_months' => round($average, 1),
            'roles_evaluated' => count($recentTenures),
        ];
    }

    private function detectShortTenureHistory(User $user): array
    {
        $history = $this->analyzeTenureHistory($user);
        return [
            'average_months' => $history['average_months'] ?? 36,
            'is_concerning' => ($history['average_months'] ?? 36) < 24,
        ];
    }

    private function assessOverqualification(Application $application): array
    {
        $jobLevel = $application->job->level ?? 'mid';
        $candidateLevel = $application->user->profile['seniority_level'] ?? 'mid';

        $score = match ([$candidateLevel, $jobLevel]) {
            ['senior', 'junior'], ['lead', 'mid'], ['lead', 'junior'], ['director', 'mid'] => 0.85,
            ['senior', 'mid'], ['principal', 'senior'] => 0.7,
            default => 0.3,
        };

        return [
            'score' => $score,
            'is_overqualified' => $score >= 0.7,
        ];
    }

    private function assessGrowthLimitations(Application $application): array
    {
        $teamStructure = $application->job->team_structure ?? 'flat';
        $advancementSlots = $application->job->open_progression_slots ?? 1;
        $score = $teamStructure === 'flat' ? 0.7 : 0.4;
        if ($advancementSlots >= 2) {
            $score -= 0.2;
        }

        return [
            'score' => min(max($score, 0), 1),
            'has_limitations' => $score > 0.6,
        ];
    }

    private function assessCompensationRisk(Application $application): array
    {
        $expected = $application->user->profile['salary_expectation'] ?? null;
        $offer = $application->job->compensation_offer ?? null;
        $belowMarket = false;
        $delta = 0;

        if ($expected && $offer) {
            $delta = ($expected - $offer) / max($offer, 1);
            $belowMarket = $delta > 0.07;
        }

        return [
            'below_market' => $belowMarket,
            'market_alignment' => $belowMarket ? 1 - min($delta, 0.3) : 0.9,
            'expectation_delta' => round($delta * 100, 1),
        ];
    }

    private function assessCulturalFitRisk(Application $application): array
    {
        $fitScore = $this->analyzeCulturalFit($application);
        return [
            'score' => $fitScore,
            'is_risky' => $fitScore < 0.55,
        ];
    }

    private function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 0.8 => 'critical',
            $score >= 0.6 => 'high',
            $score >= 0.4 => 'medium',
            $score >= 0.2 => 'low',
            default => 'very_low',
        };
    }

    private function mapRiskLevelToCategory(string $level): string
    {
        return match ($level) {
            'critical', 'high' => 'immediate_flight',
            'medium' => 'short_term_flight',
            'low' => 'long_term_risk',
            default => 'stable',
        };
    }

    private function generateRetentionStrategies(array $factors, string $level): array
    {
        $strategies = [];

        if (isset($factors['compensation'])) {
            $strategies[] = [
                'action' => 'Review total compensation and introduce performance-linked variable pay.',
                'priority' => 10,
                'owner' => 'Compensation lead',
                'timeline_days' => 21,
            ];
        }

        if (isset($factors['limited_growth'])) {
            $strategies[] = [
                'action' => 'Co-create a 12-month progression roadmap with defined skill checkpoints.',
                'priority' => 9,
                'owner' => 'Hiring manager',
                'timeline_days' => 30,
            ];
        }

        if (isset($factors['job_hopping'])) {
            $strategies[] = [
                'action' => 'Schedule bi-weekly engagement sessions during the first quarter.',
                'priority' => 8,
                'owner' => 'People partner',
                'timeline_days' => 7,
            ];
        }

        if (empty($strategies)) {
            $strategies[] = [
                'action' => 'Maintain monthly retention check-ins and reinforce growth narratives.',
                'priority' => 6,
                'owner' => 'Manager',
                'timeline_days' => 30,
            ];
        }

        return $strategies;
    }

    private function generateFlightRiskInsights(float $score, array $factors): array
    {
        return [
            'summary' => sprintf(
                'Overall flight risk scored at %s%%. Focus mitigation on %s to stabilize retention outlook.',
                round($score * 100, 1),
                Str::title(str_replace('_', ' ', array_key_first($factors) ?? 'engagement'))
            ),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    private function buildFlightRiskRecommendation(string $riskLevel, array $strategies): string
    {
        return match ($riskLevel) {
            'critical' => 'Initiate retention SWAT plan immediately; execute top 2 mitigation actions within 14 days.',
            'high' => 'Implement mitigation actions within 30 days and review progress weekly.',
            'medium' => 'Track engagement monthly and prioritize top mitigation strategy within first 45 days.',
            default => 'Maintain standard engagement cadence; revisit risk posture quarterly.',
        };
    }

    private function identifySkillGaps(Application $application): array
    {
        $jobCompetencies = Arr::wrap($application->job->competency_matrix ?? []);
        $candidateSkills = Arr::wrap($application->user->profile['skills'] ?? []);

        $gaps = [];
        foreach ($jobCompetencies as $competency) {
            $name = $competency['name'] ?? null;
            if (!$name) {
                continue;
            }

            $candidateScore = $this->findSkillScore($candidateSkills, $name);
            $requiredScore = $competency['required_score'] ?? 0.7;

            if ($candidateScore < $requiredScore) {
                $gaps[] = [
                    'category' => $competency['category'] ?? 'technical',
                    'description' => $name,
                    'priority' => $candidateScore < $requiredScore - 0.2 ? 'high' : 'medium',
                    'time_estimate' => $candidateScore < $requiredScore - 0.2 ? 60 : 30,
                    'actions' => $this->buildActionsForGap($name, $competency['category'] ?? 'technical'),
                    'current_score' => round($candidateScore, 2),
                    'target_score' => round($requiredScore, 2),
                ];
            }
        }

        return $gaps;
    }

    private function identifyKnowledgeGaps(Application $application): array
    {
        $requiredKnowledge = Arr::wrap($application->job->knowledge_domains ?? []);
        $candidateKnowledge = Arr::wrap($application->user->profile['knowledge_domains'] ?? []);

        $gaps = [];
        foreach ($requiredKnowledge as $domain) {
            if (!in_array($domain, $candidateKnowledge, true)) {
                $gaps[] = [
                    'domain' => $domain,
                    'priority' => 'medium',
                    'description' => 'Exposure required for role-critical decision-making.',
                ];
            }
        }

        return $gaps;
    }

    private function identifyBehavioralNeeds(Application $application): array
    {
        $desiredTraits = Arr::wrap($application->job->behavioral_competencies ?? []);
        $candidateTraits = Arr::wrap($application->user->profile['behavioral_profile'] ?? []);

        $needs = [];
        foreach ($desiredTraits as $trait) {
            if (!in_array($trait, $candidateTraits, true)) {
                $needs[] = [
                    'trait' => $trait,
                    'development_action' => 'Assign relevant scenario-based coaching during first quarter.',
                ];
            }
        }

        return $needs;
    }

    private function assessLeadershipDevelopmentNeeds(Application $application): array
    {
        $aspiration = $application->user->profile['leadership_aspiration'] ?? 'individual_contributor';
        if ($aspiration !== 'leadership_track') {
            return [];
        }

        return [
            [
                'focus' => 'Strategic Communication',
                'recommended_action' => 'Enroll in leadership storytelling workshop by month 3.',
            ],
            [
                'focus' => 'Coaching & Mentoring',
                'recommended_action' => 'Pair with emerging leaders program for peer mentoring practice.',
            ],
        ];
    }

    private function generateTrainingRecommendations(array $skillGaps, array $knowledgeGaps, array $behavioralNeeds): array
    {
        $recommendations = [];

        foreach ($skillGaps as $gap) {
            $recommendations[] = [
                'type' => 'technical',
                'title' => 'Deep dive: ' . $gap['description'],
                'duration' => $gap['time_estimate'] . ' hours',
                'provider' => 'Internal academy',
            ];
        }

        foreach ($knowledgeGaps as $gap) {
            $recommendations[] = [
                'type' => 'domain',
                'title' => 'Domain immersion: ' . $gap['domain'],
                'duration' => '2 weeks',
                'provider' => 'Senior SME sessions',
            ];
        }

        foreach ($behavioralNeeds as $need) {
            $recommendations[] = [
                'type' => 'behavioral',
                'title' => 'Behavioral coaching: ' . Str::title($need['trait'] ?? 'core trait'),
                'duration' => '6 sessions',
                'provider' => 'People partner',
            ];
        }

        return $recommendations;
    }

    private function buildDevelopmentTimeline(array $skillGaps, array $knowledgeGaps, array $behavioralNeeds, array $leadershipNeeds): array
    {
        return [
            'immediate' => array_map(fn ($gap) => 'Close ' . $gap['description'] . ' gap', array_slice($skillGaps, 0, 2)),
            'short_term' => array_map(fn ($gap) => 'Gain proficiency in ' . ($gap['domain'] ?? 'core domain'), array_slice($knowledgeGaps, 0, 2)),
            'medium_term' => array_map(fn ($need) => 'Strengthen trait: ' . ($need['trait'] ?? 'core competency'), array_slice($behavioralNeeds, 0, 3)),
            'long_term' => array_map(fn ($need) => 'Leadership focus: ' . ($need['focus'] ?? 'strategic impact'), $leadershipNeeds),
        ];
    }

    private function buildResourceRequirements(array $trainingRecommendations, array $timeline): array
    {
        $resources = ['Mentor allocation (2 hrs/week)', 'Learning budget block (₹35,000)', 'Access to knowledge base'];
        if (!empty($timeline['long_term'])) {
            $resources[] = 'Leadership coach pairing';
        }

        if (count($trainingRecommendations) > 3) {
            $resources[] = 'Dedicated learning sprint (Month 2)';
        }

        return $resources;
    }

    private function buildDevelopmentSuccessMetrics(array $timeline): array
    {
        return [
            '30_day' => 'Complete onboarding deliverables and close at least one critical skill gap.',
            '60_day' => 'Own core module independently with less than 10% rework.',
            '90_day' => 'Demonstrate cross-functional collaboration impact and mentor another team member.',
            '180_day' => empty($timeline['long_term']) ? 'Maintain quality and velocity benchmarks.' : 'Take ownership of strategic initiative aligned with leadership trajectory.',
        ];
    }

    private function persistDevelopmentNeeds(Application $application, array $skillGaps, array $knowledgeGaps, array $behavioralNeeds, array $leadershipNeeds): void
    {
        $records = [];

        foreach ($skillGaps as $gap) {
            $records[] = [
                'need_type' => 'skill_gap',
                'need_category' => $gap['category'] ?? 'technical',
                'need_description' => $gap['description'],
                'priority' => $gap['priority'],
                'estimated_time_to_address' => $gap['time_estimate'],
                'recommended_actions' => $gap['actions'],
            ];
        }

        foreach ($knowledgeGaps as $gap) {
            $records[] = [
                'need_type' => 'knowledge_gap',
                'need_category' => 'domain',
                'need_description' => $gap['domain'] ?? 'Domain knowledge',
                'priority' => 'medium',
                'estimated_time_to_address' => 45,
                'recommended_actions' => ['Participate in domain immersion workshops', 'Shadow domain experts for two sprints'],
            ];
        }

        foreach ($behavioralNeeds as $need) {
            $records[] = [
                'need_type' => 'behavioral_need',
                'need_category' => 'behavioral',
                'need_description' => $need['trait'] ?? 'Behavioral competency',
                'priority' => 'medium',
                'estimated_time_to_address' => 60,
                'recommended_actions' => [$need['development_action'] ?? 'Enroll in behavioral coaching sessions'],
            ];
        }

        foreach ($leadershipNeeds as $need) {
            $records[] = [
                'need_type' => 'leadership_need',
                'need_category' => 'leadership',
                'need_description' => $need['focus'] ?? 'Leadership capability',
                'priority' => 'high',
                'estimated_time_to_address' => 90,
                'recommended_actions' => [$need['recommended_action'] ?? 'Leadership workshop'],
            ];
        }

        foreach ($records as $record) {
            DevelopmentNeed::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'company_id' => $application->job->company_id,
                    'need_description' => $record['need_description'],
                ],
                $record + ['identified_at' => Carbon::now()]
            );
        }
    }

    private function buildDevelopmentRecommendation(array $timeline, array $trainingRecommendations): string
    {
        $immediateFocus = count($timeline['immediate'] ?? []);
        return sprintf(
            'Prioritize closing %d immediate skill gaps with the recommended learning plan; review progress every two weeks.',
            max($immediateFocus, 1)
        );
    }

    private function generatePlanPhases(Application $application, ?Job $job): array
    {
        return [
            [
                'phase' => 'Day 0 - Day 7',
                'focus' => 'Orientation & Systems Immersion',
                'objectives' => [
                    'Complete compliance onboarding and environment setup',
                    'Shadow key ceremonies to understand team cadence',
                ],
            ],
            [
                'phase' => 'Week 2 - Week 4',
                'focus' => 'Guided Delivery',
                'objectives' => [
                    'Deliver first scoped task with mentor support',
                    'Pair with product/QA counterparts to internalize lifecycle',
                ],
            ],
            [
                'phase' => 'Week 5 - Week 12',
                'focus' => 'Autonomy & Impact',
                'objectives' => [
                    'Own sprint backlog items end-to-end',
                    'Lead demo or knowledge sharing session by week 10',
                ],
            ],
        ];
    }

    private function buildOnboardingMilestones(): array
    {
        return [
            'Day 3: Complete environment setup and access verification.',
            'Week 2: Ship first production change with peer review.',
            'Week 6: Demonstrate ownership of feature slice with positive stakeholder feedback.',
            'Week 10: Lead retrospective item or improvement initiative.',
        ];
    }

    private function assignResourcesToPlan(Application $application): array
    {
        return [
            'Mentor' => $application->job->assigned_mentor ?? 'Designated senior engineer',
            'Buddy' => $application->job->onboarding_buddy ?? 'Peer assigned on Day 1',
            'People Partner' => $application->job->people_partner ?? 'HR Business Partner',
            'Enablement' => 'Learning & Development team for curated resources',
        ];
    }

    private function buildSuccessCheckpoints(): array
    {
        return [
            'Day 7 Checkpoint: Confirm access, orientation completion, and social integration.',
            'Day 30 Checkpoint: Review first deliverables and gather peer feedback.',
            'Day 60 Checkpoint: Evaluate autonomy level and address any blockers.',
            'Day 90 Checkpoint: Formal performance review and development plan refresh.',
        ];
    }

    private function calculatePlanDuration(): int
    {
        return 90;
    }

    private function buildOnboardingRecommendation(array $planPhases): string
    {
        return 'Execute the structured 90-day onboarding plan with weekly feedback loops to accelerate time-to-value.';
    }

    private function analyzeCareerPotential(Application $application): array
    {
        $performanceSignals = $application->user->profile['performance_trend_score'] ?? 0.78;
        $aspirationAlignment = $application->user->profile['career_aspiration_alignment'] ?? 0.8;
        $learningAgility = $this->assessLearningAgility($application);

        $score = ($performanceSignals * 0.4) + ($aspirationAlignment * 0.3) + ($learningAgility * 0.3);

        return [
            'score' => min(max($score, 0), 1),
            'category' => $score >= 0.8 ? 'high_potential' : ($score >= 0.6 ? 'growth_potential' : 'emerging'),
        ];
    }

    private function identifyProgressionPaths(Application $application, array $potential): array
    {
        $baseRole = $application->job->title ?? 'Current Role';
        $paths = [
            [
                'target_role' => 'Senior ' . $baseRole,
                'path_type' => 'vertical',
                'timeline_months' => 24,
                'probability' => min(0.85, ($potential['score'] ?? 0.7) + 0.1),
                'required_skills' => ['Advanced architecture', 'Stakeholder communication'],
                'milestones' => ['Lead feature delivery roadmap', 'Mentor junior engineers'],
            ],
            [
                'target_role' => 'Product Specialist',
                'path_type' => 'lateral',
                'timeline_months' => 18,
                'probability' => 0.55,
                'required_skills' => ['Product discovery', 'Customer empathy'],
                'milestones' => ['Shadow product manager sessions', 'Lead discovery workshop'],
            ],
        ];

        if (($potential['score'] ?? 0) >= 0.75) {
            $paths[] = [
                'target_role' => 'Engineering Manager',
                'path_type' => 'leadership',
                'timeline_months' => 36,
                'probability' => 0.45,
                'required_skills' => ['People leadership', 'Strategic planning'],
                'milestones' => ['Complete leadership program', 'Drive org-wide initiative'],
            ];
        }

        return $paths;
    }

    private function predictPathTimelines(array $paths): array
    {
        return array_map(function ($path) {
            return [
                'role' => $path['target_role'],
                'estimated_months' => $path['timeline_months'],
                'probability' => round(($path['probability'] ?? 0) * 100, 1),
            ];
        }, $paths);
    }

    private function assessPathRequirements(array $paths): array
    {
        $requirements = [];
        foreach ($paths as $path) {
            $requirements[] = [
                'path' => $path['target_role'],
                'requirements' => array_map(fn ($skill) => 'Demonstrate proficiency in ' . $skill, $path['required_skills'] ?? []),
            ];
        }

        return $requirements;
    }

    private function generateSuccessionInsights(array $paths): array
    {
        if (empty($paths)) {
            return ['summary' => 'No succession pathway identified yet'];
        }

        $highProbabilityPath = Arr::first($paths, fn ($path) => ($path['probability'] ?? 0) >= 0.7);

        return [
            'summary' => $highProbabilityPath
                ? 'Candidate can be succession-ready for ' . $highProbabilityPath['target_role'] . ' within ' . $highProbabilityPath['timeline_months'] . ' months.'
                : 'Candidate displays emerging potential; revisit succession readiness after first performance review.',
        ];
    }

    private function selectRecommendedPath(array $paths): ?array
    {
        if (empty($paths)) {
            return null;
        }

        usort($paths, fn ($a, $b) => ($b['probability'] ?? 0) <=> ($a['probability'] ?? 0));
        return $paths[0] ?? null;
    }

    private function persistCareerPathPredictions(Application $application, array $paths): void
    {
        foreach ($paths as $path) {
            CareerPathPrediction::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'company_id' => $application->job->company_id,
                    'predicted_role' => $path['target_role'],
                ],
                [
                    'current_role' => $application->job->title,
                    'path_type' => $path['path_type'] ?? 'vertical',
                    'estimated_timeline_months' => $path['timeline_months'] ?? null,
                    'probability' => $path['probability'] ?? 0.4,
                    'required_skills' => $path['required_skills'] ?? [],
                    'development_milestones' => $path['milestones'] ?? [],
                    'predicted_at' => Carbon::now(),
                ]
            );
        }
    }

    private function determineCareerTrajectory(array $paths): string
    {
        $types = array_count_values(array_map(fn ($path) => $path['path_type'] ?? 'vertical', $paths));
        arsort($types);
        $dominant = array_key_first($types) ?? 'vertical';

        return match ($dominant) {
            'vertical' => 'Growth within current expertise (IC track)',
            'lateral' => 'Lateral expansion into adjacent disciplines',
            'leadership' => 'Leadership development trajectory',
            'diagonal' => 'Hybrid role progression',
            default => 'Flexible career lattice',
        };
    }

    private function computeSuccessionPotential(array $successionInsights): float
    {
        if (Str::contains(Str::lower($successionInsights['summary'] ?? ''), 'succession-ready')) {
            return 72.5;
        }

        if (Str::contains(Str::lower($successionInsights['summary'] ?? ''), 'emerging')) {
            return 55.0;
        }

        return 48.0;
    }

    private function buildCareerPathRecommendation(?array $recommendedPath, array $developmentRequirements): string
    {
        if (!$recommendedPath) {
            return 'Establish baseline performance metrics before charting a clear career trajectory.';
        }

        return sprintf(
            'Focus on the %s path; complete the highlighted development requirements to unlock progression within %d months.',
            $recommendedPath['target_role'],
            $recommendedPath['timeline_months'] ?? 24
        );
    }

    private function compileActionItems(array ...$sections): array
    {
        $items = [];

        foreach ($sections as $section) {
            if (isset($section['recommendation'])) {
                $items[] = $section['recommendation'];
            }
        }

        return array_unique($items);
    }

    private function prioritizeRecommendations(array $success, array $tenure, array $productivity, array $flightRisk, array $development, array $career): array
    {
        return [
            [
                'title' => 'Mitigate top flight-risk factor',
                'priority' => match ($flightRisk['risk_level'] ?? 'low') {
                    'critical' => 'P0',
                    'high' => 'P1',
                    'medium' => 'P2',
                    default => 'P3',
                },
                'detail' => $flightRisk['recommendation'] ?? '',
            ],
            [
                'title' => 'Execute onboarding acceleration plan',
                'priority' => match ($productivity['productivity_category'] ?? 'average_ramp') {
                    'quick_ramp' => 'P2',
                    'average_ramp' => 'P1',
                    'slow_ramp', 'extended_ramp' => 'P0',
                    default => 'P2',
                },
                'detail' => $productivity['recommendation'] ?? '',
            ],
            [
                'title' => 'Activate targeted development plan',
                'priority' => 'P1',
                'detail' => $development['recommendation'] ?? '',
            ],
            [
                'title' => 'Chart career trajectory conversation',
                'priority' => 'P2',
                'detail' => $career['recommendation'] ?? '',
            ],
        ];
    }

    private function buildVisualizationPayload(array $success, array $tenure, array $productivity, array $flightRisk, array $career): array
    {
        return [
            'success' => [
                'probability' => round(($success['success_probability'] ?? 0) * 100, 1),
                'confidence' => round(($success['confidence_score'] ?? 0) * 100, 1),
            ],
            'tenure' => [
                'months' => $tenure['predicted_tenure_months'] ?? null,
                'flight_risk_score' => round(($tenure['flight_risk_score'] ?? 0) * 100, 1),
                'range' => $tenure['tenure_range'] ?? ['min' => 0, 'max' => 0],
            ],
            'productivity' => [
                'estimated_weeks' => $productivity['estimated_weeks'] ?? null,
                'category' => $productivity['productivity_category'] ?? 'average_ramp',
            ],
            'flight_risk' => [
                'score' => round(($flightRisk['risk_score'] ?? 0) * 100, 1),
                'level' => $flightRisk['risk_level'] ?? 'low',
            ],
            'career' => [
                'succession_potential' => $career['succession_potential'] ?? 0,
                'recommended_timeline' => $career['estimated_timeline_months'] ?? null,
            ],
        ];
    }

    private function composeComprehensiveReport(
        Application $application,
        array $success,
        array $tenure,
        array $productivity,
        array $flightRisk,
        array $development,
        array $onboarding,
        array $career,
        array $actionItems
    ): string {
        $candidateName = $application->user->name ?? 'Candidate';
        $roleTitle = $application->job->title ?? 'Role';

        $sections = [
            sprintf('<h3>Summary for %s - %s</h3>', e($candidateName), e($roleTitle)),
            sprintf('<p>Success probability stands at <strong>%s%%</strong> with %s.</p>',
                round(($success['success_probability'] ?? 0) * 100, 1),
                strtolower($this->formatConfidenceLabel($this->mapConfidenceToLevel($success['confidence_score'] ?? 0.7)))
            ),
            sprintf('<p>Projected tenure is <strong>%d months</strong> (%s). Flight risk category: <strong>%s</strong>.</p>',
                $tenure['predicted_tenure_months'] ?? 0,
                $tenure['player_type_display'] ?? 'N/A',
                Str::title(str_replace('_', ' ', $tenure['risk_category'] ?? 'stable'))
            ),
            sprintf('<p>Expected time to full productivity: <strong>%d weeks</strong> (%s ramp). Support level required: <strong>%s</strong>.</p>',
                $productivity['estimated_weeks'] ?? 0,
                Str::title(str_replace('_', ' ', $productivity['productivity_category'] ?? 'average_ramp')),
                strtoupper($productivity['support_requirements']['level'] ?? 'STANDARD')
            ),
            sprintf('<p>Flight risk assessment indicates <strong>%s</strong> risk with score %s%%.</p>',
                Str::title($flightRisk['risk_level'] ?? 'low'),
                round(($flightRisk['risk_score'] ?? 0) * 100, 1)
            ),
            '<h4>Immediate Action Items</h4><ul>' . implode('', array_map(fn ($item) => '<li>' . e($item) . '</li>', $actionItems)) . '</ul>',
            '<h4>Career Trajectory</h4><p>' . e($career['recommendation'] ?? 'No career path defined yet.') . '</p>',
        ];

        return implode("\n", $sections);
    }

    private function calculateDurationInMonths(?string $startDate, ?string $endDate): int
    {
        if (!$startDate) {
            return 0;
        }

        $start = Carbon::parse($startDate);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        return max(1, $start->diffInMonths($end));
    }

    private function isRelevantExperience(array $pastJob, Job $currentJob): bool
    {
        $pastRole = Str::lower($pastJob['role'] ?? '');
        $currentTitle = Str::lower($currentJob->title ?? '');

        if (!$pastRole || !$currentTitle) {
            return true;
        }

        return Str::contains($pastRole, explode(' ', $currentTitle)) || Str::contains($currentTitle, explode(' ', $pastRole));
    }

    private function findSkillScore(array $skills, string $name): float
    {
        foreach ($skills as $skill) {
            $skillName = is_array($skill) ? ($skill['name'] ?? '') : $skill;
            if (Str::lower($skillName) === Str::lower($name)) {
                return is_array($skill) ? ($skill['score'] ?? 0.5) : 0.6;
            }
        }

        return 0.4;
    }

    private function buildActionsForGap(string $name, string $category): array
    {
        if ($category === 'technical') {
            return [
                'Enroll in advanced course for ' . $name,
                'Pair with subject matter expert for two sprints',
                'Complete code lab focused on ' . $name,
            ];
        }

        return [
            'Participate in workshop on ' . $name,
            'Apply learning in simulated projects',
        ];
    }

    /**
     * Build a structured decision explanation for traceability.
     *
     * Captures the full scoring context so that any prediction can be
     * audited, reproduced, or compared against future model versions.
     */
    private function buildDecisionExplanation(
        string $predictionType,
        array $factors,
        array $rawScores,
        float $finalScore
    ): array {
        $modelVersion = (string) config('ai.azure.api_version', self::MODEL_VERSION);

        $inputFactors = [];
        foreach ($factors as $name => $data) {
            $value = (float) ($data['value'] ?? 0);
            $weight = (float) ($data['weight'] ?? 0);
            $inputFactors[] = [
                'factor' => $name,
                'value' => round($value, 4),
                'weight' => round($weight, 4),
                'weighted_contribution' => round($value * $weight, 4),
            ];
        }

        $scoringBreakdown = [];
        foreach ($rawScores as $name => $score) {
            $weight = (float) ($factors[$name]['weight'] ?? 0);
            $scoringBreakdown[] = [
                'component' => $name,
                'raw_score' => round((float) $score, 4),
                'weight' => round($weight, 4),
                'weighted_score' => round((float) $score * $weight, 4),
            ];
        }

        $nonZeroFactors = count(array_filter(
            $inputFactors,
            fn (array $f) => abs($f['value']) > 0
        ));
        $totalFactors = max(count($inputFactors), 1);
        $dataCompleteness = $nonZeroFactors / $totalFactors;

        $confidenceLevel = match (true) {
            $dataCompleteness >= 0.90 => 'very_high',
            $dataCompleteness >= 0.75 => 'high',
            $dataCompleteness >= 0.50 => 'moderate',
            default => 'low',
        };

        $driverCandidates = $inputFactors;
        usort(
            $driverCandidates,
            fn (array $a, array $b) => abs($b['weighted_contribution']) <=> abs($a['weighted_contribution'])
        );
        $keyDrivers = array_map(
            fn (array $d) => [
                'factor' => $d['factor'],
                'contribution' => $d['weighted_contribution'],
                'influence' => $d['weighted_contribution'] > 0 ? 'positive' : 'neutral',
            ],
            array_slice($driverCandidates, 0, 3)
        );

        return [
            'prediction_type' => $predictionType,
            'timestamp' => Carbon::now()->toIso8601String(),
            'model_version' => $modelVersion,
            'input_factors' => $inputFactors,
            'scoring_breakdown' => $scoringBreakdown,
            'final_score' => round($finalScore, 4),
            'confidence_level' => $confidenceLevel,
            'key_drivers' => array_values($keyDrivers),
        ];
    }

    /**
     * Persist the decision explanation as a traceable record.
     *
     * Uses the DecisionTrace model to store explanation_json alongside
     * the application reference, enabling retrospective audits and
     * bias reviews across all prediction types.
     */
    private function persistDecisionTrace(
        int $applicationId,
        string $predictionType,
        array $explanation
    ): void {
        try {
            DecisionTrace::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'prediction_type' => $predictionType,
                ],
                [
                    'explanation_json' => $explanation,
                    'model_version' => $explanation['model_version'] ?? self::MODEL_VERSION,
                    'final_score' => $explanation['final_score'] ?? null,
                    'confidence_level' => $explanation['confidence_level'] ?? null,
                    'traced_at' => Carbon::now(),
                ]
            );
        } catch (Exception $e) {
            Log::warning('Failed to persist decision trace', [
                'application_id' => $applicationId,
                'prediction_type' => $predictionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
