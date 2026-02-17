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
 * Job programado que importa procesos TDR del SEACE y notifica a suscriptores
 * de Telegram y WhatsApp.
 *
 * Horario: Lunes a viernes, cada 2 horas entre 06:00 y 20:00 (hora Lima).
 * Ejecuciones: 06:00, 08:00, 10:00, 12:00, 14:00, 16:00, 18:00, 20:00.
 *
 * La ejecución de las 06:00 busca procesos de HOY + AYER para cubrir la
 * brecha nocturna (procesos publicados entre 20:00 y 23:59 del día anterior).
 * Carbon::subDay() maneja automáticamente transiciones de mes:
 *   - 1 marzo → 28/29 febrero
 *   - 1 julio → 30 junio
 *   - 1 enero → 31 diciembre del año anterior
 *
 * Las demás ejecuciones (08:00-20:00) solo buscan procesos de HOY.
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
        $ahora = Carbon::now($timezone);
        $fechaHoy = $ahora->copy()->startOfDay();

        // ── Detectar si es la primera ejecución del día (06:00-07:59) ──
        // En ese rango, también buscamos procesos del día ANTERIOR para cubrir
        // la brecha nocturna (publicados entre 20:00 y 23:59 de ayer).
        // Carbon::subDay() maneja automáticamente fin de mes:
        //   31 enero → 30 enero ✗ → en realidad 1 feb subDay = 31 ene ✔
        //   1 marzo  → 28/29 febrero ✔
        //   1 julio  → 30 junio ✔
        //   1 enero  → 31 diciembre año anterior ✔
        $esPrimeraEjecucion = $ahora->hour < 8;
        $fechaAyer = $esPrimeraEjecucion ? $fechaHoy->copy()->subDay() : null;

        Log::info('ImportarTdrNotificarJob: iniciando', [
            'fecha_hoy' => $fechaHoy->format('d/m/Y'),
            'fecha_ayer' => $fechaAyer?->format('d/m/Y'),
            'hora_local' => $ahora->format('H:i'),
            'primera_ejecucion' => $esPrimeraEjecucion,
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

        // ── Ejecución principal: procesos de HOY ──
        try {
            $resumen = $engine->ejecutar($fechaHoy, $suscripciones, $this->limite);

            Log::info('ImportarTdrNotificarJob: completado (HOY)', [
                'fecha' => $resumen['stats']['fecha'] ?? $fechaHoy->format('d/m/Y'),
                'descargados' => $resumen['stats']['total_descargados'] ?? 0,
                'filtrados' => $resumen['stats']['total_filtrados'] ?? 0,
                'pendientes' => $resumen['stats']['total_pendientes'] ?? 0,
                'coincidencias' => $resumen['stats']['total_coincidencias'] ?? 0,
                'envios' => $resumen['stats']['total_envios'] ?? 0,
                'errores' => $resumen['stats']['errores_envio'] ?? 0,
                'tiempo_ms' => $resumen['stats']['tiempo_ms'] ?? 0,
            ]);
        } catch (Exception $e) {
            Log::error('ImportarTdrNotificarJob: error (HOY)', [
                'fecha' => $fechaHoy->format('d/m/Y'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Si falla HOY pero hay AYER pendiente, no abortar
            if (!$esPrimeraEjecucion) {
                throw $e;
            }
        }

        // ── Ejecución complementaria: procesos de AYER (solo en primera ejecución) ──
        if ($esPrimeraEjecucion && $fechaAyer) {
            try {
                Log::info('ImportarTdrNotificarJob: buscando procesos rezagados de AYER', [
                    'fecha_ayer' => $fechaAyer->format('d/m/Y'),
                ]);

                $resumenAyer = $engine->ejecutar($fechaAyer, $suscripciones, $this->limite);

                Log::info('ImportarTdrNotificarJob: completado (AYER)', [
                    'fecha' => $resumenAyer['stats']['fecha'] ?? $fechaAyer->format('d/m/Y'),
                    'descargados' => $resumenAyer['stats']['total_descargados'] ?? 0,
                    'filtrados' => $resumenAyer['stats']['total_filtrados'] ?? 0,
                    'pendientes' => $resumenAyer['stats']['total_pendientes'] ?? 0,
                    'coincidencias' => $resumenAyer['stats']['total_coincidencias'] ?? 0,
                    'envios' => $resumenAyer['stats']['total_envios'] ?? 0,
                    'errores' => $resumenAyer['stats']['errores_envio'] ?? 0,
                    'tiempo_ms' => $resumenAyer['stats']['tiempo_ms'] ?? 0,
                ]);
            } catch (Exception $e) {
                // No fallar el job entero por procesos de ayer
                Log::warning('ImportarTdrNotificarJob: error buscando procesos de AYER (no crítico)', [
                    'fecha_ayer' => $fechaAyer->format('d/m/Y'),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
