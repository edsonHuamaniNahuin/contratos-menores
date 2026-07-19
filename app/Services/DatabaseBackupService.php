<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DatabaseBackupService
{
    protected string $directory;
    protected int $maxBackups;
    protected string $dbConnection;
    protected string $dbHost;
    protected string $dbPort;
    protected string $dbName;
    protected string $dbUser;
    protected string $dbPassword;

    public function __construct()
    {
        $this->directory = config('backup.directory', storage_path('backups'));
        $this->maxBackups = (int) config('backup.max_backups', 7);
        $this->dbConnection = config('database.default', 'mysql');
        $this->dbHost = config("database.connections.{$this->dbConnection}.host", '127.0.0.1');
        $this->dbPort = config("database.connections.{$this->dbConnection}.port", '3306');
        $this->dbName = config("database.connections.{$this->dbConnection}.database", '');
        $this->dbUser = config("database.connections.{$this->dbConnection}.username", 'root');
        $this->dbPassword = config("database.connections.{$this->dbConnection}.password", '');
    }

    public function backup(): array
    {
        $this->ensureDirectory();

        $todayFilename = $this->getFilename(Carbon::today());
        $todayPath = $this->directory . DIRECTORY_SEPARATOR . $todayFilename;

        if (File::exists($todayPath)) {
            Log::info('Backup DB: ya existe un backup de hoy, omitiendo.', ['file' => $todayFilename]);

            return [
                'success' => true,
                'message' => 'Ya existe un backup de hoy.',
                'file' => $todayFilename,
                'skipped' => true,
            ];
        }

        $command = $this->buildDumpCommand($todayPath);
        $timeout = (int) config('backup.timeout', 300);

        $result = Process::timeout($timeout)
            ->run($command);

        if (!$result->successful()) {
            Log::error('Backup DB: falló mysqldump.', [
                'command' => $this->maskPassword($command),
                'error' => $result->errorOutput(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al ejecutar mysqldump: ' . $result->errorOutput(),
            ];
        }

        $size = File::size($todayPath);
        $sizeReadable = $this->formatBytes($size);

        Log::info('Backup DB: creado exitosamente.', [
            'file' => $todayFilename,
            'size' => $sizeReadable,
        ]);

        $this->rotateOldBackups();

        return [
            'success' => true,
            'message' => "Backup creado: {$todayFilename} ({$sizeReadable})",
            'file' => $todayFilename,
            'size' => $sizeReadable,
            'skipped' => false,
        ];
    }

    public function restore(string $filename): array
    {
        $filepath = $this->directory . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($filepath)) {
            return [
                'success' => false,
                'message' => "No se encuentra el archivo: {$filename}",
            ];
        }

        $isGzip = str_ends_with($filename, '.gz');

        if ($isGzip) {
            $command = $this->buildRestoreGzipCommand($filepath);
        } else {
            $command = $this->buildRestoreCommand($filepath);
        }

        $timeout = (int) config('backup.timeout', 300);

        $result = Process::timeout($timeout)
            ->run($command);

        if (!$result->successful()) {
            Log::error('Backup DB: falló restauración.', [
                'file' => $filename,
                'error' => $result->errorOutput(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al restaurar: ' . $result->errorOutput(),
            ];
        }

        Log::info('Backup DB: restauración exitosa.', ['file' => $filename]);

        return [
            'success' => true,
            'message' => "Base de datos restaurada desde: {$filename}",
        ];
    }

    public function listBackups(): array
    {
        $this->ensureDirectory();

        $files = File::glob($this->directory . DIRECTORY_SEPARATOR . '*.sql*');

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $size = File::size($file);

            $backups[] = [
                'filename' => $filename,
                'size' => $size,
                'size_readable' => $this->formatBytes($size),
                'date' => Carbon::createFromTimestamp(File::lastModified($file))->toDateString(),
                'modified' => Carbon::createFromTimestamp(File::lastModified($file))->toDateTimeString(),
            ];
        }

        usort($backups, fn ($a, $b) => $b['filename'] <=> $a['filename']);

        return $backups;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    protected function rotateOldBackups(): void
    {
        $backups = $this->listBackups();

        if (count($backups) <= $this->maxBackups) {
            return;
        }

        $toDelete = array_slice($backups, $this->maxBackups);

        foreach ($toDelete as $backup) {
            $filepath = $this->directory . DIRECTORY_SEPARATOR . $backup['filename'];
            File::delete($filepath);

            Log::info('Backup DB: eliminado backup antiguo.', [
                'file' => $backup['filename'],
                'rotated' => true,
            ]);
        }
    }

    protected function buildDumpCommand(string $outputPath): string
    {
        $parts = [];

        $mysqldump = config('backup.mysqldump_path', 'mysqldump');
        $parts[] = escapeshellcmd($mysqldump);

        $parts[] = '--host=' . escapeshellarg($this->dbHost);
        $parts[] = '--port=' . escapeshellarg($this->dbPort);
        $parts[] = '--user=' . escapeshellarg($this->dbUser);

        if (!empty($this->dbPassword)) {
            $parts[] = '--password=' . escapeshellarg($this->dbPassword);
        }

        $parts[] = '--single-transaction';
        $parts[] = '--routines';
        $parts[] = '--triggers';
        $parts[] = '--events';
        $parts[] = '--hex-blob';
        $parts[] = '--default-character-set=utf8mb4';

        $parts[] = escapeshellarg($this->dbName);

        if (config('backup.compress', true)) {
            return implode(' ', $parts) . ' | gzip > ' . escapeshellarg($outputPath);
        }

        $parts[] = '--result-file=' . escapeshellarg($outputPath);
        return implode(' ', $parts);
    }

    protected function buildRestoreCommand(string $filepath): string
    {
        $parts = [];

        $mysql = config('backup.mysql_path', 'mysql');
        $parts[] = escapeshellcmd($mysql);

        $parts[] = '--host=' . escapeshellarg($this->dbHost);
        $parts[] = '--port=' . escapeshellarg($this->dbPort);
        $parts[] = '--user=' . escapeshellarg($this->dbUser);

        if (!empty($this->dbPassword)) {
            $parts[] = '--password=' . escapeshellarg($this->dbPassword);
        }

        $parts[] = '--default-character-set=utf8mb4';
        $parts[] = escapeshellarg($this->dbName);

        return implode(' ', $parts) . ' < ' . escapeshellarg($filepath);
    }

    protected function buildRestoreGzipCommand(string $filepath): string
    {
        $parts = [];

        $mysql = config('backup.mysql_path', 'mysql');
        $parts[] = escapeshellcmd($mysql);

        $parts[] = '--host=' . escapeshellarg($this->dbHost);
        $parts[] = '--port=' . escapeshellarg($this->dbPort);
        $parts[] = '--user=' . escapeshellarg($this->dbUser);

        if (!empty($this->dbPassword)) {
            $parts[] = '--password=' . escapeshellarg($this->dbPassword);
        }

        $parts[] = '--default-character-set=utf8mb4';
        $parts[] = escapeshellarg($this->dbName);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'gzip -d -c ' . escapeshellarg($filepath) . ' | ' . implode(' ', $parts);
        }

        return 'gunzip < ' . escapeshellarg($filepath) . ' | ' . implode(' ', $parts);
    }

    protected function getFilename(Carbon $date): string
    {
        $ext = config('backup.compress', true) ? '.sql.gz' : '.sql';
        return $this->dbName . '_' . $date->format('Y-m-d') . $ext;
    }

    protected function ensureDirectory(): void
    {
        if (!File::exists($this->directory)) {
            File::makeDirectory($this->directory, 0755, true);
        }
    }

    protected function maskPassword(string $command): string
    {
        return preg_replace('/--password=\S+/', '--password=***', $command);
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), $precision) . ' ' . $units[$i];
    }
}
