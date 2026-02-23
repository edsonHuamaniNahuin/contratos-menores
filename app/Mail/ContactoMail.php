<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para el formulario de contacto.
 *
 * Envía los datos del visitante a services@sunqupacha.com.
 */
class ContactoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $asunto,
        public string $mensajeTexto,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Contacto Web] {$this->asunto}",
            replyTo: [$this->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contacto',
        );
    }
}
