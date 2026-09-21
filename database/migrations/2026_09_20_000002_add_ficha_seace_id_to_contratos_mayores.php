<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referencia a la Ficha de Selección del SEACE (solo el `id`).
     *
     * No se almacena el contenido de la ficha: el buscador enlaza a SEACE
     * on-demand (cronograma, ítems, contratos, historial) para no duplicar
     * información que ya es pública y puede cambiar.
     */
    public function up(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->string('ficha_seace_id', 64)->nullable()->after('url_documento');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->dropColumn('ficha_seace_id');
        });
    }
};
