<?php

/**
 * Sentry Laravel SDK Configuration
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The Data Source Name (DSN) for your Sentry project. This tells the SDK
    | where to send events. You can find this in your Sentry project settings
    | under Client Keys (DSN).
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Release Tracking
    |--------------------------------------------------------------------------
    |
    | Track which release an error occurred in. This is useful for correlating
    | errors with deployments. Typically set to your git commit SHA.
    |
    */

    'release' => env('SENTRY_RELEASE', env('APP_VERSION')),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The environment name sent with events. This helps filter events by
    | environment in the Sentry dashboard.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Enable performance monitoring to track application performance.
    | traces_sample_rate: 0.0 = no traces, 1.0 = all traces
    | Set lower in production to control costs.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', env('APP_ENV') === 'production' ? 0.1 : 1.0),

    /*
    |--------------------------------------------------------------------------
    | Profiling
    |--------------------------------------------------------------------------
    |
    | Enable profiling to get detailed performance data for transactions.
    | Requires traces to be enabled.
    |
    */

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', env('APP_ENV') === 'production' ? 0.1 : 1.0),

    /*
    |--------------------------------------------------------------------------
    | Error Sample Rate
    |--------------------------------------------------------------------------
    |
    | Sample rate for error events. 1.0 means all errors are sent.
    | Lower this if you're experiencing rate limiting.
    |
    */

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | Whether to send personally identifiable information (PII) by default.
    | When enabled, this will include user data like email addresses.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', true),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure which breadcrumbs to capture. Breadcrumbs are a trail of
    | events that led up to an error.
    |
    */

    'breadcrumbs' => [
        // Capture Laravel logs as breadcrumbs
        'logs' => true,

        // Capture database queries as breadcrumbs
        'sql_queries' => true,

        // Capture bindings in SQL queries
        'sql_bindings' => true,

        // Capture queue job information
        'queue_info' => true,

        // Capture HTTP client requests
        'http_client_requests' => true,

        // Capture command information
        'command_info' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is invoked before sending the event to Sentry.
    | You can modify the event or return null to discard it.
    |
    */

    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Add correlation ID to all events
        $correlationId = \App\Http\Middleware\CorrelationIdMiddleware::getCorrelationId();

        if ($correlationId) {
            $event->setTag('correlation_id', $correlationId);
        }

        // Add user context if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->name,
                ]);
            });
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | List of exception classes that should not be reported to Sentry.
    | These are typically expected exceptions that don't need tracking.
    |
    */

    'ignore_exceptions' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Transactions
    |--------------------------------------------------------------------------
    |
    | List of transaction names that should not be traced.
    | Useful for health check endpoints or other noisy routes.
    |
    */

    'ignore_transactions' => [
        '/health',
        '/ready',
        '/up',
        '/horizon/api/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    |
    | Default tags to add to all events. Tags are indexed and searchable.
    |
    */

    'tags' => [
        'app_name' => env('APP_NAME', 'StudAI Career'),
        'server' => gethostname(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    |
    | Default context to add to all events. Context provides additional
    | information but is not indexed.
    |
    */

    'context' => [
        // Add any default context here
    ],

];
