<?php

namespace App\Jobs;

use App\Mail\ReporteConvocatoriasMail;
use App\Models\DemoLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía el reporte semanal de convocatorias al lead (recompensa inmediata).
 * Se dispara al crear un lead (web/plantillas) y desde leads:reporte.
 */
class EnviarReporteConvocatoriasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $leadId) {}

    public function handle(): void
    {
        $lead = DemoLead::find($this->leadId);
        if (! $lead || ! $lead->email) {
            return;
        }

        $desde = now()->subDays(7)->startOfDay();
        $hasta = now();

        $filas = DB::table('contratos')
            ->select('descripcion', 'entidad', 'nombre_departamento', 'fin_cotizacion')
            ->whereNotNull('fecha_publicacion')
            ->where('fecha_publicacion', '>=', $desde)
            ->orderByRaw("CASE
                WHEN descripcion LIKE '%OBRA%' OR descripcion LIKE '%CARRETERA%' OR descripcion LIKE '%PAVIMENT%' OR descripcion LIKE '%PISTA%' OR descripcion LIKE '%VEREDA%' OR descripcion LIKE '%RIEGO%' OR descripcion LIKE '%SANEAMIENTO%' OR descripcion LIKE '%AGUA%' OR descripcion LIKE '%ALCANTARILLADO%' OR descripcion LIKE '%ELECTRI%' OR descripcion LIKE '%ALUMBRADO%' THEN 0
                ELSE 1 END")
            ->orderByDesc('fecha_publicacion')
            ->limit(10)
            ->get();

        $items = [];
        foreach ($filas as $f) {
            $desc = trim(preg_replace('/\s+/', ' ', (string) $f->descripcion));
            if ($desc === '') {
                continue;
            }
            $items[] = [
                'rubro' => $this->detectarRubro($desc),
                'descripcion' => mb_strimwidth($desc, 0, 130, '…'),
                'entidad' => mb_strimwidth((string) $f->entidad, 0, 90),
                'departamento' => (string) ($f->nombre_departamento ?? ''),
                'cierre' => $this->fechaCorta((string) ($f->fin_cotizacion ?? '')),
            ];
        }

        try {
            Mail::to($lead->email)->send(new ReporteConvocatoriasMail(
                nombre: $lead->nombre,
                convocatorias: $items,
                fechaInicio: $desde->format('d/m'),
                fechaFin: $hasta->format('d/m'),
            ));
            Log::info('ReporteConvocatorias: enviado', ['lead_id' => $lead->id, 'items' => count($items)]);
        } catch (\Throwable $e) {
            Log::warning('ReporteConvocatorias: fallo', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
        }
    }

    private function detectarRubro(string $t): string
    {
        $u = mb_strtoupper($t);
        $mapa = [
            'Obras' => ['OBRA', 'CARRETERA', 'PAVIMENT', 'PISTA', 'VEREDA', 'RIEGO', 'VIAL', 'PUENTE', 'MOVIMIENTO DE TIERRA'],
            'Saneamiento' => ['SANEAMIENTO', 'AGUA', 'ALCANTARILLADO', 'DESAGUE', 'PTAR', 'RESIDUAL'],
            'Eléctrico' => ['ELECTRI', 'ALUMBRADO', 'TENSION', 'TRANSFORMADOR'],
            'Mantenimiento' => ['MANTENIMIENTO', 'REPARACION'],
            'Salud' => ['SALUD', 'MEDIC', 'HOSPITAL', 'LABORATORIO'],
            'Tecnología' => ['SOFTWARE', 'SISTEMA', 'INFORMAT', 'CABLEADO'],
            'Limpieza' => ['LIMPIEZA', 'ASEO'],
        ];
        foreach ($mapa as $label => $claves) {
            foreach ($claves as $c) {
                if (str_contains($u, $c)) {
                    return $label;
                }
            }
        }

        return 'Servicios';
    }

    private function fechaCorta(string $f): string
    {
        $f = trim($f);
        if ($f === '') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($f)->format('d/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }
}
