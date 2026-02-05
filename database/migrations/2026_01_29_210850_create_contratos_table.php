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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

            // Identificador único del SEACE (CLAVE PRIMARIA FUNCIONAL)
            $table->unsignedBigInteger('id_contrato_seace')->unique()->comment('idContrato del JSON');

            // Datos básicos
            $table->integer('nro_contratacion')->comment('nroContratacion');
            $table->string('codigo_proceso')->index()->comment('desContratacion - Ej: CM-19-2026-MDH/CM');

            // Información de la entidad
            $table->string('entidad')->index()->comment('nomEntidad');

            // Objeto del contrato
            $table->unsignedTinyInteger('id_objeto_contrato')->comment('idObjetoContrato (1=Bien, 2=Servicio, 3=Obra, 4=Consultoría)');
            $table->string('objeto')->comment('nomObjetoContrato');
            $table->text('descripcion')->comment('desObjetoContrato - Descripción completa');

            // Estado
            $table->unsignedTinyInteger('id_estado_contrato')->comment('idEstadoContrato (1=Borrador, 2=Vigente, 3=En Evaluación, 4=Culminado)');
            $table->string('estado')->comment('nomEstadoContrato');

            // Fechas importantes
            $table->dateTime('fecha_publicacion')->comment('fecPublica');
            $table->dateTime('inicio_cotizacion')->nullable()->comment('fecIniCotizacion');
            $table->dateTime('fin_cotizacion')->nullable()->comment('fecFinCotizacion - Para alertas');

            // Etapa
            $table->string('etapa_contratacion')->nullable()->comment('nomEtapaContratacion');

            // Datos adicionales
            $table->unsignedTinyInteger('id_tipo_cotizacion')->nullable()->comment('idTipoCotizacion');
            $table->unsignedInteger('num_subsanaciones_total')->default(0)->comment('numSubsanacionesTotal');
            $table->unsignedInteger('num_subsanaciones_pendientes')->default(0)->comment('numSubsanacionesPendientes');

            // JSON completo por si acaso
            $table->json('datos_raw')->nullable()->comment('JSON original completo del SEACE');

            // Auditoría
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('estado');
            $table->index('fecha_publicacion');
            $table->index('fin_cotizacion');
            $table->index(['entidad', 'estado']);
            $table->index(['id_estado_contrato', 'fecha_publicacion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
