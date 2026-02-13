<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notificación personalizada de restablecimiento de contraseña.
 * Extiende la de Laravel para mantener el token pero con diseño en español.
 */
class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->subject('Restablecer contraseña — Vigilante SEACE')
            ->greeting('¡Hola!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en Vigilante SEACE.')
            ->action('Restablecer mi contraseña', $url)
            ->line('Este enlace expirará en ' . config('auth.passwords.users.expire', 60) . ' minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña seguirá siendo la misma.')
            ->salutation('— Equipo Vigilante SEACE');
    }
}
