<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill: procesos vigilados cuyo estado YA es final en
     * contratos_mayores (buena pro o desierto/cancelado/nulo) pero que
     * nunca fueron marcados como resueltos — quedaban "En vigilancia"
     * para siempre porque el scan excluía los estados finales.
     *
     * Se marcan como RESUELTOS (notificado_en + estado_notificado) SIN
     * enviar alertas: son adjudicaciones/deseirtos previos al arranque
     * de la vigilancia o detectados por otros jobs. Las transiciones
     * futuras sí envían alerta en el scan.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE vigilancia_adjudicaciones v
            JOIN contratos_mayores c ON c.ocid = v.ocid
            SET v.notificado_en = NOW(),
                v.estado_notificado = c.estado,
                v.updated_at = NOW()
            WHERE v.notificado_en IS NULL
              AND c.estado IN (
                  'ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO',
                  'DESIERTO', 'CANCELADO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO'
              )
        ");
    }

    public function down(): void
    {
        // No reversible (marca resueltos). No hay schema que revertir.
    }
};
