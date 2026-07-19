<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            if (!Schema::hasColumn('tdr_analisis', 'documento_extraido_id')) {
                $table->unsignedBigInteger('documento_extraido_id')
                    ->nullable()
                    ->default(null)
                    ->after('tipo_analisis')
                    ->comment('FK a documentos_extraidos. NULL = documento único');
            }
        });

        // El FK contrato_archivo_id usa el índice unique, hay que soltarlo y recrearlo
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropForeign('tdr_analisis_contrato_archivo_id_foreign');
            $table->dropUnique('tdr_analisis_unq');
        });

        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->unique(
                ['contrato_archivo_id', 'tipo_analisis', 'proveedor', 'modelo', 'documento_extraido_id'],
                'tdr_analisis_unq'
            );
            $table->foreign('contrato_archivo_id')
                ->references('id')->on('contrato_archivos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropForeign('tdr_analisis_contrato_archivo_id_foreign');
            $table->dropUnique('tdr_analisis_unq');
        });

        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->unique(
                ['contrato_archivo_id', 'tipo_analisis', 'proveedor', 'modelo'],
                'tdr_analisis_unq'
            );
            $table->foreign('contrato_archivo_id')
                ->references('id')->on('contrato_archivos')
                ->onDelete('cascade');
        });

        Schema::table('tdr_analisis', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_analisis', 'documento_extraido_id')) {
                $table->dropColumn('documento_extraido_id');
            }
        });
    }
};
