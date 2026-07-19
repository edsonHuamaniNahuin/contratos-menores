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

            // Siempre usar BD local (API OCDS no soporta búsqueda ni filtros)
            return $this->buscarEnBaseDeDatos($keyword, $filtroEntidad, $filtroObjeto, $filtroEstado, $page, $perPage);
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
     * Buscar por keyword en la base de datos local.
     * La API OCDS no soporta búsqueda por texto.
     */
    protected function buscarEnBaseDeDatos(string $keyword, string $filtroEntidad, string $filtroObjeto, string $filtroEstado, int $page, int $perPage): array
    {
        $query = \App\Models\ContratoMayor::query();

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
        $ruc = '';
        $address = '';
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
        foreach ($parties as $party) {
            if (in_array('buyer', $party['roles'] ?? [])) {
                $addr = $party['address'] ?? [];
                $address = trim(($addr['streetAddress'] ?? '') . ', ' . ($addr['locality'] ?? '') . ', ' . ($addr['region'] ?? ''), ', ');
                break;
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
            'entidad_direccion' => $address,
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
