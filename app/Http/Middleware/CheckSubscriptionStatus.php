<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $requiredFeature  Specific feature to check for
     */
    public function handle(Request $request, Closure $next, ?string $requiredFeature = null): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Active subscription required',
                    'upgrade_url' => route('pricing')
                ], 403);
            }
            
            return redirect()->route('pricing')
                ->with('error', 'This feature requires an active subscription.');
        }
        
        // Check for specific feature if provided
        if ($requiredFeature && !$user->hasFeature($requiredFeature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Your current plan doesn't include this feature",
                    'upgrade_url' => route('pricing')
                ], 403);
            }
            
            return redirect()->route('pricing')
                ->with('error', "Upgrade required to access {$requiredFeature}.");
        }
        
        return $next($request);
    }
}

