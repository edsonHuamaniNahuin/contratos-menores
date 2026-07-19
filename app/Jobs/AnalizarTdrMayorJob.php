<?php

namespace App\Jobs;

use App\Models\TdrAnalisisMayor;
use App\Services\MayoresTdrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalizarTdrMayorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public readonly string $ocid,
        public readonly string $pdfUrl,
        public readonly array $ctx = [],
        public readonly int $userId = 0,
        public readonly ?string $localPath = null,
    ) {}

    public function handle(MayoresTdrService $service): void
    {
        Log::info('AnalizarTdrMayorJob: iniciando', [
            'ocid' => $this->ocid,
            'local' => $this->localPath ? 'si' : 'no',
        ]);

        $resultado = $service->analizar(
            ocid: $this->ocid,
            pdfUrl: $this->pdfUrl,
            ctx: $this->ctx,
            userId: $this->userId,
            localPath: $this->localPath,
        );

        if ($resultado['success']) {
            Log::info('AnalizarTdrMayorJob: completado', ['ocid' => $this->ocid]);
        } else {
            Log::warning('AnalizarTdrMayorJob: falló', [
                'ocid' => $this->ocid,
                'error' => $resultado['error'] ?? 'unknown',
            ]);
        }
    }

    public function failed(\Exception $e): void
    {
        Log::error('AnalizarTdrMayorJob: excepción', ['ocid' => $this->ocid, 'error' => $e->getMessage()]);
    }
}
