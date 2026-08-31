<?php

namespace App\Jobs;

use App\Services\SeaceProcedimientosScraperService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Descarga el Excel de Procedimientos de Selección del SEACE (2x/día:
 * 12:00 y 21:00) e importa a contratos_mayores los que la API OCDS del
 * OECE aún no ha publicado (gap de latencia de hasta semanas).
 */
class ScrapearProcedimientosSeaceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;

    public function __construct(
        public ?string $desde = null,
        public ?string $hasta = null,
    ) {
    }

    public function handle(SeaceProcedimientosScraperService $service): void
    {
        $desde = $this->desde ? Carbon::parse($this->desde) : now()->startOfDay();
        $hasta = $this->hasta ? Carbon::parse($this->hasta) : now()->endOfDay();

        $resultado = $service->sincronizar($desde, $hasta);

        Log::info('ScrapearProcedimientosSeaceJob: ' . $resultado['message'], [
            'nuevos' => $resultado['nuevos'],
            'actualizados' => $resultado['actualizados'],
            'count' => $resultado['count'],
        ]);

        if (!$resultado['success']) {
            throw new Exception($resultado['message']);
        }
    }
}
