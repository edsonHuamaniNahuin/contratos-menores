<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lead magnet instantáneo: reporte semanal de convocatorias.
 * Se envía automáticamente al registrarse un lead (la recompensa prometida).
 */
class ReporteConvocatoriasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre = '',
        public array $convocatorias = [],
        public string $fechaInicio = '',
        public string $fechaFin = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Tu reporte de convocatorias de esta semana — Vigilante SEACE',
            replyTo: ['services@sunqupacha.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-convocatorias',
        );
    }
}
