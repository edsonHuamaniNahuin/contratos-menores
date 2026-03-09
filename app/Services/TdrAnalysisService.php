<?php

namespace App\Services;

use App\Models\ContratoArchivo;
use App\Models\CuentaSeace;
use App\Services\SeacePublicArchivoService;
use App\Services\Tdr\PublicTdrDocumentService;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio centralizado para análisis de TDR
 * Usado por PruebaEndpoints (Livewire) y TelegramBotListener (Command)
 */
class TdrAnalysisService
{
    // ── Lock atómico: evita N llamadas concurrentes al LLM por el mismo archivo ──
    private const ANALYSIS_LOCK_PREFIX = 'tdr:analyze:';
    private const ANALYSIS_LOCK_TTL = 180; // 3 min máximo de procesamiento
    private const ANALYSIS_LOCK_WAIT = 90;  // esperar hasta 90s si otro proceso tiene el lock

    protected string $baseUrl;
    protected TdrAnalysisFormatter $formatter;
    protected TdrPersistenceService $persistence;
    protected TdrDocumentService $documentService;
    protected bool $debugLogging;

    public function __construct(?TdrAnalysisFormatter $formatter = null)
    {
        $this->baseUrl = config('services.seace.base_url');
        $this->formatter = $formatter ?? new TdrAnalysisFormatter();
        $this->persistence = new TdrPersistenceService();
        $this->documentService = new TdrDocumentService($this->persistence);
        $this->debugLogging = (bool) config('tdr.debug_logs', config('services.analizador_tdr.debug_logs', false));
    }

    /**
     * Analizar un TDR desde SEACE
     *
     * @param int $idContratoArchivo ID del archivo en SEACE
     * @param string $nombreArchivo Nombre del archivo
     * @param CuentaSeace|null $cuenta Cuenta SEACE para autenticación (opcional, usa endpoint público como fallback)
     * @param array|null $contratoData Datos adicionales del contrato (opcional)
     * @param string $target Destino del resultado ('dashboard' por defecto, 'telegram' para texto legible)
     * @return array Resultado del análisis con estructura: ['success' => bool, 'data' => array, 'formatted' => array|null, 'error' => string]
     */
    public function analizarDesdeSeace(
        int $idContratoArchivo,
        string $nombreArchivo,
        ?CuentaSeace $cuenta = null,
        ?array $contratoData = null,
        string $target = 'dashboard',
        bool $forceRefresh = false,
        string $tipoAnalisis = 'general'
    ): array {
        try {
            $this->debug('Inicio análisis TDR', [
                'idContratoArchivo' => $idContratoArchivo,
                'nombreArchivo' => $nombreArchivo,
                'cuenta_id' => $cuenta?->id,
                'force_refresh' => $forceRefresh,
                'tipo_analisis' => $tipoAnalisis,
            ]);

            if (!config('services.analizador_tdr.enabled', false)) {
                return [
                    'success' => false,
                    'error' => 'El servicio de Análisis TDR no está habilitado. Habilítalo en Configuración.',
                ];
            }

            $contratoSeaceId = $contratoData['idContrato'] ?? $contratoData['id_contrato_seace'] ?? null;
            $archivoPersistido = $this->persistence->resolveArchivo(
                $idContratoArchivo,
                $nombreArchivo,
                $contratoSeaceId,
                $contratoData
            );

            // ── Fast-path: resultado ya existe en DB ─────────────────
            if ($cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis)) {
                $this->debug('Usando análisis en caché', [
                    'contrato_archivo_id' => $archivoPersistido->id,
                    'analisis_id' => $cachedAnalisis->id,
                    'tipo_analisis' => $tipoAnalisis,
                ]);

                $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
            }

            // ── Lock atómico (DB): solo 1 proceso llama al LLM por archivo ──
            $lock = Cache::lock(
                self::ANALYSIS_LOCK_PREFIX . $tipoAnalisis . ':' . $idContratoArchivo,
                self::ANALYSIS_LOCK_TTL
            );

            $acquired = $lock->block(self::ANALYSIS_LOCK_WAIT);

            if (!$acquired) {
                $cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis);
                if ($cachedAnalisis) {
                    $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                    return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
                }

                return [
                    'success' => false,
                    'error' => 'El análisis está en proceso por otro usuario. Inténtalo en unos segundos.',
                ];
            }

            try {
                // ── Double-check: otro proceso pudo completar mientras esperábamos ──
                $cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis);
                if ($cachedAnalisis) {
                    $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                    return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
                }

                return $this->executeAnalysis($archivoPersistido, $idContratoArchivo, $nombreArchivo, $cuenta, $contratoData, $target, $tipoAnalisis);
            } finally {
                $lock->release();
            }

        } catch (Exception $e) {
            Log::error('TDR: Error en análisis', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ejecuta el análisis real: descarga + LLM + persistencia.
     * SOLO se invoca cuando el lock fue adquirido y no hay cache.
     */
    protected function executeAnalysis(
        ContratoArchivo $archivoPersistido,
        int $idContratoArchivo,
        string $nombreArchivo,
        ?CuentaSeace $cuenta,
        ?array $contratoData,
        string $target,
        string $tipoAnalisis = 'general'
    ): array {
        // Usar endpoint autenticado si hay cuenta, de lo contrario fallback a endpoint público
        if ($cuenta) {
            $filePath = $this->documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);
        } else {
            $publicService = new PublicTdrDocumentService(
                $this->persistence,
                new SeacePublicArchivoService()
            );
            $idContrato = (int) ($contratoData['idContrato'] ?? $archivoPersistido->id_contrato_seace ?? 0);
            $archivoPersistido = $publicService->ensureLocalArchivo(
                $idContrato,
                ['idContratoArchivo' => $idContratoArchivo, 'nombre' => $nombreArchivo],
                $contratoData
            );
            $filePath = $this->persistence->getAbsolutePath($archivoPersistido);

            if (!$filePath || !is_file($filePath)) {
                return ['success' => false, 'error' => 'No se pudo descargar el archivo desde el endpoint público.'];
            }
        }

        $analizador = new AnalizadorTDRService();

        if ($tipoAnalisis === 'direccionamiento') {
            $resultado = $analizador->analyzeDireccionamiento($filePath);
        } else {
            $resultado = $analizador->analyzeSingle($filePath);
        }

        if (!$resultado['success']) {
            return $resultado;
        }

        $analisisData = $tipoAnalisis === 'direccionamiento'
            ? ($resultado['data'] ?? [])
            : $this->normalizeAnalysisKeys($resultado['data'] ?? []);

        $contextoContrato = $this->buildContextoContrato($contratoData, $archivoPersistido);

        $analisisModel = $this->persistence->storeAnalysis(
            $archivoPersistido,
            $analisisData,
            $resultado,
            $contextoContrato,
            [
                'proveedor' => config('services.analizador_tdr.provider', 'gemini'),
                'modelo' => config('services.analizador_tdr.model'),
                'tipo_analisis' => $tipoAnalisis,
            ]
        );

        $payload = $this->persistence->buildPayloadFromAnalysis($analisisModel, false);

        return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
    }

    /**
     * Analizar un archivo ya persistido en el repositorio local (flujo público).
     */
    public function analizarArchivoLocal(
        ContratoArchivo $archivoPersistido,
        ?array $contratoData = null,
        string $target = 'dashboard',
        bool $forceRefresh = false,
        string $tipoAnalisis = 'general'
    ): array {
        try {
            $this->debug('Inicio análisis TDR local', [
                'contrato_archivo_id' => $archivoPersistido->id,
                'force_refresh' => $forceRefresh,
            ]);

            if (!config('services.analizador_tdr.enabled', false)) {
                return [
                    'success' => false,
                    'error' => 'El servicio de Análisis TDR no está habilitado. Habilítalo en Configuración.',
                ];
            }

            // ── Fast-path ──
            if ($cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis)) {
                $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
            }

            // ── Lock atómico (misma lógica que analizarDesdeSeace) ──
            $archivoSeaceId = $archivoPersistido->id_archivo_seace ?? $archivoPersistido->id;
            $lock = Cache::lock(
                self::ANALYSIS_LOCK_PREFIX . $tipoAnalisis . ':' . $archivoSeaceId,
                self::ANALYSIS_LOCK_TTL
            );

            $acquired = $lock->block(self::ANALYSIS_LOCK_WAIT);

            if (!$acquired) {
                $cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis);
                if ($cachedAnalisis) {
                    $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                    return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
                }

                return [
                    'success' => false,
                    'error' => 'El análisis está en proceso por otro usuario. Inténtalo en unos segundos.',
                ];
            }

            try {
                // ── Double-check ──
                $cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh, $tipoAnalisis);
                if ($cachedAnalisis) {
                    $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);
                    return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
                }

                $filePath = $this->persistence->getAbsolutePath($archivoPersistido);

                if (!$filePath || !is_file($filePath)) {
                    return [
                        'success' => false,
                        'error' => 'El archivo no está disponible en el repositorio local.',
                    ];
                }

                $analizador = new AnalizadorTDRService();

                if ($tipoAnalisis === 'direccionamiento') {
                    $resultado = $analizador->analyzeDireccionamiento($filePath);
                } else {
                    $resultado = $analizador->analyzeSingle($filePath);
                }

                if (!($resultado['success'] ?? false)) {
                    return $resultado;
                }

                $analisisData = $tipoAnalisis === 'direccionamiento'
                    ? ($resultado['data'] ?? [])
                    : $this->normalizeAnalysisKeys($resultado['data'] ?? []);
                $contextoContrato = $this->buildContextoContrato($contratoData, $archivoPersistido);

                $analisisModel = $this->persistence->storeAnalysis(
                    $archivoPersistido,
                    $analisisData,
                    $resultado,
                    $contextoContrato,
                    [
                        'proveedor' => config('services.analizador_tdr.provider', 'gemini'),
                        'modelo' => config('services.analizador_tdr.model'),
                        'tipo_analisis' => $tipoAnalisis,
                    ]
                );

                $payload = $this->persistence->buildPayloadFromAnalysis($analisisModel, false);

                return $this->buildResponseFromPayload($payload, $target, $tipoAnalisis);
            } finally {
                $lock->release();
            }

        } catch (Exception $e) {
            Log::error('TDR: Error en análisis local', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Intentar obtener un análisis previamente cacheado sin invocar al LLM.
     */
    public function obtenerAnalisisDesdeCache(int $idContratoArchivo, string $target = 'dashboard'): ?array
    {
        $archivoPersistido = ContratoArchivo::where('id_archivo_seace', $idContratoArchivo)->first();

        if (!$archivoPersistido) {
            return null;
        }

        $cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido);

        if (!$cachedAnalisis) {
            return null;
        }

        $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);

        return $this->buildResponseFromPayload($payload, $target);
    }

    protected function buildResponseFromPayload(array $payload, string $target, string $tipoAnalisis = 'general'): array
    {
        $response = [
            'success' => true,
            'data' => $payload,
            'tipo_analisis' => $tipoAnalisis,
        ];

        if ($target === 'telegram') {
            if ($tipoAnalisis === 'direccionamiento') {
                $response['formatted']['telegram'] = $this->formatter->formatDireccionamientoForTelegram(
                    $payload['analisis'] ?? [],
                    $payload['archivo'] ?? 'Archivo',
                    $payload['contexto_contrato'] ?? null
                );
            } else {
                $response['formatted']['telegram'] = $this->formatter->formatForTelegram(
                    $payload['analisis'] ?? [],
                    $payload['archivo'] ?? 'Archivo',
                    $payload['contexto_contrato'] ?? null
                );
            }
        }

        return $response;
    }

    protected function buildContextoContrato(?array $contratoData, ?ContratoArchivo $archivo): ?array
    {
        $contexto = $contratoData ?? $archivo?->datos_contrato ?? null;

        if (!$contexto && $archivo) {
            $contexto = [
                'nomEntidad' => $archivo->entidad,
                'desContratacion' => $archivo->codigo_proceso,
            ];
        }

        if (!$contexto) {
            return null;
        }

        return [
            'entidad' => $contexto['nomEntidad'] ?? 'No disponible',
            'codigo_proceso' => $contexto['desContratacion'] ?? 'No disponible',
            'objeto' => $contexto['nomObjetoContrato'] ?? $contexto['objeto'] ?? 'No disponible',
            'descripcion' => $contexto['desObjetoContrato'] ?? $contexto['descripcion'] ?? null,
            'fecha_publicacion' => $contexto['fecPublica'] ?? $contexto['fecha_publicacion'] ?? null,
            'fecha_cierre' => $contexto['fecFinCotizacion'] ?? $contexto['fin_cotizacion'] ?? null,
            'estado' => $contexto['nomEstadoContrato'] ?? $contexto['estado'] ?? 'No disponible',
            'etapa' => $contexto['nomEtapaContratacion'] ?? $contexto['etapa_contratacion'] ?? 'No disponible',
        ];
    }

    protected function normalizeAnalysisKeys($analisis): array
    {
        if (!is_array($analisis)) {
            return ['monto' => null];
        }

        $candidateKeys = ['presupuesto_referencial', 'monto_referencial', 'monto'];
        $valor = null;

        foreach ($candidateKeys as $key) {
            if (array_key_exists($key, $analisis) && $this->valueHasContent($analisis[$key])) {
                $valor = $analisis[$key];
                break;
            }
        }

        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $analisis)) {
                $analisis[$key] = $valor;
            }
        }

        return $analisis;
    }

    protected function valueHasContent($value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('TDR: ' . $message, $context);
    }
}

