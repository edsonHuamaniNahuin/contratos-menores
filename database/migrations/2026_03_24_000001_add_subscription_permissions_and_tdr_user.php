<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega permisos para agregar suscripciones por canal (Telegram, WhatsApp, Email)
 * y campo requested_by_user_id en tdr_analisis para vincular análisis a usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Nuevos permisos de suscripción ──────────────────────
        $permissions = [
            ['name' => 'Agregar suscripcion Telegram', 'slug' => 'add-telegram-subscription', 'description' => 'Permite agregar suscriptores de Telegram'],
            ['name' => 'Agregar suscripcion WhatsApp', 'slug' => 'add-whatsapp-subscription', 'description' => 'Permite agregar suscripcion de WhatsApp'],
            ['name' => 'Agregar suscripcion Email', 'slug' => 'add-email-subscription', 'description' => 'Permite agregar suscripcion de Email'],
        ];

        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $proveedorRoleId = DB::table('roles')->where('slug', 'proveedor')->value('id');

        foreach ($permissions as $permission) {
            $existingId = DB::table('permissions')->where('slug', $permission['slug'])->value('id');

            if (!$existingId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name'        => $permission['name'],
                    'slug'        => $permission['slug'],
                    'description' => $permission['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                $permissionId = $existingId;
            }

            // Asignar a admin y proveedor
            $roleIds = array_filter([$adminRoleId, $proveedorRoleId]);
            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $roleId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // ── 2. Campo requested_by_user_id en tdr_analisis ─────────
        if (!Schema::hasColumn('tdr_analisis', 'requested_by_user_id')) {
            Schema::table('tdr_analisis', function (Blueprint $table) {
                $table->foreignId('requested_by_user_id')
                    ->nullable()
                    ->after('contrato_archivo_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index('requested_by_user_id', 'idx_tdr_requested_user');
            });
        }
    }

    public function down(): void
    {
        // Quitar permisos
        $slugs = ['add-telegram-subscription', 'add-whatsapp-subscription', 'add-email-subscription'];
        $permissionIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();

        // Quitar columna
        if (Schema::hasColumn('tdr_analisis', 'requested_by_user_id')) {
            Schema::table('tdr_analisis', function (Blueprint $table) {
                $table->dropForeign(['requested_by_user_id']);
                $table->dropIndex('idx_tdr_requested_user');
                $table->dropColumn('requested_by_user_id');
            });
        }
    }
};
