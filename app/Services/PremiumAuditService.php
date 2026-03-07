<?php

namespace App\Services;

use App\Models\PremiumAuditLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para registrar auditoría de cambios premium.
 *
 * Todas las operaciones que otorguen o revoquen el rol proveedor-premium
 * DEBEN pasar por este servicio para garantizar trazabilidad completa.
 */
class PremiumAuditService
{
    /**
     * Registra que se otorgó premium a un usuario.
     */
    public static function logGranted(
        User $user,
        string $source,
        ?Subscription $subscription = null,
        ?int $grantedBy = null,
        array $extra = []
    ): PremiumAuditLog {
        return self::createLog(
            $user,
            PremiumAuditLog::ACTION_GRANTED,
            $source,
            $subscription,
            $grantedBy,
            $extra
        );
    }

    /**
     * Registra que se revocó premium a un usuario.
     */
    public static function logRevoked(
        User $user,
        string $source,
        ?Subscription $subscription = null,
        ?int $grantedBy = null,
        array $extra = []
    ): PremiumAuditLog {
        return self::createLog(
            $user,
            PremiumAuditLog::ACTION_REVOKED,
            $source,
            $subscription,
            $grantedBy,
            $extra
        );
    }

    /**
     * Crea el registro de auditoría con todos los datos disponibles.
     */
    private static function createLog(
        User $user,
        string $action,
        string $source,
        ?Subscription $subscription,
        ?int $grantedBy,
        array $extra
    ): PremiumAuditLog {
        $data = [
            'user_id'           => $user->id,
            'action'            => $action,
            'source'            => $source,
            'plan'              => $subscription?->plan,
            'subscription_id'   => $subscription?->id,
            'granted_by'        => $grantedBy,
            'premium_starts_at' => $subscription?->starts_at,
            'premium_ends_at'   => $subscription?->ends_at,
            'days_remaining'    => $subscription?->daysRemaining(),
            'gateway_provider'  => $subscription?->gateway_provider ?? ($extra['gateway_provider'] ?? null),
            'charge_id'         => $subscription?->gateway_transaction_id ?? ($extra['charge_id'] ?? null),
            'amount'            => $subscription?->amount ?? ($extra['amount'] ?? 0),
            'metadata'          => array_filter([
                'reason'       => $extra['reason'] ?? null,
                'ip'           => request()?->ip(),
                'user_agent'   => request()?->userAgent(),
                'auto_renew'   => $subscription?->auto_renew ?? null,
                'payment_method' => $subscription?->payment_method ?? null,
            ]),
            'created_at'        => now(),
        ];

        $log = PremiumAuditLog::create($data);

        Log::info("Premium audit: {$action} para user #{$user->id}", [
            'source'   => $source,
            'plan'     => $data['plan'],
            'admin'    => $grantedBy,
        ]);

        return $log;
    }

    /**
     * Obtiene el historial de auditoría de un usuario.
     */
    public static function historyForUser(int $userId, int $limit = 50)
    {
        return PremiumAuditLog::forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene el último evento de un usuario.
     */
    public static function lastEventForUser(int $userId): ?PremiumAuditLog
    {
        return PremiumAuditLog::forUser($userId)
            ->orderByDesc('created_at')
            ->first();
    }
}
