<?php

namespace App\Livewire;

use App\Models\PremiumAuditLog;
use App\Models\Subscription;
use App\Services\PremiumAuditService;
use App\Services\SubscriptionService;
use Livewire\Component;

class MiSuscripcion extends Component
{
    public ?array $subscription = null;
    public bool $isPremium = false;
    public bool $isOnTrial = false;
    public bool $canStartTrial = false;
    public int $daysRemaining = 0;
    public ?string $planLabel = null;
    public ?string $statusLabel = null;
    public ?string $gatewayLabel = null;
    public ?string $endsAt = null;
    public ?string $startsAt = null;

    // Historial de auditoría
    public array $auditHistory = [];

    // Modal de cancelación
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
        $this->loadSubscriptionData();
    }

    public function loadSubscriptionData(): void
    {
        $user = auth()->user();
        $activeSub = $user->activeSubscription();

        $this->isPremium     = $user->isPremium();
        $this->isOnTrial     = $user->isOnTrial();
        $this->canStartTrial = $user->canStartTrial();
        $this->daysRemaining = $user->subscriptionDaysLeft();

        if ($activeSub) {
            $this->subscription = $activeSub->toArray();
            $this->startsAt     = $activeSub->starts_at?->format('d/m/Y');
            $this->endsAt       = $activeSub->ends_at?->format('d/m/Y');

            $this->planLabel = match ($activeSub->plan) {
                Subscription::PLAN_TRIAL   => 'Prueba gratuita (15 días)',
                Subscription::PLAN_MONTHLY => 'Mensual — S/ 49',
                Subscription::PLAN_YEARLY  => 'Anual — S/ 470',
                default                     => ucfirst($activeSub->plan),
            };

            $this->statusLabel = match ($activeSub->status) {
                Subscription::STATUS_ACTIVE          => 'Activa',
                Subscription::STATUS_EXPIRED         => 'Expirada',
                Subscription::STATUS_CANCELLED       => 'Cancelada',
                Subscription::STATUS_PAYMENT_PENDING => 'Pago pendiente',
                default                               => ucfirst($activeSub->status),
            };

            $this->gatewayLabel = match ($activeSub->gateway_provider) {
                'mercadopago' => 'Mercado Pago',
                'openpay'     => 'Openpay',
                default       => $activeSub->gateway_provider ? ucfirst($activeSub->gateway_provider) : 'Sin pasarela',
            };
        }

        // Cargar historial de auditoría
        $this->auditHistory = PremiumAuditLog::forUser($user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'action'     => $log->action,
                'action_label' => $log->isGranted() ? 'Otorgado' : 'Revocado',
                'source'     => $log->sourceLabel(),
                'plan'       => $log->plan,
                'amount'     => $log->amount,
                'created_at' => $log->created_at?->format('d/m/Y H:i'),
                'admin'      => $log->grantedByUser?->name,
                'days_remaining' => $log->days_remaining,
            ])
            ->toArray();
    }

    public function confirmCancel(): void
    {
        $this->showCancelModal = true;
    }

    public function cancelSubscription(): void
    {
        $user    = auth()->user();
        $service = new SubscriptionService();

        $reason = $this->cancellationReason ?: null;

        if ($service->cancel($user, $reason)) {
            $this->showCancelModal = false;
            $this->cancellationReason = '';
            $this->loadSubscriptionData();
            session()->flash('success', '✅ Tu suscripción ha sido cancelada.');
        } else {
            session()->flash('error', 'No se pudo cancelar. Contacta soporte.');
        }
    }

    public function setCancellationReason(string $reason): void
    {
        $this->cancellationReason = $reason;
    }

    public function dismissCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function render()
    {
        return view('livewire.mi-suscripcion');
    }
}
