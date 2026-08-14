<?php

namespace App\Jobs;

use App\Models\ContratoMayor;
use App\Services\SeaceMayoresService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Refresca el estado de los contratos mayores MÁS ANTIGUOS (por updated_at).
 *
 * Problema que resuelve:
 *  - El endpoint /releases solo expone los últimos ~2 días de eventos.
 *  - Los contratos pueden cambiar de estado semanas después de publicarse.
 *  - El endpoint /records?ocid= devuelve el estado ACTUAL de cualquier
 *    contrato, sin importar su antigüedad.
 *
 * Estrategia: cada ejecución toma los N contratos con updated_at más viejo
 * y los refresca vía /records?ocid=. Tras varias pasadas (N × frecuencia),
 * TODOS los contratos almacenados quedan con estado fresco.
 *
 * Schedule: cada 6 horas, 300 contratos por corrida.
 * Con ~2100 contratos: ciclo completo cada ~42 horas.
 */
class RefrescarEstadosContratosMayoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 7200; // 2h: la corrida nocturna completa (~11,000 contratos) toma ~1.8h

    protected int $porCorrida;

    public function __construct(int $porCorrida = 300)
    {
        $this->porCorrida = $porCorrida;
    }

    public function handle(SeaceMayoresService $service): void
    {
        $contratos = ContratoMayor::orderBy('updated_at', 'asc')
            ->limit($this->porCorrida)
            ->get(['ocid', 'estado', 'fecha_fin', 'proveedores', 'nomenclatura', 'valor_referencial', 'url_documento', 'metodo_contratacion', 'entidad_nombre']);

        if ($contratos->isEmpty()) {
            Log::info('RefrescarEstadosContratosMayores: sin contratos almacenados, abortando.');
            return;
        }

        Log::info('RefrescarEstadosContratosMayores: iniciando', [
            'contratos_a_refrescar' => $contratos->count(),
            'por_corrida' => $this->porCorrida,
        ]);

        $actualizados = 0;
        $sinCambios = 0;
        $fallos = 0;
        $fallosConsecutivos = 0;
        $sinCambiosOcids = []; // se actualizan en bulk al final

        foreach ($contratos as $indice => $contrato) {
            $resultado = $service->fetchRecordPorOcid($contrato->ocid);

            if (!$resultado['success']) {
                $fallos++;
                $fallosConsecutivos++;

                // Salvaguarda: si la API está caída (15 fallos seguidos al
                // inicio del run), abortar para no quemar miles de llamadas.
                if ($fallosConsecutivos >= 15 && $indice < 50) {
                    Log::critical('RefrescarEstadosContratosMayores: API aparentemente caída, abortando', [
                        'fallos_consecutivos' => $fallosConsecutivos,
                        'procesados' => $indice + 1,
                    ]);
                    $this->aplicarBulkSinCambios($sinCambiosOcids);
                    return;
                }
                continue;
            }

            $fallosConsecutivos = 0;

            $fresh = $resultado['data'];
            $update = $this->columnasCambiadas($contrato, $fresh);

            if (empty($update)) {
                // Sin cambios: marcar updated_at en bulk al final
                $sinCambiosOcids[] = $contrato->ocid;
                $sinCambios++;
            } else {
                $update['updated_at'] = now();
                ContratoMayor::where('ocid', $contrato->ocid)->update($update);
                $actualizados++;

                Log::info('RefrescarEstadosContratosMayores: contrato actualizado', [
                    'ocid' => $contrato->ocid,
                    'campos' => array_keys($update),
                    'estado_nuevo' => $fresh['estado'] ?? '',
                ]);
            }

            // Progreso periódico para monitoreo nocturno
            if (($indice + 1) % 250 === 0) {
                Log::info('RefrescarEstadosContratosMayores: progreso', [
                    'procesados' => $indice + 1,
                    'actualizados' => $actualizados,
                    'fallos' => $fallos,
                ]);
            }

            usleep(150_000); // 150ms entre llamadas: ser amable con la API
        }

        $this->aplicarBulkSinCambios($sinCambiosOcids);

        Log::info('RefrescarEstadosContratosMayores: completado', [
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'fallos' => $fallos,
        ]);
    }

    /**
     * Marca updated_at en UNA sola query para todos los contratos sin cambios.
     * Evita ~2,000 UPDATEs individuales en la corrida nocturna.
     */
    protected function aplicarBulkSinCambios(array $ocids): void
    {
        if (empty($ocids)) {
            return;
        }

        ContratoMayor::whereIn('ocid', $ocids)->update(['updated_at' => now()]);
    }

    /**
     * Compara el registro en BD contra el estado fresco de la API.
     * Devuelve solo las columnas que cambiaron.
     */
    protected function columnasCambiadas(ContratoMayor $db, array $fresh): array
    {
        $update = [];

        if (($db->estado ?? '') !== ($fresh['estado'] ?? '')) {
            $update['estado'] = $fresh['estado'] ?? '';
        }

        $dbFin = $db->fecha_fin?->format('Y-m-d H:i:s');
        $freshFin = isset($fresh['fecha_fin']) && $fresh['fecha_fin'] !== null
            ? \Carbon\Carbon::parse($fresh['fecha_fin'])->format('Y-m-d H:i:s')
            : null;
        if ($dbFin !== $freshFin) {
            $update['fecha_fin'] = $fresh['fecha_fin'] ?? null;
        }

        $dbProv = json_encode($db->proveedores ?? []);
        $freshProv = json_encode($fresh['proveedores'] ?? []);
        if ($dbProv !== $freshProv) {
            $update['proveedores'] = $fresh['proveedores'] ?? [];
        }

        if (($db->nomenclatura ?? '') !== ($fresh['nomenclatura'] ?? '')) {
            $update['nomenclatura'] = $fresh['nomenclatura'] ?? '';
        }

        if ((float) ($db->valor_referencial ?? 0) !== (float) ($fresh['valor_referencial'] ?? 0)) {
            $update['valor_referencial'] = $fresh['valor_referencial'] ?? 0;
        }

        if (($db->url_documento ?? '') !== ($fresh['url_documento'] ?? '')) {
            $update['url_documento'] = $fresh['url_documento'] ?? '';
        }

        if (($db->metodo_contratacion ?? '') !== ($fresh['metodo_contratacion'] ?? '')) {
            $update['metodo_contratacion'] = $fresh['metodo_contratacion'] ?? '';
        }

        if (($db->entidad_nombre ?? '') !== ($fresh['entidad_nombre'] ?? '')) {
            $update['entidad_nombre'] = $fresh['entidad_nombre'] ?? '';
        }

        return $update;
    }
}
