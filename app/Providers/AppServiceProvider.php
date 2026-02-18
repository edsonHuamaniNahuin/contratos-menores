<?php

namespace App\Providers;

use App\Contracts\NotificationTrackerContract;
use App\Services\ProcessNotificationTracker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // ── Rate Limiter para rutas API (requerido por throttleApi()) ──
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        Gate::define('view-tdr-repository', fn ($user) => $user->hasPermission('view-tdr-repository'));
        Gate::define('view-configuracion', fn ($user) => $user->hasPermission('view-configuracion'));
        Gate::define('view-buscador-publico', fn ($user) => $user->hasPermission('view-buscador-publico'));
        Gate::define('view-cuentas', fn ($user) => $user->hasPermission('view-cuentas'));
        Gate::define('view-prueba-endpoints', fn ($user) => $user->hasPermission('view-prueba-endpoints'));
        Gate::define('view-suscriptores', fn ($user) => $user->hasPermission('view-suscriptores'));
        Gate::define('manage-roles-permissions', fn ($user) => $user->hasPermission('manage-roles-permissions'));
        Gate::define('import-tdr', fn ($user) => $user->hasPermission('import-tdr'));
        Gate::define('analyze-tdr', fn ($user) => $user->hasPermission('analyze-tdr'));
        Gate::define('follow-contracts', fn ($user) => $user->hasPermission('follow-contracts'));
        Gate::define('manage-subscriptions', fn ($user) => $user->hasPermission('manage-subscriptions'));
        Gate::define('view-mis-procesos', fn ($user) => $user->hasPermission('view-mis-procesos'));
    }
}
