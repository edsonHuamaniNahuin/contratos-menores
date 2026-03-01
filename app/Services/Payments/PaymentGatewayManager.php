<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayContract;
use InvalidArgumentException;

/**
 * Resuelve la pasarela de pago activa según configuración.
 *
 * Uso:
 *   $gateway = app(PaymentGatewayManager::class)->driver();
 *   $gateway = app(PaymentGatewayManager::class)->driver('mercadopago');
 */
class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayContract>> */
    private array $drivers = [
        'openpay'     => OpenpayGateway::class,
        'mercadopago' => MercadoPagoGateway::class,
    ];

    /** Resolved instances cache */
    private array $resolved = [];

    /**
     * Obtiene la pasarela por nombre o la predeterminada.
     */
    public function driver(?string $name = null): PaymentGatewayContract
    {
        $name = $name ?? $this->getDefaultDriver();

        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Pasarela de pago [{$name}] no soportada. Disponibles: " . implode(', ', array_keys($this->drivers)));
        }

        if (!isset($this->resolved[$name])) {
            $this->resolved[$name] = new $this->drivers[$name]();
        }

        return $this->resolved[$name];
    }

    /**
     * Driver predeterminado desde config.
     */
    public function getDefaultDriver(): string
    {
        return (string) config('services.payment_gateway', 'mercadopago');
    }

    /**
     * Lista de pasarelas disponibles (nombre => displayName).
     */
    public function availableDrivers(): array
    {
        $list = [];
        foreach ($this->drivers as $key => $class) {
            /** @var PaymentGatewayContract $instance */
            $instance = $this->driver($key);
            $list[$key] = [
                'name'         => $key,
                'display_name' => $instance->displayName(),
                'configured'   => $instance->isConfigured(),
            ];
        }
        return $list;
    }

    /**
     * Registra una nueva pasarela.
     */
    public function extend(string $name, string $class): void
    {
        $this->drivers[$name] = $class;
    }
}
