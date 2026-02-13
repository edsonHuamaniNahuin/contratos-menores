<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificación personalizada de verificación de correo.
 * Extiende la de Laravel para mantener la URL firmada pero con diseño propio.
 */
class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->subject('Verifica tu correo — Vigilante SEACE')
            ->greeting('¡Bienvenido a Vigilante SEACE!')
            ->line('Gracias por registrarte. Para activar tu cuenta, confirma tu dirección de correo electrónico haciendo clic en el botón de abajo.')
            ->action('Verificar mi correo', $url)
            ->line('Este enlace expirará en 60 minutos.')
            ->line('Si no creaste una cuenta en nuestra plataforma, puedes ignorar este mensaje.')
            ->salutation('— Equipo Vigilante SEACE');
    }
}
