<?php

namespace App\Console\Commands;

use App\Services\EntidadesMayoresService;
use Illuminate\Console\Command;

class ActualizarEntidadesMayores extends Command
{
    protected $signature = 'contratos-mayores:actualizar-entidades';
    protected $description = 'Refresca la tabla entidades_mayores desde contratos_mayores y limpia cache';

    public function handle(EntidadesMayoresService $service): int
    {
        $this->info('Refrescando entidades de contratos mayores...');
        
        $resultado = $service->refrescar();
        
        $this->info("Insertadas/actualizadas: {$resultado['insertadas']}");
        $this->info("Omitidas: {$resultado['omitidas']}");
        $this->info('Cache invalidado — próxima consulta recargará desde BD.');

        return self::SUCCESS;
    }
}
