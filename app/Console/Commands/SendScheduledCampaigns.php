<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailCampaign;
use App\Models\EmailCampaign;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:send-scheduled';
    protected $description = 'Envía campañas programadas que están pendientes';

    public function handle(): int
    {
        $campaigns = EmailCampaign::where('status', EmailCampaign::STATUS_PROGRAMADA)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => EmailCampaign::STATUS_ENVIANDO]);
            SendEmailCampaign::dispatch($campaign);
            $this->info("Campaña #{$campaign->id} '{$campaign->name}' enviada al queue.");
        }

        $this->info("{$campaigns->count()} campañas procesadas.");

        return 0;
    }
}
