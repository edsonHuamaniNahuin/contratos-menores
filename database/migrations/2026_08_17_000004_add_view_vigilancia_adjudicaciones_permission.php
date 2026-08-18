<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permiso para ver la bandeja de Vigilancia de Adjudicaciones
     * (procesos >= umbral en seguimiento de buena pro).
     */
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'view-vigilancia-adjudicaciones'],
            [
                'name' => 'Ver vigilancia de adjudicaciones',
                'description' => 'Bandeja de procesos vigilados (>= S/ 1M) con filtros y estado de seguimiento de buena pro',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permisoId = DB::table('permissions')->where('slug', 'view-vigilancia-adjudicaciones')->value('id');
        if (!$permisoId) {
            return;
        }

        $roles = DB::table('roles')->whereIn('slug', ['admin', 'proveedor-premium'])->pluck('id');

        foreach ($roles as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permisoId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('permission_role')
            ->whereIn('permission_id', DB::table('permissions')->where('slug', 'view-vigilancia-adjudicaciones')->pluck('id'))
            ->delete();

        DB::table('permissions')->where('slug', 'view-vigilancia-adjudicaciones')->delete();
    }
};
