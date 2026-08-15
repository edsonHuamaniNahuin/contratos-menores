<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas maestras de geografía (ubigeo administrativo normalizado)
     * para Contratos Mayores. Los procesos guardan solo el FK, evitando
     * duplicar cadenas de departamento/provincia/distrito en cada fila.
     */
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->timestamps();

            $table->unique(['departamento_id', 'nombre']);
        });

        Schema::create('distritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_id')->constrained('provincias')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->timestamps();

            $table->unique(['provincia_id', 'nombre']);
        });

        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->foreignId('departamento_id')->nullable()->after('entidad_direccion')
                ->constrained('departamentos')->nullOnDelete();
            $table->foreignId('provincia_id')->nullable()->after('departamento_id')
                ->constrained('provincias')->nullOnDelete();
            $table->foreignId('distrito_id')->nullable()->after('provincia_id')
                ->constrained('distritos')->nullOnDelete();

            $table->index('departamento_id');
            $table->index('provincia_id');
            $table->index('distrito_id');
        });
    }

    public function down(): void
    {
        Schema::table('contratos_mayores', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['provincia_id']);
            $table->dropForeign(['distrito_id']);
            $table->dropIndex(['departamento_id']);
            $table->dropIndex(['provincia_id']);
            $table->dropIndex(['distrito_id']);
            $table->dropColumn(['departamento_id', 'provincia_id', 'distrito_id']);
        });

        Schema::dropIfExists('distritos');
        Schema::dropIfExists('provincias');
        Schema::dropIfExists('departamentos');
    }
};
