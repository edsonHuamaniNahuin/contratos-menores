<?php

namespace App\Services\Tdr;

use App\Contracts\NotificationChannelContract;
use App\Contracts\NotificationTrackerContract;
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

    /**
     * Canales de notificación registrados.
     *
     * @var array<string, NotificationChannelContract>
     */
    protected array $channels = [];

    public function __construct(
        protected SeaceBuscadorPublicoService $buscadorService,
        protected SeacePublicArchivoService $archivoService,
        protected TelegramNotificationService $telegramService,
        protected NotificationTrackerContract $tracker
    ) {
        // Registrar Telegram como canal por defecto (backwards-compatible)
        $this->registerChannel($telegramService);
    }

    /**
     * Registrar un canal de notificación adicional.
     */
    public function registerChannel(NotificationChannelContract $channel): static
    {
        if ($channel->isEnabled()) {
            $this->channels[$channel->channelName()] = $channel;
        }

        return $this;
    }

    /**
     * Resolver qué canal usar para una suscripción dada.
     */
    protected function resolveChannelForSubscription(object $suscripcion): ?NotificationChannelContract
    {
        // Si la suscripción tiene un método channelName(), usarlo
        if (method_exists($suscripcion, 'channelName')) {
            return $this->channels[$suscripcion->channelName()] ?? null;
        }

        // Fallback por tipo de modelo
        $class = get_class($suscripcion);

        return match (true) {
            str_contains($class, 'WhatsApp') => $this->channels['whatsapp'] ?? null,
            str_contains($class, 'Telegram') => $this->channels['telegram'] ?? null,
            default => $this->channels['telegram'] ?? reset($this->channels) ?: null,
        };
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

        // ── Dedup per-subscriber (BD) en lugar de global (caché) ──────
        // Cada suscriptor tiene su propio historial de envíos. Así, cuando
        // un usuario cambia keywords, los procesos que ahora coinciden
        // pero no fueron enviados antes SÍ se procesan.
        $indiceFiltrados = $this->indexarPorContrato($filtrados);
        $archivoMetaCache = [];

        $suscriptoresResumen = [];
        $procesosNotificados = [];
        $totalCoincidencias = 0;
        $totalEnvios = 0;
        $totalDedupOmitidos = 0;
        $erroresEnvio = 0;

        foreach ($suscripciones->chunk(20) as $grupo) {
            foreach ($grupo as $suscripcion) {
                $nombreSuscriptor = $suscripcion->nombre ?: ('ID ' . ($suscripcion->getRecipientId ?? $suscripcion->id));
                $coincidencias = [];
                $enviosExitosos = 0;
                $enviosFallidos = 0;
                $dedupOmitidos = 0;
                $detalleErrores = [];

                // Obtener procesos ya notificados a ESTE suscriptor específico
                $canal = method_exists($suscripcion, 'channelName') ? $suscripcion->channelName() : 'telegram';
                $recipientId = method_exists($suscripcion, 'getRecipientId') ? $suscripcion->getRecipientId() : ($suscripcion->chat_id ?? '');
                $yaNotificados = $this->tracker->getNotifiedProcessIds(
                    $suscripcion->user_id,
                    $canal,
                    $recipientId
                );

                foreach ($indiceFiltrados as $contratoId => $contrato) {
                    // Dedup per-subscriber: omitir si ya fue notificado a este usuario+canal+recipient
                    if (in_array((string) $contratoId, $yaNotificados, true)) {
                        $dedupOmitidos++;
                        continue;
                    }

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

                    $channel = $this->resolveChannelForSubscription($suscripcion);

                    if (!$channel) {
                        Log::warning('ImportadorTdrEngine: no se encontró canal para suscriptor', [
                            'suscriptor' => $suscripcion->id ?? 'unknown',
                            'tipo' => get_class($suscripcion),
                        ]);
                        continue;
                    }

                    $respuesta = $channel->enviarProcesoASuscriptor(
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

                        // Registrar envío exitoso en BD (dedup persistente)
                        $this->tracker->recordNotification(
                            $contrato,
                            (string) $contratoId,
                            $suscripcion->user_id,
                            $canal,
                            $recipientId,
                            $nombreSuscriptor,
                            $resultado['keywords']
                        );
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
                $totalDedupOmitidos += $dedupOmitidos;

                $suscriptoresResumen[] = [
                    'id' => $suscripcion->id,
                    'nombre' => $nombreSuscriptor,
                    'canal' => $canal,
                    'recipient' => $recipientId,
                    'keywords' => $suscripcion->keywords->pluck('nombre')->all(),
                    'coincidencias' => count($coincidencias),
                    'envios_exitosos' => $enviosExitosos,
                    'envios_fallidos' => $enviosFallidos,
                    'dedup_omitidos' => $dedupOmitidos,
                    'errores' => array_slice($detalleErrores, 0, 3),
                    'muestras' => array_slice($coincidencias, 0, 3),
                ];
            }
        }

        $procesosSinEnvio = $this->detectarProcesosSinEnvio($indiceFiltrados, $procesosNotificados);
        $duracionMs = round((microtime(true) - $inicio) * 1000, 2);

        $mensaje = match (true) {
            empty($filtrados) => 'No hay procesos para la fecha seleccionada.',
            $totalEnvios > 0 => "Se enviaron {$totalEnvios} notificación(es) a " . implode(', ', array_keys($this->channels)) . ". ({$totalDedupOmitidos} omitidos por dedup)",
            $totalDedupOmitidos > 0 => "Todos los procesos ya fueron notificados anteriormente a los suscriptores activos. ({$totalDedupOmitidos} omitidos por dedup)",
            default => 'Los procesos filtrados no coincidieron con los filtros de los suscriptores activos.',
        };

        return [
            'success' => true,
            'message' => $mensaje,
            'stats' => [
                'fecha' => $fechaObjetivo->format('d/m/Y'),
                'total_descargados' => count($dataset),
                'total_filtrados' => count($filtrados),
                'total_pendientes' => count($filtrados),
                'total_dedup_omitidos' => $totalDedupOmitidos,
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
            ], $filtrados), 0, 5),
            'raw_dataset' => $dataset,
            'raw_filtrado' => $filtrados,
            'procesos_notificados' => array_values($procesosNotificados),
            'procesos_sin_envio' => $procesosSinEnvio,
            'procesos_dedup_omitidos' => $totalDedupOmitidos,
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

    /**
     * @deprecated Reemplazado por dedup per-subscriber vía ProcessNotificationTracker (BD).
     *             Mantenido temporalmente para compatibilidad inversa.
     */
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

        // Verificar si hay un resultado válido en caché
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ($cached['idContratoArchivo'] ?? 0) > 0) {
            return $cached;
        }

        // Consultar SEACE (no cachear si falla o devuelve id=0)
        try {
            $respuesta = $this->archivoService->listarArchivos($idContrato);

            if (!($respuesta['success'] ?? false) || empty($respuesta['data'])) {
                Log::debug('ImportadorTdrEngine: SEACE no devolvió archivos', [
                    'contrato' => $idContrato,
                ]);
                return [
                    'idContratoArchivo' => 0,
                    'nombreArchivo' => 'tdr.pdf',
                ];
            }

            $archivo = collect($respuesta['data'])
                ->first(fn ($item) => str_contains(strtolower($item['descripcionExtension'] ?? ''), 'pdf'))
                ?? $respuesta['data'][0];

            $result = [
                'idContratoArchivo' => (int) ($archivo['idContratoArchivo'] ?? 0),
                'nombreArchivo' => $archivo['nombre'] ?? ($archivo['descripcionArchivo'] ?? 'tdr.pdf'),
            ];

            // Solo cachear si obtuvimos un ID válido
            if ($result['idContratoArchivo'] > 0) {
                Cache::put($cacheKey, $result, now()->addMinutes(60));
            }

            return $result;
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
    }

    protected function prepararContratoParaEnvio(array $contrato, array $archivoMeta): array
    {
        $payload = $contrato;
        $payload['idContrato'] = $contrato['idContrato'] ?? $contrato['id_contrato_seace'] ?? 0;
        $payload['idContratoArchivo'] = $archivoMeta['idContratoArchivo'] ?? 0;
        $payload['nombreArchivo'] = $archivoMeta['nombreArchivo'] ?? 'tdr.pdf';

        return $payload;
    }

    protected function acumularProcesoNotificado(array &$registro, array $contrato, object $suscripcion, array $keywords, array $respuesta): void
    {
        $contratoId = $this->obtenerIdentificadorContrato($contrato) ?? spl_object_hash((object) $contrato);

        if (!isset($registro[$contratoId])) {
            $registro[$contratoId] = [
                'contrato' => $this->resumirContrato($contrato),
                'suscriptores' => [],
                'envios' => [],
            ];
        }

        $nombreSuscriptor = $suscripcion->nombre ?: ('ID ' . ($suscripcion->id ?? 'unknown'));

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

    /**
     * @deprecated Ya no se usa dedup por caché. Ver ProcessNotificationTracker.
     */
    protected function buildProcesadosKey(Carbon $fecha): string
    {
        return 'tdr:procesados:' . $fecha->format('Y-m-d');
    }
}
