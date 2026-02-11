<?php

namespace App\Services\Tdr;

use App\Models\TelegramSubscription;
use App\Services\SeaceBuscadorPublicoService;
use App\Services\SeacePublicArchivoService;
use App\Services\TelegramNotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImportadorTdrEngine
{
    private const MAX_DATASET_PAGE_SIZE = 150;
    public function __construct(
        protected SeaceBuscadorPublicoService $buscadorService,
        protected SeacePublicArchivoService $archivoService,
        protected TelegramNotificationService $telegramService
    ) {
    }

    public function ejecutar(Carbon $fechaObjetivo, Collection $suscripciones, int $limite = self::MAX_DATASET_PAGE_SIZE): array
    {
        if ($suscripciones->isEmpty()) {
            throw new RuntimeException('No hay suscriptores activos configurados.');
        }

        $inicio = microtime(true);

        $datasetPayload = $this->obtenerDataset($fechaObjetivo, $limite);
        $dataset = $datasetPayload['data'];
        $datasetMeta = $datasetPayload['meta'];
        $cacheHit = $datasetPayload['cache_hit'];

        if (empty($dataset)) {
            throw new RuntimeException('No se pudo recuperar información del buscador público del SEACE.');
        }

        $filtrados = $this->filtrarPorFecha($dataset, $fechaObjetivo);
        $procesadosKey = $this->buildProcesadosKey($fechaObjetivo);
        [$pendientes, $omitidos] = $this->separarNuevosYRepetidos($filtrados, $procesadosKey);
        $indicePendientes = $this->indexarPorContrato($pendientes);
        $archivoMetaCache = [];

        $suscriptoresResumen = [];
        $procesosNotificados = [];
        $totalCoincidencias = 0;
        $totalEnvios = 0;
        $erroresEnvio = 0;

        foreach ($suscripciones->chunk(20) as $grupo) {
            /** @var TelegramSubscription $suscripcion */
            foreach ($grupo as $suscripcion) {
                $nombreSuscriptor = $suscripcion->nombre ?: 'Chat ' . $suscripcion->chat_id;
                $coincidencias = [];
                $enviosExitosos = 0;
                $enviosFallidos = 0;
                $detalleErrores = [];

                foreach ($indicePendientes as $contratoId => $contrato) {
                    $resultado = $suscripcion->resolverCoincidenciasContrato($contrato);

                    if (!$resultado['pasa']) {
                        continue;
                    }

                    $contratoSeaceId = (int) ($contrato['idContrato'] ?? $contrato['id_contrato_seace'] ?? 0);

                    if ($contratoSeaceId > 0 && !isset($archivoMetaCache[$contratoSeaceId])) {
                        $archivoMetaCache[$contratoSeaceId] = $this->resolverArchivoPrincipal($contratoSeaceId);
                    }

                    $payloadContrato = $this->prepararContratoParaEnvio(
                        $contrato,
                        $archivoMetaCache[$contratoSeaceId] ?? []
                    );
                    $payloadContrato['_match_keywords'] = $resultado['keywords'];

                    $respuesta = $this->telegramService->enviarProcesoASuscriptor(
                        $suscripcion,
                        $payloadContrato,
                        $resultado['keywords']
                    );

                    $this->acumularProcesoNotificado(
                        $procesosNotificados,
                        $payloadContrato,
                        $suscripcion,
                        $resultado['keywords'],
                        $respuesta
                    );

                    if ($respuesta['success']) {
                        $enviosExitosos++;
                        $totalEnvios++;
                    } else {
                        $enviosFallidos++;
                        $erroresEnvio++;
                        $detalleErrores[] = $respuesta['message'] ?? 'Error desconocido';
                    }

                    $coincidencias[] = [
                        'codigo' => $contrato['desContratacion'] ?? 'N/A',
                        'entidad' => $contrato['nomEntidad'] ?? 'N/A',
                        'match_keywords' => $resultado['keywords'],
                    ];
                }

                $totalCoincidencias += count($coincidencias);

                $suscriptoresResumen[] = [
                    'id' => $suscripcion->id,
                    'nombre' => $nombreSuscriptor,
                    'chat_id' => $suscripcion->chat_id,
                    'keywords' => $suscripcion->keywords->pluck('nombre')->all(),
                    'coincidencias' => count($coincidencias),
                    'envios_exitosos' => $enviosExitosos,
                    'envios_fallidos' => $enviosFallidos,
                    'errores' => array_slice($detalleErrores, 0, 3),
                    'muestras' => array_slice($coincidencias, 0, 3),
                ];
            }
        }

        $procesosSinEnvio = $this->detectarProcesosSinEnvio($indicePendientes, $procesosNotificados);
        $duracionMs = round((microtime(true) - $inicio) * 1000, 2);

        $mensaje = match (true) {
            empty($pendientes) => 'No hay procesos nuevos para la fecha seleccionada. Todos los registros fueron procesados anteriormente.',
            $totalEnvios > 0 => "Se enviaron {$totalEnvios} proceso(s) nuevos filtrados a Telegram.",
            default => 'Los procesos filtrados no coincidieron con los filtros de los suscriptores activos.',
        };

        return [
            'success' => true,
            'message' => $mensaje,
            'stats' => [
                'fecha' => $fechaObjetivo->format('d/m/Y'),
                'total_descargados' => count($dataset),
                'total_filtrados' => count($filtrados),
                'total_pendientes' => count($pendientes),
                'total_omitidos' => count($omitidos),
                'total_suscriptores' => $suscripciones->count(),
                'total_coincidencias' => $totalCoincidencias,
                'total_envios' => $totalEnvios,
                'errores_envio' => $erroresEnvio,
                'cache_hit' => $cacheHit,
                'tiempo_ms' => $duracionMs,
            ],
            'suscriptores' => $suscriptoresResumen,
            'preview' => array_slice(array_map(fn ($contrato) => [
                'codigo' => $contrato['desContratacion'] ?? 'N/A',
                'entidad' => $contrato['nomEntidad'] ?? 'N/A',
                'objeto' => $contrato['nomObjetoContrato'] ?? null,
                'fecha_publicacion' => $contrato['fecPublica'] ?? null,
            ], $pendientes), 0, 5),
            'raw_dataset' => $dataset,
            'raw_filtrado' => $pendientes,
            'procesos_notificados' => array_values($procesosNotificados),
            'procesos_sin_envio' => $procesosSinEnvio,
            'procesos_omitidos' => array_slice(array_map(fn ($contrato) => $this->resumirContrato($contrato), $omitidos), 0, 10),
            'dataset_meta' => array_merge($datasetMeta, ['cache_hit' => $cacheHit]),
        ];
    }

    protected function obtenerDataset(Carbon $fechaObjetivo, int $limite): array
    {
        $pageSize = max(1, min($limite, self::MAX_DATASET_PAGE_SIZE));
        $anio = (int) $fechaObjetivo->format('Y');
        $cacheKey = sprintf('tdr:dataset:%d:%d', $anio, $pageSize);

        $payload = Cache::get($cacheKey);
        $cacheHit = is_array($payload);

        if (!$cacheHit) {
            $respuesta = $this->buscadorService->buscarContratos([
                'anio' => $anio,
                'orden' => 2,
                'page' => 1,
                'page_size' => $pageSize,
            ]);

            if (!($respuesta['success'] ?? false)) {
                $mensaje = $respuesta['error'] ?? 'No se pudo consultar el buscador público del SEACE.';
                throw new RuntimeException($mensaje);
            }

            $payload = [
                'data' => $respuesta['data'] ?? [],
                'meta' => [
                    'anio' => $anio,
                    'page' => 1,
                    'page_size' => $pageSize,
                    'total_elements' => $respuesta['pageable']['totalElements'] ?? count($respuesta['data'] ?? []),
                    'fetched_at' => now()->format('Y-m-d H:i:s'),
                ],
            ];

            Cache::put($cacheKey, $payload, now()->addMinutes(5));
        }

        return [
            'data' => $payload['data'] ?? [],
            'meta' => $payload['meta'] ?? [],
            'cache_hit' => $cacheHit,
        ];
    }

    protected function filtrarPorFecha(array $dataset, Carbon $fechaObjetivo): array
    {
        return array_values(array_filter($dataset, function (array $contrato) use ($fechaObjetivo) {
            foreach (['fecPublica', 'fecFinCotizacion', 'fecIniCotizacion'] as $campo) {
                if ($this->coincideConFechaObjetivo($contrato[$campo] ?? null, $fechaObjetivo)) {
                    return true;
                }
            }

            return false;
        }));
    }

    protected function separarNuevosYRepetidos(array $contratos, string $cacheKey): array
    {
        $procesados = Cache::get($cacheKey, []);
        $procesados = is_array($procesados) ? $procesados : [];

        $pendientes = [];
        $omitidos = [];

        foreach ($contratos as $contrato) {
            $identificador = $this->obtenerIdentificadorContrato($contrato);

            if ($identificador === null) {
                $pendientes[] = $contrato;
                continue;
            }

            if (in_array($identificador, $procesados, true)) {
                $omitidos[] = $contrato;
                continue;
            }

            $pendientes[] = $contrato;
            $procesados[] = $identificador;
        }

        $procesados = array_values(array_unique(
            count($procesados) > 1000 ? array_slice($procesados, -1000) : $procesados
        ));

        Cache::put($cacheKey, $procesados, now()->addHours(24));

        return [$pendientes, $omitidos];
    }

    protected function indexarPorContrato(array $contratos): array
    {
        $indexed = [];

        foreach ($contratos as $contrato) {
            $id = $this->obtenerIdentificadorContrato($contrato);

            if ($id !== null) {
                $indexed[$id] = $contrato;
            }
        }

        return $indexed;
    }

    protected function resolverArchivoPrincipal(int $idContrato): array
    {
        $cacheKey = sprintf('tdr:archivo-meta:%d', $idContrato);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($idContrato) {
            try {
                $respuesta = $this->archivoService->listarArchivos($idContrato);

                if (!($respuesta['success'] ?? false) || empty($respuesta['data'])) {
                    return [
                        'idContratoArchivo' => 0,
                        'nombreArchivo' => 'tdr.pdf',
                    ];
                }

                $archivo = collect($respuesta['data'])
                    ->first(fn ($item) => str_contains(strtolower($item['descripcionExtension'] ?? ''), 'pdf'))
                    ?? $respuesta['data'][0];

                return [
                    'idContratoArchivo' => (int) ($archivo['idContratoArchivo'] ?? 0),
                    'nombreArchivo' => $archivo['nombre'] ?? ($archivo['descripcionArchivo'] ?? 'tdr.pdf'),
                ];
            } catch (Exception $e) {
                Log::warning('ImportadorTdrEngine: no se pudo resolver archivo principal', [
                    'contrato' => $idContrato,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'idContratoArchivo' => 0,
                    'nombreArchivo' => 'tdr.pdf',
                ];
            }
        });
    }

    protected function prepararContratoParaEnvio(array $contrato, array $archivoMeta): array
    {
        $payload = $contrato;
        $payload['idContrato'] = $contrato['idContrato'] ?? $contrato['id_contrato_seace'] ?? 0;
        $payload['idContratoArchivo'] = $archivoMeta['idContratoArchivo'] ?? 0;
        $payload['nombreArchivo'] = $archivoMeta['nombreArchivo'] ?? 'tdr.pdf';

        return $payload;
    }

    protected function acumularProcesoNotificado(array &$registro, array $contrato, TelegramSubscription $suscripcion, array $keywords, array $respuesta): void
    {
        $contratoId = $this->obtenerIdentificadorContrato($contrato) ?? spl_object_hash((object) $contrato);

        if (!isset($registro[$contratoId])) {
            $registro[$contratoId] = [
                'contrato' => $this->resumirContrato($contrato),
                'suscriptores' => [],
                'envios' => [],
            ];
        }

        $nombreSuscriptor = $suscripcion->nombre ?: 'Chat ' . $suscripcion->chat_id;

        if (!in_array($nombreSuscriptor, $registro[$contratoId]['suscriptores'], true)) {
            $registro[$contratoId]['suscriptores'][] = $nombreSuscriptor;
        }

        $registro[$contratoId]['envios'][] = [
            'suscriptor_id' => $suscripcion->id,
            'suscriptor' => $nombreSuscriptor,
            'status' => $respuesta['success'] ? 'enviado' : 'error',
            'mensaje' => $respuesta['message'] ?? null,
            'keywords' => $keywords,
        ];
    }

    protected function detectarProcesosSinEnvio(array $contratos, array $procesosNotificados): array
    {
        $sinEnvio = [];

        foreach ($contratos as $contratoId => $contrato) {
            if (!isset($procesosNotificados[$contratoId])) {
                $sinEnvio[] = $this->resumirContrato($contrato);
            }
        }

        return $sinEnvio;
    }

    protected function resumirContrato(array $contrato): array
    {
        return [
            'id' => $contrato['idContrato'] ?? $contrato['id_contrato_seace'] ?? null,
            'codigo' => $contrato['desContratacion'] ?? 'N/A',
            'entidad' => $contrato['nomEntidad'] ?? 'N/A',
            'objeto' => $contrato['nomObjetoContrato'] ?? $contrato['desObjetoContrato'] ?? null,
            'estado' => $contrato['nomEstadoContrato'] ?? null,
            'fecha_publicacion' => $contrato['fecPublica'] ?? null,
            'fecha_cierre' => $contrato['fecFinCotizacion'] ?? null,
        ];
    }

    protected function obtenerIdentificadorContrato(array $contrato): ?string
    {
        $candidatos = [
            $contrato['idContrato'] ?? null,
            $contrato['id_contrato_seace'] ?? null,
            $contrato['desContratacion'] ?? null,
        ];

        foreach ($candidatos as $valor) {
            if (!empty($valor)) {
                return (string) $valor;
            }
        }

        return null;
    }

    protected function parseSeaceTimestamp(?string $valor): ?Carbon
    {
        if (empty($valor)) {
            return null;
        }

        $formatos = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];

        foreach ($formatos as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor, 'America/Lima');
            } catch (Exception $e) {
                continue;
            }
        }

        Log::warning('ImportadorTdrEngine: formato de fecha desconocido', [
            'valor' => $valor,
        ]);

        return null;
    }

    protected function coincideConFechaObjetivo(?string $valor, Carbon $fechaObjetivo): bool
    {
        $fecha = $this->parseSeaceTimestamp($valor);

        if ($fecha && $fecha->isSameDay($fechaObjetivo)) {
            return true;
        }

        $stringNormalizado = trim(substr((string) $valor, 0, 10));

        return $stringNormalizado !== '' && $stringNormalizado === $fechaObjetivo->format('d/m/Y');
    }

    protected function buildProcesadosKey(Carbon $fecha): string
    {
        return 'tdr:procesados:' . $fecha->format('Y-m-d');
    }
}
