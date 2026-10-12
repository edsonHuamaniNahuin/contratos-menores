<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo hacia el VISITANTE que descargó el modelo de TDR (lead magnet).
 * Se envía al correo del lead; si responde, la respuesta va a services@.
 * El aviso interno para el equipo es `LeadPlantillaTdrInternoMail`.
 */
class TdrPlantillaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu modelo de TDR está listo — Vigilante SEACE',
            replyTo: ['services@sunqupacha.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tdr-plantilla',
        );
    }
}
