<?php

namespace App\Services\Contratos;

use App\Models\Contrato;
use App\Models\CuentaSeace;
use App\Services\SeaceScraperService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;
use RuntimeException;

class ContratoRepositoryService
{
    protected string $baseUrl;
    protected int $maxPageSize;
    protected int $cacheTtlMinutes;

    public function __construct(?int $cacheTtlMinutes = null)
    {
        $this->baseUrl = rtrim((string) config('services.seace.base_url', ''), '/');
        if ($this->baseUrl === '') {
            throw new RuntimeException('Configura SEACE_BASE_URL para utilizar el repositorio de contratos');
        }
        $this->maxPageSize = (int) config('services.seace.page_size', 100);
        $this->cacheTtlMinutes = $cacheTtlMinutes ?? (int) config('services.seace.process_cache_minutes', 0);
    }

    public function buscar(array $filters, CuentaSeace $cuenta, bool $forceRemote = false): array
    {
        $normalized = $this->normalizeFilters($filters, $cuenta);
        $shouldBypassLocal = $forceRemote || $this->requiresRemote($normalized);

        if (!$shouldBypassLocal) {
            $localQuery = $this->buildLocalQuery($normalized);
            $totalLocal = (clone $localQuery)->count();

            if ($totalLocal > 0) {
                $records = $localQuery->forPage($normalized['page'], $normalized['page_size'])->get();

                if ($this->isFreshCollection($records)) {
                    return [
                        'source' => 'database',
                        'data' => $records->map(fn (Contrato $contrato) => $this->formatContratoPayload($contrato))->all(),
                        'pageable' => [
                            'pageNumber' => $normalized['page'],
                            'pageSize' => $normalized['page_size'],
                            'totalElements' => $totalLocal,
                        ],
                    ];
                }
            }
        }

        $remotePayload = $this->fetchRemote($normalized, $cuenta);
        $this->persistRemoteContratos($remotePayload['data'] ?? []);

        return [
            'source' => 'remote',
            'data' => $remotePayload['data'] ?? [],
            'pageable' => $remotePayload['pageable'] ?? [
                'pageNumber' => $normalized['page'],
                'pageSize' => $normalized['page_size'],
                'totalElements' => count($remotePayload['data'] ?? []),
            ],
        ];
    }

    protected function normalizeFilters(array $filters, CuentaSeace $cuenta): array
    {
        $page = max(1, (int) ($filters['page'] ?? $filters['pagina'] ?? 1));
        $pageSize = (int) ($filters['page_size'] ?? $filters['registros'] ?? $this->maxPageSize);
        $pageSize = max(1, min($pageSize, $this->maxPageSize));

        $normalized = [
            'anio' => (int) ($filters['anio'] ?? now()->year),
            'ruc' => $cuenta->username,
            'cotizaciones_enviadas' => false,
            'invitaciones_por_cotizar' => false,
            'orden' => (int) ($filters['orden'] ?? 2),
            'page' => $page,
            'page_size' => $pageSize,
        ];

        if (!empty($filters['palabra_clave'])) {
            $normalized['palabra_clave'] = $filters['palabra_clave'];
        } elseif (!empty($filters['texto'])) {
            $normalized['palabra_clave'] = $filters['texto'];
        }

        if (!empty($filters['lista_estado_contrato'])) {
            $normalized['lista_estado_contrato'] = (int) $filters['lista_estado_contrato'];
        } elseif (!empty($filters['estado'])) {
            $normalized['lista_estado_contrato'] = (int) $filters['estado'];
        }

        if (!empty($filters['lista_objeto_contrato'])) {
            $normalized['lista_objeto_contrato'] = $filters['lista_objeto_contrato'];
        } elseif (!empty($filters['objeto'])) {
            $normalized['lista_objeto_contrato'] = $filters['objeto'];
        }

        foreach (['codigo_entidad', 'codigo_departamento', 'codigo_provincia', 'codigo_distrito'] as $key) {
            if (!empty($filters[$key])) {
                $normalized[$key] = (int) $filters[$key];
            }
        }

        $geoFallbacks = [
            'departamento' => 'codigo_departamento',
            'provincia' => 'codigo_provincia',
            'distrito' => 'codigo_distrito',
        ];

        foreach ($geoFallbacks as $source => $target) {
            if (!empty($filters[$source]) && empty($normalized[$target])) {
                $normalized[$target] = (int) $filters[$source];
            }
        }

        return $normalized;
    }

    protected function requiresRemote(array $filters): bool
    {
        return !empty($filters['codigo_entidad'])
            || !empty($filters['codigo_departamento'])
            || !empty($filters['codigo_provincia'])
            || !empty($filters['codigo_distrito']);
    }

    protected function buildLocalQuery(array $filters): Builder
    {
        $query = Contrato::query();

        if (!empty($filters['palabra_clave'])) {
            $palabra = $filters['palabra_clave'];
            $query->where(function (Builder $builder) use ($palabra) {
                $builder->where('codigo_proceso', 'like', "%{$palabra}%")
                    ->orWhere('entidad', 'like', "%{$palabra}%")
                    ->orWhere('descripcion', 'like', "%{$palabra}%");
            });
        }

        if (!empty($filters['lista_estado_contrato'])) {
            $estados = $this->normalizeListFilter($filters['lista_estado_contrato']);
            if (count($estados) > 1) {
                $query->whereIn('id_estado_contrato', $estados);
            } elseif (!empty($estados)) {
                $query->where('id_estado_contrato', $estados[0]);
            }
        }

        if (!empty($filters['lista_objeto_contrato'])) {
            $objetos = $this->normalizeListFilter($filters['lista_objeto_contrato']);
            if (count($objetos) > 1) {
                $query->whereIn('id_objeto_contrato', $objetos);
            } elseif (!empty($objetos)) {
                $query->where('id_objeto_contrato', $objetos[0]);
            }
        }

        if (!empty($filters['anio'])) {
            $inicio = Carbon::create((int) $filters['anio'], 1, 1, 0, 0, 0);
            $fin = (clone $inicio)->endOfYear();
            $query->whereBetween('fecha_publicacion', [$inicio, $fin]);
        }

        $orderDirection = ((int) $filters['orden']) === 1 ? 'asc' : 'desc';
        $query->orderBy('fecha_publicacion', $orderDirection);

        return $query;
    }

    protected function isFreshCollection(Collection $records): bool
    {
        if ($records->isEmpty()) {
            return false;
        }

        if ($this->cacheTtlMinutes <= 0) {
            return true;
        }

        $threshold = Carbon::now()->subMinutes($this->cacheTtlMinutes);

        return $records->every(function (Contrato $contrato) use ($threshold) {
            return $contrato->updated_at && $contrato->updated_at->greaterThanOrEqualTo($threshold);
        });
    }

    protected function normalizeListFilter($value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $items = array_map('trim', $items);
        $items = array_filter($items, fn ($item) => $item !== '');

        return array_values(array_map('intval', $items));
    }

    protected function fetchRemote(array $filters, CuentaSeace $cuenta): array
    {
        $scraper = new SeaceScraperService($cuenta);
        $endpoint = $this->baseUrl . config('services.seace.endpoints.buscador');

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $scraper->fetchWithRetry($endpoint, $filters);

        if (!$response->successful()) {
            Log::error('SEACE: Error consultando buscador', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('No se pudo obtener la información del buscador del SEACE.');
        }

        return $response->json();
    }

    protected function persistRemoteContratos(array $dataset): void
    {
        foreach ($dataset as $registro) {
            if (!isset($registro['idContrato'])) {
                continue;
            }

            Contrato::updateOrCreate(
                ['id_contrato_seace' => $registro['idContrato']],
                $this->mapContratoAttributes($registro)
            );
        }
    }

    protected function mapContratoAttributes(array $data): array
    {
        return [
            'nro_contratacion' => $data['nroContratacion'] ?? 0,
            'codigo_proceso' => $data['desContratacion'] ?? 'N/A',
            'entidad' => $data['nomEntidad'] ?? 'N/A',
            'id_objeto_contrato' => $data['idObjetoContrato'] ?? null,
            'objeto' => $data['nomObjetoContrato'] ?? null,
            'descripcion' => $data['desObjetoContrato'] ?? null,
            'id_estado_contrato' => $data['idEstadoContrato'] ?? null,
            'estado' => $data['nomEstadoContrato'] ?? null,
            'fecha_publicacion' => $this->parseSeaceDate($data['fecPublica'] ?? null),
            'inicio_cotizacion' => $this->parseSeaceDate($data['fecIniCotizacion'] ?? null),
            'fin_cotizacion' => $this->parseSeaceDate($data['fecFinCotizacion'] ?? null),
            'etapa_contratacion' => $data['nomEtapaContratacion'] ?? null,
            'id_tipo_cotizacion' => $data['idTipoCotizacion'] ?? null,
            'num_subsanaciones_total' => $data['numSubsanacionesTotal'] ?? 0,
            'num_subsanaciones_pendientes' => $data['numSubsanacionesPendientes'] ?? 0,
            'datos_raw' => $data,
        ];
    }

    protected function parseSeaceDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $value);
        } catch (Exception $e) {
            Log::warning('SEACE: Fecha inválida en payload', ['value' => $value]);
            return null;
        }
    }

    protected function formatContratoPayload(Contrato $contrato): array
    {
        if (is_array($contrato->datos_raw) && !empty($contrato->datos_raw)) {
            return $contrato->datos_raw;
        }

        return [
            'idContrato' => $contrato->id_contrato_seace,
            'nroContratacion' => $contrato->nro_contratacion,
            'desContratacion' => $contrato->codigo_proceso,
            'nomEntidad' => $contrato->entidad,
            'idObjetoContrato' => $contrato->id_objeto_contrato,
            'nomObjetoContrato' => $contrato->objeto,
            'desObjetoContrato' => $contrato->descripcion,
            'nomEtapaContratacion' => $contrato->etapa_contratacion,
            'idEstadoContrato' => $contrato->id_estado_contrato,
            'nomEstadoContrato' => $contrato->estado,
            'fecPublica' => optional($contrato->fecha_publicacion)?->format('d/m/Y H:i:s'),
            'fecIniCotizacion' => optional($contrato->inicio_cotizacion)?->format('d/m/Y H:i:s'),
            'fecFinCotizacion' => optional($contrato->fin_cotizacion)?->format('d/m/Y H:i:s'),
        ];
    }
}
