<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public EmailCampaign $campaign) {}

    public function handle(): void
    {
        if ($this->campaign->status !== EmailCampaign::STATUS_ENVIANDO) {
            return;
        }

        $users = $this->resolveRecipients();
        $blacklist = collect($this->campaign->blacklist_ids ?? []);

        $sent = 0;
        foreach ($users as $user) {
            if ($blacklist->contains($user->id)) continue;
            if (empty($user->email)) continue;

            $replacements = [
                '{{ nombre }}' => $user->name ?? $user->email,
                '{{ email }}' => $user->email,
                '{{ plan }}' => match($user->activeSubscription()?->plan) {
                    'monthly' => 'Plan Premium Mensual',
                    'yearly' => 'Plan Premium Anual',
                    'mayores-premium' => 'Premium + Contratos Mayores',
                    'trial' => 'Prueba gratuita',
                    default => 'Plan Gratuito',
                },
                '{{ dias_restantes }}' => (string) ($user->subscriptionDaysLeft() > 0 ? $user->subscriptionDaysLeft() . ' días' : 'Sin suscripción activa'),
            ];

            $subjectUser = strtr($this->campaign->subject, $replacements);
            $bodyUser = strtr($this->campaign->body, $replacements);

            try {
                Mail::html($bodyUser, function ($message) use ($user, $subjectUser) {
                    $message->to($user->email)
                        ->subject($subjectUser)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
                $sent++;
            } catch (\Exception $e) {
                Log::warning('EmailCampaign: error enviando a ' . $user->email, [
                    'campaign_id' => $this->campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Small delay to avoid rate limiting
            if ($sent % 50 === 0) {
                sleep(1);
            }
        }

        $this->campaign->update([
            'status' => EmailCampaign::STATUS_ENVIADA,
            'sent_at' => now(),
            'total_sent' => $sent,
            'total_recipients' => $users->count(),
        ]);

        Log::info('EmailCampaign: campaña enviada', [
            'campaign_id' => $this->campaign->id,
            'sent' => $sent,
            'total' => $users->count(),
        ]);
    }

    protected function resolveRecipients()
    {
        $query = User::query();

        match ($this->campaign->filtro_tipo) {
            EmailCampaign::FILTRO_TODOS => null,
            EmailCampaign::FILTRO_PREMIUM => $query->whereHas('roles', fn($q) =>
                $q->whereIn('slug', ['proveedor-premium', 'proveedor-premium-total'])
            ),
            EmailCampaign::FILTRO_NO_PREMIUM => $query->whereDoesntHave('roles', fn($q) =>
                $q->whereIn('slug', ['proveedor-premium', 'proveedor-premium-total', 'admin'])
            ),
            EmailCampaign::FILTRO_ESPECIFICO => $query->whereIn('id', (array) ($this->campaign->filtro_ids ?? [])),
            EmailCampaign::FILTRO_WSP_VENTANA => $query->whereHas('whatsappSubscriptions', function ($q) {
                $q->where('activo', true)
                    ->where(function ($w) {
                        // Ventana cerrada:
                        // A) Meta rechazó una entrega (131047) y no hubo interacción posterior, o
                        // B) la última interacción del usuario superó las 24 horas.
                        $w->where(function ($a) {
                            $a->whereNotNull('ultima_entrega_fallida_at')
                                ->where(function ($a2) {
                                    $a2->whereNull('ultima_interaccion_at')
                                        ->orWhereColumn('ultima_entrega_fallida_at', '>', 'ultima_interaccion_at');
                                });
                        })->orWhere(function ($b) {
                            $b->whereNotNull('ultima_interaccion_at')
                                ->where('ultima_interaccion_at', '<', now()->subHours(24));
                        });
                    });
            }),
            default => null,
        };

        return $query->get();
    }
}
