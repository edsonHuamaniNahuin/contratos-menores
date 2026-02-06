<?php

namespace App\Services\Tdr;

use App\Models\ContratoArchivo;
use App\Models\CuentaSeace;
use App\Services\SeaceScraperService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TdrDocumentService
{
    protected bool $debugLogging;
    protected string $downloadReferer;

    public function __construct(protected TdrPersistenceService $persistence)
    {
        $this->debugLogging = (bool) config('tdr.debug_logs', config('services.analizador_tdr.debug_logs', false));

        $frontendOrigin = rtrim((string) config('services.seace.frontend_origin', ''), '/');

        $referer = rtrim((string) config('services.seace.contrataciones_referer', ''), '/');
        if (empty($referer) && !empty($frontendOrigin)) {
            $referer = $frontendOrigin . '/cotizacion/contrataciones';
        }

        if (empty($referer)) {
            throw new RuntimeException('Configura SEACE_CONTRATACIONES_REFERER en el .env');
        }

        $this->downloadReferer = $referer;
    }

    public function ensureLocalFile(ContratoArchivo $archivo, CuentaSeace $cuenta, string $nombreArchivo): string
    {
        if ($localPath = $this->persistence->getAbsolutePath($archivo)) {
            if (is_file($localPath) && @filesize($localPath) > 0) {
                $this->debug('Usando archivo persistido', [
                    'contrato_archivo_id' => $archivo->id,
                    'path' => $localPath,
                ]);

                return $localPath;
            }

            Log::warning('TDR: Archivo persistido inválido, se eliminará', [
                'contrato_archivo_id' => $archivo->id,
                'path' => $localPath,
            ]);

            $this->persistence->purgeStoredFile($archivo);
        }

        $this->debug('Descargando archivo desde SEACE', [
            'contrato_archivo_id' => $archivo->id,
            'id_archivo_seace' => $archivo->id_archivo_seace,
        ]);

        $scraper = new SeaceScraperService($cuenta);
        $baseUrl = rtrim((string) config('services.seace.base_url', ''), '/');

        if (empty($baseUrl)) {
            throw new RuntimeException('Configura SEACE_BASE_URL para descargar archivos');
        }

        $downloadUrl = sprintf(
            '%s/archivo/archivos/descargar-archivo-contrato/%s',
            $baseUrl,
            $archivo->id_archivo_seace
        );

        $referer = $this->downloadReferer;
        $downloadHeaders = [
            'Accept' => 'application/octet-stream,application/json,text/plain,*/*',
            'Content-Type' => null,
        ];

        $response = $scraper->makeResilientRequest(
            'GET',
            $downloadUrl,
            [],
            [],
            $referer,
            $downloadHeaders
        );

        if (!$response->successful()) {
            $statusCode = $response->status();

            if ($statusCode === 500) {
                throw new Exception(
                    "⚠️ El SEACE está temporalmente fuera de servicio.\n\n" .
                    "Por favor, intenta nuevamente en unos minutos.\n\n" .
                    "💡 Tip: El archivo puede estar siendo procesado o el servidor está saturado."
                );
            }

            if ($statusCode === 404) {
                throw new Exception(
                    "❌ El archivo TDR no fue encontrado en el SEACE.\n\n" .
                    "Posiblemente fue eliminado o el proceso cambió de estado."
                );
            }

            throw new Exception(
                "⚠️ Error al descargar archivo del SEACE (HTTP {$statusCode}).\n\n" .
                "Por favor, intenta más tarde."
            );
        }

        $contentType = $response->header('Content-Type');
        if (strpos((string) $contentType, 'application/json') !== false) {
            $errorData = $response->json();
            $errorMsg = $errorData['message'] ?? 'Error desconocido del servidor SEACE';
            throw new Exception("⚠️ SEACE: {$errorMsg}");
        }

        $binary = $response->body();
        $detectedMime = $this->resolvePdfMime($binary, $contentType) ?? 'application/octet-stream';

        if ($detectedMime !== 'application/pdf') {
            Log::warning('TDR: Descarga sin cabecera PDF, continuando por compatibilidad', [
                'contrato_archivo_id' => $archivo->id,
                'content_type' => $contentType,
                'detected_mime' => $detectedMime,
            ]);
        }

        $safeName = $this->sanitizePdfFilename($nombreArchivo);

        $localPath = $this->persistence->storeBinary(
            $archivo,
            $binary,
            $detectedMime,
            $safeName
        );

        $this->debug('Archivo guardado en storage', [
            'path' => $localPath,
            'tamano' => @filesize($localPath) ?: null,
        ]);

        return $localPath;
    }

    protected function fileLooksPdf(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $resource = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $resource ? finfo_file($resource, $path) : null;

        if ($resource) {
            finfo_close($resource);
        }

        if ($mime === 'application/pdf') {
            return true;
        }

        $handle = fopen($path, 'rb');

        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 8) ?: '';
        fclose($handle);

        return $this->binaryLooksPdf($bytes);
    }

    protected function resolvePdfMime(string $binary, ?string $contentType = null): ?string
    {
        if ($contentType && stripos($contentType, 'application/pdf') !== false) {
            return 'application/pdf';
        }

        $resource = finfo_open(FILEINFO_MIME_TYPE);
        $detected = $resource ? finfo_buffer($resource, $binary) : null;

        if ($resource) {
            finfo_close($resource);
        }

        if ($detected === 'application/pdf') {
            return $detected;
        }

        return $this->binaryLooksPdf($binary) ? 'application/pdf' : null;
    }

    protected function sanitizePdfFilename(string $nombreOriginal): string
    {
        $baseName = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $slug = Str::slug($baseName) ?: 'tdr';

        return $slug . '.pdf';
    }

    protected function binaryLooksPdf(string $binary): bool
    {
        if (strlen($binary) < 4) {
            return false;
        }

        $signature = substr($binary, 0, 4);

        return $signature === '%PDF';
    }

    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugLogging) {
            return;
        }

        Log::debug('TDR: ' . $message, $context);
    }
}
