<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag por usuario: "alertar cuando procesos se hayan adjudicado".
     * Cuando está activo, el job de vigilancia de adjudicaciones notifica
     * al usuario por sus canales activos (Telegram/WhatsApp/Email) cada
     * vez que un proceso vigilado (>= umbral) pase a buena pro.
     */
    public function up(): void
    {
        Schema::table('subscriber_profiles', function (Blueprint $table) {
            $table->boolean('alerta_adjudicaciones')->default(false)->after('company_copy');
        });
    }

    public function down(): void
    {
        Schema::table('subscriber_profiles', function (Blueprint $table) {
            $table->dropColumn('alerta_adjudicaciones');
        });
    }
};
