<?php

namespace App\Jobs;

use App\Mail\AlertaAdjudicacion;
use App\Models\ContratoMayor;
use App\Models\EmailSubscription;
use App\Models\SubscriberProfile;
use App\Models\SystemSetting;
use App\Models\TelegramSubscription;
use App\Models\VigilanciaAdjudicacion;
use App\Models\VigilanciaAdjudicacionDestinatario;
use App\Models\WhatsAppSubscription;
use App\Services\ProcessNotificationTracker;
use App\Services\SeaceMayoresService;
use App\Services\TelegramNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Vigilancia automática de adjudicaciones — Contratos Mayores.
 *
 * Diseño acordado:
 *  1. SYNC: todo Contrato Mayor con valor_referencial >= umbral (default
 *     S/ 1,000,000) se registra en vigilancia_adjudicaciones.
 *  2. SCAN: cada 5 horas se consulta 1x1 contra la API OCDS
 *     (/records?ocid=) el estado actual de los procesos vigilados que aún
 *     no llegan a buena pro.
 *  3. ALERTA: cuando un proceso pasa a ADJUDICADO / CONSENTIDO / OTORGADO /
 *     CONTRATADO (buena pro) se notifica a TODOS los destinatarios
 *     configurados (email y/o WhatsApp), UNA sola vez por proceso.
 *
 * Schedule: cada 5 horas (hora Lima).
 */
class VigilarAdjudicacionesMayoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 7200;

    public function handle(SeaceMayoresService $service, WhatsAppNotificationService $whatsapp): void
    {
        $umbral = (float) SystemSetting::getValue('vigilancia_monto_min', 1_000_000);

        Log::info('VigilarAdjudicacionesMayores: iniciando', [
            'umbral' => $umbral,
        ]);

        $registrados = $this->sincronizarVigilados($umbral);
        $this->escanearVigilados($service, $whatsapp, $umbral);

        Log::info('VigilarAdjudicacionesMayores: completado', [
            'nuevos_registrados' => $registrados,
        ]);
    }

    /**
     * Registra en vigilancia los contratos >= umbral que aún no están.
     *
     * @return int cantidad de nuevos registros
     */
    protected function sincronizarVigilados(float $umbral): int
    {
        $nuevos = 0;

        ContratoMayor::query()
            ->where('valor_referencial', '>=', $umbral)
            ->select(['id', 'ocid', 'nomenclatura', 'entidad_nombre', 'valor_referencial', 'estado', 'fecha_publicacion'])
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$nuevos) {
                foreach ($chunk as $c) {
                    $existe = VigilanciaAdjudicacion::where('ocid', $c->ocid)->exists();
                    if ($existe) {
                        continue;
                    }

                    VigilanciaAdjudicacion::create([
                        'ocid' => $c->ocid,
                        'nomenclatura' => $c->nomenclatura,
                        'entidad_nombre' => $c->entidad_nombre,
                        'valor_referencial' => $c->valor_referencial,
                        'estado' => $c->estado,
                        'fecha_publicacion' => $c->fecha_publicacion,
                    ]);

                    $nuevos++;
                }
            });

        if ($nuevos > 0) {
            Log::info('VigilarAdjudicacionesMayores: nuevos vigilados', ['nuevos' => $nuevos]);
        }

        return $nuevos;
    }

    /**
     * Consulta 1x1 el estado actual de los vigilados pendientes y notifica
     * cuando se detecta la buena pro.
     */
    protected function escanearVigilados(SeaceMayoresService $service, WhatsAppNotificationService $whatsapp, float $umbral): void
    {
        $vigilados = VigilanciaAdjudicacion::query()
            ->whereNull('notificado_en')
            ->whereNotIn('estado', VigilanciaAdjudicacion::ESTADOS_FINALES)
            ->orderBy('updated_at', 'asc')
            ->limit(500)
            ->get();

        if ($vigilados->isEmpty()) {
            Log::info('VigilarAdjudicacionesMayores: sin vigilados pendientes de escaneo.');
            return;
        }

        Log::info('VigilarAdjudicacionesMayores: escaneando', [
            'pendientes' => $vigilados->count(),
        ]);

        $buenaPro = 0;
        $sinCambio = 0;
        $fallos = 0;
        $fallosConsecutivos = 0;

        foreach ($vigilados as $indice => $vig) {
            $resultado = $service->fetchRecordPorOcid($vig->ocid);

            if (!$resultado['success']) {
                $fallos++;
                $fallosConsecutivos++;

                // Salvaguarda: API caída → abortar sin quemar llamadas
                if ($fallosConsecutivos >= 15 && $indice < 50) {
                    Log::critical('VigilarAdjudicacionesMayores: API aparentemente caída, abortando', [
                        'fallos_consecutivos' => $fallosConsecutivos,
                        'procesados' => $indice + 1,
                    ]);
                    return;
                }
                continue;
            }

            $fallosConsecutivos = 0;

            $fresh = $resultado['data'];
            $nuevoEstado = strtoupper((string) ($fresh['estado'] ?? ''));

            if (in_array($nuevoEstado, VigilanciaAdjudicacion::ESTADOS_BUENA_PRO, true)) {
                // 🏆 BUENA PRO detectada → notificar UNA vez
                $vig->update([
                    'estado' => $nuevoEstado,
                    'estado_notificado' => $nuevoEstado,
                    'notificado_en' => now(),
                ]);

                $this->notificarDestinatarios($vig, $fresh, $whatsapp, $umbral);
                $buenaPro++;

                Log::info('VigilarAdjudicacionesMayores: BUENA PRO detectada', [
                    'ocid' => $vig->ocid,
                    'nomenclatura' => $vig->nomenclatura,
                    'estado' => $nuevoEstado,
                ]);
            } else {
                $vig->update(['estado' => $nuevoEstado ?: $vig->estado]);
                $sinCambio++;
            }

            usleep(150_000); // 150ms entre llamadas: ser amable con la API

            if (($indice + 1) % 100 === 0) {
                Log::info('VigilarAdjudicacionesMayores: progreso', [
                    'procesados' => $indice + 1,
                    'buena_pro' => $buenaPro,
                    'fallos' => $fallos,
                ]);
            }
        }

        Log::info('VigilarAdjudicacionesMayores: escaneo completado', [
            'procesados' => $vigilados->count(),
            'buena_pro' => $buenaPro,
            'sin_cambio' => $sinCambio,
            'fallos' => $fallos,
        ]);
    }

    /**
     * Envía la alerta a TODOS los destinatarios activos (email y/o WhatsApp).
     */
    protected function notificarDestinatarios(
        VigilanciaAdjudicacion $vig,
        array $fresh,
        WhatsAppNotificationService $whatsapp,
        float $umbral
    ): void {
        $destinatarios = VigilanciaAdjudicacionDestinatario::activos()->get();

        $proceso = [
            'nomenclatura' => $vig->nomenclatura,
            'entidad_nombre' => $vig->entidad_nombre,
            'valor_referencial' => (float) ($vig->valor_referencial ?? 0),
            'estado' => $fresh['estado'] ?? '',
            'proveedores' => $fresh['proveedores'] ?? [],
            'fecha_publicacion' => $vig->fecha_publicacion?->format('d/m/Y') ?? '',
            'umbral' => $umbral,
        ];

        $mensajeWhatsApp = $this->buildMensajeWhatsApp($proceso);

        if ($destinatarios->isEmpty()) {
            Log::warning('VigilarAdjudicacionesMayores: buena pro detectada pero SIN destinatarios configurados', [
                'ocid' => $vig->ocid,
            ]);
            // NO retornar: los usuarios opt-in de la plataforma siguen abajo
        } else {
            foreach ($destinatarios as $dest) {
                if (!empty($dest->email)) {
                    try {
                        Mail::to($dest->email)->send(new AlertaAdjudicacion($proceso));
                        Log::info('VigilarAdjudicacionesMayores: email enviado', [
                            'ocid' => $vig->ocid,
                            'email' => $dest->email,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('VigilarAdjudicacionesMayores: fallo email', [
                            'ocid' => $vig->ocid,
                            'email' => $dest->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if (!empty($dest->telefono)) {
                    try {
                        $whatsapp->enviarMensaje($dest->telefono, $mensajeWhatsApp);
                        Log::info('VigilarAdjudicacionesMayores: whatsapp enviado', [
                            'ocid' => $vig->ocid,
                            'telefono' => $dest->telefono,
                        ]);
                        usleep(500_000); // margen entre envíos WhatsApp
                    } catch (\Throwable $e) {
                        Log::error('VigilarAdjudicacionesMayores: fallo whatsapp', [
                            'ocid' => $vig->ocid,
                            'telefono' => $dest->telefono,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Usuarios de la plataforma que activaron "alertar cuando procesos se hayan adjudicado"
        $this->notificarUsuariosOptIn($vig, $proceso, $mensajeWhatsApp, $whatsapp);
    }

    /**
     * Notifica a los usuarios que activaron la opción en /configuracion-alertas,
     * por sus canales activos (Telegram/WhatsApp/Email), con dedup per-usuario.
     */
    protected function notificarUsuariosOptIn(
        VigilanciaAdjudicacion $vig,
        array $proceso,
        string $mensajeWhatsApp,
        WhatsAppNotificationService $whatsapp
    ): void {
        $profiles = SubscriberProfile::query()
            ->where('alerta_adjudicaciones', true)
            ->with('user')
            ->get();

        if ($profiles->isEmpty()) {
            return;
        }

        $tracker = app(ProcessNotificationTracker::class);
        $telegram = app(TelegramNotificationService::class);
        $mensajeTelegram = $this->buildMensajeTelegram($proceso);

        foreach ($profiles as $profile) {
            $userId = $profile->user_id;

            // Defensa en profundidad: si perdió el permiso, no notificar
            if (!$profile->user?->hasPermission('alerta-adjudicaciones')) {
                continue;
            }

            // ── Telegram ──
            $tgSubs = TelegramSubscription::where('user_id', $userId)
                ->where('activo', true)
                ->get();

            foreach ($tgSubs as $tg) {
                if (!$tg->recibir_mayores) {
                    continue;
                }
                $recipient = (string) $tg->chat_id;
                if ($tracker->wasAlreadyNotified($vig->ocid, $userId, 'adj-telegram', $recipient)) {
                    continue;
                }
                try {
                    $resultado = $telegram->enviarMensaje($tg->chat_id, $mensajeTelegram);
                    if ($resultado['success'] ?? false) {
                        $tracker->recordNotification(
                            [
                                'desContratacion' => $proceso['nomenclatura'],
                                'nomEntidad' => $proceso['entidad_nombre'],
                                'montoReferencial' => $proceso['valor_referencial'],
                                'fecPublica' => $proceso['fecha_publicacion'],
                                'nomObjetoContrato' => 'Buena Pro',
                            ],
                            $vig->ocid,
                            $userId,
                            'adj-telegram',
                            $recipient,
                            'alerta-adjudicacion'
                        );
                        Log::info('VigilarAdjudicacionesMayores: telegram usuario', [
                            'ocid' => $vig->ocid,
                            'user_id' => $userId,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('VigilarAdjudicacionesMayores: fallo telegram usuario', [
                        'ocid' => $vig->ocid,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ── WhatsApp ──
            $wa = WhatsAppSubscription::where('user_id', $userId)
                ->where('activo', true)
                ->first();

            if ($wa && $wa->recibir_mayores && !$tracker->wasAlreadyNotified($vig->ocid, $userId, 'adj-whatsapp', (string) $wa->phone_number)) {
                try {
                    $resultado = $whatsapp->enviarMensaje($wa->phone_number, $mensajeWhatsApp);
                    if ($resultado['success'] ?? false) {
                        $tracker->recordNotification(
                            [
                                'desContratacion' => $proceso['nomenclatura'],
                                'nomEntidad' => $proceso['entidad_nombre'],
                                'montoReferencial' => $proceso['valor_referencial'],
                                'fecPublica' => $proceso['fecha_publicacion'],
                                'nomObjetoContrato' => 'Buena Pro',
                            ],
                            $vig->ocid,
                            $userId,
                            'adj-whatsapp',
                            (string) $wa->phone_number,
                            'alerta-adjudicacion'
                        );
                        Log::info('VigilarAdjudicacionesMayores: whatsapp usuario', [
                            'ocid' => $vig->ocid,
                            'user_id' => $userId,
                        ]);
                    }
                    usleep(300_000);
                } catch (\Throwable $e) {
                    Log::error('VigilarAdjudicacionesMayores: fallo whatsapp usuario', [
                        'ocid' => $vig->ocid,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ── Email ──
            $emailSub = EmailSubscription::where('user_id', $userId)
                ->where('activo', true)
                ->first();

            if ($emailSub && !$tracker->wasAlreadyNotified($vig->ocid, $userId, 'adj-email', (string) $emailSub->email)) {
                try {
                    Mail::to($emailSub->email)->send(new AlertaAdjudicacion($proceso));
                    $tracker->recordNotification(
                        [
                            'desContratacion' => $proceso['nomenclatura'],
                            'nomEntidad' => $proceso['entidad_nombre'],
                            'montoReferencial' => $proceso['valor_referencial'],
                            'fecPublica' => $proceso['fecha_publicacion'],
                            'nomObjetoContrato' => 'Buena Pro',
                        ],
                        $vig->ocid,
                        $userId,
                        'adj-email',
                        (string) $emailSub->email,
                        'alerta-adjudicacion'
                    );
                    Log::info('VigilarAdjudicacionesMayores: email usuario', [
                        'ocid' => $vig->ocid,
                        'user_id' => $userId,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('VigilarAdjudicacionesMayores: fallo email usuario', [
                        'ocid' => $vig->ocid,
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    protected function buildMensajeTelegram(array $p): string
    {
        $mensaje = "🏆 <b>BUENA PRO DETECTADA</b>\n\n";
        $mensaje .= "📋 <b>{$p['nomenclatura']}</b>\n";
        $mensaje .= "🏢 {$p['entidad_nombre']}\n";
        $mensaje .= "📌 Estado: <b>{$p['estado']}</b>\n";

        if ($p['valor_referencial'] > 0) {
            $mensaje .= '💰 Valor referencial: S/ ' . number_format($p['valor_referencial'], 2) . "\n";
        }

        if (!empty($p['proveedores'])) {
            $mensaje .= '🤝 Proveedor: ' . implode(', ', array_slice($p['proveedores'], 0, 3)) . "\n";
        }

        if ($p['fecha_publicacion'] !== '') {
            $mensaje .= "📅 Publicado: {$p['fecha_publicacion']}\n";
        }

        $mensaje .= "\n🔍 Revisa el detalle en licitacionesmype.pe/buscador-contratos-mayores";

        return $mensaje;
    }

    protected function buildMensajeWhatsApp(array $p): string
    {
        $mensaje = "🏆 *BUENA PRO DETECTADA*\n\n";
        $mensaje .= "📋 *{$p['nomenclatura']}*\n";
        $mensaje .= "🏢 {$p['entidad_nombre']}\n";
        $mensaje .= "📌 Estado: *{$p['estado']}*\n";

        if ($p['valor_referencial'] > 0) {
            $mensaje .= '💰 Valor referencial: S/ ' . number_format($p['valor_referencial'], 2) . "\n";
        }

        if (!empty($p['proveedores'])) {
            $mensaje .= '🤝 Proveedor: ' . implode(', ', array_slice($p['proveedores'], 0, 3)) . "\n";
        }

        if ($p['fecha_publicacion'] !== '') {
            $mensaje .= "📅 Publicado: {$p['fecha_publicacion']}\n";
        }

        $mensaje .= "\n🔍 Revisa el detalle en licitacionesmype.pe/buscador-contratos-mayores";

        return $mensaje;
    }
}
