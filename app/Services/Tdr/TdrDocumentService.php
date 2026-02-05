<?php

namespace App\Services\Tdr;

use App\Models\ContratoArchivo;
use App\Models\CuentaSeace;
use App\Services\SeaceScraperService;
use Exception;
use Illuminate\Support\Facades\Log;

class TdrDocumentService
{
    public function __construct(protected TdrPersistenceService $persistence)
    {
    }

    public function ensureLocalFile(ContratoArchivo $archivo, CuentaSeace $cuenta, string $nombreArchivo): string
    {
        if ($localPath = $this->persistence->getAbsolutePath($archivo)) {
            Log::info('TDR: Usando archivo persistido', [
                'contrato_archivo_id' => $archivo->id,
                'path' => $localPath,
            ]);

            return $localPath;
        }

        Log::info('TDR: Descargando archivo desde SEACE', [
            'contrato_archivo_id' => $archivo->id,
            'id_archivo_seace' => $archivo->id_archivo_seace,
        ]);

        $scraper = new SeaceScraperService($cuenta);
        $downloadUrl = sprintf(
            '%s/archivo/archivos/descargar-archivo-contrato/%s',
            config('services.seace.base_url'),
            $archivo->id_archivo_seace
        );

        $referer = 'https://prod6.seace.gob.pe/cotizacion/contrataciones';
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

        $localPath = $this->persistence->storeBinary(
            $archivo,
            $response->body(),
            $contentType,
            $nombreArchivo
        );

        Log::info('TDR: Archivo guardado en storage', [
            'path' => $localPath,
            'tamano' => @filesize($localPath) ?: null,
        ]);

        return $localPath;
    }
}
