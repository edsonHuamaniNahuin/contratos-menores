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
    public int $timeout = 900;

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

        foreach ($contratos as $contrato) {
            $resultado = $service->fetchRecordPorOcid($contrato->ocid);

            if (!$resultado['success']) {
                $fallos++;
                continue;
            }

            $fresh = $resultado['data'];

            $update = $this->columnasCambiadas($contrato, $fresh);

            if (empty($update)) {
                // Sin cambios de estado: solo actualizar updated_at
                // para que no vuelva a entrar en la próxima corrida pronto.
                ContratoMayor::where('ocid', $contrato->ocid)
                    ->update(['updated_at' => now()]);
                $sinCambios++;
                continue;
            }

            $update['updated_at'] = now();
            ContratoMayor::where('ocid', $contrato->ocid)->update($update);
            $actualizados++;

            Log::info('RefrescarEstadosContratosMayores: contrato actualizado', [
                'ocid' => $contrato->ocid,
                'campos' => array_keys($update),
                'estado_nuevo' => $fresh['estado'] ?? '',
            ]);

            usleep(150_000); // 150ms entre llamadas: ser amable con la API
        }

        Log::info('RefrescarEstadosContratosMayores: completado', [
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'fallos' => $fallos,
        ]);
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
