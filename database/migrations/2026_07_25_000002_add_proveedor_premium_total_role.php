<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roleId = DB::table('roles')->where('slug', 'proveedor-premium-total')->value('id');
        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'PROVEEDOR PREMIUM TOTAL',
                'slug' => 'proveedor-premium-total',
                'description' => 'Proveedor Premium con acceso completo a Contratos Mayores',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $premiumRoleId = DB::table('roles')->where('slug', 'proveedor-premium')->value('id');

        if ($premiumRoleId) {
            $permisoIds = DB::table('permission_role')
                ->where('role_id', $premiumRoleId)
                ->pluck('permission_id');

            foreach ($permisoIds as $permisoId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permisoId, 'role_id' => $roleId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('role_user')->where('role_id', function ($q) {
            $q->select('id')->from('roles')->where('slug', 'proveedor-premium-total');
        })->delete();

        DB::table('permission_role')->where('role_id', function ($q) {
            $q->select('id')->from('roles')->where('slug', 'proveedor-premium-total');
        })->delete();

        DB::table('roles')->where('slug', 'proveedor-premium-total')->delete();
    }
};
