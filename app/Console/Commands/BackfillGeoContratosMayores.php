<?php

namespace App\Console\Commands;

use App\Models\ContratoMayor;
use App\Services\GeoResolverService;
use App\Services\SeaceMayoresService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Backfill de IDs geográficos (departamento/provincia/distrito) para
 * contratos mayores ya almacenados. NO llama a la API: extrae la
 * geografía del JSON `datos_raw` (cobertura 100% en producción) y
 * resuelve los IDs con GeoResolverService.
 *
 * Uso:
 *   php artisan backfill:geo-mayores            # toda la tabla (chunks de 500)
 *   php artisan backfill:geo-mayores --limit=2000
 */
class BackfillGeoContratosMayores extends Command
{
    protected $signature = 'backfill:geo-mayores {--limit=0 : Máximo de filas a procesar (0 = todas)} {--chunk=500 : Tamaño del chunk}';

    protected $description = 'Backfill departamento_id/provincia_id/distrito_id en contratos_mayores desde datos_raw';

    public function handle(GeoResolverService $geo, SeaceMayoresService $service): int
    {
        $query = ContratoMayor::query()
            ->whereNull('departamento_id')
            ->select(['id', 'ocid', 'datos_raw'])
            ->orderBy('id');

        $maxFilas = (int) $this->option('limit');

        $totalPendientes = ContratoMayor::whereNull('departamento_id')->count();
        $this->info("Backfill geo: {$totalPendientes} filas sin departamento_id.");

        $procesados = 0;
        $resueltos = 0;
        $sinDatos = 0;
        $sinGeo = 0;

        // chunkById: los UPDATEs dentro del chunk no desfasan la paginación
        // por offset (clásico bug de chunk() cuando se modifica la tabla).
        $query->chunkById((int) $this->option('chunk'), function ($chunk) use (&$procesados, &$resueltos, &$sinDatos, &$sinGeo, $geo, $service, $maxFilas) {
            foreach ($chunk as $contrato) {
                if ($maxFilas > 0 && $procesados >= $maxFilas) {
                    return false;
                }

                $procesados++;

                $raw = $contrato->datos_raw;
                if (is_string($raw)) {
                    $raw = json_decode($raw, true);
                }

                if (!is_array($raw) || empty($raw)) {
                    $sinDatos++;
                    continue;
                }

                $geoExtraido = $service->extraerGeografiaDeRelease($raw);

                if (empty($geoExtraido['departamento']) && empty($geoExtraido['provincia']) && empty($geoExtraido['distrito'])) {
                    $sinGeo++;
                    continue;
                }

                $ids = $geo->resolver(
                    $geoExtraido['departamento'],
                    $geoExtraido['provincia'],
                    $geoExtraido['distrito']
                );

                if ($ids['departamento_id'] === null) {
                    $sinGeo++;
                    continue;
                }

                ContratoMayor::where('id', $contrato->id)->update([
                    'departamento_id' => $ids['departamento_id'],
                    'provincia_id' => $ids['provincia_id'],
                    'distrito_id' => $ids['distrito_id'],
                    'updated_at' => now(),
                ]);

                $resueltos++;
            }

            $this->info("Progreso: {$procesados} procesados, {$resueltos} con geo, {$sinDatos} sin datos_raw, {$sinGeo} sin geografía.");
            Log::info('BackfillGeoContratosMayores: progreso', [
                'procesados' => $procesados,
                'resueltos' => $resueltos,
                'sin_datos_raw' => $sinDatos,
                'sin_geografia' => $sinGeo,
            ]);
        });

        $this->info("Backfill geo completado. Procesados: {$procesados} | Con geo: {$resueltos} | Sin datos: {$sinDatos} | Sin geo: {$sinGeo}");

        return self::SUCCESS;
    }
}
