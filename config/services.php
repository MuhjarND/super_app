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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'fonnte'),
        'api_url' => env('WHATSAPP_API_URL', env('FONNTE_API_URL', 'https://api.fonnte.com/send')),
        'api_key' => env('WHATSAPP_API_KEY', env('FONNTE_TOKEN')),
        'magic_link_ttl' => env('WHATSAPP_MAGIC_LINK_TTL', 10080),
        'work_start_hour' => env('WHATSAPP_WORK_START_HOUR', 0),
        'work_end_hour' => env('WHATSAPP_WORK_END_HOUR', 24),
        'work_days' => env('WHATSAPP_WORK_DAYS', '1,2,3,4,5,6,7'),
        'minimum_interval_seconds' => env('WHATSAPP_MIN_INTERVAL_SECONDS', 20),
        'max_per_hour' => env('WHATSAPP_MAX_PER_HOUR', 30),
        'max_per_phone_hour' => env('WHATSAPP_MAX_PER_PHONE_HOUR', 5),
        'max_per_day' => env('WHATSAPP_MAX_PER_DAY', 150),
        'deduplicate_minutes' => env('WHATSAPP_DEDUPLICATE_MINUTES', 10),
        'batch_size' => env('WHATSAPP_BATCH_SIZE', 3),
        'http_timeout' => env('WHATSAPP_HTTP_TIMEOUT', 15),
    ],

];
