<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación INTERNA a services@sunqupacha.com cuando alguien descarga
 * el modelo de TDR (lead magnet /plantillas-tdr).
 *
 * Ojo: el correo hacia el VISITANTE es `TdrPlantillaMail` (con su nombre y
 * el botón de descarga). Este es solo el aviso interno para seguimiento.
 */
class LeadPlantillaTdrInternoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public ?string $origen = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Plantilla TDR] Nuevo lead: {$this->nombre}",
            replyTo: [$this->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-plantilla-tdr-interno',
        );
    }
}
