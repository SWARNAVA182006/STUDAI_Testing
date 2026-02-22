<?php

namespace App\Services\AI;

use App\Models\NegotiationStrategy;
use App\Models\NegotiationScenario;
use App\Models\NegotiationScript;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use OpenAI\Laravel\Facades\OpenAI;

class NegotiationScriptService
{
    /**
     * Generate negotiation scripts for a scenario
     */
    public function generateScripts(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        $scripts = [];

        // Generate email script (most common)
        $scripts[] = $this->generateEmailScript($strategy, $scenario);

        // Generate phone script
        $scripts[] = $this->generatePhoneScript($strategy, $scenario);

        // Generate in-person script (if applicable)
        if ($scenario->risk_level !== 'very_high') {
            $scripts[] = $this->generateInPersonScript($strategy, $scenario);
        }

        // Save all scripts
        $savedScripts = [];
        foreach ($scripts as $scriptData) {
            $script = NegotiationScript::create(array_merge($scriptData, [
                'strategy_id' => $strategy->id,
                'scenario_id' => $scenario->id,
            ]));
            $savedScripts[] = $script;
        }

        return $savedScripts;
    }

    /**
     * Generate professional email script
     */
    protected function generateEmailScript(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        $tone = $this->determineTone($strategy, $scenario);
        
        $cacheKey = 'negotiation_email_script_' . $strategy->id . '_' . $scenario->id;
        
        $aiGenerated = Cache::remember($cacheKey, 3600, function() use ($strategy, $scenario, $tone) {
            return $this->generateScriptWithAI($strategy, $scenario, 'email', 'counter_offer', $tone);
        });

        $subjectLine = $aiGenerated['subject_line'] ?? $this->generateDefaultSubjectLine($strategy);
        $opening = $aiGenerated['opening'] ?? $this->generateDefaultOpening($strategy, $tone);
        $body = $aiGenerated['body'] ?? $this->generateDefaultBody($strategy, $scenario, $tone);
        $closing = $aiGenerated['closing'] ?? $this->generateDefaultClosing($tone);

        return [
            'script_name' => 'Email Counter-Offer - ' . ucfirst($tone),
            'script_type' => 'email',
            'script_stage' => 'counter_offer',
            
            'subject_line' => $subjectLine,
            'opening' => $opening,
            'body' => $body,
            'closing' => $closing,
            
            'key_talking_points' => $this->extractTalkingPoints($strategy, $scenario),
            'phrases_to_use' => $this->getRecommendedPhrases($tone, 'email'),
            'phrases_to_avoid' => $this->getPhrasesToAvoid('email'),
            'transition_phrases' => $this->getTransitionPhrases('email'),
            
            'tone' => $tone,
            'formality_level' => $this->determineFormality($strategy),
            
            'anchoring_tactics' => $this->getAnchoringTactics($scenario),
            'framing_strategies' => $this->getFramingStrategies($strategy, $scenario),
            'reciprocity_elements' => $this->getReciprocityElements($scenario),
            
            'includes_deadline' => false,
            'includes_alternatives' => $scenario->risk_level === 'high',
            'includes_data' => true,
        ];
    }

    /**
     * Generate phone conversation script
     */
    protected function generatePhoneScript(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        $tone = $this->determineTone($strategy, $scenario);

        return [
            'script_name' => 'Phone Conversation - ' . ucfirst($tone),
            'script_type' => 'phone',
            'script_stage' => 'counter_offer',
            
            'subject_line' => null,
            'opening' => $this->generatePhoneOpening($strategy, $tone),
            'body' => $this->generatePhoneBody($strategy, $scenario, $tone),
            'closing' => $this->generatePhoneClosing($tone),
            
            'key_talking_points' => $this->extractTalkingPoints($strategy, $scenario),
            'phrases_to_use' => $this->getRecommendedPhrases($tone, 'phone'),
            'phrases_to_avoid' => $this->getPhrasesToAvoid('phone'),
            'transition_phrases' => $this->getTransitionPhrases('phone'),
            
            'tone' => $tone,
            'formality_level' => $this->determineFormality($strategy),
            
            'anchoring_tactics' => $this->getAnchoringTactics($scenario),
            'framing_strategies' => $this->getFramingStrategies($strategy, $scenario),
            'reciprocity_elements' => $this->getReciprocityElements($scenario),
            
            'includes_deadline' => false,
            'includes_alternatives' => $scenario->risk_level === 'high',
            'includes_data' => true,
        ];
    }

    /**
     * Generate in-person meeting script
     */
    protected function generateInPersonScript(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        $tone = $this->determineTone($strategy, $scenario);

        return [
            'script_name' => 'In-Person Discussion - ' . ucfirst($tone),
            'script_type' => 'in_person',
            'script_stage' => 'counter_offer',
            
            'subject_line' => null,
            'opening' => $this->generateInPersonOpening($strategy, $tone),
            'body' => $this->generateInPersonBody($strategy, $scenario, $tone),
            'closing' => $this->generateInPersonClosing($tone),
            
            'key_talking_points' => $this->extractTalkingPoints($strategy, $scenario),
            'phrases_to_use' => $this->getRecommendedPhrases($tone, 'in_person'),
            'phrases_to_avoid' => $this->getPhrasesToAvoid('in_person'),
            'transition_phrases' => $this->getTransitionPhrases('in_person'),
            
            'tone' => $tone,
            'formality_level' => $this->determineFormality($strategy),
            
            'anchoring_tactics' => $this->getAnchoringTactics($scenario),
            'framing_strategies' => $this->getFramingStrategies($strategy, $scenario),
            'reciprocity_elements' => $this->getReciprocityElements($scenario),
            
            'includes_deadline' => false,
            'includes_alternatives' => $scenario->risk_level === 'high',
            'includes_data' => true,
        ];
    }

    /**
     * Generate script with AI
     */
    protected function generateScriptWithAI(
        NegotiationStrategy $strategy,
        NegotiationScenario $scenario,
        string $type,
        string $stage,
        string $tone
    ): array {
        try {
            $prompt = $this->buildScriptPrompt($strategy, $scenario, $type, $stage, $tone);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-5',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert negotiation coach. Generate professional, persuasive negotiation scripts that are tactful, data-driven, and relationship-preserving.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            $content = $response->choices[0]->message->content;

            // Parse AI response
            return [
                'subject_line' => $this->extractSection($content, 'Subject:', 'Opening:'),
                'opening' => $this->extractSection($content, 'Opening:', 'Body:'),
                'body' => $this->extractSection($content, 'Body:', 'Closing:'),
                'closing' => $this->extractSection($content, 'Closing:', null),
            ];
        } catch (\Exception $e) {
            Log::error('Script AI generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Build AI prompt for script generation
     */
    protected function buildScriptPrompt(
        NegotiationStrategy $strategy,
        NegotiationScenario $scenario,
        string $type,
        string $stage,
        string $tone
    ): string {
        $prompt = "Generate a professional negotiation script for {$type} communication.\n\n";
        $prompt .= "**Context:**\n";
        $prompt .= "- Role: {$strategy->role}\n";
        $prompt .= "- Company: {$strategy->company_name}\n";
        $prompt .= "- Current Offer: $" . number_format($strategy->offered_salary) . "\n";
        $prompt .= "- Counter Offer: $" . number_format((float) $scenario->counter_offer_amount) . "\n";
        $prompt .= "- Market Median: $" . number_format((float) $strategy->market_median) . "\n";
        $prompt .= "- Tone: {$tone}\n";
        $prompt .= "- Company Culture: {$strategy->company_culture_analysis}\n\n";
        
        $prompt .= "**Your Strengths:**\n";
        foreach (array_slice($strategy->strongest_points, 0, 3) as $point) {
            $prompt .= "- {$point}\n";
        }
        
        $prompt .= "\n**Generate:**\n";
        if ($type === 'email') {
            $prompt .= "Subject: [Professional subject line]\n";
        }
        $prompt .= "Opening: [Warm, appreciative opening paragraph]\n";
        $prompt .= "Body: [Data-driven justification with market context and value proposition, 2-3 paragraphs]\n";
        $prompt .= "Closing: [Collaborative, forward-looking closing]\n\n";
        $prompt .= "Use placeholders: [Your Name], [Hiring Manager Name], [Role], [Company]\n";
        $prompt .= "Be professional, confident, and collaborative. Avoid ultimatums or demands.";

        return $prompt;
    }

    /**
     * Determine appropriate tone
     */
    protected function determineTone(NegotiationStrategy $strategy, NegotiationScenario $scenario): string
    {
        // High risk scenarios need more collaborative tone
        if ($scenario->risk_level === 'high' || $scenario->risk_level === 'very_high') {
            return 'collaborative';
        }

        // Use company flexibility to guide tone
        if ($strategy->company_negotiation_flexibility === 'high') {
            return 'enthusiastic';
        } elseif ($strategy->company_negotiation_flexibility === 'low') {
            return 'professional';
        }

        return 'confident';
    }

    /**
     * Determine formality level
     */
    protected function determineFormality(NegotiationStrategy $strategy): string
    {
        // Startup = casual, corporate = formal
        $culture = strtolower($strategy->company_culture_analysis ?? '');
        
        if (str_contains($culture, 'startup') || str_contains($culture, 'informal')) {
            return 'casual';
        } elseif (str_contains($culture, 'corporate') || str_contains($culture, 'traditional')) {
            return 'formal';
        }

        return 'semi-formal';
    }

    /**
     * Extract key talking points
     */
    protected function extractTalkingPoints(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        $points = [];

        // Market data point
        if ($strategy->market_median) {
            $points[] = "Market median for this role is $" . number_format((float) $strategy->market_median);
        }

        // Top strengths
        foreach (array_slice($strategy->strongest_points, 0, 3) as $strength) {
            $points[] = $strength;
        }

        // Value propositions
        foreach (array_slice($strategy->value_propositions, 0, 2) as $value) {
            $points[] = $value;
        }

        return $points;
    }

    /**
     * Get recommended phrases
     */
    protected function getRecommendedPhrases(string $tone, string $type): array
    {
        $phrases = [
            'collaborative' => [
                "I'm excited about the opportunity to join the team",
                "Based on my research and market data",
                "I'd love to explore how we can align on compensation",
                "I'm confident we can find a solution that works for both of us",
            ],
            'enthusiastic' => [
                "I'm thrilled about this opportunity",
                "I'm eager to contribute my expertise",
                "This role is exactly what I've been looking for",
                "I can't wait to get started",
            ],
            'confident' => [
                "My experience demonstrates",
                "Industry data shows",
                "Given my track record of",
                "The value I bring includes",
            ],
            'professional' => [
                "I appreciate your consideration",
                "After careful analysis",
                "Based on industry standards",
                "I respectfully request",
            ],
        ];

        return $phrases[$tone] ?? $phrases['professional'];
    }

    /**
     * Get phrases to avoid
     */
    protected function getPhrasesToAvoid(string $type): array
    {
        return [
            "I need...",
            "I deserve...",
            "My current salary is...",
            "Take it or leave it",
            "This is my final offer",
            "I have other offers (unless you actually do)",
            "Everyone else makes more",
            "I'm worth...",
        ];
    }

    /**
     * Get transition phrases
     */
    protected function getTransitionPhrases(string $type): array
    {
        if ($type === 'email') {
            return [
                "Additionally, I'd like to highlight...",
                "Another factor to consider is...",
                "Furthermore, my background includes...",
                "In terms of compensation...",
            ];
        } else {
            return [
                "That said...",
                "Building on that...",
                "Let me also mention...",
                "One more thing to consider...",
            ];
        }
    }

    /**
     * Get anchoring tactics
     */
    protected function getAnchoringTactics(NegotiationScenario $scenario): array
    {
        return [
            'start_high' => $scenario->risk_level !== 'low',
            'cite_market_75th' => true,
            'reference_total_comp' => true,
            'multiple_data_points' => true,
        ];
    }

    /**
     * Get framing strategies
     */
    protected function getFramingStrategies(NegotiationStrategy $strategy, NegotiationScenario $scenario): array
    {
        return [
            'frame_as_investment' => "Investment in top talent",
            'frame_as_win_win' => "Mutual benefit and long-term success",
            'frame_as_market_alignment' => "Aligning with market standards",
            'frame_as_value_exchange' => "Fair exchange for value delivered",
        ];
    }

    /**
     * Get reciprocity elements
     */
    protected function getReciprocityElements(NegotiationScenario $scenario): array
    {
        $elements = [
            'show_flexibility' => "Willing to discuss total compensation package",
            'acknowledge_constraints' => "Understand budget considerations",
        ];

        if ($scenario->risk_level === 'low' || $scenario->risk_level === 'medium') {
            $elements['offer_concession'] = "Open to alternative benefit structures";
        }

        return $elements;
    }

    /**
     * Generate default subject line
     */
    protected function generateDefaultSubjectLine(NegotiationStrategy $strategy): string
    {
        return "Re: {$strategy->role} Offer - [Your Name]";
    }

    /**
     * Generate default email opening
     */
    protected function generateDefaultOpening(NegotiationStrategy $strategy, string $tone): string
    {
        $openings = [
            'enthusiastic' => "Dear [Hiring Manager Name],\n\nThank you so much for the offer to join {$strategy->company_name} as {$strategy->role}! I'm incredibly excited about this opportunity and the chance to contribute to the team.",
            'collaborative' => "Dear [Hiring Manager Name],\n\nThank you for extending the offer for the {$strategy->role} position. I'm very excited about joining {$strategy->company_name} and appreciate the time you've invested in the hiring process.",
            'confident' => "Dear [Hiring Manager Name],\n\nThank you for the offer to join {$strategy->company_name} as {$strategy->role}. I'm enthusiastic about this opportunity and confident I can deliver significant value to the team.",
            'professional' => "Dear [Hiring Manager Name],\n\nThank you for your offer for the {$strategy->role} position at {$strategy->company_name}. I appreciate your confidence in my abilities and am eager to contribute to your organization's success.",
        ];

        return $openings[$tone] ?? $openings['professional'];
    }

    /**
     * Generate default email body
     */
    protected function generateDefaultBody(NegotiationStrategy $strategy, NegotiationScenario $scenario, string $tone): string
    {
        $body = "After careful consideration and research into market standards for this role, I'd like to discuss the compensation package. ";
        $body .= "Based on industry data for {$strategy->role} positions in {$strategy->location}, along with my [X years] of experience and specialized skills, ";
        $body .= "the market rate typically ranges from $" . number_format((float) $strategy->market_25th_percentile) . " to $" . number_format((float) $strategy->market_75th_percentile) . ".\n\n";
        
        $body .= "Given my background in [specific expertise] and proven track record of [key achievements], ";
        $body .= "I believe a salary of $" . number_format((float) $scenario->counter_offer_amount) . " would better reflect the value I'll bring to {$strategy->company_name}. ";
        
        if (!empty($scenario->additional_requests)) {
            $body .= "Additionally, I'd welcome the opportunity to discuss the overall compensation package, including [mention 1-2 benefits].";
        }

        return $body;
    }

    /**
     * Generate default email closing
     */
    protected function generateDefaultClosing(string $tone): string
    {
        $closings = [
            'enthusiastic' => "I'm confident we can reach an agreement that reflects my value while respecting your budget. I'm very excited to join the team and make an immediate impact!\n\nLooking forward to your thoughts.\n\nBest regards,\n[Your Name]",
            'collaborative' => "I'm optimistic we can find a mutually beneficial solution. I'm eager to join the team and contribute to [Company]'s continued success.\n\nI look forward to discussing this further.\n\nBest regards,\n[Your Name]",
            'confident' => "I'm confident this adjustment will reflect fair market value for the expertise I bring. I'm ready to make a significant impact at [Company].\n\nI look forward to your response.\n\nBest regards,\n[Your Name]",
            'professional' => "I appreciate your consideration of this request and remain enthusiastic about joining [Company]. I'm happy to discuss this at your convenience.\n\nThank you for your time.\n\nBest regards,\n[Your Name]",
        ];

        return $closings[$tone] ?? $closings['professional'];
    }

    /**
     * Generate phone opening
     */
    protected function generatePhoneOpening(NegotiationStrategy $strategy, string $tone): string
    {
        return "Hi [Hiring Manager Name], thanks for taking the time to speak with me today. I wanted to start by saying how excited I am about the {$strategy->role} opportunity at {$strategy->company_name}. I've been thinking about the offer and wanted to discuss the compensation package if you have a few minutes.";
    }

    /**
     * Generate phone body
     */
    protected function generatePhoneBody(NegotiationStrategy $strategy, NegotiationScenario $scenario, string $tone): string
    {
        $body = "[Pause for their response]\n\n";
        $body .= "Great. So I've done some research on market rates for this role, and based on my [X years] of experience and the specific skills I bring, ";
        $body .= "I was hoping we could discuss a salary closer to $" . number_format((float) $scenario->counter_offer_amount) . ". ";
        $body .= "The market data I'm seeing shows this is around the [65-70th] percentile for similar roles.\n\n";
        $body .= "[Pause - let them respond]\n\n";
        $body .= "I completely understand if there are budget constraints. I'm also open to discussing the total compensation package, including [mention alternatives if needed].";

        return $body;
    }

    /**
     * Generate phone closing
     */
    protected function generatePhoneClosing(string $tone): string
    {
        return "I really appreciate you hearing me out. I'm very excited about this role and confident I can deliver strong results. What are the next steps from here?\n\n[Listen to their response and thank them]";
    }

    /**
     * Generate in-person opening
     */
    protected function generateInPersonOpening(NegotiationStrategy $strategy, string $tone): string
    {
        return "[Smile, make eye contact, positive body language]\n\n" .
               "Thank you for meeting with me today. I'm really excited about the opportunity to join {$strategy->company_name} as {$strategy->role}. " .
               "I wanted to take this opportunity to discuss the compensation package in more detail.";
    }

    /**
     * Generate in-person body
     */
    protected function generateInPersonBody(NegotiationStrategy $strategy, NegotiationScenario $scenario, string $tone): string
    {
        $body = "[Maintain collaborative posture - lean slightly forward, open gestures]\n\n";
        $body .= "I've researched market standards for this role, and given my background and the value I'll bring, ";
        $body .= "I'd like to propose a salary of $" . number_format((float) $scenario->counter_offer_amount) . ". ";
        $body .= "This aligns with the market rate for someone with my experience level and skill set.\n\n";
        $body .= "[Pull out printed market data if appropriate]\n\n";
        $body .= "Specifically, my expertise in [key areas] and track record of [achievements] position me to make an immediate impact on [specific company goals].\n\n";
        $body .= "[Pause - read their body language and response]\n\n";
        $body .= "I'm also open to discussing the broader compensation package if salary flexibility is limited.";

        return $body;
    }

    /**
     * Generate in-person closing
     */
    protected function generateInPersonClosing(string $tone): string
    {
        return "[Maintain eye contact and confident posture]\n\n" .
               "I'm genuinely excited about this opportunity and confident we can find a solution that works for both of us. " .
               "What are your thoughts on this?\n\n" .
               "[Listen actively - nod, take notes if appropriate]\n\n" .
               "Thank you for considering my request. I look forward to joining the team.";
    }

    /**
     * Helper: Extract section from AI response
     */
    protected function extractSection(string $content, string $startMarker, ?string $endMarker): string
    {
        $start = strpos($content, $startMarker);
        if ($start === false) return '';

        $start += strlen($startMarker);

        if ($endMarker) {
            $end = strpos($content, $endMarker, $start);
            if ($end === false) {
                $section = substr($content, $start);
            } else {
                $section = substr($content, $start, $end - $start);
            }
        } else {
            $section = substr($content, $start);
        }

        return trim($section);
    }
}
