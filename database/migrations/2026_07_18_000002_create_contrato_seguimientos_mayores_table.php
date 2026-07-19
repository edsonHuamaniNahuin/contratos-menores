<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_seguimientos_mayores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ocid')->comment('OCID del contrato mayor');
            $table->string('codigo_proceso')->comment('Nomenclatura');
            $table->string('entidad_nombre')->nullable();
            $table->string('objeto_contratacion')->nullable();
            $table->string('estado')->nullable();
            $table->dateTime('fecha_publicacion')->nullable();
            $table->decimal('valor_referencial', 18, 2)->nullable();
            $table->string('moneda', 20)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ocid']);
            $table->index('ocid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_seguimientos_mayores');
    }
};
