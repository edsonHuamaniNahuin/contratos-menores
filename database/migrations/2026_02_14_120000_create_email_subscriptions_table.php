<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email')->comment('Correo al que se envian las notificaciones');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultima_notificacion_at')->nullable();
            $table->unsignedInteger('notificaciones_enviadas')->default(0);
            $table->timestamps();

            $table->unique('user_id'); // Solo 1 suscripcion email por usuario
            $table->index('activo');
        });

        // Tabla para evitar envios duplicados (dedup)
        Schema::create('email_contract_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_subscription_id')->constrained('email_subscriptions')->cascadeOnDelete();
            $table->unsignedBigInteger('contrato_seace_id');
            $table->string('contrato_codigo')->nullable();
            $table->timestamp('enviado_at')->useCurrent();
            $table->timestamps();

            $table->unique(['email_subscription_id', 'contrato_seace_id'], 'email_contrato_unique');
            $table->index('contrato_seace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_contract_sends');
        Schema::dropIfExists('email_subscriptions');
    }
};
