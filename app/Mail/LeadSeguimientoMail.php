<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Nurture automático: se envía ~24h después del lead si nadie lo contactó.
 * Protege el lead de la "última milla humana" sin coste adicional.
 */
class LeadSeguimientoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¿Ya viste las licitaciones de tu rubro? — Vigilante SEACE',
            replyTo: ['services@sunqupacha.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-seguimiento',
        );
    }
}
