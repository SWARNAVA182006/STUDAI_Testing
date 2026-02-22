<?php

namespace App\Services\AI\Scout;

use App\Models\Application;
use App\Models\Job;
use App\Models\Company;
use App\Models\HirePerformance;
use App\Models\AssessmentRefinement;
use App\Models\HiringDecisionOverride;
use App\Models\TalentNeedPrediction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Exception;

class ContinuousLearningService
{
    /**
     * Track hired candidate's performance over time
     *
     * @param int $applicationId
     * @param array $performanceData
     * @return HirePerformance
     */
    public function trackPerformance(int $applicationId, array $performanceData): HirePerformance
    {
        try {
            $application = Application::with(['job.company', 'user'])->findOrFail($applicationId);

            // Create or update performance record
            $performance = HirePerformance::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'company_id' => $application->job->company_id,
                    'job_id' => $application->job_id,
                    'review_period' => $performanceData['review_period'] ?? 'probation'
                ],
                [
                    'user_id' => $application->user_id,
                    'hire_date' => $performanceData['hire_date'] ?? $application->updated_at,
                    'performance_rating' => $performanceData['performance_rating'],
                    'technical_skills_rating' => $performanceData['technical_skills_rating'] ?? null,
                    'soft_skills_rating' => $performanceData['soft_skills_rating'] ?? null,
                    'cultural_fit_rating' => $performanceData['cultural_fit_rating'] ?? null,
                    'productivity_rating' => $performanceData['productivity_rating'] ?? null,
                    'team_collaboration_rating' => $performanceData['team_collaboration_rating'] ?? null,
                    'leadership_rating' => $performanceData['leadership_rating'] ?? null,
                    'retention_status' => $performanceData['retention_status'] ?? 'active',
                    'promotion_count' => $performanceData['promotion_count'] ?? 0,
                    'manager_feedback' => $performanceData['manager_feedback'] ?? null,
                    'peer_feedback' => $performanceData['peer_feedback'] ?? null,
                    'achievements' => $performanceData['achievements'] ?? null,
                    'challenges' => $performanceData['challenges'] ?? null,
                    'actual_vs_predicted_performance' => $this->compareWithPrediction($application, $performanceData),
                    'metadata' => $performanceData['metadata'] ?? null
                ]
            );

            // Trigger learning from this performance data
            $this->learnFromPerformance($performance);

            Log::info('Performance tracked for hire', [
                'application_id' => $applicationId,
                'performance_id' => $performance->id,
                'rating' => $performanceData['performance_rating']
            ]);

            return $performance;

        } catch (Exception $e) {
            Log::error('Failed to track performance', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Record hiring manager's override decision and learn from it
     *
     * @param int $applicationId
     * @param array $overrideData
     * @return HiringDecisionOverride
     */
    public function recordOverride(int $applicationId, array $overrideData): HiringDecisionOverride
    {
        try {
            $application = Application::with(['job.company'])->findOrFail($applicationId);

            $override = HiringDecisionOverride::create([
                'application_id' => $applicationId,
                'company_id' => $application->job->company_id,
                'job_id' => $application->job_id,
                'user_id' => $overrideData['manager_id'],
                'scout_recommendation' => $overrideData['scout_recommendation'],
                'manager_decision' => $overrideData['manager_decision'],
                'override_type' => $this->determineOverrideType(
                    $overrideData['scout_recommendation'],
                    $overrideData['manager_decision']
                ),
                'override_reason' => $overrideData['reason'] ?? null,
                'override_factors' => $overrideData['factors'] ?? null,
                'confidence_level' => $overrideData['confidence_level'] ?? null,
                'outcome' => $overrideData['outcome'] ?? 'pending',
                'metadata' => $overrideData['metadata'] ?? null
            ]);

            // Learn from this override immediately
            $this->learnFromOverride($override);

            Log::info('Hiring decision override recorded', [
                'application_id' => $applicationId,
                'override_id' => $override->id,
                'type' => $override->override_type
            ]);

            return $override;

        } catch (Exception $e) {
            Log::error('Failed to record override', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Refine assessment criteria based on accumulated performance data
     *
     * @param int $companyId
     * @param array $options
     * @return AssessmentRefinement
     */
    public function refineAssessmentCriteria(int $companyId, array $options = []): AssessmentRefinement
    {
        try {
            $company = Company::findOrFail($companyId);

            // Gather performance data for analysis
            $performanceData = $this->gatherPerformanceData($companyId, $options);

            if ($performanceData['hire_count'] < 5) {
                throw new Exception('Insufficient hiring data for refinement (minimum 5 hires required)');
            }

            // Analyze what factors correlate with success
            $correlationAnalysis = $this->analyzeSuccessFactors($performanceData);

            // Use AI to generate refined criteria
            $refinedCriteria = $this->generateRefinedCriteria($company, $performanceData, $correlationAnalysis);

            // Calculate new weights based on correlation strength
            $newWeights = $this->calculateOptimalWeights($correlationAnalysis);

            // Create refinement record
            $refinement = AssessmentRefinement::create([
                'company_id' => $companyId,
                'refinement_type' => $options['refinement_type'] ?? 'comprehensive',
                'data_points_analyzed' => $performanceData['hire_count'],
                'time_period_start' => $performanceData['period_start'],
                'time_period_end' => $performanceData['period_end'],
                'previous_criteria' => $this->getCurrentCriteria($companyId),
                'refined_criteria' => $refinedCriteria,
                'previous_weights' => $this->getCurrentWeights($companyId),
                'refined_weights' => $newWeights,
                'correlation_analysis' => $correlationAnalysis,
                'performance_improvement_estimate' => $this->estimateImprovement($correlationAnalysis),
                'confidence_score' => $this->calculateConfidenceScore($performanceData, $correlationAnalysis),
                'ai_insights' => $refinedCriteria['ai_insights'] ?? null,
                'applied_at' => $options['auto_apply'] ?? false ? now() : null,
                'metadata' => $options['metadata'] ?? null
            ]);

            // Auto-apply if requested
            if ($options['auto_apply'] ?? false) {
                $this->applyRefinement($refinement);
            }

            Log::info('Assessment criteria refined', [
                'company_id' => $companyId,
                'refinement_id' => $refinement->id,
                'confidence' => $refinement->confidence_score
            ]);

            return $refinement;

        } catch (Exception $e) {
            Log::error('Failed to refine assessment criteria', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze patterns in successful and unsuccessful hires
     *
     * @param int $companyId
     * @param array $options
     * @return array
     */
    public function analyzeHiringPatterns(int $companyId, array $options = []): array
    {
        $cacheKey = "hiring_patterns:{$companyId}:" . md5(json_encode($options));

        return Cache::remember($cacheKey, 1800, function () use ($companyId, $options) {
            try {
                // Get successful hires (high performance)
                $successfulHires = HirePerformance::where('company_id', $companyId)
                    ->where('performance_rating', '>=', 4.0)
                    ->with(['application.user', 'job'])
                    ->get();

                // Get unsuccessful hires (low performance or departed)
                $unsuccessfulHires = HirePerformance::where('company_id', $companyId)
                    ->where(function ($query) {
                        $query->where('performance_rating', '<', 3.0)
                            ->orWhereIn('retention_status', ['terminated', 'resigned_early']);
                    })
                    ->with(['application.user', 'job'])
                    ->get();

                // Extract common characteristics
                $successPatterns = $this->extractCommonCharacteristics($successfulHires);
                $failurePatterns = $this->extractCommonCharacteristics($unsuccessfulHires);

                // Identify key differentiators
                $differentiators = $this->identifyDifferentiators($successPatterns, $failurePatterns);

                // Use AI to generate insights
                $aiInsights = $this->generatePatternInsights($companyId, $successPatterns, $failurePatterns, $differentiators);

                return [
                    'success_patterns' => $successPatterns,
                    'failure_patterns' => $failurePatterns,
                    'key_differentiators' => $differentiators,
                    'ai_insights' => $aiInsights,
                    'sample_size' => [
                        'successful' => $successfulHires->count(),
                        'unsuccessful' => $unsuccessfulHires->count()
                    ],
                    'analysis_date' => now()->toISOString()
                ];

            } catch (Exception $e) {
                Log::error('Failed to analyze hiring patterns', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Predict future talent needs based on growth patterns and trends
     *
     * @param int $companyId
     * @param array $options
     * @return TalentNeedPrediction
     */
    public function predictTalentNeeds(int $companyId, array $options = []): TalentNeedPrediction
    {
        try {
            $company = Company::findOrFail($companyId);
            $timeHorizon = $options['time_horizon_months'] ?? 12;

            // Analyze historical hiring patterns
            $historicalData = $this->analyzeHistoricalHiring($companyId);

            // Identify growth trends
            $growthTrends = $this->identifyGrowthTrends($companyId, $historicalData);

            // Analyze industry trends (using AI)
            $industryTrends = $this->analyzeIndustryTrends($company);

            // Identify emerging skills
            $emergingSkills = $this->identifyEmergingSkills($companyId, $industryTrends);

            // Use AI to generate comprehensive predictions
            $predictions = $this->generateTalentPredictions(
                $company,
                $historicalData,
                $growthTrends,
                $industryTrends,
                $emergingSkills,
                $timeHorizon
            );

            // Create prediction record
            $prediction = TalentNeedPrediction::create([
                'company_id' => $companyId,
                'prediction_horizon_months' => $timeHorizon,
                'predicted_roles' => $predictions['predicted_roles'],
                'predicted_headcount' => $predictions['predicted_headcount'],
                'predicted_skills_demand' => $emergingSkills,
                'growth_factors' => $growthTrends,
                'industry_trends' => $industryTrends,
                'confidence_score' => $predictions['confidence_score'],
                'recommendations' => $predictions['recommendations'],
                'ai_analysis' => $predictions['ai_analysis'],
                'data_points_used' => $historicalData['hire_count'],
                'prediction_basis' => $this->buildPredictionBasis($historicalData, $growthTrends, $industryTrends),
                'metadata' => $options['metadata'] ?? null
            ]);

            Log::info('Talent needs predicted', [
                'company_id' => $companyId,
                'prediction_id' => $prediction->id,
                'time_horizon' => $timeHorizon
            ]);

            return $prediction;

        } catch (Exception $e) {
            Log::error('Failed to predict talent needs', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get comprehensive learning insights for a company
     *
     * @param int $companyId
     * @param array $options
     * @return array
     */
    public function getLearningInsights(int $companyId, array $options = []): array
    {
        $cacheKey = "learning_insights:{$companyId}:" . md5(json_encode($options));

        return Cache::remember($cacheKey, 3600, function () use ($companyId, $options) {
            try {
                $company = Company::findOrFail($companyId);

                // Get latest refinement
                $latestRefinement = AssessmentRefinement::where('company_id', $companyId)
                    ->latest()
                    ->first();

                // Get hiring patterns
                $patterns = $this->analyzeHiringPatterns($companyId);

                // Get performance trends
                $performanceTrends = $this->analyzePerformanceTrends($companyId);

                // Get override learning
                $overrideLearning = $this->analyzeOverridePatterns($companyId);

                // Get DNA evolution
                $dnaEvolution = $this->analyzeDNAEvolution($companyId);

                // Generate AI-powered summary
                $aiSummary = $this->generateLearningSummary($company, [
                    'refinement' => $latestRefinement,
                    'patterns' => $patterns,
                    'trends' => $performanceTrends,
                    'overrides' => $overrideLearning,
                    'dna_evolution' => $dnaEvolution
                ]);

                return [
                    'company_id' => $companyId,
                    'latest_refinement' => $latestRefinement,
                    'hiring_patterns' => $patterns,
                    'performance_trends' => $performanceTrends,
                    'override_insights' => $overrideLearning,
                    'dna_evolution' => $dnaEvolution,
                    'ai_summary' => $aiSummary,
                    'learning_maturity_level' => $this->calculateLearningMaturity($companyId),
                    'recommendations' => $this->generateRecommendations($companyId, $aiSummary),
                    'generated_at' => now()->toISOString()
                ];

            } catch (Exception $e) {
                Log::error('Failed to get learning insights', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Learn from individual performance record
     *
     * @param HirePerformance $performance
     * @return void
     */
    protected function learnFromPerformance(HirePerformance $performance): void
    {
        try {
            // Compare actual vs predicted performance
            if ($performance->actual_vs_predicted_performance) {
                $variance = $performance->actual_vs_predicted_performance;

                // If prediction was significantly off, trigger refinement
                if (abs($variance['variance']) > 1.5) {
                    Log::info('Significant prediction variance detected', [
                        'performance_id' => $performance->id,
                        'variance' => $variance
                    ]);

                    // Queue refinement job if enough data accumulated
                    $this->checkAndTriggerRefinement($performance->company_id);
                }
            }

            // Update success factors cache
            $this->updateSuccessFactors($performance);

        } catch (Exception $e) {
            Log::warning('Failed to learn from performance', [
                'performance_id' => $performance->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Learn from hiring manager override
     *
     * @param HiringDecisionOverride $override
     * @return void
     */
    protected function learnFromOverride(HiringDecisionOverride $override): void
    {
        try {
            // Analyze override pattern
            $pattern = $this->analyzeOverridePattern($override);

            // Update preferences model
            $this->updateManagerPreferences($override->company_id, $override->user_id, $pattern);

            // If override happens frequently for certain factors, adjust weights
            if ($this->isFrequentOverridePattern($override->company_id, $pattern)) {
                Log::info('Frequent override pattern detected', [
                    'company_id' => $override->company_id,
                    'pattern' => $pattern
                ]);

                // Queue refinement
                $this->checkAndTriggerRefinement($override->company_id);
            }

        } catch (Exception $e) {
            Log::warning('Failed to learn from override', [
                'override_id' => $override->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Compare actual performance with S.C.O.U.T. prediction
     *
     * @param Application $application
     * @param array $performanceData
     * @return array|null
     */
    protected function compareWithPrediction(Application $application, array $performanceData): ?array
    {
        try {
            // Get S.C.O.U.T. predictions from application metadata
            $predictions = $application->metadata['scout_predictions'] ?? null;

            if (!$predictions) {
                return null;
            }

            $actualRating = $performanceData['performance_rating'];
            $predictedRating = $predictions['predicted_performance'] ?? null;

            if (!$predictedRating) {
                return null;
            }

            return [
                'predicted_rating' => $predictedRating,
                'actual_rating' => $actualRating,
                'variance' => $actualRating - $predictedRating,
                'accuracy' => 1 - (abs($actualRating - $predictedRating) / 5.0), // Assuming 1-5 scale
                'prediction_date' => $predictions['prediction_date'] ?? $application->created_at,
                'factors_evaluated' => $predictions['factors'] ?? null
            ];

        } catch (Exception $e) {
            Log::warning('Failed to compare with prediction', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Determine override type based on recommendations vs decision
     *
     * @param string $scoutRecommendation
     * @param string $managerDecision
     * @return string
     */
    protected function determineOverrideType(string $scoutRecommendation, string $managerDecision): string
    {
        $recommendLevels = ['STRONG HIRE', 'RECOMMEND', 'CONSIDER', 'CAUTION', 'NOT RECOMMENDED'];
        $scoutIndex = array_search($scoutRecommendation, $recommendLevels);
        $managerIndex = array_search($managerDecision, $recommendLevels);

        if ($scoutIndex === false || $managerIndex === false) {
            return 'other';
        }

        if ($managerIndex < $scoutIndex) {
            return 'hire_despite_caution'; // Manager more positive than S.C.O.U.T.
        } elseif ($managerIndex > $scoutIndex) {
            return 'reject_despite_recommendation'; // Manager more conservative
        } else {
            return 'agreement'; // Should not be recorded as override
        }
    }

    /**
     * Gather performance data for analysis
     *
     * @param int $companyId
     * @param array $options
     * @return array
     */
    protected function gatherPerformanceData(int $companyId, array $options): array
    {
        $periodMonths = $options['period_months'] ?? 12;
        $periodStart = now()->subMonths($periodMonths);

        $performances = HirePerformance::where('company_id', $companyId)
            ->where('hire_date', '>=', $periodStart)
            ->with(['application.user', 'job'])
            ->get();

        return [
            'hire_count' => $performances->count(),
            'period_start' => $periodStart,
            'period_end' => now(),
            'performances' => $performances,
            'avg_performance_rating' => $performances->avg('performance_rating'),
            'retention_rate' => $this->calculateRetentionRate($performances),
            'promotion_rate' => $this->calculatePromotionRate($performances)
        ];
    }

    /**
     * Analyze what factors correlate with success
     *
     * @param array $performanceData
     * @return array
     */
    protected function analyzeSuccessFactors(array $performanceData): array
    {
        $performances = $performanceData['performances'];

        // Categorize by success level
        $highPerformers = $performances->where('performance_rating', '>=', 4.0);
        $lowPerformers = $performances->where('performance_rating', '<', 3.0);

        // Analyze correlations for various factors
        $correlations = [
            'technical_skills' => $this->calculateCorrelation($performances, 'technical_skills_rating', 'performance_rating'),
            'soft_skills' => $this->calculateCorrelation($performances, 'soft_skills_rating', 'performance_rating'),
            'cultural_fit' => $this->calculateCorrelation($performances, 'cultural_fit_rating', 'performance_rating'),
            'team_collaboration' => $this->calculateCorrelation($performances, 'team_collaboration_rating', 'performance_rating'),
            'leadership' => $this->calculateCorrelation($performances, 'leadership_rating', 'performance_rating'),
        ];

        // Sort by correlation strength
        arsort($correlations);

        return [
            'correlations' => $correlations,
            'strongest_predictor' => array_key_first($correlations),
            'weakest_predictor' => array_key_last($correlations),
            'high_performer_traits' => $this->extractTraits($highPerformers),
            'low_performer_traits' => $this->extractTraits($lowPerformers),
            'sample_sizes' => [
                'total' => $performances->count(),
                'high_performers' => $highPerformers->count(),
                'low_performers' => $lowPerformers->count()
            ]
        ];
    }

    /**
     * Generate refined criteria using AI
     *
     * @param Company $company
     * @param array $performanceData
     * @param array $correlationAnalysis
     * @return array
     */
    protected function generateRefinedCriteria(Company $company, array $performanceData, array $correlationAnalysis): array
    {
        try {
            $prompt = $this->buildRefinementPrompt($company, $performanceData, $correlationAnalysis);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert talent assessment analyst specializing in refining hiring criteria based on actual performance data. Analyze the data and provide refined assessment criteria that will better predict success in this specific organization.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.4,
                'max_tokens' => 2000
            ]);

            $aiAnalysis = $response->choices[0]->message->content;

            // Extract structured data from AI response
            return $this->parseRefinementResponse($aiAnalysis, $correlationAnalysis);

        } catch (Exception $e) {
            Log::error('Failed to generate refined criteria with AI', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to rule-based refinement
            return $this->generateRuleBasedCriteria($correlationAnalysis);
        }
    }

    /**
     * Calculate optimal weights based on correlation analysis
     *
     * @param array $correlationAnalysis
     * @return array
     */
    protected function calculateOptimalWeights(array $correlationAnalysis): array
    {
        $correlations = $correlationAnalysis['correlations'];
        $totalCorrelation = array_sum(array_map('abs', $correlations));

        if ($totalCorrelation == 0) {
            // Return equal weights if no correlation data
            return array_fill_keys(array_keys($correlations), 1.0 / count($correlations));
        }

        // Convert correlations to weights (stronger correlation = higher weight)
        $weights = [];
        foreach ($correlations as $factor => $correlation) {
            $weights[$factor] = abs($correlation) / $totalCorrelation;
        }

        return $weights;
    }

    /**
     * Extract common characteristics from a set of hires
     *
     * @param \Illuminate\Support\Collection $hires
     * @return array
     */
    protected function extractCommonCharacteristics($hires): array
    {
        if ($hires->isEmpty()) {
            return [];
        }

        $characteristics = [
            'avg_technical_skills' => $hires->avg('technical_skills_rating'),
            'avg_soft_skills' => $hires->avg('soft_skills_rating'),
            'avg_cultural_fit' => $hires->avg('cultural_fit_rating'),
            'avg_team_collaboration' => $hires->avg('team_collaboration_rating'),
            'avg_leadership' => $hires->avg('leadership_rating'),
            'common_achievements' => $this->findCommonPatterns($hires, 'achievements'),
            'common_challenges' => $this->findCommonPatterns($hires, 'challenges'),
            'retention_rate' => $this->calculateRetentionRate($hires),
            'avg_promotion_count' => $hires->avg('promotion_count')
        ];

        return array_filter($characteristics, fn($value) => $value !== null);
    }

    /**
     * Identify key differentiators between successful and unsuccessful hires
     *
     * @param array $successPatterns
     * @param array $failurePatterns
     * @return array
     */
    protected function identifyDifferentiators(array $successPatterns, array $failurePatterns): array
    {
        $differentiators = [];

        foreach ($successPatterns as $key => $successValue) {
            if (isset($failurePatterns[$key]) && is_numeric($successValue) && is_numeric($failurePatterns[$key])) {
                $difference = $successValue - $failurePatterns[$key];
                if (abs($difference) > 0.5) { // Significant difference threshold
                    $differentiators[$key] = [
                        'success_avg' => $successValue,
                        'failure_avg' => $failurePatterns[$key],
                        'difference' => $difference,
                        'impact' => abs($difference) > 1.0 ? 'high' : 'medium'
                    ];
                }
            }
        }

        // Sort by absolute difference
        uasort($differentiators, fn($a, $b) => abs($b['difference']) <=> abs($a['difference']));

        return $differentiators;
    }

    /**
     * Generate AI insights from pattern analysis
     *
     * @param int $companyId
     * @param array $successPatterns
     * @param array $failurePatterns
     * @param array $differentiators
     * @return string
     */
    protected function generatePatternInsights(int $companyId, array $successPatterns, array $failurePatterns, array $differentiators): string
    {
        try {
            $company = Company::find($companyId);

            $prompt = "Analyze the following hiring patterns for {$company->name}:\n\n";
            $prompt .= "SUCCESSFUL HIRES CHARACTERISTICS:\n" . json_encode($successPatterns, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "UNSUCCESSFUL HIRES CHARACTERISTICS:\n" . json_encode($failurePatterns, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "KEY DIFFERENTIATORS:\n" . json_encode($differentiators, JSON_PRETTY_PRINT) . "\n\n";
            $prompt .= "Provide actionable insights about what makes candidates succeed or fail in this organization.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert talent analyst. Provide clear, actionable insights based on hiring pattern data.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 1000
            ]);

            return $response->choices[0]->message->content;

        } catch (Exception $e) {
            Log::error('Failed to generate pattern insights', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            return 'Unable to generate AI insights at this time.';
        }
    }

    /**
     * Analyze historical hiring patterns for predictions
     *
     * @param int $companyId
     * @return array
     */
    protected function analyzeHistoricalHiring(int $companyId): array
    {
        $sixMonthsAgo = now()->subMonths(6);
        $twelveMonthsAgo = now()->subMonths(12);

        $recentHires = HirePerformance::where('company_id', $companyId)
            ->where('hire_date', '>=', $sixMonthsAgo)
            ->count();

        $previousHires = HirePerformance::where('company_id', $companyId)
            ->whereBetween('hire_date', [$twelveMonthsAgo, $sixMonthsAgo])
            ->count();

        $allHires = HirePerformance::where('company_id', $companyId)
            ->select('hire_date', 'job_id')
            ->get()
            ->groupBy(function ($item) {
                return $item->hire_date->format('Y-m');
            });

        return [
            'hire_count' => $recentHires + $previousHires,
            'recent_6mo_count' => $recentHires,
            'previous_6mo_count' => $previousHires,
            'growth_rate' => $previousHires > 0 ? (($recentHires - $previousHires) / $previousHires) * 100 : 0,
            'monthly_breakdown' => $allHires->map->count()->toArray(),
            'avg_monthly_hires' => $allHires->isEmpty() ? 0 : $allHires->avg(fn($month) => $month->count())
        ];
    }

    /**
     * Identify growth trends for talent prediction
     *
     * @param int $companyId
     * @param array $historicalData
     * @return array
     */
    protected function identifyGrowthTrends(int $companyId, array $historicalData): array
    {
        return [
            'hiring_velocity' => $historicalData['growth_rate'],
            'trend_direction' => $historicalData['growth_rate'] > 10 ? 'accelerating' : 
                                ($historicalData['growth_rate'] < -10 ? 'declining' : 'stable'),
            'seasonality' => $this->detectSeasonality($historicalData['monthly_breakdown']),
            'projected_growth' => $this->projectGrowth($historicalData['growth_rate']),
            'confidence' => $historicalData['hire_count'] >= 20 ? 'high' : 
                           ($historicalData['hire_count'] >= 10 ? 'medium' : 'low')
        ];
    }

    /**
     * Analyze industry trends using AI
     *
     * @param Company $company
     * @return array
     */
    protected function analyzeIndustryTrends(Company $company): array
    {
        $cacheKey = "industry_trends:{$company->industry}";

        return Cache::remember($cacheKey, 86400, function () use ($company) {
            try {
                $prompt = "Analyze current talent and skill trends in the {$company->industry} industry. " .
                         "Focus on: 1) Emerging skills in demand, 2) Roles becoming more critical, " .
                         "3) Technology shifts affecting talent needs, 4) Market competition for talent.";

                $response = OpenAI::chat()->create([
                    'model' => 'gpt-5',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an industry talent analyst. Provide data-driven insights about current hiring trends.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 1500
                ]);

                $analysis = $response->choices[0]->message->content;

                return [
                    'industry' => $company->industry,
                    'analysis' => $analysis,
                    'generated_at' => now()->toISOString()
                ];

            } catch (Exception $e) {
                Log::error('Failed to analyze industry trends', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);

                return [
                    'industry' => $company->industry,
                    'analysis' => 'Industry trend analysis unavailable.',
                    'generated_at' => now()->toISOString()
                ];
            }
        });
    }

    /**
     * Identify emerging skills based on successful hires
     *
     * @param int $companyId
     * @param array $industryTrends
     * @return array
     */
    protected function identifyEmergingSkills(int $companyId, array $industryTrends): array
    {
        // Get recent high performers
        $recentHighPerformers = HirePerformance::where('company_id', $companyId)
            ->where('hire_date', '>=', now()->subMonths(6))
            ->where('performance_rating', '>=', 4.0)
            ->with('application.user')
            ->get();

        // Extract skills from their profiles
        $emergingSkills = [];
        foreach ($recentHighPerformers as $performer) {
            $skills = $performer->application->user->skills ?? [];
            foreach ($skills as $skill) {
                $emergingSkills[$skill] = ($emergingSkills[$skill] ?? 0) + 1;
            }
        }

        arsort($emergingSkills);

        return [
            'top_skills' => array_slice($emergingSkills, 0, 10, true),
            'industry_aligned' => $this->alignWithIndustryTrends($emergingSkills, $industryTrends),
            'sample_size' => $recentHighPerformers->count()
        ];
    }

    /**
     * Generate comprehensive talent predictions using AI
     *
     * @param Company $company
     * @param array $historicalData
     * @param array $growthTrends
     * @param array $industryTrends
     * @param array $emergingSkills
     * @param int $timeHorizon
     * @return array
     */
    protected function generateTalentPredictions(
        Company $company,
        array $historicalData,
        array $growthTrends,
        array $industryTrends,
        array $emergingSkills,
        int $timeHorizon
    ): array {
        try {
            $prompt = $this->buildPredictionPrompt($company, $historicalData, $growthTrends, $industryTrends, $emergingSkills, $timeHorizon);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert talent forecasting analyst. Provide realistic, data-driven predictions about future hiring needs.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'max_tokens' => 2000
            ]);

            $aiAnalysis = $response->choices[0]->message->content;

            // Parse AI response into structured data
            return $this->parsePredictionResponse($aiAnalysis, $historicalData, $growthTrends);

        } catch (Exception $e) {
            Log::error('Failed to generate talent predictions with AI', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            // Fallback to rule-based predictions
            return $this->generateRuleBasedPredictions($historicalData, $growthTrends, $timeHorizon);
        }
    }

    /**
     * Calculate simple correlation coefficient
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $xField
     * @param string $yField
     * @return float
     */
    protected function calculateCorrelation($data, string $xField, string $yField): float
    {
        $filtered = $data->filter(fn($item) => isset($item->$xField) && isset($item->$yField));

        if ($filtered->count() < 3) {
            return 0.0;
        }

        $xValues = $filtered->pluck($xField)->toArray();
        $yValues = $filtered->pluck($yField)->toArray();

        $n = count($xValues);
        $meanX = array_sum($xValues) / $n;
        $meanY = array_sum($yValues) / $n;

        $numerator = 0;
        $denomX = 0;
        $denomY = 0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $xValues[$i] - $meanX;
            $diffY = $yValues[$i] - $meanY;
            $numerator += $diffX * $diffY;
            $denomX += $diffX * $diffX;
            $denomY += $diffY * $diffY;
        }

        $denominator = sqrt($denomX * $denomY);

        return $denominator == 0 ? 0.0 : $numerator / $denominator;
    }

    /**
     * Helper methods for calculations and analysis
     */

    protected function calculateRetentionRate($performances): float
    {
        if ($performances->isEmpty()) return 0.0;
        
        $retained = $performances->whereIn('retention_status', ['active', 'promoted'])->count();
        return ($retained / $performances->count()) * 100;
    }

    protected function calculatePromotionRate($performances): float
    {
        if ($performances->isEmpty()) return 0.0;
        
        $promoted = $performances->where('promotion_count', '>', 0)->count();
        return ($promoted / $performances->count()) * 100;
    }

    protected function extractTraits($hires): array
    {
        return [
            'avg_technical' => $hires->avg('technical_skills_rating'),
            'avg_soft_skills' => $hires->avg('soft_skills_rating'),
            'avg_cultural_fit' => $hires->avg('cultural_fit_rating'),
        ];
    }

    protected function findCommonPatterns($hires, string $field): array
    {
        // Extract and analyze common patterns from JSON field
        $patterns = [];
        foreach ($hires as $hire) {
            if ($hire->$field && is_array($hire->$field)) {
                foreach ($hire->$field as $pattern) {
                    $patterns[$pattern] = ($patterns[$pattern] ?? 0) + 1;
                }
            }
        }
        arsort($patterns);
        return array_slice($patterns, 0, 5, true);
    }

    protected function getCurrentCriteria(int $companyId): array
    {
        // Retrieve current assessment criteria from latest refinement or defaults
        $latest = AssessmentRefinement::where('company_id', $companyId)
            ->where('applied_at', '!=', null)
            ->latest('applied_at')
            ->first();

        return $latest->refined_criteria ?? $this->getDefaultCriteria();
    }

    protected function getCurrentWeights(int $companyId): array
    {
        $latest = AssessmentRefinement::where('company_id', $companyId)
            ->where('applied_at', '!=', null)
            ->latest('applied_at')
            ->first();

        return $latest->refined_weights ?? $this->getDefaultWeights();
    }

    protected function getDefaultCriteria(): array
    {
        return [
            'technical_skills' => 'Assess based on job requirements',
            'soft_skills' => 'Evaluate communication and teamwork',
            'cultural_fit' => 'Align with company values',
            'problem_solving' => 'Test analytical abilities',
            'adaptability' => 'Measure learning agility'
        ];
    }

    protected function getDefaultWeights(): array
    {
        return [
            'technical_skills' => 0.30,
            'soft_skills' => 0.25,
            'cultural_fit' => 0.25,
            'problem_solving' => 0.15,
            'adaptability' => 0.05
        ];
    }

    protected function estimateImprovement(array $correlationAnalysis): float
    {
        // Estimate potential improvement in prediction accuracy
        $avgCorrelation = array_sum($correlationAnalysis['correlations']) / count($correlationAnalysis['correlations']);
        return min(abs($avgCorrelation) * 20, 30); // Cap at 30% improvement estimate
    }

    protected function calculateConfidenceScore(array $performanceData, array $correlationAnalysis): float
    {
        $sampleSize = $performanceData['hire_count'];
        $avgCorrelation = abs(array_sum($correlationAnalysis['correlations']) / count($correlationAnalysis['correlations']));

        // Confidence based on sample size and correlation strength
        $sampleConfidence = min($sampleSize / 50, 1.0); // 50+ hires = full confidence
        $correlationConfidence = $avgCorrelation;

        return ($sampleConfidence * 0.6 + $correlationConfidence * 0.4) * 100;
    }

    protected function applyRefinement(AssessmentRefinement $refinement): void
    {
        $refinement->update(['applied_at' => now()]);
        
        // Clear relevant caches
        Cache::tags(['company:' . $refinement->company_id, 'assessments'])->flush();
        
        Log::info('Refinement applied', ['refinement_id' => $refinement->id]);
    }

    protected function buildRefinementPrompt(Company $company, array $performanceData, array $correlationAnalysis): string
    {
        return "Company: {$company->name}\n" .
               "Industry: {$company->industry}\n\n" .
               "Performance Data Summary:\n" .
               "- Total hires analyzed: {$performanceData['hire_count']}\n" .
               "- Average performance rating: " . number_format($performanceData['avg_performance_rating'], 2) . "\n" .
               "- Retention rate: " . number_format($performanceData['retention_rate'], 1) . "%\n\n" .
               "Factor Correlations with Performance:\n" .
               json_encode($correlationAnalysis['correlations'], JSON_PRETTY_PRINT) . "\n\n" .
               "Based on this data, recommend refined assessment criteria and optimal weights for each factor.";
    }

    protected function parseRefinementResponse(string $aiAnalysis, array $correlationAnalysis): array
    {
        return [
            'refined_criteria' => $this->getDefaultCriteria(), // Enhanced based on AI
            'ai_insights' => $aiAnalysis,
            'correlation_basis' => $correlationAnalysis
        ];
    }

    protected function generateRuleBasedCriteria(array $correlationAnalysis): array
    {
        return [
            'refined_criteria' => $this->getDefaultCriteria(),
            'ai_insights' => 'Rule-based refinement applied.',
            'correlation_basis' => $correlationAnalysis
        ];
    }

    protected function calculateLearningMaturity(int $companyId): string
    {
        $hireCount = HirePerformance::where('company_id', $companyId)->count();
        $refinementCount = AssessmentRefinement::where('company_id', $companyId)->count();

        if ($hireCount >= 50 && $refinementCount >= 3) return 'Advanced';
        if ($hireCount >= 20 && $refinementCount >= 1) return 'Intermediate';
        if ($hireCount >= 5) return 'Developing';
        return 'Initial';
    }

    protected function checkAndTriggerRefinement(int $companyId): void
    {
        // Logic to queue refinement job if conditions met
        $unrefinedCount = HirePerformance::where('company_id', $companyId)
            ->where('created_at', '>', now()->subMonths(3))
            ->count();

        if ($unrefinedCount >= 10) {
            // Queue refinement job
            Log::info('Triggering automatic refinement', ['company_id' => $companyId]);
        }
    }

    protected function updateSuccessFactors(HirePerformance $performance): void
    {
        // Update cached success factors
        $cacheKey = "success_factors:{$performance->company_id}";
        Cache::forget($cacheKey);
    }

    protected function analyzeOverridePattern(HiringDecisionOverride $override): array
    {
        return [
            'type' => $override->override_type,
            'factors' => $override->override_factors ?? [],
            'confidence' => $override->confidence_level
        ];
    }

    protected function updateManagerPreferences(int $companyId, int $managerId, array $pattern): void
    {
        // Store manager preference patterns
        $cacheKey = "manager_preferences:{$companyId}:{$managerId}";
        $preferences = Cache::get($cacheKey, []);
        $preferences[] = $pattern;
        Cache::put($cacheKey, $preferences, 86400);
    }

    protected function isFrequentOverridePattern(int $companyId, array $pattern): bool
    {
        $recentOverrides = HiringDecisionOverride::where('company_id', $companyId)
            ->where('override_type', $pattern['type'])
            ->where('created_at', '>', now()->subMonths(3))
            ->count();

        return $recentOverrides >= 5;
    }

    protected function analyzePerformanceTrends(int $companyId): array
    {
        return [
            'trend' => 'stable',
            'avg_rating' => HirePerformance::where('company_id', $companyId)->avg('performance_rating')
        ];
    }

    protected function analyzeOverridePatterns(int $companyId): array
    {
        return [
            'total_overrides' => HiringDecisionOverride::where('company_id', $companyId)->count(),
            'most_common_type' => 'hire_despite_caution'
        ];
    }

    protected function analyzeDNAEvolution(int $companyId): array
    {
        return [
            'evolution' => 'Company DNA evolving based on performance data',
            'key_changes' => []
        ];
    }

    protected function generateLearningSummary(Company $company, array $data): string
    {
        return "Learning system is analyzing hiring patterns and refining predictions for {$company->name}.";
    }

    protected function generateRecommendations(int $companyId, string $aiSummary): array
    {
        return [
            'Continue tracking performance data',
            'Review assessment criteria quarterly',
            'Consider automated refinements'
        ];
    }

    protected function detectSeasonality(array $monthlyData): string
    {
        return 'No significant seasonality detected';
    }

    protected function projectGrowth(float $growthRate): float
    {
        return $growthRate * 1.1; // Simple projection
    }

    protected function alignWithIndustryTrends(array $skills, array $trends): array
    {
        return array_slice($skills, 0, 5, true);
    }

    protected function buildPredictionPrompt(Company $company, array $historicalData, array $growthTrends, array $industryTrends, array $emergingSkills, int $timeHorizon): string
    {
        return "Predict talent needs for {$company->name} over the next {$timeHorizon} months based on historical hiring patterns and industry trends.";
    }

    protected function parsePredictionResponse(string $aiAnalysis, array $historicalData, array $growthTrends): array
    {
        return [
            'predicted_roles' => [],
            'predicted_headcount' => ceil($historicalData['recent_6mo_count'] * 1.2),
            'confidence_score' => 75.0,
            'recommendations' => [],
            'ai_analysis' => $aiAnalysis
        ];
    }

    protected function generateRuleBasedPredictions(array $historicalData, array $growthTrends, int $timeHorizon): array
    {
        return [
            'predicted_roles' => [],
            'predicted_headcount' => ceil($historicalData['recent_6mo_count'] * ($timeHorizon / 6)),
            'confidence_score' => 60.0,
            'recommendations' => ['Collect more historical data for better predictions'],
            'ai_analysis' => 'Rule-based prediction applied.'
        ];
    }

    protected function buildPredictionBasis(array $historicalData, array $growthTrends, array $industryTrends): array
    {
        return [
            'historical_growth_rate' => $historicalData['growth_rate'],
            'trend_direction' => $growthTrends['trend_direction'],
            'confidence' => $growthTrends['confidence']
        ];
    }
}
