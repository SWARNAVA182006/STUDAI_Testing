<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AIFormAssistantController extends Controller
{
    protected AIService $aiService;

    // Context prompts for different form fields
    protected array $fieldContexts = [
        'summary' => 'You are helping a job seeker write their professional summary. Keep it concise, impactful, and focused on their value proposition.',
        'headline' => 'You are helping create a professional headline for a career profile. Make it attention-grabbing and keyword-rich.',
        'experience_description' => 'You are helping describe work experience. Focus on achievements, impact, and quantifiable results.',
        'cover_letter' => 'You are writing a compelling cover letter. Be professional yet personable, and show enthusiasm.',
        'career_goals' => 'You are helping articulate career goals. Be specific, realistic, and show ambition.',
        'project_description' => 'You are describing a project. Highlight the problem solved, technologies used, and impact achieved.',
        'skill_description' => 'You are describing skill proficiency. Be specific about experience level and applications.',
        'bio' => 'You are writing a professional bio. Keep it engaging and highlight key accomplishments.',
    ];

    // Tone modifiers
    protected array $toneModifiers = [
        'professional' => 'Use formal, business-appropriate language. Be direct and clear.',
        'friendly' => 'Use warm, approachable language while remaining professional.',
        'confident' => 'Use assertive, strong language that shows conviction and expertise.',
        'creative' => 'Use engaging, creative language that stands out while staying professional.',
        'concise' => 'Be extremely brief and to the point. Every word should count.',
    ];

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Generate text content using AI
     */
    public function generateText(Request $request): JsonResponse
    {
        $request->validate([
            'field' => 'required|string|max:50',
            'context' => 'nullable|string|max:500',
            'prompt' => 'nullable|string|max:500',
            'tone' => 'nullable|string|in:professional,friendly,confident,creative,concise',
            'current_text' => 'nullable|string|max:2000',
        ]);

        // Rate limiting
        $key = 'ai-generate:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'error' => 'Too many requests. Please wait a moment.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $field = $request->input('field');
            $tone = $request->input('tone', 'professional');
            $userContext = $request->input('context', '');
            $currentText = $request->input('current_text', '');

            // Build system prompt
            $systemPrompt = $this->fieldContexts[$field] ?? 'You are a helpful writing assistant.';
            $systemPrompt .= "\n\n" . ($this->toneModifiers[$tone] ?? $this->toneModifiers['professional']);
            $systemPrompt .= "\n\nIMPORTANT: Return ONLY the generated text, no explanations, no quotes, no markdown formatting.";

            // Build user prompt
            $userPrompt = "Generate content for a '{$field}' field.";
            if ($userContext) {
                $userPrompt .= "\n\nContext: {$userContext}";
            }
            if ($currentText) {
                $userPrompt .= "\n\nCurrent draft to improve upon: {$currentText}";
            }

            // Get user profile context if authenticated
            if ($request->user()) {
                $profile = $request->user()->profile;
                if ($profile) {
                    $userPrompt .= "\n\nUser background:";
                    if ($profile->headline) $userPrompt .= "\n- Headline: {$profile->headline}";
                    if (!empty($profile->skills)) {
                        $skillNames = collect($profile->skills)->pluck('name')->take(10)->implode(', ');
                        $userPrompt .= "\n- Skills: {$skillNames}";
                    }
                    if (!empty($profile->experience)) {
                        $latestExp = $profile->experience[0] ?? null;
                        if ($latestExp) {
                            $userPrompt .= "\n- Current/Latest Role: {$latestExp['title']} at {$latestExp['company']}";
                        }
                    }
                }
            }

            // Generate with AI
            $text = $this->aiService
                ->forUser($request->user())
                ->generateText($userPrompt, $systemPrompt, [
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            // Clean up the response
            $text = trim($text, '"\'');
            $text = preg_replace('/^(Here\'s|Here is|I\'ve written|This is).*?:\s*/i', '', $text);

            return response()->json([
                'text' => $text,
                'field' => $field,
            ]);

        } catch (\Exception $e) {
            Log::error('AI text generation failed', [
                'error' => $e->getMessage(),
                'field' => $request->input('field'),
            ]);

            return response()->json([
                'error' => 'Failed to generate text. Please try again.',
            ], 500);
        }
    }

    /**
     * Enhance existing text using AI
     */
    public function enhanceText(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:2000',
            'field' => 'nullable|string|max:50',
            'tone' => 'nullable|string|in:professional,friendly,confident,creative,concise',
            'action' => 'nullable|string|in:improve,shorten,expand,fix_grammar,make_impactful',
        ]);

        // Rate limiting
        $key = 'ai-enhance:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json([
                'error' => 'Too many requests. Please wait a moment.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $text = $request->input('text');
            $field = $request->input('field', 'general');
            $tone = $request->input('tone', 'professional');
            $action = $request->input('action', 'improve');

            $actions = [
                'improve' => 'Improve this text to be more professional, clear, and impactful.',
                'shorten' => 'Make this text more concise while preserving key information.',
                'expand' => 'Expand this text with more detail and depth.',
                'fix_grammar' => 'Fix any grammar, spelling, or punctuation errors in this text.',
                'make_impactful' => 'Rewrite this to be more action-oriented and results-focused.',
            ];

            $systemPrompt = "You are an expert editor helping improve professional content. ";
            $systemPrompt .= $this->toneModifiers[$tone] ?? $this->toneModifiers['professional'];
            $systemPrompt .= "\n\nIMPORTANT: Return ONLY the enhanced text, no explanations, no quotes, no markdown formatting.";

            $userPrompt = $actions[$action] ?? $actions['improve'];
            $userPrompt .= "\n\nOriginal text:\n{$text}";

            $enhanced = $this->aiService
                ->forUser($request->user())
                ->generateText($userPrompt, $systemPrompt, [
                    'temperature' => 0.5,
                    'max_tokens' => 600,
                ]);

            // Clean up
            $enhanced = trim($enhanced, '"\'');

            return response()->json([
                'enhanced' => $enhanced,
                'original' => $text,
                'action' => $action,
            ]);

        } catch (\Exception $e) {
            Log::error('AI text enhancement failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to enhance text. Please try again.',
            ], 500);
        }
    }

    /**
     * Get AI suggestions for a field
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'field' => 'required|string|max:50',
            'context' => 'nullable|string|max:500',
        ]);

        try {
            $field = $request->input('field');
            $context = $request->input('context', '');

            $systemPrompt = "You are helping generate options for a form field. Provide 5 concise, varied suggestions.";
            $systemPrompt .= "\n\nReturn as a JSON array of strings, nothing else.";

            $userPrompt = "Generate 5 suggestions for '{$field}' field.";
            if ($context) {
                $userPrompt .= "\n\nContext: {$context}";
            }

            // Get profile context
            if ($request->user()?->profile) {
                $profile = $request->user()->profile;
                if (!empty($profile->skills)) {
                    $skillNames = collect($profile->skills)->pluck('name')->take(5)->implode(', ');
                    $userPrompt .= "\n\nUser's skills: {$skillNames}";
                }
            }

            $response = $this->aiService
                ->forUser($request->user())
                ->generateJSON($userPrompt, $systemPrompt);

            return response()->json([
                'suggestions' => $response,
                'field' => $field,
            ]);

        } catch (\Exception $e) {
            Log::error('AI suggestions failed', ['error' => $e->getMessage()]);

            return response()->json([
                'suggestions' => [],
                'error' => 'Could not generate suggestions.',
            ]);
        }
    }

    /**
     * Auto-complete text as user types
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:500',
            'field' => 'nullable|string|max:50',
        ]);

        // Strict rate limiting for autocomplete
        $key = 'ai-autocomplete:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json(['completion' => ''], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $text = $request->input('text');

            // Only complete if text is substantial enough
            if (strlen($text) < 20) {
                return response()->json(['completion' => '']);
            }

            $systemPrompt = "You are an autocomplete assistant. Complete the user's sentence naturally.";
            $systemPrompt .= "\n\nRules:\n- Return ONLY the completion (the next few words)\n- Keep it brief (5-15 words max)\n- Match the tone and style\n- If the sentence seems complete, return empty string";

            $userPrompt = "Complete this text naturally:\n\n{$text}";

            $completion = $this->aiService
                ->forUser($request->user())
                ->generateText($userPrompt, $systemPrompt, [
                    'temperature' => 0.3,
                    'max_tokens' => 50,
                ]);

            return response()->json([
                'completion' => trim($completion),
            ]);

        } catch (\Exception $e) {
            return response()->json(['completion' => '']);
        }
    }

    /**
     * Generate work experience description using AI
     */
    public function generateExperienceDescription(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'company' => 'required|string|max:200',
            'current_description' => 'nullable|string|max:1000',
        ]);

        $key = 'ai-exp-desc:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $title = $request->input('title');
            $company = $request->input('company');
            $currentDesc = $request->input('current_description', '');

            $systemPrompt = "You are an expert resume writer. Generate a professional job description for a resume.";
            $systemPrompt .= "\n\nRules:\n- Write 2-4 sentences\n- Focus on responsibilities and impact\n- Use action verbs\n- Be specific but not too detailed\n- Return ONLY the description text, no quotes or formatting";

            $userPrompt = "Generate a professional job description for:\nTitle: {$title}\nCompany: {$company}";
            if ($currentDesc) {
                $userPrompt .= "\n\nCurrent draft to improve: {$currentDesc}";
            }

            $description = $this->aiService
                ->forUser($request->user())
                ->generateText($userPrompt, $systemPrompt, [
                    'temperature' => 0.7,
                    'max_tokens' => 300,
                ]);

            return response()->json([
                'description' => trim($description, '"\''),
            ]);

        } catch (\Exception $e) {
            Log::error('AI experience description failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate description.'], 500);
        }
    }

    /**
     * Suggest achievement bullet points for a job
     */
    public function suggestAchievements(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'company' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        $key = 'ai-achievements:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $title = $request->input('title');
            $company = $request->input('company');
            $description = $request->input('description', '');

            $systemPrompt = "You are an expert resume writer. Generate impactful achievement bullet points.";
            $systemPrompt .= "\n\nRules:\n- Generate exactly 5 achievements\n- Start each with a strong action verb\n- Include metrics/numbers where possible (e.g., 'Increased sales by 25%')\n- Focus on results and impact\n- Return as a JSON array of strings";

            $userPrompt = "Generate 5 achievement bullet points for:\nTitle: {$title}\nCompany: {$company}";
            if ($description) {
                $userPrompt .= "\nRole context: {$description}";
            }

            $achievements = $this->aiService
                ->forUser($request->user())
                ->generateJSON($userPrompt, $systemPrompt);

            return response()->json([
                'achievements' => $achievements,
            ]);

        } catch (\Exception $e) {
            Log::error('AI achievements failed', ['error' => $e->getMessage()]);
            return response()->json([
                'achievements' => [
                    'Led cross-functional team to deliver key projects on time',
                    'Improved process efficiency resulting in significant cost savings',
                    'Collaborated with stakeholders to achieve business objectives',
                    'Implemented best practices that enhanced team productivity',
                    'Contributed to company growth through innovative solutions',
                ],
            ]);
        }
    }

    /**
     * Suggest skills based on job context
     */
    public function suggestSkills(Request $request): JsonResponse
    {
        $request->validate([
            'context' => 'nullable|string|max:500',
            'existing_skills' => 'nullable|array',
            'existing_skills.*' => 'string|max:100',
        ]);

        $key = 'ai-skills:' . ($request->user()?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $context = $request->input('context', '');
            $existingSkills = $request->input('existing_skills', []);

            // Build context from user profile if authenticated
            if (!$context && $request->user()) {
                $profile = $request->user()->profile;
                if ($profile) {
                    if ($profile->headline) {
                        $context = "Current role: {$profile->headline}";
                    }
                    if (!empty($profile->experience)) {
                        $latestExp = $profile->experience[0] ?? null;
                        if ($latestExp) {
                            $context .= ". Recent experience: {$latestExp['title']} at {$latestExp['company']}";
                        }
                    }
                }
            }

            $systemPrompt = "You are a career advisor suggesting relevant skills.";
            $systemPrompt .= "\n\nRules:\n- Suggest 10 skills that are relevant to the context\n- Mix technical and soft skills\n- Include both common and trending skills\n- Don't repeat existing skills\n- Return as JSON array of objects with: name, category (technical/soft/tools/languages)";

            $userPrompt = "Suggest 10 relevant skills";
            if ($context) {
                $userPrompt .= " for someone with this background: {$context}";
            }
            if (!empty($existingSkills)) {
                $userPrompt .= "\n\nAlready have these skills (don't suggest these): " . implode(', ', $existingSkills);
            }

            $skills = $this->aiService
                ->forUser($request->user())
                ->generateJSON($userPrompt, $systemPrompt);

            return response()->json([
                'skills' => $skills,
            ]);

        } catch (\Exception $e) {
            Log::error('AI skills suggestion failed', ['error' => $e->getMessage()]);
            // Return fallback suggestions
            return response()->json([
                'skills' => [
                    ['name' => 'Communication', 'category' => 'soft'],
                    ['name' => 'Problem Solving', 'category' => 'soft'],
                    ['name' => 'Project Management', 'category' => 'soft'],
                    ['name' => 'Data Analysis', 'category' => 'technical'],
                    ['name' => 'Microsoft Excel', 'category' => 'tools'],
                ],
            ]);
        }
    }
}
