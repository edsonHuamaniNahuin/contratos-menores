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

        $mime = $this->resolveAllowedMime($binary, $descarga['mime'] ?? null);

        if ($mime === null) {
            throw new Exception('El portal público devolvió un archivo inválido (no es PDF/ZIP/RAR).');
        }

        $safeName = $this->sanitizeFilename(
            $descarga['filename'] ?? $nombreOriginal,
            $this->extensionFromMime($mime)
        );

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

    protected function sanitizeFilename(string $original, ?string $forcedExtension = null): string
    {
        $trimmed = trim($original);

        if ($trimmed === '') {
            return 'tdr.pdf';
        }

        $trimmed = str_replace(['\\', '/'], '', $trimmed);
        $extension = strtolower(pathinfo($trimmed, PATHINFO_EXTENSION) ?: '');
        $allowed = ['pdf', 'zip', 'rar'];
        $targetExtension = $forcedExtension ?: ($extension !== '' ? $extension : 'pdf');

        if (!in_array($targetExtension, $allowed, true)) {
            $targetExtension = 'pdf';
        }

        if (!in_array($extension, $allowed, true) || $extension !== $targetExtension) {
            $base = pathinfo($trimmed, PATHINFO_FILENAME) ?: 'tdr';
            $base = trim($base, ' ._-');
            $trimmed = ($base !== '' ? $base : 'tdr') . '.' . $targetExtension;
        }

        return $trimmed;
    }

    protected function resolveAllowedMime(string $binary, ?string $headerMime = null): ?string
    {
        if ($headerMime && stripos($headerMime, 'application/pdf') !== false) {
            return 'application/pdf';
        }

        if ($headerMime && stripos($headerMime, 'application/zip') !== false) {
            return 'application/zip';
        }

        if ($headerMime && (stripos($headerMime, 'application/x-rar-compressed') !== false
            || stripos($headerMime, 'application/vnd.rar') !== false)) {
            return 'application/x-rar-compressed';
        }

        if ($this->binaryLooksPdf($binary)) {
            return 'application/pdf';
        }

        if ($this->binaryLooksZip($binary)) {
            return 'application/zip';
        }

        if ($this->binaryLooksRar($binary)) {
            return 'application/x-rar-compressed';
        }

        return null;
    }

    protected function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'application/zip' => 'zip',
            'application/x-rar-compressed', 'application/vnd.rar' => 'rar',
            default => 'pdf',
        };
    }

    protected function binaryLooksPdf(string $binary): bool
    {
        $signature = substr($binary, 0, 8);

        if ($signature === false) {
            return false;
        }

        return str_contains($signature, '%PDF');
    }

    protected function binaryLooksZip(string $binary): bool
    {
        $signature = substr($binary, 0, 4);

        if ($signature === false) {
            return false;
        }

        return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
    }

    protected function binaryLooksRar(string $binary): bool
    {
        $signature = substr($binary, 0, 7);

        if ($signature === false) {
            return false;
        }

        return $signature === "Rar!\x1A\x07\x00" || $signature === "Rar!\x1A\x07\x01";
    }
}
