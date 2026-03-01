<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pasarela Openpay Perú — implementa PaymentGatewayContract.
 *
 * Usa HTTP Client (Guzzle) contra la API REST de Openpay Perú.
 * Docs: https://www.openpay.pe/docs/api/
 */
class OpenpayGateway implements PaymentGatewayContract
{
    private string $merchantId;
    private string $privateKey;
    private string $publicKey;
    private bool   $production;
    private string $baseUrl;
    private string $currency;

    public function __construct()
    {
        $this->merchantId = (string) config('services.openpay.merchant_id', '');
        $this->privateKey = (string) config('services.openpay.private_key', '');
        $this->publicKey  = (string) config('services.openpay.public_key', '');
        $this->production = (bool) config('services.openpay.production', false);
        $this->currency   = (string) config('services.openpay.currency', 'PEN');

        $this->baseUrl = $this->production
            ? 'https://api.openpay.pe/v1'
            : 'https://sandbox-api.openpay.pe/v1';
    }

    /* ── Identidad ─────────────────────────────── */

    public function name(): string
    {
        return 'openpay';
    }

    public function displayName(): string
    {
        return 'Openpay';
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->privateKey);
    }

    /* ── Checkout ──────────────────────────────── */

    public function createCheckout(User $user, string $plan, bool $isTrial, array $extra = []): array
    {
        return [
            'type'      => 'view',
            'view'      => $this->checkoutView(),
            'view_data' => array_merge([
                'plan'       => $plan,
                'price'      => static::priceFor($plan),
                'isTrial'    => $isTrial,
                'gateway'    => $this->name(),
            ], $this->frontendConfig()),
        ];
    }

    public function processPayment(User $user, string $plan, bool $isTrial, array $paymentData): array
    {
        $tokenId          = $paymentData['token_id'] ?? '';
        $deviceSessionId  = $paymentData['device_session_id'] ?? '';
        $price            = static::priceFor($plan);

        // 1. Crear customer
        $customerId = $paymentData['existing_customer_id'] ?? null;
        if (!$customerId) {
            $customerId = $this->createCustomer($user);
        }
        if (!$customerId) {
            return ['success' => false, 'error' => 'No se pudo crear tu perfil de pago. Intenta de nuevo.'];
        }

        // 2. Registrar tarjeta
        $cardId = $this->storeCard($customerId, $tokenId, $deviceSessionId);

        if ($isTrial) {
            if (!$cardId) {
                return ['success' => false, 'error' => 'No se pudo registrar la tarjeta. Verifica tus datos e intenta de nuevo.'];
            }

            return [
                'success'     => true,
                'charge_id'   => null,
                'customer_id' => $customerId,
                'card_id'     => $cardId,
                'amount'      => 0,
                'data'        => ['trial' => true],
            ];
        }

        // 3. Cobrar
        if ($price <= 0) {
            return ['success' => false, 'error' => 'Plan no válido.'];
        }

        $description = 'Vigilante SEACE — Plan ' . ($plan === 'yearly' ? 'Anual' : 'Mensual');

        if ($cardId) {
            $result = $this->chargeCustomerCard($customerId, $cardId, $price, $description);
        } else {
            $result = $this->chargeWithToken($tokenId, $price, $description, $customerId, $deviceSessionId);
        }

        if (!$result['success']) {
            return $result;
        }

        return [
            'success'     => true,
            'charge_id'   => $result['charge_id'],
            'customer_id' => $customerId,
            'card_id'     => $cardId,
            'amount'      => $price,
            'data'        => $result['data'] ?? null,
        ];
    }

    /* ── Cobros recurrentes ────────────────────── */

    public function chargeRecurring(string $customerId, string $cardId, float $amount, string $description): array
    {
        return $this->chargeCustomerCard($customerId, $cardId, $amount, $description);
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    /* ── Webhook ───────────────────────────────── */

    public function verifyWebhook(string $payload, string $signature): bool
    {
        $secret = config('services.openpay.webhook_secret', '');
        if (empty($secret)) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function handleWebhookEvent(array $eventData): array
    {
        Log::info('Openpay webhook recibido', $eventData);
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
            'merchantId' => $this->merchantId,
            'publicKey'  => $this->publicKey,
            'sandbox'    => !$this->production,
        ];
    }

    public function checkoutView(): string
    {
        return 'checkout.openpay';
    }

    /* ── Privados: Openpay API ─────────────────── */

    private function createCustomer(User $user): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()->post(
                "{$this->baseUrl}/{$this->merchantId}/customers",
                [
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'requires_account' => false,
                    'external_id'      => "user_{$user->id}",
                ]
            );

            if ($response->successful()) {
                $id = $response->json('id');
                Log::info('Openpay customer creado', ['user_id' => $user->id, 'customer_id' => $id]);
                return $id;
            }

            Log::error('Error creando customer Openpay', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción Openpay createCustomer', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeCard(string $customerId, string $tokenId, string $deviceSessionId = ''): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request()->post(
                "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/cards",
                ['token_id' => $tokenId, 'device_session_id' => $deviceSessionId]
            );

            if ($response->successful()) {
                $cardId = $response->json('id');
                Log::info('Tarjeta almacenada Openpay', ['customer_id' => $customerId, 'card_id' => $cardId]);
                return $cardId;
            }

            Log::error('Error almacenando tarjeta Openpay', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción Openpay storeCard', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function chargeCustomerCard(string $customerId, string $cardId, float $amount, string $description): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Openpay no configurado'];
        }

        try {
            $response = $this->request()->post(
                "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/charges",
                [
                    'source_id'   => $cardId,
                    'method'      => 'card',
                    'amount'      => round($amount, 2),
                    'currency'    => $this->currency,
                    'description' => $description,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cargo Openpay exitoso', ['charge_id' => $data['id'], 'amount' => $amount]);
                return ['success' => true, 'charge_id' => $data['id'], 'data' => $data];
            }

            $error = $response->json();
            Log::error('Error cargo Openpay', ['status' => $response->status(), 'error' => $error]);
            return ['success' => false, 'error' => $error['description'] ?? 'Error al procesar el cobro.', 'data' => $error];
        } catch (\Exception $e) {
            Log::error('Excepción Openpay chargeCustomerCard', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function chargeWithToken(string $tokenId, float $amount, string $description, ?string $customerId = null, string $deviceSessionId = ''): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Openpay no configurado'];
        }

        try {
            $endpoint = $customerId
                ? "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/charges"
                : "{$this->baseUrl}/{$this->merchantId}/charges";

            $response = $this->request()->post($endpoint, [
                'source_id'         => $tokenId,
                'method'            => 'card',
                'amount'            => round($amount, 2),
                'currency'          => $this->currency,
                'description'       => $description,
                'device_session_id' => $deviceSessionId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cargo con token Openpay exitoso', ['charge_id' => $data['id'], 'amount' => $amount]);
                return ['success' => true, 'charge_id' => $data['id'], 'data' => $data];
            }

            $error = $response->json();
            Log::error('Error cargo con token Openpay', ['status' => $response->status(), 'error' => $error]);
            return ['success' => false, 'error' => $error['description'] ?? 'Error al procesar el pago', 'data' => $error];
        } catch (\Exception $e) {
            Log::error('Excepción Openpay chargeWithToken', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function request()
    {
        return Http::withBasicAuth($this->privateKey, '')
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->timeout(30);
    }
}
