<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el permiso 'create-proforma' al sistema de permisos.
 * Permite generar una proforma técnica desde el TDR de un proceso (sección TDR y procesos).
 */
return new class extends Migration
{
    public function up(): void
    {
        $existingId = DB::table('permissions')->where('slug', 'create-proforma')->value('id');

        if (!$existingId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name'        => 'Generar Proforma Técnica',
                'slug'        => 'create-proforma',
                'description' => 'Permite generar una proforma técnica con IA desde el TDR de un proceso licitado',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            $permissionId = $existingId;
        }

        // Asignar a admin y proveedor-premium
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['admin', 'proveedor-premium'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'create-proforma')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
