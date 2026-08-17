<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permiso para exportar Contratos Mayores a Excel (plantillas Honda/Seguimiento).
     * El botón de exportación solo aparece para roles con este permiso.
     */
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'export-mayores'],
            [
                'name' => 'Exportar contratos mayores a Excel',
                'description' => 'Exporta los resultados del buscador de Contratos Mayores a Excel (plantillas Honda/Seguimiento)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permisoId = DB::table('permissions')->where('slug', 'export-mayores')->value('id');
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
            ->whereIn('permission_id', DB::table('permissions')->where('slug', 'export-mayores')->pluck('id'))
            ->delete();

        DB::table('permissions')->where('slug', 'export-mayores')->delete();
    }
};
