<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdr_analisis_mayores', function (Blueprint $table) {
            $table->id();
            $table->string('ocid')->comment('Identificador OCDS del contrato mayor');
            $table->string('url_documento', 2000)->nullable();
            $table->string('estado')->default('pendiente'); // pendiente | exitoso | fallido
            $table->string('proveedor', 50)->nullable()->comment('gemini, openai, anthropic');
            $table->string('modelo', 100)->nullable();
            $table->json('contexto_contrato')->nullable();
            $table->json('resumen')->nullable();
            $table->json('requisitos_calificacion')->nullable();
            $table->json('reglas_ejecucion')->nullable();
            $table->json('penalidades')->nullable();
            $table->string('monto_referencial_text', 1000)->nullable();
            $table->json('payload')->comment('Respuesta completa del LLM');
            $table->integer('duracion_ms')->nullable();
            $table->integer('tokens_prompt')->nullable();
            $table->integer('tokens_respuesta')->nullable();
            $table->decimal('costo_estimado', 10, 4)->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('origin', 50)->nullable()->default('web');
            $table->string('share_token', 36)->nullable()->unique();
            $table->timestamp('analizado_en')->nullable();
            $table->timestamps();

            $table->index('ocid');
            $table->index('estado');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdr_analisis_mayores');
    }
};
