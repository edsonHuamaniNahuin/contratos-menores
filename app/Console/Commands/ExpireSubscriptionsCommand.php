<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expira suscripciones y trials vencidos, revoca rol premium';

    public function handle(): int
    {
        $service = new SubscriptionService();
        $count   = $service->expireOverdue();

        if ($count > 0) {
            $this->info("✅ {$count} suscripción(es) expirada(s).");
        } else {
            $this->info('No hay suscripciones por expirar.');
        }

        return Command::SUCCESS;
    }
}
