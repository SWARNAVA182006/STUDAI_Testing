<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Structured JSON Logging Channel
        |--------------------------------------------------------------------------
        |
        | This channel outputs logs in JSON format for easy parsing by log
        | aggregation systems (ELK, CloudWatch, Datadog, etc.).
        |
        */

        'json' => [
            'driver' => 'daily',
            'path' => storage_path('logs/json/laravel.json'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Production Stack
        |--------------------------------------------------------------------------
        |
        | Combines daily logs with JSON structured logs for production environments.
        |
        */

        'production' => [
            'driver' => 'stack',
            'channels' => ['daily', 'json'],
            'ignore_exceptions' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | AI Operations Logging Channel
        |--------------------------------------------------------------------------
        |
        | Dedicated channel for AI service operations, circuit breaker events,
        | and AI usage tracking in JSON format.
        |
        */

        'ai' => [
            'driver' => 'daily',
            'path' => storage_path('logs/ai/operations.json'),
            'level' => env('AI_LOG_LEVEL', 'info'),
            'days' => env('AI_LOG_DAYS', 30),
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => false,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Agent Activity Logging Channel
        |--------------------------------------------------------------------------
        |
        | Dedicated channel for autonomous agent operations, applications,
        | and decision tracking in JSON format.
        |
        */

        'agent' => [
            'driver' => 'daily',
            'path' => storage_path('logs/agent/activity.json'),
            'level' => env('AGENT_LOG_LEVEL', 'info'),
            'days' => env('AGENT_LOG_DAYS', 30),
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => false,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment Logging Channel
        |--------------------------------------------------------------------------
        |
        | Dedicated channel for payment transactions and webhook events
        | in JSON format for audit compliance.
        |
        */

        'payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/payments/transactions.json'),
            'level' => 'info',
            'days' => env('PAYMENT_LOG_DAYS', 90),
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Security Events Logging Channel
        |--------------------------------------------------------------------------
        |
        | Dedicated channel for security-related events (auth attempts,
        | suspicious activity, etc.) in JSON format.
        |
        */

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security/events.json'),
            'level' => 'info',
            'days' => env('SECURITY_LOG_DAYS', 90),
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
        ],

    ],

];
