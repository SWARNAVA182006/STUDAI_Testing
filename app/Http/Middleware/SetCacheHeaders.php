<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'html'): Response
    {
        $response = $next($request);

        // Don't cache in development
        if (config('app.env') === 'local') {
            return $response;
        }

        // Get cache control header for type
        $cacheControl = config("cdn.cache_control.{$type}", 'no-cache');

        $response->headers->set('Cache-Control', $cacheControl);

        // Add ETag for cache validation
        if ($type !== 'html') {
            $etag = md5($response->getContent());
            $response->headers->set('ETag', $etag);

            // Check if client has valid cache
            if ($request->header('If-None-Match') === $etag) {
                return response('', 304);
            }
        }

        // Add Vary header for compression
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
