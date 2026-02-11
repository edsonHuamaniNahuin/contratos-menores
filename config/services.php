<?php

$seaceBaseUrl = rtrim((string) env('SEACE_BASE_URL', ''), '/');
$seaceOrigin = null;

if (!empty($seaceBaseUrl)) {
    $parsed = parse_url($seaceBaseUrl);

    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $seaceOrigin = sprintf('%s://%s%s', $parsed['scheme'], $parsed['host'], $port);
    }
}

$seaceFrontendOrigin = rtrim((string) env('SEACE_FRONTEND_ORIGIN', $seaceOrigin ?? ''), '/');

$authReferer = rtrim(
    (string) env('SEACE_AUTH_REFERER', $seaceFrontendOrigin ? $seaceFrontendOrigin . '/auth-proveedor' : ''),
    '/'
);

if (!empty($authReferer) && !str_ends_with($authReferer, '/')) {
    $authReferer .= '/';
}

$contratacionesReferer = rtrim(
    (string) env('SEACE_CONTRATACIONES_REFERER', $seaceFrontendOrigin ? $seaceFrontendOrigin . '/cotizacion/contrataciones' : ''),
    '/'
);

$publicBaseUrl = rtrim((string) env('SEACE_PUBLIC_BASE_URL', $seaceBaseUrl), '/');
$publicFrontendOrigin = rtrim((string) env('SEACE_PUBLIC_FRONTEND_ORIGIN', $seaceFrontendOrigin ?: ''), '/');

$defaultPublicRefererBase = $publicFrontendOrigin ?: $seaceFrontendOrigin;
$defaultPublicReferer = $defaultPublicRefererBase
    ? $defaultPublicRefererBase . '/busqueda/buscadorContrataciones'
    : '';

$publicReferer = rtrim(
    (string) env('SEACE_PUBLIC_REFERER', $defaultPublicReferer),
    '/'
);

$defaultArchivosRefererBase = $publicFrontendOrigin ?: $seaceFrontendOrigin;
$defaultArchivosReferer = $defaultArchivosRefererBase
    ? $defaultArchivosRefererBase . '/cotizacion/contrataciones'
    : '';

$publicArchivosBaseUrl = rtrim(
    (string) env('SEACE_PUBLIC_ARCHIVOS_BASE_URL', $publicBaseUrl ?: $seaceBaseUrl),
    '/'
);

$publicArchivosReferer = rtrim(
    (string) env('SEACE_PUBLIC_ARCHIVOS_REFERER', $defaultArchivosReferer),
    '/'
);

$telegramApiBase = rtrim((string) env('TELEGRAM_API_BASE', ''), '/');

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
        'base_url' => $seaceBaseUrl ?: null,
        'ruc_proveedor' => env('SEACE_RUC_PROVEEDOR'),
        'password' => env('SEACE_PASSWORD'),
        'token_cache_duration' => env('SEACE_TOKEN_CACHE_DURATION', 300),
        'page_size' => env('SEACE_PAGE_SIZE', 100),
        'process_cache_minutes' => env('SEACE_PROCESS_CACHE_MINUTES', 240),
        'min_delay_minutes' => env('SEACE_MIN_DELAY_MINUTES', 42),
        'max_delay_minutes' => env('SEACE_MAX_DELAY_MINUTES', 50),
        'frontend_origin' => $seaceFrontendOrigin ?: null,
        'auth_referer' => $authReferer ?: null,
        'contrataciones_referer' => $contratacionesReferer ?: null,
        'debug_logs' => env('SEACE_DEBUG_LOGS', false),
        'public_base_url' => $publicBaseUrl ?: ($seaceBaseUrl ?: null),
        'public_frontend_origin' => $publicFrontendOrigin ?: ($seaceFrontendOrigin ?: null),
        'public_referer' => $publicReferer ?: null,
        'public_archivos_base_url' => $publicArchivosBaseUrl ?: ($publicBaseUrl ?: $seaceBaseUrl ?: null),
        'public_archivos_referer' => $publicArchivosReferer ?: null,

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
        'api_base' => $telegramApiBase ?: null,
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'debug_logs' => env('TELEGRAM_DEBUG_LOGS', false),
    ],

    'analizador_tdr' => [
        'url' => env('ANALIZADOR_TDR_URL', 'http://127.0.0.1:8001'), // SIN slash final
        'enabled' => env('ANALIZADOR_TDR_ENABLED', false),
        'timeout' => env('ANALIZADOR_TDR_TIMEOUT', 60),
        'max_file_size' => env('ANALIZADOR_TDR_MAX_FILE_SIZE', 10485760), // 10MB
        'provider' => env('ANALIZADOR_TDR_PROVIDER', 'gemini'),
        'model' => env('ANALIZADOR_TDR_MODEL', 'gemini-2.5-flash'),
        'debug_logs' => env('ANALIZADOR_TDR_DEBUG_LOGS', false),
    ],
];
