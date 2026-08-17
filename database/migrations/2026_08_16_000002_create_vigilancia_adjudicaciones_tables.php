<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vigilancia automática de adjudicaciones para Contratos Mayores.
     *
     * vigilancia_adjudicaciones: procesos > umbral (default S/ 1M) que se
     *   monitorean 1x1 contra la API OCDS cada 5 horas. Cuando el proceso
     *   pasa a ADJUDICADO/CONSENTIDO/OTORGADO/CONTRATADO (buena pro) se
     *   notifica a los destinatarios configurados.
     *
     * vigilancia_adjudicacion_destinatarios: a quién alertar (email y/o
     *   WhatsApp), 1 o varias personas.
     */
    public function up(): void
    {
        Schema::create('vigilancia_adjudicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('ocid')->unique();
            $table->string('nomenclatura')->nullable();
            $table->string('entidad_nombre')->nullable();
            $table->decimal('valor_referencial', 15, 2)->nullable();
            $table->string('estado')->nullable();
            $table->timestamp('fecha_publicacion')->nullable();
            $table->string('estado_notificado')->nullable();
            $table->timestamp('notificado_en')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('notificado_en');
        });

        Schema::create('vigilancia_adjudicacion_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Umbral por defecto: S/ 1,000,000 (configurable en SystemSetting)
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'vigilancia_monto_min'],
            ['value' => '1000000', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('vigilancia_adjudicacion_destinatarios');
        Schema::dropIfExists('vigilancia_adjudicaciones');

        DB::table('system_settings')->where('key', 'vigilancia_monto_min')->delete();
    }
};
