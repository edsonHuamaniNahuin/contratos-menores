<?php

namespace App\Services;

use App\Models\ContratoMayor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Importador de Procedimientos de Selección vía el buscador oficial del SEACE.
 *
 * Cubre el gap de latencia de la API OCDS del OECE (que puede publicar
 * releases con días/semanas de retraso). Descarga el Excel del día desde
 * el buscador público (prod2) usando un navegador headless, y sincroniza
 * contratos_mayores con dedupe por nomenclatura.
 */
class SeaceProcedimientosScraperService
{
    protected string $scriptPath;

    public function __construct()
    {
        // .cjs: el package.json del repo tiene "type": "module" y Node trataría
        // un .js como ES module (donde require() no existe).
        $this->scriptPath = base_path('scripts/scrape-procesos-seace.cjs');
    }

    /**
     * Resolver el binario de Node.js (por env, by name o ruta común).
     */
    protected function resolverNodeBin(): string
    {
        $env = env('SCRAPE_NODE_BIN');

        if ($env) {
            return $env;
        }

        $comunes = ['/usr/local/bin/node', '/usr/bin/node', 'node'];

        foreach ($comunes as $candidato) {
            if ($candidato === 'node' || is_executable($candidato)) {
                return $candidato;
            }
        }

        return 'node';
    }

    /**
     * Límite de filas del reporte del SEACE (el Excel se trunca a 500).
     * Umbral de división con margen (490): si el reporte llegó casi al
     * límite, repartir en mitades para no perder filas por truncamiento.
     */
    protected int $limiteFilasSeace = 490;

    /**
     * Ejecutar el scraping para el rango de fechas dado.
     *
     * Si el Excel llega al límite de filas del SEACE (500), el reporte pudo
     * truncarse: se reparte la búsqueda en 2 mitades (00:00-11:59 y
     * 12:00-23:59) y se combinan las filas.
     *
     * @return array{success: bool, nuevos: int, actualizados: int, count: int, message: string}
     */
    public function sincronizar(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $desde = $desde ?? now()->startOfDay();
        $hasta = $hasta ?? now()->copy()->endOfDay();

        $filas = $this->obtenerFilas($desde, $hasta);

        if ($filas === null) {
            return [
                'success' => false,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => 0,
                'message' => 'Fallo al obtener los procedimientos del SEACE.',
            ];
        }

        // El reporte del SEACE trunca a 500 filas: dividir en mitades para no perder datos
        if (count($filas) >= $this->limiteFilasSeace) {
            Log::warning('ScraperProcesos: reporte en límite de filas, dividiendo en mitades', [
                'count' => count($filas),
                'desde' => $desde->format('d/m/Y'),
                'hasta' => $hasta->format('d/m/Y'),
            ]);

            $mitad = $desde->copy()->addHours(12)->subSecond();

            $f1 = $this->obtenerFilas($desde, $mitad) ?? [];
            $f2 = $this->obtenerFilas($mitad->copy()->addSecond(), $hasta) ?? [];

            $filas = array_merge($f1, $f2);
        }

        if (empty($filas)) {
            Log::info('ScraperProcesos: sin procedimientos en el rango');

            return [
                'success' => true,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => 0,
                'message' => 'Sin procedimientos en el rango.',
            ];
        }

        return $this->importarFilas($filas);
    }

    /**
     * Ejecutar el script Node y devolver las filas del Excel (o null si falla).
     */
    protected function obtenerFilas(Carbon $desde, Carbon $hasta): ?array
    {
        $salida = storage_path('logs/scrape-procesos-seace.json');

        $nodeBin = $this->resolverNodeBin();

        $comando = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellarg($nodeBin),
            escapeshellarg($this->scriptPath),
            escapeshellarg($desde->format('d/m/Y')),
            escapeshellarg($hasta->format('d/m/Y')),
            escapeshellarg($salida)
        );

        Log::info('ScraperProcesos: ejecutando', [
            'desde' => $desde->format('d/m/Y'),
            'hasta' => $hasta->format('d/m/Y'),
        ]);

        $output = [];
        $exitCode = 0;
        exec($comando, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::error('ScraperProcesos: fallo', [
                'exit' => $exitCode,
                'output' => implode("\n", array_slice($output, -5)),
            ]);

            return null;
        }

        if (!file_exists($salida)) {
            Log::error('ScraperProcesos: sin archivo de salida');

            return null;
        }

        $payload = json_decode(file_get_contents($salida), true);

        if (!($payload['success'] ?? false)) {
            Log::error('ScraperProcesos: payload sin exito');

            return null;
        }

        return $payload['rows'] ?? [];
    }

    /**
     * Importar las filas con dedupe por nomenclatura.
     */
    protected function importarFilas(array $rows): array
    {
        $nuevos = 0;
        $actualizados = 0;

        foreach ($rows as $row) {
            $nomenclatura = $row['nomenclatura'] ?? '';

            if ($nomenclatura === '') {
                continue;
            }

            $fechaPublicacion = $this->parsearFecha($row['fecha'] ?? '');
            $valorReferencial = isset($row['vr']) && $row['vr'] !== null
                ? (float) $row['vr']
                : 0;

            $campos = [
                'entidad_nombre' => $row['entidad'] ?? 'N/A',
                'nomenclatura' => $nomenclatura,
                'descripcion_objeto' => $row['descripcion'] ?: null,
                'objeto_contratacion' => $row['objeto'] ?: null,
                'valor_referencial' => $valorReferencial,
                'moneda' => $row['moneda'] ?: 'Soles',
                'fecha_publicacion' => $fechaPublicacion,
            ];

            $existente = ContratoMayor::where('nomenclatura', $nomenclatura)->first();

            if ($existente) {
                $cambiados = [];

                foreach ($campos as $campo => $valor) {
                    $actual = $existente->{$campo};
                    $normalizado = $campo === 'fecha_publicacion'
                        ? optional($actual)?->format('Y-m-d H:i:s')
                        : $actual;

                    if ($campo === 'fecha_publicacion') {
                        if ($valor && $normalizado !== $valor->format('Y-m-d H:i:s')) {
                            $cambiados[$campo] = $valor;
                        }
                    } elseif ((string) ($normalizado ?? '') !== (string) ($valor ?? '')) {
                        // No sobreescribir campos con datos del OCDS por valores vacíos del Excel
                        if ($campo !== 'valor_referencial' || $valor > 0 || empty($actual)) {
                            $cambiados[$campo] = $valor;
                        }
                    }
                }

                if (!empty($cambiados)) {
                    $existente->update($cambiados);
                    $actualizados++;
                }

                continue;
            }

            ContratoMayor::create([
                'ocid' => 'ocds-scraped-' . md5($nomenclatura),
                'entidad_nombre' => $campos['entidad_nombre'],
                'nomenclatura' => $nomenclatura,
                'descripcion_objeto' => $campos['descripcion_objeto'],
                'objeto_contratacion' => $campos['objeto_contratacion'],
                'valor_referencial' => $campos['valor_referencial'],
                'moneda' => $campos['moneda'],
                'fecha_publicacion' => $campos['fecha_publicacion'],
                'estado' => 'CONVOCADO',
                // Array REAL: la columna es json con cast 'array' en el modelo;
                // insertar el string '[]' causaría doble codificación ('"[]"')
                // y rompería implode() en la vista (proveedores como string).
                'proveedores' => [],
                'datos_raw' => null,
            ]);

            $nuevos++;
        }

        Log::info('ScraperProcesos: importación completada', [
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
        ]);

        return [
            'success' => true,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'count' => count($rows),
            'message' => "{$nuevos} nuevos, {$actualizados} actualizados de " . count($rows) . ' procedimientos.',
        ];
    }

    /**
     * Parsear fecha del Excel (dd/mm/yyyy HH:mm o dd/mm/yyyy).
     */
    protected function parsearFecha(string $fecha): ?Carbon
    {
        $fecha = trim($fecha);

        if ($fecha === '') {
            return null;
        }

        foreach (['d/m/Y H:i', 'd/m/Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $fecha);
            } catch (\Throwable) {
                // siguiente formato
            }
        }

        try {
            return Carbon::parse($fecha);
        } catch (\Throwable) {
            return null;
        }
    }
}
