<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('contrato_seace_id');
            $table->string('codigo_proceso');
            $table->string('entidad')->nullable();
            $table->string('objeto')->nullable();
            $table->string('estado')->nullable();
            $table->dateTime('fecha_publicacion')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'contrato_seace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_seguimientos');
    }
};
