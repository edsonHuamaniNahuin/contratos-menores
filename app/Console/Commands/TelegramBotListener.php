<?php

namespace App\Console\Commands;

use App\Models\CuentaSeace;
use App\Models\SubscriptionContractMatch;
use App\Models\TelegramSubscription;
use App\Services\AccountCompatibilityService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use App\Services\TdrAnalysisFormatter;
use App\Services\TdrAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TelegramBotListener extends Command
{
    protected $signature = 'telegram:listen {--once : Procesar solo una vez}';
    protected $description = 'Escuchar actualizaciones de Telegram (polling) y procesar clicks de botones';

    protected int $lastUpdateId = 0;
    protected string $baseUrl;
    protected string $telegramApiBase;
    protected bool $debugLogging;
    protected TdrAnalysisFormatter $formatter;
    protected CompatibilityScoreService $compatibilityService;
    protected AccountCompatibilityService $compatibilityRepository;
    protected string $contratoCachePrefix = 'telegram:contrato:';

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = (string) config('services.seace.base_url');
        $this->telegramApiBase = rtrim((string) config('services.telegram.api_base', ''), '/');
        $this->debugLogging = (bool) config('services.telegram.debug_logs', false);
        $this->formatter = new TdrAnalysisFormatter();
        $this->compatibilityService = app(CompatibilityScoreService::class);
        $this->compatibilityRepository = app(AccountCompatibilityService::class);

        if (!empty(config('services.telegram.bot_token')) && $this->telegramApiBase === '') {
            Log::warning('Telegram Listener: TELEGRAM_API_BASE no configurado; el comando quedará inactivo hasta definirlo.');
        }
    }

    public function handle()
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('Token de Telegram no configurado');
            return Command::FAILURE;
        }

        if ($this->telegramApiBase === '') {
            $this->error('Configura TELEGRAM_API_BASE en el .env antes de iniciar el listener');
            return Command::FAILURE;
        }

        $this->info('🤖 Bot de Telegram iniciado (modo polling)');
        $this->info('📡 Esperando clicks en botones...');
        $this->info('🛑 Presiona Ctrl+C para detener');

        do {
            try {
                $updates = $this->getUpdates($token);

                foreach ($updates as $update) {
                    // Actualizar ID de última actualización
                    $this->lastUpdateId = $update['update_id'];

                    // Procesar callback_query (clicks en botones)
                    if (isset($update['callback_query'])) {
                        $this->handleCallbackQuery($update['callback_query'], $token);
                    }
                }

                // Esperar 2 segundos antes de siguiente consulta
                if (!$this->option('once')) {
                    sleep(2);
                }

            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                Log::error('Telegram Bot Listener Error', ['exception' => $e->getMessage()]);

                if (!$this->option('once')) {
                    sleep(5); // Esperar más tiempo si hay error
                }
            }
        } while (!$this->option('once'));

        return Command::SUCCESS;
    }

    /**
     * Obtener actualizaciones de Telegram (getUpdates)
     */
    protected function getUpdates(string $token): array
    {
        $response = Http::timeout(30)->get($this->buildTelegramUrl($token, 'getUpdates'), [
            'offset' => $this->lastUpdateId + 1,
            'timeout' => 25, // Long polling
            'allowed_updates' => ['callback_query'], // Solo callbacks
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error al obtener updates: ' . $response->body());
        }

        $data = $response->json();

        if (!($data['ok'] ?? false)) {
            throw new \Exception('Telegram API error: ' . ($data['description'] ?? 'Unknown'));
        }

        return $data['result'] ?? [];
    }

    /**
     * Procesar click en botón
     */
    protected function handleCallbackQuery(array $callbackQuery, string $token): void
    {
        $callbackId = $callbackQuery['id'];
        $chatId = $callbackQuery['from']['id'] ?? $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'] ?? '';

        $this->debug('Callback recibido', [
            'chat_id' => $chatId,
            'data' => $data,
        ]);

        // Verificar si es un click en "Analizar"
        if (strpos($data, 'analizar_') === 0) {
            // Formato: analizar_{idContrato}_{idContratoArchivo}_{nombreArchivo}
            $parts = explode('_', $data, 4); // Limitar a 4 partes
            $idContrato = (int) ($parts[1] ?? 0);
            $idContratoArchivo = (int) ($parts[2] ?? 0);
            $nombreArchivo = $parts[3] ?? 'archivo.pdf';

            $this->info("🔍 Usuario {$chatId} solicitó análisis del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

            // Responder inmediatamente al callback
            $this->answerCallbackQuery($callbackId, '⏳ Analizando proceso...', $token);

            // Procesar análisis en background
            $this->analizarProcesoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
        } elseif (strpos($data, 'descargar_') === 0) {
            // Formato: descargar_{idContrato}_{idContratoArchivo}_{nombreArchivo}
            $parts = explode('_', $data, 4);
            $idContrato = (int) ($parts[1] ?? 0);
            $idContratoArchivo = (int) ($parts[2] ?? 0);
            $nombreArchivo = $parts[3] ?? 'archivo.pdf';

            $this->info("📥 Usuario {$chatId} solicitó descarga del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

            $this->answerCallbackQuery($callbackId, '📥 Preparando descarga...', $token);
            $this->descargarArchivoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
        } elseif (str_starts_with($data, 'compatibilidad_') || str_starts_with($data, 'compatrefresh_')) {
            $parts = explode('_', $data, 4);
            $idContrato = (int) ($parts[1] ?? 0);
            $idContratoArchivo = (int) ($parts[2] ?? 0);
            $nombreArchivo = $parts[3] ?? 'archivo.pdf';
            $forceRefresh = str_starts_with($data, 'compatrefresh_');

            $this->info("🏅 Usuario {$chatId} solicitó compatibilidad del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

            $this->answerCallbackQuery(
                $callbackId,
                $forceRefresh ? '🔄 Recalculando score...' : '⏱️ Calculando score...',
                $token
            );

            $this->evaluarCompatibilidadParaUsuario(
                $chatId,
                $idContrato,
                $idContratoArchivo,
                $nombreArchivo,
                $token,
                $forceRefresh
            );
        } else {
            $this->answerCallbackQuery($callbackId, '❌ Acción no reconocida', $token);
        }
    }

    /**
     * Responder al callback query
     */
    protected function answerCallbackQuery(string $callbackQueryId, string $text, string $token): void
    {
        Http::post($this->buildTelegramUrl($token, 'answerCallbackQuery'), [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]);
    }

    /**
     * Analizar proceso y enviar resultado al usuario
     * OPTIMIZADO: Ya no consulta SEACE para listar archivos - recibe datos directamente del callback
     */
    protected function analizarProcesoParaUsuario(
        string $chatId,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token
    ): void {
        try {
            // 1. Obtener cuenta SEACE activa
            $cuenta = CuentaSeace::activa()->first();

            if (!$cuenta) {
                $this->enviarMensaje($chatId, '❌ No hay cuenta SEACE activa configurada', $token);
                return;
            }

            // 2. Validar que tengamos el ID del archivo
            if ($idContratoArchivo === 0) {
                $this->enviarMensaje($chatId, '❌ ID de archivo inválido. Por favor, intenta de nuevo.', $token);
                return;
            }

            $tdrService = new TdrAnalysisService();

            if ($cached = $tdrService->obtenerAnalisisDesdeCache($idContratoArchivo, 'telegram')) {
                $this->info("✅ Análisis recuperado desde caché para archivo {$idContratoArchivo}");
                $this->enviarResultadoAnalisisTelegram($chatId, $cached, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
                return;
            }

            $this->info("🤖 Analizando {$nombreArchivo} (ID: {$idContratoArchivo}) con IA...");

            // 3. Usar servicio compartido para análisis completo (DIRECTO - sin listar archivos)
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                ['idContrato' => $idContrato],
                'telegram'
            );

            // 4. Enviar resultado al usuario con botones
            if ($resultado['success']) {
                $this->enviarResultadoAnalisisTelegram($chatId, $resultado, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
                $this->info("✅ Análisis enviado a usuario {$chatId}");
            } else {
                // Error del análisis
                $errorMsg = $resultado['error'] ?? 'Error desconocido';

                // Agregar botón de reintentar si es un error temporal
                if (strpos($errorMsg, 'temporalmente') !== false ||
                    strpos($errorMsg, 'intenta') !== false ||
                    strpos($errorMsg, 'saturado') !== false) {

                    $retryCallback = $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '🔄 Reintentar Análisis',
                                    'callback_data' => $retryCallback,
                                ]
                            ]
                        ]
                    ];
                    $this->enviarMensajeConBotones($chatId, "❌ {$errorMsg}", $keyboard, $token);
                } else {
                    $this->enviarMensaje($chatId, "❌ Error al analizar: {$errorMsg}", $token);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al analizar proceso para usuario', [
                'chat_id' => $chatId,
                'id_contrato' => $idContrato,
                'exception' => $e->getMessage()
            ]);

            // Determinar si es un error temporal o permanente
            $errorMsg = $e->getMessage();
            $esErrorTemporal = strpos($errorMsg, 'temporalmente') !== false
                            || strpos($errorMsg, 'intenta') !== false
                            || strpos($errorMsg, 'HTTP 500') !== false
                            || strpos($errorMsg, 'saturado') !== false;

            if ($esErrorTemporal) {
                $retryCallback = $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🔄 Reintentar Análisis',
                                'callback_data' => $retryCallback,
                            ]
                        ]
                    ]
                ];
                $this->enviarMensajeConBotones($chatId, "❌ {$errorMsg}", $keyboard, $token);
            } else {
                $this->enviarMensaje($chatId, "❌ Error al procesar: {$errorMsg}", $token);
            }
        }
    }

    /**     * Enviar mensaje con botones inline
     */
    protected function enviarMensajeConBotones(string $chatId, string $mensaje, array $keyboard, string $token): void
    {
        try {
            $response = Http::post($this->buildTelegramUrl($token, 'sendMessage'), [
                'chat_id' => $chatId,
                'text' => $mensaje,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($keyboard),
            ]);

            $success = $response->successful() && ($response->json()['ok'] ?? false);

            if (!$success) {
                $error = $response->json()['description'] ?? ($response->body() ?: 'Error desconocido');
                Log::error('Telegram: Error al enviar respuesta con botones', [
                    'chat_id' => $chatId,
                    'error' => $error,
                ]);
                $this->enviarMensaje($chatId, "❌ Telegram rechazó el mensaje: {$error}", $token);
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje con botones', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            $this->enviarMensaje($chatId, '❌ Error al enviar la respuesta. Intenta nuevamente.', $token);
        }
    }

    /**     * Enviar mensaje a usuario
     */
    protected function enviarMensaje(string $chatId, string $texto, string $token): void
    {
        Http::post($this->buildTelegramUrl($token, 'sendMessage'), [
            'chat_id' => $chatId,
            'text' => $texto,
            'parse_mode' => 'HTML',
        ]);
    }

    protected function enviarResultadoAnalisisTelegram(
        string $chatId,
        array $resultado,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token
    ): void {
        $analisisData = $resultado['data']['analisis'] ?? [];
        $archivoNombre = $resultado['data']['archivo'] ?? $nombreArchivo;
        $contextoContrato = $resultado['data']['contexto_contrato'] ?? null;

        $mensaje = $resultado['formatted']['telegram']
            ?? $this->formatter->formatForTelegram($analisisData, $archivoNombre, $contextoContrato);

        $downloadCallback = $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo);
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => $downloadCallback,
                    ]
                ]
            ]
        ];

        $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);
    }
    /**
     * Descargar archivo TDR y enviarlo al usuario por Telegram
     * Reutiliza la lógica de descarga del TdrAnalysisService
     */
    protected function descargarArchivoParaUsuario(
        string $chatId,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token
    ): void {
        try {
            $cuenta = CuentaSeace::activa()->first();

            if (!$cuenta) {
                $this->enviarMensaje($chatId, '❌ No hay cuenta SEACE activa', $token);
                return;
            }

            $this->info("📥 Descargando {$nombreArchivo} (ID: {$idContratoArchivo})...");
            $this->enviarMensaje($chatId, '📥 Preparando descarga...', $token);

            $persistence = new TdrPersistenceService();
            $archivoPersistido = $persistence->resolveArchivo(
                $idContratoArchivo,
                $nombreArchivo,
                $idContrato,
                ['idContrato' => $idContrato]
            );

            $documentService = new TdrDocumentService($persistence);
            $documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);

            if (!$archivoPersistido->hasStoredFile()) {
                $this->enviarMensaje($chatId, '❌ No fue posible almacenar el archivo en caché', $token);
                return;
            }

            $disk = Storage::disk($archivoPersistido->storage_disk ?? config('filesystems.default'));
            $documentBinary = $disk->get($archivoPersistido->storage_path);

            $telegramResponse = Http::attach(
                'document',
                $documentBinary,
                $nombreArchivo
            )->post($this->buildTelegramUrl($token, 'sendDocument'), [
                'chat_id' => $chatId,
                'caption' => "📄 {$nombreArchivo}\n\n✅ Enviado desde caché local",
            ]);

            if ($telegramResponse->successful()) {
                $this->info("✅ Archivo enviado a usuario {$chatId}");
            } else {
                $this->enviarMensaje($chatId, '❌ Error al enviar archivo', $token);
                Log::error('Error al enviar documento por Telegram', [
                    'response' => $telegramResponse->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al descargar archivo para usuario', [
                'chat_id' => $chatId,
                'id_archivo' => $idContratoArchivo,
                'error' => $e->getMessage()
            ]);

            $this->enviarMensaje($chatId, '❌ Error: ' . $e->getMessage(), $token);
        }
    }

    protected function evaluarCompatibilidadParaUsuario(
        string $chatId,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token,
        bool $forceRefresh = false
    ): void {
        if ($idContrato <= 0) {
            $this->enviarMensaje($chatId, '❌ No se pudo identificar el proceso.', $token);
            return;
        }

        if ($idContratoArchivo <= 0) {
            $this->enviarMensaje($chatId, '❌ Este proceso no tiene un TDR público disponible.', $token);
            return;
        }

        $subscription = TelegramSubscription::where('chat_id', $chatId)->first();

        if (!$subscription) {
            $this->enviarMensaje($chatId, '❌ No encontramos una suscripción activa para este chat. Usa /start en el bot para registrarte.', $token);
            return;
        }

        if (blank($subscription->company_copy)) {
            $this->enviarMensaje($chatId, '✍️ Configura el copy de tu empresa en el panel web antes de solicitar el score.', $token);
            return;
        }

        $cachedContrato = $this->getCachedContratoPayload($idContrato);
        $existingMatch = $this->compatibilityRepository->findMatch($subscription, $idContrato);

        if (!$forceRefresh && $this->compatibilityRepository->canReuseMatch($existingMatch, $subscription)) {
            $this->enviarMensajeCompatibilidad(
                $chatId,
                $existingMatch,
                true,
                $token,
                $idContrato,
                $idContratoArchivo,
                $nombreArchivo
            );
            return;
        }

        $analisis = $this->obtenerAnalisisParaCompatibilidad(
            $idContrato,
            $idContratoArchivo,
            $nombreArchivo,
            $cachedContrato
        );

        if (!($analisis['success'] ?? false)) {
            $mensaje = $analisis['error'] ?? 'No se pudo completar el análisis IA del TDR.';
            $this->enviarMensaje($chatId, '❌ ' . $mensaje, $token);
            return;
        }

        $payload = $analisis['data'] ?? [];
        $contratoSnapshot = $this->resolveContratoSnapshotForCompatibility(
            $idContrato,
            $payload,
            $existingMatch,
            $cachedContrato
        );

        try {
            $compatResult = $this->compatibilityService->ensureScore(
                $subscription,
                $contratoSnapshot,
                $payload,
                $forceRefresh
            );
        } catch (\Throwable $e) {
            Log::error('Compatibilidad IA: excepción', [
                'chat_id' => $chatId,
                'contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);

            $this->enviarMensaje($chatId, '❌ Error al evaluar compatibilidad: ' . $e->getMessage(), $token);
            return;
        }

        if (!empty($compatResult['error'])) {
            $this->enviarMensaje($chatId, '❌ ' . $compatResult['error'], $token);
            return;
        }

        /** @var SubscriptionContractMatch|null $match */
        $match = $compatResult['match'] ?? null;

        if (!$match) {
            $this->enviarMensaje($chatId, '❌ No se pudo registrar el puntaje de compatibilidad para este proceso.', $token);
            return;
        }

        $this->enviarMensajeCompatibilidad(
            $chatId,
            $match,
            $compatResult['from_cache'] ?? false,
            $token,
            $idContrato,
            $idContratoArchivo,
            $nombreArchivo
        );
    }

    protected function obtenerAnalisisParaCompatibilidad(
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        ?array $contratoCache = null
    ): array {
        $tdrService = new TdrAnalysisService();

        if ($cached = $tdrService->obtenerAnalisisDesdeCache($idContratoArchivo, 'dashboard')) {
            return $cached;
        }

        $cuenta = CuentaSeace::activa()->first();

        if (!$cuenta) {
            return [
                'success' => false,
                'error' => 'No hay cuenta SEACE activa configurada para descargar el TDR.',
            ];
        }

        $contextoContrato = array_merge(['idContrato' => $idContrato], $contratoCache ?? []);

        return $tdrService->analizarDesdeSeace(
            $idContratoArchivo,
            $nombreArchivo,
            $cuenta,
            $contextoContrato,
            'dashboard'
        );
    }

    protected function resolveContratoSnapshotForCompatibility(
        int $idContrato,
        array $analysisPayload,
        ?SubscriptionContractMatch $existingMatch = null,
        ?array $contratoCache = null
    ): array {
        $contexto = $analysisPayload['contexto_contrato'] ?? [];
        $cacheData = $contratoCache ?? [];

        return [
            'idContrato' => $idContrato,
            'desContratacion' => $cacheData['desContratacion']
                ?? $contexto['codigo_proceso']
                ?? $existingMatch?->contrato_codigo,
            'nomEntidad' => $cacheData['nomEntidad']
                ?? $contexto['entidad']
                ?? $existingMatch?->contrato_entidad,
            'nomObjetoContrato' => $cacheData['nomObjetoContrato']
                ?? $contexto['objeto']
                ?? $existingMatch?->contrato_objeto,
            'desObjetoContrato' => $cacheData['desObjetoContrato']
                ?? $contexto['descripcion']
                ?? null,
            'nomEstadoContrato' => $cacheData['nomEstadoContrato']
                ?? $contexto['estado']
                ?? null,
            'fecPublica' => $cacheData['fecPublica']
                ?? $contexto['fecha_publicacion']
                ?? null,
            'fecFinCotizacion' => $cacheData['fecFinCotizacion']
                ?? $contexto['fecha_cierre']
                ?? null,
        ];
    }

    protected function enviarMensajeCompatibilidad(
        string $chatId,
        SubscriptionContractMatch $match,
        bool $fromCache,
        string $token,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo
    ): void {
        $payload = $match->analisis_payload ?? [];
        $nivel = strtoupper((string) ($payload['nivel'] ?? 'SIN CLASIFICAR'));
        $explicacion = trim((string) ($payload['explicacion'] ?? $payload['detalle'] ?? 'Sin explicación detallada.'));
        $score = $match->score !== null ? number_format((float) $match->score, 1) : 'N/D';
        $timestamp = $match->analizado_en
            ? $match->analizado_en->copy()->timezone(config('app.timezone', 'UTC'))->format('d/m/Y H:i')
            : null;

        $mensaje = "🏅 <b>Compatibilidad IA</b>\n\n";
        $mensaje .= "📊 <b>Puntaje:</b> {$score}/100\n";
        $mensaje .= "🎯 <b>Nivel:</b> {$nivel}\n";

        if ($timestamp) {
            $mensaje .= "🕒 <b>Evaluado:</b> {$timestamp}\n";
        }

        $mensaje .= "\n📝 <b>Código:</b> " . ($match->contrato_codigo ?? 'N/A') . "\n";
        $mensaje .= "🏢 <b>Entidad:</b> " . ($match->contrato_entidad ?? 'N/A') . "\n";

        if ($match->contrato_objeto) {
            $mensaje .= "🎯 <b>Objeto:</b> {$match->contrato_objeto}\n";
        }

        $mensaje .= "\n🧠 <b>Explicación:</b> {$explicacion}\n";

        if ($fromCache) {
            $mensaje .= "\n♻️ Resultado recuperado desde caché para tu copy actual.";
        }

        $mensaje .= "\n🤖 <i>Vigilante SEACE</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                    [
                        'text' => '🤖 Analizar TDR',
                        'callback_data' => $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                ],
                [
                    [
                        'text' => '🔄 Recalcular Score',
                        'callback_data' => $this->buildCallbackData('compatrefresh', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                ],
            ],
        ];

        $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);
    }

    protected function getCachedContratoPayload(int $idContrato): ?array
    {
        if ($idContrato <= 0) {
            return null;
        }

        return Cache::get($this->buildContratoCacheKey($idContrato));
    }

    protected function buildContratoCacheKey(int $idContrato): string
    {
        return $this->contratoCachePrefix . $idContrato;
    }

    protected function buildCallbackData(string $action, int $idContrato, int $idArchivo, string $nombreArchivo): string
    {
        $nombre = $this->sanitizeCallbackFilename($nombreArchivo);
        return sprintf('%s_%d_%d_%s', $action, $idContrato, $idArchivo, $nombre);
    }

    protected function sanitizeCallbackFilename(string $nombre): string
    {
        $sanitized = str_replace([' ', '/', '\\'], '_', $nombre);
        $sanitized = preg_replace('/[^A-Za-z0-9_\-.]/', '', $sanitized) ?? '';

        if ($sanitized === '') {
            $sanitized = 'archivo.pdf';
        }

        return substr($sanitized, 0, 30);
    }

    protected function buildTelegramUrl(string $token, string $method): string
    {
        if ($this->telegramApiBase === '') {
            throw new RuntimeException('TELEGRAM_API_BASE no está configurada');
        }

        return sprintf('%s/bot%s/%s', $this->telegramApiBase, $token, ltrim($method, '/'));
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('Telegram Listener: ' . $message, $context);
    }
}
