<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLoginAttempts
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if IP is blocked
        if ($this->isIpBlocked($request->ip())) {
            return response()->json([
                'error' => 'Too many failed login attempts. Your IP has been temporarily blocked.',
                'retry_after' => $this->getBlockExpiration($request->ip()),
            ], 429);
        }

        // Check rate limiting for this IP
        if ($this->exceedsRateLimit($request->ip())) {
            return response()->json([
                'error' => 'Too many login attempts. Please try again later.',
            ], 429);
        }

        return $next($request);
    }

    /**
     * Check if IP is blocked.
     */
    private function isIpBlocked(string $ip): bool
    {
        $block = DB::table('ip_blocks')
            ->where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $block !== null;
    }

    /**
     * Get block expiration time.
     */
    private function getBlockExpiration(string $ip): ?string
    {
        $block = DB::table('ip_blocks')
            ->where('ip_address', $ip)
            ->first();

        return $block?->expires_at;
    }

    /**
     * Check if IP exceeds rate limit.
     */
    private function exceedsRateLimit(string $ip): bool
    {
        $attempts = DB::table('login_attempts')
            ->where('ip_address', $ip)
            ->where('attempted_at', '>', now()->subMinutes(5))
            ->count();

        return $attempts >= 10;
    }

    /**
     * Record login attempt.
     */
    public static function recordAttempt(string $email, string $ip, bool $successful): void
    {
        DB::table('login_attempts')->insert([
            'email' => $email,
            'ip_address' => $ip,
            'successful' => $successful,
            'user_agent' => request()->userAgent(),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Block IP if too many failed attempts
        if (!$successful) {
            static::checkAndBlockIp($ip, $email);
        }
    }

    /**
     * Check and block IP if needed.
     */
    private static function checkAndBlockIp(string $ip, string $email): void
    {
        // Count failed attempts in last 15 minutes
        $failedAttempts = DB::table('login_attempts')
            ->where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>', now()->subMinutes(15))
            ->count();

        // Block for 1 hour after 10 failed attempts
        if ($failedAttempts >= 10) {
            DB::table('ip_blocks')->updateOrInsert(
                ['ip_address' => $ip],
                [
                    'reason' => "Too many failed login attempts for {$email}",
                    'failed_attempts' => $failedAttempts,
                    'blocked_at' => now(),
                    'expires_at' => now()->addHour(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
