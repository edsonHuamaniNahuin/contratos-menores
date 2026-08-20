<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('slug', 'view-analytics')->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'Ver Analytics (GA4)',
                'slug' => 'view-analytics',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        $permission = DB::table('permissions')->where('slug', 'view-analytics')->first();

        if ($adminRole && $permission) {
            $assigned = DB::table('permission_role')
                ->where('role_id', $adminRole->id)
                ->where('permission_id', $permission->id)
                ->exists();

            if (! $assigned) {
                DB::table('permission_role')->insert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'view-analytics')->first();

        if ($permission) {
            DB::table('permission_role')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
