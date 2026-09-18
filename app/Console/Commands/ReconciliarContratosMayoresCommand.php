<?php

namespace App\Console\Commands;

use App\Models\ContratoMayor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fusiona registros sintéticos del scraper (ocid "ocds-scraped-*") con su
 * release OCDS real cuando la nomenclatura difiere en puntuación
 * (ej. scraper "LP-ABR-1-2026-MDH/CS.-1" vs OCDS "LP-ABR-1-2026-MDH/CS-1").
 *
 * Migra las referencias (vigilancia, seguimientos, notificaciones) y copia
 * los datos completos del release (incluido url_documento) al registro que
 * conserva su ID. Así el proceso deja de aparecer sin opciones en el buscador.
 *
 * Uso: php artisan contratos-mayores:reconciliar-sinteticos [--dry-run]
 */
class ReconciliarContratosMayoresCommand extends Command
{
    protected $signature = 'contratos-mayores:reconciliar-sinteticos
                            {--dry-run : Mostrar los pares a fusionar sin modificar la BD}';

    protected $description = 'Fusiona procesos sintéticos del scraper con su release OCDS real (nomenclatura normalizada)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $ocdsPorClave = [];
        foreach (ContratoMayor::where('ocid', 'not like', 'ocds-scraped-%')
            ->whereNotNull('url_documento')
            ->where('url_documento', '!=', '')
            ->get(['ocid', 'nomenclatura']) as $ocds) {
            $clave = $this->normalizarNomenclatura($ocds->nomenclatura);
            if ($clave !== '') {
                $ocdsPorClave[$clave] = $ocds->ocid;
            }
        }

        $this->info('Registros OCDS con documento indexados: ' . count($ocdsPorClave));

        $sinteticos = ContratoMayor::where('ocid', 'like', 'ocds-scraped-%')
            ->get(['ocid', 'nomenclatura']);

        $fusionados = 0;
        $sinPar = 0;
        $errores = 0;

        foreach ($sinteticos as $sintetico) {
            $clave = $this->normalizarNomenclatura($sintetico->nomenclatura);
            $ocidReal = $clave !== '' ? ($ocdsPorClave[$clave] ?? null) : null;

            if (!$ocidReal) {
                $sinPar++;
                continue;
            }

            if ($dryRun) {
                $this->line("→ [{$sintetico->nomenclatura}] {$sintetico->ocid} ⇒ {$ocidReal}");
                $fusionados++;
                continue;
            }

            try {
                $this->fusionar($sintetico->ocid, $ocidReal);
                $fusionados++;
            } catch (\Throwable $e) {
                $errores++;
                $this->error("Error fusionando {$sintetico->ocid} ⇒ {$ocidReal}: {$e->getMessage()}");
                Log::error('ReconciliarContratosMayores: error', [
                    'sintetico' => $sintetico->ocid,
                    'real' => $ocidReal,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $resumen = "Fusionados: {$fusionados} | Sin par OCDS: {$sinPar} | Errores: {$errores}";
        $this->info(($dryRun ? '[dry-run] ' : '') . $resumen);
        Log::info('ReconciliarContratosMayores: completado', [
            'fusionados' => $fusionados,
            'sin_par' => $sinPar,
            'errores' => $errores,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    /**
     * Migrar referencias y copiar los datos del release real al registro
     * sintético (que conserva su ID y las referencias migradas).
     */
    protected function fusionar(string $ocidSintetico, string $ocidReal): void
    {
        $real = ContratoMayor::where('ocid', $ocidReal)->first();

        if (!$real) {
            throw new \RuntimeException('Registro OCDS no encontrado');
        }

        $datos = $real->only([
            'entidad_nombre', 'entidad_ruc', 'entidad_direccion', 'nomenclatura',
            'descripcion_objeto', 'objeto_contratacion', 'valor_referencial',
            'moneda', 'fecha_publicacion', 'fecha_inicio', 'fecha_fin',
            'metodo_contratacion', 'estado', 'codigo_snip', 'proveedores',
            'url_documento', 'cuantia', 'datos_raw',
            'departamento_id', 'provincia_id', 'distrito_id',
        ]);

        DB::transaction(function () use ($ocidSintetico, $ocidReal, $datos) {
            // Vigilancia (ocid único): mover solo si el real no está vigilado
            $vigilancia = DB::table('vigilancia_adjudicaciones')->where('ocid', $ocidSintetico)->first();
            if ($vigilancia) {
                $yaVigilado = DB::table('vigilancia_adjudicaciones')->where('ocid', $ocidReal)->exists();
                if ($yaVigilado) {
                    DB::table('vigilancia_adjudicaciones')->where('id', $vigilancia->id)->delete();
                } else {
                    DB::table('vigilancia_adjudicaciones')->where('id', $vigilancia->id)->update(['ocid' => $ocidReal]);
                }
            }

            // Seguimientos (user_id + ocid únicos): mover los que no choquen
            foreach (DB::table('contrato_seguimientos_mayores')->where('ocid', $ocidSintetico)->get() as $seguimiento) {
                $yaSigue = DB::table('contrato_seguimientos_mayores')
                    ->where('user_id', $seguimiento->user_id)
                    ->where('ocid', $ocidReal)
                    ->exists();
                if ($yaSigue) {
                    DB::table('contrato_seguimientos_mayores')->where('id', $seguimiento->id)->delete();
                } else {
                    DB::table('contrato_seguimientos_mayores')->where('id', $seguimiento->id)->update(['ocid' => $ocidReal]);
                }
            }

            // Notificaciones: conservar una sola fila por proceso
            $realYaNotificado = DB::table('notified_processes')->where('seace_proceso_id', $ocidReal)->exists();
            if ($realYaNotificado) {
                DB::table('notified_processes')->where('seace_proceso_id', $ocidSintetico)->delete();
            } else {
                DB::table('notified_processes')->where('seace_proceso_id', $ocidSintetico)
                    ->update(['seace_proceso_id' => $ocidReal]);
            }

            // Eliminar el duplicado real y quedarse con el registro sintético
            ContratoMayor::where('ocid', $ocidReal)->delete();
            ContratoMayor::where('ocid', $ocidSintetico)->update($datos + ['ocid' => $ocidReal]);
        });

        Log::info('ReconciliarContratosMayores: fusión exitosa', [
            'sintetico' => $ocidSintetico,
            'real' => $ocidReal,
        ]);
    }

    /**
     * Normalizar nomenclatura para comparar scraper vs OCDS:
     * minúsculas y solo caracteres alfanuméricos.
     */
    protected function normalizarNomenclatura(?string $nomenclatura): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($nomenclatura ?? '')) ?? '';
    }
}
