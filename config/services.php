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

    'hrmis' => [
        // Base URL for legacy photo filenames (e.g. http://10.0.129.214/storage/photos)
        'photo_base_url' => env('PHOTO_BASE_URL', ''),
        // API endpoint that serves profile images by civil servant ID
        // e.g. https://mef-pd.net/hrmis/api/profile_image
        'photo_api_base' => env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'),
    ],

];
