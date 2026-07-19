<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Create a compressed database backup (max 7 retained)';

    public function handle(DatabaseBackupService $service): int
    {
        $this->info('Iniciando backup de base de datos...');
        $this->info('Directorio: ' . $service->getDirectory());

        $result = $service->backup();

        if ($result['success']) {
            if ($result['skipped'] ?? false) {
                $this->comment($result['message']);
            } else {
                $this->info($result['message']);
            }
            return self::SUCCESS;
        }

        $this->error($result['message']);
        return self::FAILURE;
    }
}
