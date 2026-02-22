<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'profile.complete' => \App\Http\Middleware\CheckProfileCompleteness::class,
            'subscription' => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'rate.plan' => \App\Http\Middleware\RateLimitByPlan::class,
            'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
            'employer' => \App\Http\Middleware\EnsureUserIsEmployer::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'idempotent' => \App\Http\Middleware\IdempotencyMiddleware::class,
            'agent.killswitch' => \App\Http\Middleware\AgentKillSwitchMiddleware::class,
        ]);

        // Apply correlation ID tracking to all requests (web and API)
        $middleware->append(\App\Http\Middleware\CorrelationIdMiddleware::class);

        // Apply activity tracking to web routes
        $middleware->web(append: [
            \App\Http\Middleware\TrackUserActivity::class,
        ]);
    })
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
        \App\Providers\HorizonServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // Integrate Sentry error tracking if DSN is configured
        if (app()->bound('sentry') && config('sentry.dsn')) {
            $exceptions->reportable(function (\Throwable $e): void {
                // Add correlation ID to Sentry scope
                $correlationId = \App\Http\Middleware\CorrelationIdMiddleware::getCorrelationId();

                \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($correlationId): void {
                    if ($correlationId) {
                        $scope->setTag('correlation_id', $correlationId);
                    }
                });

                \Sentry\captureException($e);
            });
        }
    })->create();
