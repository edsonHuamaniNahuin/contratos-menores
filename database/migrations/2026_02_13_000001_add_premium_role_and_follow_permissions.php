<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'analyze-tdr' => 'Analizar TDR con IA',
            'follow-contracts' => 'Hacer seguimiento de procesos',
        ];

        $permissionIds = [];
        foreach ($permissions as $slug => $name) {
            $existingId = DB::table('permissions')->where('slug', $slug)->value('id');
            if ($existingId) {
                $permissionIds[$slug] = $existingId;
                continue;
            }

            $permissionIds[$slug] = DB::table('permissions')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'description' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $providerRoleId = DB::table('roles')->where('slug', 'proveedor')->value('id');

        $premiumRoleId = DB::table('roles')->where('slug', 'proveedor-premium')->value('id');
        if (!$premiumRoleId) {
            $premiumRoleId = DB::table('roles')->insertGetId([
                'name' => 'PROVEEDOR PREMIUM',
                'slug' => 'proveedor-premium',
                'description' => 'Proveedor premium con acceso a analisis y seguimiento',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($providerRoleId) {
            $providerPermissions = DB::table('permission_role')
                ->where('role_id', $providerRoleId)
                ->pluck('permission_id')
                ->all();

            foreach ($providerPermissions as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $premiumRoleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        foreach (['analyze-tdr', 'follow-contracts'] as $slug) {
            $permissionId = $permissionIds[$slug] ?? null;
            if (!$permissionId) {
                continue;
            }

            if ($adminRoleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $adminRoleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $premiumRoleId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['analyze-tdr', 'follow-contracts'])
            ->pluck('id')
            ->all();

        if (!empty($permissionIds)) {
            DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        DB::table('roles')->where('slug', 'proveedor-premium')->delete();
    }
};
