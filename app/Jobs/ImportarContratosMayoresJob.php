<?php

namespace App\Jobs;

use App\Models\ContratoMayor;
use App\Services\SeaceMayoresService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Importa contratos mayores desde la API OCDS y los persiste en BD.
 *
 * Optimización anti-duplicados:
 *  1. Cache en memoria (Redis/file) con los OCIDs ya almacenados HOY.
 *  2. Unique constraint en BD (columna `ocid`) como capa final de seguridad.
 *
 * Sin cache → se carga desde la BD la lista de OCIDs del día y se cachea.
 * En cada escaneo se filtra contra el cache, evitando 1 query por registro.
 *
 * Schedule: cada 1.5 horas (90 minutos).
 */
class ImportarContratosMayoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    protected int $pagesToScan;
    protected int $pageSize;

    public function __construct(int $pagesToScan = 80, int $pageSize = 20)
    {
        $this->pagesToScan = $pagesToScan;
        $this->pageSize = $pageSize;
    }

    public function handle(SeaceMayoresService $service): void
    {
        $cacheKey = 'contratos_mayores:ocids:' . now()->format('Y-m-d');

        $storedOcids = Cache::get($cacheKey);

        if ($storedOcids === null) {
            $storedOcids = ContratoMayor::whereDate('created_at', now()->toDateString())
                ->pluck('ocid')
                ->toArray();

            Cache::put($cacheKey, $storedOcids, now()->addHours(26));
        }

        $storedMap = array_flip($storedOcids);

        Log::info('ImportarContratosMayores: iniciando', [
            'pages' => $this->pagesToScan,
            'page_size' => $this->pageSize,
            'stored_today' => count($storedOcids),
            'cache_key' => $cacheKey,
        ]);

        $totalRecibidos = 0;
        $nuevos = 0;
        $omitidos = 0;
        $errores = 0;
        $nuevosOcids = [];

        for ($page = 1; $page <= $this->pagesToScan; $page++) {
            try {
                $resultado = $service->fetchFromApi($page, $this->pageSize);

                if (!$resultado['success']) {
                    $errores++;
                    continue;
                }

                $data = $resultado['data'] ?? [];
                $totalRecibidos += count($data);

                $batch = [];
                foreach ($data as $contrato) {
                    $ocid = $contrato['ocid'] ?? null;
                    if (empty($ocid)) {
                        continue;
                    }

                    if (isset($storedMap[$ocid])) {
                        $omitidos++;
                        continue;
                    }

                    $batch[] = $this->mapearCampos($contrato);
                    $storedMap[$ocid] = true;
                    $nuevosOcids[] = $ocid;
                }

                if (!empty($batch)) {
                    try {
                        ContratoMayor::insert($batch);
                        $nuevos += count($batch);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        $insertados = $this->insertarUnoPorUnoIgnorandoDuplicados($batch);
                        $nuevos += $insertados;
                    }
                }

                if (empty($resultado['pagination']['has_next'] ?? null)) {
                    Log::info('ImportarContratosMayores: fin de datos (no hay más páginas)', ['page' => $page]);
                    break;
                }

                usleep(100_000);
            } catch (\Exception $e) {
                $errores++;
                Log::error('ImportarContratosMayores: excepción en página', [
                    'page' => $page,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($nuevosOcids)) {
            $merged = array_merge($storedOcids, $nuevosOcids);
            Cache::put($cacheKey, $merged, now()->addHours(26));
        }

        Log::info('ImportarContratosMayores: completado', [
            'pages_scanned' => $page - 1 - $errores,
            'pages_error' => $errores,
            'total_api' => $totalRecibidos,
            'nuevos' => $nuevos,
            'omitidos_cache' => $omitidos,
            'total_hoy' => count($storedOcids) + $nuevos,
        ]);
    }

    protected function insertarUnoPorUnoIgnorandoDuplicados(array $batch): int
    {
        $count = 0;
        foreach ($batch as $row) {
            try {
                ContratoMayor::insert($row);
                $count++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            }
        }
        return $count;
    }

    protected function mapearCampos(array $c): array
    {
        $parseDate = function ($val): ?string {
            if (empty($val)) return null;
            try {
                return \Carbon\Carbon::parse($val)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        return [
            'ocid' => $c['ocid'] ?? '',
            'entidad_nombre' => $c['entidad_nombre'] ?? '',
            'entidad_ruc' => $c['entidad_ruc'] ?? '',
            'entidad_direccion' => $c['entidad_direccion'] ?? '',
            'nomenclatura' => $c['nomenclatura'] ?? '',
            'descripcion_objeto' => $c['descripcion_objeto'] ?? '',
            'objeto_contratacion' => $c['objeto_contratacion'] ?? '',
            'valor_referencial' => $c['valor_referencial'] ?? 0,
            'cuantia' => $c['cuantia'] ?? null,
            'moneda' => $c['moneda'] ?? 'PEN',
            'fecha_publicacion' => $parseDate($c['fecha_publicacion'] ?? null),
            'fecha_inicio' => $parseDate($c['fecha_inicio'] ?? null),
            'fecha_fin' => $parseDate($c['fecha_fin'] ?? null),
            'metodo_contratacion' => $c['metodo_contratacion'] ?? '',
            'estado' => $c['estado'] ?? '',
            'codigo_snip' => $c['codigo_snip'] ?? '',
            'proveedores' => is_array($c['proveedores'] ?? null) ? json_encode($c['proveedores']) : ($c['proveedores'] ?? '[]'),
            'url_documento' => $c['url_documento'] ?? '',
            'datos_raw' => is_array($c['datos_raw'] ?? null) ? json_encode($c['datos_raw']) : ($c['datos_raw'] ?? '[]'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
