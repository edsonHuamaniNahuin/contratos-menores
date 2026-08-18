<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La vigilancia SOLO guarda procesos PENDIENTES (sin estado terminal).
     * Los procesos ya adjudicados/desiertos/nulos/cancelados salen de la
     * tabla: el objetivo de la vigilancia es alertar cuando se ADJUDIQUE;
     * si termina en otro estado final, también se retira.
     *
     * Este backfill limpia los registros existentes cuyo proceso ya está
     * en estado terminal (incluye los que se marcaron "resueltos").
     */
    public function up(): void
    {
        DB::statement("
            DELETE v FROM vigilancia_adjudicaciones v
            JOIN contratos_mayores c ON c.ocid = v.ocid
            WHERE c.estado IN (
                'ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO',
                'DESIERTO', 'CANCELADO', 'NULO', 'SUSPENDIDO', 'ARCHIVADO'
            )
        ");
    }

    public function down(): void
    {
        // No reversible: los registros eliminados se re-registran con el
        // sync del job (solo los pendientes) en la siguiente corrida.
    }
};
