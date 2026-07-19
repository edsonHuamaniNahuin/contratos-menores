<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_mayores', function (Blueprint $table) {
            $table->id();
            $table->string('ocid')->unique()->comment('OCID único del release OCDS');
            $table->string('entidad_nombre')->comment('Nombre de la entidad compradora');
            $table->string('entidad_ruc', 11)->nullable()->comment('RUC de la entidad');
            $table->string('entidad_direccion', 500)->nullable();
            $table->string('nomenclatura')->comment('Código de nomenclatura, ej: CP-ABR-2-2026-C-1');
            $table->text('descripcion_objeto')->nullable()->comment('Descripción completa del objeto');
            $table->string('objeto_contratacion', 50)->nullable()->comment('Bien, Servicio, Obra');
            $table->decimal('valor_referencial', 18, 2)->nullable()->default(0);
            $table->decimal('cuantia', 18, 2)->nullable();
            $table->string('moneda', 20)->nullable()->default('PEN');
            $table->dateTime('fecha_publicacion')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->string('metodo_contratacion', 200)->nullable()->comment('Método de procurement: open, selective, etc.');
            $table->string('estado', 50)->nullable()->comment('Estado: CONVOCADO, etc.');
            $table->string('codigo_snip', 50)->nullable();
            $table->json('proveedores')->nullable()->comment('Lista de proveedores adjudicados');
            $table->string('url_documento', 2000)->nullable()->comment('URL directa al PDF de bases');
            $table->json('datos_raw')->nullable()->comment('JSON completo del release OCDS');
            $table->timestamps();

            $table->index('fecha_publicacion');
            $table->index('nomenclatura');
            $table->index('entidad_nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_mayores');
    }
};
