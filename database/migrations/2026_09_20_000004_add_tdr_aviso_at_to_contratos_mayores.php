<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca de "ya se avisó que el TDR está disponible".
     *
     * Problema que resuelve: si la primera alerta salió cuando el proceso aún
     * no tenía documento, el cliente nunca se enteraba cuando el TDR aparecía
     * después. Con esta marca, un job detecta procesos ya alertados que ahora
     * SÍ tienen documento y envía el aviso de seguimiento (una sola vez).
     */
    public function up(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->timestamp('tdr_aviso_at')->nullable()->after('items_seace');
            $table->index(['tdr_aviso_at', 'fecha_publicacion'], 'idx_cm_tdr_aviso');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->dropIndex('idx_cm_tdr_aviso');
            $table->dropColumn('tdr_aviso_at');
        });
    }
};
