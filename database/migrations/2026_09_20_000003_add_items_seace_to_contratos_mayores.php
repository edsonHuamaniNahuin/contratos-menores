<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ítems del proceso (resumen compacto, máx. 50 filas de texto acotado).
     *
     * Se capturan durante el scrape diario (el scraper ya navega la ficha),
     * con tope de tamaño para no inflar la tabla. El detalle completo se
     * consulta en la Ficha de Selección del SEACE (deep-link).
     */
    public function up(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->json('items_seace')->nullable()->after('ficha_seace_id');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->dropColumn('items_seace');
        });
    }
};
