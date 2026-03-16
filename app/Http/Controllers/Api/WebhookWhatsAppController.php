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

        if (!$hasMessages) {
            // Status updates (delivered, read, etc.) - ack without processing
            return response()->json(['status' => 'ok'], 200);
        }

        // Encolar para procesamiento por WhatsAppBotListener
        $this->enqueuePayload($payload);

        return response()->json(['status' => 'ok'], 200);
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
