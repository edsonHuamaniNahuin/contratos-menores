<?php

namespace App\Mail;

use App\Models\PagoYape;
use Illuminate\Mail\Mailable;

class PagoAprobado extends Mailable
{
    public function __construct(public PagoYape $pago) {}

    public function build(): self
    {
        $planLabels = [
            'monthly' => 'Plan Premium Mensual',
            'yearly' => 'Plan Premium Anual',
            'mayores-premium' => 'Premium + Contratos Mayores',
        ];

        $diasVigencia = $this->pago->plan === 'yearly' ? 365 : 30;

        return $this
            ->subject('Pago aprobado — Vigilante SEACE')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.pago-aprobado', [
                'userName' => $this->pago->user->name,
                'planLabel' => $planLabels[$this->pago->plan] ?? $this->pago->plan,
                'monto' => $this->pago->monto,
                'diasVigencia' => $diasVigencia,
                'fechaAprobacion' => now()->format('d/m/Y H:i'),
                'fechaVencimiento' => now()->addDays($diasVigencia)->format('d/m/Y'),
            ]);
    }
}
