<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice para el ORDER BY fecha_publicacion DESC usado por la bandeja
     * de vigilancia y el buscador de Contratos Mayores. Evita el sort
     * completo de la tabla en cada consulta.
     */
    public function up(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->index('fecha_publicacion', 'idx_cm_fecha_publicacion');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->dropIndex('idx_cm_fecha_publicacion');
        });
    }
};
