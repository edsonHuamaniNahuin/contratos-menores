<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soporte para seguimiento de entrega de mensajes WhatsApp y cola de
     * reenvío cuando la ventana de 24h (política de Meta) está cerrada.
     *
     * - notification_sends.wamid: id del mensaje en Meta para correlacionar
     *   con los estados del webhook (delivered / failed).
     * - notification_sends.estado_entrega: aceptado | delivered | read | failed.
     * - notification_sends.reenviado_at: cuándo se reenvió el proceso tras
     *   reabrirse la ventana.
     * - whatsapp_subscriptions.ultima_entrega_fallida_at: evidencia de que
     *   Meta rechazó la entrega por ventana cerrada (error 131047).
     */
    public function up(): void
    {
        Schema::table('notification_sends', function (Blueprint $table) {
            $table->string('wamid')->nullable()->after('keywords_matched')->index();
            $table->string('estado_entrega', 20)->default('aceptado')->after('wamid');
            $table->timestamp('reenviado_at')->nullable()->after('estado_entrega');
        });

        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->timestamp('ultima_entrega_fallida_at')->nullable()->after('ultima_interaccion_at');
        });
    }

    public function down(): void
    {
        Schema::table('notification_sends', function (Blueprint $table) {
            $table->dropColumn(['wamid', 'estado_entrega', 'reenviado_at']);
        });

        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->dropColumn('ultima_entrega_fallida_at');
        });
    }
};
