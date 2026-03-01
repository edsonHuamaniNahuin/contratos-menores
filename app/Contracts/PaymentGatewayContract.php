<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Contrato para pasarelas de pago.
 *
 * Cada pasarela (Openpay, MercadoPago, etc.) implementa esta interfaz.
 * El PaymentGatewayManager resuelve la pasarela activa según config.
 */
interface PaymentGatewayContract
{
    /* ── Identidad ─────────────────────────────── */

    /** Slug interno: 'openpay', 'mercadopago' */
    public function name(): string;

    /** Nombre legible: 'Openpay', 'Mercado Pago' */
    public function displayName(): string;

    /** ¿Tiene credenciales configuradas? */
    public function isConfigured(): bool;

    /* ── Checkout ──────────────────────────────── */

    /**
     * Prepara el checkout para el usuario.
     *
     * @return array{
     *   type: 'redirect'|'view',
     *   url?: string,
     *   view?: string,
     *   view_data?: array
     * }
     */
    public function createCheckout(User $user, string $plan, bool $isTrial, array $extra = []): array;

    /**
     * Procesa el pago después de que el usuario completa el checkout.
     *
     * @return array{success: bool, charge_id?: string, customer_id?: string, card_id?: string, error?: string, data?: array}
     */
    public function processPayment(User $user, string $plan, bool $isTrial, array $paymentData): array;

    /* ── Cobros recurrentes ────────────────────── */

    /**
     * Cobra usando tarjeta/método almacenado (para renovaciones automáticas).
     *
     * @return array{success: bool, charge_id?: string, error?: string}
     */
    public function chargeRecurring(string $customerId, string $cardId, float $amount, string $description): array;

    /** ¿Soporta cobros recurrentes con tarjeta guardada? */
    public function supportsRecurring(): bool;

    /* ── Webhook ───────────────────────────────── */

    public function verifyWebhook(string $payload, string $signature): bool;

    /**
     * Procesa un evento de webhook.
     *
     * @return array{action: string, payment_id?: string, status?: string}
     */
    public function handleWebhookEvent(array $eventData): array;

    /* ── Precios ───────────────────────────────── */

    public static function planPrices(): array;

    public static function priceFor(string $plan): float;

    /* ── Frontend ──────────────────────────────── */

    /** Datos necesarios para la vista de checkout */
    public function frontendConfig(): array;

    /** Nombre de la vista blade del checkout */
    public function checkoutView(): string;
}
