<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tabla principal de suscripciones WhatsApp ─────────────────
        Schema::create('whatsapp_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Identificador único de WhatsApp (número internacional sin +)
            $table->string('phone_number')->unique()
                ->comment('Número WhatsApp con código país, ej: 51987654321');

            $table->string('nombre')->nullable()
                ->comment('Nombre descriptivo del suscriptor');

            $table->text('company_copy')->nullable()
                ->comment('Copy descriptivo del rubro de la empresa para IA');

            $table->boolean('activo')->default(true)
                ->comment('Si está activo recibe notificaciones');

            $table->json('filtros')->nullable()
                ->comment('Filtros personalizados: departamento, objeto, palabras clave');

            // Auditoría
            $table->timestamp('subscrito_at')->useCurrent();
            $table->timestamp('ultima_notificacion_at')->nullable();
            $table->unsignedInteger('notificaciones_recibidas')->default(0);

            $table->timestamps();

            // Índices
            $table->index('activo');
            $table->index('phone_number');
        });

        // ── Tabla pivot: keywords ↔ whatsapp subscriptions ───────────
        Schema::create('whatsapp_subscription_keyword', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('whatsapp_subscription_id');
            $table->unsignedBigInteger('notification_keyword_id');
            $table->timestamps();

            $table->unique(
                ['whatsapp_subscription_id', 'notification_keyword_id'],
                'wa_sub_keyword_unique'
            );

            $table->foreign('whatsapp_subscription_id', 'wa_sub_fk')
                ->references('id')->on('whatsapp_subscriptions')
                ->cascadeOnDelete();

            $table->foreign('notification_keyword_id', 'wa_keyword_fk')
                ->references('id')->on('notification_keywords')
                ->cascadeOnDelete();
        });

        // ── Agregar columna whatsapp_subscription_id a subscription_contract_matches ──
        // Para soportar scores de compatibilidad también para WhatsApp
        if (Schema::hasTable('subscription_contract_matches')
            && !Schema::hasColumn('subscription_contract_matches', 'whatsapp_subscription_id')) {

            Schema::table('subscription_contract_matches', function (Blueprint $table) {
                $table->unsignedBigInteger('whatsapp_subscription_id')
                    ->nullable()
                    ->after('telegram_subscription_id');

                $table->foreign('whatsapp_subscription_id', 'scm_wa_sub_fk')
                    ->references('id')->on('whatsapp_subscriptions')
                    ->cascadeOnDelete();

                $table->index('whatsapp_subscription_id', 'scm_wa_sub_idx');
            });

            // Hacer telegram_subscription_id nullable (antes era required)
            // para que un match pueda pertenecer a Telegram O WhatsApp
            Schema::table('subscription_contract_matches', function (Blueprint $table) {
                $table->unsignedBigInteger('telegram_subscription_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscription_contract_matches', 'whatsapp_subscription_id')) {
            Schema::table('subscription_contract_matches', function (Blueprint $table) {
                $table->dropForeign('scm_wa_sub_fk');
                $table->dropIndex('scm_wa_sub_idx');
                $table->dropColumn('whatsapp_subscription_id');
            });
        }

        Schema::dropIfExists('whatsapp_subscription_keyword');
        Schema::dropIfExists('whatsapp_subscriptions');
    }
};
