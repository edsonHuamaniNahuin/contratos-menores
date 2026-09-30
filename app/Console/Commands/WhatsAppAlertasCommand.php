<?php

namespace App\Console\Commands;

use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

/**
 * Switch maestro de alertas por WhatsApp.
 *
 * `php artisan whatsapp:alertas off` pausa TODOS los envíos de WhatsApp en la
 * app de forma instantánea: crea un archivo centinela en storage/app que se
 * consulta en cada envío (sobrevive a `optimize:clear` y no requiere reiniciar
 * colas ni bots). `on` lo elimina y todo vuelve a funcionar.
 *
 * También se puede desactivar por configuración: WHATSAPP_ALERTAS_ACTIVAS=false
 * (requiere optimize:clear + reinicio de servicios).
 */
class WhatsAppAlertasCommand extends Command
{
    protected $signature = 'whatsapp:alertas {estado=status : on | off | status}';

    protected $description = 'Enciende/apaga todas las alertas por WhatsApp (switch maestro instantáneo)';

    public function handle(WhatsAppNotificationService $servicio): int
    {
        $estado = strtolower((string) $this->argument('estado'));

        switch ($estado) {
            case 'off':
                $servicio->pausarAlertas();
                $this->warn('🔕 Alertas por WhatsApp PAUSADAS en toda la app.');
                $this->line('   Centinela: ' . WhatsAppNotificationService::rutaPausa());
                $this->line('   Reactivar con: php artisan whatsapp:alertas on');
                break;

            case 'on':
                $servicio->reanudarAlertas();
                $this->info('🔔 Alertas por WhatsApp REACTIVADAS.');
                break;

            case 'status':
            default:
                $env = config('services.whatsapp.alertas_activas', true) ? 'true' : 'false';
                $local = $servicio->alertasPausadasLocalmente();
                $efectivo = $servicio->alertasActivas();

                $this->line('WHATSAPP_ALERTAS_ACTIVAS (env/config): ' . $env);
                $this->line('Pausa local (whatsapp:alertas off): ' . ($local
                    ? 'SÍ — desde ' . trim((string) @file_get_contents(WhatsAppNotificationService::rutaPausa()))
                    : 'no'));
                $this->line('Credenciales WhatsApp configuradas: ' . ($servicio->isEnabled() ? 'sí' : 'no'));
                $this->line('ESTADO EFECTIVO: ' . ($efectivo ? '✅ ACTIVAS' : '🔕 PAUSADAS'));
                break;
        }

        return self::SUCCESS;
    }
}
