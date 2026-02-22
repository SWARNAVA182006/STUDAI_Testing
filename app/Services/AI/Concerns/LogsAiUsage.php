<?php

namespace App\Services\AI\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait LogsAiUsage
{
    /**
     * Persist AI usage metrics into ai_usage_logs table when available.
     */
    protected function logAiUsage(?int $userId, string $feature, string $model, $usage = null, array $metadata = []): void
    {
        try {
            $promptTokens = $this->extractUsageValue($usage, ['prompt_tokens', 'promptTokens']);
            $completionTokens = $this->extractUsageValue($usage, ['completion_tokens', 'completionTokens']);
            $totalTokens = $this->extractUsageValue($usage, ['total_tokens', 'totalTokens']);

            DB::table('ai_usage_logs')->insert([
                'user_id' => $userId,
                'feature' => $feature,
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost' => $metadata['cost'] ?? null,
                'metadata' => json_encode(Arr::except($metadata, ['cost'])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::debug('AI usage logging skipped', [
                'feature' => $feature,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Safely read token counts from OpenAI usage object / array
     */
    protected function extractUsageValue($usage, array $keys): int
    {
        if (is_null($usage)) {
            return 0;
        }

        $usageArray = is_object($usage) ? get_object_vars($usage) : (array) $usage;

        foreach ($keys as $key) {
            if (array_key_exists($key, $usageArray)) {
                return (int) $usageArray[$key];
            }
        }

        return 0;
    }
}
