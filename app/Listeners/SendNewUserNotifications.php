<?php

namespace App\Listeners;

use App\Mail\WelcomeMail;
use App\Services\AdminTelegramNotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Listener para el evento Registered de Laravel.
 *
 * Ejecuta dos acciones cuando un nuevo usuario se registra:
 * 1. Envía notificación al admin vía Telegram Admin Bot
 * 2. Envía correo de bienvenida al nuevo usuario (con link al manual)
 */
class SendNewUserNotifications
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // 1. Notificar al admin por Telegram
        try {
            $adminNotifier = new AdminTelegramNotificationService();
            $adminNotifier->notifyNewUser($user);
        } catch (\Exception $e) {
            Log::warning('Error al notificar nuevo usuario al admin', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        // 2. Enviar correo de bienvenida
        try {
            Mail::to($user->email)->queue(new WelcomeMail($user));
        } catch (\Exception $e) {
            Log::warning('Error al enviar correo de bienvenida', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
