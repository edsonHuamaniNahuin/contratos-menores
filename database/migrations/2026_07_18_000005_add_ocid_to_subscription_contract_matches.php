<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_contract_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_contract_matches', 'ocid')) {
                $table->string('ocid', 100)->nullable()->after('whatsapp_subscription_id')
                    ->comment('OCID del contrato mayor (cuando es contrato_mayores, nullable para menores)');
                $table->index('ocid');
            }

            // Hacer contrato_seace_id nullable para permitir matches con solo OCID
            DB::statement('ALTER TABLE subscription_contract_matches MODIFY contrato_seace_id BIGINT UNSIGNED NULL');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_contract_matches', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_contract_matches', 'ocid')) {
                $table->dropIndex(['ocid']);
                $table->dropColumn('ocid');
            }
        });

        DB::statement('ALTER TABLE subscription_contract_matches MODIFY contrato_seace_id BIGINT UNSIGNED NOT NULL');
    }
};
