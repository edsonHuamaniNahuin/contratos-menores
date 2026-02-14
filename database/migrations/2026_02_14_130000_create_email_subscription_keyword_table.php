<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna para decidir si recibe todo o filtra por keywords
        Schema::table('email_subscriptions', function (Blueprint $table) {
            $table->boolean('notificar_todo')->default(true)->after('activo')
                ->comment('true = recibe todos los procesos, false = filtra por keywords');
        });

        // Tabla pivot para keywords propios de la suscripción email
        Schema::create('email_subscription_keyword', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_subscription_id');
            $table->unsignedBigInteger('notification_keyword_id');
            $table->timestamps();

            $table->unique(
                ['email_subscription_id', 'notification_keyword_id'],
                'email_sub_keyword_unique'
            );

            $table->foreign('email_subscription_id', 'esk_subscription_fk')
                ->references('id')->on('email_subscriptions')
                ->cascadeOnDelete();

            $table->foreign('notification_keyword_id', 'esk_keyword_fk')
                ->references('id')->on('notification_keywords')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_subscription_keyword');

        Schema::table('email_subscriptions', function (Blueprint $table) {
            $table->dropColumn('notificar_todo');
        });
    }
};
