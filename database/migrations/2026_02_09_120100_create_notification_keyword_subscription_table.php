<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_keyword_subscription', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_keyword_id');
            $table->unsignedBigInteger('telegram_subscription_id');
            $table->timestamps();

            $table->unique(['notification_keyword_id', 'telegram_subscription_id'], 'nk_subscription_unique');

            $table->foreign('notification_keyword_id', 'nk_keyword_fk')
                ->references('id')->on('notification_keywords')
                ->cascadeOnDelete();

            $table->foreign('telegram_subscription_id', 'nk_subscription_fk')
                ->references('id')->on('telegram_subscriptions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_keyword_subscription');
    }
};
