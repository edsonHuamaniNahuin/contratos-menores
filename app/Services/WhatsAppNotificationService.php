<?php

namespace App\Services;

use App\Contracts\InteractiveChannelContract;
use App\Contracts\NotificationChannelContract;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de notificaciones de procesos SEACE vía WhatsApp Business Cloud API.
 *
 * Implementa las mismas interfaces que TelegramNotificationService (SOLID – OCP/LSP),
 * usando la API oficial de Meta (Cloud API) para enviar mensajes interactivos.
 *
 * Requisitos:
 * - Cuenta Meta Business verificada
 * - Aplicación WhatsApp Business en Meta Developers
 * - Token de acceso permanente (System User Token)
 * - Número de teléfono registrado en el panel de Meta
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class WhatsAppNotificationService implements NotificationChannelContract, InteractiveChannelContract
{
    protected string $token;
    protected string $phoneNumberId;
    protected string $apiVersion;
    protected string $baseUrl;
    protected int $timeout;
    protected bool $enabled;
    protected bool $debugLogging;
    protected int $contratoCacheTtl;
    protected string $contratoCachePrefix = 'whatsapp:contrato:';

    public function __construct()
    {
        $this->token = trim((string) config('services.whatsapp.token', ''));
        $this->phoneNumberId = trim((string) config('services.whatsapp.phone_number_id', ''));
        $this->apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = 'https://graph.facebook.com';
        $this->timeout = (int) config('services.whatsapp.timeout', 15);
        $this->debugLogging = (bool) config('services.whatsapp.debug_logs', false);
        $this->contratoCacheTtl = (int) config('services.whatsapp.contrato_cache_ttl', 720);

        $this->enabled = $this->token !== '' && $this->phoneNumberId !== '';

        if ($this->token !== '' && $this->phoneNumberId === '') {
            Log::warning('WhatsApp: WHATSAPP_PHONE_NUMBER_ID no configurado; deshabilitando servicio.');
        }
    }

    // ─── NotificationChannelContract ──────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function channelName(): string
    {
        return 'whatsapp';
    }

    /**
     * Enviar notificación de un proceso a un suscriptor de WhatsApp.
     */
    public function enviarProcesoASuscriptor(object $suscripcion, array $contratoData, array $matchedKeywords = []): array
    {
        $this->cacheContratoContext($contratoData);
        $mensaje = $this->construirMensaje($contratoData);

        if (!empty($matchedKeywords)) {
            $mensaje .= "\n\n🔎 *Coincidencias:* " . implode(', ', array_unique($matchedKeywords));
        }

        $companyCopy = method_exists($suscripcion, 'getCompanyCopy')
            ? $suscripcion->getCompanyCopy()
            : ($suscripcion->company_copy ?? null);

        if (!empty($companyCopy)) {
            $mensaje .= "\n\n🏢 *Perfil:* " . $companyCopy;
        }

        $recipientId = method_exists($suscripcion, 'getRecipientId')
            ? $suscripcion->getRecipientId()
            : $suscripcion->phone_number;

        $keyboard = $this->buildDefaultKeyboard($contratoData);

        $resultado = $keyboard
            ? $this->enviarMensajeConBotones($recipientId, $mensaje, $keyboard)
            : $this->enviarMensaje($recipientId, $mensaje);

        if ($resultado['success'] && method_exists($suscripcion, 'registrarNotificacion')) {
            $suscripcion->registrarNotificacion();
        }

        return $resultado;
    }

    /**
     * Enviar mensaje de texto simple vía WhatsApp Cloud API.
     */
    public function enviarMensaje(string $recipientId, string $mensaje): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp Bot está deshabilitado en la configuración',
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->post($this->buildApiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->normalizePhone($recipientId),
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $this->stripHtmlToWhatsApp($mensaje),
                    ],
                ]);

            if ($response->successful()) {
                $this->debug('Mensaje enviado', ['to' => $recipientId]);
                return [
                    'success' => true,
                    'message' => 'Mensaje enviado exitosamente',
                    'wamid' => $response->json('messages.0.id'),
                ];
            }

            $error = $response->json('error.message') ?? $response->body();
            Log::error('WhatsApp: Error al enviar mensaje', [
                'to' => $recipientId,
                'error' => $error,
                'status' => $response->status(),
            ]);

            return ['success' => false, 'message' => $error];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
        }
    }

    /**
     * Enviar mensaje con botones interactivos (Interactive Message - buttons).
     *
     * WhatsApp Cloud API soporta máximo 3 botones por mensaje interactivo.
     * Para las 3 acciones (Analizar, Descargar, Compatibilidad) es perfecto.
     */
    public function enviarMensajeConBotones(string $recipientId, string $mensaje, array $keyboard): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp Bot está deshabilitado en la configuración',
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->post($this->buildApiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->normalizePhone($recipientId),
                    'type' => 'interactive',
                    'interactive' => $keyboard,
                ]);

            $success = $response->successful();
            $error = $response->json('error.message') ?? $response->body();

            if (!$success) {
                Log::error('WhatsApp: Error al enviar mensaje interactivo', [
                    'to' => $recipientId,
                    'error' => $error,
                    'status' => $response->status(),
                ]);
            }

            return [
                'success' => $success,
                'message' => $success ? 'Mensaje interactivo enviado exitosamente' : $error,
                'wamid' => $success ? $response->json('messages.0.id') : null,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
        }
    }

    // ─── InteractiveChannelContract ────────────────────────────────────

    /**
     * Construir estructura de botones interactivos (WhatsApp Cloud API format).
     *
     * WhatsApp permite máximo 3 botones por mensaje interactivo (tipo button).
     * Cada botón tiene un `id` que funciona como callback_data.
     */
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
            Log::debug('WhatsApp: omitiendo botones de archivo (idContratoArchivo=0)', [
                'idContrato' => $idContrato,
            ]);
            return null;
        }

        return [
            'type' => 'button',
            'header' => [
                'type' => 'text',
                'text' => '📋 Acciones del Proceso',
            ],
            'body' => [
                'text' => $this->stripHtmlToWhatsApp($this->construirMensaje($contratoData)),
            ],
            'footer' => [
                'text' => '🤖 Vigilante SEACE',
            ],
            'action' => [
                'buttons' => [
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
                            'id' => $this->buildCallbackData('descargar', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '📥 Descargar TDR',
                        ],
                    ],
                    [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $this->buildCallbackData('compatibilidad', $idContrato, $idContratoArchivo, $nombreArchivo),
                            'title' => '🏅 Compatibilidad',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Cachear contexto de un contrato para uso posterior en callbacks.
     */
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
            $this->contratoCachePrefix . $idContrato,
            $payload,
            now()->addMinutes(max(1, $this->contratoCacheTtl))
        );
    }

    /**
     * Enviar documento (PDF) al usuario vía WhatsApp Cloud API.
     *
     * Nota: WhatsApp Cloud API requiere subir el media primero o pasar una URL pública.
     * Aquí usamos la estrategia de subir el binario vía la Media API.
     */
    public function enviarDocumento(string $recipientId, string $documentBinary, string $filename, string $caption = ''): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp Bot está deshabilitado'];
        }

        try {
            // 1. Subir media a Meta
            $mediaId = $this->uploadMedia($documentBinary, $filename);

            if (!$mediaId) {
                return ['success' => false, 'message' => 'No se pudo subir el archivo a WhatsApp'];
            }

            // 2. Enviar documento con media_id
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->post($this->buildApiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->normalizePhone($recipientId),
                    'type' => 'document',
                    'document' => [
                        'id' => $mediaId,
                        'filename' => $filename,
                        'caption' => $caption ?: "📄 {$filename}",
                    ],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Documento enviado exitosamente',
                    'wamid' => $response->json('messages.0.id'),
                ];
            }

            $error = $response->json('error.message') ?? $response->body();
            return ['success' => false, 'message' => $error];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al enviar documento: ' . $e->getMessage()];
        }
    }

    // ─── Métodos Privados ──────────────────────────────────────────────

    /**
     * Subir archivo binario a la Media API de WhatsApp.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#upload-media
     */
    protected function uploadMedia(string $binary, string $filename): ?string
    {
        try {
            $mimeType = $this->guessMimeType($filename);

            $response = Http::withToken($this->token)
                ->timeout(30)
                ->attach('file', $binary, $filename, ['Content-Type' => $mimeType])
                ->post("{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/media", [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('WhatsApp: Error al subir media', [
                'filename' => $filename,
                'error' => $response->json('error.message') ?? $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('WhatsApp: Excepción al subir media', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Construir mensaje de notificación con formato WhatsApp (Markdown ligero).
     *
     * WhatsApp usa: *bold*, _italic_, ~strikethrough~, ```code```
     */
    protected function construirMensaje(array $contratoData): string
    {
        $mensaje = "🔔 *NUEVO PROCESO SEACE*\n\n";
        $mensaje .= "🏢 *Entidad:* " . ($contratoData['nomEntidad'] ?? 'N/A') . "\n";
        $mensaje .= "📝 *Código:* " . ($contratoData['desContratacion'] ?? 'N/A') . "\n";
        $mensaje .= "🎯 *Objeto:* " . ($contratoData['nomObjetoContrato'] ?? 'N/A') . "\n";

        $descripcion = $contratoData['desObjetoContrato'] ?? 'N/A';
        if (strlen($descripcion) > 200) {
            $descripcion = substr($descripcion, 0, 200) . '...';
        }
        $mensaje .= "📋 *Descripción:* {$descripcion}\n\n";

        $mensaje .= "📅 *Publicado:* " . ($contratoData['fecPublica'] ?? 'N/A') . "\n";
        $mensaje .= "⏰ *Inicio Cotización:* " . ($contratoData['fecIniCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "🕐 *Fin Cotización:* " . ($contratoData['fecFinCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "💼 *Estado:* " . ($contratoData['nomEstadoContrato'] ?? 'N/A') . "\n";
        $mensaje .= "📍 *Etapa:* " . ($contratoData['nomEtapaContratacion'] ?? 'N/A');

        return $mensaje;
    }

    /**
     * Construir callback data para botones (misma convención que Telegram: accion_id_idArchivo_nombre).
     */
    protected function buildCallbackData(string $action, int $idContrato, int $idContratoArchivo, string $nombreArchivo): string
    {
        $nombre = $this->sanitizeCallbackFilename($nombreArchivo);
        // WhatsApp button id máximo 256 chars
        return sprintf('%s_%d_%d_%s', $action, $idContrato, $idContratoArchivo, $nombre);
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

    /**
     * Convertir HTML (formato Telegram) a formato WhatsApp.
     * Telegram usa <b>, <i>; WhatsApp usa *bold*, _italic_.
     */
    protected function stripHtmlToWhatsApp(string $html): string
    {
        $text = str_replace(['<b>', '</b>'], '*', $html);
        $text = str_replace(['<i>', '</i>'], '_', $text);
        $text = str_replace(['<code>', '</code>'], '```', $text);
        $text = strip_tags($text);

        return $text;
    }

    /**
     * Normalizar número de teléfono (quitar +, espacios, guiones).
     * WhatsApp Cloud API requiere formato: código país + número sin +
     * Ejemplo: 51987654321 (Perú)
     */
    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Adivinar MIME type por extensión de archivo.
     */
    protected function guessMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    /**
     * Construir URL de la API de WhatsApp Cloud.
     */
    protected function buildApiUrl(string $endpoint): string
    {
        return sprintf('%s/%s/%s/%s', $this->baseUrl, $this->apiVersion, $this->phoneNumberId, $endpoint);
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('WhatsApp: ' . $message, $context);
    }
}
