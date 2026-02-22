<?php

namespace App\Services\AI;

use App\Models\NegotiationStrategy;
use App\Models\NegotiationScenario;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class NegotiationScenarioService
{
    /**
     * Generate multiple negotiation scenarios
     */
    public function generateScenarios(NegotiationStrategy $strategy): array
    {
        $scenarios = [];

        // Generate 3-4 scenarios with different risk levels
        $scenarios[] = $this->generateConservativeScenario($strategy);
        $scenarios[] = $this->generateBalancedScenario($strategy);
        $scenarios[] = $this->generateAggressiveScenario($strategy);

        // Optional: Generate alternative benefits scenario if salary flexibility is low
        if ($strategy->company_negotiation_flexibility === 'low') {
            $scenarios[] = $this->generateAlternativeBenefitsScenario($strategy);
        }

        // Save all scenarios
        $savedScenarios = [];
        foreach ($scenarios as $index => $scenarioData) {
            $scenario = NegotiationScenario::create(array_merge($scenarioData, [
                'strategy_id' => $strategy->id,
                'scenario_order' => $index + 1,
            ]));
            $savedScenarios[] = $scenario;
        }

        return $savedScenarios;
    }

    /**
     * Generate conservative scenario (low risk)
     */
    protected function generateConservativeScenario(NegotiationStrategy $strategy): array
    {
        // Counter offer slightly above current offer, well-justified
        $counterOffer = $strategy->offered_salary * 1.05; // 5% increase
        $counterOffer = min($counterOffer, $strategy->optimal_ask * 0.95); // Cap at 95% of optimal

        $expectedOutcome = ($counterOffer + (float) $strategy->offered_salary) / 2;

        return [
            'scenario_name' => 'Conservative Approach',
            'counter_offer_amount' => round($counterOffer, 2),
            'additional_requests' => [],
            'counter_offer_justification' => 'Request modest increase based on market data and relevant experience, emphasizing enthusiasm for the role.',
            
            'predicted_response' => 'accept',
            'predicted_response_probability' => 75,
            'predicted_final_salary' => round($expectedOutcome, 2),
            'predicted_employer_counter' => 'Employer likely to accept or make minor adjustment',
            
            'risk_level' => 'low',
            'risk_score' => 20,
            'risk_factors' => [
                'Minimal risk of offer withdrawal',
                'May leave money on the table',
            ],
            'mitigation_strategies' => [
                'Frame request as market alignment',
                'Express strong interest in the role',
            ],
            
            'best_case_outcome' => round($counterOffer, 2),
            'expected_outcome' => round($expectedOutcome, 2),
            'worst_case_outcome' => (float) $strategy->offered_salary,
            
            'success_indicators' => [
                'Quick positive response from employer',
                'Manager acknowledges market data',
                'Counter-offer accepted or met halfway',
            ],
            'failure_indicators' => [
                'Delayed response',
                'Request for additional justification',
            ],
            
            'recommendation' => 'viable',
            'recommendation_rationale' => 'Safe approach with high probability of success, though may not maximize total compensation.',
            'confidence_score' => 85,
        ];
    }

    /**
     * Generate balanced scenario (recommended approach)
     */
    protected function generateBalancedScenario(NegotiationStrategy $strategy): array
    {
        // Counter offer at or near optimal ask
        $counterOffer = (float) $strategy->optimal_ask;
        $expectedOutcome = (($counterOffer + (float) $strategy->offered_salary) / 2) * 1.05;

        $additionalRequests = [];
        if ($strategy->hasAlternativeBenefits()) {
            $benefits = $strategy->benefits_to_negotiate;
            $additionalRequests = array_slice($benefits, 0, 2); // Include 2 alternative benefits
        }

        return [
            'scenario_name' => 'Balanced Approach',
            'counter_offer_amount' => round($counterOffer, 2),
            'additional_requests' => $additionalRequests,
            'counter_offer_justification' => 'Request salary aligned with market 65-70th percentile based on experience, skills, and value proposition. Include alternative compensation if salary is constrained.',
            
            'predicted_response' => 'negotiate',
            'predicted_response_probability' => 65,
            'predicted_final_salary' => round($expectedOutcome, 2),
            'predicted_employer_counter' => 'Employer likely to counter with mid-point or slightly below, potentially offering alternative benefits',
            
            'risk_level' => 'medium',
            'risk_score' => 40,
            'risk_factors' => [
                'May require multiple negotiation rounds',
                'Employer might push back on some requests',
            ],
            'mitigation_strategies' => [
                'Provide detailed justification with market data',
                'Show flexibility on benefit mix vs. pure salary',
                'Maintain collaborative tone throughout',
            ],
            
            'best_case_outcome' => round($counterOffer, 2),
            'expected_outcome' => round($expectedOutcome, 2),
            'worst_case_outcome' => round($strategy->offered_salary * 1.03, 2),
            
            'success_indicators' => [
                'Employer engages in dialogue',
                'Manager shares budget constraints',
                'Discussion of total compensation package',
            ],
            'failure_indicators' => [
                'Flat rejection without discussion',
                'Emphasis on "take it or leave it"',
            ],
            
            'recommendation' => 'recommended',
            'recommendation_rationale' => 'Optimal balance of risk and reward. Demonstrates professionalism while maximizing compensation. Most likely to achieve 85-95% of optimal outcome.',
            'confidence_score' => 75,
        ];
    }

    /**
     * Generate aggressive scenario (higher risk, higher reward)
     */
    protected function generateAggressiveScenario(NegotiationStrategy $strategy): array
    {
        // Counter offer at stretch goal
        $counterOffer = (float) $strategy->stretch_goal;
        $expectedOutcome = ($counterOffer + (float) $strategy->optimal_ask) / 2;

        return [
            'scenario_name' => 'Aggressive Approach',
            'counter_offer_amount' => round($counterOffer, 2),
            'additional_requests' => [
                'equity_package' => 'Stock options or RSUs',
                'sign_on_bonus' => 'Signing bonus to bridge gap',
            ],
            'counter_offer_justification' => 'Request top-tier compensation based on exceptional qualifications, unique value proposition, and market scarcity of skillset.',
            
            'predicted_response' => 'counter',
            'predicted_response_probability' => 45,
            'predicted_final_salary' => round($expectedOutcome, 2),
            'predicted_employer_counter' => 'Employer likely to counter significantly lower, but may meet optimal ask if justification is strong',
            
            'risk_level' => 'high',
            'risk_score' => 65,
            'risk_factors' => [
                'May be perceived as unrealistic',
                'Risk of damaging relationship before start',
                'Could trigger offer withdrawal if poorly executed',
            ],
            'mitigation_strategies' => [
                'Anchor high with exceptional value demonstration',
                'Be prepared to justify every dollar',
                'Show willingness to negotiate downward',
                'Have competing offer or strong alternatives',
            ],
            
            'best_case_outcome' => round($counterOffer, 2),
            'expected_outcome' => round($expectedOutcome, 2),
            'worst_case_outcome' => (float) $strategy->offered_salary,
            
            'success_indicators' => [
                'Employer takes request seriously',
                'Discussion of exceptional value proposition',
                'Willingness to explore creative compensation',
            ],
            'failure_indicators' => [
                'Immediate rejection',
                'Defensive or dismissive response',
                'Threat to withdraw offer',
            ],
            
            'recommendation' => count($strategy->strongest_points) >= 5 ? 'viable' : 'risky',
            'recommendation_rationale' => count($strategy->strongest_points) >= 5 
                ? 'Viable for exceptionally strong candidates with proven leverage. High risk but potential for maximum compensation.'
                : 'Not recommended without exceptional leverage. Risk outweighs potential benefit.',
            'confidence_score' => 40,
        ];
    }

    /**
     * Generate alternative benefits scenario (when salary inflexible)
     */
    protected function generateAlternativeBenefitsScenario(NegotiationStrategy $strategy): array
    {
        $counterOffer = $strategy->offered_salary * 1.03; // Modest 3% salary increase
        
        $alternativeBenefits = [
            'sign_on_bonus' => '$15,000-25,000 signing bonus',
            'equity' => 'RSUs or stock options',
            'performance_bonus' => 'Increased bonus target (e.g., 20% vs 15%)',
            'additional_pto' => '5 additional PTO days',
            'remote_flexibility' => 'Full remote or hybrid flexibility',
            'learning_budget' => '$5,000 annual professional development budget',
        ];

        return [
            'scenario_name' => 'Total Compensation Focus',
            'counter_offer_amount' => round($counterOffer, 2),
            'additional_requests' => $alternativeBenefits,
            'counter_offer_justification' => 'If base salary is constrained, request enhanced total compensation package including signing bonus, equity, performance bonus, and benefits.',
            
            'predicted_response' => 'negotiate',
            'predicted_response_probability' => 70,
            'predicted_final_salary' => round($counterOffer, 2),
            'predicted_employer_counter' => 'Employer likely to accept modest salary increase and negotiate on alternative benefits mix',
            
            'risk_level' => 'low',
            'risk_score' => 25,
            'risk_factors' => [
                'Some benefits may not be negotiable',
                'Total value harder to quantify',
            ],
            'mitigation_strategies' => [
                'Prioritize benefits by importance',
                'Calculate total compensation value',
                'Show flexibility on benefit mix',
            ],
            
            'best_case_outcome' => round($counterOffer + 10000, 2), // Include sign-on bonus value
            'expected_outcome' => round($counterOffer, 2),
            'worst_case_outcome' => (float) $strategy->offered_salary,
            
            'success_indicators' => [
                'Employer opens discussion on benefits',
                'HR/Manager explores alternative options',
                'Agreement on enhanced total package',
            ],
            'failure_indicators' => [
                'All benefit requests denied',
                'No flexibility offered',
            ],
            
            'recommendation' => 'recommended',
            'recommendation_rationale' => 'Ideal for companies with limited salary flexibility. Maximizes total compensation through creative benefit structuring.',
            'confidence_score' => 70,
        ];
    }

    /**
     * Analyze scenario with AI
     */
    public function analyzeScenarioWithAI(NegotiationScenario $scenario): array
    {
        try {
            $strategy = $scenario->strategy;
            
            $prompt = "Analyze this negotiation scenario:\n\n";
            $prompt .= "**Current Offer:** $" . number_format($strategy->offered_salary) . "\n";
            $prompt .= "**Counter Offer:** $" . number_format((float) $scenario->counter_offer_amount) . "\n";
            $prompt .= "**Market Median:** $" . number_format((float) $strategy->market_median) . "\n";
            $prompt .= "**Scenario:** {$scenario->scenario_name}\n";
            $prompt .= "**Risk Level:** {$scenario->risk_level}\n\n";
            $prompt .= "Provide:\n";
            $prompt .= "1. Probability of success (0-100%)\n";
            $prompt .= "2. Likely employer response\n";
            $prompt .= "3. Recommended tactics\n";
            $prompt .= "4. Potential pitfalls to avoid\n\n";
            $prompt .= "Be specific and actionable.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert negotiation analyst. Provide tactical, data-driven scenario analysis.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 400,
                'temperature' => 0.7,
            ]);

            $analysis = $response->choices[0]->message->content;

            return [
                'ai_analysis' => $analysis,
                'updated_probability' => $this->extractProbability($analysis),
                'key_recommendations' => $this->extractRecommendations($analysis),
            ];
        } catch (\Exception $e) {
            Log::error('Scenario AI analysis failed', ['error' => $e->getMessage()]);
            
            return [
                'ai_analysis' => 'Analysis unavailable. Proceed based on scenario confidence score and risk level.',
                'updated_probability' => null,
                'key_recommendations' => [],
            ];
        }
    }

    /**
     * Compare scenarios side-by-side
     */
    public function compareScenarios(array $scenarios): array
    {
        $comparison = [];

        foreach ($scenarios as $scenario) {
            $comparison[] = [
                'id' => $scenario->id,
                'name' => $scenario->scenario_name,
                'counter_offer' => (float) $scenario->counter_offer_amount,
                'expected_outcome' => (float) $scenario->expected_outcome,
                'success_probability' => $scenario->success_probability,
                'risk_level' => $scenario->risk_level,
                'recommendation' => $scenario->recommendation,
                'confidence' => $scenario->confidence_score,
                'roi' => $scenario->calculateRoi(),
            ];
        }

        // Sort by ROI (highest first)
        usort($comparison, function($a, $b) {
            return $b['roi'] <=> $a['roi'];
        });

        return $comparison;
    }

    /**
     * Get recommended scenario
     */
    public function getRecommendedScenario(NegotiationStrategy $strategy): ?NegotiationScenario
    {
        return $strategy->scenarios()
            ->where('recommendation', 'recommended')
            ->orderBy('confidence_score', 'desc')
            ->first();
    }

    /**
     * Helper: Extract probability from AI response
     */
    protected function extractProbability(string $text): ?float
    {
        if (preg_match('/(\d+)%/', $text, $matches)) {
            return (float) $matches[1];
        }
        return null;
    }

    /**
     * Helper: Extract recommendations from AI response
     */
    protected function extractRecommendations(string $text): array
    {
        $recommendations = [];
        
        // Simple extraction of numbered/bulleted points
        if (preg_match_all('/[-•]\s*([^\n]+)/', $text, $matches)) {
            $recommendations = array_slice($matches[1], 0, 5);
        }

        return $recommendations;
    }
}
