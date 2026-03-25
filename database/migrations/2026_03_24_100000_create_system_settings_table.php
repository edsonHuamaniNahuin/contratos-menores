<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Migrar valores actuales del .env a la BD
        $settings = [
            'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
            'telegram_chat_id' => env('TELEGRAM_CHAT_ID', ''),
            'telegram_admin_bot_token' => env('TELEGRAM_ADMIN_BOT_TOKEN', ''),
            'telegram_admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
            'analizador_tdr_url' => env('ANALIZADOR_TDR_URL', 'http://127.0.0.1:8001'),
            'analizador_tdr_enabled' => env('ANALIZADOR_TDR_ENABLED', false) ? '1' : '0',
            'whatsapp_bot_token' => env('WHATSAPP_BOT_TOKEN', ''),
            'whatsapp_group_id' => env('WHATSAPP_GROUP_ID', ''),
            'payment_gateway' => env('PAYMENT_GATEWAY', 'mercadopago'),
            'mercadopago_access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
            'mercadopago_public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
            'mercadopago_webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET', ''),
            'openpay_merchant_id' => env('OPENPAY_MERCHANT_ID', ''),
            'openpay_private_key' => env('OPENPAY_PRIVATE_KEY', ''),
            'openpay_public_key' => env('OPENPAY_PUBLIC_KEY', ''),
            'openpay_production' => env('OPENPAY_PRODUCTION', false) ? '1' : '0',
        ];

        $now = now();
        foreach ($settings as $key => $value) {
            DB::table('system_settings')->insert([
                'key' => $key,
                'value' => (string) $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
