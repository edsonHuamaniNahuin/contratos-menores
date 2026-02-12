<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class AnalizadorTDRService
{
    protected string $baseUrl;
    protected int $timeout;
    protected bool $enabled;
    protected bool $debugLogging;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.analizador_tdr.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = config('services.analizador_tdr.timeout', 60);
        $this->enabled = config('services.analizador_tdr.enabled', false);
        $this->debugLogging = (bool) config('tdr.debug_logs', config('services.analizador_tdr.debug_logs', false));
    }

    /**
     * Verificar salud del servicio
     */
    public function healthCheck(): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'Servicio deshabilitado en configuración'
            ];
        }

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar un TDR individual
     *
     * @param string $filePath Ruta al archivo PDF
     * @return array
     */
    public function analyzeSingle(string $filePath): array
    {
        if (!$this->enabled) {
            Log::warning('AnalizadorTDR: Servicio deshabilitado', [
                'enabled' => $this->enabled,
                'config' => config('services.analizador_tdr'),
            ]);
            throw new Exception('Servicio AnalizadorTDR deshabilitado');
        }

        if (!file_exists($filePath)) {
            Log::error('AnalizadorTDR: Archivo no encontrado', [
                'file_path' => $filePath,
                'exists' => file_exists($filePath),
                'storage_temp' => storage_path('app/temp'),
            ]);
            throw new Exception("Archivo no encontrado: {$filePath}");
        }

        try {
            $fileSize = filesize($filePath);
            $fileName = basename($filePath);
            $fullUrl = "{$this->baseUrl}/analyze-tdr";

            $this->debug('Inicio de análisis', [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size_bytes' => $fileSize,
                'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                'full_url' => $fullUrl,
                'base_url' => $this->baseUrl,
                'timeout' => $this->timeout,
                'enabled' => $this->enabled,
            ]);

            // Verificar salud del servicio primero
            $healthCheck = $this->healthCheck();
            $this->debug('Health check antes de análisis', [
                'healthy' => $healthCheck['healthy'] ?? false,
                'health_data' => $healthCheck,
            ]);

            // Preparar el archivo
            $fileContents = file_get_contents($filePath);
            $this->debug('Archivo leído', [
                'content_length' => strlen($fileContents),
            ]);

            $this->debug('Enviando petición HTTP');

            $response = Http::timeout($this->timeout)
                ->attach(
                    'file',
                    $fileContents,
                    $fileName
                )
                ->post($fullUrl);

            $this->debug('Respuesta recibida', [
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'reason' => $response->reason(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 300),
            ]);

            if (!$response->successful()) {
                Log::error('AnalizadorTDR: ========== ERROR HTTP ==========', [
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'body' => $response->body(),
                    'url' => $fullUrl,
                    'file_name' => $fileName,
                ]);
                throw new Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
            }

            $data = $response->json();

            Log::info('AnalizadorTDR: análisis completado', [
                'success' => $data['success'] ?? false,
                'file' => $fileName,
                'data_keys' => array_keys($data ?? []),
            ]);

            return $data;

        } catch (Exception $e) {
            Log::error('AnalizadorTDR: ========== EXCEPCIÓN ==========', [
                'file' => basename($filePath),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace_preview' => substr($e->getTraceAsString(), 0, 500),
            ]);

            throw $e;
        }
    }

    /**
     * Analizar múltiples TDRs en lote (batch processing)
     *
     * @param array $filePaths Array de rutas a archivos PDF
     * @return array
     */
    public function analyzeBatch(array $filePaths): array
    {
        if (!$this->enabled) {
            throw new Exception('Servicio AnalizadorTDR deshabilitado');
        }

        if (empty($filePaths)) {
            throw new Exception('No se proporcionaron archivos para analizar');
        }

        if (count($filePaths) > 10) {
            throw new Exception('Máximo 10 archivos por lote (límite del servicio)');
        }

        try {
            $this->debug('Enviando batch para análisis', [
                'count' => count($filePaths)
            ]);

            $httpClient = Http::timeout($this->timeout * 2); // Más tiempo para batch

            foreach ($filePaths as $index => $filePath) {
                if (!file_exists($filePath)) {
                    throw new Exception("Archivo no encontrado: {$filePath}");
                }

                $httpClient->attach(
                    "files[{$index}]",
                    file_get_contents($filePath),
                    basename($filePath)
                );
            }

            $response = $httpClient->post("{$this->baseUrl}/batch/analyze");

            if (!$response->successful()) {
                throw new Exception('Error HTTP ' . $response->status() . ': ' . $response->body());
            }

            $data = $response->json();

            Log::info('AnalizadorTDR: batch completado', [
                'success_count' => count($data['results'] ?? []),
                'total' => count($filePaths)
            ]);

            return $data;

        } catch (Exception $e) {
            Log::error('AnalizadorTDR: Error en batch', [
                'count' => count($filePaths),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Analizar TDR descargado de SEACE
     *
     * @param int $idContratoArchivo ID del archivo en SEACE
     * @param string $nombreArchivo Nombre del archivo
     * @return array
     */
    public function analyzeFromSeace(int $idContratoArchivo, string $nombreArchivo): array
    {
        // Buscar archivo en storage temporal
        $tempPath = storage_path("app/temp/{$nombreArchivo}");

        if (!file_exists($tempPath)) {
            throw new Exception("Archivo no encontrado en storage temporal");
        }

        return $this->analyzeSingle($tempPath);
    }

    /**
     * Obtener información del servicio
     */
    public function getServiceInfo(): array
    {
        return [
            'enabled' => $this->enabled,
            'url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'status' => $this->enabled ? $this->healthCheck() : ['success' => false, 'error' => 'Deshabilitado']
        ];
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('AnalizadorTDR: ' . $message, $context);
    }
}
