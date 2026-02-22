<?php

namespace App\Services\AI\Scout;

use App\Models\Application;
use App\Models\Job;
use App\Models\BehavioralAssessment;
use App\Models\SituationalScenario;
use App\Models\ScenarioResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Behavioral and Situational Intelligence Service
 * 
 * Conducts sophisticated behavioral assessments through AI-powered analysis:
 * - Generates situational judgment tests based on real workplace scenarios
 * - Evaluates cultural fit and alignment with company values
 * - Analyzes communication patterns and emotional intelligence
 * - Assesses leadership potential and decision-making style
 * - Identifies candidates who would thrive vs struggle in specific environments
 */
class BehavioralIntelligenceService
{
    /**
     * Cultural fit scoring thresholds
     */
    const EXCELLENT_FIT = 85;
    const GOOD_FIT = 70;
    const MODERATE_FIT = 55;
    const POOR_FIT = 40;

    /**
     * Emotional intelligence dimensions
     */
    const EI_DIMENSIONS = [
        'self_awareness',
        'self_regulation',
        'empathy',
        'social_skills',
        'motivation'
    ];

    /**
     * Leadership competencies
     */
    const LEADERSHIP_COMPETENCIES = [
        'strategic_thinking',
        'people_management',
        'decision_making',
        'conflict_resolution',
        'vision_communication',
        'change_management'
    ];

    /**
     * Communication pattern indicators
     */
    const COMMUNICATION_PATTERNS = [
        'clarity',
        'diplomacy',
        'assertiveness',
        'active_listening',
        'adaptability'
    ];

    /**
     * Generate comprehensive behavioral assessment with situational scenarios
     *
     * @param int $applicationId
     * @param int $jobId
     * @param array $options Configuration options
     * @return array Assessment data
     */
    public function generateBehavioralAssessment(int $applicationId, int $jobId, array $options = []): array
    {
        try {
            Log::info('Generating behavioral assessment', [
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'options' => $options
            ]);

            // Load application and job data
            $application = Application::with(['user.profile', 'user.experiences', 'user.educations'])
                ->findOrFail($applicationId);
            
            $job = Job::with(['company'])->findOrFail($jobId);

            // Validate application belongs to job
            if ($application->job_id !== $job->id) {
                throw new \Exception("Application does not belong to the specified job");
            }

            // Extract company culture context
            $companyCulture = $this->extractCompanyCulture($job);

            // Generate workplace scenarios based on company culture
            $scenarioCount = $options['scenario_count'] ?? 6;
            $scenarios = $this->generateSituationalScenarios(
                $job,
                $companyCulture,
                $scenarioCount,
                $options
            );

            // Create behavioral assessment record
            $assessment = BehavioralAssessment::create([
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'company_id' => $job->company_id,
                'status' => 'pending',
                'scenario_count' => $scenarioCount,
                'company_culture_context' => $companyCulture,
                'assessment_type' => $options['type'] ?? 'comprehensive',
                'focus_areas' => $options['focus_areas'] ?? ['cultural_fit', 'emotional_intelligence', 'leadership'],
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'generated_by' => auth()->id(),
                    'options' => $options
                ]
            ]);

            // Store scenarios
            foreach ($scenarios as $index => $scenarioData) {
                SituationalScenario::create([
                    'behavioral_assessment_id' => $assessment->id,
                    'scenario_number' => $index + 1,
                    'title' => $scenarioData['title'],
                    'context' => $scenarioData['context'],
                    'situation' => $scenarioData['situation'],
                    'category' => $scenarioData['category'],
                    'difficulty_level' => $scenarioData['difficulty_level'],
                    'valid_approaches' => $scenarioData['valid_approaches'],
                    'preferred_approach' => $scenarioData['preferred_approach'],
                    'cultural_alignment_weights' => $scenarioData['cultural_alignment_weights'],
                    'evaluates_dimensions' => $scenarioData['evaluates_dimensions'],
                    'metadata' => $scenarioData['metadata'] ?? []
                ]);
            }

            Log::info('Behavioral assessment generated successfully', [
                'assessment_id' => $assessment->id,
                'scenario_count' => $scenarioCount
            ]);

            return [
                'assessment_id' => $assessment->id,
                'scenario_count' => $scenarioCount,
                'company_culture_summary' => $companyCulture['summary'] ?? '',
                'focus_areas' => $assessment->focus_areas,
                'first_scenario' => $scenarios[0] ?? null,
                'estimated_time' => $scenarioCount * 5 // 5 minutes per scenario
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate behavioral assessment', [
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extract and analyze company culture from job and company data
     *
     * @param Job $job
     * @return array Culture analysis
     */
    protected function extractCompanyCulture(Job $job): array
    {
        $cacheKey = "company_culture_{$job->company_id}";

        return Cache::remember($cacheKey, 3600, function() use ($job) {
            try {
                // Build company culture context from available data
                $culturalData = $this->buildCulturalContext($job);

                // Use AI to analyze and extract cultural values
                $response = OpenAI::chat()->create([
                    'model' => 'gpt-5',
                    'temperature' => 0.4,
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert organizational psychologist and culture analyst. Analyze company information and extract core cultural values, work environment characteristics, leadership style preferences, and behavioral expectations.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Analyze the following company and job information to extract cultural profile:\n\n" . 
                                        json_encode($culturalData, JSON_PRETTY_PRINT) . 
                                        "\n\nProvide a comprehensive cultural analysis including:\n" .
                                        "1. Core values and principles\n" .
                                        "2. Work environment characteristics\n" .
                                        "3. Leadership and management style\n" .
                                        "4. Decision-making approach\n" .
                                        "5. Communication preferences\n" .
                                        "6. Team dynamics expectations\n" .
                                        "7. Innovation vs stability orientation\n" .
                                        "8. Work-life balance philosophy\n\n" .
                                        "Return as structured JSON."
                        ]
                    ]
                ]);

                $analysis = json_decode($response->choices[0]->message->content, true);

                return [
                    'summary' => $analysis['summary'] ?? '',
                    'core_values' => $analysis['core_values'] ?? [],
                    'work_environment' => $analysis['work_environment'] ?? [],
                    'leadership_style' => $analysis['leadership_style'] ?? '',
                    'decision_making' => $analysis['decision_making'] ?? '',
                    'communication_style' => $analysis['communication_style'] ?? '',
                    'team_dynamics' => $analysis['team_dynamics'] ?? [],
                    'innovation_orientation' => $analysis['innovation_orientation'] ?? 'balanced',
                    'work_life_balance' => $analysis['work_life_balance'] ?? 'moderate',
                    'raw_data' => $culturalData
                ];

            } catch (\Exception $e) {
                Log::error('Failed to extract company culture', [
                    'company_id' => $job->company_id,
                    'error' => $e->getMessage()
                ]);

                // Return default cultural context
                return [
                    'summary' => 'Standard professional environment',
                    'core_values' => ['integrity', 'collaboration', 'excellence'],
                    'work_environment' => ['professional', 'team-oriented'],
                    'leadership_style' => 'collaborative',
                    'decision_making' => 'consensus-based',
                    'communication_style' => 'open and transparent',
                    'team_dynamics' => ['cooperative', 'supportive'],
                    'innovation_orientation' => 'balanced',
                    'work_life_balance' => 'moderate'
                ];
            }
        });
    }

    /**
     * Build cultural context from job and company data
     *
     * @param Job $job
     * @return array
     */
    protected function buildCulturalContext(Job $job): array
    {
        return [
            'company' => [
                'name' => $job->company->name ?? '',
                'industry' => $job->company->industry ?? '',
                'size' => $job->company->size ?? '',
                'description' => $job->company->description ?? '',
                'mission' => $job->company->mission ?? '',
                'values' => $job->company->values ?? []
            ],
            'job' => [
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->requirements ?? [],
                'responsibilities' => $job->responsibilities ?? [],
                'team_structure' => $job->team_structure ?? '',
                'reporting_to' => $job->reporting_to ?? '',
                'work_arrangement' => $job->work_arrangement ?? 'hybrid'
            ]
        ];
    }

    /**
     * Generate situational judgment scenarios based on company culture
     *
     * @param Job $job
     * @param array $companyCulture
     * @param int $count
     * @param array $options
     * @return array Scenarios
     */
    protected function generateSituationalScenarios(Job $job, array $companyCulture, int $count, array $options): array
    {
        try {
            $focusAreas = $options['focus_areas'] ?? ['cultural_fit', 'emotional_intelligence', 'leadership'];
            $scenarioCategories = $this->determineScenarioCategories($focusAreas, $count);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'temperature' => 0.8, // Higher for variety
                'max_tokens' => 4000,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in behavioral assessment and situational judgment tests. Create realistic workplace scenarios that evaluate cultural fit, emotional intelligence, and leadership potential. Each scenario should have multiple valid approaches with different cultural alignments.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate {$count} situational judgment scenarios for the following role and company culture:\n\n" .
                                    "**Job Role:** {$job->title}\n\n" .
                                    "**Company Culture:**\n" . json_encode($companyCulture, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Scenario Categories:** " . json_encode($scenarioCategories) . "\n\n" .
                                    "**Focus Areas:** " . implode(', ', $focusAreas) . "\n\n" .
                                    "For each scenario, provide:\n" .
                                    "1. Title: Brief scenario name\n" .
                                    "2. Context: Background information and stakeholders\n" .
                                    "3. Situation: The specific challenge or decision point\n" .
                                    "4. Category: Type of scenario (conflict, decision-making, leadership, etc.)\n" .
                                    "5. Difficulty Level: easy, medium, hard, expert\n" .
                                    "6. Valid Approaches: Array of 4-5 different valid response approaches, each with:\n" .
                                    "   - Approach description\n" .
                                    "   - Cultural alignment score (0-100) based on company culture\n" .
                                    "   - Strengths of this approach\n" .
                                    "   - Potential concerns\n" .
                                    "   - EI dimensions demonstrated\n" .
                                    "   - Leadership competencies shown\n" .
                                    "7. Preferred Approach: Which approach best aligns with this company's culture\n" .
                                    "8. Cultural Alignment Weights: How to score different approaches\n" .
                                    "9. Evaluates Dimensions: Which EI/leadership dimensions this scenario tests\n\n" .
                                    "Make scenarios realistic, relevant to the role, and tied to actual company culture values. " .
                                    "Ensure multiple approaches are genuinely valid but differ in cultural fit.\n\n" .
                                    "Return as JSON array."
                    ]
                ]
            ]);

            $scenarios = json_decode($response->choices[0]->message->content, true);

            if (!is_array($scenarios)) {
                throw new \Exception("Invalid scenarios format from AI");
            }

            // Ensure each scenario has required structure
            return array_map(function($scenario, $index) {
                return [
                    'title' => $scenario['title'] ?? "Scenario " . ($index + 1),
                    'context' => $scenario['context'] ?? '',
                    'situation' => $scenario['situation'] ?? '',
                    'category' => $scenario['category'] ?? 'general',
                    'difficulty_level' => $scenario['difficulty_level'] ?? 'medium',
                    'valid_approaches' => $scenario['valid_approaches'] ?? [],
                    'preferred_approach' => $scenario['preferred_approach'] ?? 0,
                    'cultural_alignment_weights' => $scenario['cultural_alignment_weights'] ?? [],
                    'evaluates_dimensions' => $scenario['evaluates_dimensions'] ?? [],
                    'metadata' => [
                        'generated_at' => now()->toIso8601String(),
                        'ai_model' => 'gpt-5'
                    ]
                ];
            }, $scenarios, array_keys($scenarios));

        } catch (\Exception $e) {
            Log::error('Failed to generate situational scenarios', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Determine scenario categories based on focus areas
     *
     * @param array $focusAreas
     * @param int $count
     * @return array
     */
    protected function determineScenarioCategories(array $focusAreas, int $count): array
    {
        $categoryPool = [
            'cultural_fit' => ['team_conflict', 'work_style_alignment', 'value_decision'],
            'emotional_intelligence' => ['interpersonal_conflict', 'feedback_handling', 'stress_management'],
            'leadership' => ['team_motivation', 'difficult_decision', 'change_management'],
            'communication' => ['stakeholder_management', 'presentation_challenge', 'difficult_conversation'],
            'problem_solving' => ['resource_constraint', 'competing_priorities', 'crisis_management']
        ];

        $categories = [];
        foreach ($focusAreas as $area) {
            if (isset($categoryPool[$area])) {
                $categories = array_merge($categories, $categoryPool[$area]);
            }
        }

        // If not enough categories, add general scenarios
        if (count($categories) < $count) {
            $categories = array_merge($categories, array_fill(0, $count - count($categories), 'general'));
        }

        return array_slice($categories, 0, $count);
    }

    /**
     * Evaluate candidate's response to a situational scenario
     *
     * @param int $assessmentId
     * @param int $scenarioId
     * @param array $responseData
     * @return array Evaluation results
     */
    public function evaluateScenarioResponse(int $assessmentId, int $scenarioId, array $responseData): array
    {
        try {
            Log::info('Evaluating scenario response', [
                'assessment_id' => $assessmentId,
                'scenario_id' => $scenarioId
            ]);

            $assessment = BehavioralAssessment::findOrFail($assessmentId);
            $scenario = SituationalScenario::findOrFail($scenarioId);

            // Validate scenario belongs to assessment
            if ($scenario->behavioral_assessment_id !== $assessment->id) {
                throw new \Exception("Scenario does not belong to this assessment");
            }

            // Check if already answered
            $existingResponse = ScenarioResponse::where('behavioral_assessment_id', $assessmentId)
                ->where('situational_scenario_id', $scenarioId)
                ->first();

            if ($existingResponse) {
                throw new \Exception("Scenario already answered");
            }

            $selectedApproach = $responseData['selected_approach'] ?? null;
            $reasoning = $responseData['reasoning'] ?? '';
            $timeTaken = $responseData['time_taken'] ?? 0;

            // Evaluate the response using AI
            $evaluation = $this->evaluateWithAI(
                $scenario,
                $selectedApproach,
                $reasoning,
                $assessment->company_culture_context
            );

            // Store response
            $response = ScenarioResponse::create([
                'behavioral_assessment_id' => $assessmentId,
                'situational_scenario_id' => $scenarioId,
                'selected_approach' => $selectedApproach,
                'reasoning' => $reasoning,
                'time_taken' => $timeTaken,
                'cultural_alignment_score' => $evaluation['cultural_alignment_score'],
                'approach_quality_score' => $evaluation['approach_quality_score'],
                'reasoning_quality_score' => $evaluation['reasoning_quality_score'],
                'overall_score' => $evaluation['overall_score'],
                'ei_dimensions_demonstrated' => $evaluation['ei_dimensions_demonstrated'],
                'leadership_competencies_shown' => $evaluation['leadership_competencies_shown'],
                'communication_patterns_detected' => $evaluation['communication_patterns_detected'],
                'strengths_identified' => $evaluation['strengths_identified'],
                'areas_for_improvement' => $evaluation['areas_for_improvement'],
                'ai_feedback' => $evaluation['feedback'],
                'metadata' => [
                    'evaluated_at' => now()->toIso8601String(),
                    'evaluation_model' => 'gpt-5'
                ]
            ]);

            // Update assessment progress
            $this->updateAssessmentProgress($assessment);

            // Calculate current performance metrics
            $performanceMetrics = $this->calculateCurrentPerformance($assessment);

            // Determine if assessment is complete
            $totalScenarios = $assessment->scenario_count;
            $completedScenarios = ScenarioResponse::where('behavioral_assessment_id', $assessmentId)->count();
            $isComplete = $completedScenarios >= $totalScenarios;

            if ($isComplete) {
                $assessment->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                // Generate final results
                $finalResults = $this->generateFinalResults($assessment);

                return [
                    'is_complete' => true,
                    'evaluation' => $evaluation,
                    'performance_metrics' => $performanceMetrics,
                    'final_results' => $finalResults
                ];
            }

            // Get next scenario
            $nextScenario = $this->getNextScenario($assessment, $completedScenarios + 1);

            return [
                'is_complete' => false,
                'evaluation' => $evaluation,
                'performance_metrics' => $performanceMetrics,
                'next_scenario' => $nextScenario,
                'progress' => [
                    'completed' => $completedScenarios,
                    'total' => $totalScenarios,
                    'percentage' => round(($completedScenarios / $totalScenarios) * 100, 1)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to evaluate scenario response', [
                'assessment_id' => $assessmentId,
                'scenario_id' => $scenarioId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Evaluate response using AI
     *
     * @param SituationalScenario $scenario
     * @param int|null $selectedApproach
     * @param string $reasoning
     * @param array $companyCulture
     * @return array Evaluation
     */
    protected function evaluateWithAI(SituationalScenario $scenario, ?int $selectedApproach, string $reasoning, array $companyCulture): array
    {
        try {
            $validApproaches = $scenario->valid_approaches;
            $selectedApproachData = $validApproaches[$selectedApproach] ?? null;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'temperature' => 0.3,
                'max_tokens' => 1500,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert in behavioral assessment and organizational psychology. Evaluate situational judgment responses for cultural fit, emotional intelligence, leadership potential, and communication effectiveness.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Evaluate the following response to a situational scenario:\n\n" .
                                    "**Scenario:**\n" .
                                    "Title: {$scenario->title}\n" .
                                    "Context: {$scenario->context}\n" .
                                    "Situation: {$scenario->situation}\n\n" .
                                    "**Company Culture:**\n" . json_encode($companyCulture, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Selected Approach:**\n" . json_encode($selectedApproachData, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Candidate's Reasoning:**\n{$reasoning}\n\n" .
                                    "**Preferred Approach for This Culture:**\n" . 
                                    json_encode($validApproaches[$scenario->preferred_approach] ?? null, JSON_PRETTY_PRINT) . "\n\n" .
                                    "Provide:\n" .
                                    "1. Cultural Alignment Score (0-100): How well does this align with company culture?\n" .
                                    "2. Approach Quality Score (0-100): Is this a valid/effective approach?\n" .
                                    "3. Reasoning Quality Score (0-100): Quality of explanation and thought process\n" .
                                    "4. Overall Score (0-100): Weighted average\n" .
                                    "5. EI Dimensions Demonstrated: Which emotional intelligence dimensions were shown\n" .
                                    "6. Leadership Competencies Shown: Which leadership skills were demonstrated\n" .
                                    "7. Communication Patterns Detected: Communication style indicators\n" .
                                    "8. Strengths Identified: What was done well\n" .
                                    "9. Areas for Improvement: Constructive feedback\n" .
                                    "10. Feedback: Comprehensive evaluation message\n\n" .
                                    "Return as JSON."
                    ]
                ]
            ]);

            $evaluation = json_decode($response->choices[0]->message->content, true);

            return [
                'cultural_alignment_score' => $evaluation['cultural_alignment_score'] ?? 50,
                'approach_quality_score' => $evaluation['approach_quality_score'] ?? 50,
                'reasoning_quality_score' => $evaluation['reasoning_quality_score'] ?? 50,
                'overall_score' => $evaluation['overall_score'] ?? 50,
                'ei_dimensions_demonstrated' => $evaluation['ei_dimensions_demonstrated'] ?? [],
                'leadership_competencies_shown' => $evaluation['leadership_competencies_shown'] ?? [],
                'communication_patterns_detected' => $evaluation['communication_patterns_detected'] ?? [],
                'strengths_identified' => $evaluation['strengths_identified'] ?? [],
                'areas_for_improvement' => $evaluation['areas_for_improvement'] ?? [],
                'feedback' => $evaluation['feedback'] ?? 'Response evaluated successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('AI evaluation failed', ['error' => $e->getMessage()]);

            // Fallback to basic evaluation
            return $this->basicEvaluation($scenario, $selectedApproach);
        }
    }

    /**
     * Basic evaluation fallback (without AI)
     *
     * @param SituationalScenario $scenario
     * @param int|null $selectedApproach
     * @return array
     */
    protected function basicEvaluation(SituationalScenario $scenario, ?int $selectedApproach): array
    {
        $validApproaches = $scenario->valid_approaches;
        $selectedApproachData = $validApproaches[$selectedApproach] ?? null;

        if (!$selectedApproachData) {
            return [
                'cultural_alignment_score' => 0,
                'approach_quality_score' => 0,
                'reasoning_quality_score' => 0,
                'overall_score' => 0,
                'ei_dimensions_demonstrated' => [],
                'leadership_competencies_shown' => [],
                'communication_patterns_detected' => [],
                'strengths_identified' => [],
                'areas_for_improvement' => ['Invalid approach selected'],
                'feedback' => 'Please select a valid approach.'
            ];
        }

        $culturalScore = $selectedApproachData['cultural_alignment_score'] ?? 50;
        $isPreferred = $selectedApproach === $scenario->preferred_approach;

        return [
            'cultural_alignment_score' => $culturalScore,
            'approach_quality_score' => $isPreferred ? 90 : 70,
            'reasoning_quality_score' => 50,
            'overall_score' => round(($culturalScore + ($isPreferred ? 90 : 70) + 50) / 3),
            'ei_dimensions_demonstrated' => $selectedApproachData['ei_dimensions_demonstrated'] ?? [],
            'leadership_competencies_shown' => $selectedApproachData['leadership_competencies_shown'] ?? [],
            'communication_patterns_detected' => [],
            'strengths_identified' => $selectedApproachData['strengths'] ?? [],
            'areas_for_improvement' => $selectedApproachData['potential_concerns'] ?? [],
            'feedback' => 'Your approach has been evaluated based on cultural alignment.'
        ];
    }

    /**
     * Update assessment progress after each response
     *
     * @param BehavioralAssessment $assessment
     * @return void
     */
    protected function updateAssessmentProgress(BehavioralAssessment $assessment): void
    {
        $responses = ScenarioResponse::where('behavioral_assessment_id', $assessment->id)->get();

        if ($responses->isEmpty()) {
            return;
        }

        $avgCulturalFit = $responses->avg('cultural_alignment_score');
        $avgApproachQuality = $responses->avg('approach_quality_score');
        $avgReasoningQuality = $responses->avg('reasoning_quality_score');
        $avgOverallScore = $responses->avg('overall_score');

        $assessment->update([
            'cultural_fit_score' => round($avgCulturalFit, 1),
            'approach_quality_score' => round($avgApproachQuality, 1),
            'reasoning_quality_score' => round($avgReasoningQuality, 1),
            'overall_score' => round($avgOverallScore, 1),
            'status' => 'in_progress'
        ]);
    }

    /**
     * Calculate current performance metrics
     *
     * @param BehavioralAssessment $assessment
     * @return array
     */
    protected function calculateCurrentPerformance(BehavioralAssessment $assessment): array
    {
        $responses = ScenarioResponse::where('behavioral_assessment_id', $assessment->id)->get();

        if ($responses->isEmpty()) {
            return [
                'cultural_fit_score' => 0,
                'emotional_intelligence_score' => 0,
                'leadership_score' => 0,
                'communication_score' => 0,
                'overall_score' => 0,
                'responses_completed' => 0
            ];
        }

        // Aggregate EI dimensions
        $eiDimensions = [];
        foreach ($responses as $response) {
            $demonstrated = $response->ei_dimensions_demonstrated ?? [];
            foreach ($demonstrated as $dimension) {
                $eiDimensions[$dimension] = ($eiDimensions[$dimension] ?? 0) + 1;
            }
        }

        // Aggregate leadership competencies
        $leadershipCompetencies = [];
        foreach ($responses as $response) {
            $shown = $response->leadership_competencies_shown ?? [];
            foreach ($shown as $competency) {
                $leadershipCompetencies[$competency] = ($leadershipCompetencies[$competency] ?? 0) + 1;
            }
        }

        // Calculate scores
        $culturalFitScore = $responses->avg('cultural_alignment_score');
        $eiScore = count($eiDimensions) > 0 ? (array_sum($eiDimensions) / count($eiDimensions)) * 10 : 0;
        $leadershipScore = count($leadershipCompetencies) > 0 ? (array_sum($leadershipCompetencies) / count($leadershipCompetencies)) * 10 : 0;
        $communicationScore = $responses->avg('reasoning_quality_score');

        return [
            'cultural_fit_score' => round($culturalFitScore, 1),
            'emotional_intelligence_score' => round(min($eiScore, 100), 1),
            'leadership_score' => round(min($leadershipScore, 100), 1),
            'communication_score' => round($communicationScore, 1),
            'overall_score' => round($responses->avg('overall_score'), 1),
            'responses_completed' => $responses->count(),
            'ei_dimensions_breakdown' => $eiDimensions,
            'leadership_competencies_breakdown' => $leadershipCompetencies
        ];
    }

    /**
     * Get next scenario for assessment
     *
     * @param BehavioralAssessment $assessment
     * @param int $scenarioNumber
     * @return array|null
     */
    protected function getNextScenario(BehavioralAssessment $assessment, int $scenarioNumber): ?array
    {
        $scenario = SituationalScenario::where('behavioral_assessment_id', $assessment->id)
            ->where('scenario_number', $scenarioNumber)
            ->first();

        if (!$scenario) {
            return null;
        }

        return [
            'scenario_id' => $scenario->id,
            'scenario_number' => $scenario->scenario_number,
            'title' => $scenario->title,
            'context' => $scenario->context,
            'situation' => $scenario->situation,
            'category' => $scenario->category,
            'difficulty_level' => $scenario->difficulty_level,
            'valid_approaches' => $scenario->valid_approaches,
            'evaluates_dimensions' => $scenario->evaluates_dimensions
        ];
    }

    /**
     * Generate comprehensive final results with cultural fit analysis
     *
     * @param BehavioralAssessment $assessment
     * @return array
     */
    public function generateFinalResults(BehavioralAssessment $assessment): array
    {
        try {
            Log::info('Generating final behavioral assessment results', [
                'assessment_id' => $assessment->id
            ]);

            $responses = ScenarioResponse::where('behavioral_assessment_id', $assessment->id)
                ->with('scenario')
                ->get();

            $performanceMetrics = $this->calculateCurrentPerformance($assessment);

            // Analyze cultural fit deeply
            $culturalFitAnalysis = $this->analyzeCulturalFit($assessment, $responses, $performanceMetrics);

            // Assess emotional intelligence
            $eiAssessment = $this->assessEmotionalIntelligence($responses);

            // Evaluate leadership potential
            $leadershipEvaluation = $this->evaluateLeadership($responses);

            // Analyze communication patterns
            $communicationAnalysis = $this->analyzeCommunicationPatterns($responses);

            // Determine thriving likelihood
            $thrivingLikelihood = $this->determineThrivingLikelihood(
                $performanceMetrics,
                $culturalFitAnalysis,
                $eiAssessment,
                $leadershipEvaluation
            );

            // Generate AI-powered comprehensive insights
            $comprehensiveInsights = $this->generateComprehensiveInsights(
                $assessment,
                $responses,
                $performanceMetrics,
                $culturalFitAnalysis,
                $eiAssessment,
                $leadershipEvaluation,
                $communicationAnalysis,
                $thrivingLikelihood
            );

            $finalResults = [
                'overall_score' => $performanceMetrics['overall_score'],
                'cultural_fit_score' => $performanceMetrics['cultural_fit_score'],
                'cultural_fit_level' => $culturalFitAnalysis['fit_level'],
                'emotional_intelligence_score' => $performanceMetrics['emotional_intelligence_score'],
                'emotional_intelligence_level' => $eiAssessment['ei_level'],
                'leadership_score' => $performanceMetrics['leadership_score'],
                'leadership_potential' => $leadershipEvaluation['leadership_potential'],
                'communication_score' => $performanceMetrics['communication_score'],
                'communication_style' => $communicationAnalysis['communication_style'],
                'thriving_likelihood' => $thrivingLikelihood['likelihood'],
                'thriving_probability' => $thrivingLikelihood['probability'],
                'recommendation' => $thrivingLikelihood['recommendation'],
                'cultural_fit_analysis' => $culturalFitAnalysis,
                'emotional_intelligence_assessment' => $eiAssessment,
                'leadership_evaluation' => $leadershipEvaluation,
                'communication_analysis' => $communicationAnalysis,
                'comprehensive_insights' => $comprehensiveInsights,
                'performance_breakdown' => $performanceMetrics,
                'scenario_responses' => $responses->map(function($response) {
                    return [
                        'scenario_title' => $response->scenario->title,
                        'category' => $response->scenario->category,
                        'selected_approach' => $response->selected_approach,
                        'reasoning' => $response->reasoning,
                        'scores' => [
                            'cultural_alignment' => $response->cultural_alignment_score,
                            'approach_quality' => $response->approach_quality_score,
                            'reasoning_quality' => $response->reasoning_quality_score,
                            'overall' => $response->overall_score
                        ],
                        'feedback' => $response->ai_feedback
                    ];
                })
            ];

            // Update assessment with final results
            $assessment->update([
                'cultural_fit_score' => $performanceMetrics['cultural_fit_score'],
                'emotional_intelligence_score' => $performanceMetrics['emotional_intelligence_score'],
                'leadership_score' => $performanceMetrics['leadership_score'],
                'communication_score' => $performanceMetrics['communication_score'],
                'overall_score' => $performanceMetrics['overall_score'],
                'thriving_likelihood' => $thrivingLikelihood['likelihood'],
                'recommendation' => $thrivingLikelihood['recommendation'],
                'final_insights' => $comprehensiveInsights,
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return $finalResults;

        } catch (\Exception $e) {
            Log::error('Failed to generate final results', [
                'assessment_id' => $assessment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Analyze cultural fit in depth
     *
     * @param BehavioralAssessment $assessment
     * @param \Illuminate\Support\Collection $responses
     * @param array $performanceMetrics
     * @return array
     */
    protected function analyzeCulturalFit(BehavioralAssessment $assessment, $responses, array $performanceMetrics): array
    {
        $culturalFitScore = $performanceMetrics['cultural_fit_score'];

        $fitLevel = match(true) {
            $culturalFitScore >= self::EXCELLENT_FIT => 'Excellent Fit',
            $culturalFitScore >= self::GOOD_FIT => 'Good Fit',
            $culturalFitScore >= self::MODERATE_FIT => 'Moderate Fit',
            $culturalFitScore >= self::POOR_FIT => 'Poor Fit',
            default => 'Misalignment'
        };

        // Analyze alignment with specific cultural values
        $companyCulture = $assessment->company_culture_context;
        $coreValues = $companyCulture['core_values'] ?? [];

        $valueAlignments = [];
        foreach ($responses as $response) {
            $alignmentScore = $response->cultural_alignment_score;
            $category = $response->scenario->category;
            $valueAlignments[$category] = ($valueAlignments[$category] ?? []);
            $valueAlignments[$category][] = $alignmentScore;
        }

        $categoryScores = [];
        foreach ($valueAlignments as $category => $scores) {
            $categoryScores[$category] = round(array_sum($scores) / count($scores), 1);
        }

        return [
            'fit_level' => $fitLevel,
            'fit_score' => $culturalFitScore,
            'category_alignment' => $categoryScores,
            'core_values_alignment' => $coreValues,
            'strengths' => $this->identifyFitStrengths($categoryScores, $companyCulture),
            'concerns' => $this->identifyFitConcerns($categoryScores, $companyCulture),
            'cultural_adaptation_potential' => $this->assessAdaptationPotential($culturalFitScore, $responses)
        ];
    }

    /**
     * Identify cultural fit strengths
     *
     * @param array $categoryScores
     * @param array $companyCulture
     * @return array
     */
    protected function identifyFitStrengths(array $categoryScores, array $companyCulture): array
    {
        $strengths = [];

        foreach ($categoryScores as $category => $score) {
            if ($score >= 75) {
                $strengths[] = "Strong alignment in " . str_replace('_', ' ', $category);
            }
        }

        if (empty($strengths)) {
            $strengths[] = "Demonstrates adaptability to organizational culture";
        }

        return $strengths;
    }

    /**
     * Identify cultural fit concerns
     *
     * @param array $categoryScores
     * @param array $companyCulture
     * @return array
     */
    protected function identifyFitConcerns(array $categoryScores, array $companyCulture): array
    {
        $concerns = [];

        foreach ($categoryScores as $category => $score) {
            if ($score < 50) {
                $concerns[] = "Potential misalignment in " . str_replace('_', ' ', $category);
            }
        }

        return $concerns;
    }

    /**
     * Assess cultural adaptation potential
     *
     * @param float $culturalFitScore
     * @param \Illuminate\Support\Collection $responses
     * @return string
     */
    protected function assessAdaptationPotential(float $culturalFitScore, $responses): string
    {
        $reasoningQuality = $responses->avg('reasoning_quality_score');

        if ($culturalFitScore >= 70 && $reasoningQuality >= 70) {
            return 'High - Strong cultural awareness and adaptability';
        } elseif ($culturalFitScore >= 55 || $reasoningQuality >= 70) {
            return 'Moderate - Can adapt with proper onboarding';
        } else {
            return 'Low - May struggle with cultural integration';
        }
    }

    /**
     * Assess emotional intelligence from responses
     *
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    protected function assessEmotionalIntelligence($responses): array
    {
        $eiDimensions = [];
        $totalDemonstrated = 0;

        foreach ($responses as $response) {
            $demonstrated = $response->ei_dimensions_demonstrated ?? [];
            foreach ($demonstrated as $dimension) {
                $eiDimensions[$dimension] = ($eiDimensions[$dimension] ?? 0) + 1;
                $totalDemonstrated++;
            }
        }

        $eiScore = $responses->count() > 0 
            ? min(100, ($totalDemonstrated / $responses->count()) * 20) 
            : 0;

        $eiLevel = match(true) {
            $eiScore >= 85 => 'Very High',
            $eiScore >= 70 => 'High',
            $eiScore >= 55 => 'Moderate',
            $eiScore >= 40 => 'Developing',
            default => 'Low'
        };

        return [
            'ei_score' => round($eiScore, 1),
            'ei_level' => $eiLevel,
            'dimensions_demonstrated' => $eiDimensions,
            'strongest_dimension' => !empty($eiDimensions) ? array_search(max($eiDimensions), $eiDimensions) : null,
            'development_areas' => $this->identifyEIDevelopmentAreas($eiDimensions)
        ];
    }

    /**
     * Identify EI development areas
     *
     * @param array $eiDimensions
     * @return array
     */
    protected function identifyEIDevelopmentAreas(array $eiDimensions): array
    {
        $allDimensions = self::EI_DIMENSIONS;
        $developmentAreas = [];

        foreach ($allDimensions as $dimension) {
            if (!isset($eiDimensions[$dimension]) || $eiDimensions[$dimension] < 2) {
                $developmentAreas[] = str_replace('_', ' ', ucfirst($dimension));
            }
        }

        return $developmentAreas;
    }

    /**
     * Evaluate leadership potential
     *
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    protected function evaluateLeadership($responses): array
    {
        $leadershipCompetencies = [];
        $totalShown = 0;

        foreach ($responses as $response) {
            $shown = $response->leadership_competencies_shown ?? [];
            foreach ($shown as $competency) {
                $leadershipCompetencies[$competency] = ($leadershipCompetencies[$competency] ?? 0) + 1;
                $totalShown++;
            }
        }

        $leadershipScore = $responses->count() > 0 
            ? min(100, ($totalShown / $responses->count()) * 16.67) 
            : 0;

        $leadershipPotential = match(true) {
            $leadershipScore >= 80 => 'Executive Level',
            $leadershipScore >= 65 => 'Senior Management',
            $leadershipScore >= 50 => 'Team Lead',
            $leadershipScore >= 35 => 'Emerging Leader',
            default => 'Individual Contributor'
        };

        return [
            'leadership_score' => round($leadershipScore, 1),
            'leadership_potential' => $leadershipPotential,
            'competencies_demonstrated' => $leadershipCompetencies,
            'strongest_competency' => !empty($leadershipCompetencies) ? array_search(max($leadershipCompetencies), $leadershipCompetencies) : null,
            'development_opportunities' => $this->identifyLeadershipDevelopment($leadershipCompetencies)
        ];
    }

    /**
     * Identify leadership development opportunities
     *
     * @param array $leadershipCompetencies
     * @return array
     */
    protected function identifyLeadershipDevelopment(array $leadershipCompetencies): array
    {
        $allCompetencies = self::LEADERSHIP_COMPETENCIES;
        $developmentAreas = [];

        foreach ($allCompetencies as $competency) {
            if (!isset($leadershipCompetencies[$competency]) || $leadershipCompetencies[$competency] < 2) {
                $developmentAreas[] = str_replace('_', ' ', ucwords($competency));
            }
        }

        return $developmentAreas;
    }

    /**
     * Analyze communication patterns
     *
     * @param \Illuminate\Support\Collection $responses
     * @return array
     */
    protected function analyzeCommunicationPatterns($responses): array
    {
        $communicationPatterns = [];
        
        foreach ($responses as $response) {
            $detected = $response->communication_patterns_detected ?? [];
            foreach ($detected as $pattern) {
                $communicationPatterns[$pattern] = ($communicationPatterns[$pattern] ?? 0) + 1;
            }
        }

        $communicationScore = $responses->avg('reasoning_quality_score');

        $communicationStyle = match(true) {
            isset($communicationPatterns['assertiveness']) && $communicationPatterns['assertiveness'] >= 3 => 'Direct and Assertive',
            isset($communicationPatterns['diplomacy']) && $communicationPatterns['diplomacy'] >= 3 => 'Diplomatic and Collaborative',
            isset($communicationPatterns['clarity']) && $communicationPatterns['clarity'] >= 3 => 'Clear and Structured',
            default => 'Balanced and Adaptive'
        };

        return [
            'communication_score' => round($communicationScore, 1),
            'communication_style' => $communicationStyle,
            'patterns_detected' => $communicationPatterns,
            'strongest_pattern' => !empty($communicationPatterns) ? array_search(max($communicationPatterns), $communicationPatterns) : null,
            'effectiveness_assessment' => $this->assessCommunicationEffectiveness($communicationScore, $communicationPatterns)
        ];
    }

    /**
     * Assess communication effectiveness
     *
     * @param float $score
     * @param array $patterns
     * @return string
     */
    protected function assessCommunicationEffectiveness(float $score, array $patterns): string
    {
        $patternCount = count($patterns);

        if ($score >= 80 && $patternCount >= 3) {
            return 'Highly effective - Adapts style to context';
        } elseif ($score >= 65) {
            return 'Effective - Clear and professional';
        } elseif ($score >= 50) {
            return 'Adequate - Room for development';
        } else {
            return 'Needs improvement - Unclear or incomplete';
        }
    }

    /**
     * Determine likelihood of thriving in organization
     *
     * @param array $performanceMetrics
     * @param array $culturalFitAnalysis
     * @param array $eiAssessment
     * @param array $leadershipEvaluation
     * @return array
     */
    protected function determineThrivingLikelihood(
        array $performanceMetrics,
        array $culturalFitAnalysis,
        array $eiAssessment,
        array $leadershipEvaluation
    ): array {
        // Weighted scoring: Cultural fit is most important
        $culturalWeight = 0.45;
        $eiWeight = 0.25;
        $leadershipWeight = 0.15;
        $communicationWeight = 0.15;

        $probability = (
            ($performanceMetrics['cultural_fit_score'] * $culturalWeight) +
            ($performanceMetrics['emotional_intelligence_score'] * $eiWeight) +
            ($performanceMetrics['leadership_score'] * $leadershipWeight) +
            ($performanceMetrics['communication_score'] * $communicationWeight)
        );

        $likelihood = match(true) {
            $probability >= 85 => 'Highly Likely to Thrive',
            $probability >= 70 => 'Likely to Thrive',
            $probability >= 55 => 'May Thrive with Support',
            $probability >= 40 => 'Uncertain - May Struggle',
            default => 'Likely to Struggle'
        };

        $recommendation = match(true) {
            $probability >= 80 => 'STRONG HIRE - Excellent cultural fit and high performance potential',
            $probability >= 65 => 'RECOMMEND - Good fit with strong capabilities',
            $probability >= 50 => 'CONSIDER - Moderate fit, may succeed with mentoring',
            $probability >= 35 => 'CAUTION - Significant concerns about cultural alignment',
            default => 'NOT RECOMMENDED - Poor cultural fit and high risk of failure'
        };

        return [
            'probability' => round($probability, 1),
            'likelihood' => $likelihood,
            'recommendation' => $recommendation,
            'key_factors' => $this->identifyKeyThrivingFactors($performanceMetrics, $culturalFitAnalysis),
            'risk_factors' => $this->identifyRiskFactors($performanceMetrics, $culturalFitAnalysis)
        ];
    }

    /**
     * Identify key factors supporting thriving
     *
     * @param array $performanceMetrics
     * @param array $culturalFitAnalysis
     * @return array
     */
    protected function identifyKeyThrivingFactors(array $performanceMetrics, array $culturalFitAnalysis): array
    {
        $factors = [];

        if ($performanceMetrics['cultural_fit_score'] >= 75) {
            $factors[] = 'Strong cultural alignment';
        }

        if ($performanceMetrics['emotional_intelligence_score'] >= 70) {
            $factors[] = 'High emotional intelligence';
        }

        if ($performanceMetrics['leadership_score'] >= 65) {
            $factors[] = 'Demonstrated leadership potential';
        }

        if (!empty($culturalFitAnalysis['strengths'])) {
            $factors = array_merge($factors, array_slice($culturalFitAnalysis['strengths'], 0, 2));
        }

        return $factors;
    }

    /**
     * Identify risk factors
     *
     * @param array $performanceMetrics
     * @param array $culturalFitAnalysis
     * @return array
     */
    protected function identifyRiskFactors(array $performanceMetrics, array $culturalFitAnalysis): array
    {
        $risks = [];

        if ($performanceMetrics['cultural_fit_score'] < 55) {
            $risks[] = 'Cultural misalignment may cause conflicts';
        }

        if ($performanceMetrics['emotional_intelligence_score'] < 50) {
            $risks[] = 'Limited emotional intelligence may impact team dynamics';
        }

        if (!empty($culturalFitAnalysis['concerns'])) {
            $risks = array_merge($risks, array_slice($culturalFitAnalysis['concerns'], 0, 2));
        }

        return $risks;
    }

    /**
     * Generate comprehensive insights using AI
     *
     * @param BehavioralAssessment $assessment
     * @param \Illuminate\Support\Collection $responses
     * @param array $performanceMetrics
     * @param array $culturalFitAnalysis
     * @param array $eiAssessment
     * @param array $leadershipEvaluation
     * @param array $communicationAnalysis
     * @param array $thrivingLikelihood
     * @return array
     */
    protected function generateComprehensiveInsights(
        BehavioralAssessment $assessment,
        $responses,
        array $performanceMetrics,
        array $culturalFitAnalysis,
        array $eiAssessment,
        array $leadershipEvaluation,
        array $communicationAnalysis,
        array $thrivingLikelihood
    ): array {
        try {
            $companyCulture = $assessment->company_culture_context;

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'temperature' => 0.5,
                'max_tokens' => 2000,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert organizational psychologist and talent assessment specialist. Provide comprehensive, nuanced insights about candidate fit and potential based on behavioral assessment data.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Provide comprehensive insights for this behavioral assessment:\n\n" .
                                    "**Company Culture:**\n" . json_encode($companyCulture, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Performance Metrics:**\n" . json_encode($performanceMetrics, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Cultural Fit Analysis:**\n" . json_encode($culturalFitAnalysis, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Emotional Intelligence:**\n" . json_encode($eiAssessment, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Leadership Evaluation:**\n" . json_encode($leadershipEvaluation, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Communication Analysis:**\n" . json_encode($communicationAnalysis, JSON_PRETTY_PRINT) . "\n\n" .
                                    "**Thriving Likelihood:**\n" . json_encode($thrivingLikelihood, JSON_PRETTY_PRINT) . "\n\n" .
                                    "Provide:\n" .
                                    "1. Executive Summary: 2-3 sentence overview\n" .
                                    "2. Key Strengths: Top 3-5 strengths for this specific environment\n" .
                                    "3. Development Areas: Top 3-5 areas for growth\n" .
                                    "4. Cultural Fit Assessment: Detailed analysis of alignment\n" .
                                    "5. Thriving Factors: What will help them succeed\n" .
                                    "6. Risk Mitigation: How to address concerns\n" .
                                    "7. Onboarding Recommendations: Specific suggestions\n" .
                                    "8. 90-Day Outlook: Predicted performance trajectory\n\n" .
                                    "Return as JSON."
                    ]
                ]
            ]);

            $insights = json_decode($response->choices[0]->message->content, true);

            return [
                'executive_summary' => $insights['executive_summary'] ?? 'Assessment completed successfully.',
                'key_strengths' => $insights['key_strengths'] ?? [],
                'development_areas' => $insights['development_areas'] ?? [],
                'cultural_fit_assessment' => $insights['cultural_fit_assessment'] ?? '',
                'thriving_factors' => $insights['thriving_factors'] ?? [],
                'risk_mitigation' => $insights['risk_mitigation'] ?? [],
                'onboarding_recommendations' => $insights['onboarding_recommendations'] ?? [],
                'ninety_day_outlook' => $insights['ninety_day_outlook'] ?? ''
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate comprehensive insights', [
                'error' => $e->getMessage()
            ]);

            return [
                'executive_summary' => 'Candidate assessed for cultural fit and behavioral alignment.',
                'key_strengths' => $thrivingLikelihood['key_factors'] ?? [],
                'development_areas' => [],
                'cultural_fit_assessment' => $culturalFitAnalysis['fit_level'] ?? 'Evaluated',
                'thriving_factors' => [],
                'risk_mitigation' => [],
                'onboarding_recommendations' => [],
                'ninety_day_outlook' => ''
            ];
        }
    }

    /**
     * Get behavioral assessment results
     *
     * @param int $assessmentId
     * @return array
     */
    public function getAssessmentResults(int $assessmentId): array
    {
        $assessment = BehavioralAssessment::with(['scenarios.response'])->findOrFail($assessmentId);

        if ($assessment->status === 'completed') {
            return $this->generateFinalResults($assessment);
        }

        // Return current progress if not completed
        $performanceMetrics = $this->calculateCurrentPerformance($assessment);

        return [
            'status' => $assessment->status,
            'current_performance' => $performanceMetrics,
            'completed_scenarios' => ScenarioResponse::where('behavioral_assessment_id', $assessmentId)->count(),
            'total_scenarios' => $assessment->scenario_count,
            'message' => 'Assessment in progress'
        ];
    }
}
