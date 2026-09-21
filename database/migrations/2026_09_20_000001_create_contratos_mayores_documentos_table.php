<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documentos (Bases, TDR, etc.) de los procedimientos del buscador SEACE.
     *
     * Los captura el scraper desde la Ficha de Selección del buscador público
     * (el Excel no trae links y la API OCDS no cubre a la mayoría de procesos).
     * El `file_code` es el identificador del CMS del SEACE (Alfresco) con el
     * que se resuelve la URL de descarga on-demand (el ticket expira).
     */
    public function up(): void
    {
        Schema::create('contratos_mayores_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_mayor_id')->nullable()
                ->constrained('contratos_mayores')->nullOnDelete();
            // Clave normalizada (minúsculas + alfanumérico) para cruzar con el
            // registro aunque la puntuación de la nomenclatura varíe.
            $table->string('nomenclatura_normalizada', 191)->index();
            $table->string('nombre');                  // "Bases Administrativas"
            $table->string('etapa')->nullable();       // "Convocatoria"
            $table->string('tipo', 20)->nullable();    // tipo del CMS (3 = privado)
            $table->string('file_code', 64)->unique(); // uuid del documento en Alfresco
            $table->string('filename')->nullable();    // nombre del archivo
            $table->date('fecha_documento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_mayores_documentos');
    }
};
