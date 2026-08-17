<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaAdjudicacion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $proceso,
    ) {
    }

    public function envelope(): Envelope
    {
        $codigo = $this->proceso['nomenclatura'] ?? 'Proceso';

        return new Envelope(
            subject: "🏆 BUENA PRO: {$codigo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-adjudicacion',
        );
    }
}
