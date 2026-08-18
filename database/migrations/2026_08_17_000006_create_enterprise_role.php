<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rol Enterprise — creado sin permisos; se asignan manualmente
     * desde /roles-permisos.
     */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['slug' => 'enterprise'],
            [
                'name' => 'ENTERPRISE',
                'description' => 'Rol corporativo para clientes Enterprise (permisos asignados manualmente)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'enterprise')->delete();
    }
};
