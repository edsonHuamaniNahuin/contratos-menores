<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permiso para la vista de monitoreo del sistema (jobs, servicios, logs).
     */
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'view-monitoreo-sistema'],
            [
                'name' => 'Ver monitoreo del sistema',
                'description' => 'Acceso a la vista de monitoreo en tiempo real (jobs, servicios, errores)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Asignar al rol admin si existe
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $perm = DB::table('permissions')->where('slug', 'view-monitoreo-sistema')->first();

        if ($adminRole && $perm) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $adminRole->id, 'permission_id' => $perm->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('permission_role')
            ->whereIn('permission_id', DB::table('permissions')->where('slug', 'view-monitoreo-sistema')->pluck('id'))
            ->delete();

        DB::table('permissions')->where('slug', 'view-monitoreo-sistema')->delete();
    }
};
