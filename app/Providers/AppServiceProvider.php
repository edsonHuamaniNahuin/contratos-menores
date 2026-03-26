<?php

namespace App\Providers;

use App\Contracts\NotificationTrackerContract;
use App\Listeners\SendNewUserNotifications;
use App\Models\SystemSetting;
use App\Services\ProcessNotificationTracker;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding: NotificationTrackerContract → ProcessNotificationTracker (DIP)
        $this->app->bind(NotificationTrackerContract::class, ProcessNotificationTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Cargar system_settings de BD y sobrescribir config de servicios ──
        $this->loadSystemSettingsIntoConfig();

        // ── Evento: nuevo usuario registrado → notificar admin + correo bienvenida ──
        Event::listen(Registered::class, SendNewUserNotifications::class);

        // ── Rate Limiter para rutas API (requerido por throttleApi()) ──
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        Gate::define('view-tdr-repository', fn ($user) => $user->hasPermission('view-tdr-repository'));
        Gate::define('view-configuracion', fn ($user) => $user->hasPermission('view-configuracion'));
        Gate::define('view-buscador-publico', fn ($user) => $user->hasPermission('view-buscador-publico'));
        Gate::define('view-cuentas', fn ($user) => $user->hasPermission('view-cuentas'));
        Gate::define('view-prueba-endpoints', fn ($user) => $user->hasPermission('view-prueba-endpoints'));
        Gate::define('view-configuracion-alertas', fn ($user) => $user->hasPermission('view-configuracion-alertas'));
        Gate::define('manage-roles-permissions', fn ($user) => $user->hasPermission('manage-roles-permissions'));
        Gate::define('import-tdr', fn ($user) => $user->hasPermission('import-tdr'));
        Gate::define('analyze-tdr', fn ($user) => $user->hasPermission('analyze-tdr'));
        Gate::define('follow-contracts', fn ($user) => $user->hasPermission('follow-contracts'));
        Gate::define('manage-subscriptions', fn ($user) => $user->hasPermission('manage-subscriptions'));
        Gate::define('view-mis-procesos', fn ($user) => $user->hasPermission('view-mis-procesos'));
        Gate::define('cotizar-seace', fn ($user) => $user->hasPermission('cotizar-seace'));
        Gate::define('create-proforma', fn ($user) => $user->hasPermission('create-proforma'));
    }

    /**
     * Carga configuraciones de system_settings (BD) y sobrescribe config() de servicios.
     * Así todos los servicios que leen config('services.telegram.*') obtienen el valor de BD.
     */
    private function loadSystemSettingsIntoConfig(): void
    {
        try {
            if (!Schema::hasTable('system_settings')) {
                return;
            }

            $map = [
                'telegram_bot_token'       => 'services.telegram.bot_token',
                'telegram_chat_id'         => 'services.telegram.chat_id',
                'telegram_admin_bot_token' => 'services.telegram_admin.bot_token',
                'telegram_admin_chat_id'   => 'services.telegram_admin.chat_id',
                'analizador_tdr_url'       => 'services.analizador_tdr.url',
                'analizador_tdr_enabled'   => 'services.analizador_tdr.enabled',
                'whatsapp_bot_token'       => 'services.whatsapp.bot_token',
                'whatsapp_group_id'        => 'services.whatsapp.group_id',
                'payment_gateway'          => 'services.payment_gateway',
                'mercadopago_access_token'  => 'services.mercadopago.access_token',
                'mercadopago_public_key'    => 'services.mercadopago.public_key',
                'mercadopago_webhook_secret' => 'services.mercadopago.webhook_secret',
                'openpay_merchant_id'      => 'services.openpay.merchant_id',
                'openpay_private_key'      => 'services.openpay.private_key',
                'openpay_public_key'       => 'services.openpay.public_key',
                'openpay_production'       => 'services.openpay.production',
            ];

            $settings = Cache::remember('system_settings_all', 3600, function () {
                return SystemSetting::pluck('value', 'key')->toArray();
            });

            foreach ($map as $dbKey => $configKey) {
                if (isset($settings[$dbKey]) && $settings[$dbKey] !== '') {
                    config([$configKey => $settings[$dbKey]]);
                }
            }
        } catch (\Exception $e) {
            // Si la BD no está disponible, usar los valores del .env/config
        }
    }
}
