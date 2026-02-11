<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'ADMIN',
            'slug' => 'admin',
            'description' => 'Administrador del sistema',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $providerRoleId = DB::table('roles')->insertGetId([
            'name' => 'PROVEEDORES',
            'slug' => 'proveedor',
            'description' => 'Proveedores y usuarios operativos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            ['name' => 'Ver repositorio TDR', 'slug' => 'view-tdr-repository'],
            ['name' => 'Ver configuracion', 'slug' => 'view-configuracion'],
            ['name' => 'Ver buscador publico', 'slug' => 'view-buscador-publico'],
            ['name' => 'Ver cuentas SEACE', 'slug' => 'view-cuentas'],
            ['name' => 'Ver prueba endpoints', 'slug' => 'view-prueba-endpoints'],
            ['name' => 'Ver suscriptores', 'slug' => 'view-suscriptores'],
            ['name' => 'Gestionar roles y permisos', 'slug' => 'manage-roles-permissions'],
            ['name' => 'Importar contratos en dashboard', 'slug' => 'import-tdr'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'description' => $permission['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $providerPermissions = [
            'view-buscador-publico',
            'view-suscriptores',
        ];

        foreach ($providerPermissions as $slug) {
            $permissionId = $permissionIds[$slug] ?? null;
            if (!$permissionId) {
                continue;
            }

            DB::table('permission_role')->insert([
                'permission_id' => $permissionId,
                'role_id' => $providerRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $users = DB::table('users')->orderBy('id')->pluck('id');
        if ($users->isNotEmpty()) {
            $firstUserId = $users->first();

            DB::table('role_user')->insert([
                'role_id' => $adminRoleId,
                'user_id' => $firstUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($users->skip(1) as $userId) {
                DB::table('role_user')->insert([
                    'role_id' => $providerRoleId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
