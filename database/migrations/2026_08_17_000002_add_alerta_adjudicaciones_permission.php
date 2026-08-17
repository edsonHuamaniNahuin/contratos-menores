<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permiso para activar la alerta "cuando procesos se hayan adjudicado"
     * en /configuracion-alertas. El toggle solo aparece para roles con
     * este permiso.
     */
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'alerta-adjudicaciones'],
            [
                'name' => 'Alertas de adjudicación (buena pro)',
                'description' => 'Recibir alertas cuando un proceso vigilado (>= S/ 1M) pase a buena pro, por los canales activos',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permisoId = DB::table('permissions')->where('slug', 'alerta-adjudicaciones')->value('id');
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
            ->whereIn('permission_id', DB::table('permissions')->where('slug', 'alerta-adjudicaciones')->pluck('id'))
            ->delete();

        DB::table('permissions')->where('slug', 'alerta-adjudicaciones')->delete();
    }
};
