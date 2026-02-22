<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Centralized AI Model Configuration
 * 
 * This class provides easy access to the configured AI models.
 * All AI services should use these methods instead of hardcoding model names.
 * 
 * Primary: Azure OpenAI (GPT-5.1)
 * Fallback: Azure Anthropic (Claude Sonnet 4.5)
 */
final class ModelConfig
{
    /**
     * Get the primary chat model (GPT-5.1)
     */
    public static function chat(): string
    {
        return config('ai.azure.models.chat', 'gpt-5.1');
    }

    /**
     * Get the mini/fast model for quick operations
     */
    public static function chatMini(): string
    {
        return config('ai.azure.models.chat_mini', 'gpt-5.1');
    }

    /**
     * Get the embeddings model
     */
    public static function embeddings(): string
    {
        return config('ai.azure.models.embeddings', 'text-embedding-3-large');
    }

    /**
     * Get the fallback model (Claude Sonnet 4.5)
     */
    public static function fallback(): string
    {
        return config('ai.anthropic.model', 'claude-sonnet-4-5');
    }

    /**
     * Get the default max tokens
     */
    public static function maxTokens(): int
    {
        return (int) config('ai.parameters.max_tokens', 16384);
    }

    /**
     * Get the default temperature
     */
    public static function temperature(): float
    {
        return (float) config('ai.parameters.temperature', 0.7);
    }

    /**
     * Check if a model is the primary Azure OpenAI model
     */
    public static function isPrimary(string $model): bool
    {
        return str_starts_with($model, 'gpt-');
    }

    /**
     * Check if a model is the Anthropic fallback
     */
    public static function isFallback(string $model): bool
    {
        return str_starts_with($model, 'claude-');
    }
}
