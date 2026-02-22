<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    */
    'default_gateway' => env('PAYMENT_GATEWAY', 'razorpay'),

    /*
    |--------------------------------------------------------------------------
    | Razorpay Configuration
    |--------------------------------------------------------------------------
    */
    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'currency' => env('RAZORPAY_CURRENCY', 'INR'),
        'logo' => env('APP_URL') . '/logo.png',
        'theme_color' => '#ec4899', // StudAI Pink
    ],

    /*
    |--------------------------------------------------------------------------
    | PayU Configuration
    |--------------------------------------------------------------------------
    */
    'payu' => [
        'merchant_key' => env('PAYU_MERCHANT_KEY'),
        'merchant_salt' => env('PAYU_MERCHANT_SALT'),
        'surl' => env('PAYU_SURL', env('APP_URL') . '/payment/success'),
        'furl' => env('PAYU_FURL', env('APP_URL') . '/payment/failure'),
        'mode' => env('PAYU_MODE', 'test'), // test or live
        'currency' => env('PAYU_CURRENCY', 'INR'),
        'payment_url' => env('PAYU_MODE', 'test') === 'test' 
            ? 'https://test.payu.in/_payment' 
            : 'https://secure.payu.in/_payment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration (Global Payments)
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'USD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Methods
    |--------------------------------------------------------------------------
    */
    'supported_methods' => [
        'razorpay' => ['card', 'netbanking', 'upi', 'wallet', 'emi'],
        'payu' => ['card', 'netbanking', 'upi', 'wallet', 'emi'],
        'stripe' => ['card', 'ach', 'sepa', 'apple_pay', 'google_pay'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Settings
    |--------------------------------------------------------------------------
    */
    'transaction_timeout' => 900, // 15 minutes in seconds
    'max_retry_attempts' => 3,
    'auto_refund_on_failure' => true,
];
