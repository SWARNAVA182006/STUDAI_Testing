<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Define rate limits based on subscription plan
        $limits = [
            'free' => 60,        // 60 requests per minute
            'professional' => 120, // 120 requests per minute
            'premium' => 300,      // 300 requests per minute
            'enterprise' => 1000,  // 1000 requests per minute
        ];
        
        $subscription = $user->subscription;
        $planSlug = $subscription ? $subscription->subscriptionPlan->slug : 'free';
        $maxAttempts = $limits[$planSlug] ?? $limits['free'];
        
        $key = 'rate-limit:' . $user->id;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many requests',
                    'retry_after' => $seconds,
                    'upgrade_url' => route('pricing')
                ], 429);
            }
            
            return back()->with('error', "Rate limit exceeded. Please try again in {$seconds} seconds.");
        }
        
        RateLimiter::hit($key, 60); // 60 second decay
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - RateLimiter::attempts($key)));
        
        return $response;
    }
}

