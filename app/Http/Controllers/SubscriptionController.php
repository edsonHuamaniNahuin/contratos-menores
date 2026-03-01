<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentGatewayManager;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PaymentGatewayManager $gatewayManager,
    ) {}

    /**
     * GET /planes/checkout/{plan}?trial=1 — Iniciar checkout.
     *
     * Según la pasarela activa, redirige a un checkout externo (MercadoPago)
     * o renderiza un formulario in-page (Openpay).
     */
    public function checkout(Request $request, string $plan): View|RedirectResponse
    {
        if (!in_array($plan, ['monthly', 'yearly'])) {
            return redirect()->route('planes')->with('error', 'Plan no válido.');
        }

        $user    = $request->user();
        $isTrial = (bool) $request->query('trial', false);

        if ($isTrial && $plan !== 'monthly') {
            return redirect()->route('planes')->with('error', 'El trial solo está disponible para el plan mensual.');
        }

        if ($isTrial && !$user->canStartTrial()) {
            return redirect()->route('planes')->with('error', 'Ya utilizaste tu periodo de prueba gratuito.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('home')
                ->with('info', 'Como administrador ya tienes acceso completo.');
        }

        $gateway = $this->gatewayManager->driver();

        if (!$gateway->isConfigured()) {
            return redirect()->route('planes')
                ->with('error', 'La pasarela de pago no está configurada. Contacta al administrador.');
        }

        $result = $gateway->createCheckout($user, $plan, $isTrial);

        // Trial directo (MercadoPago): activar sin pago
        if (($result['type'] ?? '') === 'trial') {
            try {
                $this->subscriptionService->startTrial($user, $gateway->name());

                return redirect()->route('home')
                    ->with('success', '¡Tu trial de 15 días ha sido activado!');
            } catch (\Exception $e) {
                return redirect()->route('planes')
                    ->with('error', 'Error activando el trial: ' . $e->getMessage());
            }
        }

        // Redirect externo (reservado para futuras pasarelas)
        if (($result['type'] ?? '') === 'redirect') {
            session([
                'checkout_plan'     => $plan,
                'checkout_is_trial' => $isTrial,
                'checkout_gateway'  => $gateway->name(),
            ]);

            return redirect($result['url']);
        }

        // Error
        if (($result['type'] ?? '') === 'error') {
            return redirect()->route('planes')
                ->with('error', $result['error'] ?? 'Error al iniciar el checkout.');
        }

        // Vista in-page (Openpay / MercadoPago)
        return view($result['view'], $result['view_data']);
    }

    /**
     * POST /planes/charge — Procesar pago in-page (Openpay / MercadoPago).
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

        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'error' => 'Los administradores no necesitan suscripción.'], 400);
        }

        if ($isTrial && !$user->canStartTrial()) {
            return response()->json(['success' => false, 'error' => 'Ya utilizaste tu periodo de prueba.'], 400);
        }

        $gateway = $this->gatewayManager->driver();

        $result = $gateway->processPayment($user, $plan, $isTrial, [
            'token_id'            => $request->input('token_id'),
            'device_session_id'   => $request->input('device_session_id'),
            'existing_customer_id' => $user->activeSubscription()?->gateway_customer_id
                                      ?? $user->activeSubscription()?->openpay_customer_id,
        ]);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'] ?? 'Error al procesar el pago.',
            ], 422);
        }

        try {
            if ($isTrial) {
                $this->subscriptionService->startTrialWithCard(
                    $user,
                    $result['customer_id'],
                    $result['card_id'],
                    $gateway->name(),
                );
            } else {
                $this->subscriptionService->activatePaid($user, $plan, [
                    'gateway_provider'  => $gateway->name(),
                    'charge_id'         => $result['charge_id'],
                    'customer_id'       => $result['customer_id'],
                    'card_id'           => $result['card_id'],
                    'payment_method'    => 'card',
                    'amount'            => $result['amount'],
                    'currency'          => 'PEN',
                    'metadata'          => $result['data'] ?? null,
                ]);
            }

            $message = $isTrial
                ? '¡Tu trial de 15 días ha sido activado! Al vencer se cobrará automáticamente S/ 49/mes.'
                : '¡Pago exitoso! Tu suscripción Premium ha sido activada.';

            return response()->json([
                'success'  => true,
                'redirect' => route('home'),
                'message'  => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Error activando suscripción después del pago', [
                'user_id'   => $user->id,
                'plan'      => $plan,
                'charge_id' => $result['charge_id'] ?? null,
                'gateway'   => $gateway->name(),
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Pago procesado, pero hubo un error al activar la suscripción. Contacta soporte.',
            ], 500);
        }
    }

    /**
     * GET /planes/callback — Callback de pago externo (MercadoPago).
     */
    public function callback(Request $request): View|RedirectResponse
    {
        $gatewayName = $request->query('gateway', session('checkout_gateway', 'mercadopago'));
        $status      = $request->query('status', 'failure');
        $plan        = session('checkout_plan', 'monthly');
        $user        = $request->user();

        session()->forget(['checkout_plan', 'checkout_is_trial', 'checkout_gateway']);

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para completar el pago.');
        }

        $gateway = $this->gatewayManager->driver($gatewayName);

        if ($status === 'failure') {
            return view('checkout.mercadopago', [
                'paymentStatus' => 'failure',
                'error'         => 'El pago no fue completado.',
            ]);
        }

        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');

        if (!$paymentId) {
            return view('checkout.mercadopago', [
                'paymentStatus' => 'failure',
                'error'         => 'No se recibió confirmación del pago.',
            ]);
        }

        $result = $gateway->processPayment($user, $plan, false, [
            'payment_id' => $paymentId,
            'status'     => $status,
        ]);

        if ($result['success']) {
            try {
                $subscription = $this->subscriptionService->activatePaid($user, $plan, [
                    'gateway_provider'  => $gateway->name(),
                    'charge_id'         => $result['charge_id'],
                    'customer_id'       => $result['customer_id'],
                    'card_id'           => $result['card_id'] ?? null,
                    'payment_method'    => 'mercadopago',
                    'amount'            => $result['amount'],
                    'currency'          => 'PEN',
                    'metadata'          => $result['data'] ?? null,
                ]);

                return view('checkout.mercadopago', [
                    'paymentStatus' => 'success',
                    'subscription'  => $subscription,
                    'paymentId'     => $paymentId,
                ]);
            } catch (\Exception $e) {
                Log::error('Error activando suscripción post pago MP', ['error' => $e->getMessage()]);
                return view('checkout.mercadopago', [
                    'paymentStatus' => 'failure',
                    'error'         => 'El pago se procesó pero hubo un error activando tu suscripción. Contacta soporte.',
                ]);
            }
        }

        $paymentStatus = ($status === 'pending' || str_contains($result['error'] ?? '', 'pendiente')) ? 'pending' : 'failure';

        return view('checkout.mercadopago', [
            'paymentStatus' => $paymentStatus,
            'paymentId'     => $paymentId,
            'error'         => $result['error'] ?? null,
        ]);
    }

    /**
     * POST /api/webhooks/openpay
     */
    public function webhookOpenpay(Request $request): JsonResponse
    {
        $gateway   = $this->gatewayManager->driver('openpay');
        $payload   = $request->getContent();
        $signature = $request->header('X-Openpay-Signature', '');

        if (!$gateway->verifyWebhook($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $result = $gateway->handleWebhookEvent($request->all());

        return response()->json(['status' => 'ok', 'action' => $result['action']]);
    }

    /**
     * POST /api/webhooks/mercadopago
     */
    public function webhookMercadoPago(Request $request): JsonResponse
    {
        $gateway   = $this->gatewayManager->driver('mercadopago');
        $payload   = $request->getContent();
        $signature = $request->header('x-signature', '');

        if (!$gateway->verifyWebhook($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $result = $gateway->handleWebhookEvent($request->all());

        // Si el webhook confirma un pago aprobado, activar suscripción
        if (($result['action'] ?? '') === 'payment_update' && ($result['status'] ?? '') === 'approved') {
            $metadata = $result['metadata'] ?? [];
            $userId   = $metadata['user_id'] ?? null;
            $plan     = $metadata['plan'] ?? 'monthly';

            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user && !$user->isPremium()) {
                    try {
                        $this->subscriptionService->activatePaid($user, $plan, [
                            'gateway_provider' => 'mercadopago',
                            'charge_id'        => $result['payment_id'],
                            'customer_id'      => null,
                            'card_id'          => null,
                            'payment_method'   => 'mercadopago',
                            'amount'           => $result['data']['transaction_amount'] ?? 0,
                            'currency'         => 'PEN',
                            'metadata'         => $result['data'] ?? null,
                        ]);
                        Log::info('Suscripción activada via webhook MP', ['user_id' => $userId]);
                    } catch (\Exception $e) {
                        Log::error('Error activando via webhook MP', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok', 'action' => $result['action'] ?? 'logged']);
    }
}
