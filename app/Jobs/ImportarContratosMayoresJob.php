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
use Illuminate\Support\Facades\DB;
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

    /**
     * Mapa nomenclatura → OCID sintético (ocds-scraped-*) para migrar
     * cuando la API OCDS publica el release real.
     */
    protected array $sinteticosPorNomenclatura = [];

    public function __construct(int $pagesToScan = 80, int $pageSize = 20)
    {
        $this->pagesToScan = $pagesToScan;
        $this->pageSize = $pageSize;
    }

    public function handle(SeaceMayoresService $service, \App\Services\GeoResolverService $geo): void
    {
        $cacheKey = 'contratos_mayores:ocids:' . now()->format('Y-m-d');

        // Mapa de OCIDs sintéticos del scraper (nomenclatura → ocid).
        // Cuando la API OCDS publica el release REAL de un proceso que el
        // scraper importó primero, se MIGRA el sintético en vez de duplicar.
        $this->sinteticosPorNomenclatura = ContratoMayor::where('ocid', 'like', 'ocds-scraped-%')
            ->pluck('ocid', 'nomenclatura')
            ->all();

        $storedOcids = Cache::get($cacheKey);

        if ($storedOcids === null) {
            $storedOcids = ContratoMayor::whereDate('created_at', now()->toDateString())
                ->pluck('ocid')
                ->toArray();

            Cache::put($cacheKey, $storedOcids, now()->addHours(26));
        }

        $storedMap = array_flip($storedOcids);

        // Mapa de registros existentes en BD (no solo de hoy) para detectar cambios.
        // Clave: ocid → campos mutables actuales (para comparar y refrescar estado).
        $dbMap = ContratoMayor::get([
            'ocid', 'entidad_nombre', 'nomenclatura', 'estado', 'fecha_fin',
            'valor_referencial', 'proveedores', 'url_documento', 'metodo_contratacion',
            'departamento_id', 'provincia_id', 'distrito_id',
        ])->keyBy('ocid')->map(fn ($r) => [
            'estado' => $r->estado,
            'fecha_fin' => optional($r->fecha_fin)->format('Y-m-d H:i:s'),
            'proveedores' => $r->proveedores ?? '[]',
            'entidad_nombre' => $r->entidad_nombre,
            'nomenclatura' => $r->nomenclatura,
            'valor_referencial' => (float) $r->valor_referencial,
            'url_documento' => $r->url_documento,
            'metodo_contratacion' => $r->metodo_contratacion,
            'departamento_id' => $r->departamento_id,
            'provincia_id' => $r->provincia_id,
            'distrito_id' => $r->distrito_id,
        ])->toArray();

        Log::info('ImportarContratosMayores: iniciando', [
            'pages' => $this->pagesToScan,
            'page_size' => $this->pageSize,
            'stored_today' => count($storedOcids),
            'cache_key' => $cacheKey,
        ]);

        $totalRecibidos = 0;
        $nuevos = 0;
        $migrados = 0;
        $actualizados = 0;
        $sinCambios = 0;
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

                $batchNuevos = [];
                foreach ($data as $contrato) {
                    $ocid = $contrato['ocid'] ?? null;
                    if (empty($ocid)) {
                        continue;
                    }

                    $mapped = $this->mapearCampos($contrato, $geo);

                    // ¿Existe en BD (hoy o días anteriores)?
                    if (isset($dbMap[$ocid])) {
                        // Comparar campos mutables: actualizar SOLO los que cambiaron
                        $cambiados = $this->camposCambiados($dbMap[$ocid], $mapped);
                        if (!empty($cambiados)) {
                            $this->actualizarContrato($ocid, $mapped, $cambiados);
                            $actualizados++;
                        } else {
                            $sinCambios++;
                        }
                        // Mantener el map de BD al día para no re-actualizar en este run
                        $dbMap[$ocid] = [
                            'estado' => $mapped['estado'],
                            'fecha_fin' => $mapped['fecha_fin'],
                            'proveedores' => $mapped['proveedores'],
                            'entidad_nombre' => $mapped['entidad_nombre'],
                            'nomenclatura' => $mapped['nomenclatura'],
                            'valor_referencial' => (float) $mapped['valor_referencial'],
                            'url_documento' => $mapped['url_documento'],
                            'metodo_contratacion' => $mapped['metodo_contratacion'],
                            'departamento_id' => $mapped['departamento_id'],
                            'provincia_id' => $mapped['provincia_id'],
                            'distrito_id' => $mapped['distrito_id'],
                        ];
                        continue;
                    }

                    // ¿Existe un OCID sintético del scraper con la misma nomenclatura?
                    // El release REAL llegó → migrar el sintético (no duplicar).
                    $sintetico = $this->sinteticosPorNomenclatura[$mapped['nomenclatura'] ?? ''] ?? null;

                    if ($sintetico && $sintetico !== $ocid) {
                        $this->migrarSinteticoAReal($sintetico, $ocid, $mapped);
                        unset($this->sinteticosPorNomenclatura[$mapped['nomenclatura'] ?? '']);
                        $dbMap[$ocid] = true;
                        $migrados++;
                        continue;
                    }

                    // Nuevo: insertar
                    $batchNuevos[] = $mapped;
                    $dbMap[$ocid] = true;
                    $nuevosOcids[] = $ocid;
                }

                if (!empty($batchNuevos)) {
                    try {
                        ContratoMayor::insert($batchNuevos);
                        $nuevos += count($batchNuevos);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        $insertados = $this->insertarUnoPorUnoIgnorandoDuplicados($batchNuevos);
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
            'migrados_sinteticos' => $migrados,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'total_hoy' => count($storedOcids) + $nuevos,
        ]);
    }

    /**
     * Migrar un registro creado por el scraper (OCID sintético) al OCID real
     * del release OCDS. Reasigna referencias (vigilancia, seguimientos,
     * notificaciones) y actualiza los campos con los datos completos.
     */
    protected function migrarSinteticoAReal(string $ocidSintetico, string $ocidReal, array $mapped): void
    {
        // Reasignar referencias que apuntan al sintético
        DB::table('vigilancia_adjudicaciones')->where('ocid', $ocidSintetico)->update(['ocid' => $ocidReal]);
        DB::table('contrato_seguimientos_mayores')->where('ocid', $ocidSintetico)->update(['ocid' => $ocidReal]);

        $realYaNotificado = DB::table('notified_processes')->where('seace_proceso_id', $ocidReal)->exists();
        if (!$realYaNotificado) {
            DB::table('notified_processes')->where('seace_proceso_id', $ocidSintetico)
                ->update(['seace_proceso_id' => $ocidReal]);
        }

        // Actualizar el registro con el OCID real y los datos completos del release
        $campos = array_intersect_key($mapped, array_flip([
            'entidad_nombre', 'entidad_ruc', 'entidad_direccion', 'nomenclatura',
            'descripcion_objeto', 'objeto_contratacion', 'valor_referencial',
            'moneda', 'fecha_publicacion', 'fecha_inicio', 'fecha_fin',
            'metodo_contratacion', 'estado', 'codigo_snip', 'proveedores',
            'url_documento', 'cuantia', 'datos_raw',
            'departamento_id', 'provincia_id', 'distrito_id',
        ]));

        $campos['ocid'] = $ocidReal;

        ContratoMayor::where('ocid', $ocidSintetico)->update($campos);

        Log::info('ImportarContratosMayores: sintético migrado a OCID real', [
            'sintetico' => $ocidSintetico,
            'real' => $ocidReal,
            'nomenclatura' => $mapped['nomenclatura'] ?? null,
        ]);
    }

    /**
     * Compara campos mutables del registro en BD contra lo que trae la API.
     * Devuelve la lista de columnas que cambiaron (vacía = sin cambios).
     */
    protected function camposCambiados(array $db, array $nuevo): array
    {
        $cambiados = [];

        if (($db['estado'] ?? '') !== ($nuevo['estado'] ?? '')) {
            $cambiados[] = 'estado';
        }

        $dbFechaFin = $db['fecha_fin'] ?? null;
        $nuevoFechaFin = $nuevo['fecha_fin'] ?? null;
        if ($dbFechaFin !== $nuevoFechaFin) {
            $cambiados[] = 'fecha_fin';
        }

        // Normalizar proveedores: BD viene como array (cast), API como JSON string
        $dbProv = is_array($db['proveedores'] ?? null)
            ? json_encode($db['proveedores'])
            : (string) ($db['proveedores'] ?? '[]');
        $nuevoProv = is_array($nuevo['proveedores'] ?? null)
            ? json_encode($nuevo['proveedores'])
            : (string) ($nuevo['proveedores'] ?? '[]');
        if ($dbProv !== $nuevoProv) {
            $cambiados[] = 'proveedores';
        }

        if (($db['entidad_nombre'] ?? '') !== ($nuevo['entidad_nombre'] ?? '')) {
            $cambiados[] = 'entidad_nombre';
        }

        if (($db['nomenclatura'] ?? '') !== ($nuevo['nomenclatura'] ?? '')) {
            $cambiados[] = 'nomenclatura';
        }

        if ((float) ($db['valor_referencial'] ?? 0) !== (float) ($nuevo['valor_referencial'] ?? 0)) {
            $cambiados[] = 'valor_referencial';
        }

        if (($db['url_documento'] ?? '') !== ($nuevo['url_documento'] ?? '')) {
            $cambiados[] = 'url_documento';
        }

        if (($db['metodo_contratacion'] ?? '') !== ($nuevo['metodo_contratacion'] ?? '')) {
            $cambiados[] = 'metodo_contratacion';
        }

        // Geografía: si el ID resuelto difiere del guardado (o faltaba), actualizar
        if ((int) ($db['departamento_id'] ?? 0) !== (int) ($nuevo['departamento_id'] ?? 0)) {
            $cambiados[] = 'departamento_id';
        }
        if ((int) ($db['provincia_id'] ?? 0) !== (int) ($nuevo['provincia_id'] ?? 0)) {
            $cambiados[] = 'provincia_id';
        }
        if ((int) ($db['distrito_id'] ?? 0) !== (int) ($nuevo['distrito_id'] ?? 0)) {
            $cambiados[] = 'distrito_id';
        }

        return $cambiados;
    }

    /**
     * Actualiza SOLO las columnas que cambiaron en un contrato existente.
     * No toca created_at ni datos_raw (históricos/pesados).
     */
    protected function actualizarContrato(string $ocid, array $mapped, array $cambiados): void
    {
        $todoMap = [
            'estado' => $mapped['estado'] ?? '',
            'fecha_fin' => $mapped['fecha_fin'] ?? null,
            'proveedores' => $mapped['proveedores'] ?? '[]',
            'entidad_nombre' => $mapped['entidad_nombre'] ?? '',
            'nomenclatura' => $mapped['nomenclatura'] ?? '',
            'valor_referencial' => $mapped['valor_referencial'] ?? 0,
            'url_documento' => $mapped['url_documento'] ?? '',
            'metodo_contratacion' => $mapped['metodo_contratacion'] ?? '',
            'departamento_id' => $mapped['departamento_id'] ?? null,
            'provincia_id' => $mapped['provincia_id'] ?? null,
            'distrito_id' => $mapped['distrito_id'] ?? null,
        ];

        $update = array_intersect_key($todoMap, array_flip($cambiados));
        $update['updated_at'] = now();

        ContratoMayor::where('ocid', $ocid)->update($update);

        Log::info('ImportarContratosMayores: contrato actualizado', [
            'ocid' => $ocid,
            'campos' => $cambiados,
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

    protected function mapearCampos(array $c, \App\Services\GeoResolverService $geo): array
    {
        $parseDate = function ($val): ?string {
            if (empty($val)) return null;
            try {
                return \Carbon\Carbon::parse($val)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        // Resolver geografía a IDs de tablas maestras
        $geoIds = $geo->resolver(
            (string) ($c['departamento'] ?? ''),
            (string) ($c['provincia'] ?? ''),
            (string) ($c['distrito'] ?? '')
        );

        return [
            'ocid' => $c['ocid'] ?? '',
            'entidad_nombre' => $c['entidad_nombre'] ?? '',
            'entidad_ruc' => $c['entidad_ruc'] ?? '',
            'entidad_direccion' => $c['entidad_direccion'] ?? '',
            'departamento_id' => $geoIds['departamento_id'],
            'provincia_id' => $geoIds['provincia_id'],
            'distrito_id' => $geoIds['distrito_id'],
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
