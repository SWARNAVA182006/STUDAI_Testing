<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */

    // Content Security Policy
    'csp_enabled' => env('CSP_ENABLED', true),
    'csp_report_uri' => env('CSP_REPORT_URI', null),

    // Two-Factor Authentication
    'two_factor_enabled' => env('TWO_FACTOR_ENABLED', true),
    'two_factor_issuer' => env('APP_NAME', 'StudAI Career'),

    // Password Security
    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special' => true,
        'check_breach' => env('PASSWORD_CHECK_BREACH', true),
        'prevent_reuse' => true,
        'reuse_limit' => 5, // Remember last 5 passwords
        'expire_days' => 90, // Require password change every 90 days (0 = disabled)
    ],

    // Audit Logging
    'audit' => [
        'enabled' => env('AUDIT_LOG_ENABLED', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'retention_days_critical' => 365, // Keep critical events for 1 year
    ],

    // Rate Limiting
    'rate_limit' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        
        // Login attempts
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
            'block_after' => 10,
            'block_duration_minutes' => 60,
        ],
        
        // Registration
        'register' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        
        // Password reset
        'password_reset' => [
            'max_attempts' => 2,
            'decay_minutes' => 60,
        ],
        
        // API requests
        'api' => [
            'default' => 1000, // Per minute
            'free' => 100,
            'pro' => 1000,
            'enterprise' => 10000,
        ],
    ],

    // IP Blocking
    'ip_block' => [
        'enabled' => true,
        'auto_block_after' => 10, // Failed login attempts
        'block_duration_hours' => 1,
        'permanent_block_after' => 50, // Attempts before permanent block
    ],

    // Session Security
    'session' => [
        'secure' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'strict',
        'lifetime' => 120, // Minutes
        'absolute_timeout' => 480, // 8 hours absolute timeout
        'idle_timeout' => 30, // 30 minutes idle timeout
    ],

    // Headers
    'headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'no-referrer-when-downgrade',
        'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    // File Upload Security
    'uploads' => [
        'max_size' => 20480, // KB (20 MB)
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'txt', 'rtf', // Documents
            'jpg', 'jpeg', 'png', 'gif', 'webp', // Images
        ],
        'scan_virus' => env('UPLOAD_SCAN_VIRUS', false),
        'scan_timeout' => 30, // Seconds
    ],

    // Email Security
    'email' => [
        'verify_mx' => true,
        'disposable_domains_check' => true,
        'rate_limit_per_day' => 100,
    ],

];
