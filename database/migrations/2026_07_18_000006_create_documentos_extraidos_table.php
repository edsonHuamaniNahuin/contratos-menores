<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_extraidos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_contrato', 20)->comment('menores | mayores');
            $table->string('contrato_ref', 200)->comment('ID del contrato: OCID (mayores) o contrato_seace_id (menores)');
            $table->string('nombre_archivo', 500)->comment('Nombre del PDF dentro del ZIP');
            $table->string('ruta_archivo', 2000)->comment('Ruta absoluta al PDF extraído');
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->dateTime('extraido_en')->nullable();
            $table->timestamps();

            $table->unique(['tipo_contrato', 'contrato_ref', 'nombre_archivo'], 'doc_ext_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_extraidos');
    }
};
