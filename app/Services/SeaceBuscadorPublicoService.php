<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Servicio para consumir el endpoint público del buscador SEACE
 * NO requiere autenticación ni tokens
 */
class SeaceBuscadorPublicoService
{
    protected string $baseUrl;
    protected string $frontendOrigin;
    protected string $referer;
    protected bool $debugLogging;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('services.seace.public_base_url') ?? config('services.seace.base_url', '')), '/');

        $originFallback = config('services.seace.public_frontend_origin')
            ?? config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe');

        $this->frontendOrigin = rtrim((string) $originFallback, '/');
        $this->referer = (string) (config('services.seace.public_referer')
            ?? ($this->frontendOrigin ? $this->frontendOrigin . '/busqueda/buscadorContrataciones' : ''));

        $this->debugLogging = (bool) config('services.seace.debug_logs', false);
    }

    /**
     * Headers básicos para peticiones públicas
     */
    protected function getPublicHeaders(): array
    {
        return [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'es-419,es;q=0.9',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Origin' => $this->frontendOrigin ?: 'https://prod6.seace.gob.pe',
            'Referer' => $this->referer ?: (($this->frontendOrigin ?: 'https://prod6.seace.gob.pe') . '/busqueda/buscadorContrataciones'),
        ];
    }

    /**
     * Buscar contratos en el endpoint público
     *
     * @param array $params Parámetros de búsqueda
     * @return array Resultado de la búsqueda
     */
    public function buscarContratos(array $params = []): array
    {
        try {
            // Endpoint público correcto verificado
            $url = $this->baseUrl . '/buscadorpublico/contrataciones/buscador';

            // Parámetros por defecto (anio es OBLIGATORIO)
            $queryParams = array_merge([
                'anio' => now()->year, // Año obligatorio
                'orden' => 2, // 2 = Descendente (más recientes primero)
                'page' => 1,
                'page_size' => 20,
            ], $params);

            // Filtrar valores vacíos
            $queryParams = array_filter($queryParams, function ($value) {
                return $value !== '' && $value !== null;
            });

            if ($this->debugLogging) {
                Log::info('SEACE Público: Búsqueda de contratos', [
                    'url' => $url,
                    'params' => $queryParams,
                ]);
            }

            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout(30)
                ->get($url, $queryParams);

            if (!$response->successful()) {
                $errorMsg = 'Error al consultar el SEACE';

                // Log con más detalles para debugging
                Log::error('SEACE Público: Error en búsqueda', [
                    'status' => $response->status(),
                    'url' => $url,
                    'params' => $queryParams,
                    'body' => $response->body(),
                ]);

                // Si es 404, agregar nota sobre endpoint no disponible
                if ($response->status() === 404) {
                    $errorMsg = 'Endpoint de búsqueda pública no disponible. Use contratos autenticados.';
                }

                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'status' => $response->status(),
                    'details' => $response->body(),
                ];
            }

            $data = $response->json();

            // Calcular total de páginas manualmente
            $totalElements = $data['pageable']['totalElements'] ?? 0;
            $pageSize = $data['pageable']['pageSize'] ?? 20;
            $totalPages = $pageSize > 0 ? (int) ceil($totalElements / $pageSize) : 1;

            return [
                'success' => true,
                'data' => $data['data'] ?? [],
                'pagination' => [
                    'current_page' => $data['pageable']['pageNumber'] ?? 1,
                    'page_size' => $pageSize,
                    'total_elements' => $totalElements,
                    'total_pages' => $totalPages, // Calculado correctamente
                ],
                'raw' => $data,
            ];

        } catch (Exception $e) {
            Log::error('SEACE Público: Excepción en búsqueda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error del sistema: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener lista de departamentos (cacheada)
     */
    public function obtenerDepartamentos(): array
    {
        return Cache::remember('seace_departamentos_public', 3600, function () {
            try {
                $url = $this->baseUrl . '/buscadorpublico/maestras/listar-departamento';

                $response = Http::withHeaders($this->getPublicHeaders())
                    ->timeout(15)
                    ->get($url);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                    ];
                }

                return ['success' => false, 'data' => []];

            } catch (Exception $e) {
                Log::error('Error al obtener departamentos públicos', ['error' => $e->getMessage()]);
                return ['success' => false, 'data' => []];
            }
        });
    }

    /**
     * Obtener lista de provincias por departamento
     */
    public function obtenerProvincias(int $idDepartamento): array
    {
        try {
            $url = $this->baseUrl . "/buscadorpublico/maestras/listar-provincia/{$idDepartamento}";

            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return ['success' => false, 'data' => []];

        } catch (Exception $e) {
            Log::error('Error al obtener provincias públicas', [
                'id_departamento' => $idDepartamento,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => []];
        }
    }

    /**
     * Obtener lista de distritos por provincia
     */
    public function obtenerDistritos(int $idProvincia): array
    {
        try {
            $url = $this->baseUrl . "/buscadorpublico/maestras/listar-distrito/{$idProvincia}";

            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout(15)
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return ['success' => false, 'data' => []];

        } catch (Exception $e) {
            Log::error('Error al obtener distritos públicos', [
                'id_provincia' => $idProvincia,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => []];
        }
    }

    /**
     * Obtener objetos de contratación (cacheado)
     */
    public function obtenerObjetosContratacion(): array
    {
        return Cache::remember('seace_objetos_public', 3600, function () {
            try {
                $url = $this->baseUrl . '/buscadorpublico/maestras/listar-objeto-contratacion';

                $response = Http::withHeaders($this->getPublicHeaders())
                    ->timeout(15)
                    ->get($url);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                    ];
                }

                return ['success' => false, 'data' => []];

            } catch (Exception $e) {
                Log::error('Error al obtener objetos de contratación', ['error' => $e->getMessage()]);
                return ['success' => false, 'data' => []];
            }
        });
    }

    /**
     * Obtener estados de contratación (cacheado)
     */
    public function obtenerEstadosContratacion(): array
    {
        return Cache::remember('seace_estados_public', 3600, function () {
            try {
                $url = $this->baseUrl . '/buscadorpublico/maestras/listar-estados-contrato-cotizacion';

                $response = Http::withHeaders($this->getPublicHeaders())
                    ->timeout(15)
                    ->get($url);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json(),
                    ];
                }

                return ['success' => false, 'data' => []];

            } catch (Exception $e) {
                Log::error('Error al obtener estados de contratación', ['error' => $e->getMessage()]);
                return ['success' => false, 'data' => []];
            }
        });
    }

    /**
     * Buscar entidades por nombre (autocompletado)
     */
    public function buscarEntidades(string $texto): array
    {
        if (strlen($texto) < 3) {
            return ['success' => false, 'data' => []];
        }

        try {
            // Endpoint correcto para búsqueda de entidades
            $url = $this->baseUrl . '/servicio/servicios/obtener-entidades-cubso';

            $response = Http::withHeaders($this->getPublicHeaders())
                ->timeout(15)
                ->get($url, ['descEntidad' => $texto]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'data' => $data['lista'] ?? [],
                ];
            }

            return ['success' => false, 'data' => []];

        } catch (Exception $e) {
            Log::error('Error al buscar entidades', [
                'texto' => $texto,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'data' => []];
        }
    }
}
