<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /* ────────────────────────────────
     |  Trial (requiere registro de tarjeta en Openpay)
     |──────────────────────────────── */

    /**
     * Verifica si el usuario puede iniciar un trial.
     * Los admins NO participan en el sistema de suscripciones.
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
     * Activa trial de 15 días con tarjeta registrada en Openpay.
     *
     * El usuario registra su tarjeta en la pasarela de pago. No se cobra
     * inmediatamente; al vencer el trial el scheduler cobra automáticamente.
     *
     * @param  string $openpayCustomerId  ID del customer creado en Openpay
     * @param  string $openpayCardId      ID de la tarjeta almacenada en Openpay
     */
    public function startTrialWithCard(
        User $user,
        string $openpayCustomerId,
        string $openpayCardId,
    ): Subscription {
        if (!$this->canStartTrial($user)) {
            throw new \RuntimeException('El usuario ya utilizó su periodo de prueba o es administrador.');
        }

        $now    = Carbon::now();
        $endsAt = $now->copy()->addDays(Subscription::TRIAL_DAYS);

        return DB::transaction(function () use ($user, $now, $endsAt, $openpayCustomerId, $openpayCardId) {
            // Expirar cualquier suscripción activa previa
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscription = $user->subscriptions()->create([
                'plan'                => Subscription::PLAN_TRIAL,
                'status'              => Subscription::STATUS_ACTIVE,
                'starts_at'           => $now,
                'ends_at'             => $endsAt,
                'trial_ends_at'       => $endsAt,
                'openpay_customer_id' => $openpayCustomerId,
                'openpay_card_id'     => $openpayCardId,
                'payment_method'      => 'card',
                'amount'              => 0,
                'currency'            => 'PEN',
            ]);

            $this->assignPremiumRole($user);

            Log::info('Trial con tarjeta activado', [
                'user_id'     => $user->id,
                'ends_at'     => $endsAt->toDateTimeString(),
                'customer_id' => $openpayCustomerId,
                'card_id'     => $openpayCardId,
            ]);

            return $subscription;
        });
    }

    /* ────────────────────────────────
     |  Paid subscription
     |──────────────────────────────── */

    /**
     * Activa suscripción paga (después de cargo exitoso en Openpay).
     */
    public function activatePaid(User $user, string $plan, array $paymentData): Subscription
    {
        if ($user->isAdmin()) {
            throw new \RuntimeException('Los administradores están excluidos del sistema de suscripciones.');
        }

        $duration = $plan === Subscription::PLAN_YEARLY ? 365 : 30;
        $now      = Carbon::now();
        $endsAt   = $now->copy()->addDays($duration);

        return DB::transaction(function () use ($user, $plan, $now, $endsAt, $paymentData) {
            // Cancelar suscripciones anteriores activas
            $user->subscriptions()
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            $subscription = $user->subscriptions()->create([
                'plan'                => $plan,
                'status'              => Subscription::STATUS_ACTIVE,
                'starts_at'           => $now,
                'ends_at'             => $endsAt,
                'openpay_charge_id'   => $paymentData['charge_id'] ?? null,
                'openpay_customer_id' => $paymentData['customer_id'] ?? null,
                'openpay_card_id'     => $paymentData['card_id'] ?? null,
                'payment_method'      => $paymentData['payment_method'] ?? 'card',
                'amount'              => $paymentData['amount'] ?? 0,
                'currency'            => $paymentData['currency'] ?? 'PEN',
                'metadata'            => $paymentData['metadata'] ?? null,
            ]);

            $this->assignPremiumRole($user);

            Log::info('Suscripción premium activada', [
                'user_id'    => $user->id,
                'plan'       => $plan,
                'ends_at'    => $endsAt->toDateTimeString(),
                'charge_id'  => $paymentData['charge_id'] ?? null,
            ]);

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
     * Cobra la tarjeta guardada en Openpay.
     *
     * Flujo:
     * 1. Trial que vence → cobrar S/49 → convertir a monthly
     * 2. Monthly que vence → cobrar S/49 → extender 30 días
     * 3. Yearly que vence → cobrar S/470 → extender 365 días
     *
     * @return array{renewed: int, failed: int}
     */
    public function renewExpiring(): array
    {
        $openpay = new OpenpayService();
        $renewed = 0;
        $failed  = 0;

        // Suscripciones activas que vencen en las próximas 24h y tienen tarjeta
        $expiring = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '<=', now()->addHours(24))
            ->where('ends_at', '>', now()) // Aún no expiradas
            ->whereNotNull('openpay_customer_id')
            ->whereNotNull('openpay_card_id')
            ->with('user')
            ->get();

        foreach ($expiring as $subscription) {
            // Saltar admins
            if ($subscription->user && $subscription->user->isAdmin()) {
                continue;
            }

            // Determinar plan y monto para la renovación
            if ($subscription->plan === Subscription::PLAN_TRIAL) {
                $newPlan  = Subscription::PLAN_MONTHLY;
                $amount   = OpenpayService::priceFor(Subscription::PLAN_MONTHLY);
                $duration = 30;
                $desc     = 'Vigilante SEACE — Plan Mensual (post-trial)';
            } elseif ($subscription->plan === Subscription::PLAN_YEARLY) {
                $newPlan  = Subscription::PLAN_YEARLY;
                $amount   = OpenpayService::priceFor(Subscription::PLAN_YEARLY);
                $duration = 365;
                $desc     = 'Vigilante SEACE — Plan Anual (renovación)';
            } else {
                $newPlan  = Subscription::PLAN_MONTHLY;
                $amount   = OpenpayService::priceFor(Subscription::PLAN_MONTHLY);
                $duration = 30;
                $desc     = 'Vigilante SEACE — Plan Mensual (renovación)';
            }

            // Cobrar tarjeta guardada
            $result = $openpay->chargeCustomerCard(
                $subscription->openpay_customer_id,
                $subscription->openpay_card_id,
                $amount,
                $desc,
            );

            if ($result['success']) {
                DB::transaction(function () use ($subscription, $newPlan, $amount, $duration, $result) {
                    // Marcar la anterior como expirada
                    $subscription->markExpired();

                    // Crear nueva suscripción
                    $subscription->user->subscriptions()->create([
                        'plan'                => $newPlan,
                        'status'              => Subscription::STATUS_ACTIVE,
                        'starts_at'           => now(),
                        'ends_at'             => now()->addDays($duration),
                        'openpay_charge_id'   => $result['charge_id'],
                        'openpay_customer_id' => $subscription->openpay_customer_id,
                        'openpay_card_id'     => $subscription->openpay_card_id,
                        'payment_method'      => 'card',
                        'amount'              => $amount,
                        'currency'            => 'PEN',
                        'metadata'            => ['renewal' => true, 'previous_plan' => $subscription->plan],
                    ]);
                });

                Log::info('Suscripción renovada automáticamente', [
                    'user_id'   => $subscription->user_id,
                    'old_plan'  => $subscription->plan,
                    'new_plan'  => $newPlan,
                    'charge_id' => $result['charge_id'],
                ]);
                $renewed++;
            } else {
                Log::warning('Falló renovación automática', [
                    'user_id' => $subscription->user_id,
                    'error'   => $result['error'] ?? 'desconocido',
                ]);
                $failed++;
            }
        }

        return ['renewed' => $renewed, 'failed' => $failed];
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
}
