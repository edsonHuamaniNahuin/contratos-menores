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
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class TelegramBotListener extends Command implements SignalableCommandInterface, Isolatable
{
    protected $signature = 'telegram:listen {--once : Procesar solo una vez}';
    protected $description = 'Escuchar actualizaciones de Telegram (polling) y procesar clicks de botones';

    // ── Offset persistente: sobrevive reinicios ──────────────────────
    private const OFFSET_CACHE_KEY = 'telegram:listener:last_offset';
    private const OFFSET_CACHE_TTL = 2592000; // 30 días en segundos

    protected int $lastUpdateId = 0;
    protected bool $shouldStop = false;
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

        // ── Restaurar offset persistente (sobrevive reinicios) ───────
        $this->lastUpdateId = (int) Cache::get(self::OFFSET_CACHE_KEY, 0);
        if ($this->lastUpdateId > 0) {
            $this->info("📍 Offset restaurado: {$this->lastUpdateId}");
        }

        $this->info('🤖 Bot de Telegram iniciado (modo polling) — PID ' . getmypid());
        $this->info('📡 Esperando clicks en botones...');
        $this->info('🛑 Presiona Ctrl+C para detener');

        do {
            // Despachar señales pendientes (SIGTERM/SIGINT) explícitamente.
            // pcntl_async_signals no siempre interrumpe curl_exec,
            // así que despachamos aquí por seguridad.
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($this->shouldStop) {
                break;
            }

            try {
                $updates = $this->getUpdates($token);

                foreach ($updates as $update) {
                    if ($this->shouldStop) {
                        break;
                    }

                    $this->lastUpdateId = $update['update_id'];

                    // Persistir offset inmediatamente tras recibirlo
                    Cache::put(self::OFFSET_CACHE_KEY, $this->lastUpdateId, self::OFFSET_CACHE_TTL);

                    if (isset($update['callback_query'])) {
                        $this->handleCallbackQuery($update['callback_query'], $token);
                    }
                }

                if (!$this->option('once') && !$this->shouldStop) {
                    usleep(500_000); // 0.5s — más responsivo que sleep(2)
                }

            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                Log::error('Telegram Bot Listener Error', ['exception' => $e->getMessage()]);

                if (!$this->option('once') && !$this->shouldStop) {
                    sleep(3);
                }
            }
        } while (!$this->shouldStop && !$this->option('once'));

        $this->info('👋 Listener detenido correctamente (PID ' . getmypid() . ')');

        return Command::SUCCESS;
    }

    /**
     * Clave de lock para Isolatable.
     * Laravel usa Cache::lock() con esta clave para garantizar instancia única.
     */
    public function isolatableId(): string
    {
        return 'telegram-bot-listener';
    }

    /**
     * Señales que el comando puede manejar (Ctrl+C, kill)
     */
    public function getSubscribedSignals(): array
    {
        return defined('SIGINT') ? [SIGINT, SIGTERM] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;
        $this->info("\n🛑 Señal {$signal} recibida, deteniendo listener...");

        // Retornar 0 = "salir con código 0" (shutdown limpio).
        // NOTA: `false` significa "no salir" en Symfony → el loop seguiría
        // y systemd tendría que matar con SIGKILL.
        return 0;
    }

    /**
     * Obtener actualizaciones de Telegram (getUpdates)
     */
    protected function getUpdates(string $token): array
    {
        // timeout HTTP = 15s, long-poll Telegram = 10s
        // Mantener corto para que SIGTERM se despache entre iteraciones.
        // Telegram devuelve [] al expirar el long-poll (sin updates).
        $response = Http::timeout(15)->get($this->buildTelegramUrl($token, 'getUpdates'), [
            'offset' => $this->lastUpdateId + 1,
            'timeout' => 10,
            'allowed_updates' => ['callback_query'],
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
     * Procesar click en botón.
     *
     * Diseño seguro:
     * - Dedup atómico por callback_id → Cache::add() garantiza procesamiento único
     * - answerCallbackQuery() se invoca PRIMERO → Telegram deja de reenviar el callback
     * - Concurrencia IA → protegida a nivel de TdrAnalysisService (Cache::lock atómico en DB)
     */
    protected function handleCallbackQuery(array $callbackQuery, string $token): void
    {
        $callbackId = $callbackQuery['id'];
        $chatId = $callbackQuery['from']['id'] ?? $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'] ?? '';

        // ── Deduplicación atómica: si ya se procesó este callback, ignorar ──
        // Cache::add() retorna false si la key ya existe (atómico en DB/Redis)
        $dedupKey = "telegram:cb:{$callbackId}";
        if (!Cache::add($dedupKey, true, 300)) {
            $this->debug("Callback {$callbackId} ya procesado, ignorando (dedup)", [
                'chat_id' => $chatId,
                'data' => $data,
            ]);
            // Responder igualmente para quitar el spinner del botón
            $this->answerCallbackQuery($callbackId, '', $token);
            return;
        }

        // ── Lock anti doble-click: evita ejecutar la MISMA acción en paralelo ──
        // callback_id cambia por click, así que además bloqueamos por chat+payload.
        $actionLockKey = 'telegram:action:' . md5($chatId . '|' . $data);
        $actionLock = Cache::lock($actionLockKey, 25);

        if (!$actionLock->get()) {
            $this->debug('Acción ya en progreso, ignorando callback duplicado por doble click', [
                'chat_id' => $chatId,
                'data' => $data,
            ]);
            $this->answerCallbackQuery($callbackId, '⏳ Ya estamos procesando esta solicitud...', $token);
            return;
        }

        try {
            $this->debug('Callback recibido', [
                'chat_id' => $chatId,
                'data' => $data,
            ]);

            // Verificar si es un click en "Analizar"
            if (strpos($data, 'analizar_') === 0) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                $this->info("🔍 Usuario {$chatId} solicitó análisis del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

                $this->answerCallbackQuery($callbackId, '⏳ Analizando proceso...', $token);
                $this->analizarProcesoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);

            } elseif (strpos($data, 'descargar_') === 0) {
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
        } finally {
            $actionLock->release();
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
            $cuenta = CuentaSeace::activa()->first();

            if ($idContratoArchivo === 0) {
                $this->enviarMensaje($chatId, '❌ ID de archivo inválido. Por favor, intenta de nuevo.', $token);
                return;
            }

            $tdrService = new TdrAnalysisService();

            // TdrAnalysisService ya tiene Cache::lock atómico interno —
            // si otro usuario pide el mismo análisis, espera y reutiliza.
            $this->info("🤖 Analizando {$nombreArchivo} (ID: {$idContratoArchivo}) con IA...");
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                ['idContrato' => $idContrato],
                'telegram'
            );

            // Enviar resultado al usuario
            if ($resultado['success'] ?? false) {
                $this->enviarResultadoAnalisisTelegram($chatId, $resultado, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
                $this->info("✅ Análisis enviado a usuario {$chatId}");
            } else {
                $errorMsg = $resultado['error'] ?? 'Error desconocido';

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

            $this->info("📥 Descargando {$nombreArchivo} (ID: {$idContratoArchivo})...");

            $persistence = new TdrPersistenceService();

            // Resolver archivo (idempotente: firstOrCreate en DB)
            $archivoPersistido = $persistence->resolveArchivo(
                $idContratoArchivo, $nombreArchivo, $idContrato,
                ['idContrato' => $idContrato]
            );

            // ensureLocalFile ya es idempotente: si existe, retorna path
            if (!$archivoPersistido->hasStoredFile()) {
                if ($cuenta) {
                    try {
                        $documentService = new TdrDocumentService($persistence);
                        $documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);
                    } catch (\Throwable $authDownloadException) {
                        Log::warning('Descarga autenticada falló, intentando endpoint público', [
                            'chat_id' => $chatId,
                            'id_archivo' => $idContratoArchivo,
                            'error' => $authDownloadException->getMessage(),
                        ]);

                        $publicService = new \App\Services\Tdr\PublicTdrDocumentService(
                            $persistence,
                            new \App\Services\SeacePublicArchivoService()
                        );
                        $publicService->ensureLocalArchivo(
                            $idContrato,
                            ['idContratoArchivo' => $idContratoArchivo, 'nombre' => $nombreArchivo],
                            ['idContrato' => $idContrato]
                        );
                    }
                } else {
                    $publicService = new \App\Services\Tdr\PublicTdrDocumentService(
                        $persistence,
                        new \App\Services\SeacePublicArchivoService()
                    );
                    $publicService->ensureLocalArchivo(
                        $idContrato,
                        ['idContratoArchivo' => $idContratoArchivo, 'nombre' => $nombreArchivo],
                        ['idContrato' => $idContrato]
                    );
                }
                $archivoPersistido->refresh();
            }

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

            // Fallback resiliente: si otro intento concurrente ya lo dejó en caché,
            // enviar desde storage en vez de reportar error al usuario.
            try {
                $persistence = new TdrPersistenceService();
                $archivoPersistido = $persistence->resolveArchivo(
                    $idContratoArchivo,
                    $nombreArchivo,
                    $idContrato,
                    ['idContrato' => $idContrato]
                );

                if ($archivoPersistido->hasStoredFile()) {
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
                        $this->info("✅ Fallback desde caché enviado a usuario {$chatId}");
                        return;
                    }
                }
            } catch (\Throwable $fallbackException) {
                Log::warning('Fallback caché descarga falló', [
                    'chat_id' => $chatId,
                    'id_archivo' => $idContratoArchivo,
                    'error' => $fallbackException->getMessage(),
                ]);
            }

            $this->enviarMensaje($chatId, '❌ No se pudo descargar el archivo en este momento. Intenta nuevamente en unos minutos.', $token);
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
        $cuenta = CuentaSeace::activa()->first();
        $contextoContrato = array_merge(['idContrato' => $idContrato], $contratoCache ?? []);

        // TdrAnalysisService tiene Cache::lock atómico interno — safe para concurrencia
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
