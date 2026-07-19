<?php

namespace App\Services;

use App\Models\TdrAnalisisMayor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Servicio de análisis de TDR para Contratos Mayores.
 *
 * Descarga el PDF desde la URL directa, lo envía al microservicio Python
 * de IA, y persiste el resultado en tdr_analisis_mayores.
 *
 * Separado de TdrAnalysisService (Menores) para evitar acoplamiento.
 */
class MayoresTdrService
{
    private const LOCK_PREFIX = 'tdr:mayor:analyze:';
    private const LOCK_TTL = 180;
    private const LOCK_WAIT = 90;

    protected function debug(string $msg, array $ctx = []): void
    {
        Log::debug("MayoresTdr: {$msg}", $ctx);
    }

    /**
     * Analiza un TDR de Contrato Mayor.
     *
     * @param string      $ocid      Identificador OCDS del contrato
     * @param string      $pdfUrl    URL directa al PDF de bases
     * @param array       $ctx       Contexto del contrato (entidad, nomenclatura, etc.)
     * @param int|null    $userId    Usuario que solicita el análisis
     * @param string|null $localPath Ruta local del PDF si ya fue descargado/extraído
     */
    public function analizar(string $ocid, string $pdfUrl, array $ctx = [], ?int $userId = null, ?string $localPath = null): array
    {
        if (!config('services.analizador_tdr.enabled', false)) {
            return ['success' => false, 'error' => 'El servicio de Análisis TDR no está habilitado.'];
        }

        // ── Cache check ──
        $cacheado = TdrAnalisisMayor::where('ocid', $ocid)
            ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
            ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
            ->latest('analizado_en')
            ->first();

        if ($cacheado) {
            $this->debug('Usando análisis cacheado', ['ocid' => $ocid]);
            return [
                'success' => true,
                'data' => $cacheado->payload['data'] ?? $cacheado->resumen ?? [],
                'cached' => true,
                'analisis_id' => $cacheado->id,
            ];
        }

        // ── Lock to prevent concurrent analysis of the same contract ──
        $lockKey = self::LOCK_PREFIX . $ocid;
        $lock = Cache::lock($lockKey, self::LOCK_TTL);

        if (!$lock->block(self::LOCK_WAIT)) {
            $cacheado = TdrAnalisisMayor::where('ocid', $ocid)
                ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
                ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
                ->first();

            if ($cacheado) {
                return ['success' => true, 'data' => $cacheado->payload['data'] ?? [], 'cached' => true];
            }

            return ['success' => false, 'error' => 'El análisis está en proceso. Intenta en unos segundos.'];
        }

        try {
            // Double-check after lock
            $cacheado = TdrAnalisisMayor::where('ocid', $ocid)
                ->where('estado', TdrAnalisisMayor::ESTADO_EXITOSO)
                ->where('tipo', TdrAnalisisMayor::TIPO_GENERAL)
                ->first();

            if ($cacheado) {
                return ['success' => true, 'data' => $cacheado->payload['data'] ?? [], 'cached' => true];
            }

            // ── Download PDF (or use local path) ──
            if ($localPath && is_file($localPath)) {
                $this->debug('Usando PDF local', ['path' => $localPath]);
                $tempPath = $localPath;
                $cleanupTemp = false;
            } else {
                $this->debug('Descargando documento', ['url' => $pdfUrl]);
                $pdfBytes = Http::timeout(300)->get($pdfUrl)->body();

                if (empty($pdfBytes)) {
                    return ['success' => false, 'error' => 'No se pudo descargar el documento.'];
                }

                // Detectar formato real desde el contenido (NO desde URL sin extensión)
                $ext = $this->detectarExtensionPorContenido($pdfBytes, $pdfUrl);

                $tempPath = storage_path('app/temp/' . Str::uuid() . '.' . $ext);
                $tempDir = dirname($tempPath);
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                file_put_contents($tempPath, $pdfBytes);
                $cleanupTemp = true;
            }

            // ── Send to Python microservice ──
            $analizador = new AnalizadorTDRService();
            $resultado = $analizador->analyzeSingle($tempPath, 'mayores');

            // Clean up temp file (only if we created it)
            if ($cleanupTemp ?? true) {
                @unlink($tempPath);
            }

            if (!$resultado['success']) {
                TdrAnalisisMayor::updateOrCreate(
                    ['ocid' => $ocid],
                    [
                        'url_documento' => $pdfUrl,
                        'estado' => TdrAnalisisMayor::ESTADO_FALLIDO,
                        'error' => $resultado['error'] ?? 'Error desconocido',
                        'analizado_en' => now(),
                        'requested_by_user_id' => $userId,
                        'origin' => 'web',
                        'contexto_contrato' => $ctx,
                    ]
                );
                return $resultado;
            }

            // ── Persist result ──
            $analisis = TdrAnalisisMayor::updateOrCreate(
                ['ocid' => $ocid],
                [
                    'url_documento' => $pdfUrl,
                    'estado' => TdrAnalisisMayor::ESTADO_EXITOSO,
                    'proveedor' => config('services.analizador_tdr.provider', 'gemini'),
                    'modelo' => config('services.analizador_tdr.model'),
                    'contexto_contrato' => $ctx,
                    'resumen' => $resultado['data']['resumen_ejecutivo'] ?? null,
                    'requisitos_calificacion' => $resultado['data']['requisitos_calificacion'] ?? null,
                    'reglas_ejecucion' => $resultado['data']['reglas_ejecucion'] ?? null,
                    'penalidades' => $resultado['data']['penalidades'] ?? null,
                    'monto_referencial_text' => $resultado['data']['presupuesto_referencial'] ?? null,
                    'payload' => $resultado,
                    'duracion_ms' => $resultado['data']['duracion_ms'] ?? null,
                    'tokens_prompt' => $resultado['token_usage']['prompt_tokens'] ?? null,
                    'tokens_respuesta' => $resultado['token_usage']['completion_tokens'] ?? null,
                    'analizado_en' => now(),
                    'requested_by_user_id' => $userId,
                    'origin' => 'web',
                ]
            );

            $this->debug('Análisis completado y persistido', [
                'ocid' => $ocid,
                'analisis_id' => $analisis->id,
            ]);

            return [
                'success' => true,
                'data' => $resultado['data'] ?? [],
                'cached' => false,
                'analisis_id' => $analisis->id,
            ];

        } catch (Exception $e) {
            Log::error('MayoresTdr: excepción', [
                'ocid' => $ocid,
                'error' => $e->getMessage(),
            ]);

            TdrAnalisisMayor::updateOrCreate(
                ['ocid' => $ocid, 'tipo' => TdrAnalisisMayor::TIPO_GENERAL],
                [
                    'url_documento' => $pdfUrl,
                    'estado' => TdrAnalisisMayor::ESTADO_FALLIDO,
                    'error' => $e->getMessage(),
                    'analizado_en' => now(),
                    'requested_by_user_id' => $userId,
                    'origin' => 'web',
                ]
            );

            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            $lock->release();
        }
    }

    /**
     * Detecta la extensión real del archivo por magic bytes.
     * Las URLs de SEACE Alfresco no tienen extensión en el path.
     */
    private function detectarExtensionPorContenido(string $content, string $url): string
    {
        // 1. Por extensión de URL (si la tiene)
        $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'docx', 'doc', 'zip', 'rar'])) {
            return $ext;
        }

        // 2. Por magic bytes
        if (str_starts_with($content, "%PDF")) {
            return 'pdf';
        }

        // DOCX: es un ZIP que contiene word/document.xml
        if (str_starts_with($content, "PK\x03\x04")) {
            $tempZip = storage_path('app/temp/' . Str::uuid() . '.zip');
            $tempDir = dirname($tempZip);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            file_put_contents($tempZip, $content);

            $zip = new \ZipArchive();
            $isDocx = false;
            if ($zip->open($tempZip) === true) {
                $isDocx = $zip->locateName('[Content_Types].xml') !== false
                    && $zip->locateName('word/document.xml') !== false;
                $zip->close();
            }
            @unlink($tempZip);

            return $isDocx ? 'docx' : 'zip';
        }

        // DOC: OLE2 compound document
        if (str_starts_with($content, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return 'doc';
        }

        // 3. Fallback
        return 'pdf';
    }
}
