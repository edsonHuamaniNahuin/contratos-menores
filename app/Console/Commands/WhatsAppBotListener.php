<?php

namespace App\Console\Commands;

use App\Models\CuentaSeace;
use App\Models\SubscriptionContractMatch;
use App\Models\WhatsAppSubscription;
use App\Services\AccountCompatibilityService;
use App\Services\Tdr\CompatibilityScoreService;
use App\Services\Tdr\PublicTdrDocumentService;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use App\Services\TdrAnalysisFormatter;
use App\Services\TdrAnalysisService;
use App\Services\SeacePublicArchivoService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Command\SignalableCommandInterface;

/**
 * Listener de webhooks de WhatsApp Business Cloud API.
 *
 * Procesa clicks en los botones interactivos (reply buttons) de los
 * mensajes enviados por WhatsAppNotificationService.
 *
 * WhatsApp Cloud API usa webhooks (no long-polling como Telegram).
 * Este comando puede funcionar en 2 modos:
 *
 * 1. Webhook (producción): Las peticiones llegan via HTTP al endpoint /api/webhooks/whatsapp
 *    y se procesan en el controller. Este comando no es necesario en ese modo.
 *
 * 2. Polling local (desarrollo): Simula un listener para mensajes entrantes
 *    que revisa un endpoint temporal. Para desarrollo/testing.
 *
 * En producción se recomienda usar el webhook en el controller.
 * Este comando procesa los callback de botones cuando los recibe vía webhook.
 */
class WhatsAppBotListener extends Command implements SignalableCommandInterface, Isolatable
{
    protected $signature = 'whatsapp:listen {--once : Procesar solo una vez}';
    protected $description = 'Procesar webhooks de WhatsApp Business API (callbacks de botones interactivos)';

    private const OFFSET_CACHE_KEY = 'whatsapp:listener:last_processed';
    private const OFFSET_CACHE_TTL = 2592000; // 30 días

    protected bool $shouldStop = false;
    protected bool $debugLogging;
    protected string $contratoCachePrefix = 'whatsapp:contrato:';
    protected TdrAnalysisFormatter $formatter;
    protected CompatibilityScoreService $compatibilityService;
    protected AccountCompatibilityService $compatibilityRepository;
    protected WhatsAppNotificationService $whatsapp;

    public function __construct()
    {
        parent::__construct();
        $this->debugLogging = (bool) config('services.whatsapp.debug_logs', false);
        $this->formatter = new TdrAnalysisFormatter();
        $this->compatibilityService = app(CompatibilityScoreService::class);
        $this->compatibilityRepository = app(AccountCompatibilityService::class);
        $this->whatsapp = app(WhatsAppNotificationService::class);
    }

    /**
     * Override info() para ser null-safe cuando se instancia fuera de Artisan.
     */
    public function info($string, $verbosity = null)
    {
        if ($this->output) {
            parent::info($string, $verbosity);
        } else {
            Log::info('WhatsApp Listener: ' . $string);
        }
    }

    /**
     * Override error() para ser null-safe cuando se instancia fuera de Artisan.
     */
    public function error($string, $verbosity = null)
    {
        if ($this->output) {
            parent::error($string, $verbosity);
        } else {
            Log::error('WhatsApp Listener: ' . $string);
        }
    }

    public function handle(): int
    {
        $token = config('services.whatsapp.token');

        if (empty($token)) {
            $this->error('Token de WhatsApp no configurado (WHATSAPP_TOKEN)');
            return Command::FAILURE;
        }

        if (!$this->whatsapp->isEnabled()) {
            $this->error('WhatsApp no está habilitado. Verifica WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID en .env');
            return Command::FAILURE;
        }

        $this->info('📱 WhatsApp Bot Listener iniciado — PID ' . getmypid());
        $this->info('📡 Esperando mensajes entrantes...');
        $this->info('🛑 Presiona Ctrl+C para detener');
        $this->info('');
        $this->info('💡 En producción, los webhooks llegan vía HTTP a /api/webhooks/whatsapp');
        $this->info('   Este comando procesa la cola local de mensajes pendientes.');

        do {
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($this->shouldStop) {
                break;
            }

            try {
                $this->processQueuedMessages();

                if (!$this->option('once') && !$this->shouldStop) {
                    usleep(500_000);
                }
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                Log::error('WhatsApp Bot Listener Error', ['exception' => $e->getMessage()]);

                if (!$this->option('once') && !$this->shouldStop) {
                    sleep(3);
                }
            }
        } while (!$this->shouldStop && !$this->option('once'));

        $this->info('👋 WhatsApp Listener detenido (PID ' . getmypid() . ')');

        return Command::SUCCESS;
    }

    public function isolatableId(): string
    {
        return 'whatsapp-bot-listener';
    }

    public function getSubscribedSignals(): array
    {
        return defined('SIGINT') ? [SIGINT, SIGTERM] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->shouldStop = true;
        $this->info("\n🛑 Señal {$signal} recibida, deteniendo listener...");
        return 0;
    }

    // ─── Procesamiento de mensajes ──────────────────────────────────

    /**
     * Procesar mensajes encolados vía webhook.
     *
     * Los webhooks de Meta llegan al controller WebhookWhatsAppController
     * que los encola en cache bajo la key 'whatsapp:incoming_messages'.
     */
    protected function processQueuedMessages(): void
    {
        $messages = Cache::pull('whatsapp:incoming_messages', []);

        if (empty($messages)) {
            return;
        }

        foreach ($messages as $message) {
            if ($this->shouldStop) {
                // Re-encolar mensajes no procesados
                $remaining = array_slice($messages, array_search($message, $messages));
                Cache::put('whatsapp:incoming_messages', $remaining, now()->addHours(24));
                break;
            }

            $this->processIncomingMessage($message);
        }
    }

    /**
     * Procesar un mensaje entrante de WhatsApp.
     *
     * Estructura del webhook de Meta Cloud API:
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "changes": [{
     *       "value": {
     *         "messages": [{
     *           "from": "51987654321",
     *           "type": "interactive",
     *           "interactive": {
     *             "type": "button_reply",
     *             "button_reply": {
     *               "id": "analizar_12345_678_tdr.pdf",
     *               "title": "🤖 Analizar TDR"
     *             }
     *           }
     *         }]
     *       }
     *     }]
     *   }]
     * }
     */
    public function processIncomingMessage(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $message) {
                    $this->handleMessage($message);
                }
            }
        }
    }

    /**
     * Manejar un mensaje individual.
     */
    protected function handleMessage(array $message): void
    {
        $from = $message['from'] ?? '';
        $type = $message['type'] ?? '';

        // Solo procesar clicks en botones interactivos
        if ($type === 'interactive') {
            $interactive = $message['interactive'] ?? [];
            $buttonReply = $interactive['button_reply'] ?? [];
            $callbackData = $buttonReply['id'] ?? '';

            if (!empty($callbackData)) {
                $this->handleButtonClick($from, $callbackData);
            }
        }
    }

    /**
     * Procesar click en botón interactivo.
     * Misma lógica que TelegramBotListener::handleCallbackQuery().
     */
    protected function handleButtonClick(string $phoneNumber, string $data): void
    {
        // Dedup atómico: evita procesamiento duplicado por webhook retry
        $dedupKey = "whatsapp:cb:" . md5($phoneNumber . '|' . $data . '|' . time());
        if (!Cache::add($dedupKey, true, 300)) {
            $this->debug("Callback ya procesado, ignorando (dedup)", [
                'phone' => $phoneNumber,
                'data' => $data,
            ]);
            return;
        }

        // Lock anti doble-click
        $actionLockKey = 'whatsapp:action:' . md5($phoneNumber . '|' . $data);
        $actionLock = Cache::lock($actionLockKey, 25);

        if (!$actionLock->get()) {
            $this->whatsapp->enviarMensaje($phoneNumber, '⏳ Ya estamos procesando esta solicitud...');
            return;
        }

        try {
            $this->debug('Button click recibido', [
                'phone' => $phoneNumber,
                'data' => $data,
            ]);

            if (str_starts_with($data, 'analizar_')) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                $this->info("🔍 Usuario {$phoneNumber} solicitó análisis del contrato {$idContrato}");
                $this->whatsapp->enviarMensaje($phoneNumber, '⏳ Analizando proceso con IA...');
                $this->analizarProcesoParaUsuario($phoneNumber, $idContrato, $idContratoArchivo, $nombreArchivo);

            } elseif (str_starts_with($data, 'descargar_')) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';

                $this->info("📥 Usuario {$phoneNumber} solicitó descarga del contrato {$idContrato}");
                $this->whatsapp->enviarMensaje($phoneNumber, '📥 Preparando descarga...');
                $this->descargarArchivoParaUsuario($phoneNumber, $idContrato, $idContratoArchivo, $nombreArchivo);

            } elseif (str_starts_with($data, 'compatibilidad_') || str_starts_with($data, 'compatrefresh_')) {
                $parts = explode('_', $data, 4);
                $idContrato = (int) ($parts[1] ?? 0);
                $idContratoArchivo = (int) ($parts[2] ?? 0);
                $nombreArchivo = $parts[3] ?? 'archivo.pdf';
                $forceRefresh = str_starts_with($data, 'compatrefresh_');

                $this->info("🏅 Usuario {$phoneNumber} solicitó compatibilidad del contrato {$idContrato}");
                $this->whatsapp->enviarMensaje($phoneNumber, $forceRefresh ? '🔄 Recalculando score...' : '⏱️ Calculando score...');
                $this->evaluarCompatibilidadParaUsuario($phoneNumber, $idContrato, $idContratoArchivo, $nombreArchivo, $forceRefresh);

            } else {
                $this->whatsapp->enviarMensaje($phoneNumber, '❌ Acción no reconocida');
            }
        } finally {
            $actionLock->release();
        }
    }

    // ─── Análisis TDR ───────────────────────────────────────────────

    protected function analizarProcesoParaUsuario(
        string $phoneNumber,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo
    ): void {
        try {
            $cuenta = CuentaSeace::activa()->first();

            if ($idContratoArchivo === 0) {
                $this->whatsapp->enviarMensaje($phoneNumber, '❌ ID de archivo inválido.');
                return;
            }

            $tdrService = new TdrAnalysisService();

            // TdrAnalysisService ya tiene Cache::lock atómico interno —
            // si otro usuario pide el mismo análisis, espera y reutiliza.
            $resultado = $tdrService->analizarDesdeSeace(
                $idContratoArchivo,
                $nombreArchivo,
                $cuenta,
                ['idContrato' => $idContrato],
                'whatsapp'
            );

            if ($resultado['success'] ?? false) {
                $this->enviarResultadoAnalisis($phoneNumber, $resultado, $idContrato, $idContratoArchivo, $nombreArchivo);
                $this->info("✅ Análisis enviado a {$phoneNumber}");
            } else {
                $errorMsg = $resultado['error'] ?? 'Error desconocido';
                $this->enviarMensajeErrorConReintento($phoneNumber, $errorMsg, 'analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Error al analizar proceso', [
                'phone' => $phoneNumber,
                'id_contrato' => $idContrato,
                'exception' => $e->getMessage(),
            ]);
            $this->enviarMensajeErrorConReintento($phoneNumber, $e->getMessage(), 'analizar', $idContrato, $idContratoArchivo, $nombreArchivo);
        }
    }

    protected function enviarResultadoAnalisis(
        string $phoneNumber,
        array $resultado,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo
    ): void {
        $analisisData = $resultado['data']['analisis'] ?? [];
        $archivoNombre = $resultado['data']['archivo'] ?? $nombreArchivo;
        $contextoContrato = $resultado['data']['contexto_contrato'] ?? null;

        // Formatear para WhatsApp (mismo formatter, luego convertimos HTML → WA)
        $mensaje = $this->formatter->formatForTelegram($analisisData, $archivoNombre, $contextoContrato);
        $mensaje = $this->htmlToWhatsApp($mensaje);

        // Botón de descarga después del análisis
        $keyboard = [
            'type' => 'button',
            'body' => ['text' => $mensaje],
            'footer' => ['text' => '🤖 Vigilante SEACE'],
            'action' => [
                'buttons' => [
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '📥 Descargar TDR',
                        ],
                    ],
                ],
            ],
        ];

        $this->whatsapp->enviarMensajeConBotones($phoneNumber, $mensaje, $keyboard);
    }

    // ─── Descarga TDR ───────────────────────────────────────────────

    protected function descargarArchivoParaUsuario(
        string $phoneNumber,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo
    ): void {
        try {
            $cuenta = CuentaSeace::activa()->first();
            $persistence = new TdrPersistenceService();

            $archivoPersistido = $persistence->resolveArchivo(
                $idContratoArchivo, $nombreArchivo, $idContrato,
                ['idContrato' => $idContrato]
            );

            if (!$archivoPersistido->hasStoredFile()) {
                if ($cuenta) {
                    try {
                        $documentService = new TdrDocumentService($persistence);
                        $documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);
                    } catch (\Throwable $authException) {
                        Log::warning('WhatsApp: Descarga autenticada falló, intentando endpoint público', [
                            'phone' => $phoneNumber,
                            'id_archivo' => $idContratoArchivo,
                            'error' => $authException->getMessage(),
                        ]);

                        $publicService = new PublicTdrDocumentService(
                            $persistence,
                            new SeacePublicArchivoService()
                        );
                        $publicService->ensureLocalArchivo(
                            $idContrato,
                            ['idContratoArchivo' => $idContratoArchivo, 'nombre' => $nombreArchivo],
                            ['idContrato' => $idContrato]
                        );
                    }
                } else {
                    $publicService = new PublicTdrDocumentService(
                        $persistence,
                        new SeacePublicArchivoService()
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
                $this->whatsapp->enviarMensaje($phoneNumber, '❌ No fue posible almacenar el archivo en caché');
                return;
            }

            $disk = Storage::disk($archivoPersistido->storage_disk ?? config('filesystems.default'));
            $documentBinary = $disk->get($archivoPersistido->storage_path);

            $resultado = $this->whatsapp->enviarDocumento(
                $phoneNumber,
                $documentBinary,
                $nombreArchivo,
                "📄 {$nombreArchivo}\n\n✅ Enviado desde Vigilante SEACE"
            );

            if ($resultado['success']) {
                $this->info("✅ Archivo enviado a {$phoneNumber}");
            } else {
                $this->whatsapp->enviarMensaje($phoneNumber, '❌ Error al enviar archivo: ' . ($resultado['message'] ?? ''));
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp: Error al descargar archivo', [
                'phone' => $phoneNumber,
                'id_archivo' => $idContratoArchivo,
                'error' => $e->getMessage(),
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

                    $resultado = $this->whatsapp->enviarDocumento(
                        $phoneNumber,
                        $documentBinary,
                        $nombreArchivo,
                        "📄 {$nombreArchivo}\n\n✅ Enviado desde caché local"
                    );

                    if ($resultado['success']) {
                        $this->info("✅ Fallback desde caché enviado a {$phoneNumber}");
                        return;
                    }
                }
            } catch (\Throwable $fallbackException) {
                Log::warning('WhatsApp: Fallback caché descarga falló', [
                    'phone' => $phoneNumber,
                    'id_archivo' => $idContratoArchivo,
                    'error' => $fallbackException->getMessage(),
                ]);
            }

            $this->whatsapp->enviarMensaje($phoneNumber, '❌ No se pudo descargar el archivo. Intenta nuevamente.');
        }
    }

    // ─── Compatibilidad IA ──────────────────────────────────────────

    protected function evaluarCompatibilidadParaUsuario(
        string $phoneNumber,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo,
        bool $forceRefresh = false
    ): void {
        if ($idContrato <= 0 || $idContratoArchivo <= 0) {
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ No se pudo identificar el proceso o su TDR.');
            return;
        }

        $subscription = WhatsAppSubscription::where('phone_number', $phoneNumber)->first();

        if (!$subscription) {
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ No encontramos una suscripción activa para este número.');
            return;
        }

        if (blank($subscription->company_copy)) {
            $this->whatsapp->enviarMensaje($phoneNumber, '✍️ Configura el copy de tu empresa en el panel web antes de solicitar el score.');
            return;
        }

        $cachedContrato = Cache::get($this->contratoCachePrefix . $idContrato);

        // Usar repository polimórfico (mismo patrón que Telegram)
        $existingMatch = $this->compatibilityRepository->findMatch($subscription, $idContrato);

        if (!$forceRefresh && $this->compatibilityRepository->canReuseMatch($existingMatch, $subscription)) {
            $this->enviarMensajeCompatibilidad($phoneNumber, $existingMatch, true, $idContrato, $idContratoArchivo, $nombreArchivo);
            return;
        }

        // Obtener análisis IA
        $tdrService = new TdrAnalysisService();
        $cuenta = CuentaSeace::activa()->first();
        $contextoContrato = array_merge(['idContrato' => $idContrato], $cachedContrato ?? []);

        $analisis = $tdrService->analizarDesdeSeace(
            $idContratoArchivo,
            $nombreArchivo,
            $cuenta,
            $contextoContrato,
            'dashboard'
        );

        if (!($analisis['success'] ?? false)) {
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ ' . ($analisis['error'] ?? 'No se pudo analizar el TDR.'));
            return;
        }

        $payload = $analisis['data'] ?? [];
        $contratoSnapshot = $this->resolveContratoSnapshot($idContrato, $payload, $existingMatch, $cachedContrato);

        try {
            $compatResult = $this->compatibilityService->ensureScore(
                $subscription,
                $contratoSnapshot,
                $payload,
                $forceRefresh
            );
        } catch (\Throwable $e) {
            Log::error('WhatsApp Compatibilidad IA: excepción', [
                'phone' => $phoneNumber,
                'contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ Error al evaluar compatibilidad: ' . $e->getMessage());
            return;
        }

        if (!empty($compatResult['error'])) {
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ ' . $compatResult['error']);
            return;
        }

        $match = $compatResult['match'] ?? null;

        if (!$match) {
            $this->whatsapp->enviarMensaje($phoneNumber, '❌ No se pudo registrar el puntaje.');
            return;
        }

        $this->enviarMensajeCompatibilidad(
            $phoneNumber,
            $match,
            $compatResult['from_cache'] ?? false,
            $idContrato,
            $idContratoArchivo,
            $nombreArchivo
        );
    }

    protected function enviarMensajeCompatibilidad(
        string $phoneNumber,
        SubscriptionContractMatch $match,
        bool $fromCache,
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

        $mensaje = "🏅 *Compatibilidad IA*\n\n";
        $mensaje .= "📊 *Puntaje:* {$score}/100\n";
        $mensaje .= "🎯 *Nivel:* {$nivel}\n";

        if ($timestamp) {
            $mensaje .= "🕒 *Evaluado:* {$timestamp}\n";
        }

        $mensaje .= "\n📝 *Código:* " . ($match->contrato_codigo ?? 'N/A') . "\n";
        $mensaje .= "🏢 *Entidad:* " . ($match->contrato_entidad ?? 'N/A') . "\n";

        if ($match->contrato_objeto) {
            $mensaje .= "🎯 *Objeto:* {$match->contrato_objeto}\n";
        }

        $mensaje .= "\n🧠 *Explicación:* {$explicacion}\n";

        if ($fromCache) {
            $mensaje .= "\n♻️ Resultado desde caché para tu copy actual.";
        }

        $mensaje .= "\n🤖 _Vigilante SEACE_";

        $keyboard = [
            'type' => 'button',
            'body' => ['text' => $mensaje],
            'footer' => ['text' => '🤖 Vigilante SEACE'],
            'action' => [
                'buttons' => [
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '📥 Descargar TDR',
                        ],
                    ],
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '🤖 Analizar TDR',
                        ],
                    ],
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $this->buildCallbackData('compatrefresh', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '🔄 Recalcular',
                        ],
                    ],
                ],
            ],
        ];

        $this->whatsapp->enviarMensajeConBotones($phoneNumber, $mensaje, $keyboard);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    protected function resolveContratoSnapshot(
        int $idContrato,
        array $analysisPayload,
        ?SubscriptionContractMatch $existingMatch = null,
        ?array $contratoCache = null
    ): array {
        $contexto = $analysisPayload['contexto_contrato'] ?? [];
        $cacheData = $contratoCache ?? [];

        return [
            'idContrato' => $idContrato,
            'desContratacion' => $cacheData['desContratacion'] ?? $contexto['codigo_proceso'] ?? $existingMatch?->contrato_codigo,
            'nomEntidad' => $cacheData['nomEntidad'] ?? $contexto['entidad'] ?? $existingMatch?->contrato_entidad,
            'nomObjetoContrato' => $cacheData['nomObjetoContrato'] ?? $contexto['objeto'] ?? $existingMatch?->contrato_objeto,
            'desObjetoContrato' => $cacheData['desObjetoContrato'] ?? $contexto['descripcion'] ?? null,
            'nomEstadoContrato' => $cacheData['nomEstadoContrato'] ?? $contexto['estado'] ?? null,
            'fecPublica' => $cacheData['fecPublica'] ?? $contexto['fecha_publicacion'] ?? null,
            'fecFinCotizacion' => $cacheData['fecFinCotizacion'] ?? $contexto['fecha_cierre'] ?? null,
        ];
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
        return substr($sanitized ?: 'archivo.pdf', 0, 30);
    }

    protected function htmlToWhatsApp(string $html): string
    {
        $text = str_replace(['<b>', '</b>'], '*', $html);
        $text = str_replace(['<i>', '</i>'], '_', $text);
        $text = str_replace(['<code>', '</code>'], '```', $text);
        return strip_tags($text);
    }

    /**
     * Enviar mensaje de error con botón de reintentar (si es error temporal).
     *
     * Misma lógica que TelegramBotListener: detecta errores temporales
     * y ofrece un botón de reintento en vez de solo texto.
     */
    protected function enviarMensajeErrorConReintento(
        string $phoneNumber,
        string $errorMsg,
        string $action,
        int $idContrato,
        int $idContratoArchivo,
        string $nombreArchivo
    ): void {
        $esErrorTemporal = str_contains($errorMsg, 'temporalmente')
            || str_contains($errorMsg, 'intenta')
            || str_contains($errorMsg, 'HTTP 500')
            || str_contains($errorMsg, 'saturado');

        if ($esErrorTemporal) {
            $keyboard = [
                'type' => 'button',
                'body' => ['text' => "❌ {$errorMsg}"],
                'footer' => ['text' => '🤖 Vigilante SEACE'],
                'action' => [
                    'buttons' => [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => $this->buildCallbackData($action, $idContrato, $idContratoArchivo, $nombreArchivo),
                                'title' => '🔄 Reintentar',
                            ],
                        ],
                    ],
                ],
            ];
            $this->whatsapp->enviarMensajeConBotones($phoneNumber, "❌ {$errorMsg}", $keyboard);
        } else {
            $this->whatsapp->enviarMensaje($phoneNumber, "❌ Error: {$errorMsg}");
        }
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }
        Log::debug('WhatsApp Listener: ' . $message, $context);
    }
}
