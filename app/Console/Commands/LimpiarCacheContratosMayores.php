<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class LimpiarCacheContratosMayores extends Command
{
    protected $signature = 'contratos-mayores:limpiar-cache
                            {--dias=2 : Eliminar caches con esta antigüedad o mayor}
                            {--dry-run : Solo mostrar lo que se eliminaría}';

    protected $description = 'Elimina caches de OCIDs de contratos mayores con 2+ días de antigüedad';

    public function handle(): int
    {
        $diasAntiguedad = (int) $this->option('dias');
        $dryRun = (bool) $this->option('dry-run');
        $prefix = 'contratos_mayores:ocids:';

        $hoy = Carbon::now()->startOfDay();
        $limite = $hoy->copy()->subDays($diasAntiguedad);
        $inicio = $limite->copy()->subDays(30);

        $this->info("Rango: {$inicio->toDateString()} → {$limite->toDateString()} ({$diasAntiguedad}+ días atrás)");

        $eliminados = 0;
        $revisados = 0;

        $fecha = $limite->copy();
        while ($fecha->greaterThanOrEqualTo($inicio)) {
            $key = $prefix . $fecha->toDateString();
            $revisados++;

            if (Cache::has($key)) {
                if ($dryRun) {
                    $this->line("  [DRY-RUN] {$key}");
                } else {
                    Cache::forget($key);
                    $this->line("  Eliminado: {$key}");
                }
                $eliminados++;
            }

            $fecha->subDay();
        }

        $verb = $dryRun ? 'Se eliminarían' : 'Eliminados';
        $this->info("{$verb} {$eliminados} de {$revisados} keys.");

        return self::SUCCESS;
    }
}
