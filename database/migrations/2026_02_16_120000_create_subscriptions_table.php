<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Plan: trial | monthly | yearly
            $table->string('plan', 30)->default('trial');

            // Estado: active | expired | cancelled | payment_pending
            $table->string('status', 30)->default('active');

            // Fechas de vigencia
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Pago (Openpay)
            $table->string('openpay_charge_id')->nullable();
            $table->string('openpay_customer_id')->nullable();
            $table->string('payment_method', 30)->nullable(); // card | store | bank
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('PEN');

            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
            $table->index('openpay_charge_id');
        });

        // Agregar permiso manage-subscriptions si no existe
        $existing = DB::table('permissions')->where('slug', 'manage-subscriptions')->exists();
        if (!$existing) {
            $permId = DB::table('permissions')->insertGetId([
                'name'        => 'Gestionar suscripciones',
                'slug'        => 'manage-subscriptions',
                'description' => 'Ver y gestionar suscripciones premium de usuarios',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Asignar al rol admin
            $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
            if ($adminRoleId) {
                DB::table('permission_role')->insert([
                    'permission_id' => $permId,
                    'role_id'       => $adminRoleId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');

        DB::table('permissions')->where('slug', 'manage-subscriptions')->delete();
    }
};
