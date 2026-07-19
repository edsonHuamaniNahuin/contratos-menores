<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            if (!Schema::hasColumn('tdr_analisis_mayores', 'tipo')) {
                $table->string('tipo', 50)->nullable()->default('general')->after('ocid')
                    ->comment('Tipo de análisis: general, direccionamiento');
                $table->index('tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tdr_analisis_mayores', function (Blueprint $table) {
            if (Schema::hasColumn('tdr_analisis_mayores', 'tipo')) {
                $table->dropIndex(['tipo']);
                $table->dropColumn('tipo');
            }
        });
    }
};
