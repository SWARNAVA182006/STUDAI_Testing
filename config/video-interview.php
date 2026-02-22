<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Interview Storage
    |--------------------------------------------------------------------------
    |
    | Configure where video recordings are stored. Options: 'local', 's3', 
    | 'azure' (Azure Blob Storage).
    |
    */
    'storage_disk' => env('VIDEO_INTERVIEW_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Transcription Provider
    |--------------------------------------------------------------------------
    |
    | The service to use for transcribing video/audio recordings.
    | Options: 'azure' (Azure Speech Services), 'openai' (Whisper API)
    |
    */
    'transcription_provider' => env('VIDEO_INTERVIEW_TRANSCRIPTION_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Video Settings
    |--------------------------------------------------------------------------
    */
    'max_recording_duration_seconds' => env('VIDEO_INTERVIEW_MAX_DURATION', 300), // 5 minutes
    'max_file_size_mb' => env('VIDEO_INTERVIEW_MAX_FILE_SIZE', 500),
    'default_prep_time_seconds' => 30,
    'default_response_time_seconds' => 180,

    /*
    |--------------------------------------------------------------------------
    | WebRTC / TURN Server Configuration
    |--------------------------------------------------------------------------
    |
    | For live video interviews, you need STUN/TURN servers for NAT traversal.
    |
    */
    'turn_server' => env('VIDEO_INTERVIEW_TURN_SERVER'),
    'turn_username' => env('VIDEO_INTERVIEW_TURN_USERNAME'),
    'turn_credential' => env('VIDEO_INTERVIEW_TURN_CREDENTIAL'),

    /*
    |--------------------------------------------------------------------------
    | Analysis Settings
    |--------------------------------------------------------------------------
    */
    'analysis' => [
        'enabled' => env('VIDEO_INTERVIEW_ANALYSIS_ENABLED', true),
        'auto_analyze' => true,
        'analyze_delay_seconds' => 60, // Wait after upload before analyzing
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Defaults
    |--------------------------------------------------------------------------
    */
    'session_defaults' => [
        'async' => [
            'allow_retakes' => true,
            'max_retakes' => 2,
            'expires_days' => 7,
        ],
        'mock' => [
            'allow_retakes' => true,
            'max_retakes' => 3,
            'expires_days' => 30,
        ],
        'live' => [
            'recording_enabled' => true,
            'screen_share_enabled' => true,
            'chat_enabled' => true,
            'max_participants' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'send_invitation' => true,
        'send_reminder' => true,
        'reminder_hours_before' => [24, 1], // Send reminders 24h and 1h before deadline
        'send_completion_email' => true,
    ],
];
