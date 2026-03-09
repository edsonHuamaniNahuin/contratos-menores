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
        // Trial TAMBIÉN muestra el formulario de tarjeta (ya no se activa directo)
        if ($isTrial) {
            return [
                'type'      => 'view',
                'view'      => $this->checkoutView(),
                'view_data' => array_merge([
                    'plan'    => $plan,
                    'price'   => 0,
                    'isTrial' => true,
                    'gateway' => $this->name(),
                    'preferenceId' => null,
                ], $this->frontendConfig()),
            ];
        }

        // Crear preferencia de Checkout Pro para métodos alternativos (transferencia, efectivo, etc.)
        $preferenceId = null;
        $initPoint    = null;
        try {
            $preference = $this->createPreference($user, $plan);
            $preferenceId = $preference['id'] ?? null;
            // init_point = URL de redirección a Checkout Pro de MercadoPago
            // sandbox_init_point para entorno sandbox, init_point para producción
            $initPoint = $preference['sandbox_init_point'] ?? $preference['init_point'] ?? null;
        } catch (\Exception $e) {
            Log::warning('MercadoPago: No se pudo crear preferencia Checkout Pro', [
                'error' => $e->getMessage(),
            ]);
        }

        // Retornar vista in-page con soporte para tarjeta + otros métodos
        return [
            'type'      => 'view',
            'view'      => $this->checkoutView(),
            'view_data' => array_merge([
                'plan'          => $plan,
                'price'         => static::priceFor($plan),
                'isTrial'       => $isTrial,
                'gateway'       => $this->name(),
                'preferenceId'  => $preferenceId,
                'initPoint'     => $initPoint,
            ], $this->frontendConfig()),
        ];
    }

    public function processPayment(User $user, string $plan, bool $isTrial, array $paymentData): array
    {
        // ── Trial con tarjeta: solo validar y guardar la tarjeta, NO cobrar ──
        if ($isTrial) {
            $token = $paymentData['token_id'] ?? null;
            if (!$token) {
                return ['success' => false, 'error' => 'Se requiere ingresar una tarjeta para activar el trial.'];
            }

            // Crear customer en MercadoPago + guardar tarjeta
            $customerResult = $this->createOrGetCustomer($user);
            if (!$customerResult) {
                return ['success' => false, 'error' => 'No se pudo registrar al cliente. Intenta de nuevo.'];
            }

            $cardResult = $this->saveCardToCustomer($customerResult['id'], $token);
            if (!$cardResult) {
                return ['success' => false, 'error' => 'No se pudo guardar la tarjeta. Verifica los datos e intenta de nuevo.'];
            }

            return [
                'success'     => true,
                'charge_id'   => null,
                'customer_id' => (string) $customerResult['id'],
                'card_id'     => (string) $cardResult['id'],
                'amount'      => 0,
                'payment_method' => 'card',
                'data'        => ['trial' => true, 'card_last_four' => $cardResult['last_four_digits'] ?? null],
            ];
        }

        // ── Pago via Checkout Pro callback (transferencia, efectivo, etc.) ──
        $paymentId = $paymentData['payment_id'] ?? null;
        if ($paymentId) {
            return $this->processCheckoutProPayment($paymentId, $user, $plan);
        }

        // ── Pago directo con token de tarjeta (Checkout API transparente) ──
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
                'success'        => true,
                'charge_id'      => (string) $paymentResult['id'],
                'customer_id'    => (string) ($paymentResult['payer']['id'] ?? $user->id),
                'card_id'        => null,
                'amount'         => (float) ($paymentResult['transaction_amount'] ?? $price),
                'payment_method' => $paymentResult['payment_method_id'] ?? 'card',
                'data'           => $paymentResult,
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

    /**
     * Cobra una tarjeta guardada de un customer de MercadoPago.
     * Usa POST /v1/payments con payer.type=customer y token=card_id.
     */
    public function chargeRecurring(string $customerId, string $cardId, float $amount, string $description): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'MercadoPago no configurado.'];
        }

        try {
            $idempotencyKey = uniqid('mp_rec_', true) . '_' . time();

            $paymentData = [
                'transaction_amount' => round($amount, 2),
                'description'        => $description,
                'installments'       => 1,
                'token'              => $cardId,
                'payer'              => [
                    'type' => 'customer',
                    'id'   => $customerId,
                ],
                'statement_descriptor' => 'VIGILANTE SEACE',
            ];

            $response = $this->request()
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->post("{$this->baseUrl}/v1/payments", $paymentData);

            if ($response->successful()) {
                $payment = $response->json();
                $status  = $payment['status'] ?? 'unknown';

                if ($status === 'approved') {
                    Log::info('MercadoPago cobro recurrente exitoso', [
                        'payment_id'  => $payment['id'],
                        'customer_id' => $customerId,
                        'amount'      => $amount,
                    ]);

                    return [
                        'success'   => true,
                        'charge_id' => (string) $payment['id'],
                        'data'      => $payment,
                    ];
                }

                Log::warning('MercadoPago cobro recurrente no aprobado', [
                    'status'        => $status,
                    'status_detail' => $payment['status_detail'] ?? 'unknown',
                    'customer_id'   => $customerId,
                ]);

                return [
                    'success' => false,
                    'error'   => "Pago no aprobado (status: {$status}). " . $this->translateStatusDetail($payment['status_detail'] ?? ''),
                ];
            }

            Log::error('MercadoPago error en cobro recurrente', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'error' => 'Error al procesar el cobro recurrente.'];
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago chargeRecurring', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error inesperado: ' . $e->getMessage()];
        }
    }

    public function supportsRecurring(): bool
    {
        return true;
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
     * Crea o recupera un customer en MercadoPago para el usuario.
     * POST /v1/customers  o  GET /v1/customers/search?email=...
     */
    private function createOrGetCustomer(User $user): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            // Buscar si ya existe un customer con este email
            $searchResponse = $this->request()
                ->get("{$this->baseUrl}/v1/customers/search", ['email' => $user->email]);

            if ($searchResponse->successful()) {
                $results = $searchResponse->json('results', []);
                if (!empty($results)) {
                    Log::info('MercadoPago customer existente encontrado', [
                        'customer_id' => $results[0]['id'],
                        'user_id'     => $user->id,
                    ]);
                    return $results[0];
                }
            }

            // Crear nuevo customer
            $createResponse = $this->request()
                ->post("{$this->baseUrl}/v1/customers", [
                    'email'      => $user->email,
                    'first_name' => $user->name,
                    'description' => "Vigilante SEACE - User #{$user->id}",
                ]);

            if ($createResponse->successful()) {
                $customer = $createResponse->json();
                Log::info('MercadoPago customer creado', [
                    'customer_id' => $customer['id'],
                    'user_id'     => $user->id,
                ]);
                return $customer;
            }

            Log::error('Error creando customer MercadoPago', [
                'status' => $createResponse->status(),
                'body'   => $createResponse->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago createOrGetCustomer', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Guarda una tarjeta tokenizada en un customer de MercadoPago.
     * POST /v1/customers/{customer_id}/cards
     */
    private function saveCardToCustomer(string $customerId, string $cardToken): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()
                ->post("{$this->baseUrl}/v1/customers/{$customerId}/cards", [
                    'token' => $cardToken,
                ]);

            if ($response->successful()) {
                $card = $response->json();
                Log::info('MercadoPago tarjeta guardada', [
                    'customer_id'    => $customerId,
                    'card_id'        => $card['id'] ?? null,
                    'last_four'      => $card['last_four_digits'] ?? null,
                    'payment_method' => $card['payment_method']['id'] ?? null,
                ]);
                return $card;
            }

            Log::error('Error guardando tarjeta MercadoPago', [
                'customer_id' => $customerId,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago saveCardToCustomer', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Crea una preferencia de Checkout Pro para pagos con transferencia, efectivo, etc.
     * POST /checkout/preferences
     */
    private function createPreference(User $user, string $plan): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $price     = static::priceFor($plan);
        $planLabel = $plan === 'yearly' ? 'Anual' : 'Mensual';

        $successUrl = route('planes.callback') . '?gateway=mercadopago&status=approved';
        $failureUrl = route('planes.callback') . '?gateway=mercadopago&status=failure';
        $pendingUrl = route('planes.callback') . '?gateway=mercadopago&status=pending';

        $data = [
            'items' => [
                [
                    'title'       => "Vigilante SEACE — Plan {$planLabel}",
                    'quantity'    => 1,
                    'unit_price'  => round($price, 2),
                    'currency_id' => 'PEN',
                    'description' => "Suscripción Premium {$planLabel} — Licitaciones MYPe",
                ],
            ],
            'payer' => [
                'email' => $user->email,
                'name'  => $user->name,
            ],
            'back_urls' => [
                'success' => $successUrl,
                'failure' => $failureUrl,
                'pending' => $pendingUrl,
            ],
            'statement_descriptor' => 'VIGILANTE SEACE',
            'external_reference'   => "user_{$user->id}_plan_{$plan}_" . time(),
            'metadata' => [
                'user_id' => $user->id,
                'plan'    => $plan,
            ],
            // Habilitar todos los métodos de pago de MercadoPago
            'payment_methods' => [
                'excluded_payment_types' => [],
                'installments'           => 1,
            ],
        ];

        // auto_return requiere back_urls con HTTPS; en dev (http) lo omitimos
        if (str_starts_with($successUrl, 'https://')) {
            $data['auto_return'] = 'approved';
        }

        try {
            $response = $this->request()
                ->post("{$this->baseUrl}/checkout/preferences", $data);

            if ($response->successful()) {
                $preference = $response->json();
                Log::info('MercadoPago preferencia creada', [
                    'id'     => $preference['id'] ?? null,
                    'user_id' => $user->id,
                    'plan'    => $plan,
                ]);
                return $preference;
            }

            Log::error('Error creando preferencia MercadoPago', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción MercadoPago createPreference', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Procesa un pago completado via Checkout Pro (callback).
     * Consulta la API para verificar el estado real del pago.
     */
    private function processCheckoutProPayment(string $paymentId, User $user, string $plan): array
    {
        $payment = $this->getPayment($paymentId);

        if (!$payment) {
            return ['success' => false, 'error' => 'No se pudo verificar el pago. Contacta soporte.'];
        }

        $status = $payment['status'] ?? 'unknown';
        $price  = static::priceFor($plan);

        if ($status === 'approved') {
            $paymentMethodId = $payment['payment_method_id'] ?? 'unknown';
            $paymentTypeId   = $payment['payment_type_id'] ?? 'unknown';

            // Determinar nombre amigable del método de pago
            $paymentMethodLabel = match (true) {
                $paymentTypeId === 'bank_transfer'           => 'transferencia',
                $paymentTypeId === 'credit_card'             => 'tarjeta_credito',
                $paymentTypeId === 'debit_card'              => 'tarjeta_debito',
                $paymentTypeId === 'account_money'           => 'dinero_cuenta',
                $paymentTypeId === 'ticket'                  => 'efectivo',
                default                                      => $paymentMethodId,
            };

            return [
                'success'        => true,
                'charge_id'      => (string) $payment['id'],
                'customer_id'    => (string) ($payment['payer']['id'] ?? $user->id),
                'card_id'        => null,
                'amount'         => (float) ($payment['transaction_amount'] ?? $price),
                'payment_method' => $paymentMethodLabel,
                'data'           => $payment,
            ];
        }

        if ($status === 'in_process' || $status === 'pending') {
            return [
                'success' => false,
                'error'   => 'El pago está pendiente de aprobación. Te notificaremos cuando se confirme.',
            ];
        }

        return [
            'success' => false,
            'error'   => $this->translateStatusDetail($payment['status_detail'] ?? ''),
            'data'    => $payment,
        ];
    }

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
