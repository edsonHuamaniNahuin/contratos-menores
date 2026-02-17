<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class RenewSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Renueva suscripciones que vencen en las próximas 24h cobrando la tarjeta almacenada en Openpay';

    public function handle(): int
    {
        $this->info('Buscando suscripciones por renovar...');

        $service = new SubscriptionService();
        $result  = $service->renewExpiring();

        $renewed = $result['renewed'];
        $failed  = $result['failed'];

        if ($renewed > 0) {
            $this->info("✅ {$renewed} suscripción(es) renovada(s) exitosamente.");
        }

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} renovación(es) fallida(s). Revisar logs.");
        }

        if ($renewed === 0 && $failed === 0) {
            $this->info('No hay suscripciones por renovar en este momento.');
        }

        return Command::SUCCESS;
    }
}
