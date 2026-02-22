<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\CareerCoachCheckin;
use App\Models\CareerCoachMessage;
use App\Models\CareerCoachPreference;
use App\Models\CareerCoachSession;
use App\Models\CareerCoachSuggestion;
use App\Models\CareerGoal;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CareerCoachService extends AIService
{
    protected ?User $user = null;
    protected ?CareerCoachPreference $preferences = null;

    /**
     * Initialize the service for a specific user.
     */
    public function forUser(User $user): self
    {
        $this->user = $user;
        $this->preferences = CareerCoachPreference::getOrCreate($user);
        return $this;
    }

    /**
     * Start a new coaching session.
     */
    public function startSession(string $type = CareerCoachSession::TYPE_GENERAL_ADVICE, ?string $title = null): CareerCoachSession
    {
        $context = $this->buildUserContext();

        $session = CareerCoachSession::create([
            'user_id' => $this->user->id,
            'title' => $title ?? CareerCoachSession::getTypeLabels()[$type] ?? 'Coaching Session',
            'session_type' => $type,
            'context' => $context,
            'status' => CareerCoachSession::STATUS_ACTIVE,
        ]);

        // Add system message with context
        $this->addSystemMessage($session, $this->buildSystemPrompt($type));

        // Add initial greeting
        $greeting = $this->generateGreeting($session, $type);
        $this->addAssistantMessage($session, $greeting);

        return $session;
    }

    /**
     * Send a message and get AI response.
     */
    public function sendMessage(CareerCoachSession $session, string $message, bool $isVoice = false): CareerCoachMessage
    {
        // Save user message
        $userMessage = $this->addUserMessage($session, $message, $isVoice);

        // Get conversation history for context
        $conversationHistory = $this->buildConversationHistory($session);

        // Generate AI response
        $response = $this->generateResponse($session, $conversationHistory, $message);

        // Extract entities and sentiment from user message
        $this->analyzeUserMessage($userMessage, $message);

        // Save assistant response
        $assistantMessage = $this->addAssistantMessage($session, $response['content'], $response['metadata'] ?? []);

        // Update session
        $session->incrementMessageCount();

        // Check for action items in the conversation
        if ($session->message_count % 5 === 0) {
            $this->extractActionItems($session);
        }

        return $assistantMessage;
    }

    /**
     * Generate AI response based on conversation.
     */
    protected function generateResponse(CareerCoachSession $session, array $history, string $userMessage): array
    {
        $systemPrompt = $this->buildSystemPrompt($session->session_type);

        // Build messages array for API
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $content = $this->callAzureOpenAI($messages, [
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ]);

            return [
                'content' => $content,
                'metadata' => $this->extractResponseMetadata($content),
            ];
        } catch (\Exception $e) {
            Log::error('Career Coach AI error', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id,
                'session_id' => $session->id,
            ]);

            return [
                'content' => "I apologize, but I'm having trouble processing your request right now. Please try again in a moment, or rephrase your question.",
                'metadata' => ['error' => true],
            ];
        }
    }

    /**
     * Build system prompt based on session type and preferences.
     */
    protected function buildSystemPrompt(string $sessionType): string
    {
        $profile = $this->user->profile;
        $goals = CareerGoal::where('user_id', $this->user->id)
            ->active()
            ->get()
            ->map(fn($g) => "{$g->title} ({$g->getCategoryLabel()}) - {$g->progress_percentage}% complete")
            ->implode("\n- ");

        $coachingStyle = $this->preferences?->getSystemPromptStyle() ?? '';

        $basePrompt = <<<PROMPT
You are an expert AI Career Coach for the StudAI Career platform. Your role is to provide personalized, actionable career guidance.

USER PROFILE:
- Name: {$this->user->name}
- Current Role: {$profile?->headline}
- Years of Experience: {$this->calculateYearsOfExperience()}
- Skills: {$this->formatSkills()}
- Career Goals: {$profile?->career_goals}

ACTIVE GOALS:
{$goals}

COACHING STYLE:
{$coachingStyle}

GUIDELINES:
1. Be conversational and empathetic
2. Provide specific, actionable advice
3. Reference the user's profile and goals when relevant
4. Ask clarifying questions when needed
5. Celebrate progress and achievements
6. When discussing salary or compensation, use Indian Rupees (₹)
7. Break down complex topics into digestible steps
8. Suggest resources and next steps when appropriate
9. Track progress on discussed topics across the conversation
10. Be encouraging but realistic about timelines and expectations
PROMPT;

        // Add session-type specific instructions
        $typeInstructions = match ($sessionType) {
            CareerCoachSession::TYPE_CAREER_PLANNING => "\n\nFOCUS: Help the user create or refine their career plan. Discuss short-term and long-term goals, potential paths, and required skills.",
            CareerCoachSession::TYPE_SKILL_DEVELOPMENT => "\n\nFOCUS: Assist with skill development strategy. Recommend learning resources, certifications, and practical ways to build skills.",
            CareerCoachSession::TYPE_JOB_SEARCH => "\n\nFOCUS: Guide through job search strategies, application optimization, and finding the right opportunities.",
            CareerCoachSession::TYPE_INTERVIEW_PREP => "\n\nFOCUS: Help prepare for interviews. Discuss common questions, company research, and presentation strategies.",
            CareerCoachSession::TYPE_SALARY_NEGOTIATION => "\n\nFOCUS: Provide salary negotiation guidance. Discuss market rates, negotiation tactics, and how to communicate value.",
            CareerCoachSession::TYPE_CAREER_TRANSITION => "\n\nFOCUS: Support career change planning. Discuss transferable skills, bridge gaps, and transition strategies.",
            CareerCoachSession::TYPE_GOAL_REVIEW => "\n\nFOCUS: Review progress on existing goals. Celebrate wins, identify obstacles, and adjust plans as needed.",
            CareerCoachSession::TYPE_WEEKLY_CHECKIN => "\n\nFOCUS: Conduct a weekly check-in. Ask about wins, challenges, and priorities for the coming week.",
            default => '',
        };

        return $basePrompt . $typeInstructions;
    }

    /**
     * Build user context for session.
     */
    protected function buildUserContext(): array
    {
        $profile = $this->user->profile;

        return [
            'user_name' => $this->user->name,
            'headline' => $profile?->headline,
            'skills' => $profile?->skills ?? [],
            'experience_years' => $this->calculateYearsOfExperience(),
            'career_goals' => $profile?->career_goals,
            'active_goals_count' => CareerGoal::where('user_id', $this->user->id)->active()->count(),
            'snapshot_at' => now()->toISOString(),
        ];
    }

    /**
     * Build conversation history for context.
     */
    protected function buildConversationHistory(CareerCoachSession $session, int $limit = 20): array
    {
        return $session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Generate initial greeting based on session type.
     */
    protected function generateGreeting(CareerCoachSession $session, string $type): string
    {
        $userName = $this->user->name;
        $timeOfDay = $this->getTimeOfDay();

        $greetings = match ($type) {
            CareerCoachSession::TYPE_WEEKLY_CHECKIN => "Good {$timeOfDay}, {$userName}! 👋 It's time for our weekly check-in. How has your week been? Let's review your progress and set you up for success in the coming week.",
            CareerCoachSession::TYPE_GOAL_REVIEW => "Hi {$userName}! 🎯 I'm excited to review your career goals with you today. Let's see how you're progressing and make any adjustments needed.",
            CareerCoachSession::TYPE_CAREER_PLANNING => "Hello {$userName}! 🚀 I'm here to help you map out your career journey. What aspects of your career path would you like to explore today?",
            CareerCoachSession::TYPE_SKILL_DEVELOPMENT => "Hi {$userName}! 📚 Ready to level up your skills? Tell me what you're looking to learn or improve, and I'll help you create a learning plan.",
            CareerCoachSession::TYPE_JOB_SEARCH => "Hello {$userName}! 💼 Let's work on your job search strategy. Are you looking for new opportunities, or do you need help with your current applications?",
            CareerCoachSession::TYPE_INTERVIEW_PREP => "Hi {$userName}! 🎤 Interview preparation mode activated! Do you have an upcoming interview you'd like to prepare for, or would you like to practice general interview skills?",
            CareerCoachSession::TYPE_SALARY_NEGOTIATION => "Hello {$userName}! 💰 Let's work on your negotiation strategy. Are you preparing for a new offer, or looking to negotiate a raise?",
            CareerCoachSession::TYPE_CAREER_TRANSITION => "Hi {$userName}! 🔄 Career transitions can be exciting! Tell me about the change you're considering, and I'll help you plan the transition.",
            default => "Good {$timeOfDay}, {$userName}! 👋 I'm your AI Career Coach, here to help you achieve your professional goals. What would you like to discuss today?",
        };

        return $greetings;
    }

    /**
     * Add user message to session.
     */
    protected function addUserMessage(CareerCoachSession $session, string $content, bool $isVoice = false): CareerCoachMessage
    {
        return CareerCoachMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->user->id,
            'role' => CareerCoachMessage::ROLE_USER,
            'content' => $content,
            'is_voice_input' => $isVoice,
        ]);
    }

    /**
     * Add assistant message to session.
     */
    protected function addAssistantMessage(CareerCoachSession $session, string $content, array $metadata = []): CareerCoachMessage
    {
        return CareerCoachMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->user->id,
            'role' => CareerCoachMessage::ROLE_ASSISTANT,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add system message to session.
     */
    protected function addSystemMessage(CareerCoachSession $session, string $content): CareerCoachMessage
    {
        return CareerCoachMessage::create([
            'session_id' => $session->id,
            'user_id' => $this->user->id,
            'role' => CareerCoachMessage::ROLE_SYSTEM,
            'content' => $content,
        ]);
    }

    /**
     * Analyze user message for sentiment and entities.
     */
    protected function analyzeUserMessage(CareerCoachMessage $message, string $content): void
    {
        // Simple sentiment analysis based on keywords
        $positiveKeywords = ['excited', 'happy', 'great', 'awesome', 'achieved', 'success', 'progress'];
        $negativeKeywords = ['frustrated', 'stuck', 'difficult', 'failed', 'worried', 'stressed', 'confused'];

        $lowerContent = strtolower($content);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveKeywords as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $positiveCount++;
            }
        }

        foreach ($negativeKeywords as $keyword) {
            if (str_contains($lowerContent, $keyword)) {
                $negativeCount++;
            }
        }

        $sentiment = 'neutral';
        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
        }

        $message->update(['sentiment' => $sentiment]);
    }

    /**
     * Extract metadata from AI response.
     */
    protected function extractResponseMetadata(string $content): array
    {
        $metadata = [];

        // Check for action items
        if (str_contains(strtolower($content), 'action item') || str_contains($content, '- [ ]')) {
            $metadata['has_action_items'] = true;
        }

        // Check for resource recommendations
        if (preg_match('/(?:course|book|video|article|resource)/i', $content)) {
            $metadata['has_resources'] = true;
        }

        return $metadata;
    }

    /**
     * Extract action items from session.
     */
    public function extractActionItems(CareerCoachSession $session): array
    {
        $messages = $session->messages()
            ->where('role', CareerCoachMessage::ROLE_ASSISTANT)
            ->latest()
            ->limit(5)
            ->pluck('content')
            ->implode("\n\n");

        $prompt = <<<PROMPT
Extract any action items or next steps mentioned in the following conversation excerpt.
Return as a JSON array of action items, each with "task" and "priority" (high/medium/low).
If no action items, return an empty array.

Conversation:
{$messages}
PROMPT;

        try {
            $result = $this->callAIForJSON($prompt);

            if (!empty($result)) {
                $session->update(['action_items' => $result]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('Failed to extract action items', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate session summary.
     */
    public function generateSessionSummary(CareerCoachSession $session): array
    {
        $messages = $session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->pluck('content')
            ->implode("\n\n");

        $prompt = <<<PROMPT
Summarize this career coaching conversation. Return as JSON:
{
  "main_topics": ["Topic 1", "Topic 2"],
  "key_insights": ["Insight 1", "Insight 2"],
  "action_items": ["Action 1", "Action 2"],
  "mood": "positive/neutral/negative",
  "summary": "2-3 sentence summary"
}

Conversation:
{$messages}
PROMPT;

        try {
            $summary = $this->callAIForJSON($prompt);
            $session->update([
                'summary' => $summary,
                'key_insights' => $summary['key_insights'] ?? [],
                'action_items' => $summary['action_items'] ?? [],
            ]);
            return $summary;
        } catch (\Exception $e) {
            Log::warning('Failed to generate session summary', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate proactive suggestions for user.
     */
    public function generateSuggestions(): array
    {
        $goals = CareerGoal::where('user_id', $this->user->id)
            ->active()
            ->with('updates')
            ->get();

        $suggestions = [];

        foreach ($goals as $goal) {
            // Goal nudge if no recent updates
            $lastUpdate = $goal->updates()->latest()->first();
            if (!$lastUpdate || $lastUpdate->created_at->diffInDays(now()) > 7) {
                $suggestions[] = $this->createSuggestion(
                    $goal,
                    CareerCoachSuggestion::TYPE_GOAL_NUDGE,
                    "Time to check in on: {$goal->title}",
                    "You haven't updated progress on '{$goal->title}' recently. Would you like to review your progress?",
                );
            }

            // Deadline reminder
            if ($goal->isOverdue()) {
                $suggestions[] = $this->createSuggestion(
                    $goal,
                    CareerCoachSuggestion::TYPE_DEADLINE_REMINDER,
                    "Goal deadline passed: {$goal->title}",
                    "The target date for '{$goal->title}' has passed. Would you like to reschedule or close this goal?",
                    'high'
                );
            } elseif ($goal->getDaysRemaining() !== null && $goal->getDaysRemaining() <= 7 && $goal->getDaysRemaining() > 0) {
                $days = $goal->getDaysRemaining();
                $suggestions[] = $this->createSuggestion(
                    $goal,
                    CareerCoachSuggestion::TYPE_DEADLINE_REMINDER,
                    "Goal deadline approaching: {$goal->title}",
                    "Only {$days} days left to complete '{$goal->title}'. You're at {$goal->progress_percentage}% progress.",
                    'high'
                );
            }

            // Celebration for completed milestones
            if ($goal->progress_percentage >= 100) {
                $suggestions[] = $this->createSuggestion(
                    $goal,
                    CareerCoachSuggestion::TYPE_CELEBRATION,
                    "🎉 Goal completed: {$goal->title}",
                    "Congratulations on completing '{$goal->title}'! What an achievement!",
                );
            }
        }

        return $suggestions;
    }

    /**
     * Create a suggestion.
     */
    protected function createSuggestion(
        ?CareerGoal $goal,
        string $type,
        string $title,
        string $content,
        string $priority = 'medium'
    ): CareerCoachSuggestion {
        return CareerCoachSuggestion::create([
            'user_id' => $this->user->id,
            'goal_id' => $goal?->id,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'priority' => $priority,
            'expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Schedule weekly check-ins.
     */
    public function scheduleWeeklyCheckins(int $weeksAhead = 4): void
    {
        if (!$this->preferences?->weekly_checkins_enabled) {
            return;
        }

        $day = $this->preferences->preferred_checkin_day;
        $dayMap = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0,
        ];

        $targetDay = $dayMap[$day] ?? 1;
        $nextDate = now()->next($targetDay);

        for ($i = 0; $i < $weeksAhead; $i++) {
            $scheduledDate = $nextDate->copy()->addWeeks($i);

            CareerCoachCheckin::firstOrCreate(
                [
                    'user_id' => $this->user->id,
                    'scheduled_for' => $scheduledDate->toDateString(),
                ],
                [
                    'status' => CareerCoachCheckin::STATUS_SCHEDULED,
                ]
            );
        }
    }

    /**
     * Start weekly check-in session.
     */
    public function startCheckin(CareerCoachCheckin $checkin): CareerCoachSession
    {
        $session = $this->startSession(CareerCoachSession::TYPE_WEEKLY_CHECKIN, 'Weekly Check-in');

        $checkin->update(['session_id' => $session->id]);

        return $session;
    }

    /**
     * Complete weekly check-in.
     */
    public function completeCheckin(CareerCoachCheckin $checkin, CareerCoachSession $session): void
    {
        // Generate summary
        $summary = $this->generateSessionSummary($session);

        // Mark check-in complete
        $checkin->markCompleted([
            'goals_reviewed' => CareerGoal::where('user_id', $this->user->id)
                ->active()
                ->pluck('id')
                ->toArray(),
            'ai_summary' => $summary,
        ]);

        // Mark session as completed
        $session->markCompleted($summary);
    }

    /**
     * Get pending check-ins for user.
     */
    public function getPendingCheckins()
    {
        return CareerCoachCheckin::where('user_id', $this->user->id)
            ->pending()
            ->orderBy('scheduled_for')
            ->get();
    }

    /**
     * Get active suggestions for user.
     */
    public function getActiveSuggestions()
    {
        return CareerCoachSuggestion::where('user_id', $this->user->id)
            ->active()
            ->unread()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Calculate years of experience.
     */
    protected function calculateYearsOfExperience(): int
    {
        $profile = $this->user->profile;
        if (!$profile || !$profile->experience) {
            return 0;
        }

        $totalMonths = 0;
        foreach ($profile->experience as $exp) {
            $startDate = $exp['start_date'] ?? null;
            $endDate = $exp['end_date'] ?? now()->format('Y-m-d');
            $isCurrent = $exp['is_current'] ?? false;

            if ($startDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = $isCurrent ? now() : \Carbon\Carbon::parse($endDate);
                $totalMonths += $start->diffInMonths($end);
            }
        }

        return (int) round($totalMonths / 12);
    }

    /**
     * Format skills for prompt.
     */
    protected function formatSkills(): string
    {
        $profile = $this->user->profile;
        $skills = $profile?->skills ?? [];

        if (empty($skills)) {
            return 'Not specified';
        }

        return implode(', ', array_slice($skills, 0, 15));
    }

    /**
     * Get time of day for greeting.
     */
    protected function getTimeOfDay(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'morning';
        } elseif ($hour < 17) {
            return 'afternoon';
        } else {
            return 'evening';
        }
    }

    /**
     * Call AI for JSON response (wrapper for parent method).
     */
    protected function callAIForJSON(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt . "\n\nRespond with valid JSON only."];

        try {
            $response = $this->callAzureOpenAI($messages, array_merge([
                'temperature' => 0.3,
                'max_tokens' => 2048,
            ], $options));

            // Extract JSON from response
            preg_match('/\{[\s\S]*\}|\[[\s\S]*\]/', $response, $matches);
            
            if (!empty($matches[0])) {
                return json_decode($matches[0], true) ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Career Coach JSON call failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
