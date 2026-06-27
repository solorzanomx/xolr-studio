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

    'did' => [
        'api_key'        => env('DID_API_KEY'),
        'webhook_secret' => env('DID_WEBHOOK_SECRET'),
        'mock_mode'      => (bool) env('DID_MOCK_MODE', false),
    ],

    'elevenlabs' => [
        'api_key'          => env('ELEVENLABS_API_KEY'),
        'default_voice_id' => env('ELEVENLABS_DEFAULT_VOICE_ID', 'EXAVITQu4vr4xnSDxMaL'),
        'mock_mode'        => (bool) env('ELEVENLABS_MOCK_MODE', false),
    ],

    'suno' => [
        'api_key'   => env('SUNO_API_KEY'),
        'mock_mode' => (bool) env('SUNO_MOCK_MODE', false),
    ],

    'openai' => [
        'key'       => env('OPENAI_API_KEY'),
        'mock_mode' => (bool) env('OPENAI_MOCK_MODE', false),
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
        // Nombres de modelo tal como aparecen en el endpoint ComfyUI
        'models' => [
            'schnell' => env('RUNPOD_MODEL_SCHNELL', 'flux1-schnell-fp8.safetensors'),
            'dev'     => env('RUNPOD_MODEL_DEV',     'flux1-dev-fp8.safetensors'),
            'clip_l'  => env('RUNPOD_MODEL_CLIP_L',  'clip_l.safetensors'),
            'clip_t5' => env('RUNPOD_MODEL_CLIP_T5', 't5xxl_fp8_e4m3fn.safetensors'),
            'vae'     => env('RUNPOD_MODEL_VAE',      'ae.safetensors'),
        ],
    ],

    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'account_id'   => env('INSTAGRAM_ACCOUNT_ID'),
        'mock_mode'    => (bool) env('INSTAGRAM_MOCK_MODE', true),
    ],

    'youtube' => [
        'access_token'  => env('YOUTUBE_ACCESS_TOKEN'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
        'client_id'     => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'mock_mode'     => (bool) env('YOUTUBE_MOCK_MODE', true),
    ],

    'notion' => [
        'token'     => env('NOTION_TOKEN'),
        'mock_mode' => (bool) env('NOTION_MOCK_MODE', true),
    ],

];
