<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DatabaseQueryLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only log in local/staging environments
        if (!app()->environment(['local', 'staging'])) {
            return $next($request);
        }

        // Enable query logging
        DB::enableQueryLog();

        $response = $next($request);

        // Get executed queries
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Log if query count is high (potential N+1 problem)
        if ($queryCount > 50) {
            Log::warning('High query count detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'queries' => array_map(function ($query) {
                    return [
                        'query' => $query['query'],
                        'time' => $query['time'] . 'ms',
                    ];
                }, $queries),
            ]);
        }

        // Add header for debugging
        $response->headers->set('X-Database-Queries', $queryCount);
        $totalTime = array_sum(array_column($queries, 'time'));
        $response->headers->set('X-Database-Time', round($totalTime, 2) . 'ms');

        return $response;
    }
}
