<?php

namespace App\Jobs;

use App\Models\ContratoArchivo;
use App\Models\TdrAnalisis;
use App\Services\TdrAnalysisService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analiza un archivo TDR de forma asíncrona (para PDFs pesados / escaneados).
 *
 * El resultado se persiste en tdr_analisis con estado 'exitoso' o 'fallido'.
 * El componente Livewire hace poll cada 3s consultando ese estado.
 */
class AnalizarTdrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Minutos máximos de timeout para el job (OCR de PDF escaneado grande). */
    public int $timeout = 600;

    /** No reintentar: si Python falla, el error ya es descriptivo. */
    public int $tries = 1;

    public function __construct(
        public readonly ContratoArchivo $archivo,
        public readonly ?array $contratoData = null,
        public readonly int $userId = 0,
    ) {}

    public function handle(TdrAnalysisService $tdrService): void
    {
        Log::info('AnalizarTdrJob: iniciando', [
            'contrato_archivo_id' => $this->archivo->id,
            'tamano_bytes' => $this->archivo->tamano_bytes,
            'user_id' => $this->userId,
        ]);

        set_time_limit(0);

        $resultado = $tdrService->withOrigin('job')->withUserId($this->userId)->analizarArchivoLocal(
            archivoPersistido: $this->archivo,
            contratoData: $this->contratoData,
            target: 'buscador',
        );

        if (!($resultado['success'] ?? false)) {
            Log::warning('AnalizarTdrJob: análisis falló', [
                'contrato_archivo_id' => $this->archivo->id,
                'error' => $resultado['error'] ?? 'desconocido',
            ]);
        } else {
            Log::info('AnalizarTdrJob: completado', [
                'contrato_archivo_id' => $this->archivo->id,
            ]);
        }
    }

    public function failed(Exception $e): void
    {
        Log::error('AnalizarTdrJob: excepción no manejada', [
            'contrato_archivo_id' => $this->archivo->id,
            'error' => $e->getMessage(),
        ]);

        // Persistir el fallo para que checkAnalisisJob pueda detectarlo.
        TdrAnalisis::create([
            'contrato_archivo_id' => $this->archivo->id,
            'tipo_analisis' => TdrAnalisis::TIPO_GENERAL,
            'estado' => TdrAnalisis::ESTADO_FALLIDO,
            'error' => TdrAnalysisService::humanizeError($e->getMessage()),
            'analizado_en' => now(),
        ]);
    }
}
