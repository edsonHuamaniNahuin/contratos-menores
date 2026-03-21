<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->uuid('share_token')
                ->nullable()
                ->unique()
                ->after('tipo_analisis')
                ->comment('Token público para compartir el análisis');
        });

        // Generar tokens para análisis existentes exitosos
        DB::table('tdr_analisis')
            ->where('estado', 'exitoso')
            ->whereNull('share_token')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('tdr_analisis')
                    ->where('id', $row->id)
                    ->update(['share_token' => Str::uuid()->toString()]);
            });
    }

    public function down(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
