<?php

namespace App\Console\Commands;

use App\Models\CuentaSeace;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use App\Services\TdrAnalysisFormatter;
use App\Services\TdrAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramBotListener extends Command
{
    protected $signature = 'telegram:listen {--once : Procesar solo una vez}';
    protected $description = 'Escuchar actualizaciones de Telegram (polling) y procesar clicks de botones';

    protected int $lastUpdateId = 0;
    protected string $baseUrl;
    protected TdrAnalysisFormatter $formatter;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('services.seace.base_url');
        $this->formatter = new TdrAnalysisFormatter();
    }

    public function handle()
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            $this->error('Token de Telegram no configurado');
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
        $response = Http::timeout(30)->get("https://api.telegram.org/bot{$token}/getUpdates", [
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

        Log::info('Telegram: Callback Query recibido', [
            'chat_id' => $chatId,
            'data' => $data
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
        } else {
            $this->answerCallbackQuery($callbackId, '❌ Acción no reconocida', $token);
        }
    }

    /**
     * Responder al callback query
     */
    protected function answerCallbackQuery(string $callbackQueryId, string $text, string $token): void
    {
        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
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
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
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
        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
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
            )->post("https://api.telegram.org/bot{$token}/sendDocument", [
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
}
