<?php

namespace App\Livewire;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ClientesMercadoPago extends Component
{
    public array $clientes = [];
    public bool $cargando = false;
    public string $error = '';
    public int $totalClientes = 0;
    public int $totalTarjetas = 0;

    public function mount(): void
    {
        $this->cargarClientes();
    }

    public function cargarClientes(): void
    {
        $this->cargando = true;
        $this->error = '';

        try {
            $token = config('services.mercadopago.access_token');
            if (empty($token)) {
                $this->error = 'MercadoPago no está configurado.';
                $this->cargando = false;
                return;
            }

            // Obtener todos los usuarios con suscripciones en MercadoPago
            $userIds = Subscription::where('gateway_provider', 'mercadopago')
                ->whereNotNull('gateway_customer_id')
                ->distinct()
                ->pluck('user_id');

            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $this->clientes = [];

            foreach ($users as $user) {
                $sub = $user->subscriptions()
                    ->where('gateway_provider', 'mercadopago')
                    ->whereNotNull('gateway_customer_id')
                    ->latest()
                    ->first();

                if (!$sub || !$sub->gateway_customer_id) continue;

                // Consultar customer a MP
                $customerData = $this->getCustomer($sub->gateway_customer_id);
                if (!$customerData) continue;

                $cards = [];
                foreach ($customerData['cards'] ?? [] as $card) {
                    $cards[] = [
                        'id'               => $card['id'] ?? null,
                        'marca'            => $card['payment_method']['name'] ?? 'Desconocida',
                        'ultimos_digitos'  => $card['last_four_digits'] ?? '****',
                        'vencimiento'      => ($card['expiration_month'] ?? '?') . '/' . ($card['expiration_year'] ?? '?'),
                        'tipo'             => $card['payment_method']['payment_type_id'] ?? 'card',
                    ];
                }

                $this->clientes[] = [
                    'user_id'        => $user->id,
                    'nombre'         => $user->name ?? $user->email,
                    'email'          => $customerData['email'] ?? $user->email,
                    'mp_customer_id' => $sub->gateway_customer_id,
                    'plan'           => $sub->plan,
                    'estado'         => $sub->status,
                    'vence'          => $sub->ends_at?->format('d/m/Y'),
                    'tarjetas'       => $cards,
                ];
            }
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }

        $this->cargando = false;

        // Contar totales
        $this->totalClientes = count($this->clientes);
        $this->totalTarjetas = 0;
        foreach ($this->clientes as $c) {
            $this->totalTarjetas += count($c['tarjetas'] ?? []);
        }
    }

    private function getCustomer(string $customerId): ?array
    {
        static $cache = [];

        if (isset($cache[$customerId])) {
            return $cache[$customerId];
        }

        try {
            $response = Http::withToken(config('services.mercadopago.access_token'))
                ->get("https://api.mercadopago.com/v1/customers/{$customerId}");

            if ($response->successful()) {
                return $cache[$customerId] = $response->json();
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    public function render()
    {
        return view('livewire.clientes-mercado-pago');
    }
}
