<?php

namespace App\Services;

use App\Contracts\InteractiveChannelContract;
use App\Contracts\NotificationChannelContract;
use App\Models\Contrato;
use App\Models\TelegramSubscription;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService implements NotificationChannelContract, InteractiveChannelContract
{
    protected string $botToken;
    protected string $apiBase;
    protected ?string $chatId;
    protected int $timeout;
    protected bool $enabled;
    protected bool $debugLogging;
    protected int $contratoCacheTtl;
    protected string $contratoCachePrefix = 'telegram:contrato:';

    public function __construct()
    {
        $this->botToken = trim((string) config('services.telegram.bot_token', ''));
        $this->apiBase = rtrim((string) config('services.telegram.api_base', ''), '/');
        $this->chatId = config('services.telegram.chat_id');
        $this->timeout = 10;
        $this->debugLogging = (bool) config('services.telegram.debug_logs', false);
        $this->enabled = $this->botToken !== '' && $this->apiBase !== '';
        $this->contratoCacheTtl = (int) config('services.telegram.contrato_cache_ttl', 720);

        if ($this->botToken !== '' && $this->apiBase === '') {
            Log::warning('Telegram: TELEGRAM_API_BASE no configurado; deshabilitando bot hasta definirlo.');
        }
    }

    // ─── NotificationChannelContract ────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function channelName(): string
    {
        return 'telegram';
    }

    public function enviarAlerta(array $contratoData): array
    {
        $estadisticas = [
            'total' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
            'detalles' => [],
        ];

        $suscripciones = TelegramSubscription::activas()->get();

        if ($suscripciones->isEmpty()) {
            Log::warning('Telegram: No hay suscriptores activos');
            return $estadisticas;
        }

        $estadisticas['total'] = $suscripciones->count();

        $mensaje = $this->construirMensaje($contratoData);
        $keyboard = $this->buildDefaultKeyboard($contratoData);
        $this->cacheContratoContext($contratoData);

        foreach ($suscripciones as $suscripcion) {
            $resultadoFiltro = $suscripcion->resolverCoincidenciasContrato($contratoData);

            if (!$resultadoFiltro['pasa']) {
                continue;
            }

            $mensajePersonalizado = $mensaje;

            if (!empty($resultadoFiltro['keywords'])) {
                $mensajePersonalizado .= "\n\n🔎 Coincidencias: " . implode(', ', $resultadoFiltro['keywords']);
            }

            if (!empty($suscripcion->company_copy)) {
                $mensajePersonalizado .= "\n\n🏢 Perfil: " . $suscripcion->company_copy;
            }

            try {
                $response = $keyboard
                    ? $this->enviarMensajeConBotones($suscripcion->chat_id, $mensajePersonalizado, $keyboard)
                    : $this->enviarMensaje($suscripcion->chat_id, $mensajePersonalizado);

                if ($response['success']) {
                    $estadisticas['exitosos']++;
                    $suscripcion->registrarNotificacion();
                } else {
                    $estadisticas['fallidos']++;
                    $estadisticas['detalles'][] = [
                        'chat_id' => $suscripcion->chat_id,
                        'error' => $response['message'] ?? 'Error desconocido',
                    ];

                    Log::error('Telegram: Error al enviar mensaje', [
                        'chat_id' => $suscripcion->chat_id,
                        'error' => $response['message'] ?? 'Unknown error',
                    ]);
                }
            } catch (Exception $e) {
                $estadisticas['fallidos']++;
                $estadisticas['detalles'][] = [
                    'chat_id' => $suscripcion->chat_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Telegram: Excepción al enviar mensaje', [
                    'chat_id' => $suscripcion->chat_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $this->debug('Broadcast completado', $estadisticas);

        return $estadisticas;
    }

    public function enviarProcesoASuscriptor(object $suscripcion, array $contratoData, array $matchedKeywords = []): array
    {
        $this->cacheContratoContext($contratoData);
        $mensaje = $this->construirMensaje($contratoData);

        if (!empty($matchedKeywords)) {
            $mensaje .= "\n\n🔎 Coincidencias: " . implode(', ', array_unique($matchedKeywords));
        }

        $companyCopy = method_exists($suscripcion, 'getCompanyCopy')
            ? $suscripcion->getCompanyCopy()
            : ($suscripcion->company_copy ?? null);

        if (!empty($companyCopy)) {
            $mensaje .= "\n\n🏢 Perfil: " . $companyCopy;
        }

        $recipientId = method_exists($suscripcion, 'getRecipientId')
            ? $suscripcion->getRecipientId()
            : $suscripcion->chat_id;

        $keyboard = $this->buildDefaultKeyboard($contratoData);
        $resultado = $keyboard
            ? $this->enviarMensajeConBotones($recipientId, $mensaje, $keyboard)
            : $this->enviarMensaje($recipientId, $mensaje);

        if ($resultado['success']) {
            $suscripcion->registrarNotificacion();
        }

        return $resultado;
    }

    protected function construirMensaje(array $contratoData): string
    {
        $mensaje = "🔔 <b>NUEVO PROCESO SEACE</b>\n\n";
        $mensaje .= "🏢 <b>Entidad:</b> " . ($contratoData['nomEntidad'] ?? 'N/A') . "\n";
        $mensaje .= "📝 <b>Código:</b> " . ($contratoData['desContratacion'] ?? 'N/A') . "\n";
        $mensaje .= "🎯 <b>Objeto:</b> " . ($contratoData['nomObjetoContrato'] ?? 'N/A') . "\n";

        $descripcion = $contratoData['desObjetoContrato'] ?? 'N/A';
        if (strlen($descripcion) > 200) {
            $descripcion = substr($descripcion, 0, 200) . '...';
        }
        $mensaje .= "📋 <b>Descripción:</b> {$descripcion}\n\n";

        $mensaje .= "📅 <b>Publicado:</b> " . ($contratoData['fecPublica'] ?? 'N/A') . "\n";
        $mensaje .= "⏰ <b>Inicio Cotización:</b> " . ($contratoData['fecIniCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "🕐 <b>Fin Cotización:</b> " . ($contratoData['fecFinCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "💼 <b>Estado:</b> " . ($contratoData['nomEstadoContrato'] ?? 'N/A') . "\n";
        $mensaje .= "📍 <b>Etapa:</b> " . ($contratoData['nomEtapaContratacion'] ?? 'N/A');

        return $mensaje;
    }

    public function enviarMensaje(string $chatId, string $mensaje): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'Telegram Bot está deshabilitado en la configuración',
            ];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->buildApiUrl('sendMessage'), [
                    'chat_id' => $chatId,
                    'text' => $mensaje,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Mensaje enviado exitosamente',
                ];
            }

            $error = $response->json()['description'] ?? 'Error desconocido';

            return [
                'success' => false,
                'message' => $error,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }
    }

    public function enviarMensajeConBotones(string $chatId, string $mensaje, array $keyboard): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'Telegram Bot está deshabilitado en la configuración',
            ];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->buildApiUrl('sendMessage'), [
                    'chat_id' => $chatId,
                    'text' => $mensaje,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode($keyboard),
                ]);

            $success = $response->successful() && ($response->json()['ok'] ?? false);
            $error = $response->json()['description'] ?? ($response->body() ?: 'Error desconocido');

            if (!$success) {
                Log::error('Telegram: Error al enviar mensaje con botones', [
                    'chat_id' => $chatId,
                    'error' => $error,
                ]);
            }

            return [
                'success' => $success,
                'message' => $success ? 'Mensaje con botones enviado exitosamente' : $error,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }
    }

    protected function buildCallbackData(string $action, int $idContrato, int $idContratoArchivo, string $nombreArchivo): string
    {
        $nombre = $this->sanitizeCallbackFilename($nombreArchivo);
        return sprintf('%s_%d_%d_%s', $action, $idContrato, $idContratoArchivo, $nombre);
    }

    public function buildDefaultKeyboard(array $contratoData): ?array
    {
        $idContrato = (int) ($contratoData['idContrato'] ?? 0);

        if ($idContrato <= 0) {
            return null;
        }

        $idContratoArchivo = (int) ($contratoData['idContratoArchivo'] ?? 0);
        $nombreArchivo = $contratoData['nombreArchivo'] ?? 'tdr.pdf';

        // Si no hay archivo válido, no incluir botones que dependen del archivo
        if ($idContratoArchivo <= 0) {
            Log::debug('Telegram: omitiendo botones de archivo (idContratoArchivo=0)', [
                'idContrato' => $idContrato,
            ]);
            return null;
        }

        return [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🤖 Analizar con IA',
                        'callback_data' => $this->buildCallbackData('analizar', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                ],
                [
                    [
                        'text' => '🏅 Ver compatibilidad',
                        'callback_data' => $this->buildCallbackData('compatibilidad', $idContrato, $idContratoArchivo, $nombreArchivo),
                    ],
                ],
            ],
        ];
    }

    public function cacheContratoContext(array $contratoData): void
    {
        $idContrato = (int) ($contratoData['idContrato'] ?? 0);

        if ($idContrato <= 0) {
            return;
        }

        $payload = [
            'idContrato' => $idContrato,
            'desContratacion' => $contratoData['desContratacion'] ?? null,
            'nomEntidad' => $contratoData['nomEntidad'] ?? null,
            'nomObjetoContrato' => $contratoData['nomObjetoContrato'] ?? null,
            'desObjetoContrato' => $contratoData['desObjetoContrato'] ?? null,
            'nomEstadoContrato' => $contratoData['nomEstadoContrato'] ?? null,
            'nomEtapaContratacion' => $contratoData['nomEtapaContratacion'] ?? null,
            'fecPublica' => $contratoData['fecPublica'] ?? null,
            'fecIniCotizacion' => $contratoData['fecIniCotizacion'] ?? null,
            'fecFinCotizacion' => $contratoData['fecFinCotizacion'] ?? null,
            'idContratoArchivo' => $contratoData['idContratoArchivo'] ?? null,
            'nombreArchivo' => $contratoData['nombreArchivo'] ?? null,
        ];

        Cache::put(
            $this->buildContratoCacheKey($idContrato),
            $payload,
            now()->addMinutes(max(1, $this->contratoCacheTtl))
        );
    }

    protected function buildContratoCacheKey(int $idContrato): string
    {
        return $this->contratoCachePrefix . $idContrato;
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

    public function notifyNewContract(Contrato $contrato, ?array $archivo = null): bool
    {
        $contratoData = [
            'idContrato' => $contrato->id_contrato_seace,
            'nomEntidad' => $contrato->entidad,
            'desContratacion' => $contrato->codigo_proceso,
            'nomObjetoContrato' => $contrato->objeto,
            'desObjetoContrato' => $contrato->descripcion,
            'fecPublica' => $contrato->fecha_publicacion->format('d/m/Y H:i:s'),
            'fecIniCotizacion' => $contrato->inicio_cotizacion?->format('d/m/Y H:i:s'),
            'fecFinCotizacion' => $contrato->fin_cotizacion?->format('d/m/Y H:i:s'),
            'nomEstadoContrato' => $contrato->estado,
            'nomEtapaContratacion' => $contrato->etapa_contratacion,
            'idContratoArchivo' => $archivo['idContratoArchivo'] ?? 0,
            'nombreArchivo' => $archivo['nomArchivo'] ?? 'archivo.pdf',
        ];

        $resultado = $this->enviarAlerta($contratoData);

        return $resultado['exitosos'] > 0;
    }

    public function sendMessage(string $message, ?string $chatId = null): array
    {
        $targetChatId = $chatId ?? $this->chatId;

        if (empty($targetChatId)) {
            return [
                'success' => false,
                'message' => 'No se especificó un Chat ID',
            ];
        }

        return $this->enviarMensaje($targetChatId, $message);
    }

    public function notifyExpiringContracts(array $contratos): bool
    {
        if (empty($contratos) || !$this->enabled) {
            return false;
        }

        try {
            $message = "⏰ <b>CONTRATOS PRÓXIMOS A VENCER</b>\n\n";

            foreach ($contratos as $contrato) {
                $diasRestantes = $contrato->dias_restantes;
                $urgencia = $diasRestantes <= 2 ? '🔴' : '🟡';

                $message .= "{$urgencia} <b>{$contrato->codigo_proceso}</b>\n";
                $message .= "   📅 Vence: {$contrato->fin_cotizacion->format('d/m/Y H:i')}\n";
                $message .= "   ⏱️ Quedan: {$diasRestantes} días\n";
                $message .= "   🏢 {$contrato->entidad}\n\n";
            }

            $message .= "🤖 <i>Vigilante SEACE</i>";

            TelegramSubscription::activas()->each(function ($suscripcion) use ($message) {
                $this->enviarMensaje($suscripcion->chat_id, $message);
            });

            return true;
        } catch (Exception $e) {
            Log::error('Telegram: Error al enviar notificación de vencimientos', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function formatContratoMessage(Contrato $contrato): string
    {
        $emoji = $this->getEmojiByObjeto($contrato->objeto);

        $message = "{$emoji} <b>NUEVO CONTRATO DETECTADO</b>\n\n";
        $message .= "📋 <b>Código:</b> {$contrato->codigo_proceso}\n";
        $message .= "🏢 <b>Entidad:</b> {$contrato->entidad}\n";
        $message .= "📦 <b>Objeto:</b> {$contrato->objeto}\n";
        $message .= "📝 <b>Descripción:</b>\n" . mb_substr($contrato->descripcion, 0, 200) . "...\n\n";
        $message .= "📅 <b>Publicación:</b> {$contrato->fecha_publicacion->format('d/m/Y H:i')}\n";

        if ($contrato->inicio_cotizacion) {
            $message .= "🟢 <b>Inicio Cotización:</b> {$contrato->inicio_cotizacion->format('d/m/Y H:i')}\n";
        }

        if ($contrato->fin_cotizacion) {
            $diasRestantes = $contrato->dias_restantes;
            $urgencia = $diasRestantes <= 2 ? '🔴' : ($diasRestantes <= 5 ? '🟡' : '🟢');
            $message .= "{$urgencia} <b>Fin Cotización:</b> {$contrato->fin_cotizacion->format('d/m/Y H:i')} (";
            $message .= "{$diasRestantes} días restantes)\n";
        }

        $message .= "\n🔗 <b>Estado:</b> {$contrato->estado}\n";
        $message .= "\n🤖 <i>Vigilante SEACE</i>";

        return $message;
    }

    protected function getEmojiByObjeto(string $objeto): string
    {
        return match (strtolower($objeto)) {
            'bien' => '📦',
            'servicio' => '🛠️',
            'obra' => '🏗️',
            'consultoría de obra' => '📐',
            default => '📄',
        };
    }

    protected function buildApiUrl(string $method): string
    {
        return sprintf('%s/bot%s/%s', $this->apiBase, $this->botToken, ltrim($method, '/'));
    }

    // ─── InteractiveChannelContract ─────────────────────────────────

    /**
     * Enviar documento binario al chat de Telegram (sendDocument).
     */
    public function enviarDocumento(string $recipientId, string $documentBinary, string $filename, string $caption = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Telegram Bot está deshabilitado'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->attach('document', $documentBinary, $filename)
                ->post($this->buildApiUrl('sendDocument'), [
                    'chat_id' => $recipientId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);

            $success = $response->successful() && ($response->json()['ok'] ?? false);

            return [
                'success' => $success,
                'message' => $success ? 'Documento enviado' : ($response->json()['description'] ?? 'Error desconocido'),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
        }
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('Telegram: ' . $message, $context);
    }
}
