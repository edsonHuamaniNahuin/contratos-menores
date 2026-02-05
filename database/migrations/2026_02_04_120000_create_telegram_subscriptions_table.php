<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_subscriptions', function (Blueprint $table) {
            $table->id();

            // Identificador único de Telegram
            $table->string('chat_id')->unique()->comment('Chat ID de Telegram del suscriptor');

            // Información del suscriptor (opcional)
            $table->string('nombre')->nullable()->comment('Nombre descriptivo del suscriptor');
            $table->string('username')->nullable()->comment('Username de Telegram (@usuario)');

            // Estado
            $table->boolean('activo')->default(true)->comment('Si está activo recibe notificaciones');

            // Filtros personalizados (JSON)
            $table->json('filtros')->nullable()->comment('Filtros personalizados: departamento, objeto, palabras clave');

            // Auditoría
            $table->timestamp('subscrito_at')->useCurrent()->comment('Fecha de suscripción');
            $table->timestamp('ultima_notificacion_at')->nullable()->comment('Última notificación enviada');
            $table->unsignedInteger('notificaciones_recibidas')->default(0)->comment('Contador de notificaciones');

            $table->timestamps();

            // Índices
            $table->index('chat_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_subscriptions');
    }
};
