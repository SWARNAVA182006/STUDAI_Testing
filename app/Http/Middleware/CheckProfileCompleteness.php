<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfileCompleteness
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  int  $minCompleteness  Minimum profile completeness percentage (0-100)
     */
    public function handle(Request $request, Closure $next, int $minCompleteness = 50): Response
    {
        $user = $request->user();
        
        // Skip for API requests or if user is not a job seeker
        if (!$user || !$user->isJobSeeker() || $request->expectsJson()) {
            return $next($request);
        }
        
        $profile = $user->profile;
        
        // If no profile exists or profile completeness is below threshold
        if (!$profile || $profile->profile_completeness < $minCompleteness) {
            return redirect()->route('profile.complete')
                ->with('warning', 'Please complete your profile to access this feature.');
        }
        
        return $next($request);
    }
}

