<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContratoMayor;
use App\Services\SeaceMayoresService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API de Contratos Mayores (SEACE 3.0) para clientes con token Sanctum.
 *
 * Endpoints:
 *   GET /api/contratos-mayores              → listado con filtros
 *   GET /api/contratos-mayores/{ocid}       → detalle de UN proceso (BD → fallback API OCDS)
 *
 * Autenticación: Authorization: Bearer {token}
 * Token: POST /api/auth/token {email, password, device_name}
 */
class ContratosMayoresController extends Controller
{
    public function __construct(protected SeaceMayoresService $service)
    {
    }

    /**
     * Listado de contratos mayores con filtros.
     *
     * Parámetros (query string, todos opcionales):
     *   query             → texto libre (entidad, nomenclatura, descripción, RUC)
     *   entidad           → nombre parcial de la entidad
     *   objeto            → goods | services | works
     *   estado            → CONVOCADO | ADJUDICADO | CONSENTIDO | OTORGADO | ...
     *   departamento_id   → ID de tabla maestra (ver GET /api/catalogos/geografia)
     *   provincia_id      → ID de tabla maestra
     *   distrito_id       → ID de tabla maestra
     *   fecha_desde       → YYYY-MM-DD
     *   fecha_hasta       → YYYY-MM-DD
     *   monto_min         → valor_referencial >= X (ej: 1000000)
     *   monto_max         → valor_referencial <= X
     *   page              → número de página (default 1)
     *   paginateBy        → registros por página (default 15, max 100)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:250'],
            'entidad' => ['nullable', 'string', 'max:250'],
            'objeto' => ['nullable', 'string', 'in:goods,services,works'],
            'estado' => ['nullable', 'string', 'max:100'],
            'departamento_id' => ['nullable', 'integer', 'min:1'],
            'provincia_id' => ['nullable', 'integer', 'min:1'],
            'distrito_id' => ['nullable', 'integer', 'min:1'],
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'paginateBy' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $resultado = $this->service->buscar([
            'page' => (int) ($validated['page'] ?? 1),
            'paginateBy' => (int) ($validated['paginateBy'] ?? 15),
            'query' => $validated['query'] ?? '',
            'entidad' => $validated['entidad'] ?? '',
            'objeto' => $validated['objeto'] ?? '',
            'estado' => $validated['estado'] ?? '',
            'departamento_id' => (int) ($validated['departamento_id'] ?? 0),
            'provincia_id' => (int) ($validated['provincia_id'] ?? 0),
            'distrito_id' => (int) ($validated['distrito_id'] ?? 0),
            'fecha_desde' => $validated['fecha_desde'] ?? '',
            'fecha_hasta' => $validated['fecha_hasta'] ?? '',
            'monto_min' => (float) ($validated['monto_min'] ?? 0),
            'monto_max' => (float) ($validated['monto_max'] ?? 0),
        ]);

        $status = $resultado['success'] ? 200 : 500;

        return response()->json([
            'success' => $resultado['success'],
            'message' => $resultado['message'] ?? '',
            'data' => $resultado['data'] ?? [],
            'pagination' => $resultado['pagination'] ?? [],
        ], $status);
    }

    /**
     * Detalle de UN proceso por OCID.
     *
     * Primero busca en la BD local (rápido). Si no existe, consulta la API
     * OCDS oficial (GET /records?ocid=) que devuelve el estado ACTUAL del
     * proceso. Incluye la lista de documentos del release (Bases, Bases
     * Integradas, acta de Otorgamiento de Buena Pro, informes, etc.).
     */
    public function show(Request $request, string $ocid): JsonResponse
    {
        if (!preg_match('/^ocds-[a-zA-Z0-9\-]+$/', $ocid)) {
            return response()->json([
                'success' => false,
                'message' => 'OCID inválido. Formato esperado: ocds-dgv273-seacev3-...',
                'data' => null,
            ], 422);
        }

        // 1. BD local
        $contrato = ContratoMayor::with(['departamento', 'provincia', 'distrito'])
            ->where('ocid', $ocid)
            ->first();

        if ($contrato) {
            return response()->json([
                'success' => true,
                'message' => 'Proceso encontrado en la base de datos local.',
                'data' => $this->formatearDetalle($contrato),
                'fuente' => 'bd_local',
            ]);
        }

        // 2. Fallback: API OCDS en tiempo real
        $resultado = $this->service->fetchRecordPorOcid($ocid);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Proceso no encontrado: ' . ($resultado['error'] ?? 'OCID inexistente en SEACE.'),
                'data' => null,
            ], 404);
        }

        $mapped = $resultado['data'];
        $release = $mapped['datos_raw'] ?? [];
        if (is_string($release)) {
            $release = json_decode($release, true) ?? [];
        }

        $mapped['documentos'] = $this->service->documentosDelRelease($release);

        return response()->json([
            'success' => true,
            'message' => 'Proceso obtenido en tiempo real desde la API OCDS.',
            'data' => $mapped,
            'fuente' => 'api_ocds',
        ]);
    }

    /**
     * Catálogo de geografía normalizada (para usar en los filtros).
     *
     *   GET /api/contratos-mayores/geografia                  → todos los departamentos
     *   GET /api/contratos-mayores/geografia?departamento_id= → provincias del departamento
     *   GET /api/contratos-mayores/geografia?provincia_id=    → distritos de la provincia
     */
    public function geografia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'departamento_id' => ['nullable', 'integer', 'min:1'],
            'provincia_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $geo = app(\App\Services\GeoResolverService::class);

        if (!empty($validated['departamento_id'])) {
            return response()->json([
                'success' => true,
                'data' => $geo->provinciasParaFiltro((int) $validated['departamento_id']),
            ]);
        }

        if (!empty($validated['provincia_id'])) {
            return response()->json([
                'success' => true,
                'data' => $geo->distritosParaFiltro((int) $validated['provincia_id']),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $geo->departamentosParaFiltro(),
        ]);
    }

    /**
     * Formatea un ContratoMayor de BD para la respuesta de detalle.
     */
    protected function formatearDetalle(ContratoMayor $c): array
    {
        $release = $c->datos_raw;
        if (is_string($release)) {
            $release = json_decode($release, true) ?? [];
        }

        return [
            'ocid' => $c->ocid,
            'entidad_nombre' => $c->entidad_nombre,
            'entidad_ruc' => $c->entidad_ruc,
            'entidad_direccion' => $c->entidad_direccion,
            'departamento' => $c->departamento?->nombre,
            'provincia' => $c->provincia?->nombre,
            'distrito' => $c->distrito?->nombre,
            'nomenclatura' => $c->nomenclatura,
            'descripcion_objeto' => $c->descripcion_objeto,
            'objeto_contratacion' => $c->objeto_contratacion,
            'valor_referencial' => (float) $c->valor_referencial,
            'moneda' => $c->moneda,
            'fecha_publicacion' => $c->fecha_publicacion?->toIso8601String(),
            'fecha_inicio' => $c->fecha_inicio?->toIso8601String(),
            'fecha_fin' => $c->fecha_fin?->toIso8601String(),
            'metodo_contratacion' => $c->metodo_contratacion,
            'estado' => $c->estado,
            'codigo_snip' => $c->codigo_snip,
            'proveedores' => $c->proveedores ?? [],
            'url_documento' => $c->url_documento,
            'documentos' => $this->service->documentosDelRelease(is_array($release) ? $release : []),
            'actualizado_en_bd' => $c->updated_at?->toIso8601String(),
        ];
    }
}
