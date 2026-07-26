<?php

namespace App\Livewire;

use App\Models\PagoYape;
use App\Models\Subscription;
use App\Services\Payments\MercadoPagoGateway;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Billing extends Component
{
    use WithFileUploads;
    public ?array $subscription = null;
    public bool $isPremium = false;
    public bool $isOnTrial = false;
    public bool $autoRenew = true;
    public int $daysRemaining = 0;
    public ?string $planLabel = null;
    public ?string $endsAt = null;
    public ?string $startsAt = null;

    // Tarjeta guardada (ofuscada)
    public ?array $savedCard = null;

    // Historial de pagos
    public array $billingHistory = [];

    // Cancelación
    public bool $showCancelModal = false;
    public string $cancellationReason = '';
    public array $cancellationReasons = [
        'Es muy caro' => 'Es muy caro',
        'No lo uso lo suficiente' => 'No lo uso lo suficiente',
        'Encontré una alternativa mejor' => 'Encontré una alternativa mejor',
        'Problemas técnicos' => 'Problemas técnicos',
        'No entiendo cómo funciona' => 'No entiendo cómo funciona',
        'Ya no necesito el servicio' => 'Ya no necesito el servicio',
        'Problemas con el pago' => 'Problemas con el pago',
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $user = auth()->user();
        $activeSub = $user->activeSubscription();

        $this->isPremium     = $user->isPremium();
        $this->isOnTrial     = $user->isOnTrial();
        $this->daysRemaining = $user->subscriptionDaysLeft();

        if ($activeSub) {
            $this->subscription = $activeSub->toArray();
            $this->autoRenew    = (bool) $activeSub->auto_renew;
            $this->startsAt     = $activeSub->starts_at?->format('d/m/Y');
            $this->endsAt       = $activeSub->ends_at?->format('d/m/Y');

            $this->planLabel = match ($activeSub->plan) {
                Subscription::PLAN_TRIAL   => 'Prueba gratuita (15 días)',
                Subscription::PLAN_MONTHLY => 'Mensual — S/ 49',
                Subscription::PLAN_YEARLY  => 'Anual — S/ 470',
                Subscription::PLAN_MAYORES_PREMIUM => 'Premium + Contratos Mayores — S/ 68',
                default                    => ucfirst($activeSub->plan),
            };

            // Check for pending Yape payment
            $this->hasPendingYape = PagoYape::where('user_id', $user->id)
                ->where('estado', PagoYape::ESTADO_PENDIENTE)
                ->exists();

            // Cargar info de tarjeta desde MercadoPago
            $this->loadSavedCard($activeSub);

            // Cargar historial de pagos
            $this->loadBillingHistory($user);
        }
    }

    private function loadSavedCard(Subscription $sub): void
    {
        if (!$sub->gateway_customer_id) {
            return;
        }

        try {
            $response = Http::withToken(config('services.mercadopago.access_token'))
                ->get("https://api.mercadopago.com/v1/customers/{$sub->gateway_customer_id}/cards");

            if ($response->successful()) {
                $cards = $response->json();
                if (!empty($cards)) {
                    $card = $cards[0];
                    $this->savedCard = [
                        'id'               => $card['id'] ?? null,
                        'brand'            => $card['payment_method']['name'] ?? 'Desconocida',
                        'type'             => $card['payment_method']['payment_type_id'] ?? 'card',
                        'last_four'        => $card['last_four_digits'] ?? '****',
                        'exp_month'        => $card['expiration_month'] ?? null,
                        'exp_year'         => $card['expiration_year'] ?? null,
                        'issuer'           => $card['issuer']['name'] ?? '',
                        'first_six'        => $card['first_six_digits'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function loadBillingHistory($user): void
    {
        $this->billingHistory = $user->subscriptions()
            ->where('amount', '>', 0)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'date'      => $s->created_at?->format('d/m/Y H:i'),
                'plan'      => match ($s->plan) {
                    'monthly' => 'Mensual',
                    'yearly'  => 'Anual',
                    default   => ucfirst($s->plan),
                },
                'amount'    => number_format($s->amount, 2),
                'status'    => $s->status === 'active' ? 'Pagado' : ucfirst($s->status),
                'charge_id' => $s->gateway_charge_id,
            ])
            ->toArray();
    }

    public function toggleAutoRenew(): void
    {
        $user    = auth()->user();
        $service = new SubscriptionService();
        $result  = $service->toggleAutoRenew($user);

        if ($result) {
            $this->autoRenew = $result->auto_renew;
            session()->flash('success', $this->autoRenew
                ? '✅ Renovación automática activada'
                : '⚠️ Renovación automática desactivada');
        } else {
            session()->flash('error', 'No tienes una suscripción activa.');
        }
    }

    public function confirmCancel(): void
    {
        $this->showCancelModal = true;
    }

    public function setCancellationReason(string $reason): void
    {
        $this->cancellationReason = $reason;
    }

    public function cancelSubscription(): void
    {
        $user    = auth()->user();
        $service = new SubscriptionService();
        $reason  = $this->cancellationReason ?: null;

        if ($service->cancel($user, $reason)) {
            $this->showCancelModal    = false;
            $this->cancellationReason = '';
            $this->loadData();
            session()->flash('success', '✅ Tu suscripción ha sido cancelada.');
        } else {
            session()->flash('error', 'No se pudo cancelar. Contacta soporte.');
        }
    }

    public function dismissCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    // Yape payment
    public bool $showYapeForm = false;
    public $comprobante = null;
    public string $referenciaAdicional = '';
    public string $telefonoRenovacion = '';
    public bool $hasPendingYape = false;

    public function toggleYapeForm(): void
    {
        $this->showYapeForm = !$this->showYapeForm;
        $this->comprobante = null;
        $this->referenciaAdicional = '';
        $this->telefonoRenovacion = '';
    }

    public function submitYapePago(): void
    {
        $user = auth()->user();
        if (!$user || !$this->isPremium) return;

        $this->validate([
            'comprobante' => 'required|image|mimes:jpeg,png,webp|max:5120',
            'telefonoRenovacion' => 'nullable|string|max:9',
            'referenciaAdicional' => 'nullable|string|max:500',
        ], [
            'comprobante.required' => 'Debes adjuntar el screenshot del pago.',
            'comprobante.image' => 'El archivo debe ser una imagen.',
            'comprobante.mimes' => 'Solo JPG, PNG o WebP.',
            'comprobante.max' => 'Máximo 5 MB.',
        ]);

        $pending = PagoYape::where('user_id', $user->id)
            ->where('estado', PagoYape::ESTADO_PENDIENTE)
            ->first();
        if ($pending) {
            session()->flash('error', 'Ya tienes un pago pendiente de validación.');
            return;
        }

        $file = $this->comprobante;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            session()->flash('error', 'Formato no permitido.');
            return;
        }

        $nombreOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $nombreOriginal = substr($nombreOriginal, -100);
        $identificador = date('Ymd_His') . '_' . strtolower(Str::random(12));
        $carpeta = 'comprobantes/yape/' . $user->id;
        $filename = "pago_{$identificador}." . strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs($carpeta, $filename, 'public');

        $plan = $this->subscription['plan'] ?? Subscription::PLAN_MONTHLY;
        $prices = MercadoPagoGateway::planPrices();
        $monto = $prices[$plan] ?? 0;

        PagoYape::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'tipo' => PagoYape::TIPO_RENOVACION,
            'monto' => $monto,
            'comprobante' => $path,
            'comprobante_dir' => $carpeta,
            'nombre_original' => $nombreOriginal,
            'estado' => PagoYape::ESTADO_PENDIENTE,
            'referencia_adicional' => $this->referenciaAdicional,
            'telefono' => $this->telefonoRenovacion,
        ]);

        $this->showYapeForm = false;
        $this->comprobante = null;
        $this->referenciaAdicional = '';
        $this->telefonoRenovacion = '';

        session()->flash('success', 'Comprobante enviado. Tu pago será validado en máximo 24 horas.');
    }

    public function render()
    {
        return view('livewire.billing');
    }
}
