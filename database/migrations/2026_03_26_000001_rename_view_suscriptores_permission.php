<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('slug', 'view-suscriptores')
            ->update([
                'name' => 'Ver configuracion de alertas',
                'slug' => 'view-configuracion-alertas',
                'description' => 'Ver configuracion de alertas',
            ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', 'view-configuracion-alertas')
            ->update([
                'name' => 'Ver suscriptores',
                'slug' => 'view-suscriptores',
                'description' => 'Ver suscriptores',
            ]);
    }
};
