<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminTelegramNotificationService;
use App\Services\Payments\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /* ────────────────────────────────
     |  Trial
     |──────────────────────────────── */

    /**
     * Verifica si el usuario puede iniciar un trial.
     */
    public function canStartTrial(User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        return !$user->subscriptions()
            ->where('plan', Subscription::PLAN_TRIAL)
            ->exists();
    }

    /**
     * Activa trial de 15 días SIN tarjeta (MercadoPago).
     */
    public function startTrial(User $user, string $gatewayProvider = 'mercadopago'): Subscription
    {
        if (!$this->canStartTrial($user)) {
            throw new \RuntimeException('El usuario ya utilizó su periodo de prueba o es administrador.');
        }

        $now    = Carbon::now();
        $endsAt = $now->copy()->addDays(Subscription::TRIAL_DAYS);

        return DB::transaction(function () use ($user, $now, $endsAt, $gatewayProvider) {
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscription = $user->subscriptions()->create([
                'plan'             => Subscription::PLAN_TRIAL,
                'status'           => Subscription::STATUS_ACTIVE,
                'starts_at'        => $now,
                'ends_at'          => $endsAt,
                'trial_ends_at'    => $endsAt,
                'gateway_provider' => $gatewayProvider,
                'payment_method'   => 'none',
                'amount'           => 0,
                'currency'         => 'PEN',
            ]);

            $this->assignPremiumRole($user);

            Log::info('Trial sin tarjeta activado', [
                'user_id'  => $user->id,
                'ends_at'  => $endsAt->toDateTimeString(),
                'gateway'  => $gatewayProvider,
            ]);

            // Notificar al admin por Telegram
            $this->notifyAdminNewSubscription($user, $subscription);

            return $subscription;
        });
    }

    /**
     * Activa trial de 15 días con tarjeta registrada en la pasarela.
     */
    public function startTrialWithCard(
        User $user,
        string $customerId,
        string $cardId,
        string $gatewayProvider = 'openpay',
    ): Subscription {
        if (!$this->canStartTrial($user)) {
            throw new \RuntimeException('El usuario ya utilizó su periodo de prueba o es administrador.');
        }

        $now    = Carbon::now();
        $endsAt = $now->copy()->addDays(Subscription::TRIAL_DAYS);

        return DB::transaction(function () use ($user, $now, $endsAt, $customerId, $cardId, $gatewayProvider) {
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscriptionData = [
                'plan'                => Subscription::PLAN_TRIAL,
                'status'              => Subscription::STATUS_ACTIVE,
                'starts_at'           => $now,
                'ends_at'             => $endsAt,
                'trial_ends_at'       => $endsAt,
                'gateway_provider'    => $gatewayProvider,
                'gateway_customer_id' => $customerId,
                'gateway_card_id'     => $cardId,
                'payment_method'      => 'card',
                'amount'              => 0,
                'currency'            => 'PEN',
            ];

            // Compatibilidad: también escribir en campos openpay_* si es openpay
            if ($gatewayProvider === 'openpay') {
                $subscriptionData['openpay_customer_id'] = $customerId;
                $subscriptionData['openpay_card_id']     = $cardId;
            }

            $subscription = $user->subscriptions()->create($subscriptionData);

            $this->assignPremiumRole($user);

            Log::info('Trial con tarjeta activado', [
                'user_id'     => $user->id,
                'ends_at'     => $endsAt->toDateTimeString(),
                'customer_id' => $customerId,
                'card_id'     => $cardId,
                'gateway'     => $gatewayProvider,
            ]);

            // Notificar al admin por Telegram
            $this->notifyAdminNewSubscription($user, $subscription);

            return $subscription;
        });
    }

    /* ────────────────────────────────
     |  Paid subscription
     |──────────────────────────────── */

    /**
     * Activa suscripción paga (después de cargo exitoso en la pasarela).
     */
    public function activatePaid(User $user, string $plan, array $paymentData): Subscription
    {
        if ($user->isAdmin()) {
            throw new \RuntimeException('Los administradores están excluidos del sistema de suscripciones.');
        }

        $duration = $plan === Subscription::PLAN_YEARLY ? 365 : 30;
        $now      = Carbon::now();
        $endsAt   = $now->copy()->addDays($duration);

        $gatewayProvider = $paymentData['gateway_provider'] ?? config('services.payment_gateway', 'mercadopago');

        return DB::transaction(function () use ($user, $plan, $now, $endsAt, $paymentData, $gatewayProvider) {
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscriptionData = [
                'plan'                => $plan,
                'status'              => Subscription::STATUS_ACTIVE,
                'starts_at'           => $now,
                'ends_at'             => $endsAt,
                'gateway_provider'    => $gatewayProvider,
                'gateway_charge_id'   => $paymentData['charge_id'] ?? null,
                'gateway_customer_id' => $paymentData['customer_id'] ?? null,
                'gateway_card_id'     => $paymentData['card_id'] ?? null,
                'payment_method'      => $paymentData['payment_method'] ?? 'card',
                'amount'              => $paymentData['amount'] ?? 0,
                'currency'            => $paymentData['currency'] ?? 'PEN',
                'metadata'            => $paymentData['metadata'] ?? null,
            ];

            // Compatibilidad: también escribir en campos openpay_* si es openpay
            if ($gatewayProvider === 'openpay') {
                $subscriptionData['openpay_charge_id']   = $paymentData['charge_id'] ?? null;
                $subscriptionData['openpay_customer_id'] = $paymentData['customer_id'] ?? null;
                $subscriptionData['openpay_card_id']     = $paymentData['card_id'] ?? null;
            }

            $subscription = $user->subscriptions()->create($subscriptionData);

            $this->assignPremiumRole($user);

            Log::info('Suscripción premium activada', [
                'user_id'    => $user->id,
                'plan'       => $plan,
                'ends_at'    => $endsAt->toDateTimeString(),
                'charge_id'  => $paymentData['charge_id'] ?? null,
                'gateway'    => $gatewayProvider,
            ]);

            // Notificar al admin por Telegram
            $this->notifyAdminNewSubscription($user, $subscription);

            return $subscription;
        });
    }

    /* ────────────────────────────────
     |  Cancellation & Expiration
     |──────────────────────────────── */

    /**
     * Cancela la suscripción activa del usuario.
     */
    public function cancel(User $user): bool
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return false;
        }

        return DB::transaction(function () use ($user, $subscription) {
            $subscription->cancel();
            $this->revokePremiumRole($user);

            Log::info('Suscripción cancelada', ['user_id' => $user->id]);
            return true;
        });
    }

    /**
     * Expira suscripciones vencidas (para Scheduler/Job).
     * Excluye usuarios con rol admin.
     */
    public function expireOverdue(): int
    {
        $expired = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', now())
            ->with('user')
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            // Saltar admins — no debemos revocar su acceso
            if ($subscription->user && $subscription->user->isAdmin()) {
                continue;
            }

            DB::transaction(function () use ($subscription) {
                $subscription->markExpired();
                if ($subscription->user) {
                    $this->revokePremiumRole($subscription->user);
                }
            });
            $count++;
        }

        if ($count > 0) {
            Log::info("Suscripciones expiradas: {$count}");
        }

        return $count;
    }

    /* ────────────────────────────────
     |  Renovación automática (cobro recurrente)
     |──────────────────────────────── */

    /**
     * Renueva suscripciones que vencen dentro de las próximas 24h.
     * Solo renueva automáticamente las que usan una pasarela con soporte recurrente
     * y tienen tarjeta almacenada (gateway_customer_id + gateway_card_id).
     *
     * Flujo:
     * 1. Trial que vence → cobrar S/49 → convertir a monthly
     * 2. Monthly que vence → cobrar S/49 → extender 30 días
     * 3. Yearly que vence → cobrar S/470 → extender 365 días
     *
     * @return array{renewed: int, failed: int, skipped: int}
     */
    public function renewExpiring(): array
    {
        $manager  = new PaymentGatewayManager();
        $renewed  = 0;
        $failed   = 0;
        $skipped  = 0;

        // Suscripciones activas que vencen en las próximas 24h y tienen tarjeta almacenada
        $expiring = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', now()->addHours(24))
            ->where('ends_at', '>', now())
            ->where(function ($q) {
                // Buscar en campos gateway_* O en openpay_* (legacy)
                $q->where(function ($q2) {
                    $q2->whereNotNull('gateway_customer_id')
                       ->whereNotNull('gateway_card_id');
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('openpay_customer_id')
                       ->whereNotNull('openpay_card_id');
                });
            })
            ->with('user')
            ->get();

        foreach ($expiring as $subscription) {
            if ($subscription->user && $subscription->user->isAdmin()) {
                continue;
            }

            // Resolver la pasarela de esta suscripción
            $gatewayName = $subscription->gateway_provider ?? 'openpay';

            try {
                $gateway = $manager->driver($gatewayName);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Pasarela desconocida en renovación', [
                    'user_id' => $subscription->user_id,
                    'gateway' => $gatewayName,
                ]);
                $failed++;
                continue;
            }

            // Si la pasarela no soporta cobro recurrente, skip
            if (!$gateway->supportsRecurring()) {
                Log::info('Pasarela no soporta renovación automática, se omitirá', [
                    'user_id' => $subscription->user_id,
                    'gateway' => $gatewayName,
                ]);
                $skipped++;
                continue;
            }

            // Determinar plan y monto
            $prices = $gateway->planPrices();
            if ($subscription->plan === Subscription::PLAN_TRIAL) {
                $newPlan  = Subscription::PLAN_MONTHLY;
                $amount   = $gateway->priceFor(Subscription::PLAN_MONTHLY);
                $duration = 30;
                $desc     = 'Vigilante SEACE — Plan Mensual (post-trial)';
            } elseif ($subscription->plan === Subscription::PLAN_YEARLY) {
                $newPlan  = Subscription::PLAN_YEARLY;
                $amount   = $gateway->priceFor(Subscription::PLAN_YEARLY);
                $duration = 365;
                $desc     = 'Vigilante SEACE — Plan Anual (renovación)';
            } else {
                $newPlan  = Subscription::PLAN_MONTHLY;
                $amount   = $gateway->priceFor(Subscription::PLAN_MONTHLY);
                $duration = 30;
                $desc     = 'Vigilante SEACE — Plan Mensual (renovación)';
            }

            // Obtener IDs de cliente/tarjeta (preferir gateway_*, fallback a openpay_*)
            $customerId = $subscription->gateway_customer_id ?? $subscription->openpay_customer_id;
            $cardId     = $subscription->gateway_card_id ?? $subscription->openpay_card_id;

            // Cobrar tarjeta almacenada
            $result = $gateway->chargeRecurring($customerId, $cardId, $amount, $desc);

            if ($result['success']) {
                DB::transaction(function () use ($subscription, $newPlan, $amount, $duration, $result, $gatewayName, $customerId, $cardId) {
                    $subscription->markExpired();

                    $newData = [
                        'plan'                => $newPlan,
                        'status'              => Subscription::STATUS_ACTIVE,
                        'starts_at'           => now(),
                        'ends_at'             => now()->addDays($duration),
                        'gateway_provider'    => $gatewayName,
                        'gateway_charge_id'   => $result['charge_id'],
                        'gateway_customer_id' => $customerId,
                        'gateway_card_id'     => $cardId,
                        'payment_method'      => 'card',
                        'amount'              => $amount,
                        'currency'            => 'PEN',
                        'metadata'            => ['renewal' => true, 'previous_plan' => $subscription->plan],
                    ];

                    // Compatibilidad openpay_*
                    if ($gatewayName === 'openpay') {
                        $newData['openpay_charge_id']   = $result['charge_id'];
                        $newData['openpay_customer_id'] = $customerId;
                        $newData['openpay_card_id']     = $cardId;
                    }

                    $subscription->user->subscriptions()->create($newData);
                });

                Log::info('Suscripción renovada automáticamente', [
                    'user_id'   => $subscription->user_id,
                    'old_plan'  => $subscription->plan,
                    'new_plan'  => $newPlan,
                    'charge_id' => $result['charge_id'],
                    'gateway'   => $gatewayName,
                ]);
                $renewed++;
            } else {
                Log::warning('Falló renovación automática', [
                    'user_id' => $subscription->user_id,
                    'gateway' => $gatewayName,
                    'error'   => $result['error'] ?? 'desconocido',
                ]);
                $failed++;
            }
        }

        return ['renewed' => $renewed, 'failed' => $failed, 'skipped' => $skipped];
    }

    /* ────────────────────────────────
     |  Admin helpers
     |──────────────────────────────── */

    /**
     * Extiende la suscripción activa del usuario X días.
     */
    public function extend(User $user, int $days): ?Subscription
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return null;
        }

        $subscription->update([
            'ends_at' => $subscription->ends_at->addDays($days),
        ]);

        Log::info('Suscripción extendida', [
            'user_id' => $user->id,
            'days'    => $days,
            'new_end' => $subscription->fresh()->ends_at->toDateTimeString(),
        ]);

        return $subscription->fresh();
    }

    /**
     * Otorga premium manualmente desde admin (sin pago).
     */
    public function grantPremium(User $user, int $days, string $plan = Subscription::PLAN_MONTHLY): Subscription
    {
        if ($user->isAdmin()) {
            throw new \RuntimeException('Los administradores ya tienen acceso completo, no necesitan suscripción.');
        }

        $now    = Carbon::now();
        $endsAt = $now->copy()->addDays($days);

        return DB::transaction(function () use ($user, $plan, $now, $endsAt) {
            // Expirar anteriores
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscription = $user->subscriptions()->create([
                'plan'     => $plan,
                'status'   => Subscription::STATUS_ACTIVE,
                'starts_at' => $now,
                'ends_at'  => $endsAt,
                'amount'   => 0,
                'currency' => 'PEN',
                'metadata' => ['granted_by' => 'admin', 'granted_at' => $now->toDateTimeString()],
            ]);

            $this->assignPremiumRole($user);

            Log::info('Premium otorgado por admin', [
                'user_id' => $user->id,
                'days'    => $endsAt->diffInDays($now),
            ]);

            return $subscription;
        });
    }

    /* ────────────────────────────────
     |  Role management (private)
     |──────────────────────────────── */

    /**
     * Asigna el rol proveedor-premium.
     * NUNCA toca usuarios con rol admin.
     */
    private function assignPremiumRole(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $premiumRole = \App\Models\Role::where('slug', 'proveedor-premium')->first();
        if ($premiumRole && !$user->hasRole('proveedor-premium')) {
            $user->roles()->attach($premiumRole->id);
        }
    }

    /**
     * Revoca el rol proveedor-premium.
     * NUNCA toca usuarios con rol admin.
     */
    private function revokePremiumRole(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $premiumRole = \App\Models\Role::where('slug', 'proveedor-premium')->first();
        if ($premiumRole) {
            $user->roles()->detach($premiumRole->id);
        }
    }

    /* ────────────────────────────────
     |  Admin notification (private)
     |──────────────────────────────── */

    /**
     * Notifica al administrador vía Telegram Admin Bot sobre una nueva suscripción.
     */
    private function notifyAdminNewSubscription(User $user, Subscription $subscription): void
    {
        try {
            (new AdminTelegramNotificationService())->notifyNewSubscription($user, $subscription);
        } catch (\Exception $e) {
            Log::warning('Error notificando suscripción al admin por Telegram', [
                'user_id' => $user->id,
                'plan'    => $subscription->plan,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
