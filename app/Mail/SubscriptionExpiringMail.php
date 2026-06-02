<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Subscription $subscription,
        public int $daysRemaining,
        public bool $willAutoRenew,
        public string $type, // 'trial_ending' | 'subscription_expiring' | 'renewal_upcoming'
    ) {
    }

    public function envelope(): Envelope
    {
        return match ($this->type) {
            'trial_ending' => new Envelope(
                subject: '⏰ Tu prueba gratuita de Vigilante SEACE está por terminar',
            ),
            'renewal_upcoming' => new Envelope(
                subject: '🔔 Próximo cobro automático — Vigilante SEACE',
            ),
            default => new Envelope(
                subject: '⏰ Tu suscripción de Vigilante SEACE está por vencer',
            ),
        };
    }

    public function content(): Content
    {
        $planLabel = match ($this->subscription->plan) {
            Subscription::PLAN_TRIAL => 'Prueba gratuita (15 días)',
            Subscription::PLAN_MONTHLY => 'Plan Mensual — S/ 49',
            Subscription::PLAN_YEARLY => 'Plan Anual — S/ 470',
            default => ucfirst($this->subscription->plan),
        };

        return new Content(
            view: 'emails.subscription-expiring',
            with: [
                'userName'      => $this->user->name ?? $this->user->email,
                'planLabel'     => $planLabel,
                'daysRemaining' => $this->daysRemaining,
                'endsAt'        => $this->subscription->ends_at?->format('d/m/Y'),
                'willAutoRenew' => $this->willAutoRenew,
                'type'          => $this->type,
                'miSuscripcionUrl' => route('mi.suscripcion'),
                'planesUrl'     => route('planes'),
                'homeUrl'       => route('home'),
            ],
        );
    }
}
