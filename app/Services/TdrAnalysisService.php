<?php

namespace App\Services;

use App\Models\ContratoArchivo;
use App\Models\CuentaSeace;
use App\Services\Tdr\TdrDocumentService;
use App\Services\Tdr\TdrPersistenceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio centralizado para análisis de TDR
 * Usado por PruebaEndpoints (Livewire) y TelegramBotListener (Command)
 */
class TdrAnalysisService
{
    protected string $baseUrl;
    protected TdrAnalysisFormatter $formatter;
    protected TdrPersistenceService $persistence;
    protected TdrDocumentService $documentService;

    public function __construct(?TdrAnalysisFormatter $formatter = null)
    {
        $this->baseUrl = config('services.seace.base_url');
        $this->formatter = $formatter ?? new TdrAnalysisFormatter();
        $this->persistence = new TdrPersistenceService();
        $this->documentService = new TdrDocumentService($this->persistence);
    }

    /**
     * Analizar un TDR desde SEACE
     *
     * @param int $idContratoArchivo ID del archivo en SEACE
     * @param string $nombreArchivo Nombre del archivo
     * @param CuentaSeace $cuenta Cuenta SEACE para autenticación
     * @param array|null $contratoData Datos adicionales del contrato (opcional)
     * @param string $target Destino del resultado ('dashboard' por defecto, 'telegram' para texto legible)
     * @return array Resultado del análisis con estructura: ['success' => bool, 'data' => array, 'formatted' => array|null, 'error' => string]
     */
    public function analizarDesdeSeace(
        int $idContratoArchivo,
        string $nombreArchivo,
        CuentaSeace $cuenta,
        ?array $contratoData = null,
        string $target = 'dashboard',
        bool $forceRefresh = false
    ): array {
        try {
            Log::info('TDR: ========== INICIO ANÁLISIS TDR ==========', [
                'idContratoArchivo' => $idContratoArchivo,
                'nombreArchivo' => $nombreArchivo,
                'cuenta_id' => $cuenta->id,
                'force_refresh' => $forceRefresh,
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

            if ($cachedAnalisis = $this->persistence->getCachedAnalysis($archivoPersistido, $forceRefresh)) {
                Log::info('TDR: Usando análisis en caché', [
                    'contrato_archivo_id' => $archivoPersistido->id,
                    'analisis_id' => $cachedAnalisis->id,
                ]);

                $payload = $this->persistence->buildPayloadFromAnalysis($cachedAnalisis, true);

                return $this->buildResponseFromPayload($payload, $target);
            }

            $filePath = $this->documentService->ensureLocalFile($archivoPersistido, $cuenta, $nombreArchivo);

            $analizador = new AnalizadorTDRService();
            $resultado = $analizador->analyzeSingle($filePath);

            if (!$resultado['success']) {
                return $resultado;
            }

            $analisisData = $this->normalizeAnalysisKeys($resultado['data'] ?? []);
            $contextoContrato = $this->buildContextoContrato($contratoData, $archivoPersistido);

            $analisisModel = $this->persistence->storeAnalysis(
                $archivoPersistido,
                $analisisData,
                $resultado,
                $contextoContrato,
                [
                    'proveedor' => config('services.analizador_tdr.provider', 'gemini'),
                    'modelo' => config('services.analizador_tdr.model'),
                ]
            );

            $payload = $this->persistence->buildPayloadFromAnalysis($analisisModel, false);

            return $this->buildResponseFromPayload($payload, $target);

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

    protected function buildResponseFromPayload(array $payload, string $target): array
    {
        $response = [
            'success' => true,
            'data' => $payload,
        ];

        if ($target === 'telegram') {
            $response['formatted']['telegram'] = $this->formatter->formatForTelegram(
                $payload['analisis'] ?? [],
                $payload['archivo'] ?? 'Archivo',
                $payload['contexto_contrato'] ?? null
            );
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
}

