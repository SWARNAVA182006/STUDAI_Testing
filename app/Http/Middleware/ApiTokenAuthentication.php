<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API token is required. Include it as: Authorization: Bearer {token}',
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $apiToken = ApiToken::where('token', $hashedToken)->first();

        if (!$apiToken) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token',
            ], 401);
        }

        if (!$apiToken->isValid()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API token is expired or inactive',
            ], 401);
        }

        // Attach token and company to request
        $request->merge([
            'api_token' => $apiToken,
            'api_company' => $apiToken->company,
        ]);

        // Mark token as used
        $apiToken->markAsUsed();

        return $next($request);
    }
}
