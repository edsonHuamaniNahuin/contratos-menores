<?php

namespace App\Http\Controllers;

use App\Services\OpenpayService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private OpenpayService $openpayService,
    ) {}

    /**
     * GET /planes/checkout/{plan}?trial=1 — Mostrar checkout.
     *
     * Si llega ?trial=1, el checkout mostrará "15 días gratis, luego S/49/mes"
     * y solo registrará la tarjeta sin cobrar.
     */
    public function checkout(Request $request, string $plan): View|RedirectResponse
    {
        if (!in_array($plan, ['monthly', 'yearly'])) {
            return redirect()->route('planes')->with('error', 'Plan no válido.');
        }

        $user    = $request->user();
        $isTrial = (bool) $request->query('trial', false);

        // Solo se puede hacer trial con plan mensual
        if ($isTrial && $plan !== 'monthly') {
            return redirect()->route('planes')->with('error', 'El trial solo está disponible para el plan mensual.');
        }

        // No permitir trial si ya lo usó
        if ($isTrial && !$user->canStartTrial()) {
            return redirect()->route('planes')->with('error', 'Ya utilizaste tu periodo de prueba gratuito.');
        }

        // Admins no necesitan suscripción
        if ($user->isAdmin()) {
            return redirect()->route('home')
                ->with('info', 'Como administrador ya tienes acceso completo.');
        }

        $price = OpenpayService::priceFor($plan);

        return view('checkout', [
            'plan'       => $plan,
            'price'      => $price,
            'isTrial'    => $isTrial,
            'merchantId' => $this->openpayService->getMerchantId(),
            'publicKey'  => $this->openpayService->getPublicKey(),
            'sandbox'    => $this->openpayService->isSandbox(),
        ]);
    }

    /**
     * POST /planes/charge — Procesar el token de Openpay.
     *
     * Dos modos:
     * 1. Trial → Crear customer + guardar tarjeta + activar trial (sin cobro)
     * 2. Pago  → Crear customer + cobrar con token + activar suscripción
     */
    public function charge(Request $request): JsonResponse
    {
        $request->validate([
            'plan'              => 'required|in:monthly,yearly',
            'token_id'          => 'required|string',
            'device_session_id' => 'required|string',
            'is_trial'          => 'sometimes|boolean',
        ]);

        $user    = $request->user();
        $plan    = $request->input('plan');
        $isTrial = (bool) $request->input('is_trial', false);
        $price   = OpenpayService::priceFor($plan);

        // Validar admin
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Los administradores no necesitan suscripción.'], 400);
        }

        // Validar trial
        if ($isTrial && !$user->canStartTrial()) {
            return response()->json(['success' => false, 'error' => 'Ya utilizaste tu periodo de prueba.'], 400);
        }

        // 1. Crear customer en Openpay (reutilizar si ya existe)
        $customerId = $user->activeSubscription()?->openpay_customer_id;
        if (!$customerId) {
            $customerId = $this->openpayService->createCustomer($user);
        }
        if (!$customerId) {
            return response()->json(['success' => false, 'error' => 'No se pudo crear tu perfil de pago. Intenta de nuevo.'], 422);
        }

        if ($isTrial) {
            // ── MODO TRIAL: solo registrar tarjeta, sin cobro ──
            $cardId = $this->openpayService->addCardToCustomer(
                $customerId,
                $request->input('token_id'),
                $request->input('device_session_id'),
            );

            if (!$cardId) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No se pudo registrar la tarjeta. Verifica tus datos e intenta de nuevo.',
                ], 422);
            }

            try {
                $this->subscriptionService->startTrialWithCard($user, $customerId, $cardId);

                return response()->json([
                    'success'  => true,
                    'redirect' => route('home'),
                    'message'  => '¡Tu trial de 15 días ha sido activado! Al vencer se cobrará automáticamente S/ 49/mes.',
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Tarjeta registrada pero hubo un error activando el trial: ' . $e->getMessage(),
                ], 500);
            }
        }

        // ── MODO PAGO DIRECTO ──
        if ($price <= 0) {
            return response()->json(['success' => false, 'error' => 'Plan no válido.'], 400);
        }

        // Registrar tarjeta primero (para renovaciones futuras)
        $cardId = $this->openpayService->addCardToCustomer(
            $customerId,
            $request->input('token_id'),
            $request->input('device_session_id'),
        );

        // Cobrar con la tarjeta almacenada
        if ($cardId) {
            $result = $this->openpayService->chargeCustomerCard(
                customerId: $customerId,
                cardId: $cardId,
                amount: $price,
                description: 'Vigilante SEACE — Plan ' . ($plan === 'yearly' ? 'Anual' : 'Mensual'),
            );
        } else {
            // Fallback: cobrar directamente con token
            $result = $this->openpayService->createCharge(
                tokenId: $request->input('token_id'),
                amount: $price,
                description: 'Vigilante SEACE — Plan ' . ($plan === 'yearly' ? 'Anual' : 'Mensual'),
                customerId: $customerId,
                deviceSessionId: $request->input('device_session_id'),
            );
        }

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'] ?? 'Error al procesar el pago.',
            ], 422);
        }

        // Activar suscripción
        try {
            $this->subscriptionService->activatePaid($user, $plan, [
                'charge_id'      => $result['charge_id'],
                'customer_id'    => $customerId,
                'card_id'        => $cardId,
                'payment_method' => 'card',
                'amount'         => $price,
                'currency'       => 'PEN',
                'metadata'       => $result['data'] ?? null,
            ]);

            return response()->json([
                'success'  => true,
                'redirect' => route('home'),
                'message'  => '¡Pago exitoso! Tu suscripción Premium ha sido activada.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Pago procesado, pero hubo un error al activar la suscripción. Contacta soporte.',
            ], 500);
        }
    }

    /**
     * POST /webhooks/openpay — Recibir eventos de Openpay.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Openpay-Signature', '');

        if (!$this->openpayService->verifyWebhook($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $event = $request->all();

        \Illuminate\Support\Facades\Log::info('Openpay webhook recibido', $event);

        return response()->json(['status' => 'ok']);
    }
}
