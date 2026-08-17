<?php

namespace App\Services;

use App\Models\ContratoMayor;
use Illuminate\Support\Facades\Log;

/**
 * Exporta Contratos Mayores a Excel (XLSX nativo vía XlsxWriterService,
 * sin librerías externas) usando las 2 plantillas del cliente:
 *
 *  - 'analitico'   : "Data Seace Analítico" (30 columnas, análisis comercial)
 *  - 'seguimiento': "Seguimiento SEACE" (12 columnas, seguimiento de postores)
 *
 * Los filtros son los mismos del buscador (reutiliza
 * SeaceMayoresService::aplicarFiltros) y SIEMPRE se acota por ventana de
 * publicación (hoy / 7 días / 30 días) para no exportar toda la base.
 *
 * Genera un .xlsx real (OOXML), no SpreadsheetML, para que Excel no muestre
 * el aviso de "el formato y la extensión no coinciden".
 */
class ExportarContratosMayoresService
{
    public const PLANTILLA_ANALITICO = 'analitico';
    public const PLANTILLA_SEGUIMIENTO = 'seguimiento';

    public const VENTANA_HOY = 'hoy';
    public const VENTANA_7D = '7d';
    public const VENTANA_30D = '30d';

    public const VENTANAS = [
        self::VENTANA_HOY => 'Hoy',
        self::VENTANA_7D => 'Últimos 7 días',
        self::VENTANA_30D => 'Últimos 30 días',
    ];

    public function __construct(
        protected SeaceMayoresService $seace,
        protected XlsxWriterService $xlsx,
    ) {
    }

    /**
     * @return array{success: bool, contenido?: string, filename?: string, total?: int, message?: string}
     */
    public function exportar(array $filtros, string $plantilla, string $ventana = self::VENTANA_30D): array
    {
        try {
            if (!in_array($plantilla, [self::PLANTILLA_ANALITICO, self::PLANTILLA_SEGUIMIENTO], true)) {
                return ['success' => false, 'message' => 'Plantilla no válida.'];
            }

            if (!array_key_exists($ventana, self::VENTANAS)) {
                return ['success' => false, 'message' => 'Rango de fechas no válido.'];
            }

            $query = $this->queryBase();

            $this->seace->aplicarFiltros($query, $filtros);
            $this->aplicarVentana($query, $ventana);
            $query->orderBy('fecha_publicacion', 'desc');

            $total = $query->count();

            if ($total === 0) {
                return ['success' => false, 'message' => 'No hay resultados con los filtros actuales.', 'total' => 0];
            }

            if ($plantilla === self::PLANTILLA_ANALITICO) {
                [$filas, $nombreHoja] = $this->filasPlantillaAnalitico($query);
            } else {
                [$filas, $nombreHoja] = $this->filasPlantillaSeguimiento($query);
            }

            $contenido = $this->xlsx->generar($nombreHoja, $filas);

            $fecha = now()->format('Y-m-d');
            $filename = $plantilla === self::PLANTILLA_ANALITICO
                ? "data-seace-analitico-{$ventana}-{$fecha}.xlsx"
                : "seguimiento-seace-{$ventana}-{$fecha}.xlsx";

            return [
                'success' => true,
                'contenido' => $contenido,
                'filename' => $filename,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('ExportarContratosMayores: excepción', [
                'plantilla' => $plantilla,
                'ventana' => $ventana,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Error al generar la exportación: ' . $e->getMessage()];
        }
    }

    /**
     * Cuenta cuántos registros se exportarían con los filtros + ventana dados.
     * Se usa para mostrar los totales en el dropdown del botón Exportar.
     */
    public function contar(array $filtros, string $ventana): int
    {
        try {
            if (!array_key_exists($ventana, self::VENTANAS)) {
                return 0;
            }

            $query = $this->queryBase();
            $this->seace->aplicarFiltros($query, $filtros);
            $this->aplicarVentana($query, $ventana);

            return $query->count();
        } catch (\Exception $e) {
            Log::error('ExportarContratosMayores: error al contar', [
                'ventana' => $ventana,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Query base compartida por exportar() y contar().
     */
    protected function queryBase()
    {
        return ContratoMayor::query()
            ->with(['departamento', 'provincia', 'distrito'])
            ->select([
                'id', 'ocid', 'entidad_nombre', 'entidad_ruc', 'nomenclatura',
                'descripcion_objeto', 'objeto_contratacion', 'valor_referencial',
                'moneda', 'fecha_publicacion', 'fecha_inicio', 'fecha_fin',
                'metodo_contratacion', 'estado', 'proveedores', 'datos_raw',
                'departamento_id', 'provincia_id', 'distrito_id',
            ]);
    }

    /**
     * Ventana temporal de publicación: hoy, últimos 7 días o últimos 30 días.
     * Aplica SIEMPRE (aunque no haya filtros activos) para acotar el volumen.
     */
    protected function aplicarVentana($query, string $ventana): void
    {
        switch ($ventana) {
            case self::VENTANA_HOY:
                $query->whereDate('fecha_publicacion', now()->toDateString());
                break;
            case self::VENTANA_7D:
                $query->where('fecha_publicacion', '>=', now()->subDays(7)->startOfDay());
                break;
            case self::VENTANA_30D:
            default:
                $query->where('fecha_publicacion', '>=', now()->subDays(30)->startOfDay());
                break;
        }
    }

    // ══════════════════════════════════════════════════════════
    // Plantilla Analítico (30 columnas)
    // ══════════════════════════════════════════════════════════

    /**
     * @return array{0: array<int, array{v: mixed, t: string}>, 1: string} [filas, nombreHoja]
     */
    protected function filasPlantillaAnalitico($query): array
    {
        $s = fn ($v) => ['v' => $v, 't' => 'string'];
        $h = fn ($v) => ['v' => $v, 't' => 'string', 's' => 'header-verde'];
        $n = fn ($v) => ['v' => $v, 't' => 'number'];

        $headers = [
            'ENTIDAD', 'NOMENCLATURA', 'SEGMENTO', 'DEPARTAMENTO', 'ATENCIÓN',
            'DESCRIPCION PROCESO', 'CONVOCATORIA', 'REGISTRO DE PARTICIPANTES',
            'FORMULACION DE CONSULTAS', 'ABSOLUCION DE CONSULTAS', 'INTEGRACION DE BASES',
            'PRESENTACION DE OFERTAS', 'EVALUACION Y CALIFICACION', 'OTORGAMIENTO DE LA BUENA PRO',
            'STATUS', 'USO', 'MODELO', 'EQUIPAMIENTO', 'Q', 'VALOR ESTIMADO',
            'MARCA ADJUDICADA', 'GANADOR', 'VALOR ADJUDICADO', 'PRECIO DE LISTA',
            'PRECIO UNITARIO SOLES', 'PRECIO UNITARIO USD', 'PROVEEDOR ADJUDICADO',
            'MES PROCESO', 'AÑO PROCESO', '',
        ];

        $filas = [array_map($h, $headers)];

        $query->chunk(500, function ($chunk) use (&$filas, $s, $n) {
            foreach ($chunk as $c) {
                $extra = $this->extraerDeDatosRaw($c->datos_raw);

                $fechaPub = $c->fecha_publicacion;
                $proveedorAdjudicado = $extra['proveedor_adjudicado'] !== ''
                    ? $extra['proveedor_adjudicado']
                    : (is_array($c->proveedores) && !empty($c->proveedores) ? (string) $c->proveedores[0] : '');

                $filas[] = [
                    $s($c->entidad_nombre),
                    $s($c->nomenclatura),
                    $s($this->inferirSegmento($c->entidad_nombre)),
                    $s($c->departamento?->nombre ?? ''),
                    $s($c->provincia?->nombre ?? ''),
                    $s($c->descripcion_objeto),
                    $s($fechaPub ? $fechaPub->format('d/m/Y') : ''),
                    $s(''), // registro participantes (no disponible)
                    $s(''), // formulación consultas
                    $s(''), // absolución consultas
                    $s(''), // integración bases
                    $s(''), // presentación ofertas
                    $s(''), // evaluación y calificación
                    $s($extra['award_fecha']),
                    $s($this->mapearStatusComercial($c->estado)),
                    $s(''), // uso
                    $s(''), // modelo
                    $s(''), // equipamiento
                    $s(''), // cantidad
                    $n((float) ($c->valor_referencial ?? 0)),
                    $s(''), // marca adjudicada
                    $s($extra['ganador_nombre'] ?? ''), // ganador
                    $extra['award_valor'] !== '' && $extra['award_valor'] !== null
                        ? $n((float) $extra['award_valor'])
                        : $s(''),
                    $s(''), // precio de lista
                    $s(''), // precio unitario soles
                    $s(''), // precio unitario usd
                    $s($proveedorAdjudicado),
                    $s($fechaPub ? $fechaPub->format('m') : ''),
                    $s($fechaPub ? $fechaPub->format('Y') : ''),
                    $s(''),
                ];
            }
        });

        return [$filas, 'Data Seace Analitico'];
    }

    // ══════════════════════════════════════════════════════════
    // Plantilla Seguimiento (12 columnas)
    // ══════════════════════════════════════════════════════════

    /**
     * @return array{0: array<int, array{v: mixed, t: string}>, 1: string} [filas, nombreHoja]
     */
    protected function filasPlantillaSeguimiento($query): array
    {
        $s = fn ($v) => ['v' => $v, 't' => 'string'];
        $h = fn ($v) => ['v' => $v, 't' => 'string', 's' => 'header-verde'];
        $n = fn ($v) => ['v' => $v, 't' => 'number'];

        $headers = [
            'N°', 'Nombre o Sigla de la Entidad', 'Fecha y Hora de Publicacion',
            'Nomenclatura', 'Descripción de Objeto', 'CANTIDAD  EQUIPOS',
            'Objeto de Contratación', 'Cuantía de contratación', 'Moneda',
            'POSTORES', 'GANADOR', 'OBSERVACIONES',
        ];

        $filas = [array_map($h, $headers)];

        $nro = 0;
        $query->chunk(500, function ($chunk) use (&$filas, &$nro, $s, $n) {
            foreach ($chunk as $c) {
                $nro++;
                $extra = $this->extraerDeDatosRaw($c->datos_raw);

                $postores = '';
                $proveedores = is_array($c->proveedores) ? $c->proveedores : [];
                if (count($proveedores) > 0) {
                    $postores = count($proveedores) . ' postor(es): ' . implode(' | ', array_slice($proveedores, 0, 5));
                }

                $ganador = $extra['proveedor_adjudicado'] !== ''
                    ? $extra['proveedor_adjudicado']
                    : ($extra['ganador_nombre'] ?? '');

                $filas[] = [
                    $n($nro),
                    $s($c->entidad_nombre),
                    $s($c->fecha_publicacion ? $c->fecha_publicacion->format('d/m/Y H:i') : ''),
                    $s($c->nomenclatura),
                    $s($c->descripcion_objeto),
                    $s(''), // cantidad equipos (dato del cliente)
                    $s($extra['item_descripcion'] ?? ''),
                    $n((float) ($c->valor_referencial ?? 0)),
                    $s($this->nombreMoneda($c->moneda)),
                    $s($postores),
                    $s($ganador),
                    $s(''), // observaciones
                ];
            }
        });

        return [$filas, 'Seguimiento SEACE'];
    }

    // ══════════════════════════════════════════════════════════
    // Helpers de datos
    // ══════════════════════════════════════════════════════════

    /**
     * Extrae del JSON datos_raw los campos que no están en columnas planas:
     * fecha y valor del primer award, ganador con RUC, descripción del primer ítem.
     */
    protected function extraerDeDatosRaw($datosRaw): array
    {
        $vacio = [
            'award_fecha' => '',
            'award_valor' => '',
            'ganador_nombre' => '',
            'proveedor_adjudicado' => '',
            'item_descripcion' => '',
        ];

        if (is_string($datosRaw)) {
            $datosRaw = json_decode($datosRaw, true);
        }
        if (!is_array($datosRaw)) {
            return $vacio;
        }

        $award = $datosRaw['awards'][0] ?? [];

        $awardFecha = '';
        if (!empty($award['date'])) {
            try {
                $awardFecha = \Carbon\Carbon::parse($award['date'])->format('d/m/Y');
            } catch (\Throwable $e) {
                $awardFecha = (string) $award['date'];
            }
        }

        $awardValor = $award['value']['amount'] ?? '';

        $proveedorAdjudicado = '';
        $ganadorNombre = '';
        foreach ($award['suppliers'] ?? [] as $supplier) {
            $nombre = (string) ($supplier['name'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $ruc = '';
            foreach ($supplier['additionalIdentifiers'] ?? [] as $id) {
                if (($id['scheme'] ?? '') === 'PE-RUC') {
                    $ruc = (string) ($id['id'] ?? '');
                    break;
                }
            }
            $ganadorNombre = $nombre;
            $proveedorAdjudicado = $ruc !== '' ? "{$ruc} - {$nombre}" : $nombre;
            break;
        }

        $itemDescripcion = '';
        $items = $datosRaw['tender']['items'] ?? [];
        if (!empty($items[0]['description'])) {
            $itemDescripcion = (string) $items[0]['description'];
        }

        return [
            'award_fecha' => $awardFecha,
            'award_valor' => $awardValor,
            'ganador_nombre' => $ganadorNombre,
            'proveedor_adjudicado' => $proveedorAdjudicado,
            'item_descripcion' => $itemDescripcion,
        ];
    }

    /**
     * Infiere el SEGMENTO comercial desde el nombre de la entidad
     * (el template del cliente usa MUNICIPALIDAD / ORGANISMOS / PNP...).
     */
    protected function inferirSegmento(?string $entidad): string
    {
        $entidad = mb_strtoupper((string) $entidad, 'UTF-8');

        if (str_contains($entidad, 'MUNICIPALIDAD')) {
            return 'MUNICIPALIDAD';
        }
        if (str_contains($entidad, 'GOBIERNO REGIONAL')) {
            return 'GOBIERNO REGIONAL';
        }
        if (str_contains($entidad, 'POLICIA') || str_contains($entidad, 'PNP') || str_contains($entidad, 'MININTER')) {
            return 'PNP';
        }
        if (str_contains($entidad, 'MINISTERIO')) {
            return 'MINISTERIO';
        }
        if (str_contains($entidad, 'SERNANP') || str_contains($entidad, 'AGRO RURAL') || str_contains($entidad, 'PROGRAMA')) {
            return 'ORGANISMOS';
        }

        return '';
    }

    /**
     * Mapea el estado OCDS al STATUS comercial del template (Ganada/Desierto/Perdida...).
     */
    protected function mapearStatusComercial(?string $estado): string
    {
        $map = [
            'ADJUDICADO' => 'Ganada',
            'OTORGADO' => 'Ganada',
            'CONSENTIDO' => 'Ganada',
            'CONTRATADO' => 'Ganada',
            'DESIERTO' => 'Desierto',
            'CANCELADO' => 'Perdida',
            'NULO' => 'Perdida',
        ];

        return $map[mb_strtoupper((string) $estado, 'UTF-8')] ?? (string) $estado;
    }

    protected function nombreMoneda(?string $moneda): string
    {
        return match (mb_strtoupper((string) $moneda, 'UTF-8')) {
            'PEN' => 'Soles',
            'USD' => 'Dólares',
            default => (string) $moneda,
        };
    }
}
