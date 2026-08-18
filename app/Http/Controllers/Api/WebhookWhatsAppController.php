<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Controller para recibir webhooks de WhatsApp Business Cloud API.
 *
 * Meta envía dos tipos de peticiones:
 * 1. GET  → Verificación del webhook (hub.verify_token + hub.challenge)
 * 2. POST → Mensajes entrantes (clicks en botones, mensajes de texto, etc.)
 *
 * Los mensajes entrantes se encolan en cache para ser procesados por
 * el WhatsAppBotListener (o se procesan inline si el listener no corre).
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/set-up
 */
class WebhookWhatsAppController extends Controller
{
    /**
     * Verificación del webhook (requerido por Meta al configurar la URL).
     *
     * Meta envía: GET /api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=XXX&hub.challenge=YYY
     * Se debe responder con el valor de hub.challenge si el verify_token coincide.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = config('services.whatsapp.verify_token', '');

        if ($mode === 'subscribe' && $token === $expectedToken && !empty($expectedToken)) {
            Log::info('WhatsApp Webhook: verificación exitosa');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook: verificación fallida', [
            'mode' => $mode,
            'token_match' => $token === $expectedToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Recibir mensajes entrantes de WhatsApp (botones, texto, etc.)
     *
     * Meta espera SIEMPRE respuesta 200 OK (incluso si hay errores internos).
     * Si no se responde 200 rápidamente, Meta reintenta y puede desactivar el webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Validar que sea un evento de WhatsApp
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Verificar si hay mensajes en el payload
        $hasMessages = false;
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $messages = $change['value']['messages'] ?? [];
                if (!empty($messages)) {
                    $hasMessages = true;
                    break 2;
                }
            }
        }

        // Registrar estados de entrega (diagnóstico: delivered/read/failed)
        $this->logStatuses($payload);

        // Actualizar última interacción de los suscriptores que escriben o
        // tocan botones. Determina si la ventana de 24h está abierta.
        $this->registrarInteraccion($payload);

        if (!$hasMessages) {
            // Status updates (delivered, read, etc.) - ack without processing
            return response()->json(['status' => 'ok'], 200);
        }

        // Encolar para procesamiento por WhatsAppBotListener
        $this->enqueuePayload($payload);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Marcar la última interacción entrante del usuario (mensaje o botón).
     *
     * Además, al reabrirse la ventana de 24h, encolar el reenvío de los
     * procesos cuya entrega había fallado por ventana cerrada.
     */
    protected function registrarInteraccion(array $payload): void
    {
        $phones = [];

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                if (empty($value['messages'])) {
                    continue;
                }

                $phone = $value['contacts'][0]['wa_id'] ?? null;
                if ($phone) {
                    $phones[] = $phone;
                }
            }
        }

        $phones = array_values(array_unique($phones));

        if (empty($phones)) {
            return;
        }

        $updated = \App\Models\WhatsAppSubscription::whereIn('phone_number', $phones)
            ->update(['ultima_interaccion_at' => now()]);

        // Solo encolar reenvíos si realmente hay suscripciones afectadas
        if ($updated > 0) {
            foreach ($phones as $phone) {
                \App\Jobs\ReenviarWhatsAppPendientesJob::dispatch($phone)
                    ->delay(now()->addSeconds(3));
            }
        }
    }

    /**
     * Registrar los cambios de estado de mensajes enviados (webhook de Meta).
     *
     * - Actualiza el estado de entrega del envío en BD (correlación por wamid).
     * - Si la entrega FALLA por ventana cerrada (131047), marca la suscripción
     *   para alertar en la UI y dejar el proceso en cola de reenvío.
     */
    protected function logStatuses(array $payload): void
    {
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $statuses = $change['value']['statuses'] ?? [];

                foreach ($statuses as $status) {
                    $errors = $status['errors'] ?? [];
                    $wamid = $status['id'] ?? null;
                    $recipient = $status['recipient_id'] ?? null;
                    $estado = $status['status'] ?? 'unknown';

                    if (!empty($errors) || config('services.whatsapp.debug_logs', false)) {
                        Log::info('WhatsApp Webhook: estado de mensaje', [
                            'status' => $estado,
                            'recipient' => $recipient,
                            'wamid' => $wamid,
                            'timestamp' => $status['timestamp'] ?? null,
                            'errors' => $errors,
                        ]);
                    }

                    // Correlacionar con el envío registrado (wamid)
                    if ($wamid) {
                        \App\Models\NotificationSend::where('wamid', $wamid)
                            ->update(['estado_entrega' => $estado]);
                    }

                    // Falla por ventana cerrada → evidencia para la UI + cola de reenvío
                    $esErrorVentana = false;
                    foreach ($errors as $error) {
                        if ((string) ($error['code'] ?? '') === '131047'
                            || (string) ($error['code'] ?? '') === '130472') {
                            $esErrorVentana = true;
                            break;
                        }
                    }

                    if ($esErrorVentana && $recipient) {
                        \App\Models\WhatsAppSubscription::where('phone_number', $recipient)
                            ->update(['ultima_entrega_fallida_at' => now()]);
                    }
                }
            }
        }
    }

    /**
     * Encolar el payload para el WhatsAppBotListener.
     */
    protected function enqueuePayload(array $payload): void
    {
        $cacheKey = 'whatsapp:' . config('app.env', 'production') . ':incoming_messages';

        $existing = Cache::get($cacheKey, []);
        $existing[] = $payload;

        // Limitar cola a 500 mensajes para evitar memory issues
        if (count($existing) > 500) {
            $existing = array_slice($existing, -500);
        }

        Cache::put($cacheKey, $existing, now()->addHours(24));

        Log::debug('WhatsApp Webhook: payload encolado', [
            'queue_size' => count($existing),
        ]);
    }

}
