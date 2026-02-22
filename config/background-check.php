<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Background Check Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default background check provider that will be
    | used when no specific provider is selected. Supported: "checkr", "sterling", "goodhire"
    |
    */

    'default' => env('BACKGROUND_CHECK_PROVIDER', 'checkr'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection settings for each background check
    | provider that your application supports.
    |
    */

    'providers' => [

        'checkr' => [
            'api_key' => env('CHECKR_API_KEY'),
            'api_secret' => env('CHECKR_API_SECRET'),
            'webhook_secret' => env('CHECKR_WEBHOOK_SECRET'),
            'sandbox' => env('CHECKR_SANDBOX', true),
            'base_url' => env('CHECKR_SANDBOX', true) 
                ? 'https://api.checkr-staging.com' 
                : 'https://api.checkr.com',
        ],

        'sterling' => [
            'api_key' => env('STERLING_API_KEY'),
            'api_secret' => env('STERLING_API_SECRET'),
            'webhook_secret' => env('STERLING_WEBHOOK_SECRET'),
            'sandbox' => env('STERLING_SANDBOX', true),
            'base_url' => env('STERLING_SANDBOX', true)
                ? 'https://api-sandbox.sterlingcheck.com'
                : 'https://api.sterlingcheck.com',
        ],

        'goodhire' => [
            'api_key' => env('GOODHIRE_API_KEY'),
            'webhook_secret' => env('GOODHIRE_WEBHOOK_SECRET'),
            'sandbox' => env('GOODHIRE_SANDBOX', true),
            'base_url' => env('GOODHIRE_SANDBOX', true)
                ? 'https://api.sandbox.goodhire.com'
                : 'https://api.goodhire.com',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Consent Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for candidate consent collection process.
    |
    */

    'consent' => [
        // Number of days before a consent link expires
        'expiry_days' => env('BACKGROUND_CHECK_CONSENT_EXPIRY_DAYS', 7),
        
        // Maximum number of consent reminders to send
        'max_reminders' => 3,
        
        // Days between reminders
        'reminder_interval_days' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Adverse Action Settings (FCRA Compliance)
    |--------------------------------------------------------------------------
    |
    | Configuration for the adverse action process as required by FCRA.
    |
    */

    'adverse_action' => [
        // Minimum waiting period before final adverse action (FCRA requires 5 days)
        'waiting_period_days' => env('ADVERSE_ACTION_WAITING_DAYS', 5),
        
        // Maximum waiting period allowed
        'max_waiting_period_days' => 14,
        
        // Days to allow candidate dispute
        'dispute_window_days' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for storing background check reports.
    |
    */

    'storage' => [
        // Disk to use for storing reports
        'disk' => env('BACKGROUND_CHECK_STORAGE_DISK', 'local'),
        
        // Directory path for reports
        'path' => 'background-checks/reports',
        
        // Whether to encrypt stored reports
        'encrypt' => true,
        
        // Retention period in days (regulatory requirement)
        'retention_days' => env('BACKGROUND_CHECK_RETENTION_DAYS', 2555), // ~7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Check Types
    |--------------------------------------------------------------------------
    |
    | Available background check types with their configurations.
    |
    */

    'check_types' => [
        'criminal' => [
            'name' => 'Criminal Background',
            'description' => 'County, state, and federal criminal records search',
            'typical_days' => 3,
        ],
        'employment' => [
            'name' => 'Employment Verification',
            'description' => 'Verify previous employment history and dates',
            'typical_days' => 5,
        ],
        'education' => [
            'name' => 'Education Verification',
            'description' => 'Verify degrees, certifications, and attendance',
            'typical_days' => 5,
        ],
        'mvr' => [
            'name' => 'Motor Vehicle Report',
            'description' => 'Driving record and license verification',
            'typical_days' => 1,
        ],
        'credit' => [
            'name' => 'Credit Check',
            'description' => 'Credit history and financial responsibility check',
            'typical_days' => 1,
        ],
        'drug_test' => [
            'name' => 'Drug Test',
            'description' => 'Pre-employment drug screening',
            'typical_days' => 3,
        ],
        'professional_license' => [
            'name' => 'Professional License Verification',
            'description' => 'Verify professional licenses and certifications',
            'typical_days' => 3,
        ],
        'reference' => [
            'name' => 'Reference Check',
            'description' => 'Contact and verify professional references',
            'typical_days' => 5,
        ],
        'identity' => [
            'name' => 'Identity Verification',
            'description' => 'Verify identity using SSN and government ID',
            'typical_days' => 1,
        ],
        'ssn_trace' => [
            'name' => 'SSN Trace',
            'description' => 'Social Security Number verification and address history',
            'typical_days' => 1,
        ],
        'sex_offender' => [
            'name' => 'Sex Offender Registry',
            'description' => 'National sex offender registry search',
            'typical_days' => 1,
        ],
        'global_watchlist' => [
            'name' => 'Global Watchlist',
            'description' => 'International sanctions and watchlist search',
            'typical_days' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for background check notifications.
    |
    */

    'notifications' => [
        // Notify employer when check is complete
        'employer_on_complete' => true,
        
        // Notify employer when issues found
        'employer_on_issues' => true,
        
        // Notify candidate when check begins
        'candidate_on_start' => true,
        
        // Notify candidate when check completes
        'candidate_on_complete' => true,
        
        // Notify on adverse action steps
        'notify_on_adverse_action' => true,
    ],

];
