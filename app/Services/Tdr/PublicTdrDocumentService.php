<?php

namespace App\Services\Tdr;

use App\Models\ContratoArchivo;
use App\Services\SeacePublicArchivoService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicTdrDocumentService
{
    public function __construct(
        protected TdrPersistenceService $persistence,
        protected SeacePublicArchivoService $archivoService
    ) {
    }

    /**
     * Garantiza que el archivo indicado exista en el repositorio local.
     */
    public function ensureLocalArchivo(int $idContrato, array $archivoMeta, ?array $contratoSnapshot = null): ContratoArchivo
    {
        $idContratoArchivo = (int) ($archivoMeta['idContratoArchivo'] ?? 0);

        if ($idContratoArchivo <= 0) {
            throw new Exception('El identificador del archivo público no es válido.');
        }

        $nombreOriginal = $archivoMeta['nombre']
            ?? $archivoMeta['nombreTipoArchivo']
            ?? 'tdr.pdf';

        $contratoSeaceId = $contratoSnapshot['idContrato']
            ?? $contratoSnapshot['id_contrato_seace']
            ?? $idContrato;

        $archivoPersistido = $this->persistence->resolveArchivo(
            $idContratoArchivo,
            $nombreOriginal,
            $contratoSeaceId,
            $contratoSnapshot
        );

        if ($this->hasUsableLocalFile($archivoPersistido)) {
            return $archivoPersistido;
        }

        $descarga = $this->archivoService->descargarArchivo($idContratoArchivo);

        if (!($descarga['success'] ?? false)) {
            throw new Exception($descarga['error'] ?? 'El portal público no devolvió el archivo solicitado.');
        }

        $binary = (string) ($descarga['contents'] ?? '');

        if ($binary === '') {
            throw new Exception('El archivo descargado está vacío.');
        }

        $mime = $this->resolvePdfMime($binary, $descarga['mime'] ?? null);

        if ($mime !== 'application/pdf') {
            throw new Exception('El portal público devolvió un archivo inválido (no es un PDF).');
        }

        $safeName = $this->sanitizeFilename($descarga['filename'] ?? $nombreOriginal);

        $this->persistence->storeBinary($archivoPersistido, $binary, $mime, $safeName);

        Log::info('PublicTdrDocumentService: archivo almacenado', [
            'contrato_archivo_id' => $archivoPersistido->id,
            'id_contrato_seace' => $archivoPersistido->id_contrato_seace,
            'id_archivo_seace' => $archivoPersistido->id_archivo_seace,
        ]);

        return $archivoPersistido;
    }

    /**
     * Fuerza la re-descarga del archivo, ignorando cache local.
     */
    public function refreshLocalArchivo(int $idContrato, array $archivoMeta, ?array $contratoSnapshot = null): ContratoArchivo
    {
        $idContratoArchivo = (int) ($archivoMeta['idContratoArchivo'] ?? 0);

        if ($idContratoArchivo <= 0) {
            throw new Exception('El identificador del archivo publico no es valido.');
        }

        $nombreOriginal = $archivoMeta['nombre']
            ?? $archivoMeta['nombreTipoArchivo']
            ?? 'tdr.pdf';

        $contratoSeaceId = $contratoSnapshot['idContrato']
            ?? $contratoSnapshot['id_contrato_seace']
            ?? $idContrato;

        $archivoPersistido = $this->persistence->resolveArchivo(
            $idContratoArchivo,
            $nombreOriginal,
            $contratoSeaceId,
            $contratoSnapshot
        );

        $this->persistence->purgeStoredFile($archivoPersistido);

        return $this->ensureLocalArchivo($idContrato, $archivoMeta, $contratoSnapshot);
    }

    protected function hasUsableLocalFile(ContratoArchivo $archivo): bool
    {
        $localPath = $this->persistence->getAbsolutePath($archivo);

        if ($localPath && is_file($localPath) && filesize($localPath) > 0) {
            return true;
        }

        if ($localPath) {
            Log::warning('PublicTdrDocumentService: archivo corrupto, se purga', [
                'contrato_archivo_id' => $archivo->id,
                'path' => $localPath,
            ]);
            $this->persistence->purgeStoredFile($archivo);
        }

        return false;
    }

    protected function sanitizeFilename(string $original): string
    {
        $trimmed = trim($original);

        if ($trimmed === '') {
            return 'tdr.pdf';
        }

        $trimmed = str_replace(['\\', '/'], '', $trimmed);
        $extension = strtolower(pathinfo($trimmed, PATHINFO_EXTENSION) ?: '');

        if ($extension !== 'pdf') {
            $base = pathinfo($trimmed, PATHINFO_FILENAME) ?: 'tdr';

            if ($extension === '') {
                $base = preg_replace('/pdf$/i', '', $base) ?: $base;
            }

            $base = trim($base, ' ._-');
            $trimmed = ($base !== '' ? $base : 'tdr') . '.pdf';
        }

        if (!Str::endsWith(strtolower($trimmed), '.pdf')) {
            $trimmed .= '.pdf';
        }

        return $trimmed;
    }

    protected function resolvePdfMime(string $binary, ?string $headerMime = null): ?string
    {
        if ($headerMime && stripos($headerMime, 'application/pdf') !== false) {
            return 'application/pdf';
        }

        return $this->binaryLooksPdf($binary) ? 'application/pdf' : null;
    }

    protected function binaryLooksPdf(string $binary): bool
    {
        $signature = substr($binary, 0, 8);

        if ($signature === false) {
            return false;
        }

        return str_contains($signature, '%PDF');
    }
}
