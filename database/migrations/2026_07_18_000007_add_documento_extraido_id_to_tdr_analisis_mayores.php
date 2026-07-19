<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            if (!Schema::hasColumn('tdr_analisis_mayores', 'documento_extraido_id')) {
                $table->unsignedBigInteger('documento_extraido_id')
                    ->nullable()
                    ->default(null)
                    ->after('tipo')
                    ->comment('FK a documentos_extraidos. NULL = documento único (no extraído de ZIP)');
            }
        });

        // Agregar índice único compuesto para permitir M análisis por OCID+tipo
        // si cada uno referencia un documento distinto.
        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            $table->unique(['ocid', 'tipo', 'documento_extraido_id'], 'tdr_mayores_ocid_tipo_doc_unq');
        });
    }

    public function down(): void
    {
        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            $table->dropUnique('tdr_mayores_ocid_tipo_doc_unq');
        });

        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_analisis_mayores', 'documento_extraido_id')) {
                $table->dropColumn('documento_extraido_id');
            }
        });
    }
};
