<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_contract_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_subscription_id')
                ->constrained('telegram_subscriptions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('contrato_seace_id')->nullable();
            $table->string('contrato_codigo')->nullable();
            $table->string('contrato_entidad')->nullable();
            $table->string('contrato_objeto')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->json('keywords_snapshot')->nullable();
            $table->text('copy_snapshot')->nullable();
            $table->json('analisis_payload')->nullable();
            $table->string('source', 40)->default('public-domain');
            $table->timestamp('analizado_en')->nullable();
            $table->timestamps();

            $table->index('contrato_seace_id');
            $table->unique(['telegram_subscription_id', 'contrato_seace_id'], 'subscription_contrato_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_contract_matches');
    }
};
