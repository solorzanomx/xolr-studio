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

    'anthropic' => [
        'key'        => env('ANTHROPIC_API_KEY'),
        'model'      => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 8192),
    ],

    'runpod' => [
        'api_key'        => env('RUNPOD_API_KEY'),
        'webhook_secret' => env('RUNPOD_WEBHOOK_SECRET'),
        'mock_mode'      => (bool) env('RUNPOD_MOCK_MODE', false),
        'endpoints'      => [
            'image'   => env('RUNPOD_ENDPOINT_IMAGE'),
            'video'   => env('RUNPOD_ENDPOINT_VIDEO'),
            'upscale' => env('RUNPOD_ENDPOINT_UPSCALE'),
        ],
    ],

];
