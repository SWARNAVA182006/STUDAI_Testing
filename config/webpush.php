<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for web push notifications using VAPID protocol
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@studai.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Settings
    |--------------------------------------------------------------------------
    */

    'gcm_key' => env('GCM_KEY'), // Optional: For older browsers
    'gcm_sender_id' => env('GCM_SENDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Notification Options
    |--------------------------------------------------------------------------
    */

    'notification_options' => [
        'TTL' => 2419200, // 4 weeks in seconds
        'urgency' => 'normal', // very-low, low, normal, high
        'topic' => null,
        'batchSize' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'stale_subscriptions_days' => 90,
        'sent_notifications_retention_days' => 30,
    ],
];
