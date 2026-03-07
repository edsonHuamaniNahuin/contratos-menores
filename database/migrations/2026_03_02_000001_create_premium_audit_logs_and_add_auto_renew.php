<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea tabla de auditoría premium y agrega auto_renew a subscriptions.
 *
 * premium_audit_logs: registra cada cambio de estado premium de un usuario.
 * auto_renew: flag para que el usuario controle la renovación automática.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tabla de auditoría premium ──
        Schema::create('premium_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Acción: granted | revoked
            $table->string('action', 20);

            // Origen: purchase | trial | admin | system_expiry | admin_role_change | renewal
            $table->string('source', 30);

            // Plan al momento del evento
            $table->string('plan', 30)->nullable();

            // Referencia a la suscripción (si aplica)
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            // ¿Quién lo hizo? (null = sistema automático)
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();

            // Fechas del periodo premium
            $table->timestamp('premium_starts_at')->nullable();
            $table->timestamp('premium_ends_at')->nullable();
            $table->integer('days_remaining')->nullable();

            // Datos de pago (snapshot)
            $table->string('gateway_provider', 30)->nullable();
            $table->string('charge_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);

            // Notas adicionales
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices
            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });

        // ── Agregar auto_renew a subscriptions ──
        if (!Schema::hasColumn('subscriptions', 'auto_renew')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->boolean('auto_renew')->default(true)->after('cancelled_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('premium_audit_logs');

        if (Schema::hasColumn('subscriptions', 'auto_renew')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('auto_renew');
            });
        }
    }
};
