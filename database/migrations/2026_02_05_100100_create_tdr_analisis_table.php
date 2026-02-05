<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tdr_analisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_archivo_id')->constrained('contrato_archivos')->cascadeOnDelete();
            $table->string('estado', 32)->default('pendiente');
            $table->string('proveedor', 50)->default('gemini');
            $table->string('modelo')->nullable();
            $table->json('contexto_contrato')->nullable();
            $table->json('resumen')->nullable()->comment('Datos normalizados utilizados por el dashboard y Telegram');
            $table->json('payload')->nullable()->comment('Respuesta completa del LLM');
            $table->text('requisitos_calificacion')->nullable();
            $table->text('reglas_ejecucion')->nullable();
            $table->text('penalidades')->nullable();
            $table->string('monto_referencial_text', 128)->nullable();
            $table->unsignedInteger('duracion_ms')->nullable();
            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_respuesta')->nullable();
            $table->decimal('costo_estimado', 12, 4)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('analizado_en')->nullable();
            $table->timestamps();

            $table->unique(['contrato_archivo_id', 'proveedor', 'modelo'], 'tdr_analisis_unq');
            $table->index(['estado', 'analizado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdr_analisis');
    }
};
