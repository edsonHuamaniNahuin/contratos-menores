<?php

return [
    'storage_disk' => env('TDR_STORAGE_DISK', 'local'),
    'storage_root' => trim(env('TDR_STORAGE_ROOT', 'tdr')), // Carpeta bajo storage/app
    'analysis_cache_minutes' => env('TDR_ANALYSIS_CACHE_MINUTES', 60 * 24 * 3), // 3 días por defecto
    'default_provider' => env('ANALIZADOR_TDR_PROVIDER', 'gemini'),
    'default_model' => env('ANALIZADOR_TDR_MODEL', 'gemini-2.5-flash'),
    'debug_logs' => env('TDR_DEBUG_LOGS', false),

    // Tamaño (bytes) a partir del cual el análisis usa Job async (queue:work).
    // Local sin worker: 500 KB (pequeños van síncronos, no necesitas el worker).
    // QA / Producción: 0 → SIEMPRE async (el vigilante-queue siempre está activo).
    'async_min_size_bytes' => env('TDR_ASYNC_MIN_SIZE_BYTES', 500_000),
];
