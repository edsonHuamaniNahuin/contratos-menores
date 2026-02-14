<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoProcesoSeace extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $contrato,
        public string $seguimientoUrl,
        public array $matchedKeywords = [],
    ) {
    }

    public function envelope(): Envelope
    {
        $codigo = $this->contrato['desContratacion'] ?? 'Nuevo proceso';

        return new Envelope(
            subject: "🔔 Nuevo proceso SEACE: {$codigo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nuevo-proceso-seace',
        );
    }
}
