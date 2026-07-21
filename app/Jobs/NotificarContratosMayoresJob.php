<?php

namespace App\Jobs;

use App\Models\ContratoMayor;
use App\Models\TelegramSubscription;
use App\Models\WhatsAppSubscription;
use App\Services\TelegramNotificationService;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Notifica a suscriptores de Telegram y WhatsApp sobre nuevos Contratos Mayores.
 *
 * A diferencia de Menores (que consulta la API SEACE en vivo), Mayores
 * consulta la tabla local `contratos_mayores` (alimentada por ImportarContratosMayoresJob).
 *
 * Schedule: cada 2 horas (igual que Menores). Usa `notified_processes`
 * y `notification_sends` para dedup per-suscriptor. Los contratos se
 * identifican por su `ocid` (VARCHAR), compatible con el tracker.
 */
class NotificarContratosMayoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    protected int $horasRecientes;

    public function __construct(int $horasRecientes = 6)
    {
        $this->horasRecientes = $horasRecientes;
    }

    public function handle(
        TelegramNotificationService $telegram,
        WhatsAppNotificationService $whatsappService
    ): void {
        $timezone = 'America/Lima';
        $ahora = Carbon::now($timezone);
        $desde = $ahora->copy()->subHours($this->horasRecientes);

        Log::info('NotificarContratosMayores: iniciando', [
            'desde' => $desde->toDateTimeString(),
            'hasta' => $ahora->toDateTimeString(),
            'horas_recientes' => $this->horasRecientes,
        ]);

        // ── Suscriptores que quieren recibir Contratos Mayores ──
        $telegramSubs = TelegramSubscription::with('user.subscriberProfile.keywords')
            ->where('activo', true)
            ->where('recibir_mayores', true)
            ->get();

        $whatsappSubs = WhatsAppSubscription::with('user.subscriberProfile.keywords')
            ->where('activo', true)
            ->where('recibir_mayores', true)
            ->get();

        $suscripciones = $telegramSubs->concat($whatsappSubs);

        if ($suscripciones->isEmpty()) {
            Log::info('NotificarContratosMayores: sin suscriptores para mayores, abortando.');
            return;
        }

        Log::info('NotificarContratosMayores: suscriptores', [
            'telegram' => $telegramSubs->count(),
            'whatsapp' => $whatsappSubs->count(),
        ]);

        // ── Contratos Mayores recientes desde la BD local ──
        $contratos = ContratoMayor::where('created_at', '>=', $desde)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        if ($contratos->isEmpty()) {
            Log::info('NotificarContratosMayores: sin contratos recientes.');
            return;
        }

        Log::info('NotificarContratosMayores: contratos a evaluar', [
            'count' => $contratos->count(),
        ]);

        $totalEnviados = 0;

        foreach ($suscripciones as $sub) {
            $channel = $this->resolveChannel($sub, $telegram, $whatsappService);
            if (!$channel) {
                continue;
            }

            $keywords = $sub->user?->subscriberProfile?->keywords ?? collect();
            $keywordList = $keywords->pluck('nombre')->filter()->map(fn ($k) => mb_strtolower($k))->toArray();

            foreach ($contratos as $contrato) {
                if (empty($keywordList)) {
                    continue;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $contrato->entidad_nombre ?? '',
                    $contrato->nomenclatura ?? '',
                    $contrato->descripcion_objeto ?? '',
                    $contrato->objeto_contratacion ?? '',
                ]));

                $matched = array_filter($keywordList, fn ($kw) => $kw !== '' && str_contains($haystack, $kw));

                if (empty($matched)) {
                    continue;
                }

                $this->enviarNotificacion($channel, $sub, $contrato, $matched);
                $totalEnviados++;
            }
        }

        Log::info('NotificarContratosMayores: completado', [
            'total_enviados' => $totalEnviados,
        ]);
    }

    protected function resolveChannel(
        $sub,
        TelegramNotificationService $telegram,
        WhatsAppNotificationService $whatsapp
    ) {
        if ($sub instanceof TelegramSubscription && $telegram->isEnabled()) {
            return $telegram;
        }
        if ($sub instanceof WhatsAppSubscription && $whatsapp->isEnabled()) {
            return $whatsapp;
        }
        return null;
    }

    protected function enviarNotificacion($channel, $sub, ContratoMayor $contrato, array $keywords): void
    {
        $recipientId = $sub instanceof TelegramSubscription
            ? $sub->chat_id
            : $sub->phone_number;

        $isTelegram = $sub instanceof TelegramSubscription;
        $tipoLabel = $isTelegram
            ? "🏛️ *Contrato Mayor (> 8UIT)*"
            : "🏛️ Contrato Mayor (> 8UIT)";

        $webUrl = config('app.url') . '/buscador-contratos-mayores?query=' . urlencode($contrato->ocid);

        $mensaje = "{$tipoLabel}\n\n"
            . "📋 {$contrato->nomenclatura}\n"
            . "🏢 Entidad: {$contrato->entidad_nombre}\n"
            . "📦 Objeto: {$contrato->objeto_contratacion}\n";

        if ($contrato->valor_referencial > 0) {
            $mensaje .= "💰 Monto: S/ " . number_format($contrato->valor_referencial, 2) . "\n";
        }

        if ($contrato->fecha_publicacion) {
            $mensaje .= "📅 Publicación: " . $contrato->fecha_publicacion->format('d/m/Y') . "\n";
        }

        if (!empty($keywords)) {
            $mensaje .= "\n🔎 Coincidencias: " . implode(', ', $keywords);
        }

        $mensaje .= "\n\n🔍 Ver en web: {$webUrl}";

        if (!empty($contrato->url_documento)) {
            $mensaje .= "\n📎 TDR: {$contrato->url_documento}";
        }

        try {
            if ($isTelegram) {
                $keyboard = [
                    'inline_keyboard' => [
                        [['text' => '🤖 Analizar con IA', 'url' => $webUrl]],
                        [['text' => '🔍 Detectar Direccionamiento', 'url' => $webUrl]],
                        [['text' => '📋 Crear Proforma', 'url' => $webUrl]],
                        [['text' => '👥 Ver Postores', 'url' => $webUrl]],
                    ],
                ];
                if (!empty($contrato->url_documento)) {
                    $keyboard['inline_keyboard'][] = [
                        ['text' => '📎 Descargar TDR', 'url' => $contrato->url_documento],
                    ];
                }
                $channel->enviarMensajeConBotones($recipientId, $mensaje, $keyboard);
            } else {
                $bodyWhatsApp = "*NUEVO CONTRATO MAYOR*\n\n"
                    . "🏢 *Entidad:* {$contrato->entidad_nombre}\n"
                    . "📝 *Código:* {$contrato->nomenclatura}\n"
                    . "🎯 *Objeto:* {$contrato->objeto_contratacion}\n";

                $descripcion = $contrato->descripcion_objeto ?? '';
                if (mb_strlen($descripcion) > 200) {
                    $descripcion = mb_substr($descripcion, 0, 200) . '...';
                }
                if (!empty($descripcion)) {
                    $bodyWhatsApp .= "📋 *Descripción:* {$descripcion}\n\n";
                } else {
                    $bodyWhatsApp .= "\n";
                }

                if ($contrato->valor_referencial > 0) {
                    $bodyWhatsApp .= "💰 *Monto:* S/ " . number_format($contrato->valor_referencial, 2) . "\n";
                }
                if ($contrato->fecha_publicacion) {
                    $bodyWhatsApp .= "📅 *Publicado:* " . $contrato->fecha_publicacion->format('d/m/Y') . "\n";
                }
                $bodyWhatsApp .= "💼 *Estado:* " . ($contrato->estado ?? 'N/A');

                if (!empty($keywords)) {
                    $bodyWhatsApp .= "\n\n🔎 *Coincidencias:* " . implode(', ', $keywords);
                }

                $bodyText = str_replace(['*', '_', '~'], '', $bodyWhatsApp);

                $rows = [
                    ['id' => 'mayor_analizar_' . $contrato->ocid, 'title' => '🤖 Analizar con IA', 'description' => 'Requisitos, plazos y penalidades'],
                ];
                if (!empty($contrato->url_documento)) {
                    $rows[] = ['id' => 'mayor_descargar_' . $contrato->ocid, 'title' => '📎 Descargar TDR', 'description' => 'Documento de bases'];
                }
                $rows[] = ['id' => 'mayor_direccionar_' . $contrato->ocid, 'title' => '🔍 Detectar Direccionamiento', 'description' => 'Auditar el TDR'];
                $rows[] = ['id' => 'mayor_proforma_' . $contrato->ocid, 'title' => '📋 Crear Proforma', 'description' => 'Cotización y costos'];
                $rows[] = ['id' => 'mayor_postores_' . $contrato->ocid, 'title' => '👥 Ver Postores', 'description' => 'Entidades involucradas'];
                $rows[] = ['id' => 'mayor_verweb_' . $contrato->ocid, 'title' => '🌐 Ver en la web', 'description' => 'Abrir en el buscador'];

                $keyboard = [
                    'type' => 'list',
                    'header' => ['type' => 'text', 'text' => '📋 Acciones del Proceso'],
                    'body' => ['text' => mb_substr($bodyText, 0, 1024)],
                    'footer' => ['text' => '🤖 Vigilante SEACE'],
                    'action' => [
                        'button' => 'Ver acciones',
                        'sections' => [['title' => 'Acciones disponibles', 'rows' => $rows]],
                    ],
                ];
                $channel->enviarMensajeConBotones($recipientId, $mensaje, $keyboard);
            }

            Log::info('NotificarContratosMayores: notificación enviada', [
                'canal' => $channel->channelName(),
                'recipient' => $recipientId,
                'ocid' => $contrato->ocid,
            ]);

            if (method_exists($sub, 'registrarNotificacion')) {
                $sub->registrarNotificacion();
            }
        } catch (\Exception $e) {
            Log::warning('NotificarContratosMayores: error al enviar', [
                'canal' => $channel->channelName(),
                'recipient' => $recipientId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
