<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Track last activity in cache to avoid DB writes on every request
            $cacheKey = "user-last-activity:{$user->id}";
            $lastActivity = Cache::get($cacheKey);
            
            // Only update DB if more than 5 minutes since last update
            if (!$lastActivity || now()->diffInMinutes($lastActivity) > 5) {
                $user->update([
                    'last_login_at' => now(),
                ]);
                
                Cache::put($cacheKey, now(), 600); // Cache for 10 minutes
            }
            
            // Track session information
            session([
                'last_activity' => now()->toDateTimeString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);
        }
        
        return $next($request);
    }
}

