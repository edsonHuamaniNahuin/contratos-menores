<?php

namespace App\Mail;

use App\Models\PagoYape;
use Illuminate\Mail\Mailable;

class PagoRechazado extends Mailable
{
    public function __construct(public PagoYape $pago, public string $motivo) {}

    public function build(): self
    {
        return $this
            ->subject('Pago rechazado — Vigilante SEACE')
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->markdown('emails.pago-rechazado', [
                'userName' => $this->pago->user->name,
                'monto' => $this->pago->monto,
                'motivo' => $this->motivo,
                'fechaRechazo' => now()->format('d/m/Y H:i'),
            ]);
    }
}
