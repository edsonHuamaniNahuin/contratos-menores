<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseRestoreCommand extends Command
{
    protected $signature = 'db:restore {file? : Nombre del archivo de backup a restaurar}';
    protected $description = 'Restore database from a backup file';

    public function handle(DatabaseBackupService $service): int
    {
        $file = $this->argument('file');

        if ($file) {
            if (!$this->confirm("Estas seguro de restaurar '{$file}'? Se SOBREESCRIBIRA la base de datos actual.", false)) {
                return self::FAILURE;
            }

            $result = $service->restore($file);

            if ($result['success']) {
                $this->info($result['message']);
                return self::SUCCESS;
            }

            $this->error($result['message']);
            return self::FAILURE;
        }

        $backups = $service->listBackups();

        if (empty($backups)) {
            $this->warn('No hay backups disponibles.');
            return self::FAILURE;
        }

        $this->table(
            ['#', 'Archivo', 'Tamaño', 'Fecha', 'Modificado'],
            collect($backups)->map(fn ($b, $i) => [
                $i + 1,
                $b['filename'],
                $b['size_readable'],
                $b['date'],
                $b['modified'],
            ])->toArray()
        );

        $choice = $this->ask('Ingresa el número del backup a restaurar (o 0 para cancelar)');
        $index = (int) $choice - 1;

        if ($choice === '0' || !isset($backups[$index])) {
            $this->info('Restauración cancelada.');
            return self::SUCCESS;
        }

        $selected = $backups[$index];
        if (!$this->confirm("Restaurar '{$selected['filename']}'? Se SOBREESCRIBIRA la base de datos actual.", false)) {
            return self::FAILURE;
        }

        $result = $service->restore($selected['filename']);

        if ($result['success']) {
            $this->info($result['message']);
            return self::SUCCESS;
        }

        $this->error($result['message']);
        return self::FAILURE;
    }
}
