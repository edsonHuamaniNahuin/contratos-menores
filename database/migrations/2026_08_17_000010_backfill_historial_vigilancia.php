<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La bandeja de vigilancia conserva el HISTORIAL: todos los procesos
     * que iniciaron seguimiento siguen visibles aunque ya estén resueltos
     * (buena pro, desierto, nulo, etc.).
     *
     * Este backfill reconstruye el historial de los procesos >= umbral
     * que ya están en estado terminal y que la limpieza anterior (000009)
     * eliminó de la tabla. Se registran como RESUELTOS (sin alerta).
     */
    public function up(): void
    {
        // Umbral configurado en system_settings (default S/ 1M) — no hardcodeado.
        $umbral = (float) (DB::table('system_settings')
            ->where('key', 'vigilancia_monto_min')
            ->value('value') ?: 1000000);

        DB::statement("
            INSERT INTO vigilancia_adjudicaciones (ocid, estado_notificado, notificado_en, created_at, updated_at)
            SELECT c.ocid, c.estado, NOW(), NOW(), NOW()
            FROM contratos_mayores c
            LEFT JOIN vigilancia_adjudicaciones v ON v.ocid = c.ocid
            WHERE c.valor_referencial >= {$umbral}
              AND c.estado IN (
                  'ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO',
                  'DESIERTO', 'CANCELADO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO'
              )
              AND v.ocid IS NULL
        ");
    }

    public function down(): void
    {
        // No reversible: solo reconstruye historial.
    }
};
