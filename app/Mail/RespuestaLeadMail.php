<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Respuesta comercial al lead que pidió información (desde services@).
 * Para el aviso INTERNO de leads nuevos se usa DemoLeadMail / LeadPlantillaTdrInternoMail.
 */
class RespuestaLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vigilante SEACE — así te avisamos de las licitaciones de tu rubro',
            replyTo: ['services@sunqupacha.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.respuesta-lead',
        );
    }
}
