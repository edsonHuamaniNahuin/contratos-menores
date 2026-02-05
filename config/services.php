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

    'seace' => [
        'base_url' => env('SEACE_BASE_URL', 'https://prod6.seace.gob.pe/v1/s8uit-services'),
        'ruc_proveedor' => env('SEACE_RUC_PROVEEDOR'),
        'password' => env('SEACE_PASSWORD'),
        'token_cache_duration' => env('SEACE_TOKEN_CACHE_DURATION', 3600),
        'page_size' => env('SEACE_PAGE_SIZE', 100),
        'process_cache_minutes' => env('SEACE_PROCESS_CACHE_MINUTES', 240),
        'min_delay_minutes' => env('SEACE_MIN_DELAY_MINUTES', 42),
        'max_delay_minutes' => env('SEACE_MAX_DELAY_MINUTES', 50),

        'endpoints' => [
            'login' => env('SEACE_ENDPOINT_LOGIN', '/seguridadproveedor/seguridad/validausuariornp'),
            'refresh' => env('SEACE_ENDPOINT_REFRESH', '/seguridadproveedor/seguridad/tokens/refresh'),
            'buscador' => env('SEACE_ENDPOINT_BUSCADOR', '/contratacion/contrataciones/buscador'),
            'objeto_contratacion' => env('SEACE_ENDPOINT_OBJETO_CONTRATACION', '/maestra/maestras/listar-objeto-contratacion'),
            'estado_contratacion' => env('SEACE_ENDPOINT_ESTADO_CONTRATACION', '/maestra/maestras/listar-estado-contratacion'),
            'departamentos' => env('SEACE_ENDPOINT_DEPARTAMENTOS', '/maestra/maestras/listar-departamento'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'analizador_tdr' => [
        'url' => env('ANALIZADOR_TDR_URL', 'http://127.0.0.1:8001'), // SIN slash final
        'enabled' => env('ANALIZADOR_TDR_ENABLED', false),
        'timeout' => env('ANALIZADOR_TDR_TIMEOUT', 60),
        'max_file_size' => env('ANALIZADOR_TDR_MAX_FILE_SIZE', 10485760), // 10MB
        'provider' => env('ANALIZADOR_TDR_PROVIDER', 'gemini'),
        'model' => env('ANALIZADOR_TDR_MODEL', 'gemini-2.5-flash'),
    ],
];
