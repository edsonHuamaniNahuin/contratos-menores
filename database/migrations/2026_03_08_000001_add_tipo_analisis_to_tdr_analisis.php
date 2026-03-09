<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columna tipo_analisis a tdr_analisis.
 *
 * Permite diferenciar entre análisis 'general' (resumen TDR estándar)
 * y 'direccionamiento' (auditoría forense de corrupción).
 * Actualiza la constraint unique para incluir el tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna solo si no existe (idempotente ante migración parcial)
        if (!Schema::hasColumn('tdr_analisis', 'tipo_analisis')) {
            Schema::table('tdr_analisis', function (Blueprint $table) {
                $table->string('tipo_analisis', 32)
                    ->default('general')
                    ->after('contrato_archivo_id')
                    ->comment('general | direccionamiento');
            });
        }

        // Recrear unique constraint incluyendo tipo_analisis.
        // Debemos soltar el FK primero porque MySQL no permite drop de un
        // índice que respalda un foreign key.
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropForeign(['contrato_archivo_id']);
            $table->dropUnique('tdr_analisis_unq');
            $table->unique(
                ['contrato_archivo_id', 'tipo_analisis', 'proveedor', 'modelo'],
                'tdr_analisis_unq'
            );
            $table->foreign('contrato_archivo_id')
                ->references('id')->on('contrato_archivos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropForeign(['contrato_archivo_id']);
            $table->dropUnique('tdr_analisis_unq');
            $table->unique(
                ['contrato_archivo_id', 'proveedor', 'modelo'],
                'tdr_analisis_unq'
            );
            $table->foreign('contrato_archivo_id')
                ->references('id')->on('contrato_archivos')
                ->cascadeOnDelete();
            $table->dropColumn('tipo_analisis');
        });
    }
};
