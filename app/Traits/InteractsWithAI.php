<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\AI\AIService;
use App\Services\AI\PromptRegistryService;
use App\Models\User;

/**
 * Trait for AI service classes that need to call AI APIs.
 *
 * Provides a unified interface to AIService (circuit breaker, fallback,
 * caching, usage tracking) and PromptRegistryService (versioned prompts).
 *
 * Usage:
 *   use InteractsWithAI;
 *
 *   // Simple call
 *   $result = $this->ai('Analyze this resume...', 'You are an expert resume writer.');
 *
 *   // With PromptRegistry
 *   $result = $this->aiFromRegistry('resume_analysis', ['resume' => $content]);
 *
 *   // JSON response
 *   $result = $this->aiJSON('Return skills as JSON', $systemPrompt);
 *
 *   // With options
 *   $result = $this->ai($prompt, $system, ['model' => 'gpt-5-mini', 'temperature' => 0.3]);
 */
trait InteractsWithAI
{
    protected ?AIService $aiService = null;
    protected ?PromptRegistryService $promptRegistry = null;

    /**
     * Get or create the AIService instance.
     */
    protected function getAIService(): AIService
    {
        if (!$this->aiService) {
            $this->aiService = app(AIService::class);
        }

        return $this->aiService;
    }

    /**
     * Get or create the PromptRegistryService instance.
     */
    protected function getPromptRegistry(): PromptRegistryService
    {
        if (!$this->promptRegistry) {
            $this->promptRegistry = app(PromptRegistryService::class);
        }

        return $this->promptRegistry;
    }

    /**
     * Set the user context for AI usage tracking / credit deduction.
     */
    protected function setAIUser(User $user): self
    {
        $this->getAIService()->forUser($user);

        return $this;
    }

    /**
     * Call AI and return text response.
     *
     * Routes through AIService → circuit breaker → Azure OpenAI → Anthropic fallback.
     *
     * Options:
     *   - model: string (override deployment model)
     *   - temperature: float (0.0 – 2.0)
     *   - max_tokens: int
     *   - cache_hours: int (default 1)
     *   - skip_cache: bool
     */
    protected function ai(string $prompt, ?string $systemPrompt = null, array $options = []): string
    {
        return $this->getAIService()->generateText($prompt, $systemPrompt, $options);
    }

    /**
     * Call AI and return parsed JSON array.
     */
    protected function aiJSON(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        return $this->getAIService()->generateJSON($prompt, $systemPrompt, $options);
    }

    /**
     * Call AI using a registered prompt from PromptRegistryService.
     *
     * Falls back to inline prompt if registry entry not found.
     *
     * @param string      $promptName   Registry prompt name (e.g. 'resume_analysis')
     * @param array       $variables    Variables to render into the template
     * @param string|null $fallbackPrompt  Inline prompt used when registry entry is missing
     * @param string|null $fallbackSystem  Inline system prompt used as fallback
     * @param array       $options      Additional options
     */
    protected function aiFromRegistry(
        string $promptName,
        array $variables = [],
        ?string $fallbackPrompt = null,
        ?string $fallbackSystem = null,
        array $options = [],
    ): string {
        $registry = $this->getPromptRegistry();
        $renderedPrompt = $registry->render($promptName, $variables);
        $systemPrompt = $registry->getSystemPrompt($promptName);

        // Merge registry config (model hint, temperature, max_tokens)
        $promptConfig = $registry->getConfig($promptName);
        if ($promptConfig['model']) {
            $options['model'] = $options['model'] ?? $promptConfig['model'];
        }
        if ($promptConfig['temperature'] !== null) {
            $options['temperature'] = $options['temperature'] ?? $promptConfig['temperature'];
        }

        // Use rendered registry prompt, or fall back to inline
        $prompt = $renderedPrompt ?? $fallbackPrompt ?? '';
        $system = $systemPrompt ?? $fallbackSystem;

        if (empty($prompt)) {
            throw new \InvalidArgumentException(
                "Prompt '{$promptName}' not found in registry and no fallback provided."
            );
        }

        return $this->ai($prompt, $system, $options);
    }

    /**
     * Call AI for JSON using a registered prompt.
     */
    protected function aiJSONFromRegistry(
        string $promptName,
        array $variables = [],
        ?string $fallbackPrompt = null,
        ?string $fallbackSystem = null,
        array $options = [],
    ): array {
        $registry = $this->getPromptRegistry();
        $renderedPrompt = $registry->render($promptName, $variables);
        $systemPrompt = $registry->getSystemPrompt($promptName);

        $promptConfig = $registry->getConfig($promptName);
        if ($promptConfig['model']) {
            $options['model'] = $options['model'] ?? $promptConfig['model'];
        }
        if ($promptConfig['temperature'] !== null) {
            $options['temperature'] = $options['temperature'] ?? $promptConfig['temperature'];
        }

        $prompt = $renderedPrompt ?? $fallbackPrompt ?? '';
        $system = $systemPrompt ?? $fallbackSystem;

        if (empty($prompt)) {
            throw new \InvalidArgumentException(
                "Prompt '{$promptName}' not found in registry and no fallback provided."
            );
        }

        return $this->aiJSON($prompt, $system, $options);
    }
}
