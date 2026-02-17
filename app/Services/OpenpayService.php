<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integración con la API REST de Openpay Perú.
 *
 * Usa HTTP Client (Guzzle) en vez del SDK PHP para mantener el stack
 * aprobado sin dependencias extra.
 *
 * Docs: https://www.openpay.pe/docs/api/
 */
class OpenpayService
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

        // Openpay Perú: sandbox vs producción
        $this->baseUrl = $this->production
            ? 'https://api.openpay.pe/v1'
            : 'https://sandbox-api.openpay.pe/v1';
    }

    /**
     * Verifica si el servicio está configurado.
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->privateKey);
    }

    /* ────────────────────────────────
     |  Customers
     |──────────────────────────────── */

    /**
     * Crea un customer en Openpay y retorna su ID.
     */
    public function createCustomer(User $user): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('Openpay no configurado, omitiendo createCustomer');
            return null;
        }

        try {
            $response = $this->request()->post(
                "{$this->baseUrl}/{$this->merchantId}/customers",
                [
                    'name'           => $user->name,
                    'email'          => $user->email,
                    'requires_account' => false,
                    'external_id'    => "user_{$user->id}",
                ]
            );

            if ($response->successful()) {
                $customerId = $response->json('id');
                Log::info('Openpay customer creado', [
                    'user_id'     => $user->id,
                    'customer_id' => $customerId,
                ]);
                return $customerId;
            }

            Log::error('Error creando customer Openpay', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción Openpay createCustomer', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /* ────────────────────────────────
     |  Charges (Cobros)
     |──────────────────────────────── */

    /**
     * Registra (almacena) una tarjeta en un customer usando el token de Openpay.js.
     *
     * Endpoint: POST /customers/{customer_id}/cards
     * No genera cargo, solo guarda la tarjeta para cobros futuros.
     *
     * @return string|null  ID de la tarjeta almacenada
     */
    public function addCardToCustomer(string $customerId, string $tokenId, string $deviceSessionId = ''): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('Openpay no configurado, omitiendo addCardToCustomer');
            return null;
        }

        try {
            $payload = [
                'token_id'          => $tokenId,
                'device_session_id' => $deviceSessionId,
            ];

            $response = $this->request()->post(
                "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/cards",
                $payload,
            );

            if ($response->successful()) {
                $cardId = $response->json('id');
                Log::info('Tarjeta almacenada en Openpay', [
                    'customer_id' => $customerId,
                    'card_id'     => $cardId,
                ]);
                return $cardId;
            }

            Log::error('Error almacenando tarjeta Openpay', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Excepción Openpay addCardToCustomer', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Cobra a un customer usando su tarjeta almacenada (cargo recurrente).
     *
     * Endpoint: POST /customers/{customer_id}/charges  con source_id = card_id
     *
     * @return array{success: bool, charge_id?: string, error?: string, data?: array}
     */
    public function chargeCustomerCard(
        string $customerId,
        string $cardId,
        float $amount,
        string $description,
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Openpay no configurado'];
        }

        try {
            $endpoint = "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/charges";

            $payload = [
                'source_id'   => $cardId,
                'method'      => 'card',
                'amount'      => round($amount, 2),
                'currency'    => $this->currency,
                'description' => $description,
            ];

            $response = $this->request()->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cargo recurrente Openpay exitoso', [
                    'charge_id'   => $data['id'],
                    'customer_id' => $customerId,
                    'amount'      => $amount,
                ]);
                return [
                    'success'   => true,
                    'charge_id' => $data['id'],
                    'data'      => $data,
                ];
            }

            $errorBody = $response->json();
            Log::error('Error en cargo recurrente Openpay', [
                'status' => $response->status(),
                'error'  => $errorBody,
            ]);
            return [
                'success' => false,
                'error'   => $errorBody['description'] ?? 'Error al procesar el cobro recurrente.',
                'data'    => $errorBody,
            ];
        } catch (\Exception $e) {
            Log::error('Excepción Openpay chargeCustomerCard', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crea un cargo con tarjeta (token generado en frontend con OpenPay.js).
     *
     * @param  string      $tokenId      Token de tarjeta generado por Openpay.js
     * @param  float       $amount       Monto en PEN
     * @param  string      $description  Descripción del cargo
     * @param  string|null $customerId   ID del customer en Openpay
     * @param  string      $deviceSessionId  Device session ID del antifraude
     * @return array{success: bool, charge_id?: string, error?: string, data?: array}
     */
    public function createCharge(
        string $tokenId,
        float $amount,
        string $description,
        ?string $customerId = null,
        string $deviceSessionId = ''
    ): array {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Openpay no configurado'];
        }

        try {
            $endpoint = $customerId
                ? "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/charges"
                : "{$this->baseUrl}/{$this->merchantId}/charges";

            $payload = [
                'source_id'         => $tokenId,
                'method'            => 'card',
                'amount'            => round($amount, 2),
                'currency'          => $this->currency,
                'description'       => $description,
                'device_session_id' => $deviceSessionId,
            ];

            $response = $this->request()->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Cargo Openpay exitoso', [
                    'charge_id' => $data['id'],
                    'amount'    => $amount,
                ]);

                return [
                    'success'   => true,
                    'charge_id' => $data['id'],
                    'data'      => $data,
                ];
            }

            $errorBody = $response->json();
            Log::error('Error en cargo Openpay', [
                'status'    => $response->status(),
                'error'     => $errorBody,
            ]);

            return [
                'success' => false,
                'error'   => $errorBody['description'] ?? 'Error al procesar el pago',
                'data'    => $errorBody,
            ];
        } catch (\Exception $e) {
            Log::error('Excepción Openpay createCharge', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene detalles de un cargo.
     */
    public function getCharge(string $chargeId, ?string $customerId = null): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $endpoint = $customerId
                ? "{$this->baseUrl}/{$this->merchantId}/customers/{$customerId}/charges/{$chargeId}"
                : "{$this->baseUrl}/{$this->merchantId}/charges/{$chargeId}";

            $response = $this->request()->get($endpoint);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error('Excepción Openpay getCharge', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /* ────────────────────────────────
     |  Webhook verification
     |──────────────────────────────── */

    /**
     * Verifica la firma del webhook de Openpay.
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $secret = config('services.openpay.webhook_secret', '');
        if (empty($secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /* ────────────────────────────────
     |  Plan prices
     |──────────────────────────────── */

    /**
     * Retorna precios de los planes.
     */
    public static function planPrices(): array
    {
        return [
            Subscription::PLAN_MONTHLY => 49.00,
            Subscription::PLAN_YEARLY  => 470.00, // ~2 meses gratis
        ];
    }

    /**
     * Retorna el precio de un plan específico.
     */
    public static function priceFor(string $plan): float
    {
        return self::planPrices()[$plan] ?? 0.0;
    }

    /* ────────────────────────────────
     |  HTTP base
     |──────────────────────────────── */

    /**
     * Configura HTTP Client con auth básica (private key).
     */
    private function request()
    {
        return Http::withBasicAuth($this->privateKey, '')
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Retorna la public key para el frontend (OpenPay.js).
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Retorna el merchant ID para el frontend.
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * Retorna si es sandbox o producción.
     */
    public function isSandbox(): bool
    {
        return !$this->production;
    }
}
