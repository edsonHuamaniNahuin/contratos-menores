<?php

namespace App\Console\Commands;

use App\Models\CuentaSeace;
use App\Models\SubscriptionContractMatch;
use App\Models\TelegramSubscription;
use App\Services\AccountCompatibilityService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\Tdr\PublicTdrDocumentService;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use App\Services\TdrAnalysisFormatter;
use App\Services\TdrAnalysisService;
use App\Services\SeacePublicArchivoService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Command\SignalableCommandInterface;

class TelegramBotListener extends Command implements SignalableCommandInterface, Isolatable
{
    protected $signature = 'telegram:listen {--once : Procesar solo una vez}';
    protected $description = 'Escuchar actualizaciones de Telegram (polling) y procesar clicks de botones';

    // ── Offset persistente: sobrevive reinicios ──────────────────────
    private const OFFSET_CACHE_TTL = 2592000; // 30 días en segundos

    protected int $lastUpdateId = 0;
    protected bool $shouldStop = false;
    protected string $baseUrl;
    protected string $telegramApiBase;
    protected bool $debugLogging;
    protected TdrAnalysisFormatter $formatter;
    protected CompatibilityScoreService $compatibilityService;
    protected AccountCompatibilityService $compatibilityRepository;

    /**
     * Prefijo de cache por ambiente para aislar QA y Producción.
     */
    protected string $envCachePrefix;
    protected string $contratoCachePrefix;
    protected string $offsetCacheKey;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = (string) config('services.seace.base_url');
        $this->telegramApiBase = rtrim((string) config('services.telegram.api_base', ''), '/');
        $this->debugLogging = (bool) config('services.telegram.debug_logs', false);
        $this->envCachePrefix = 'telegram:' . config('app.env', 'production') . ':';
        $this->contratoCachePrefix = $this->envCachePrefix . 'contrato:';
        $this->offsetCacheKey = $this->envCachePrefix . 'listener:last_offset';
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
        $this->lastUpdateId = (int) Cache::get($this->offsetCacheKey, 0);
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
                    Cache::put($this->offsetCacheKey, $this->lastUpdateId, self::OFFSET_CACHE_TTL);

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
        return 'telegram-bot-listener-' . config('app.env', 'production');
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
        $dedupKey = $this->envCachePrefix . "cb:{$callbackId}";
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
        $actionLockKey = $this->envCachePrefix . 'action:' . md5($chatId . '|' . $data);
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

                // Resolver archivo dinámicamente si el callback tiene id=0
                if ($idContratoArchivo === 0 && $idContrato > 0) {
                    $resolved = $this->resolveArchivoFromCallback($idContrato, $idContratoArchivo, $nombreArchivo);
                    $idContratoArchivo = $resolved['idContratoArchivo'];
                    $nombreArchivo = $resolved['nombreArchivo'];
                }

                $this->info("🔍 Usuario {$chatId} solicitó análisis del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

                $this->answerCallbackQuery($callbackId, '⏳ Analizando proceso...', $token);
                $this->analizarProcesoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);

            } elseif (strpos($data, 'descargar_') === 0) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                // Resolver archivo dinámicamente si el callback tiene id=0
                if ($idContratoArchivo === 0 && $idContrato > 0) {
                    $resolved = $this->resolveArchivoFromCallback($idContrato, $idContratoArchivo, $nombreArchivo);
                    $idContratoArchivo = $resolved['idContratoArchivo'];
                    $nombreArchivo = $resolved['nombreArchivo'];
                }

                $this->info("📥 Usuario {$chatId} solicitó descarga del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

                $this->answerCallbackQuery($callbackId, '📥 Preparando descarga...', $token);
                $this->descargarArchivoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);

            } elseif (str_starts_with($data, 'compatibilidad_') || str_starts_with($data, 'compatrefresh_')) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';
                $forceRefresh = str_starts_with($data, 'compatrefresh_');

                // Resolver archivo dinámicamente si el callback tiene id=0
                if ($idContratoArchivo === 0 && $idContrato > 0) {
                    $resolved = $this->resolveArchivoFromCallback($idContrato, $idContratoArchivo, $nombreArchivo);
                    $idContratoArchivo = $resolved['idContratoArchivo'];
                    $nombreArchivo = $resolved['nombreArchivo'];
                }

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

            } elseif (strpos($data, 'cotizar_') === 0) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);

                $this->info("💼 Usuario {$chatId} solicitó cotizar contrato {$idContrato}");

                $this->answerCallbackQuery($callbackId, '💼 Preparando enlace de cotización...', $token);
                $this->prepararCotizacionParaUsuario($chatId, $idContrato, $token);

            } elseif (strpos($data, 'direcc_') === 0) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                if ($idContratoArchivo === 0 && $idContrato > 0) {
                    $resolved = $this->resolveArchivoFromCallback($idContrato, $idContratoArchivo, $nombreArchivo);
                    $idContratoArchivo = $resolved['idContratoArchivo'];
                    $nombreArchivo = $resolved['nombreArchivo'];
                }

                $this->info("🔍 Usuario {$chatId} solicitó análisis de direccionamiento del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

                $this->answerCallbackQuery($callbackId, '⏳ Analizando direccionamiento...', $token);
                $this->analizarDireccionamientoParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);

            } elseif (strpos($data, 'proforma_') === 0) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                if ($idContratoArchivo === 0 && $idContrato > 0) {
                    $resolved = $this->resolveArchivoFromCallback($idContrato, $idContratoArchivo, $nombreArchivo);
                    $idContratoArchivo = $resolved['idContratoArchivo'];
                    $nombreArchivo = $resolved['nombreArchivo'];
                }

                $this->info("📋 Usuario {$chatId} solicitó proforma del contrato {$idContrato} (Archivo ID: {$idContratoArchivo})");

                $this->answerCallbackQuery($callbackId, '📋 Generando proforma técnica...', $token);
                $this->generarProformaParaUsuario($chatId, $idContrato, $idContratoArchivo, $nombreArchivo, $token);

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
    protected function resolveUserIdFromChatId(string $chatId): ?int
    {
        return TelegramSubscription::where('chat_id', $chatId)->value('user_id');
    }

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

            $tdrService = (new TdrAnalysisService())->withOrigin('telegram')->withUserId($this->resolveUserIdFromChatId($chatId));

            // Recuperar contexto completo del contrato (cacheado al enviar la notificación)
            $cachedContrato = $this->getCachedContratoPayload($idContrato);
            $contratoData = array_merge(['idContrato' => $idContrato], $cachedContrato ?? []);

            // ── Feedback visible al usuario ──
            $this->sendChatAction($chatId, 'typing', $token);
            $loadingMsgId = $this->enviarMensajeProcesando(
                $chatId,
                "⏳ <b>Analizando TDR con IA...</b>\n📄 {$nombreArchivo}\n\nEsto puede tomar unos segundos, por favor espera.",
                $token
            );

            // TdrAnalysisService ya tiene Cache::lock atómico interno —
            // si otro usuario pide el mismo análisis, espera y reutiliza.
            $this->info("🤖 Analizando {$nombreArchivo} (ID: {$idContratoArchivo}) con IA...");
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                $contratoData,
                'telegram'
            );

            // Eliminar mensaje de loading
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            // Enviar resultado al usuario
            if ($resultado['success'] ?? false) {
                $this->enviarResultadoAnalisisTelegram($chatId, $resultado, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
                $this->info("✅ Análisis enviado a usuario {$chatId}");
            } else {
                if ($loadingMsgId) {
                    $this->eliminarMensaje($chatId, $loadingMsgId, $token);
                }

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
            if ($loadingMsgId ?? null) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("TelegramBot:analizarTdr [{$ref}]", [
                'ref' => $ref,
                'chat_id' => $chatId,
                'id_contrato' => $idContrato,
                'exception' => $e->getMessage()
            ]);

            $errorMsg = TdrAnalysisService::humanizeError($e->getMessage(), $ref);

            $retryCallback = $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔄 Reintentar Análisis', 'callback_data' => $retryCallback]]
                ]
            ];
            $this->enviarMensajeConBotones($chatId, "❌ {$errorMsg}", $keyboard, $token);
        }
    }

    /**
     * Analizar direccionamiento (corrupción) de TDR y enviar resultado al usuario.
     */
    protected function analizarDireccionamientoParaUsuario(
        string $chatId,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token
    ): void {
        $loadingMsgId = null;

        try {
            $cuenta = CuentaSeace::activa()->first();

            if ($idContratoArchivo === 0) {
                $this->enviarMensaje($chatId, '❌ ID de archivo inválido. Por favor, intenta de nuevo.', $token);
                return;
            }

            $tdrService = (new TdrAnalysisService())->withOrigin('telegram')->withUserId($this->resolveUserIdFromChatId($chatId));
            $cachedContrato = $this->getCachedContratoPayload($idContrato);
            $contratoData = array_merge(['idContrato' => $idContrato], $cachedContrato ?? []);

            $this->sendChatAction($chatId, 'typing', $token);
            $loadingMsgId = $this->enviarMensajeProcesando(
                $chatId,
                "⏳ <b>Analizando direccionamiento con IA...</b>\n📄 {$nombreArchivo}\n\n🔍 Detectando indicios de corrupción en las bases...",
                $token
            );

            $this->info("🔍 Analizando direccionamiento {$nombreArchivo} (ID: {$idContratoArchivo}) con IA...");
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                $contratoData,
                'telegram',
                false,
                'direccionamiento'
            );

            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            if ($resultado['success'] ?? false) {
                $this->enviarResultadoDireccionamientoTelegram($chatId, $resultado, $idContrato, $idContratoArchivo, $nombreArchivo, $token);
                $this->info("✅ Análisis de direccionamiento enviado a usuario {$chatId}");
            } else {
                $errorMsg = $resultado['error'] ?? 'Error desconocido';

                if (strpos($errorMsg, 'temporalmente') !== false || strpos($errorMsg, 'intenta') !== false) {
                    $retryCallback = $this->buildCallbackData('direcc', $idContrato, $idContratoArchivo, $nombreArchivo);
                    $keyboard = [
                        'inline_keyboard' => [
                            [['text' => '🔄 Reintentar Direccionamiento', 'callback_data' => $retryCallback]]
                        ]
                    ];
                    $this->enviarMensajeConBotones($chatId, "❌ {$errorMsg}", $keyboard, $token);
                } else {
                    $this->enviarMensaje($chatId, "❌ Error al analizar direccionamiento: {$errorMsg}", $token);
                }
            }

        } catch (\Exception $e) {
            if ($loadingMsgId ?? null) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("TelegramBot:direccionamiento [{$ref}]", [
                'ref' => $ref,
                'chat_id' => $chatId,
                'id_contrato' => $idContrato,
                'exception' => $e->getMessage(),
            ]);

            $errorMsg = TdrAnalysisService::humanizeError($e->getMessage(), $ref);
            $retryCallback = $this->buildCallbackData('direcc', $idContrato, $idContratoArchivo, $nombreArchivo);
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔄 Reintentar Direccionamiento', 'callback_data' => $retryCallback]]
                ]
            ];
            $this->enviarMensajeConBotones($chatId, "❌ {$errorMsg}", $keyboard, $token);
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
        $shareUrl = $resultado['data']['share_url'] ?? null;

        $mensaje = $resultado['formatted']['telegram']
            ?? $this->formatter->formatForTelegram($analisisData, $archivoNombre, $contextoContrato, $shareUrl);

        $downloadCallback = $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo);
        $cotizarCallback  = $this->buildCallbackData('cotizar', $idContrato, $idContratoArchivo, $nombreArchivo);
        $direccCallback   = $this->buildCallbackData('direcc', $idContrato, $idContratoArchivo, $nombreArchivo);
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => $downloadCallback,
                    ]
                ],
                [
                    [
                        'text' => '🔍 Detectar Direccionamiento',
                        'callback_data' => $direccCallback,
                    ]
                ],
                [
                    [
                        'text' => '💼 Cotizar en SEACE',
                        'callback_data' => $cotizarCallback,
                    ]
                ],
                ...($shareUrl ? [[
                    [
                        'text' => '📤 Compartir resultado',
                        'url'  => $shareUrl,
                    ]
                ]] : []),
            ]
        ];

        $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);
    }

    /**
     * Enviar resultado del análisis de direccionamiento (corrupción) por Telegram.
     */
    protected function enviarResultadoDireccionamientoTelegram(
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
        $shareUrl = $resultado['data']['share_url'] ?? null;

        $mensaje = $resultado['formatted']['telegram']
            ?? $this->formatter->formatDireccionamientoForTelegram($analisisData, $archivoNombre, $contextoContrato, $shareUrl);

        $downloadCallback = $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo);
        $analizarCallback = $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🤖 Análisis General TDR',
                        'callback_data' => $analizarCallback,
                    ]
                ],
                [
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => $downloadCallback,
                    ]
                ],
                ...($shareUrl ? [[
                    [
                        'text' => '📤 Compartir resultado',
                        'url'  => $shareUrl,
                    ]
                ]] : []),
            ]
        ];

        $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);
    }

    /**
     * Preparar enlace de cotización SEACE para el usuario de Telegram.
     *
     * Envía un mensaje con:
     * - URL de login del SEACE (botón inline)
     * - URL de cotización en formato monospace (fácil de copiar en Telegram)
     * - Instrucciones paso a paso
     *
     * No se puede autenticar automáticamente en SEACE desde fuera del
     * dominio (Same-Origin Policy), así que guiamos al usuario.
     */
    protected function prepararCotizacionParaUsuario(
        string $chatId,
        int $idContrato,
        string $token
    ): void {
        try {
            if ($idContrato <= 0) {
                $this->enviarMensaje($chatId, '❌ ID de contrato inválido para cotizar.', $token);
                return;
            }

            $this->sendChatAction($chatId, 'typing', $token);

            // Recuperar contexto del contrato si está cacheado
            $cachedContrato = $this->getCachedContratoPayload($idContrato);
            $codigoProceso = $cachedContrato['desContratacion'] ?? "Contrato #{$idContrato}";
            $entidad = $cachedContrato['nomEntidad'] ?? '';

            // Construir URL de la guía de cotización
            $cotizarGuiaUrl = url('/cotizar-guia') . '?' . http_build_query(array_filter([
                'proceso' => $codigoProceso,
                'entidad' => $entidad,
                'id' => $idContrato,
            ]));

            // Construir URL del portal SEACE
            $seaceBase = rtrim(config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe'), '/');
            $urlLogin = "{$seaceBase}/auth-proveedor/";

            $mensaje = "💼 <b>Cotizar en SEACE</b>\n\n";
            $mensaje .= "⚡ <i>Estamos casi allí — te dejamos todo listo para que sea rápido.</i>\n\n";
            $mensaje .= "📝 <b>Código:</b> <code>{$codigoProceso}</code>\n";

            if ($entidad) {
                $mensaje .= "🏢 <b>Entidad:</b> {$entidad}\n";
            }

            $mensaje .= "\n<b>3 pasos rápidos:</b>\n\n";
            $mensaje .= "1️⃣ Toca el código de arriba para <b>copiarlo</b>.\n\n";
            $mensaje .= "2️⃣ <b>Ingresa al portal SEACE</b> con el botón de abajo e inicia sesión.\n\n";
            $mensaje .= "3️⃣ En el buscador del portal, <b>pega el código</b> (Ctrl+V) y envía tu cotización.\n\n";
            $mensaje .= "🔒 <i>¿Por qué no te llevamos directo? El portal SEACE protege cada sesión de forma individual. Las cotizaciones solo pueden enviarse navegando dentro de su plataforma, garantizando la seguridad de tu cuenta.</i>";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📖 Ver guía completa de cotización',
                            'url'  => $cotizarGuiaUrl,
                        ],
                    ],
                    [
                        [
                            'text' => '🔗 Ir al portal SEACE',
                            'url'  => $urlLogin,
                        ],
                    ],
                ],
            ];

            $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);

            $this->info("✅ Instrucciones de cotización enviadas a usuario {$chatId} (contrato {$idContrato})"
);

        } catch (\Exception $e) {
            Log::error('Error al preparar cotización para usuario', [
                'chat_id'     => $chatId,
                'id_contrato' => $idContrato,
                'exception'   => $e->getMessage(),
            ]);

            $this->enviarMensaje(
                $chatId,
                '❌ Error al preparar el enlace de cotización. Intenta nuevamente.',
                $token
            );
        }
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

            // ── Feedback visible al usuario ──
            $this->sendChatAction($chatId, 'upload_document', $token);
            $loadingMsgId = $this->enviarMensajeProcesando(
                $chatId,
                "📥 <b>Preparando descarga...</b>\n📄 {$nombreArchivo}\n\nEspera un momento.",
                $token
            );

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
                if ($loadingMsgId) {
                    $this->eliminarMensaje($chatId, $loadingMsgId, $token);
                }
                $this->enviarMensaje($chatId, '❌ No fue posible almacenar el archivo en caché', $token);
                return;
            }

            $disk = Storage::disk($archivoPersistido->storage_disk ?? config('filesystems.default'));
            $documentBinary = $disk->get($archivoPersistido->storage_path);

            // Eliminar mensaje de loading antes de enviar el documento
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            $this->sendChatAction($chatId, 'upload_document', $token);

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
            if ($loadingMsgId ?? null) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

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
            // Score en caché — enviar directo sin loading
            $this->sendChatAction($chatId, 'typing', $token);
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

        // ── Feedback visible al usuario (solo cuando se calcula de cero) ──
        $this->sendChatAction($chatId, 'typing', $token);
        $loadingMsgId = $this->enviarMensajeProcesando(
            $chatId,
            $forceRefresh
                ? "🔄 <b>Recalculando score de compatibilidad...</b>\n\nEsto puede tomar unos segundos."
                : "🏅 <b>Calculando score de compatibilidad...</b>\n\nAnalizando TDR y evaluando tu perfil. Espera un momento.",
            $token
        );

        $analisis = $this->obtenerAnalisisParaCompatibilidad(
            $idContrato,
            $idContratoArchivo,
            $nombreArchivo,
            $cachedContrato,
            $subscription->user_id
        );

        if (!($analisis['success'] ?? false)) {
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }
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
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }
            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("TelegramBot:compatibilidad [{$ref}]", [
                'ref' => $ref,
                'chat_id' => $chatId,
                'contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);

            $this->enviarMensaje($chatId, '❌ ' . TdrAnalysisService::humanizeError($e->getMessage(), $ref), $token);
            return;
        }

        // Eliminar mensaje de loading antes de enviar el resultado
        if ($loadingMsgId) {
            $this->eliminarMensaje($chatId, $loadingMsgId, $token);
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
        ?array $contratoCache = null,
        ?int $userId = null
    ): array {
        $tdrService = (new TdrAnalysisService())->withOrigin('telegram')->withUserId($userId);
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
        $mensaje .= "📊 <b>Puntaje:</b> {$score}/10\n";
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

        $shareUrl = $match->analisis_payload['share_url'] ?? null;

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
                [
                    [
                        'text' => '💼 Cotizar en SEACE',
                        'callback_data' => $this->buildCallbackData('cotizar', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                ],
                ...($shareUrl ? [[
                    [
                        'text' => '📤 Compartir resultado',
                        'url'  => $shareUrl,
                    ]
                ]] : []),
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

    /**
     * Resolver archivo dinámicamente cuando el callback tiene idContratoArchivo=0.
     * Consulta el endpoint público de archivos del SEACE para obtener el ID real.
     *
     * @return array{idContratoArchivo: int, nombreArchivo: string}
     */
    protected function resolveArchivoFromCallback(int $idContrato, int $idContratoArchivo, string $nombreArchivo): array
    {
        if ($idContratoArchivo > 0) {
            return ['idContratoArchivo' => $idContratoArchivo, 'nombreArchivo' => $nombreArchivo];
        }

        if ($idContrato <= 0) {
            return ['idContratoArchivo' => 0, 'nombreArchivo' => $nombreArchivo];
        }

        try {
            $archivoService = new SeacePublicArchivoService();
            $respuesta = $archivoService->listarArchivos($idContrato);

            if (!($respuesta['success'] ?? false) || empty($respuesta['data'])) {
                Log::warning('Telegram: no se pudo resolver archivo para contrato', [
                    'idContrato' => $idContrato,
                ]);
                return ['idContratoArchivo' => 0, 'nombreArchivo' => $nombreArchivo];
            }

            $archivo = collect($respuesta['data'])
                ->first(fn ($item) => str_contains(strtolower($item['descripcionExtension'] ?? ''), 'pdf'))
                ?? $respuesta['data'][0];

            $resolvedId = (int) ($archivo['idContratoArchivo'] ?? 0);
            $resolvedName = $archivo['nombre'] ?? ($archivo['descripcionArchivo'] ?? $nombreArchivo);

            if ($resolvedId > 0) {
                $this->info("🔧 Archivo resuelto dinámicamente: ID {$resolvedId} ({$resolvedName})");

                // Cachear para futuros callbacks
                $cacheKey = sprintf('tdr:archivo-meta:%d', $idContrato);
                Cache::put($cacheKey, [
                    'idContratoArchivo' => $resolvedId,
                    'nombreArchivo' => $resolvedName,
                ], now()->addMinutes(60));
            }

            return ['idContratoArchivo' => $resolvedId, 'nombreArchivo' => $resolvedName];
        } catch (\Exception $e) {
            Log::warning('Telegram: excepción al resolver archivo dinámicamente', [
                'idContrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            return ['idContratoArchivo' => 0, 'nombreArchivo' => $nombreArchivo];
        }
    }

    protected function buildCallbackData(string $action, int $idContrato, int $idArchivo, string $nombreArchivo): string
    {
        $nombre = $this->sanitizeCallbackFilename($nombreArchivo);
        return sprintf('%s_%d_%d_%s', $action, $idContrato, $idArchivo, $nombre);
    }

    /**
     * Generar proforma técnica con IA y enviar enlace al usuario.
     */
    protected function generarProformaParaUsuario(
        string $chatId,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        string $token
    ): void {
        $loadingMsgId = null;

        try {
            if ($idContratoArchivo === 0) {
                $this->enviarMensaje($chatId, '❌ ID de archivo inválido. Por favor, intenta de nuevo.', $token);
                return;
            }

            // ── Resolver suscripción y perfil de empresa ──────────────────
            $subscription = TelegramSubscription::with('user.subscriberProfile')
                ->where('chat_id', $chatId)
                ->first();

            if (!$subscription || !$subscription->user) {
                $this->enviarMensaje($chatId, '❌ No encontramos tu cuenta. Usa /start en el bot para registrarte.', $token);
                return;
            }

            $profile = $subscription->user->subscriberProfile;

            if (!$profile || blank($profile->company_copy)) {
                $this->enviarMensaje(
                    $chatId,
                    "✍️ <b>Configura el perfil de tu empresa antes de generar la proforma.</b>\n\n"
                    . "Ve a <b>Configuración de Alertas</b> en el panel web y completa:\n"
                    . "• Nombre de empresa\n"
                    . "• Descripción de tu empresa\n\n"
                    . "🔗 " . config('app.url') . "/configurar-alertas",
                    $token
                );
                return;
            }

            $companyName = $profile->company_name ?? '';
            $companyCopy = $profile->company_copy;

            // ── Feedback visible ──────────────────────────────────────────
            $this->sendChatAction($chatId, 'typing', $token);
            $loadingMsgId = $this->enviarMensajeProcesando(
                $chatId,
                "📋 <b>Generando proforma técnica con IA...</b>\n📄 {$nombreArchivo}\n\n💼 Empresa: {$companyName}\n\n⏳ Esto puede tomar unos segundos, por favor espera.",
                $token
            );

            // ── Generar proforma via TdrAnalysisService ───────────────────
            $tdrService = (new TdrAnalysisService())->withOrigin('telegram')->withUserId($subscription->user_id);
            $cachedContrato = $this->getCachedContratoPayload($idContrato);
            $contratoData = array_merge(['idContrato' => $idContrato], $cachedContrato ?? []);

            $resultado = $tdrService->generarProformaDesdeArchivo(
                $idContratoArchivo,
                $nombreArchivo,
                $contratoData,
                $companyName,
                $companyCopy
            );

            // Eliminar loading
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
                $loadingMsgId = null;
            }

            if (!($resultado['success'] ?? false)) {
                $errorMsg = $resultado['error'] ?? 'No se pudo generar la proforma.';
                $this->enviarMensaje($chatId, "❌ {$errorMsg}", $token);
                return;
            }

            // ── Cachear proforma con token UUID ───────────────────────────
            $proformaToken = Str::uuid()->toString();
            Cache::put("proforma:{$proformaToken}", $resultado['data'] ?? [], now()->addHours(2));

            $appUrl = rtrim(config('app.url', ''), '/');
            $wordUrl  = "{$appUrl}/proforma/{$proformaToken}/word";
            $printUrl = "{$appUrl}/proforma/{$proformaToken}/print";
            $excelUrl = "{$appUrl}/proforma/{$proformaToken}/excel";

            $proformaData = $resultado['data'] ?? [];
            $proformaItems_ = $proformaData['items'] ?? [];
            $totalCalc = array_sum(array_map(fn($i) => (float)($i['subtotal'] ?? 0), $proformaItems_));
            $totalFb = (float) preg_replace('/[^0-9.]/', '', $proformaData['total_estimado'] ?? '');
            $total = 'S/ ' . number_format($totalCalc > 0 ? $totalCalc : $totalFb, 2);
            $itemsCount = count($proformaItems_);
            $viabilidad = $proformaData['analisis_viabilidad'] ?? '';
            $viabilidadResumen = mb_strlen($viabilidad) > 200
                ? mb_substr($viabilidad, 0, 200) . '...'
                : $viabilidad;

            $mensaje = "📋 <b>Proforma Técnica lista</b>\n\n"
                . "💼 <b>{$companyName}</b>\n"
                . "📦 Ítems: {$itemsCount}\n"
                . "💰 <b>Total estimado: {$total}</b>\n\n"
                . ($viabilidadResumen ? "📊 <i>{$viabilidadResumen}</i>\n\n" : '')
                . "⏱ Los enlaces expiran en 2 horas.";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📄 Descargar Word', 'url' => $wordUrl],
                    ],
                    [
                        ['text' => '🖨 Ver / Imprimir PDF', 'url' => $printUrl],
                    ],
                    [
                        ['text' => '📊 Descargar Excel', 'url' => $excelUrl],
                    ],
                ],
            ];

            $this->enviarMensajeConBotones($chatId, $mensaje, $keyboard, $token);
            $this->info("📋 Proforma generada y enviada a usuario {$chatId} para contrato {$idContrato}");

        } catch (\Exception $e) {
            if ($loadingMsgId) {
                $this->eliminarMensaje($chatId, $loadingMsgId, $token);
            }

            $ref = 'TDR-' . strtoupper(Str::random(6));
            Log::error("TelegramBot:proforma [{$ref}]", [
                'ref' => $ref,
                'chat_id' => $chatId,
                'id_contrato' => $idContrato,
                'exception' => $e->getMessage(),
            ]);
            $this->enviarMensaje($chatId, '❌ ' . TdrAnalysisService::humanizeError($e->getMessage(), $ref), $token);
        }
    }

    protected function sanitizeCallbackFilename(string $nombre): string
    {
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        $base = pathinfo($nombre, PATHINFO_FILENAME);

        $sanitized = str_replace([' ', '/', '\\'], '_', $base);
        $sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '', $sanitized) ?? '';

        if ($sanitized === '') {
            return 'archivo.pdf';
        }

        // Preservar extensión, truncar base para que quepa dentro de 30 chars
        $ext = in_array($ext, ['pdf', 'doc', 'docx', 'zip', 'rar', 'xls', 'xlsx']) ? $ext : 'pdf';
        $maxBase = 30 - strlen($ext) - 1;
        return substr($sanitized, 0, $maxBase) . '.' . $ext;
    }

    protected function buildTelegramUrl(string $token, string $method): string
    {
        if ($this->telegramApiBase === '') {
            throw new RuntimeException('TELEGRAM_API_BASE no está configurada');
        }

        return sprintf('%s/bot%s/%s', $this->telegramApiBase, $token, ltrim($method, '/'));
    }

    /**
     * Enviar acción de chat (typing, upload_document, etc.)
     * Muestra el indicador nativo de "escribiendo..." o "enviando documento..." en Telegram.
     */
    protected function sendChatAction(string $chatId, string $action, string $token): void
    {
        try {
            Http::post($this->buildTelegramUrl($token, 'sendChatAction'), [
                'chat_id' => $chatId,
                'action' => $action,
            ]);
        } catch (\Throwable $e) {
            // No fallar si el chat action no se envía
        }
    }

    /**
     * Enviar mensaje de procesamiento visible y devolver su message_id
     * para poder eliminarlo después si se desea.
     */
    protected function enviarMensajeProcesando(string $chatId, string $texto, string $token): ?int
    {
        try {
            $response = Http::post($this->buildTelegramUrl($token, 'sendMessage'), [
                'chat_id' => $chatId,
                'text' => $texto,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return $response->json()['result']['message_id'] ?? null;
            }
        } catch (\Throwable $e) {
            // No fallar si no se puede enviar el loading
        }

        return null;
    }

    /**
     * Eliminar un mensaje de procesamiento temporal.
     */
    protected function eliminarMensaje(string $chatId, int $messageId, string $token): void
    {
        try {
            Http::post($this->buildTelegramUrl($token, 'deleteMessage'), [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        } catch (\Throwable $e) {
            // No fallar si no se puede eliminar
        }
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('Telegram Listener: ' . $message, $context);
    }
}
