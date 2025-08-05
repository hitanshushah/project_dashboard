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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

            'minio' => [
            'endpoint' => env('MINIO_ENDPOINT', 'http://host.docker.internal:4810'),
            'public_url' => env('MINIO_PUBLIC_URL', 'http://localhost:4811'),
            'bucket' => env('MINIO_BUCKET', 'projectsdashboard'),
            'region' => env('MINIO_REGION', 'us-east-1'),
            'access_key' => env('MINIO_ACCESS_KEY', 'admin'),
            'secret_key' => env('MINIO_SECRET_KEY', 'password'),
            'use_ssl' => env('MINIO_USE_SSL', false),
        ],

];
