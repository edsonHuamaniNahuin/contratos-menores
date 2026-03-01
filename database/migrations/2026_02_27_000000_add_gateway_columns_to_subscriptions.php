<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Agrega columnas genéricas de pasarela de pago a subscriptions.
 *
 * Las columnas openpay_* se mantienen por compatibilidad con datos existentes.
 * El nuevo código usa gateway_* para todas las pasarelas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('gateway_provider', 30)->nullable()->after('trial_ends_at')
                ->comment('openpay | mercadopago');
            $table->string('gateway_charge_id')->nullable()->after('gateway_provider');
            $table->string('gateway_customer_id')->nullable()->after('gateway_charge_id');
            $table->string('gateway_card_id', 100)->nullable()->after('gateway_customer_id');

            $table->index('gateway_charge_id');
            $table->index('gateway_provider');
        });

        // Migrar datos existentes de openpay_* → gateway_*
        DB::table('subscriptions')
            ->whereNotNull('openpay_charge_id')
            ->orWhereNotNull('openpay_customer_id')
            ->orWhereNotNull('openpay_card_id')
            ->update([
                'gateway_provider' => DB::raw("'openpay'"),
            ]);

        DB::table('subscriptions')
            ->whereNotNull('openpay_charge_id')
            ->update([
                'gateway_charge_id' => DB::raw('openpay_charge_id'),
            ]);

        DB::table('subscriptions')
            ->whereNotNull('openpay_customer_id')
            ->update([
                'gateway_customer_id' => DB::raw('openpay_customer_id'),
            ]);

        DB::table('subscriptions')
            ->whereNotNull('openpay_card_id')
            ->update([
                'gateway_card_id' => DB::raw('openpay_card_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['gateway_charge_id']);
            $table->dropIndex(['gateway_provider']);
            $table->dropColumn([
                'gateway_provider',
                'gateway_charge_id',
                'gateway_customer_id',
                'gateway_card_id',
            ]);
        });
    }
};
