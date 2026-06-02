<?php

namespace App\Services;

use App\Mail\SubscriptionExpiringMail;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionAlertService
{
    /**
     * Envia alertas por email a usuarios cuya suscripción está por vencer.
     *
     * Alertas:
     * - Trial: 3 días antes del vencimiento (advirtiendo del cobro automático)
     * - Trial: 1 día antes del vencimiento (recordatorio urgente)
     * - Mensual/Anual con auto_renew: 3 días antes (aviso de próximo cobro)
     * - Mensual/Anual sin auto_renew: 3 días antes (aviso de expiración)
     * - Mensual/Anual: 1 día antes (recordatorio urgente)
     *
     * @return array{sent: int, failed: int}
     */
    public function sendExpiryAlerts(): array
    {
        $sent   = 0;
        $failed = 0;

        // ─── Trial que vence en 3 días ───
        $sent   += $this->notifyTrialEnding(3);
        $failed += 0;

        // ─── Trial que vence en 1 día ───
        $sent   += $this->notifyTrialEnding(1);
        $failed += 0;

        // ─── Suscripciones que vencen en 3 días ───
        $sent   += $this->notifySubscriptionExpiring(3);

        // ─── Suscripciones que vencen en 1 día ───
        $sent   += $this->notifySubscriptionExpiring(1);

        Log::info('Alertas de suscripción enviadas', ['sent' => $sent, 'failed' => $failed]);

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function notifyTrialEnding(int $daysBefore): int
    {
        $targetDate = Carbon::now()->addDays($daysBefore)->startOfDay();

        $subscriptions = Subscription::where('plan', Subscription::PLAN_TRIAL)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('trial_ends_at', '=', $targetDate->toDateString())
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($subscriptions as $sub) {
            if (!$sub->user || !$sub->user->email) {
                continue;
            }

            try {
                Mail::to($sub->user)->send(new SubscriptionExpiringMail(
                    user: $sub->user,
                    subscription: $sub,
                    daysRemaining: $daysBefore,
                    willAutoRenew: (bool) $sub->auto_renew,
                    type: 'trial_ending',
                ));
                $sent++;

                Log::info('Alerta trial enviada', [
                    'user_id'   => $sub->user_id,
                    'email'     => $sub->user->email,
                    'days_left' => $daysBefore,
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando alerta trial', [
                    'user_id' => $sub->user_id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function notifySubscriptionExpiring(int $daysBefore): int
    {
        $targetDate = Carbon::now()->addDays($daysBefore)->startOfDay();

        $subscriptions = Subscription::whereIn('plan', [Subscription::PLAN_MONTHLY, Subscription::PLAN_YEARLY])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereDate('ends_at', '=', $targetDate->toDateString())
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($subscriptions as $sub) {
            if (!$sub->user || !$sub->user->email) {
                continue;
            }

            $type = $sub->auto_renew ? 'renewal_upcoming' : 'subscription_expiring';

            try {
                Mail::to($sub->user)->send(new SubscriptionExpiringMail(
                    user: $sub->user,
                    subscription: $sub,
                    daysRemaining: $daysBefore,
                    willAutoRenew: (bool) $sub->auto_renew,
                    type: $type,
                ));
                $sent++;

                Log::info('Alerta suscripción enviada', [
                    'user_id'   => $sub->user_id,
                    'email'     => $sub->user->email,
                    'days_left' => $daysBefore,
                    'type'      => $type,
                ]);
            } catch (\Exception $e) {
                Log::error('Error enviando alerta suscripción', [
                    'user_id' => $sub->user_id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
