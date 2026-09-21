<?php

namespace App\Services;

use App\Models\ContratoMayor;
use App\Models\ContratoMayorDocumento;
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

        $resultado = $this->obtenerFilas($desde, $hasta);

        if ($resultado === null) {
            return [
                'success' => false,
                'nuevos' => 0,
                'actualizados' => 0,
                'count' => 0,
                'message' => 'Fallo al obtener los procedimientos del SEACE.',
            ];
        }

        $filas = $resultado['rows'];
        $documentos = $resultado['documentos'];

        // El reporte del SEACE trunca a 500 filas: dividir en mitades para no perder datos
        if (count($filas) >= $this->limiteFilasSeace) {
            Log::warning('ScraperProcesos: reporte en límite de filas, dividiendo en mitades', [
                'count' => count($filas),
                'desde' => $desde->format('d/m/Y'),
                'hasta' => $hasta->format('d/m/Y'),
            ]);

            $mitad = $desde->copy()->addHours(12)->subSecond();

            $f1 = $this->obtenerFilas($desde, $mitad) ?? ['rows' => [], 'documentos' => []];
            $f2 = $this->obtenerFilas($mitad->copy()->addSecond(), $hasta) ?? ['rows' => [], 'documentos' => []];

            $filas = array_merge($f1['rows'], $f2['rows']);
            $documentos = array_merge($f1['documentos'], $f2['documentos']);
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

        return $this->importarFilas($filas, $documentos);
    }

    /**
     * Ejecutar el script Node y devolver las filas del Excel + documentos
     * de las fichas (o null si falla).
     */
    protected function obtenerFilas(Carbon $desde, Carbon $hasta): ?array
    {
        $salida = storage_path('logs/scrape-procesos-seace.json');

        $nodeBin = $this->resolverNodeBin();

        // Salto incremental: los procesos con ficha ya capturada (barrido
        // reciente) no se vuelven a navegar. La 2ª corrida diaria solo
        // procesa lo nuevo. DB = fuente de verdad; el scraper recibe la lista.
        $skipFile = storage_path('app/scrape-cubiertos.json');
        try {
            // Solo se salta lo que YA tiene documento cubierto: link del OCDS
            // o documentos capturados. Los procesos sin documento se revisan
            // de nuevo (pueden publicarlo después) — evita "no veo mi TDR".
            $cubiertos = ContratoMayor::whereNotNull('ficha_seace_id')
                ->where('updated_at', '>=', now()->subDays(7))
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNotNull('url_documento')->where('url_documento', '!=', '');
                    })->orWhereHas('documentos');
                })
                ->pluck('nomenclatura')
                ->map(fn ($n) => $this->normalizarNomenclatura($n))
                ->filter()
                ->unique()
                ->values()
                ->all();

            file_put_contents($skipFile, json_encode($cubiertos));
        } catch (\Throwable $e) {
            Log::warning('ScraperProcesos: no se pudo escribir la lista de cubiertos', [
                'error' => $e->getMessage(),
            ]);
        }

        $comando = sprintf(
            'SCRAPE_SKIP_FILE=%s %s %s %s %s %s 2>&1',
            escapeshellarg($skipFile),
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

        return [
            'rows' => $payload['rows'] ?? [],
            'documentos' => $payload['documentos'] ?? [],
        ];
    }

    /**
     * Importar las filas con dedupe por nomenclatura y guardar los documentos
     * capturados de las fichas (Bases, TDR, ...).
     */
    protected function importarFilas(array $rows, array $documentos = []): array
    {
        $nuevos = 0;
        $actualizados = 0;
        $docsGuardados = 0;

        // Documentos y ficha (id) por clave normalizada de nomenclatura
        $docsPorClave = [];
        $fichasPorClave = [];
        $itemsPorClave = [];
        foreach ($documentos as $entrada) {
            $clave = $entrada['clave'] ?? $this->normalizarNomenclatura($entrada['nomenclatura'] ?? '');
            if ($clave === '') {
                continue;
            }
            if (!empty($entrada['documentos'])) {
                $docsPorClave[$clave] = $entrada['documentos'];
            }
            if (!empty($entrada['fichaId'])) {
                $fichasPorClave[$clave] = $entrada['fichaId'];
            }
            if (!empty($entrada['items'])) {
                $itemsPorClave[$clave] = $entrada['items'];
            }
        }

        // Mapa normalizado (sin espacios ni signos): el scraper y la API OCDS
        // escriben la nomenclatura con puntuación distinta, y sin esto se
        // crearían duplicados del mismo proceso.
        $existentesNormalizados = [];
        foreach (ContratoMayor::get(['id', 'ocid', 'nomenclatura']) as $registro) {
            $clave = $this->normalizarNomenclatura($registro->nomenclatura);
            if ($clave !== '') {
                $existentesNormalizados[$clave] = $registro;
            }
        }

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

            $claveDoc = $this->normalizarNomenclatura($nomenclatura);
            $docs = $claveDoc !== '' ? ($docsPorClave[$claveDoc] ?? []) : [];
            $fichaId = $claveDoc !== '' ? ($fichasPorClave[$claveDoc] ?? null) : null;
            $items = $claveDoc !== '' ? ($itemsPorClave[$claveDoc] ?? []) : [];

            $existente = ContratoMayor::where('nomenclatura', $nomenclatura)->first();

            if (!$existente) {
                $clave = $this->normalizarNomenclatura($nomenclatura);
                $candidato = $clave !== '' ? ($existentesNormalizados[$clave] ?? null) : null;

                // Si el release OCDS ya importó el proceso, no duplicar:
                // los datos del OCDS (con documento) son la fuente completa.
                // Igual se le adjuntan los documentos capturados de la ficha.
                if ($candidato && !str_starts_with((string) $candidato->ocid, 'ocds-scraped-')) {
                    $docsGuardados += $this->guardarDocumentos($candidato, $docs, $claveDoc);
                    $this->guardarFichaId($candidato, $fichaId);
                    $this->guardarItems($candidato, $items);

                    continue;
                }

                $existente = $candidato;
            }

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

                $docsGuardados += $this->guardarDocumentos($existente, $docs, $claveDoc);
                $this->guardarFichaId($existente, $fichaId);
                $this->guardarItems($existente, $items);

                continue;
            }

            $contrato = ContratoMayor::create([
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

            $docsGuardados += $this->guardarDocumentos($contrato, $docs, $claveDoc);
            $this->guardarFichaId($contrato, $fichaId);
            $this->guardarItems($contrato, $items);

            $nuevos++;
        }

        Log::info('ScraperProcesos: importación completada', [
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'documentos_guardados' => $docsGuardados,
        ]);

        return [
            'success' => true,
            'nuevos' => $nuevos,
            'actualizados' => $actualizados,
            'count' => count($rows),
            'message' => "{$nuevos} nuevos, {$actualizados} actualizados de " . count($rows) . " procedimientos ({$docsGuardados} documentos).",
        ];
    }

    /**
     * Guardar los documentos capturados de la ficha (upsert por file_code).
     *
     * @return int cantidad de documentos guardados
     */
    protected function guardarDocumentos(ContratoMayor $contrato, array $docs, string $clave): int
    {
        $guardados = 0;

        foreach ($docs as $doc) {
            $fileCode = trim((string) ($doc['fileCode'] ?? ''));
            if ($fileCode === '') {
                continue;
            }

            try {
                ContratoMayorDocumento::updateOrCreate(
                    ['file_code' => $fileCode],
                    [
                        'contrato_mayor_id' => $contrato->id,
                        'nomenclatura_normalizada' => $clave,
                        'nombre' => mb_substr(trim((string) ($doc['nombre'] ?? '')) ?: 'Documento', 0, 250),
                        'etapa' => mb_substr(trim((string) ($doc['etapa'] ?? '')), 0, 250) ?: null,
                        'tipo' => mb_substr(trim((string) ($doc['tipo'] ?? '')), 0, 20) ?: null,
                        'filename' => mb_substr(trim((string) ($doc['filename'] ?? '')), 0, 250) ?: null,
                        'fecha_documento' => $this->parsearFechaDocumento($doc['fecha'] ?? ''),
                    ]
                );
                $guardados++;
            } catch (\Throwable $e) {
                Log::warning('ScraperProcesos: no se pudo guardar documento', [
                    'file_code' => $fileCode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $guardados;
    }

    /**
     * Guardar solo el `id` de la Ficha de Selección (deep-link público).
     * El contenido de la ficha no se almacena: se consulta en SEACE.
     */
    protected function guardarFichaId(ContratoMayor $contrato, ?string $fichaId): void
    {
        $fichaId = trim((string) $fichaId);

        if ($fichaId === '' || $contrato->ficha_seace_id === $fichaId) {
            return;
        }

        try {
            $contrato->update(['ficha_seace_id' => $fichaId]);
        } catch (\Throwable $e) {
            Log::warning('ScraperProcesos: no se pudo guardar ficha_seace_id', [
                'ocid' => $contrato->ocid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Guardar el resumen de ítems del proceso (máx. 50 filas, ya acotado por
     * el scraper). Solo se actualiza si cambió: evita escrituras inútiles.
     */
    protected function guardarItems(ContratoMayor $contrato, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $compacto = array_slice($items, 0, 50);

        if ($contrato->items_seace === $compacto) {
            return;
        }

        try {
            $contrato->update(['items_seace' => $compacto]);
        } catch (\Throwable $e) {
            Log::warning('ScraperProcesos: no se pudo guardar items_seace', [
                'ocid' => $contrato->ocid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parsear la fecha del documento de la ficha (dd/mm/yyyy HH:mm).
     */
    protected function parsearFechaDocumento(string $fecha): ?string
    {
        $fecha = trim($fecha);

        if ($fecha === '') {
            return null;
        }

        foreach (['d/m/Y H:i', 'd/m/Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $fecha)->format('Y-m-d');
            } catch (\Throwable) {
                // siguiente formato
            }
        }

        return null;
    }

    /**
     * Normalizar nomenclatura para comparar scraper vs OCDS:
     * minúsculas y solo caracteres alfanuméricos.
     */
    protected function normalizarNomenclatura(?string $nomenclatura): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($nomenclatura ?? '')) ?? '';
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
