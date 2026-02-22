<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't apply in local development
        if (config('app.env') === 'local') {
            return $response;
        }

        $headers = config('security.headers');

        // X-Frame-Options
        $response->headers->set('X-Frame-Options', $headers['x_frame_options']);

        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', $headers['x_content_type_options']);

        // X-XSS-Protection
        $response->headers->set('X-XSS-Protection', $headers['x_xss_protection']);

        // Referrer-Policy
        $response->headers->set('Referrer-Policy', $headers['referrer_policy']);

        // Permissions-Policy
        $response->headers->set('Permissions-Policy', $headers['permissions_policy']);

        // Strict-Transport-Security (HSTS) - HTTPS only
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
