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
        Schema::create('contrato_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')->nullable()->constrained('contratos')->nullOnDelete();
            $table->unsignedBigInteger('id_contrato_seace')->nullable()->index();
            $table->unsignedBigInteger('id_archivo_seace')->unique()->comment('ID del archivo dentro del SEACE');
            $table->string('codigo_proceso')->nullable()->index();
            $table->string('entidad')->nullable()->index();
            $table->string('nombre_original');
            $table->string('nombre_sistema')->nullable()->comment('Nombre del archivo dentro del storage local');
            $table->string('extension', 16)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('sha256', 96)->nullable();
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path')->nullable();
            $table->timestamp('descargado_en')->nullable();
            $table->timestamp('verificado_en')->nullable();
            $table->json('datos_contrato')->nullable()->comment('Snapshot del proceso al momento de la descarga');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['id_contrato_seace', 'id_archivo_seace'], 'seace_contrato_archivo_idx');
            $table->index(['descargado_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_archivos');
    }
};
