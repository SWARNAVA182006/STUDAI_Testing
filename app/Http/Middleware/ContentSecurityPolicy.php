<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't apply CSP in local development
        if (config('app.env') === 'local') {
            return $response;
        }

        $csp = $this->generateCspHeader();
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Also set report-only header for monitoring
        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        return $response;
    }

    /**
     * Generate CSP header value.
     */
    private function generateCspHeader(): string
    {
        $appUrl = config('app.url');
        $cdnUrl = config('cdn.url', '');

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com {$cdnUrl}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com {$cdnUrl}",
            "font-src 'self' https://fonts.gstatic.com data: {$cdnUrl}",
            "img-src 'self' data: https: {$cdnUrl}",
            "connect-src 'self' https://api.openai.com https://api.razorpay.com {$cdnUrl}",
            "frame-src 'self' https://api.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://secure.payu.in",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ];

        // Add report URI if configured
        if ($reportUri = config('security.csp_report_uri')) {
            $directives[] = "report-uri {$reportUri}";
        }

        return implode('; ', $directives);
    }
}
