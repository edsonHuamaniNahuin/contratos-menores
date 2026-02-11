<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Maneja la descarga y listado de archivos TDR expuestos en el dominio público del SEACE.
 */
class SeacePublicArchivoService
{
    protected string $archivosBaseUrl;
    protected string $frontendOrigin;
    protected string $referer;
    protected bool $debugLogging;

    public function __construct()
    {
        $this->archivosBaseUrl = rtrim((string) (
            config('services.seace.public_archivos_base_url')
            ?? config('services.seace.public_base_url')
            ?? config('services.seace.base_url', '')
        ), '/');

        $originFallback = config('services.seace.public_frontend_origin')
            ?? config('services.seace.frontend_origin', 'https://prod6.seace.gob.pe');

        $this->frontendOrigin = rtrim((string) $originFallback, '/');
        $this->referer = (string) (
            config('services.seace.public_archivos_referer')
            ?? config('services.seace.public_referer')
            ?? ($this->frontendOrigin ? $this->frontendOrigin . '/cotizacion/contrataciones' : '')
        );

        $this->debugLogging = (bool) config('services.seace.debug_logs', false);
    }

    /**
     * Lista los archivos asociados a un contrato usando los endpoints públicos.
     */
    public function listarArchivos(int $idContrato, int $tipoArchivo = 1): array
    {
        $url = $this->buildUrl("archivo/archivos-publico/listar-archivos-contrato/{$idContrato}/{$tipoArchivo}");

        try {
            /** @var Response $response */
            $response = Http::withHeaders($this->headers())
            ->timeout(20)
            ->get($url);

            if (!$response->successful()) {
                return $this->errorPayload('Error al listar archivos', $response);
            }

            $data = $response->json();

            if ($this->debugLogging) {
                Log::info('SeacePublicArchivoService:listarArchivos', [
                    'url' => $url,
                    'id_contrato' => $idContrato,
                    'count' => is_array($data) ? count($data) : 0,
                ]);
            }

            return [
                'success' => true,
                'data' => $data ?? [],
            ];
        } catch (Exception $e) {
            Log::error('SeacePublicArchivoService:listarArchivos exception', [
                'id_contrato' => $idContrato,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudieron recuperar los archivos públicos',
            ];
        }
    }

    /**
     * Descarga un archivo puntual del endpoint público (flujo binario).
     */
    public function descargarArchivo(int $idContratoArchivo): array
    {
        $url = $this->buildUrl("archivo/archivos-publico/descargar-archivo-contrato/{$idContratoArchivo}");

        try {
            /** @var Response $response */
            $response = Http::withHeaders($this->headers())
            ->timeout(60)
            ->get($url);

            if (!$response->successful()) {
                return $this->errorPayload('Error al descargar archivo', $response);
            }

            $contents = (string) $response->body();
            $mime = $response->header('Content-Type', 'application/octet-stream');

            if (!$this->isPdfPayload($contents, $mime)) {
                Log::warning('SeacePublicArchivoService: respuesta no PDF', [
                    'id_contrato_archivo' => $idContratoArchivo,
                    'status' => $response->status(),
                    'content_type' => $mime,
                    'body_preview' => substr($contents, 0, 200),
                ]);

                return [
                    'success' => false,
                    'error' => 'El portal público devolvió un archivo inválido (no es PDF). Intenta nuevamente en unos minutos.',
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'filename' => $this->resolveFilename($response),
                'mime' => 'application/pdf',
                'contents' => $contents,
            ];
        } catch (Exception $e) {
            Log::error('SeacePublicArchivoService:descargarArchivo exception', [
                'id_contrato_archivo' => $idContratoArchivo,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudo descargar el archivo solicitado',
            ];
        }
    }

    protected function headers(): array
    {
        return [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'es-419,es;q=0.9',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Origin' => $this->frontendOrigin ?: 'https://prod6.seace.gob.pe',
            'Referer' => $this->referer ?: (($this->frontendOrigin ?: 'https://prod6.seace.gob.pe') . '/cotizacion/contrataciones'),
        ];
    }

    protected function buildUrl(string $path): string
    {
        return $this->archivosBaseUrl . '/' . ltrim($path, '/');
    }

    protected function resolveFilename(Response $response): string
    {
        $contentDisposition = $response->header('Content-Disposition');
        if ($contentDisposition && preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $matches)) {
            return $this->normalizeFilename($matches[1]);
        }

        return 'tdr.pdf';
    }

    protected function errorPayload(string $message, Response $response): array
    {
        Log::warning('SeacePublicArchivoService HTTP error', [
            'message' => $message,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'error' => $message,
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    protected function isPdfPayload(string $binary, ?string $headerMime = null): bool
    {
        if ($binary === '') {
            return false;
        }

        $prefix = substr($binary, 0, 12) ?: '';

        if (str_contains($prefix, '%PDF')) {
            return true;
        }

        if ($this->looksLikeHtml($binary)) {
            return false;
        }

        return $headerMime && stripos($headerMime, 'pdf') !== false;
    }

    protected function looksLikeHtml(string $binary): bool
    {
        $snippet = strtolower(ltrim(substr($binary, 0, 50)));

        return str_starts_with($snippet, '<!doctype')
            || str_starts_with($snippet, '<html')
            || str_starts_with($snippet, '<');
    }

    protected function normalizeFilename(string $filename): string
    {
        $clean = trim(str_replace(['\\', '/'], '', $filename));

        if ($clean === '') {
            return 'tdr.pdf';
        }

        $extension = strtolower(pathinfo($clean, PATHINFO_EXTENSION) ?: '');

        if ($extension !== 'pdf') {
            $base = pathinfo($clean, PATHINFO_FILENAME) ?: 'tdr';
            $base = trim(preg_replace('/pdf$/i', '', $base) ?? '', ' ._-');
            $clean = ($base !== '' ? $base : 'tdr') . '.pdf';
        }

        if (!str_ends_with(strtolower($clean), '.pdf')) {
            $clean .= '.pdf';
        }

        return $clean;
    }
}
