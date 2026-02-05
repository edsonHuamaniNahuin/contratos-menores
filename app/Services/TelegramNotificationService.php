<?php

namespace App\Services;

use App\Models\TelegramSubscription;
use App\Models\Contrato;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TelegramNotificationService
{
    protected string $botToken;
    protected ?string $chatId;
    protected int $timeout;
    protected bool $enabled;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->chatId = config('services.telegram.chat_id');
        $this->timeout = 10;
        $this->enabled = !empty($this->botToken);
    }

    /**
     * Enviar alerta a TODOS los suscriptores activos CON BOTÓN DE ANÁLISIS
     *
     * @param array $contratoData Datos del contrato/proceso SEACE
     * @return array Estadísticas del envío ['total', 'exitosos', 'fallidos']
     */
    public function enviarAlerta(array $contratoData): array
    {
        $estadisticas = [
            'total' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
            'detalles' => []
        ];

        // Obtener todos los suscriptores activos
        $suscripciones = TelegramSubscription::activas()->get();

        if ($suscripciones->isEmpty()) {
            Log::warning('Telegram: No hay suscriptores activos');
            return $estadisticas;
        }

        $estadisticas['total'] = $suscripciones->count();

        // Construir mensaje
        $mensaje = $this->construirMensaje($contratoData);

        // Crear botón inline para análisis IA
        // Formato: analizar_{idContrato}_{idContratoArchivo}_{nombreArchivo}
        $idContrato = $contratoData['idContrato'] ?? 0;
        $idContratoArchivo = $contratoData['idContratoArchivo'] ?? 0;
        $nombreArchivo = $contratoData['nombreArchivo'] ?? 'archivo.pdf';

        // Codificar nombre de archivo para URL (eliminar espacios, caracteres especiales)
        $nombreArchivoSafe = str_replace([' ', '/', '\\'], ['_', '_', '_'], $nombreArchivo);

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🤖 Analizar con IA',
                        'callback_data' => "analizar_{$idContrato}_{$idContratoArchivo}_{$nombreArchivoSafe}"
                    ],
                    [
                        'text' => '📥 Descargar TDR',
                        'callback_data' => "descargar_{$idContrato}_{$idContratoArchivo}_{$nombreArchivoSafe}"
                    ]
                ]
            ]
        ];

        // Enviar a cada suscriptor
        foreach ($suscripciones as $suscripcion) {
            // Verificar si el contrato coincide con los filtros del suscriptor
            if (!$suscripcion->coincideConFiltros($contratoData)) {
                Log::info("Telegram: Suscriptor {$suscripcion->chat_id} filtrado", [
                    'chat_id' => $suscripcion->chat_id,
                    'nombre' => $suscripcion->nombre
                ]);
                continue;
            }

            try {
                $response = $this->enviarMensajeConBotones($suscripcion->chat_id, $mensaje, $keyboard);

                if ($response['success']) {
                    $estadisticas['exitosos']++;
                    $suscripcion->registrarNotificacion();

                    Log::info('Telegram: Mensaje enviado exitosamente', [
                        'chat_id' => $suscripcion->chat_id,
                        'nombre' => $suscripcion->nombre
                    ]);
                } else {
                    $estadisticas['fallidos']++;
                    $estadisticas['detalles'][] = [
                        'chat_id' => $suscripcion->chat_id,
                        'error' => $response['message'] ?? 'Error desconocido'
                    ];

                    Log::error('Telegram: Error al enviar mensaje', [
                        'chat_id' => $suscripcion->chat_id,
                        'error' => $response['message'] ?? 'Unknown error'
                    ]);
                }
            } catch (Exception $e) {
                $estadisticas['fallidos']++;
                $estadisticas['detalles'][] = [
                    'chat_id' => $suscripcion->chat_id,
                    'error' => $e->getMessage()
                ];

                Log::error('Telegram: Excepción al enviar mensaje', [
                    'chat_id' => $suscripcion->chat_id,
                    'exception' => $e->getMessage()
                ]);
            }
        }

        Log::info('Telegram: Broadcast completado', $estadisticas);

        return $estadisticas;
    }

    /**
     * Construir mensaje HTML para Telegram
     *
     * @param array $contratoData Datos del contrato
     * @return string Mensaje formateado en HTML
     */
    protected function construirMensaje(array $contratoData): string
    {
        $mensaje = "🔔 <b>NUEVO PROCESO SEACE</b>\n\n";
        $mensaje .= "🏢 <b>Entidad:</b> " . ($contratoData['nomEntidad'] ?? 'N/A') . "\n";
        $mensaje .= "📝 <b>Código:</b> " . ($contratoData['desContratacion'] ?? 'N/A') . "\n";
        $mensaje .= "🎯 <b>Objeto:</b> " . ($contratoData['nomObjetoContrato'] ?? 'N/A') . "\n";

        // Truncar descripción si es muy larga
        $descripcion = $contratoData['desObjetoContrato'] ?? 'N/A';
        if (strlen($descripcion) > 200) {
            $descripcion = substr($descripcion, 0, 200) . '...';
        }
        $mensaje .= "📋 <b>Descripción:</b> " . $descripcion . "\n\n";

        $mensaje .= "📅 <b>Publicado:</b> " . ($contratoData['fecPublica'] ?? 'N/A') . "\n";
        $mensaje .= "⏰ <b>Inicio Cotización:</b> " . ($contratoData['fecIniCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "🕐 <b>Fin Cotización:</b> " . ($contratoData['fecFinCotizacion'] ?? 'N/A') . "\n";
        $mensaje .= "💼 <b>Estado:</b> " . ($contratoData['nomEstadoContrato'] ?? 'N/A') . "\n";
        $mensaje .= "📍 <b>Etapa:</b> " . ($contratoData['nomEtapaContratacion'] ?? 'N/A');

        return $mensaje;
    }

    /**
     * Enviar mensaje a un chat_id específico
     *
     * @param string $chatId ID del chat de Telegram
     * @param string $mensaje Texto del mensaje
     * @return array ['success' => bool, 'message' => string]
     */
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
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
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

    /**
     * Enviar mensaje con botones inline (callback buttons)
     *
     * @param string $chatId ID del chat de Telegram
     * @param string $mensaje Texto del mensaje
     * @param array $keyboard Keyboard markup con botones
     * @return array ['success' => bool, 'message' => string]
     */
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
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $mensaje,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode($keyboard),
                ]);

            if ($response->successful() && ($response->json()['ok'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Mensaje con botones enviado exitosamente',
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

    /**
     * Enviar notificación de nuevo contrato (método legacy)
     *
     * @param Contrato $contrato Modelo del contrato
     * @param array|null $archivo Datos del primer archivo TDR (si existe)
     * @return bool
     */
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
            // Datos del archivo TDR para el botón de análisis
            'idContratoArchivo' => $archivo['idContratoArchivo'] ?? 0,
            'nombreArchivo' => $archivo['nomArchivo'] ?? 'archivo.pdf',
        ];

        $resultado = $this->enviarAlerta($contratoData);

        return $resultado['exitosos'] > 0;
    }

    /**
     * Método legacy para compatibilidad
     */
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

    /**
     * Enviar notificación de contratos próximos a vencer
     */
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

            // Enviar a todos los suscriptores activos
            $suscripciones = TelegramSubscription::activas()->get();

            foreach ($suscripciones as $suscripcion) {
                $this->enviarMensaje($suscripcion->chat_id, $message);
            }

            return true;

        } catch (Exception $e) {
            Log::error('Telegram: Error al enviar notificación de vencimientos', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Formatear mensaje de contrato para Telegram (método legacy)
     */
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
            $message .= "{$urgencia} <b>Fin Cotización:</b> {$contrato->fin_cotizacion->format('d/m/Y H:i')} ";
            $message .= "({$diasRestantes} días restantes)\n";
        }

        $message .= "\n🔗 <b>Estado:</b> {$contrato->estado}\n";
        $message .= "\n🤖 <i>Vigilante SEACE</i>";

        return $message;
    }

    /**
     * Obtener emoji según tipo de objeto
     */
    protected function getEmojiByObjeto(string $objeto): string
    {
        return match(strtolower($objeto)) {
            'bien' => '📦',
            'servicio' => '🛠️',
            'obra' => '🏗️',
            'consultoría de obra' => '📐',
            default => '📋',
        };
    }
}
