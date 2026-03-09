<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el permiso 'cotizar-seace' al sistema de permisos.
 * Permite a ciertos roles redirigir al portal SEACE para cotizar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Crear permiso
        $existingId = DB::table('permissions')->where('slug', 'cotizar-seace')->value('id');

        if (!$existingId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'        => 'Cotizar en SEACE',
                'slug'        => 'cotizar-seace',
                'description' => 'Permite iniciar sesión en SEACE y redirigir al portal de cotización',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            $permissionId = $existingId;
        }

        // Asignar a admin y proveedor-premium
        $rolesSlugs = ['admin', 'proveedor-premium'];
        $roleIds = DB::table('roles')->whereIn('slug', $rolesSlugs)->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'cotizar-seace')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
