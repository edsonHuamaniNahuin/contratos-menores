<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Umbral personalizado por usuario para las alertas de adjudicación.
     * Cada usuario define desde qué monto quiere ser alertado (default 1M).
     */
    public function up(): void
    {
        Schema::table('subscriber_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('alerta_adjudicaciones_umbral')
                ->default(1000000)
                ->after('alerta_adjudicaciones');
        });
    }

    public function down(): void
    {
        Schema::table('subscriber_profiles', function (Blueprint $table) {
            $table->dropColumn('alerta_adjudicaciones_umbral');
        });
    }
};
