<?php

namespace App\Services;

use App\Contracts\NotificationTrackerContract;
use App\Models\NotificationSend;
use App\Models\NotifiedProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de tracking de notificaciones basado en BD.
 *
 * Reemplaza el dedup global por caché con dedup per-usuario persistente.
 *
 * Principios SOLID:
 *   - SRP: solo gestiona registro y consulta de envíos.
 *   - OCP: nuevos canales se soportan sin modificar esta clase.
 *   - DIP: ImportadorTdrEngine depende de NotificationTrackerContract.
 */
class ProcessNotificationTracker implements NotificationTrackerContract
{
    /**
     * {@inheritdoc}
     *
     * Consulta optimizada: JOIN notification_sends ↔ notified_processes
     * con índice compuesto (user_id, canal, recipient_id).
     */
    public function getNotifiedProcessIds(int $userId, string $canal, string $recipientId): array
    {
        return NotificationSend::where('user_id', $userId)
            ->where('canal', $canal)
            ->where('recipient_id', $recipientId)
            ->join('notified_processes', 'notified_processes.id', '=', 'notification_sends.notified_process_id')
            ->pluck('notified_processes.seace_proceso_id')
            ->toArray();
    }

    /**
     * {@inheritdoc}
     *
     * Usa transacción para garantizar consistencia entre
     * notified_processes y notification_sends.
     */
    public function recordNotification(
        array $contratoData,
        string $seaceProcesoId,
        int $userId,
        string $canal,
        string $recipientId,
        ?string $subscriptionLabel = null,
        array $keywordsMatched = []
    ): bool {
        try {
            return DB::transaction(function () use (
                $contratoData, $seaceProcesoId, $userId, $canal,
                $recipientId, $subscriptionLabel, $keywordsMatched
            ) {
                // 1. Crear o encontrar el proceso (un solo INSERT por proceso único)
                $process = NotifiedProcess::findOrCreateFromSeace($seaceProcesoId, $contratoData);

                // 2. Registrar el envío (respeta unique constraint)
                $created = NotificationSend::firstOrCreate(
                    [
                        'notified_process_id' => $process->id,
                        'user_id' => $userId,
                        'canal' => $canal,
                        'recipient_id' => $recipientId,
                    ],
                    [
                        'subscription_label' => $subscriptionLabel,
                        'keywords_matched' => $keywordsMatched,
                        'notified_at' => now(),
                    ]
                );

                return $created->wasRecentlyCreated;
            });
        } catch (\Exception $e) {
            Log::warning('ProcessNotificationTracker: error al registrar envío', [
                'seace_id' => $seaceProcesoId,
                'user_id' => $userId,
                'canal' => $canal,
                'recipient' => $recipientId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function wasAlreadyNotified(
        string $seaceProcesoId,
        int $userId,
        string $canal,
        string $recipientId
    ): bool {
        return NotificationSend::whereHas(
            'notifiedProcess',
            fn ($q) => $q->where('seace_proceso_id', $seaceProcesoId)
        )
            ->where('user_id', $userId)
            ->where('canal', $canal)
            ->where('recipient_id', $recipientId)
            ->exists();
    }
}
