<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAlertService;
use Illuminate\Console\Command;

class SubscriptionAlertsCommand extends Command
{
    protected $signature = 'subscriptions:alerts';

    protected $description = 'Envía alertas por email a usuarios con suscripción/trial próximo a vencer';

    public function handle(): int
    {
        $this->info('Enviando alertas de suscripción...');

        $service = new SubscriptionAlertService();
        $result  = $service->sendExpiryAlerts();

        $sent   = $result['sent'];
        $failed = $result['failed'];

        if ($sent > 0) {
            $this->info("✅ {$sent} alerta(s) enviada(s).");
        }

        if ($failed > 0) {
            $this->warn("⚠️ {$failed} alerta(s) fallida(s). Revisar logs.");
        }

        if ($sent === 0 && $failed === 0) {
            $this->info('No hay alertas pendientes en este momento.');
        }

        return Command::SUCCESS;
    }
}
