<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de notificaciones Telegram para el administrador.
 *
 * Envía alertas al admin cuando:
 * - Se registra un nuevo usuario
 * - Un usuario compra/activa una suscripción premium
 *
 * Usa un bot de Telegram separado configurado en:
 *   TELEGRAM_ADMIN_BOT_TOKEN / TELEGRAM_ADMIN_CHAT_ID
 */
class AdminTelegramNotificationService
{
    protected string $botToken;
    protected string $apiBase;
    protected string $chatId;
    protected bool $enabled;

    public function __construct()
    {
        $this->botToken = trim((string) config('services.telegram_admin.bot_token', ''));
        $this->apiBase  = rtrim((string) config('services.telegram_admin.api_base', config('services.telegram.api_base', '')), '/');
        $this->chatId   = trim((string) config('services.telegram_admin.chat_id', ''));
        $this->enabled  = $this->botToken !== '' && $this->apiBase !== '' && $this->chatId !== '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Notifica al admin sobre un nuevo usuario registrado.
     */
    public function notifyNewUser(User $user): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $tipo = $user->account_type === 'empresa' ? '🏢 Empresa' : '👤 Personal';
        $ruc  = $user->ruc ? "\n📋 *RUC:* `{$user->ruc}`" : '';
        $razon = $user->razon_social ? "\n🏛️ *Razón Social:* {$user->razon_social}" : '';

        $mensaje = "👤 *NUEVO USUARIO REGISTRADO*\n\n"
                 . "📧 *Email:* {$user->email}\n"
                 . "📝 *Nombre:* {$user->name}\n"
                 . "🏷️ *Tipo:* {$tipo}"
                 . $ruc
                 . $razon
                 . "\n📅 *Fecha:* " . now()->format('d/m/Y H:i') . "\n\n"
                 . "📊 *Total usuarios:* " . User::count();

        return $this->send($mensaje);
    }

    /**
     * Notifica al admin sobre una nueva suscripción premium.
     */
    public function notifyNewSubscription(User $user, Subscription $subscription): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $planLabel = match ($subscription->plan) {
            'monthly' => '📅 Mensual (S/ 49.00)',
            'yearly'  => '📆 Anual (S/ 470.00)',
            'trial'   => '🆓 Trial 15 días',
            default   => $subscription->plan,
        };

        $gateway = $subscription->gateway_provider
            ? strtoupper($subscription->gateway_provider)
            : 'N/A';

        $mensaje = "💰 *NUEVA SUSCRIPCIÓN PREMIUM*\n\n"
                 . "📧 *Usuario:* {$user->email}\n"
                 . "📝 *Nombre:* {$user->name}\n"
                 . "🎯 *Plan:* {$planLabel}\n"
                 . "💳 *Pasarela:* {$gateway}\n"
                 . "💵 *Monto:* S/ " . number_format((float) $subscription->amount, 2) . "\n"
                 . "📅 *Inicio:* " . ($subscription->starts_at?->format('d/m/Y') ?? 'N/A') . "\n"
                 . "⏰ *Vence:* " . ($subscription->ends_at?->format('d/m/Y') ?? 'N/A') . "\n"
                 . "🔄 *Cargo ID:* `" . ($subscription->gateway_charge_id ?? 'N/A') . "`\n\n"
                 . "📅 *Fecha:* " . now()->format('d/m/Y H:i');

        return $this->send($mensaje);
    }

    /**
     * Envía un mensaje al chat del admin.
     */
    protected function send(string $message): bool
    {
        try {
            $url = sprintf('%s/bot%s/sendMessage', $this->apiBase, $this->botToken);

            $response = Http::timeout(10)->post($url, [
                'chat_id'    => $this->chatId,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ]);

            if (!$response->successful()) {
                Log::warning('AdminTelegram: Error al enviar mensaje', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('AdminTelegram: Excepción al enviar mensaje', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
