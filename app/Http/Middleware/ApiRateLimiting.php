<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiToken = $request->input('api_token');
        
        if (!$apiToken) {
            return $next($request);
        }

        $key = 'rate_limit:' . $apiToken->id;
        $limit = $apiToken->rate_limit; // Requests per minute
        $window = 60; // seconds

        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            $retryAfter = Cache::get($key . ':reset', $window);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => "You have exceeded the rate limit of {$limit} requests per minute",
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        // Increment counter
        if ($current === 0) {
            Cache::put($key, 1, $window);
            Cache::put($key . ':reset', $window, $window);
        } else {
            Cache::increment($key);
        }

        $remaining = $limit - ($current + 1);
        $resetTime = Cache::get($key . ':reset', $window);

        return $next($request)
            ->header('X-RateLimit-Limit', $limit)
            ->header('X-RateLimit-Remaining', max(0, $remaining))
            ->header('X-RateLimit-Reset', now()->addSeconds($resetTime)->timestamp);
    }
}
