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
        $this->scriptPath = base_path('scripts/scrape-procesos-seace.js');
    }

    /**
     * Ejecutar el scraping para el rango de fechas dado.
     *
     * @return array{success: bool, nuevos: int, actualizados: int, count: int, message: string}
     */
    public function sincronizar(?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $desde = $desde ?? now()->startOfDay();
        $hasta = $hasta ?? now()->copy()->endOfDay();

        $salida = storage_path('logs/scrape-procesos-seace.json');

        $comando = sprintf(
            '%s %s %s %s %s 2>&1',
            escapeshellarg(PHP_BINARY === '' ? 'node' : 'node'),
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

            return [
                'success' => false,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => 0,
                'message' => 'Fallo al ejecutar el scraper (exit ' . $exitCode . '): ' . implode(' | ', array_slice($output, -3)),
            ];
        }

        if (!file_exists($salida)) {
            Log::error('ScraperProcesos: sin archivo de salida');

            return [
                'success' => false,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => 0,
                'message' => 'No se generó el archivo de salida del scraper.',
            ];
        }

        $payload = json_decode(file_get_contents($salida), true);

        if (!($payload['success'] ?? false) || empty($payload['rows'])) {
            Log::info('ScraperProcesos: sin procedimientos en el rango', [
                'count' => $payload['count'] ?? 0,
            ]);

            return [
                'success' => true,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => $payload['count'] ?? 0,
                'message' => 'Sin procedimientos en el rango.',
            ];
        }

        return $this->importarFilas($payload['rows']);
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
                'proveedores' => '[]',
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
     * Parsear fecha del Excel (dd/mm/yyyy HH:mm).
     */
    protected function parsearFecha(string $fecha): ?Carbon
    {
        if ($fecha === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i', $fecha);
        } catch (\Throwable) {
            try {
                return Carbon::parse($fecha);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
