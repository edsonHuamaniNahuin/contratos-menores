<?php

namespace App\Jobs;

use App\Models\TdrAnalisisMayor;
use App\Services\AnalizadorTDRService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Análisis de direccionamiento (auditoría forense) de un Contrato Mayor.
 *
 * Se ejecuta en segundo plano (queue) para que el navegador no quede
 * bloqueado más de 100s (timeout de Cloudflare) mientras la IA procesa
 * documentos extensos.
 */
class DireccionarTdrMayorJob implements ShouldQueue
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

    public function handle(): void
    {
        Log::info('DireccionarTdrMayorJob: iniciando', [
            'ocid' => $this->ocid,
            'local' => $this->localPath ? 'si' : 'no',
        ]);

        $analizador = new AnalizadorTDRService();
        $resultado = $analizador->analyzeDireccionamiento((string) $this->localPath, 'mayores');

        if (!$resultado['success']) {
            TdrAnalisisMayor::updateOrCreate(
                ['ocid' => $this->ocid, 'tipo' => TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO],
                [
                    'url_documento' => $this->pdfUrl,
                    'estado' => TdrAnalisisMayor::ESTADO_FALLIDO,
                    'error' => $resultado['error'] ?? 'Error desconocido',
                    'contexto_contrato' => $this->ctx,
                    'analizado_en' => now(),
                    'requested_by_user_id' => $this->userId,
                    'origin' => 'web',
                ]
            );

            Log::warning('DireccionarTdrMayorJob: falló', [
                'ocid' => $this->ocid,
                'error' => $resultado['error'] ?? 'unknown',
            ]);

            return;
        }

        TdrAnalisisMayor::updateOrCreate(
            ['ocid' => $this->ocid, 'tipo' => TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO],
            [
                'url_documento' => $this->pdfUrl,
                'estado' => TdrAnalisisMayor::ESTADO_EXITOSO,
                'contexto_contrato' => $this->ctx,
                'payload' => $resultado,
                'analizado_en' => now(),
                'requested_by_user_id' => $this->userId,
                'origin' => 'web',
            ]
        );

        Log::info('DireccionarTdrMayorJob: completado', [
            'ocid' => $this->ocid,
            'score' => $resultado['data']['score_probabilidad_direccionamiento']
                ?? $resultado['data']['score_riesgo_corrupcion']
                ?? null,
        ]);
    }

    public function failed(\Exception $e): void
    {
        Log::error('DireccionarTdrMayorJob: excepción', [
            'ocid' => $this->ocid,
            'error' => $e->getMessage(),
        ]);

        TdrAnalisisMayor::updateOrCreate(
            ['ocid' => $this->ocid, 'tipo' => TdrAnalisisMayor::TIPO_DIRECCIONAMIENTO],
            [
                'url_documento' => $this->pdfUrl,
                'estado' => TdrAnalisisMayor::ESTADO_FALLIDO,
                'error' => $e->getMessage(),
                'contexto_contrato' => $this->ctx,
                'analizado_en' => now(),
                'requested_by_user_id' => $this->userId,
                'origin' => 'web',
            ]
        );
    }
}
