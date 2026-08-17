<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para consumir la API pública OCDS del OECE (Contrataciones Abiertas).
 * Reemplaza al scraper Playwright. API REST pública, sin autenticación ni reCAPTCHA.
 *
 * @see https://contratacionesabiertas.oece.gob.pe/
 */
class SeaceMayoresService
{
    protected string $baseUrl;
    protected int $timeout;
    protected bool $debugLogging;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.seace_mayores.base_url', 'https://contratacionesabiertas.oece.gob.pe/api/v1'), '/');
        $this->timeout = (int) config('services.seace_mayores.timeout', 30);
        $this->debugLogging = (bool) config('services.seace_mayores.debug_logs', false);
    }

    /**
     * Fetch releases directly from the OCDS API (for import job).
     *
     * @return array ['success'=>bool, 'data'=>[], 'pagination'=>[], 'error'=>'']
     */
    public function fetchFromApi(int $page = 1, int $limit = 20): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/releases", [
                    'page' => $page,
                    'limit' => $limit,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}",
                ];
            }

            $json = $response->json();

            if (!is_array($json)) {
                return ['success' => false, 'error' => 'Respuesta no es JSON válido.'];
            }

            $releases = $json['data'] ?? $json['releases'] ?? $json;

            if (isset($releases[0])) {
                $items = $releases;
            } else {
                return ['success' => false, 'error' => 'Estructura de releases inesperada.'];
            }

            $data = array_map(fn ($release) => $this->mapearRelease($release), $items);

            $links = $json['links'] ?? [];
            $meta = $json['meta'] ?? $json['pagination'] ?? [];
            $hasNext = !empty($links['next']) || ($meta['has_next'] ?? false);

            return [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'has_next' => $hasNext,
                    'total' => $meta['total'] ?? 0,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('SEACE Mayores API: fetch', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Buscar releases (procedimientos de selección) en la API OCDS.
     *
     * @param array $params Parámetros: page, paginateBy, source, year, keyword
     * @return array ['success' => bool, 'data' => [], 'pagination' => [], 'message' => '']
     */
    public function buscar(array $params = []): array
    {
        try {
            $page = (int) ($params['page'] ?? 1);
            $perPage = (int) ($params['paginateBy'] ?? 15);
            $keyword = trim($params['query'] ?? '');
            $filtroEntidad = $params['entidad'] ?? '';
            $filtroObjeto = $params['objeto'] ?? '';
            $filtroEstado = $params['estado'] ?? '';
            $filtroDepartamento = (int) ($params['departamento_id'] ?? 0);
            $filtroProvincia = (int) ($params['provincia_id'] ?? 0);
            $filtroDistrito = (int) ($params['distrito_id'] ?? 0);
            $filtroFechaDesde = $params['fecha_desde'] ?? '';
            $filtroFechaHasta = $params['fecha_hasta'] ?? '';
            $filtroMontoMin = (float) ($params['monto_min'] ?? 0);
            $filtroMontoMax = (float) ($params['monto_max'] ?? 0);

            // Siempre usar BD local (API OCDS no soporta búsqueda ni filtros)
            return $this->buscarEnBaseDeDatos(
                $keyword, $filtroEntidad, $filtroObjeto, $filtroEstado,
                $filtroDepartamento, $filtroProvincia, $filtroDistrito,
                $filtroFechaDesde, $filtroFechaHasta,
                $filtroMontoMin, $filtroMontoMax,
                $page, $perPage
            );
        } catch (\Exception $e) {
            Log::error('SEACE Mayores BD: Excepción', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [],
                'pagination' => [],
            ];
        }
    }

    /**
     * Aplica los filtros del buscador a una query de ContratoMayor.
     * Reutilizado por el buscador y por el exportador Excel.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filtros query, entidad, objeto, estado, departamento_id,
     *                        provincia_id, distrito_id, fecha_desde, fecha_hasta
     */
    public function aplicarFiltros($query, array $filtros): void
    {
        $keyword = trim((string) ($filtros['query'] ?? ''));
        $filtroEntidad = (string) ($filtros['entidad'] ?? '');
        $filtroObjeto = (string) ($filtros['objeto'] ?? '');
        $filtroEstado = (string) ($filtros['estado'] ?? '');
        $filtroDepartamento = (int) ($filtros['departamento_id'] ?? 0);
        $filtroProvincia = (int) ($filtros['provincia_id'] ?? 0);
        $filtroDistrito = (int) ($filtros['distrito_id'] ?? 0);
        $filtroFechaDesde = (string) ($filtros['fecha_desde'] ?? '');
        $filtroFechaHasta = (string) ($filtros['fecha_hasta'] ?? '');

        // ── Keyword search ──
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('entidad_nombre', 'like', "%{$keyword}%")
                  ->orWhere('nomenclatura', 'like', "%{$keyword}%")
                  ->orWhere('descripcion_objeto', 'like', "%{$keyword}%")
                  ->orWhere('entidad_ruc', 'like', "%{$keyword}%");
            });
        }

        // ── Entidad filter ──
        if (!empty($filtroEntidad)) {
            $query->where('entidad_nombre', 'like', "%{$filtroEntidad}%");
        }

        // ── Objeto filter ──
        if (!empty($filtroObjeto)) {
            $objetoMap = ['goods' => 'Bien', 'services' => 'Servicio', 'works' => 'Obra'];
            $query->where('objeto_contratacion', $objetoMap[$filtroObjeto] ?? $filtroObjeto);
        }

        // ── Estado filter ──
        if (!empty($filtroEstado)) {
            $query->where('estado', $filtroEstado);
        }

        // ── Geografía (IDs de tablas maestras) ──
        if ($filtroDepartamento > 0) {
            $query->where('departamento_id', $filtroDepartamento);
        }
        if ($filtroProvincia > 0) {
            $query->where('provincia_id', $filtroProvincia);
        }
        if ($filtroDistrito > 0) {
            $query->where('distrito_id', $filtroDistrito);
        }

        // ── Rango de fechas de publicación ──
        if ($filtroFechaDesde !== '') {
            try {
                $query->where('fecha_publicacion', '>=', \Carbon\Carbon::parse($filtroFechaDesde)->startOfDay());
            } catch (\Throwable $e) {
                // fecha inválida: ignorar filtro
            }
        }
        if ($filtroFechaHasta !== '') {
            try {
                $query->where('fecha_publicacion', '<=', \Carbon\Carbon::parse($filtroFechaHasta)->endOfDay());
            } catch (\Throwable $e) {
                // fecha inválida: ignorar filtro
            }
        }

        // ── Rango de monto (valor_referencial) ──
        $montoMin = (float) ($filtros['monto_min'] ?? 0);
        $montoMax = (float) ($filtros['monto_max'] ?? 0);

        if ($montoMin > 0) {
            $query->where('valor_referencial', '>=', $montoMin);
        }
        if ($montoMax > 0) {
            $query->where('valor_referencial', '<=', $montoMax);
        }
    }

    /**
     * Buscar por keyword en la base de datos local.
     * La API OCDS no soporta búsqueda por texto.
     */
    protected function buscarEnBaseDeDatos(
        string $keyword,
        string $filtroEntidad,
        string $filtroObjeto,
        string $filtroEstado,
        int $filtroDepartamento,
        int $filtroProvincia,
        int $filtroDistrito,
        string $filtroFechaDesde,
        string $filtroFechaHasta,
        float $filtroMontoMin,
        float $filtroMontoMax,
        int $page,
        int $perPage
    ): array
    {
        $query = \App\Models\ContratoMayor::query();

        $this->aplicarFiltros($query, [
            'query' => $keyword,
            'entidad' => $filtroEntidad,
            'objeto' => $filtroObjeto,
            'estado' => $filtroEstado,
            'departamento_id' => $filtroDepartamento,
            'provincia_id' => $filtroProvincia,
            'distrito_id' => $filtroDistrito,
            'fecha_desde' => $filtroFechaDesde,
            'fecha_hasta' => $filtroFechaHasta,
            'monto_min' => $filtroMontoMin,
            'monto_max' => $filtroMontoMax,
        ]);

        $total = $query->count();
        $registros = $query->orderBy('fecha_publicacion', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $contratos = $registros->map(function ($c) {
            $release = $c->datos_raw;
            if (is_string($release)) {
                $release = json_decode($release, true) ?? [];
            }
            return $release ? $this->mapearRelease($release) : [
                'ocid' => $c->ocid,
                'entidad_nombre' => $c->entidad_nombre,
                'entidad_ruc' => $c->entidad_ruc,
                'entidad_direccion' => $c->entidad_direccion,
                'nomenclatura' => $c->nomenclatura,
                'descripcion_objeto' => $c->descripcion_objeto,
                'objeto_contratacion' => $c->objeto_contratacion,
                'valor_referencial' => $c->valor_referencial,
                'moneda' => $c->moneda,
                'fecha_publicacion' => $c->fecha_publicacion,
                'fecha_inicio' => $c->fecha_inicio,
                'fecha_fin' => $c->fecha_fin,
                'metodo_contratacion' => $c->metodo_contratacion,
                'estado' => $c->estado,
                'vigente' => null,
                'estado_vigencia' => $c->estado,
                'codigo_snip' => $c->codigo_snip,
                'proveedores' => $c->proveedores,
                'url_documento' => $c->url_documento,
                'cuantia' => $c->cuantia,
                'datos_raw' => $c->datos_raw,
            ];
        })->toArray();

        $totalPages = (int) ceil($total / $perPage);

        if ($this->debugLogging) {
            Log::info('SEACE Mayores BD: Búsqueda local', [
                'keyword' => $keyword,
                'total' => $total,
                'page' => $page,
            ]);
        }

        return [
            'success' => true,
            'data' => $contratos,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => max(1, $totalPages),
                'per_page' => $perPage,
                'total' => $total,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
            ],
            'message' => $keyword ? "Se encontraron {$total} resultados para \"{$keyword}\"" : "{$total} contratos mayores disponibles",
        ];
    }

    /**
     * Mapear un release OCDS a la estructura plana para la vista.
     */
    protected function mapearRelease(array $release): array
    {
        $tender = $release['tender'] ?? [];
        $buyer = $release['buyer'] ?? [];
        $parties = $release['parties'] ?? [];
        $awards = $release['awards'] ?? [];
        $planning = $release['planning'] ?? [];
        $documents = $tender['documents'] ?? [];
        $tags = $release['tag'] ?? [];

        $mainCategory = $tender['mainProcurementCategory'] ?? '';
        $objetoMap = [
            'goods' => 'Bien',
            'services' => 'Servicio',
            'works' => 'Obra',
        ];

        // ── Vigencia ──────────────────────────────────────────────────
        $items = $tender['items'] ?? [];
        $primerItem = $items[0] ?? [];
        $statusOcds = $primerItem['status'] ?? '';
        $statusDetails = $primerItem['statusDetails'] ?? '';

        $tenderEndDate = $tender['tenderPeriod']['endDate'] ?? null;

        $estadosNoVigentes = ['ADJUDICADO', 'DESIERTO', 'CANCELADO', 'OTORGADO',
            'CONSENTIDO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO'];

        $faseFinal = !empty(array_intersect($tags, ['award', 'contract', 'implementation']));
        $itemInactivo = $statusOcds !== 'active';
        $estadoNoVigente = in_array(strtoupper($statusDetails), $estadosNoVigentes);

        $fechaVencida = false;
        if ($tenderEndDate) {
            $fechaVencida = now()->gt(\Illuminate\Support\Carbon::parse($tenderEndDate));
        }

        $vigente = true;
        $razonNoVigente = '';

        if ($faseFinal) {
            $vigente = false;
            $razonNoVigente = 'Proceso adjudicado/contratado';
        } elseif ($itemInactivo) {
            $vigente = false;
            $razonNoVigente = 'Ítem no activo';
        } elseif ($estadoNoVigente) {
            $vigente = false;
            $razonNoVigente = ucfirst(strtolower($statusDetails));
        } elseif ($fechaVencida) {
            $vigente = false;
            $razonNoVigente = 'Fecha de cierre vencida';
        }

        $estadoVigencia = $vigente ? 'Vigente' : $razonNoVigente;

        // ── Resto del mapeo ──────────────────────────────────────────
        $geo = $this->extraerGeografiaDeRelease($release);
        $ruc = '';
        foreach ($parties as $party) {
            if (in_array('buyer', $party['roles'] ?? [])) {
                foreach ($party['additionalIdentifiers'] ?? [] as $id) {
                    if (($id['scheme'] ?? '') === 'PE-RUC') {
                        $ruc = $id['id'] ?? '';
                        break 2;
                    }
                }
            }
        }

        $suppliers = [];
        foreach ($awards as $award) {
            foreach ($award['suppliers'] ?? [] as $supplier) {
                $suppliers[] = $supplier['name'] ?? '';
            }
        }

        $pdfUrl = '';
        foreach ($documents as $doc) {
            if (($doc['format'] ?? '') === 'pdf' || ($doc['documentType'] ?? '') === 'biddingDocuments') {
                $pdfUrl = $doc['url'] ?? '';
                break;
            }
        }

        return [
            'ocid' => $release['ocid'] ?? '',
            'entidad_nombre' => $buyer['name'] ?? '',
            'entidad_ruc' => $ruc,
            'entidad_direccion' => $geo['direccion'],
            'departamento' => $geo['departamento'],
            'provincia' => $geo['provincia'],
            'distrito' => $geo['distrito'],
            'nomenclatura' => $tender['title'] ?? '',
            'descripcion_objeto' => $tender['description'] ?? '',
            'objeto_contratacion' => $objetoMap[$mainCategory] ?? $mainCategory,
            'valor_referencial' => $tender['value']['amount'] ?? 0,
            'moneda' => $tender['value']['currencyName'] ?? $tender['value']['currency'] ?? '',
            'fecha_publicacion' => $tender['datePublished'] ?? null,
            'fecha_inicio' => $tender['tenderPeriod']['startDate'] ?? null,
            'fecha_fin' => $tender['tenderPeriod']['endDate'] ?? null,
            'metodo_contratacion' => $tender['procurementMethodDetails'] ?? $tender['procurementMethod'] ?? '',
            'estado' => $statusDetails ?: '---',
            'vigente' => $vigente,
            'estado_vigencia' => $estadoVigencia,
            'codigo_snip' => $planning['budget']['projectID'] ?? '',
            'proveedores' => $suppliers,
            'url_documento' => $pdfUrl,
            'cuantia' => $tender['value']['amount_PEN'] ?? null,
            'datos_raw' => $release,
        ];
    }

    /**
     * Refrescar el estado actual de UN contrato desde /records?ocid=.
     *
     * El endpoint /records devuelve el compiledRelease (estado consolidado
     * al día de hoy) de cualquier OCID, sin importar cuántos días hayan
     * pasado desde su publicación. Es la fuente de verdad para refrescar
     * contratos que dejaron la ventana de /releases (~2 días).
     */
    public function fetchRecordPorOcid(string $ocid): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/records", [
                    'ocid' => $ocid,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'error' => "HTTP {$response->status()}"];
            }

            $json = $response->json();
            $records = $json['records'] ?? [];

            if (empty($records)) {
                return ['success' => false, 'error' => 'OCID no encontrado en /records'];
            }

            // El primer record contiene el compiledRelease con el estado actual
            $compiled = $records[0]['compiledRelease'] ?? null;
            if (!$compiled) {
                return ['success' => false, 'error' => 'compiledRelease ausente'];
            }

            return [
                'success' => true,
                'data' => $this->mapearRelease($compiled),
            ];
        } catch (\Exception $e) {
            Log::error('SEACE Mayores API: fetchRecordPorOcid', [
                'ocid' => $ocid,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extraer geografía (departamento/provincia/distrito/dirección) de un
     * release OCDS. Semántica confirmada de la API SEACE:
     *   address.department → departamento (25 únicos)
     *   address.region     → provincia  (194 únicos)
     *   address.locality   → distrito   (1049 únicos)
     */
    public function extraerGeografiaDeRelease(array $release): array
    {
        $parties = $release['parties'] ?? [];
        $direccion = '';
        $departamento = '';
        $provincia = '';
        $distrito = '';

        foreach ($parties as $party) {
            if (in_array('buyer', $party['roles'] ?? [])) {
                $addr = $party['address'] ?? [];
                $direccion = trim(($addr['streetAddress'] ?? '') . ', ' . ($addr['locality'] ?? '') . ', ' . ($addr['region'] ?? ''), ', ');
                $departamento = $addr['department'] ?? '';
                $provincia = $addr['region'] ?? '';
                $distrito = $addr['locality'] ?? '';
                break;
            }
        }

        return [
            'direccion' => $direccion,
            'departamento' => $departamento ?? '',
            'provincia' => $provincia ?? '',
            'distrito' => $distrito ?? '',
        ];
    }

    /**
     * Extrae la lista de documentos (Bases, Bases Integradas, acta de Buena
     * Pro / awardNotice, informes, etc.) de un release OCDS.
     *
     * @return array<int, array{tipo: string, titulo: string, url: string, formato: string, fecha_publicacion: string}>
     */
    public function documentosDelRelease(array $release): array
    {
        $documentos = [];

        foreach ($release['tender']['documents'] ?? [] as $doc) {
            $url = (string) ($doc['url'] ?? '');
            if ($url === '') {
                continue;
            }
            $documentos[] = [
                'tipo' => (string) ($doc['documentType'] ?? ''),
                'titulo' => (string) ($doc['title'] ?? ''),
                'url' => $url,
                'formato' => (string) ($doc['format'] ?? ''),
                'fecha_publicacion' => (string) ($doc['datePublished'] ?? ''),
            ];
        }

        return $documentos;
    }

    /**
     * Extraer información de paginación desde los links HATEOAS de la API.
     */
    protected function parsePagination(array $links, array $currentParams): array
    {
        $currentPage = (int) ($currentParams['page'] ?? 1);
        $perPage = (int) ($currentParams['paginateBy'] ?? 15);
        $totalPages = 1;

        if (!empty($links['next'])) {
            $parsedNext = parse_url($links['next']);
            parse_str($parsedNext['query'] ?? '', $nextParams);
            $totalPages = (int) ($nextParams['page'] ?? $currentPage) - 1;
            if ($totalPages < 1) {
                $totalPages = 1;
            }
        } elseif ($currentPage > 1) {
            $totalPages = $currentPage;
        }

        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'has_next' => !empty($links['next']),
            'has_prev' => !empty($links['prev']),
        ];
    }
}
