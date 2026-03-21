<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla de perfiles de suscriptor (fuente única de company_copy + keywords)
        Schema::create('subscriber_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('company_copy')->nullable();
            $table->timestamps();
        });

        // 2. Crear tabla pivot unificada
        Schema::create('subscriber_profile_keyword', function (Blueprint $table) {
            $table->foreignId('subscriber_profile_id')->constrained('subscriber_profiles')->cascadeOnDelete();
            $table->foreignId('notification_keyword_id')->constrained('notification_keywords')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['subscriber_profile_id', 'notification_keyword_id'], 'spk_primary');
        });

        // 3. Migrar datos existentes: crear perfiles desde telegram_subscriptions
        DB::statement("
            INSERT INTO subscriber_profiles (user_id, company_copy, created_at, updated_at)
            SELECT user_id, company_copy, NOW(), NOW()
            FROM telegram_subscriptions
            WHERE user_id IS NOT NULL
            ON DUPLICATE KEY UPDATE
                company_copy = COALESCE(subscriber_profiles.company_copy, VALUES(company_copy))
        ");

        // 4. Migrar datos desde whatsapp_subscriptions (solo si no existía en telegram)
        DB::statement("
            INSERT INTO subscriber_profiles (user_id, company_copy, created_at, updated_at)
            SELECT ws.user_id, ws.company_copy, NOW(), NOW()
            FROM whatsapp_subscriptions ws
            WHERE ws.user_id IS NOT NULL
            AND ws.user_id NOT IN (SELECT user_id FROM subscriber_profiles)
            ON DUPLICATE KEY UPDATE
                company_copy = COALESCE(subscriber_profiles.company_copy, VALUES(company_copy))
        ");

        // 5. Migrar keywords de telegram pivot
        DB::statement("
            INSERT IGNORE INTO subscriber_profile_keyword (subscriber_profile_id, notification_keyword_id, created_at, updated_at)
            SELECT sp.id, nks.notification_keyword_id, NOW(), NOW()
            FROM notification_keyword_subscription nks
            INNER JOIN telegram_subscriptions ts ON ts.id = nks.telegram_subscription_id
            INNER JOIN subscriber_profiles sp ON sp.user_id = ts.user_id
        ");

        // 6. Migrar keywords de whatsapp pivot
        DB::statement("
            INSERT IGNORE INTO subscriber_profile_keyword (subscriber_profile_id, notification_keyword_id, created_at, updated_at)
            SELECT sp.id, wsk.notification_keyword_id, NOW(), NOW()
            FROM whatsapp_subscription_keyword wsk
            INNER JOIN whatsapp_subscriptions ws ON ws.id = wsk.whatsapp_subscription_id
            INNER JOIN subscriber_profiles sp ON sp.user_id = ws.user_id
        ");

        // 7. Migrar keywords de email pivot
        DB::statement("
            INSERT IGNORE INTO subscriber_profile_keyword (subscriber_profile_id, notification_keyword_id, created_at, updated_at)
            SELECT sp.id, esk.notification_keyword_id, NOW(), NOW()
            FROM email_subscription_keyword esk
            INNER JOIN email_subscriptions es ON es.id = esk.email_subscription_id
            INNER JOIN subscriber_profiles sp ON sp.user_id = es.user_id
        ");

        // 8. Para emails que no tenían perfil (sin telegram ni whatsapp), crear perfil
        DB::statement("
            INSERT INTO subscriber_profiles (user_id, company_copy, created_at, updated_at)
            SELECT es.user_id, NULL, NOW(), NOW()
            FROM email_subscriptions es
            WHERE es.user_id IS NOT NULL
            AND es.user_id NOT IN (SELECT user_id FROM subscriber_profiles)
        ");

        // 9. Migrar keywords de email para perfiles recién creados
        DB::statement("
            INSERT IGNORE INTO subscriber_profile_keyword (subscriber_profile_id, notification_keyword_id, created_at, updated_at)
            SELECT sp.id, esk.notification_keyword_id, NOW(), NOW()
            FROM email_subscription_keyword esk
            INNER JOIN email_subscriptions es ON es.id = esk.email_subscription_id
            INNER JOIN subscriber_profiles sp ON sp.user_id = es.user_id
            WHERE (sp.id, esk.notification_keyword_id) NOT IN (
                SELECT subscriber_profile_id, notification_keyword_id FROM subscriber_profile_keyword
            )
        ");

        // 10. Eliminar columnas y tablas duplicadas
        Schema::table('telegram_subscriptions', function (Blueprint $table) {
            $table->dropColumn('company_copy');
        });

        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->dropColumn('company_copy');
        });

        // 11. Eliminar tablas pivot viejas
        Schema::dropIfExists('notification_keyword_subscription');
        Schema::dropIfExists('whatsapp_subscription_keyword');
        Schema::dropIfExists('email_subscription_keyword');
    }

    public function down(): void
    {
        // Restaurar columnas
        Schema::table('telegram_subscriptions', function (Blueprint $table) {
            $table->text('company_copy')->nullable()->after('filtros');
        });

        Schema::table('whatsapp_subscriptions', function (Blueprint $table) {
            $table->text('company_copy')->nullable()->after('filtros');
        });

        // Restaurar tablas pivot
        Schema::create('notification_keyword_subscription', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_keyword_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['telegram_subscription_id', 'notification_keyword_id'], 'nks_unique');
        });

        Schema::create('whatsapp_subscription_keyword', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_keyword_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['whatsapp_subscription_id', 'notification_keyword_id'], 'wsk_unique');
        });

        Schema::create('email_subscription_keyword', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_keyword_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['email_subscription_id', 'notification_keyword_id'], 'esk_unique');
        });

        // Migrar datos de vuelta
        DB::statement("
            UPDATE telegram_subscriptions ts
            INNER JOIN subscriber_profiles sp ON sp.user_id = ts.user_id
            SET ts.company_copy = sp.company_copy
        ");

        DB::statement("
            UPDATE whatsapp_subscriptions ws
            INNER JOIN subscriber_profiles sp ON sp.user_id = ws.user_id
            SET ws.company_copy = sp.company_copy
        ");

        // Eliminar tablas normalizadas
        Schema::dropIfExists('subscriber_profile_keyword');
        Schema::dropIfExists('subscriber_profiles');
    }
};
