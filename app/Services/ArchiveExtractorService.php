<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentoExtraido;
use ZipArchive;
use RuntimeException;

class ArchiveExtractorService
{
    private const CACHE_BASE = 'tdr-extracted';

    private const MAGIC_PDF = "%PDF";
    private const MAGIC_ZIP = "PK\x03\x04";
    private const MAGIC_RAR7Z = "Rar!\x1A\x07";
    private const MAGIC_RAR5  = "Rar!\x1A\x07\x01\x00";
    private const MAGIC_DOC  = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

    /**
     * Procesa un documento TDR desde una URL.
     *
     * @param string $url        URL del documento
     * @param string $contractId Identificador del contrato (int para menores, OCID string para mayores)
     * @param string $tipo       'menores' | 'mayores'
     * @return array  pdfs[] incluye 'documento_id'
     */
    public function process(string $url, string $contractId, string $tipo): array
    {
        if (!in_array($tipo, ['menores', 'mayores'])) {
            return $this->error('Tipo de contrato no válido.');
        }

        $extractDir = storage_path('app/' . self::CACHE_BASE . "/{$tipo}/{$contractId}");

        // ── Cache: ya extraído? ──
        $cachedPdfs = $this->getCachedPdfs($extractDir);
        if ($cachedPdfs !== null) {
            return $this->selectBestPdf($cachedPdfs, $extractDir);
        }

        // ── Descargar ──
        try {
            $response = Http::timeout(60)
                ->withOptions(['stream' => false])
                ->get($url);

            if (!$response->successful()) {
                return $this->error('No se pudo descargar el documento. HTTP ' . $response->status());
            }

            $body = $response->body();
            if (empty($body)) {
                return $this->error('El documento descargado está vacío.');
            }

            $contentType = $response->header('Content-Type') ?? '';
        } catch (\Exception $e) {
            Log::error('ArchiveExtractor: descarga fallida', ['url' => $url, 'error' => $e->getMessage()]);
            return $this->error('No se pudo descargar el documento: ' . $e->getMessage());
        }

        // ── Detectar formato ──
        $format = $this->detectFormat($body, $contentType, $url);

        // Formatos de documento único (no archivo comprimido)
        if (in_array($format, ['pdf', 'docx', 'doc'])) {
            $ext = $format === 'docx' ? 'docx' : ($format === 'doc' ? 'doc' : 'pdf');
            $tempPath = $this->saveTemp($body, $ext);
            return $this->pdfResult($tempPath);
        }

        if ($format === 'zip') {
            $result = $this->extractZip($body, $extractDir);
            // Si parece ZIP pero no tiene PDFs, podría ser DOCX
            if ($result['type'] === 'error' && $this->isDocxContent($body)) {
                $tempPath = $this->saveTemp($body, 'docx');
                return $this->pdfResult($tempPath);
            }
            return $result;
        }

        if ($format === 'rar') {
            return $this->extractRar($body, $extractDir);
        }

        return $this->error('Formato de documento no soportado (se espera PDF o ZIP).');
    }

    /**
     * Obtiene PDFs ya cacheados sin descargar de nuevo.
     * Busca recursivamente en subdirectorios.
     */
    public function getCachedPdfs(string $extractDir): ?array
    {
        if (!is_dir($extractDir)) {
            return null;
        }

        return $this->findPdfsRecursive($extractDir, $extractDir);
    }

    private function findPdfsRecursive(string $dir, string $baseDir): ?array
    {
        $pdfs = [];
        $items = scandir($dir) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $subPdfs = $this->findPdfsRecursive($fullPath, $baseDir);
                if ($subPdfs) {
                    $pdfs = array_merge($pdfs, $subPdfs);
                }
            } elseif (is_file($fullPath) && strtolower(pathinfo($item, PATHINFO_EXTENSION)) === 'pdf') {
                // Usar ruta relativa al baseDir como key para mejor legibilidad
                $relativeKey = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $fullPath);
                $pdfs[$relativeKey] = $fullPath;
            }
        }

        return empty($pdfs) ? null : $pdfs;
    }

    /**
     * Procesa un archivo ya descargado localmente (para Menores).
     * Usa el contenido del archivo para detectar formato y extraer si es necesario.
     *
     * @param string $localPath  Ruta absoluta al archivo ya descargado
     * @param string $contractId Identificador del contrato
     * @param string $tipo       'menores' | 'mayores'
     */
    public function processLocal(string $localPath, string $contractId, string $tipo): array
    {
        if (!is_file($localPath)) {
            return $this->error('El archivo local no existe.');
        }

        $extractDir = storage_path('app/' . self::CACHE_BASE . "/{$tipo}/{$contractId}");

        $cachedPdfs = $this->getCachedPdfs($extractDir);
        if ($cachedPdfs !== null) {
            return $this->selectBestPdf($cachedPdfs, $extractDir);
        }

        $content = file_get_contents($localPath);
        if ($content === false || $content === '') {
            return $this->error('No se pudo leer el archivo local.');
        }

        $format = $this->detectFormat($content, 'application/pdf', $localPath);

        if ($format === 'pdf') {
            return $this->pdfResult($localPath);
        }

        if ($format === 'zip') {
            $result = $this->extractZip($content, $extractDir);
            if ($result['type'] === 'error' && $this->isDocxContent($content)) {
                $tempPath = $this->saveTemp($content, 'docx');
                return $this->pdfResult($tempPath);
            }
            return $result;
        }

        if ($format === 'rar') {
            return $this->extractRar($content, $extractDir);
        }

        return $this->error('Formato no soportado.');
    }

    private function detectFormat(string $content, string $contentType, string $url): string
    {
        // ── Por extensión de URL primero (confiable para distinguir DOCX de ZIP) ──
        $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));

        // ── Por Content-Type ──
        if (stripos($contentType, 'application/pdf') !== false) {
            return 'pdf';
        }
        if (stripos($contentType, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') !== false
            || stripos($contentType, 'application/vnd.openxmlformats-officedocument') !== false) {
            return 'docx';
        }
        if (stripos($contentType, 'application/msword') !== false) {
            return 'doc';
        }
        if (stripos($contentType, 'application/zip') !== false ||
            stripos($contentType, 'application/x-zip-compressed') !== false) {
            return $ext === 'docx' ? 'docx' : ($ext === 'doc' ? 'doc' : 'zip');
        }
        if (stripos($contentType, 'application/x-rar-compressed') !== false ||
            stripos($contentType, 'application/vnd.rar') !== false) {
            return 'rar';
        }

        // ── Por extensión de URL ──
        if ($ext === 'pdf') { return 'pdf'; }
        if ($ext === 'docx') { return 'docx'; }
        if ($ext === 'doc')  { return 'doc'; }
        if (in_array($ext, ['zip', '7z'])) { return 'zip'; }
        if ($ext === 'rar') { return 'rar'; }

        // ── Por magic bytes ──
        if (str_starts_with($content, self::MAGIC_PDF)) {
            return 'pdf';
        }
        if (str_starts_with($content, self::MAGIC_DOC)) {
            return 'doc';
        }
        if (str_starts_with($content, self::MAGIC_ZIP)) {
            // DOCX también empieza con PK — la extensión ya fue descartada arriba
            return 'zip';
        }
        if (str_starts_with($content, 'Rar!')) {
            return 'rar';
        }

        // Fallback: asumir PDF
        return 'pdf';
    }

    private function extractZip(string $content, string $extractDir): array
    {
        $tempZip = $this->saveTemp($content, 'zip');

        $zip = new ZipArchive();
        $result = $zip->open($tempZip);

        if ($result !== true) {
            @unlink($tempZip);
            return $this->error('No se pudo abrir el archivo ZIP.');
        }

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $zip->extractTo($extractDir);
        $zip->close();
        @unlink($tempZip);

        $pdfs = $this->getCachedPdfs($extractDir);

        if (!$pdfs || empty($pdfs)) {
            return $this->error('El archivo ZIP no contiene documentos PDF.');
        }

        return $this->selectBestPdf($pdfs, $extractDir);
    }

    private function extractRar(string $content, string $extractDir): array
    {
        $tempRar = $this->saveTemp($content, 'rar');

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        // Resolver herramienta de extracción RAR
        $tool = $this->resolveRarToolPath();

        if (!$tool) {
            @unlink($tempRar);
            return $this->error(
                'Formato RAR detectado pero no hay extractor disponible. ' .
                'Instala 7-Zip (https://7-zip.org) o WinRAR en el servidor.'
            );
        }

        // Construir comando según herramienta (7z vs unrar tienen flags distintos)
        if (str_contains(basename($tool), '7z')) {
            // 7z: output con -o sin espacio
            $cmd = sprintf(
                '%s x -y %s -o%s 2>&1',
                escapeshellarg($tool),
                escapeshellarg($tempRar),
                escapeshellarg($extractDir)
            );
        } else {
            // unrar: output como último argumento
            $cmd = sprintf(
                '%s x -y %s %s 2>&1',
                escapeshellarg($tool),
                escapeshellarg($tempRar),
                escapeshellarg($extractDir)
            );
        }

        exec($cmd, $output, $exitCode);
        @unlink($tempRar);

        if ($exitCode !== 0) {
            return $this->error('No se pudo extraer el archivo RAR (código ' . $exitCode . ').');
        }

        $pdfs = $this->getCachedPdfs($extractDir);

        if (!$pdfs || empty($pdfs)) {
            return $this->error('El archivo RAR no contiene documentos PDF.');
        }

        return $this->selectBestPdf($pdfs, $extractDir);
    }

    private function resolveRarToolPath(): ?string
    {
        // 1. 7z.exe bundled in bin/ (full version with RAR codecs via 7z.dll)
        $local7z = base_path('bin/7z.exe');
        if (is_file($local7z) && is_file(base_path('bin/7z.dll'))) {
            return realpath($local7z);
        }

        // 2. unrar.exe bundled in bin/
        $localUnrar = base_path('bin/unrar.exe');
        if (is_file($localUnrar)) {
            return realpath($localUnrar);
        }

        // 3. 7z/7za in PATH
        if ($this->commandExists('7z')) {
            return '7z';
        }
        if ($this->commandExists('7za')) {
            return '7za';
        }

        // 4. unrar in PATH
        if ($this->commandExists('unrar')) {
            return 'unrar';
        }

        // 5. Common install paths
        $common = [
            'C:\Program Files\7-Zip\7z.exe',
            'C:\Program Files (x86)\7-Zip\7z.exe',
            'C:\Program Files\WinRAR\UnRAR.exe',
            'C:\Program Files (x86)\WinRAR\UnRAR.exe',
            '/usr/bin/7z',
            '/usr/local/bin/7z',
            '/usr/bin/unrar',
            '/usr/local/bin/unrar',
        ];
        foreach ($common as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function resolveSevenZipPath(): ?string
    {
        // 1. 7z.exe bundled in bin/
        $local = [
            base_path('bin/7z.exe'),
            base_path('bin/7za.exe'),
        ];
        foreach ($local as $path) {
            if (is_file($path)) {
                return realpath($path);
            }
        }

        // 2. Common install paths
        $common = [
            'C:\Program Files\7-Zip\7z.exe',
            'C:\Program Files (x86)\7-Zip\7z.exe',
            '/usr/bin/7z',
            '/usr/local/bin/7z',
        ];
        foreach ($common as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // 3. In PATH
        if ($this->commandExists('7z')) {
            return '7z';
        }
        if ($this->commandExists('7za')) {
            return '7za';
        }

        return null;
    }

    private function commandExists(string $cmd): bool
    {
        $where = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        exec(sprintf('%s %s 2>&1', $where, escapeshellarg($cmd)), $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Detecta si un archivo que parece ZIP es en realidad un DOCX (Office Open XML).
     */
    private function isDocxContent(string $content): bool
    {
        $tempFile = $this->saveTemp($content, 'zip');
        $zip = new ZipArchive();

        if ($zip->open($tempFile) === true) {
            // Office Open XML siempre tiene [Content_Types].xml y word/document.xml
            $isDocx = $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false;
            $zip->close();
        } else {
            $isDocx = false;
        }

        @unlink($tempFile);
        return $isDocx;
    }

    private function saveTemp(string $content, string $extension): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $path = $tempDir . '/' . uniqid('tdr_', true) . '.' . $extension;
        file_put_contents($path, $content);

        return $path;
    }

    private function pdfResult(string $path): array
    {
        return [
            'type' => 'pdf',
            'path' => $path,
        ];
    }

    private function selectBestPdf(array $pdfs, string $extractDir): array
    {
        ksort($pdfs);

        if (count($pdfs) === 1) {
            $single = array_values($pdfs)[0];
            $this->registerDocumento($extractDir, basename($single), $single);
            return $this->pdfResult($single);
        }

        $tdrMatches = [];
        foreach ($pdfs as $filename => $fullPath) {
            if (preg_match('/tdr/i', pathinfo($filename, PATHINFO_FILENAME))) {
                $tdrMatches[$filename] = $fullPath;
            }
        }

        if (count($tdrMatches) === 1) {
            $single = array_values($tdrMatches)[0];
            $this->registerDocumento($extractDir, basename($single), $single);
            return $this->pdfResult($single);
        }

        if (count($tdrMatches) > 1) {
            return $this->archiveResult($tdrMatches, $extractDir);
        }

        return $this->archiveResult($pdfs, $extractDir);
    }

    private function archiveResult(array $pdfs, string $dir): array
    {
        $list = [];
        foreach ($pdfs as $filename => $fullPath) {
            $doc = $this->registerDocumento($dir, $filename, $fullPath);
            $list[] = [
                'filename' => $filename,
                'path' => $fullPath,
                'size' => filesize($fullPath),
                'documento_id' => $doc->id,
            ];
        }

        return [
            'type' => 'archive',
            'pdfs' => $list,
            'dir' => $dir,
        ];
    }

    /**
     * Registra (o recupera) un documento extraído en la BD.
     */
    private function registerDocumento(string $extractDir, string $filename, string $fullPath): DocumentoExtraido
    {
        // Inferir tipo y contrato_ref del directorio: tdr-extracted/{tipo}/{contrato_ref}/
        $parts = explode('/', str_replace('\\', '/', $extractDir));
        $tipo = $parts[count($parts) - 2] ?? 'desconocido';
        $contratoRef = $parts[count($parts) - 1] ?? '0';

        return DocumentoExtraido::firstOrCreate([
            'tipo_contrato' => $tipo,
            'contrato_ref' => $contratoRef,
            'nombre_archivo' => $filename,
        ], [
            'ruta_archivo' => $fullPath,
            'tamano_bytes' => filesize($fullPath),
            'extraido_en' => now(),
        ]);
    }

    private function error(string $message): array
    {
        return [
            'type' => 'error',
            'message' => $message,
        ];
    }
}
