<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
        'webhook_token' => env('FONNTE_WEBHOOK_TOKEN'),
        'allow_insecure_webhook' => env('FONNTE_ALLOW_INSECURE_WEBHOOK', false),
        'session_timeout_minutes' => (int) env('FONNTE_SESSION_TIMEOUT_MINUTES', 30),
        'rate_limit_per_minute' => (int) env('FONNTE_WEBHOOK_RATE_PER_MINUTE', 40),
        'global_rate_limit_per_minute' => (int) env('FONNTE_WEBHOOK_GLOBAL_RATE_PER_MINUTE', 1200),
        'webhook_lock_seconds' => (int) env('FONNTE_WEBHOOK_LOCK_SECONDS', 30),
        'webhook_wait_seconds' => (int) env('FONNTE_WEBHOOK_WAIT_SECONDS', 20),
    ],

];
