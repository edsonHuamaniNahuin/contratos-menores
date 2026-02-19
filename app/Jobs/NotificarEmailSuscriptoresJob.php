<?php

namespace App\Jobs;

use App\Mail\NuevoProcesoSeace;
use App\Models\EmailSubscription;
use App\Services\SeaceBuscadorPublicoService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job que envía notificaciones por correo electrónico a todos los
 * suscriptores activos de email cada vez que hay nuevos procesos SEACE.
 *
 * Dos modos de suscripción:
 * 1. notificar_todo = true  → recibe TODOS los procesos nuevos
 * 2. notificar_todo = false → solo recibe los que coinciden con sus keywords
 *
 * Usa dedup (email_contract_sends) para no enviar el mismo proceso dos veces.
 */
class NotificarEmailSuscriptoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    protected int $limite;

    public function __construct(int $limite = 150)
    {
        $this->limite = $limite;
    }

    public function handle(SeaceBuscadorPublicoService $buscadorService): void
    {
        $timezone       = 'America/Lima';
        $ahora          = Carbon::now($timezone);
        $fechaHoy       = $ahora->copy()->startOfDay();

        // ── Primera ejecución del día (06:00-07:59): buscar también AYER ──
        // Cubre la brecha nocturna (procesos publicados 20:00-23:59 de ayer).
        // Carbon::subDay() maneja automáticamente fin de mes (31→1, 28/29→1, etc.)
        $esPrimeraEjecucion = $ahora->hour < 8;
        $fechaAyer          = $esPrimeraEjecucion ? $fechaHoy->copy()->subDay() : null;

        Log::info('NotificarEmailSuscriptoresJob: iniciando', [
            'fecha_hoy'          => $fechaHoy->format('d/m/Y'),
            'fecha_ayer'         => $fechaAyer?->format('d/m/Y'),
            'hora_local'         => $ahora->format('H:i'),
            'primera_ejecucion'  => $esPrimeraEjecucion,
        ]);

        // ── 1. Obtener suscriptores activos de email ──────────────
        $suscripciones = EmailSubscription::with('keywords')
            ->activas()
            ->get();

        if ($suscripciones->isEmpty()) {
            Log::info('NotificarEmailSuscriptoresJob: no hay suscriptores de email activos.');
            return;
        }

        // ── 2. Obtener dataset del SEACE (reutiliza caché del Engine) ─
        $dataset = $this->obtenerDataset($buscadorService, $fechaHoy);

        if (empty($dataset)) {
            Log::warning('NotificarEmailSuscriptoresJob: dataset vacío del SEACE.');
            return;
        }

        // ── 3. Filtrar procesos de HOY ────────────────────────────
        $procesosHoy = $this->filtrarPorFecha($dataset, $fechaHoy);

        // ── 3b. Si es primera ejecución, agregar procesos de AYER ─
        if ($esPrimeraEjecucion && $fechaAyer) {
            $procesosAyer = $this->filtrarPorFecha($dataset, $fechaAyer);

            if (!empty($procesosAyer)) {
                Log::info('NotificarEmailSuscriptoresJob: procesos rezagados de ayer encontrados', [
                    'fecha_ayer'     => $fechaAyer->format('d/m/Y'),
                    'procesos_ayer'  => count($procesosAyer),
                ]);

                // Merge sin duplicados (por idContrato)
                $idsProcesados = collect($procesosHoy)
                    ->pluck('idContrato')
                    ->filter()
                    ->all();

                foreach ($procesosAyer as $procesoAyer) {
                    $idContrato = $procesoAyer['idContrato'] ?? null;
                    if ($idContrato && !in_array($idContrato, $idsProcesados, true)) {
                        $procesosHoy[] = $procesoAyer;
                        $idsProcesados[] = $idContrato;
                    }
                }
            }
        }

        if (empty($procesosHoy)) {
            Log::info('NotificarEmailSuscriptoresJob: no hay procesos nuevos.');
            return;
        }

        Log::info('NotificarEmailSuscriptoresJob: procesos a notificar', [
            'total_dataset'  => count($dataset),
            'procesos_total' => count($procesosHoy),
            'suscriptores'   => $suscripciones->count(),
        ]);

        // ── 4. Iterar suscriptores y enviar emails ────────────────
        $totalEnvios  = 0;
        $totalErrores = 0;

        foreach ($suscripciones as $emailSub) {
            try {
                $enviados = $this->procesarSuscriptor($emailSub, $procesosHoy);
                $totalEnvios += $enviados;
            } catch (Exception $e) {
                $totalErrores++;
                Log::error('NotificarEmailSuscriptoresJob: error con suscriptor', [
                    'email_subscription_id' => $emailSub->id,
                    'email'                 => $emailSub->email,
                    'error'                 => $e->getMessage(),
                ]);
            }
        }

        Log::info('NotificarEmailSuscriptoresJob: completado', [
            'fecha'         => $fechaHoy->format('d/m/Y'),
            'procesos'      => count($procesosHoy),
            'suscriptores'  => $suscripciones->count(),
            'total_envios'  => $totalEnvios,
            'total_errores' => $totalErrores,
        ]);
    }

    /**
     * Procesa un suscriptor: determina qué procesos le corresponden y envía emails.
     */
    protected function procesarSuscriptor(EmailSubscription $emailSub, array $procesos): int
    {
        $enviados = 0;

        foreach ($procesos as $contrato) {
            $contratoSeaceId = (int) ($contrato['idContrato'] ?? $contrato['id_contrato_seace'] ?? 0);

            // ── Dedup: no enviar si ya fue enviado ─────────
            if ($contratoSeaceId > 0 && $emailSub->yaEnviado($contratoSeaceId)) {
                continue;
            }

            // ── Matching: verificar si coincide con los filtros ─
            $resultado = $emailSub->resolverCoincidenciasContrato($contrato);

            if (!$resultado['pasa']) {
                continue;
            }

            // ── Enviar email ───────────────────────────────
            try {
                $seguimientoUrl = route('buscador.publico');

                Mail::to($emailSub->email)->send(new NuevoProcesoSeace(
                    contrato: $contrato,
                    seguimientoUrl: $seguimientoUrl,
                    matchedKeywords: $resultado['keywords'] ?? [],
                ));

                // ── Registrar envío (dedup) ────────────────
                if ($contratoSeaceId > 0) {
                    $emailSub->registrarEnvio(
                        $contratoSeaceId,
                        $contrato['desContratacion'] ?? null
                    );
                }

                $enviados++;

                Log::debug('NotificarEmailSuscriptoresJob: email enviado', [
                    'email'    => $emailSub->email,
                    'contrato' => $contrato['desContratacion'] ?? 'N/A',
                    'keywords' => $resultado['keywords'],
                ]);
            } catch (Exception $e) {
                Log::error('NotificarEmailSuscriptoresJob: fallo al enviar email', [
                    'email'    => $emailSub->email,
                    'contrato' => $contrato['desContratacion'] ?? 'N/A',
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $enviados;
    }

    /**
     * Obtiene el dataset del buscador público SEACE, reutilizando caché si existe.
     */
    protected function obtenerDataset(SeaceBuscadorPublicoService $buscadorService, Carbon $fechaObjetivo): array
    {
        $anio     = (int) $fechaObjetivo->format('Y');
        $pageSize = max(1, min($this->limite, 150));
        $cacheKey = sprintf('tdr:dataset:%d:%d', $anio, $pageSize);

        // Reutilizar caché del ImportadorTdrEngine si existe
        $payload = Cache::get($cacheKey);

        if (is_array($payload) && !empty($payload['data'])) {
            Log::debug('NotificarEmailSuscriptoresJob: usando dataset en caché.');
            return $payload['data'];
        }

        // Si no hay caché, consultar directamente
        $respuesta = $buscadorService->buscarContratos([
            'anio'      => $anio,
            'orden'     => 2,
            'page'      => 1,
            'page_size' => $pageSize,
        ]);

        if (!($respuesta['success'] ?? false)) {
            Log::error('NotificarEmailSuscriptoresJob: fallo al consultar buscador público', [
                'error' => $respuesta['error'] ?? 'Respuesta no exitosa',
            ]);
            return [];
        }

        $data = $respuesta['data'] ?? [];

        // Guardar en caché para que otros jobs lo reutilicen
        Cache::put($cacheKey, [
            'data' => $data,
            'meta' => [
                'anio'           => $anio,
                'page'           => 1,
                'page_size'      => $pageSize,
                'total_elements' => $respuesta['pageable']['totalElements'] ?? count($data),
                'fetched_at'     => now()->format('Y-m-d H:i:s'),
            ],
        ], now()->addMinutes(5));

        return $data;
    }

    /**
     * Filtra procesos publicados en la fecha objetivo.
     */
    protected function filtrarPorFecha(array $dataset, Carbon $fechaObjetivo): array
    {
        return array_values(array_filter($dataset, function (array $contrato) use ($fechaObjetivo) {
            foreach (['fecPublica', 'fecFinCotizacion', 'fecIniCotizacion'] as $campo) {
                if ($this->coincideConFechaObjetivo($contrato[$campo] ?? null, $fechaObjetivo)) {
                    return true;
                }
            }
            return false;
        }));
    }

    protected function coincideConFechaObjetivo(?string $valor, Carbon $fechaObjetivo): bool
    {
        if (empty($valor)) {
            return false;
        }

        // Intento con Carbon
        $formatos = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];
        foreach ($formatos as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $valor, 'America/Lima');
                if ($fecha && $fecha->isSameDay($fechaObjetivo)) {
                    return true;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        // Fallback: comparar string de fecha
        $stringNormalizado = trim(substr((string) $valor, 0, 10));
        return $stringNormalizado !== '' && $stringNormalizado === $fechaObjetivo->format('d/m/Y');
    }
}
