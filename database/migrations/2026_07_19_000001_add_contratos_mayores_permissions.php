<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $permisos = [
        'view-buscador-mayores'          => 'Ver bandeja contratos mayores',
        'view-detalle-mayores'            => 'Ver detalle de contrato mayor',
        'download-tdr-mayores'           => 'Descargar TDR de contrato mayor',
        'follow-mayores'                 => 'Seguimiento de contrato mayor',
        'analyze-tdr-mayores'            => 'Analizar TDR con IA (contrato mayor)',
        'detect-direccionamiento-mayores'=> 'Detectar direccionamiento (contrato mayor)',
        'create-proforma-mayores'        => 'Crear proforma técnica (contrato mayor)',
        'view-partes-mayores'            => 'Ver partes involucradas (contrato mayor)',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permisos as $slug => $name) {
            if (!DB::table('permissions')->where('slug', $slug)->exists()) {
                DB::table('permissions')->insert([
                    'name'       => $name,
                    'slug'       => $slug,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $permisoIds = [];
        foreach ($this->permisos as $slug => $name) {
            $permisoIds[$slug] = DB::table('permissions')->where('slug', $slug)->value('id');
        }

        $admin = DB::table('roles')->where('slug', 'admin')->value('id');
        $premium = DB::table('roles')->where('slug', 'proveedor-premium')->value('id');

        foreach ($permisoIds as $slug => $permisoId) {
            if (!$permisoId) continue;

            foreach ([$admin, $premium] as $roleId) {
                if (!$roleId) continue;

                $exists = DB::table('permission_role')
                    ->where('permission_id', $permisoId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (!$exists) {
                    DB::table('permission_role')->insert([
                        'permission_id' => $permisoId,
                        'role_id'       => $roleId,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', array_keys($this->permisos))->delete();
    }
};
