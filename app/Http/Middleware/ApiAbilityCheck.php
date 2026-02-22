<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAbilityCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $apiToken = $request->input('api_token');

        if (!$apiToken) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'API token is required',
            ], 403);
        }

        if (!$apiToken->hasAbility($ability)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => "This API token does not have the '{$ability}' permission",
            ], 403);
        }

        return $next($request);
    }
}
