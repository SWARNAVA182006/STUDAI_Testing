<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\AIConversation;
use App\Models\User;
use App\Services\CircuitBreakerService;
use App\Exceptions\CircuitOpenException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AIService 
{
    protected string $model;
    protected int $maxTokens;
    protected float $temperature;
    protected int $timeout;
    protected ?User $user = null;
    protected string $provider;

    public function __construct()
    {
        $this->provider = config('ai.provider', 'azure');
        $this->model = config('ai.default_model', 'gpt-5.1');
        $this->maxTokens = config('ai.max_tokens', 16384);
        $this->temperature = config('ai.temperature', 0.7);
        $this->timeout = config('ai.timeout.default', 60);
    }

    /**
     * Set the user for tracking purposes
     */
    public function forUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Call Azure OpenAI API (Primary)
     */
    protected function callAzureOpenAI(array $messages, array $options = []): string
    {
        $endpoint = config('ai.azure.endpoint');
        $apiKey = config('ai.azure.api_key');
        $deploymentId = config('ai.azure.deployment_id', 'gpt-5.1');
        $apiVersion = config('ai.azure.api_version', '2024-12-01-preview');

        if (empty($endpoint) || empty($apiKey)) {
            throw new \Exception('Azure OpenAI credentials not configured');
        }

        $url = rtrim($endpoint, '/') . "/openai/deployments/{$deploymentId}/chat/completions?api-version={$apiVersion}";

        $response = Http::timeout($options['timeout'] ?? $this->timeout)
            ->withHeaders([
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'messages' => $messages,
                'max_completion_tokens' => $options['max_tokens'] ?? $this->maxTokens,
                'temperature' => $options['temperature'] ?? $this->temperature,
            ]);

        if (!$response->successful()) {
            Log::error('Azure OpenAI API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Azure OpenAI API failed: ' . $response->body());
        }

        $data = $response->json();
        
        // Track usage
        $this->trackAzureUsage($data, $messages);
        
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Call Azure Anthropic API (Fallback - Claude Sonnet 4.5)
     */
    protected function callAzureAnthropic(array $messages, array $options = []): string
    {
        $endpoint = config('ai.anthropic.endpoint');
        $apiKey = config('ai.anthropic.api_key');
        $model = config('ai.anthropic.model', 'claude-sonnet-4-5');

        if (empty($endpoint) || empty($apiKey)) {
            throw new \Exception('Azure Anthropic credentials not configured');
        }

        // Convert OpenAI message format to Anthropic format
        $anthropicMessages = $this->convertToAnthropicFormat($messages);

        $url = rtrim($endpoint, '/') . "/v1/messages";

        $response = Http::timeout($options['timeout'] ?? $this->timeout)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])
            ->post($url, [
                'model' => $model,
                'messages' => $anthropicMessages['messages'],
                'system' => $anthropicMessages['system'] ?? '',
                'max_tokens' => $options['max_tokens'] ?? config('ai.anthropic.max_tokens', 4096),
            ]);

        if (!$response->successful()) {
            Log::error('Azure Anthropic API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Azure Anthropic API failed: ' . $response->body());
        }

        $data = $response->json();
        
        // Track Anthropic usage
        $this->trackAnthropicUsage($data, $messages);
        
        return $data['content'][0]['text'] ?? '';
    }

    /**
     * Convert OpenAI message format to Anthropic format
     */
    protected function convertToAnthropicFormat(array $messages): array
    {
        $system = '';
        $anthropicMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system = $message['content'];
            } else {
                $anthropicMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ];
            }
        }

        return [
            'system' => $system,
            'messages' => $anthropicMessages,
        ];
    }

    /**
     * Call AI API with caching, rate limiting, and fallback
     */
    protected function callAI(string $prompt, ?string $systemPrompt = null, array $options = []): string
    {
        // Check user's AI credits if user is set
        if ($this->user && !$this->user->hasAICredits()) {
            throw new \Exception('Insufficient AI credits. Please upgrade your plan.');
        }

        $cacheKey = 'ai_response_' . md5($prompt . $systemPrompt);
        
        // Check cache if enabled
        if (!($options['skip_cache'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info('AI response served from cache', ['prompt_hash' => md5($prompt)]);
                return $cached;
            }
        }

        // Build messages array
        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Get circuit breakers for both providers
        $openAICircuit = CircuitBreakerService::forAzureOpenAI();
        $anthropicCircuit = CircuitBreakerService::forAzureAnthropic();

        try {
            // Primary: Azure OpenAI (GPT-5.1) with Circuit Breaker
            Log::info('Calling Azure OpenAI API', [
                'model' => $this->model,
                'tokens' => $this->maxTokens,
                'user_id' => $this->user?->id,
                'circuit_state' => $openAICircuit->getState(),
            ]);

            $content = $openAICircuit->execute(
                fn () => $this->callAzureOpenAI($messages, $options)
            );

            // Cache the response
            Cache::put($cacheKey, $content, now()->addHours($options['cache_hours'] ?? 1));

            return $content;

        } catch (CircuitOpenException $e) {
            // Circuit is open, go directly to fallback
            Log::warning('Azure OpenAI circuit breaker is OPEN, using Anthropic fallback', [
                'service' => 'azure_openai',
                'user_id' => $this->user?->id,
            ]);

        } catch (\Exception $e) {
            Log::warning('Azure OpenAI failed, trying Anthropic fallback', [
                'error' => $e->getMessage(),
                'user_id' => $this->user?->id,
                'circuit_state' => $openAICircuit->getState(),
            ]);
        }

        // Fallback: Azure Anthropic (Claude Sonnet 4.5) with Circuit Breaker
        if (config('ai.fallback.use_anthropic_if_azure_fails', true)) {
            try {
                Log::info('Calling Azure Anthropic API (fallback)', [
                    'circuit_state' => $anthropicCircuit->getState(),
                    'user_id' => $this->user?->id,
                ]);

                $content = $anthropicCircuit->execute(
                    fn () => $this->callAzureAnthropic($messages, $options)
                );

                // Cache the response
                Cache::put($cacheKey, $content, now()->addHours($options['cache_hours'] ?? 1));

                return $content;

            } catch (CircuitOpenException $fallbackCircuitError) {
                Log::error('Both Azure OpenAI and Anthropic circuit breakers are OPEN', [
                    'user_id' => $this->user?->id,
                ]);
            } catch (\Exception $fallbackError) {
                Log::error('Both Azure OpenAI and Anthropic failed', [
                    'azure_error' => $e->getMessage() ?? 'Circuit open',
                    'anthropic_error' => $fallbackError->getMessage(),
                    'anthropic_circuit_state' => $anthropicCircuit->getState(),
                ]);
            }
        }

        // Return fallback response
        return $this->fallbackResponse($prompt, $e ?? null);
    }

    /**
     * Call AI with structured JSON response
     */
    protected function callAIForJSON(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        $response = $this->callAI($prompt, $systemPrompt, $options);
        
        try {
            // Extract JSON from markdown code blocks if present
            if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $response, $matches)) {
                $jsonString = $matches[1];
            } else {
                $jsonString = $response;
            }
            
            $decoded = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }
            
            return $decoded;
            
        } catch (\Exception $e) {
            Log::error('Failed to parse AI JSON response', [
                'error' => $e->getMessage(),
                'response' => $response
            ]);
            
            throw new \Exception('AI returned invalid JSON format');
        }
    }

    /**
     * Generate embeddings for semantic search
     */
    protected function generateEmbedding(string $text): array
    {
        $cacheKey = 'embedding_' . md5($text);
        
        return Cache::remember($cacheKey, now()->addDays(7), function() use ($text) {
            try {
                $timeout = config('ai.timeout.embeddings', 15);
                $response = OpenAI::timeout($timeout)->embeddings()->create([
                    'model' => 'text-embedding-ada-002',
                    'input' => $text,
                ]);
                
                return $response->embeddings[0]->embedding;
                
            } catch (\Exception $e) {
                Log::error('Embedding generation failed', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Calculate cosine similarity between two embeddings
     */
    protected function cosineSimilarity(array $embedding1, array $embedding2): float
    {
        if (empty($embedding1) || empty($embedding2)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] ** 2;
            $magnitude2 += $embedding2[$i] ** 2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Track Azure OpenAI usage and costs
     */
    protected function trackAzureUsage(array $data, array $messages): void
    {
        try {
            $usage = $data['usage'] ?? [];
            $tokensUsed = ($usage['prompt_tokens'] ?? 0) + ($usage['completion_tokens'] ?? 0);
            
            // Calculate cost (Azure OpenAI GPT-5.1 pricing estimate)
            $cost = (($usage['prompt_tokens'] ?? 0) * 0.01 / 1000) + 
                    (($usage['completion_tokens'] ?? 0) * 0.03 / 1000);

            // Store conversation if user is set
            if ($this->user) {
                $systemPrompt = collect($messages)->firstWhere('role', 'system')['content'] ?? null;
                $userPrompt = collect($messages)->firstWhere('role', 'user')['content'] ?? '';
                
                AIConversation::create([
                    'user_id' => $this->user->id,
                    'context' => debug_backtrace()[3]['class'] ?? 'azure_openai',
                    'messages' => array_merge($messages, [
                        ['role' => 'assistant', 'content' => $data['choices'][0]['message']['content'] ?? '']
                    ]),
                    'tokens_used' => $tokensUsed,
                    'cost' => $cost,
                ]);

                $this->deductAICredits($tokensUsed);
            }

            Log::info('Azure OpenAI usage tracked', [
                'user_id' => $this->user?->id,
                'tokens' => $tokensUsed,
                'cost' => $cost,
                'model' => 'gpt-5.1',
                'provider' => 'azure_openai'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track Azure OpenAI usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track Azure Anthropic (Claude) usage and costs
     */
    protected function trackAnthropicUsage(array $data, array $messages): void
    {
        try {
            $usage = $data['usage'] ?? [];
            $tokensUsed = ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0);
            
            // Calculate cost (Claude Sonnet 4.5 pricing estimate)
            $cost = (($usage['input_tokens'] ?? 0) * 0.003 / 1000) + 
                    (($usage['output_tokens'] ?? 0) * 0.015 / 1000);

            // Store conversation if user is set
            if ($this->user) {
                AIConversation::create([
                    'user_id' => $this->user->id,
                    'context' => debug_backtrace()[3]['class'] ?? 'azure_anthropic',
                    'messages' => array_merge($messages, [
                        ['role' => 'assistant', 'content' => $data['content'][0]['text'] ?? '']
                    ]),
                    'tokens_used' => $tokensUsed,
                    'cost' => $cost,
                ]);

                $this->deductAICredits($tokensUsed);
            }

            Log::info('Azure Anthropic usage tracked', [
                'user_id' => $this->user?->id,
                'tokens' => $tokensUsed,
                'cost' => $cost,
                'model' => 'claude-sonnet-4-5',
                'provider' => 'azure_anthropic'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track Azure Anthropic usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track AI usage and costs (Legacy - for OpenAI SDK responses)
     */
    protected function trackUsage($response, string $prompt, ?string $systemPrompt): void
    {
        try {
            $usage = $response->usage;
            $tokensUsed = $usage->totalTokens ?? 0;
            
            // Calculate cost (Azure OpenAI pricing: GPT-5.1)
            $cost = (($usage->promptTokens ?? 0) * 0.01 / 1000) + 
                    (($usage->completionTokens ?? 0) * 0.03 / 1000);

            // Store conversation if user is set
            if ($this->user) {
                AIConversation::create([
                    'user_id' => $this->user->id,
                    'context' => debug_backtrace()[2]['class'] ?? 'unknown',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                        ['role' => 'assistant', 'content' => $response->choices[0]->message->content]
                    ],
                    'tokens_used' => $tokensUsed,
                    'cost' => $cost,
                ]);

                // Deduct AI credits from user's subscription
                $this->deductAICredits($tokensUsed);
            }

            Log::info('AI usage tracked', [
                'user_id' => $this->user?->id,
                'tokens' => $tokensUsed,
                'cost' => $cost,
                'model' => $this->model
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track AI usage', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Deduct AI credits from user's subscription
     */
    protected function deductAICredits(int $tokens): void
    {
        if (!$this->user) {
            return;
        }

        $subscription = $this->user->subscription;
        if (!$subscription) {
            return;
        }

        // Convert tokens to credits (e.g., 1000 tokens = 1 credit)
        $creditsToDeduct = ceil($tokens / 1000);

        $subscription->increment('ai_credits_used_this_month', $creditsToDeduct);

        Log::info('AI credits deducted', [
            'user_id' => $this->user->id,
            'credits_deducted' => $creditsToDeduct,
            'remaining' => $subscription->subscriptionPlan->ai_credits - $subscription->ai_credits_used_this_month
        ]);
    }

    /**
     * Fallback response when AI fails
     */
    protected function fallbackResponse(string $prompt, ?\Exception $error = null): string
    {
        // Log the failure
        Log::warning('Using fallback AI response', [
            'prompt_hash' => md5($prompt),
            'error' => $error?->getMessage()
        ]);

        // Try to return a cached similar response
        $similarCacheKey = $this->findSimilarCachedResponse($prompt);
        if ($similarCacheKey) {
            return Cache::get($similarCacheKey);
        }

        // Return generic fallback message
        return "I'm currently experiencing technical difficulties. Please try again in a moment.";
    }

    /**
     * Find similar cached responses for fallback
     */
    protected function findSimilarCachedResponse(string $prompt): ?string
    {
        // Simplified: In production, use vector similarity search
        $cacheKeys = Cache::getRedis()->keys('ai_response_*');
        
        foreach ($cacheKeys as $key) {
            // Return first available cached response
            if (Cache::has($key)) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Check if user has available AI credits
     */
    protected function hasAICredits(): bool
    {
        if (!$this->user) {
            return true; // Allow if no user context
        }

        $subscription = $this->user->subscription;
        if (!$subscription) {
            return false;
        }

        $plan = $subscription->subscriptionPlan;
        $used = $subscription->ai_credits_used_this_month;
        
        return $plan->ai_credits === null || $used < $plan->ai_credits;
    }

    /**
     * Batch process multiple prompts
     */
    protected function batchProcess(array $prompts, ?string $systemPrompt = null): array
    {
        $results = [];
        
        foreach ($prompts as $key => $prompt) {
            try {
                $results[$key] = $this->callAI($prompt, $systemPrompt);
            } catch (\Exception $e) {
                $results[$key] = null;
                Log::error('Batch AI processing failed', [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Stream AI response for real-time display
     */
    protected function streamAI(string $prompt, callable $callback, ?string $systemPrompt = null): void
    {
        // Note: OpenAI Laravel package doesn't support streaming yet
        // This is a placeholder for future implementation
        
        $response = $this->callAI($prompt, $systemPrompt, ['skip_cache' => true]);
        $callback($response);
    }

    /* -----------------------------------------------------------------
     | Public Wrapper Methods
     |------------------------------------------------------------------
     | Expose safe, clearly named public APIs while keeping the core
     | implementation methods (callAI, callAIForJSON, generateEmbedding)
     | protected. This prevents controllers/services from poking at
     | low-level internals and allows future centralization of cross-
     | cutting concerns (credits, caching, fallbacks) in one place.
     |------------------------------------------------------------------ */

    /**
     * Generate plain text completion from the AI model.
     *
     * @param string $prompt        User/content prompt
     * @param string|null $systemPrompt Optional system/role instructions
     * @param array $options        High-level options:
     *   - model: override model string
     *   - temperature: float override
     *   - timeout: int override seconds
     *   - cache_hours: int hours to cache
     *   - api_options: array raw options merged into OpenAI request
     */
    public function generateText(string $prompt, ?string $systemPrompt = null, array $options = []): string
    {
        // Normalize convenience keys (model, temperature)
        $apiOptions = $options['api_options'] ?? [];
        if (isset($options['model'])) {
            $apiOptions['model'] = $options['model'];
        }
        if (isset($options['temperature'])) {
            $apiOptions['temperature'] = $options['temperature'];
        }
        $options['api_options'] = $apiOptions;
        return $this->callAI($prompt, $systemPrompt, $options);
    }

    /**
     * Generate and parse JSON response from the AI model.
     * Returns decoded associative array. Throws on invalid JSON.
     */
    public function generateJSON(string $prompt, ?string $systemPrompt = null, array $options = []): array
    {
        $apiOptions = $options['api_options'] ?? [];
        if (isset($options['model'])) {
            $apiOptions['model'] = $options['model'];
        }
        if (isset($options['temperature'])) {
            $apiOptions['temperature'] = $options['temperature'];
        }
        $options['api_options'] = $apiOptions;
        return $this->callAIForJSON($prompt, $systemPrompt, $options);
    }

    /**
     * Generate embeddings for the provided text.
     */
    public function generateEmbeddings(string $text): array
    {
        return $this->generateEmbedding($text);
    }
}
