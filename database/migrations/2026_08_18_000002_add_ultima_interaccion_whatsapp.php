<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra la última interacción entrante del usuario (mensaje o botón).
     *
     * WhatsApp Cloud API solo permite mensajes free-form/interactive dentro
     * de la ventana de 24h desde el último mensaje del cliente. Fuera de ella,
     * las notificaciones deben ir como Template Message (entrega garantizada).
     */
    public function up(): void
    {
        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->timestamp('ultima_interaccion_at')->nullable()->after('ultima_notificacion_at');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->dropColumn('ultima_interaccion_at');
        });
    }
};
