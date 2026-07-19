<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Telegram
        Schema::table('telegram_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_subscriptions', 'recibir_menores')) {
                $table->boolean('recibir_menores')->default(true)->after('activo');
            }
            if (!Schema::hasColumn('telegram_subscriptions', 'recibir_mayores')) {
                $table->boolean('recibir_mayores')->default(true)->after('recibir_menores');
            }
        });

        // WhatsApp
        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_subscriptions', 'recibir_menores')) {
                $table->boolean('recibir_menores')->default(true)->after('activo');
            }
            if (!Schema::hasColumn('whatsapp_subscriptions', 'recibir_mayores')) {
                $table->boolean('recibir_mayores')->default(true)->after('recibir_menores');
            }
        });

        // Email
        Schema::table('email_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('email_subscriptions', 'recibir_menores')) {
                $table->boolean('recibir_menores')->default(true)->after('notificar_todo');
            }
            if (!Schema::hasColumn('email_subscriptions', 'recibir_mayores')) {
                $table->boolean('recibir_mayores')->default(true)->after('recibir_menores');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['recibir_menores', 'recibir_mayores']);
        });
        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['recibir_menores', 'recibir_mayores']);
        });
        Schema::table('email_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['recibir_menores', 'recibir_mayores']);
        });
    }
};
