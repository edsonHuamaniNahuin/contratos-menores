<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Secuencia de onboarding de la prueba gratuita (15 días).
 *
 * Pasos: d1, d3, d7, d12, d15 (vencimiento) y "vencido" (win-back de
 * pruebas expiradas que nunca pagaron).
 */
class OnboardingTrialMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $paso,
    ) {}

    public function envelope(): Envelope
    {
        $asuntos = [
            'd1' => 'Tus primeros pasos en Vigilante SEACE (2 minutos)',
            'd3' => 'El análisis de TDR con IA que te evita postular en vano',
            'd7' => 'Tu prueba va por la mitad — ¿te mostramos el sistema en vivo?',
            'd12' => 'Tu prueba vence en 3 días: así mantienes todo activo',
            'd15' => 'Hoy termina tu prueba de Vigilante SEACE',
            'vencido' => 'Tu cuenta de Vigilante SEACE te está esperando',
        ];

        return new Envelope(
            subject: $asuntos[$this->paso] ?? 'Vigilante SEACE',
            replyTo: ['services@sunqupacha.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-trial',
        );
    }
}
