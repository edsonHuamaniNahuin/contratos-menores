<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La vigilancia solo guarda el IDENTIFICADOR (ocid) del proceso.
     * Los datos del proceso (entidad, monto, estado, etc.) viven en
     * contratos_mayores (job de importación) y se consultan por relación
     * vigilancia_adjudicaciones.ocid → contratos_mayores.ocid.
     *
     * Se eliminan las columnas duplicadas que almacenaban una copia de
     * la información del proceso.
     */
    public function up(): void
    {
        Schema::table('vigilancia_adjudicaciones', function (Blueprint $table) {
            $table->dropColumn([
                'nomenclatura',
                'entidad_nombre',
                'valor_referencial',
                'estado',
                'fecha_publicacion',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('vigilancia_adjudicaciones', function (Blueprint $table) {
            $table->string('nomenclatura')->nullable();
            $table->string('entidad_nombre')->nullable();
            $table->decimal('valor_referencial', 15, 2)->nullable();
            $table->string('estado')->nullable();
            $table->timestamp('fecha_publicacion')->nullable();
        });
    }
};
