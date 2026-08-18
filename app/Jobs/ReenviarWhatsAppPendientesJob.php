<?php

namespace App\Jobs;

use App\Models\NotificationSend;
use App\Models\WhatsAppSubscription;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reenvía a un usuario de WhatsApp los procesos cuya entrega falló por
 * ventana de 24h cerrada (error 131047 de Meta), una vez que el usuario
 * reabre la ventana enviando un mensaje o respondiendo una alerta.
 *
 * - Solo reenvía si la ventana está abierta (interacción < 24h).
 * - Usa el payload original guardado en notified_processes.
 * - Marca reenviado_at para no duplicar.
 */
class ReenviarWhatsAppPendientesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    /**
     * Máximo de procesos a reenviar por corrida (evita ráfagas al par rate limit).
     */
    protected int $maxReenvios = 30;

    public function __construct(public string $phoneNumber)
    {
    }

    public function handle(WhatsAppNotificationService $service): void
    {
        $sub = WhatsAppSubscription::where('phone_number', $this->phoneNumber)
            ->activas()
            ->first();

        if (!$sub) {
            return;
        }

        // La ventana debe estar abierta (el webhook acaba de registrar interacción)
        if (!$sub->ultima_interaccion_at
            || $sub->ultima_interaccion_at->diffInHours(Carbon::now()) >= 24) {
            Log::info('ReenviarWhatsAppPendientes: ventana cerrada, no se reenvía', [
                'phone' => $this->phoneNumber,
            ]);

            return;
        }

        $pendientes = NotificationSend::where('canal', 'whatsapp')
            ->where('recipient_id', $this->phoneNumber)
            ->where('estado_entrega', 'failed')
            ->whereNull('reenviado_at')
            ->where('notified_at', '>=', Carbon::now()->subDays(3))
            ->with('notifiedProcess')
            ->orderBy('notified_at')
            ->limit($this->maxReenvios)
            ->get();

        if ($pendientes->isEmpty()) {
            return;
        }

        Log::info('ReenviarWhatsAppPendientes: reenviando procesos fallidos', [
            'phone' => $this->phoneNumber,
            'pendientes' => $pendientes->count(),
        ]);

        $reenviados = 0;

        foreach ($pendientes as $send) {
            $payload = $send->notifiedProcess?->payload;

            if (empty($payload)) {
                continue;
            }

            $resultado = $service->enviarProcesoASuscriptor(
                $sub,
                $payload,
                $send->keywords_matched ?? []
            );

            if ($resultado['success'] ?? false) {
                $send->update([
                    'wamid' => $resultado['wamid'] ?? null,
                    'estado_entrega' => 'aceptado',
                    'reenviado_at' => Carbon::now(),
                ]);
                $reenviados++;
            }
        }

        // Al reenviar, la evidencia de ventana cerrada quedó obsoleta
        if ($reenviados > 0) {
            $sub->update(['ultima_entrega_fallida_at' => null]);
        }

        Log::info('ReenviarWhatsAppPendientes: completado', [
            'phone' => $this->phoneNumber,
            'reenviados' => $reenviados,
        ]);
    }
}
