<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pasarela Mercado Pago — implementa PaymentGatewayContract.
 *
 * Usa Checkout API (transparente / in-page) para pagos con tarjeta.
 * El usuario paga directamente en la página sin salir al sitio de MP.
 * No requiere SDK PHP: solo HTTP Client (Guzzle) + MercadoPago.js v2 en frontend.
 *
 * Docs: https://www.mercadopago.com.pe/developers/es/docs/checkout-api/landing
 */
class MercadoPagoGateway implements PaymentGatewayContract
{
    private string $accessToken;
    private string $publicKey;
    private string $webhookSecret;
    private string $baseUrl = 'https://api.mercadopago.com';

    public function __construct()
    {
        $this->accessToken   = (string) config('services.mercadopago.access_token', '');
        $this->publicKey     = (string) config('services.mercadopago.public_key', '');
        $this->webhookSecret = (string) config('services.mercadopago.webhook_secret', '');
    }

    /* ── Identidad ─────────────────────────────── */

    public function name(): string
    {
        return 'mercadopago';
    }

    public function displayName(): string
    {
        return 'Mercado Pago';
    }

    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->publicKey);
    }

    /* ── Checkout (API Transparente — In-page) ─── */

    public function createCheckout(User $user, string $plan, bool $isTrial, array $extra = []): array
    {
        if ($isTrial) {
            return [
                'type'      => 'trial',
                'view'      => null,
                'view_data' => ['plan' => $plan, 'isTrial' => true],
            ];
        }

        // Retornar vista in-page (igual que Openpay)
        return [
            'type'      => 'view',
            'view'      => $this->checkoutView(),
            'view_data' => array_merge([
                'plan'    => $plan,
                'price'   => static::priceFor($plan),
                'isTrial' => $isTrial,
                'gateway' => $this->name(),
            ], $this->frontendConfig()),
        ];
    }

    public function processPayment(User $user, string $plan, bool $isTrial, array $paymentData): array
    {
        if ($isTrial) {
            return [
                'success'     => true,
                'charge_id'   => null,
                'customer_id' => null,
                'card_id'     => null,
                'amount'      => 0,
                'data'        => ['trial' => true],
            ];
        }

        $token     = $paymentData['token_id'] ?? null;
        $price     = static::priceFor($plan);
        $planLabel = $plan === 'yearly' ? 'Anual' : 'Mensual';

        if (!$token) {
            return ['success' => false, 'error' => 'No se recibió el token de la tarjeta.'];
        }

        if ($price <= 0) {
            return ['success' => false, 'error' => 'Plan no válido.'];
        }

        // Crear pago directo con token de tarjeta vía Checkout API
        $paymentResult = $this->createPayment([
            'transaction_amount' => round($price, 2),
            'token'              => $token,
            'description'        => "Vigilante SEACE — Plan {$planLabel}",
            'installments'       => 1,
            'payer'              => [
                'email' => $user->email,
            ],
            'statement_descriptor' => 'VIGILANTE SEACE',
            'metadata' => [
                'user_id' => $user->id,
                'plan'    => $plan,
            ],
        ]);

        if (!$paymentResult) {
            return ['success' => false, 'error' => 'No se pudo procesar el pago. Intenta de nuevo.'];
        }

        $status = $paymentResult['status'] ?? 'unknown';

        if ($status === 'approved') {
            return [
                'success'     => true,
                'charge_id'   => (string) $paymentResult['id'],
                'customer_id' => (string) ($paymentResult['payer']['id'] ?? $user->id),
                'card_id'     => null,
                'amount'      => (float) ($paymentResult['transaction_amount'] ?? $price),
                'data'        => $paymentResult,
            ];
        }

        if ($status === 'in_process' || $status === 'pending') {
            return [
                'success' => false,
                'error'   => 'El pago está pendiente de aprobación. Te notificaremos cuando se confirme.',
            ];
        }

        // Pago rechazado — traducir status_detail a mensaje amigable
        Log::warning('MercadoPago pago rechazado', [
            'status'        => $status,
            'status_detail' => $paymentResult['status_detail'] ?? 'unknown',
            'payment_id'    => $paymentResult['id'] ?? null,
        ]);

        $friendlyError = $this->translateStatusDetail($paymentResult['status_detail'] ?? '');

        return [
            'success' => false,
            'error'   => $friendlyError,
            'data'    => $paymentResult,
        ];
    }

    /* ── Cobros recurrentes ────────────────────── */

    public function chargeRecurring(string $customerId, string $cardId, float $amount, string $description): array
    {
        Log::info('MercadoPago: cobro recurrente no soportado en Checkout API básico', [
            'customer_id' => $customerId,
            'amount'      => $amount,
        ]);

        return ['success' => false, 'error' => 'Mercado Pago no soporta cobros recurrentes automáticos. El usuario debe renovar manualmente.'];
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    /* ── Webhook ───────────────────────────────── */

    public function verifyWebhook(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            return true;
        }

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[trim($key)] = trim($value);
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if (empty($ts) || empty($v1)) {
            return false;
        }

        $data   = json_decode($payload, true);
        $dataId = $data['data']['id'] ?? '';

        $manifest = "id:{$dataId};request-id:;ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $v1);
    }

    public function handleWebhookEvent(array $eventData): array
    {
        $type   = $eventData['type'] ?? $eventData['action'] ?? 'unknown';
        $dataId = $eventData['data']['id'] ?? null;

        Log::info('MercadoPago webhook recibido', ['type' => $type, 'data_id' => $dataId]);

        if ($type === 'payment' || str_contains($type, 'payment')) {
            if (!$dataId) {
                return ['action' => 'ignored', 'reason' => 'No data_id'];
            }

            $payment = $this->getPayment($dataId);

            if (!$payment) {
                return ['action' => 'error', 'reason' => 'No se pudo obtener el pago'];
            }

            return [
                'action'     => 'payment_update',
                'payment_id' => (string) $dataId,
                'status'     => $payment['status'] ?? 'unknown',
                'metadata'   => $payment['metadata'] ?? [],
                'data'       => $payment,
            ];
        }

        return ['action' => 'logged'];
    }

    /* ── Precios ───────────────────────────────── */

    public static function planPrices(): array
    {
        return [
            Subscription::PLAN_MONTHLY => 49.00,
            Subscription::PLAN_YEARLY  => 470.00,
        ];
    }

    public static function priceFor(string $plan): float
    {
        return self::planPrices()[$plan] ?? 0.0;
    }

    /* ── Frontend ──────────────────────────────── */

    public function frontendConfig(): array
    {
        return [
            'publicKey' => $this->publicKey,
        ];
    }

    public function checkoutView(): string
    {
        return 'checkout.mercadopago';
    }

    /* ── Privados: MercadoPago API ─────────────── */

    /**
     * Crea un pago directo con token de tarjeta (Checkout API).
     * POST /v1/payments
     */
    private function createPayment(array $data): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('MercadoPago no configurado, omitiendo createPayment');
            return null;
        }

        try {
            $idempotencyKey = uniqid('mp_pay_', true) . '_' . time();

            $response = $this->request()
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->post("{$this->baseUrl}/v1/payments", $data);

            if ($response->successful()) {
                $payment = $response->json();
                Log::info('MercadoPago pago creado', [
                    'id'     => $payment['id'],
                    'status' => $payment['status'],
                    'amount' => $payment['transaction_amount'] ?? null,
                ]);
                return $payment;
            }

            Log::error('Error creando pago MercadoPago', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago createPayment', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Consulta el estado de un pago por ID.
     */
    private function getPayment(string $paymentId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()->get("{$this->baseUrl}/v1/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error obteniendo pago MercadoPago', ['status' => $response->status(), 'payment_id' => $paymentId]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago getPayment', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Traduce status_detail de MercadoPago a mensaje amigable en español.
     */
    private function translateStatusDetail(string $detail): string
    {
        return match ($detail) {
            'cc_rejected_bad_filled_card_number'   => 'Número de tarjeta incorrecto. Revísalo e intenta de nuevo.',
            'cc_rejected_bad_filled_date'          => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_other'         => 'Datos de la tarjeta incorrectos. Revísalos e intenta de nuevo.',
            'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto.',
            'cc_rejected_blacklist'                => 'Tu tarjeta no pudo ser procesada. Usa otra tarjeta.',
            'cc_rejected_call_for_authorize'       => 'Tu banco requiere autorización. Llama a tu banco y vuelve a intentar.',
            'cc_rejected_card_disabled'            => 'Tarjeta deshabilitada. Contacta a tu banco para activarla.',
            'cc_rejected_card_error'               => 'No se pudo procesar tu tarjeta. Intenta con otra.',
            'cc_rejected_duplicated_payment'       => 'Ya realizaste un pago por este monto. Si deseas pagar de nuevo, espera unos minutos.',
            'cc_rejected_high_risk'                => 'Tu pago fue rechazado por seguridad. Intenta con otra tarjeta.',
            'cc_rejected_insufficient_amount'      => 'Fondos insuficientes. Intenta con otra tarjeta.',
            'cc_rejected_invalid_installments'     => 'Tu tarjeta no permite cuotas. Intenta de nuevo.',
            'cc_rejected_max_attempts'             => 'Llegaste al límite de intentos. Intenta con otra tarjeta.',
            'cc_rejected_other_reason'             => 'Tu tarjeta fue rechazada. Intenta con otra.',
            default                                => 'El pago fue rechazado. Verifica tus datos o intenta con otra tarjeta.',
        };
    }

    private function request()
    {
        return Http::withToken($this->accessToken)
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->timeout(30);
    }
}
