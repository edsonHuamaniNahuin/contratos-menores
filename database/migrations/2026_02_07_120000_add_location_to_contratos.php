<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->unsignedSmallInteger('codigo_departamento')->nullable()->after('entidad')->comment('codigo_departamento del buscador público');
            $table->string('nombre_departamento')->nullable()->after('codigo_departamento')->comment('Nombre del departamento');
            $table->unsignedInteger('codigo_provincia')->nullable()->after('nombre_departamento')->comment('Identificador de provincia si está disponible');
            $table->string('nombre_provincia')->nullable()->after('codigo_provincia')->comment('Nombre de la provincia');

            $table->index('codigo_departamento');
            $table->index('codigo_provincia');
        });
    }

    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropIndex(['codigo_departamento']);
            $table->dropIndex(['codigo_provincia']);

            $table->dropColumn([
                'codigo_departamento',
                'nombre_departamento',
                'codigo_provincia',
                'nombre_provincia',
            ]);
        });
    }
};
