<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Columna origin en tdr_analisis ──
        if (!Schema::hasColumn('tdr_analisis', 'origin')) {
            Schema::table('tdr_analisis', function (Blueprint $table) {
                $table->string('origin', 32)->nullable()->after('requested_by_user_id')->index('idx_tdr_origin');
            });
        }

        // ── Permiso view-consumo-ia ──
        $exists = DB::table('permissions')->where('slug', 'view-consumo-ia')->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'Ver consumo IA',
                'slug' => 'view-consumo-ia',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Asignar al rol admin si existe
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $permission = DB::table('permissions')->where('slug', 'view-consumo-ia')->first();

        if ($adminRole && $permission) {
            $exists = DB::table('permission_role')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permission->id)
                ->exists();

            if (! $exists) {
                DB::table('permission_role')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tdr_analisis', function (Blueprint $table) {
            $table->dropColumn('origin');
        });

        $permission = DB::table('permissions')->where('slug', 'view-consumo-ia')->first();

        if ($permission) {
            DB::table('permission_role')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
