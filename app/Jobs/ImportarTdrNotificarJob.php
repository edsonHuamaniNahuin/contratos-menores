<?php

namespace App\Jobs;

use App\Models\TelegramSubscription;
use App\Models\WhatsAppSubscription;
use App\Services\Tdr\ImportadorTdrEngine;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job programado que replica la funcionalidad de "Importador TDR + Notificación Telegram"
 * de PruebaEndpoints, ejecutándose automáticamente de lunes a viernes cada 2 horas
 * (10:00, 12:00, 14:00, 16:00, 18:00 hora Lima).
 *
 * Busca procesos del SEACE publicados HOY, cruza con keywords de todos los
 * suscriptores activos y envía notificaciones por Telegram.
 */
class ImportarTdrNotificarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Máximo de reintentos ante fallos transitorios.
     */
    public int $tries = 2;

    /**
     * Timeout amplio para procesamiento de lotes grandes.
     */
    public int $timeout = 300;

    /**
     * Límite de procesos a recuperar del buscador público por ejecución.
     */
    protected int $limite;

    public function __construct(int $limite = 150)
    {
        $this->limite = $limite;
    }

    public function handle(ImportadorTdrEngine $engine): void
    {
        $timezone = 'America/Lima';
        $fechaObjetivo = Carbon::now($timezone)->startOfDay();

        Log::info('ImportarTdrNotificarJob: iniciando', [
            'fecha' => $fechaObjetivo->format('d/m/Y'),
            'hora_local' => Carbon::now($timezone)->format('H:i'),
            'limite' => $this->limite,
        ]);

        // Registrar canal WhatsApp si está habilitado (DIP – canales se registran dinámicamente)
        $whatsapp = app(WhatsAppNotificationService::class);
        if ($whatsapp->isEnabled()) {
            $engine->registerChannel($whatsapp);
            Log::info('ImportarTdrNotificarJob: canal WhatsApp registrado.');
        }

        // Obtener TODOS los suscriptores activos de TODOS los canales
        $telegramSubs = TelegramSubscription::with('keywords')
            ->activas()
            ->get();

        $whatsappSubs = WhatsAppSubscription::with('keywords')
            ->activas()
            ->get();

        // Merge en una sola colección polimórfica
        $suscripciones = $telegramSubs->merge($whatsappSubs);

        if ($suscripciones->isEmpty()) {
            Log::warning('ImportarTdrNotificarJob: no hay suscriptores activos (Telegram ni WhatsApp), abortando.');
            return;
        }

        Log::info('ImportarTdrNotificarJob: suscriptores encontrados', [
            'telegram' => $telegramSubs->count(),
            'whatsapp' => $whatsappSubs->count(),
            'total' => $suscripciones->count(),
        ]);

        try {
            $resumen = $engine->ejecutar($fechaObjetivo, $suscripciones, $this->limite);

            Log::info('ImportarTdrNotificarJob: completado', [
                'fecha' => $resumen['stats']['fecha'] ?? $fechaObjetivo->format('d/m/Y'),
                'descargados' => $resumen['stats']['total_descargados'] ?? 0,
                'filtrados' => $resumen['stats']['total_filtrados'] ?? 0,
                'pendientes' => $resumen['stats']['total_pendientes'] ?? 0,
                'coincidencias' => $resumen['stats']['total_coincidencias'] ?? 0,
                'envios' => $resumen['stats']['total_envios'] ?? 0,
                'errores' => $resumen['stats']['errores_envio'] ?? 0,
                'tiempo_ms' => $resumen['stats']['tiempo_ms'] ?? 0,
            ]);
        } catch (Exception $e) {
            Log::error('ImportarTdrNotificarJob: error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-lanzar para que el queue worker reintente
        }
    }
}
