<?php

namespace App\Services\AI;

use App\Models\NegotiationSession;
use App\Models\NegotiationMessage;
use App\Models\NegotiationStrategy;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class NegotiationCoachingService
{
    /**
     * Start a new coaching session
     */
    public function startSession(
        int $userId,
        NegotiationStrategy $strategy,
        ?int $scenarioId = null,
        string $sessionType = 'live_coaching',
        string $communicationMode = 'email'
    ): NegotiationSession {
        $session = NegotiationSession::create([
            'user_id' => $userId,
            'strategy_id' => $strategy->id,
            'scenario_id' => $scenarioId,
            'session_type' => $sessionType,
            'communication_mode' => $communicationMode,
            'current_stage' => 'initial_outreach',
        ]);

        $session->startSession();

        // Send initial coaching message
        $this->sendInitialGuidance($session, $strategy);

        return $session;
    }

    /**
     * Send initial coaching guidance
     */
    protected function sendInitialGuidance(NegotiationSession $session, NegotiationStrategy $strategy): void
    {
        $guidance = "🎯 **Negotiation Session Started**\n\n";
        $guidance .= "**Your Strategy Overview:**\n";
        $guidance .= "- Target Salary: $" . number_format((float) $strategy->optimal_ask) . "\n";
        $guidance .= "- Minimum Acceptable: $" . number_format((float) $strategy->minimum_acceptable) . "\n";
        $guidance .= "- Confidence Level: " . $strategy->confidence_level . "\n\n";
        
        $guidance .= "**Key Strengths to Emphasize:**\n";
        foreach (array_slice($strategy->strongest_points, 0, 3) as $point) {
            $guidance .= "• {$point}\n";
        }
        
        $guidance .= "\n**Recommended Approach:**\n";
        $guidance .= "• Tone: " . ucfirst($strategy->recommended_tone) . "\n";
        $guidance .= "• Timing: " . str_replace('_', ' ', $strategy->recommended_timing) . "\n\n";
        
        $guidance .= "I'll provide real-time coaching as you communicate with the employer. Share employer messages and I'll help you craft strategic responses.";

        NegotiationMessage::create([
            'session_id' => $session->id,
            'message_type' => 'ai_analysis',
            'content' => $guidance,
            'urgency' => 'low',
            'confidence_score' => 90,
        ]);
    }

    /**
     * Analyze employer message and provide coaching
     */
    public function analyzeEmployerMessage(NegotiationSession $session, string $employerMessage): array
    {
        // Save employer message
        $message = NegotiationMessage::create([
            'session_id' => $session->id,
            'message_type' => 'employer_response',
            'content' => $employerMessage,
        ]);

        // Analyze tone and sentiment
        $tone = $message->analyzeEmployerTone();
        $keyPhrases = $message->extractKeyPhrases();

        // Detect employer signals
        $signals = $this->detectEmployerSignals($employerMessage, $tone);
        if (!empty($signals)) {
            foreach ($signals as $signal) {
                $session->recordEmployerSignal($signal);
            }
        }

        // Generate AI coaching response
        $coaching = $this->generateCoachingResponse($session, $employerMessage, $tone, $keyPhrases);

        // Save coaching message
        $coachingMessage = NegotiationMessage::create([
            'session_id' => $session->id,
            'message_type' => 'ai_analysis',
            'in_response_to_id' => $message->id,
            'content' => $coaching['analysis'],
            'urgency' => $coaching['urgency'],
            'confidence_score' => $coaching['confidence'],
        ]);

        // Generate response suggestions
        $suggestions = $this->generateResponseSuggestions($session, $employerMessage, $tone, $coaching['tactical_recommendations']);

        // Save suggestions
        foreach ($suggestions as $suggestion) {
            NegotiationMessage::create([
                'session_id' => $session->id,
                'message_type' => 'ai_suggestion',
                'in_response_to_id' => $message->id,
                'content' => $suggestion['response'],
                'suggestion_category' => $suggestion['category'],
                'urgency' => $suggestion['urgency'],
                'confidence_score' => $suggestion['confidence'],
                'context_analysis' => $suggestion['context'],
            ]);
        }

        return [
            'tone' => $tone,
            'key_phrases' => $keyPhrases,
            'signals' => $signals,
            'coaching' => $coaching,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Detect employer signals from message
     */
    protected function detectEmployerSignals(string $message, string $tone): array
    {
        $signals = [];
        $messageLower = strtolower($message);

        // Positive signals
        if (str_contains($messageLower, 'flexible') || str_contains($messageLower, 'work with you')) {
            $signals[] = 'Flexibility indicated - employer open to negotiation';
        }
        if (str_contains($messageLower, 'budget') || str_contains($messageLower, 'constraints')) {
            $signals[] = 'Budget constraints mentioned - may need creative solutions';
        }
        if (str_contains($messageLower, 'understand') || str_contains($messageLower, 'appreciate')) {
            $signals[] = 'Empathy shown - positive relationship building';
        }
        
        // Receptive signals
        if (str_contains($messageLower, 'let me check') || str_contains($messageLower, 'get back to you')) {
            $signals[] = 'Considering request - employer taking it seriously';
        }
        if (str_contains($messageLower, 'discuss') || str_contains($messageLower, 'talk about')) {
            $signals[] = 'Open to dialogue - good sign for negotiation';
        }

        // Warning signals
        if (str_contains($messageLower, 'final') || str_contains($messageLower, 'best we can do')) {
            $signals[] = 'Finality language - may be at budget limit';
        }
        if (str_contains($messageLower, 'other candidates') || str_contains($messageLower, 'timeline')) {
            $signals[] = 'Pressure tactic detected - employer may be testing resolve';
        }
        if (str_contains($messageLower, 'take it or leave') || str_contains($messageLower, 'not negotiable')) {
            $signals[] = 'Hard stance - consider alternative benefits approach';
        }

        return $signals;
    }

    /**
     * Generate AI coaching response
     */
    protected function generateCoachingResponse(
        NegotiationSession $session,
        string $employerMessage,
        string $tone,
        array $keyPhrases
    ): array {
        try {
            $strategy = $session->strategy;
            
            $prompt = $this->buildCoachingPrompt($session, $employerMessage, $tone, $keyPhrases);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert negotiation coach providing real-time tactical guidance. Be specific, actionable, and strategic.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $coaching = $response->choices[0]->message->content;

            // Parse coaching into structured format
            return [
                'analysis' => $coaching,
                'urgency' => $this->determineUrgency($tone, $employerMessage),
                'confidence' => $this->calculateCoachingConfidence($tone),
                'tactical_recommendations' => $this->extractTacticalRecommendations($coaching),
            ];
        } catch (\Exception $e) {
            Log::error('Coaching AI failed', ['error' => $e->getMessage()]);
            
            return [
                'analysis' => $this->getFallbackCoaching($tone),
                'urgency' => 'medium',
                'confidence' => 60,
                'tactical_recommendations' => [],
            ];
        }
    }

    /**
     * Build coaching prompt
     */
    protected function buildCoachingPrompt(
        NegotiationSession $session,
        string $employerMessage,
        string $tone,
        array $keyPhrases
    ): string {
        $strategy = $session->strategy;
        
        $prompt = "**Real-Time Negotiation Coaching Needed**\n\n";
        $prompt .= "**Context:**\n";
        $prompt .= "- Current Stage: {$session->current_stage}\n";
        $prompt .= "- Target Salary: $" . number_format((float) $strategy->optimal_ask) . "\n";
        $prompt .= "- Minimum Acceptable: $" . number_format((float) $strategy->minimum_acceptable) . "\n";
        $prompt .= "- Communication Mode: {$session->communication_mode}\n\n";
        
        $prompt .= "**Employer's Message:**\n\"{$employerMessage}\"\n\n";
        $prompt .= "**Detected Tone:** {$tone}\n";
        
        if (!empty($keyPhrases)) {
            $prompt .= "**Key Phrases:** " . implode(', ', array_slice($keyPhrases, 0, 5)) . "\n\n";
        }
        
        $prompt .= "**Provide:**\n";
        $prompt .= "1. **Interpretation:** What does this message really mean?\n";
        $prompt .= "2. **Tactical Analysis:** What's the employer's position and flexibility?\n";
        $prompt .= "3. **Recommended Response Strategy:** How should the candidate respond?\n";
        $prompt .= "4. **Warning Signs:** Any red flags or concerns?\n";
        $prompt .= "5. **Next Steps:** Specific actions to take\n\n";
        $prompt .= "Be direct, tactical, and strategic. Focus on winning this negotiation.";

        return $prompt;
    }

    /**
     * Generate response suggestions
     */
    protected function generateResponseSuggestions(
        NegotiationSession $session,
        string $employerMessage,
        string $tone,
        array $tacticalRecs
    ): array {
        $suggestions = [];
        $strategy = $session->strategy;

        // Generate 2-4 response options with different approaches
        
        // Option 1: Collaborative/Flexible Response
        $suggestions[] = [
            'response' => $this->generateCollaborativeResponse($session, $employerMessage, $tone),
            'category' => 'response_suggestion',
            'urgency' => 'medium',
            'confidence' => 75,
            'context' => 'Collaborative approach - maintains relationship while advancing position',
        ];

        // Option 2: Data-Driven Response
        if ($tone !== 'negative') {
            $suggestions[] = [
                'response' => $this->generateDataDrivenResponse($session, $employerMessage),
                'category' => 'response_suggestion',
                'urgency' => 'medium',
                'confidence' => 80,
                'context' => 'Data-driven approach - reinforces value with market evidence',
            ];
        }

        // Option 3: Alternative Benefits Response (if salary resistance detected)
        if ($this->detectSalaryResistance($employerMessage)) {
            $suggestions[] = [
                'response' => $this->generateAlternativeBenefitsResponse($session, $employerMessage),
                'category' => 'pivot_suggestion',
                'urgency' => 'high',
                'confidence' => 70,
                'context' => 'Alternative benefits approach - pivots to total compensation if salary inflexible',
            ];
        }

        // Option 4: Closing/Acceptance Response (if favorable)
        if ($tone === 'positive' || $tone === 'receptive') {
            $suggestions[] = [
                'response' => $this->generateClosingResponse($session, $employerMessage),
                'category' => 'response_suggestion',
                'urgency' => 'low',
                'confidence' => 85,
                'context' => 'Closing approach - move to acceptance if offer meets minimum requirements',
            ];
        }

        return $suggestions;
    }

    /**
     * Generate collaborative response
     */
    protected function generateCollaborativeResponse(
        NegotiationSession $session,
        string $employerMessage,
        string $tone
    ): string {
        $strategy = $session->strategy;
        
        $response = "Thank you for your response. I really appreciate your openness to discussing this further. ";
        $response .= "I'm very excited about joining {$strategy->company_name} and contributing to [specific team goal].\n\n";
        $response .= "I understand there may be budget considerations. Would it be possible to explore a total compensation package that includes ";
        $response .= "[mention 1-2 benefits like sign-on bonus, equity, additional PTO]? I'm confident we can find a solution that works for both of us.\n\n";
        $response .= "I'm flexible and eager to join the team. What options might be available?";

        return $response;
    }

    /**
     * Generate data-driven response
     */
    protected function generateDataDrivenResponse(
        NegotiationSession $session,
        string $employerMessage
    ): string {
        $strategy = $session->strategy;
        
        $response = "Thank you for considering my request. I wanted to provide some additional context:\n\n";
        $response .= "Based on current market data for {$strategy->role} positions in {$strategy->location}, ";
        $response .= "the median salary is around $" . number_format((float) $strategy->market_median) . ", ";
        $response .= "with the 75th percentile at $" . number_format((float) $strategy->market_75th_percentile) . ".\n\n";
        $response .= "Given my [X years] experience in [key skill areas] and proven track record of [specific achievements], ";
        $response .= "I believe a salary of $" . number_format((float) $strategy->optimal_ask) . " is well-aligned with the value I'll bring.\n\n";
        $response .= "I'm happy to discuss this further and explore what's feasible within your budget.";

        return $response;
    }

    /**
     * Generate alternative benefits response
     */
    protected function generateAlternativeBenefitsResponse(
        NegotiationSession $session,
        string $employerMessage
    ): string {
        $response = "I appreciate your transparency about the salary constraints. ";
        $response .= "I'm still very interested in this role and would like to explore the total compensation package.\n\n";
        $response .= "If there's limited flexibility on base salary, would it be possible to discuss:\n";
        $response .= "• Sign-on bonus to help bridge the gap\n";
        $response .= "• Performance bonus or equity component\n";
        $response .= "• Additional PTO or remote work flexibility\n";
        $response .= "• Professional development budget\n\n";
        $response .= "I'm confident we can structure a package that reflects my value while respecting your budget.";

        return $response;
    }

    /**
     * Generate closing response
     */
    protected function generateClosingResponse(
        NegotiationSession $session,
        string $employerMessage
    ): string {
        $strategy = $session->strategy;
        
        $response = "Thank you so much for working with me on this. I really appreciate your flexibility and the offer meets my expectations.\n\n";
        $response .= "I'm excited to accept the position and join {$strategy->company_name}! ";
        $response .= "When would you like me to officially confirm my acceptance, and what are the next steps?\n\n";
        $response .= "Looking forward to contributing to the team's success.";

        return $response;
    }

    /**
     * Record user's selected response
     */
    public function recordUserResponse(NegotiationSession $session, string $userResponse, ?int $suggestionId = null): void
    {
        $message = NegotiationMessage::create([
            'session_id' => $session->id,
            'message_type' => 'user_input',
            'content' => $userResponse,
        ]);

        // Mark suggestion as used if applicable
        if ($suggestionId) {
            $suggestion = NegotiationMessage::find($suggestionId);
            if ($suggestion) {
                $suggestion->markAsUsed();
            }
        }

        // Track that AI provided coaching
        $session->recordAiIntervention('response_guidance', 'Provided response suggestions for employer message');
    }

    /**
     * Update session stage
     */
    public function updateSessionStage(NegotiationSession $session, string $stage): void
    {
        $session->updateStage($stage);

        // Provide stage-specific guidance
        $guidance = $this->getStageGuidance($stage);
        if ($guidance) {
            NegotiationMessage::create([
                'session_id' => $session->id,
                'message_type' => 'ai_analysis',
                'content' => $guidance,
                'urgency' => 'low',
                'confidence_score' => 85,
            ]);
        }
    }

    /**
     * Get stage-specific guidance
     */
    protected function getStageGuidance(string $stage): ?string
    {
        $guidance = [
            'initial_outreach' => "📧 **Initial Outreach Stage**: Express enthusiasm and gratitude. Set a collaborative tone. Don't mention salary yet unless responding to their offer.",
            'counter_offer' => "💰 **Counter Offer Stage**: Present your counter professionally. Use data and value propositions. Be prepared to justify your ask.",
            'negotiation' => "🤝 **Active Negotiation**: Stay collaborative but firm. Listen for flexibility signals. Be ready to pivot to alternative benefits if needed.",
            'benefits_discussion' => "🎁 **Benefits Discussion**: Explore total compensation. Prioritize which benefits matter most. Calculate total package value.",
            'closing' => "✅ **Closing Stage**: Confirm final numbers in writing. Express enthusiasm. Clarify start date and next steps.",
            'accepted' => "🎉 **Congratulations!** You've successfully negotiated your offer. Review the final written offer carefully before signing.",
        ];

        return $guidance[$stage] ?? null;
    }

    /**
     * End session and record outcome
     */
    public function endSession(
        NegotiationSession $session,
        string $outcome,
        ?float $finalSalary = null,
        ?array $finalBenefits = null
    ): void {
        $session->recordOutcome($outcome, $finalSalary, $finalBenefits);
        $session->endSession();

        // Provide outcome summary
        $summary = $this->generateOutcomeSummary($session, $outcome, $finalSalary);
        
        NegotiationMessage::create([
            'session_id' => $session->id,
            'message_type' => 'system_note',
            'content' => $summary,
            'urgency' => 'low',
            'confidence_score' => 100,
        ]);
    }

    /**
     * Generate outcome summary
     */
    protected function generateOutcomeSummary(
        NegotiationSession $session,
        string $outcome,
        ?float $finalSalary
    ): string {
        $strategy = $session->strategy;
        
        $summary = "📊 **Negotiation Session Summary**\n\n";
        $summary .= "**Outcome:** " . ucfirst(str_replace('_', ' ', $outcome)) . "\n";
        
        if ($finalSalary) {
            $summary .= "**Final Salary:** $" . number_format($finalSalary) . "\n";
            $summary .= "**Initial Offer:** $" . number_format((float) $strategy->offered_salary) . "\n";
            $gain = $finalSalary - (float) $strategy->offered_salary;
            $gainPercent = ($gain / (float) $strategy->offered_salary) * 100;
            $summary .= "**Salary Gain:** $" . number_format($gain) . " (" . number_format($gainPercent, 1) . "%)\n\n";
        }
        
        $summary .= "**Session Duration:** " . $session->duration . "\n";
        $summary .= "**Messages Exchanged:** " . $session->messages()->count() . "\n\n";
        
        if ($outcome === 'accepted' || $outcome === 'offer_improved') {
            $summary .= "🎉 Great job! You successfully negotiated your offer.";
        } elseif ($outcome === 'offer_withdrawn') {
            $summary .= "The offer was withdrawn. This is rare but happens. Learn from this experience.";
        } else {
            $summary .= "Thank you for using the negotiation coach. We hope it was helpful!";
        }

        return $summary;
    }

    /**
     * Helper: Determine urgency level
     */
    protected function determineUrgency(string $tone, string $message): string
    {
        if ($tone === 'negative' || str_contains(strtolower($message), 'final') || str_contains(strtolower($message), 'deadline')) {
            return 'high';
        } elseif ($tone === 'receptive' || $tone === 'positive') {
            return 'low';
        }
        return 'medium';
    }

    /**
     * Helper: Calculate coaching confidence
     */
    protected function calculateCoachingConfidence(string $tone): int
    {
        return match($tone) {
            'positive' => 90,
            'receptive' => 85,
            'neutral' => 70,
            'negative' => 60,
            'resistant' => 50,
            default => 70,
        };
    }

    /**
     * Helper: Extract tactical recommendations from coaching
     */
    protected function extractTacticalRecommendations(string $coaching): array
    {
        $recommendations = [];
        
        if (preg_match_all('/[-•]\s*([^\n]+)/', $coaching, $matches)) {
            $recommendations = array_slice($matches[1], 0, 5);
        }

        return $recommendations;
    }

    /**
     * Helper: Detect salary resistance
     */
    protected function detectSalaryResistance(string $message): bool
    {
        $messageLower = strtolower($message);
        return str_contains($messageLower, 'budget') ||
               str_contains($messageLower, 'constraints') ||
               str_contains($messageLower, 'cannot') ||
               str_contains($messageLower, 'unable to');
    }

    /**
     * Helper: Get fallback coaching
     */
    protected function getFallbackCoaching(string $tone): string
    {
        $fallbacks = [
            'positive' => "The employer's tone is positive. They're receptive to your request. Continue with confidence, provide data-driven justification, and work toward agreement.",
            'receptive' => "The employer is open to discussion. Present your case clearly, show flexibility, and explore win-win solutions.",
            'neutral' => "The employer's response is neutral. Stay professional, reinforce your value proposition, and be prepared to justify your ask with market data.",
            'negative' => "The employer's tone suggests resistance. Consider pivoting to alternative benefits or exploring total compensation package options.",
            'resistant' => "Strong resistance detected. You may need to lower your ask or focus on non-salary benefits. Assess if this offer meets your minimum requirements.",
        ];

        return $fallbacks[$tone] ?? $fallbacks['neutral'];
    }
}
