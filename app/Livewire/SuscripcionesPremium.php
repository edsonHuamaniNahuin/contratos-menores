<?php

namespace App\Livewire;

use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Livewire\Component;
use Livewire\WithPagination;

class SuscripcionesPremium extends Component
{
    use WithPagination;

    // Filtros
    public string $search       = '';
    public string $filterStatus = '';
    public string $filterPlan   = '';

    // Modal: otorgar premium
    public bool   $showGrantModal  = false;
    public ?int   $grantUserId     = null;
    public string $grantUserName   = '';
    public int    $grantDays       = 30;
    public string $grantPlan       = Subscription::PLAN_MONTHLY;

    // Modal: extender
    public bool   $showExtendModal = false;
    public ?int   $extendSubId     = null;
    public string $extendUserName  = '';
    public int    $extendDays      = 30;

    // Feedback
    public ?string $successMessage = null;
    public ?string $errorMessage   = null;

    public int $perPage = 15;

    protected $queryString = ['search', 'filterStatus', 'filterPlan'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPlan(): void
    {
        $this->resetPage();
    }

    /* ────────────────────────────────
     |  Otorgar premium manual
     |──────────────────────────────── */

    public function openGrantModal(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) return;

        // Admins excluidos del sistema de suscripciones
        if ($user->isAdmin()) {
            $this->errorMessage = 'Los administradores ya tienen acceso completo y no necesitan suscripción.';
            return;
        }

        $this->grantUserId   = $userId;
        $this->grantUserName = $user->name;
        $this->grantDays     = 30;
        $this->grantPlan     = Subscription::PLAN_MONTHLY;
        $this->showGrantModal = true;
    }

    public function grantPremium(): void
    {
        $user = User::find($this->grantUserId);
        if (!$user) {
            $this->errorMessage = 'Usuario no encontrado.';
            $this->showGrantModal = false;
            return;
        }

        try {
            $service = new SubscriptionService();
            $service->grantPremium($user, $this->grantDays, $this->grantPlan);
            $this->successMessage = "Premium otorgado a {$user->name} por {$this->grantDays} días.";
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }

        $this->showGrantModal = false;
    }

    /* ────────────────────────────────
     |  Extender suscripción
     |──────────────────────────────── */

    public function openExtendModal(int $subscriptionId): void
    {
        $sub = Subscription::with('user')->find($subscriptionId);
        if (!$sub) return;

        $this->extendSubId    = $subscriptionId;
        $this->extendUserName = $sub->user->name;
        $this->extendDays     = 30;
        $this->showExtendModal = true;
    }

    public function extendSubscription(): void
    {
        $sub = Subscription::with('user')->find($this->extendSubId);
        if (!$sub || !$sub->user) {
            $this->errorMessage = 'Suscripción no encontrada.';
            $this->showExtendModal = false;
            return;
        }

        try {
            $service = new SubscriptionService();
            $service->extend($sub->user, $this->extendDays);
            $this->successMessage = "Suscripción extendida {$this->extendDays} días para {$sub->user->name}.";
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }

        $this->showExtendModal = false;
    }

    /* ────────────────────────────────
     |  Cancelar suscripción
     |──────────────────────────────── */

    public function cancelSubscription(int $subscriptionId): void
    {
        $sub = Subscription::with('user')->find($subscriptionId);
        if (!$sub || !$sub->user) {
            $this->errorMessage = 'Suscripción no encontrada.';
            return;
        }

        try {
            $service = new SubscriptionService();
            $service->cancel($sub->user);
            $this->successMessage = "Suscripción de {$sub->user->name} cancelada.";
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /* ────────────────────────────────
     |  Activar trial manualmente
     |──────────────────────────────── */

    public function activateTrialFor(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->errorMessage = 'Usuario no encontrado.';
            return;
        }

        // Admins excluidos del sistema de suscripciones
        if ($user->isAdmin()) {
            $this->errorMessage = 'Los administradores ya tienen acceso completo y no necesitan trial.';
            return;
        }

        try {
            $service = new SubscriptionService();
            // Trial manual desde admin (sin tarjeta, solo para que el admin pueda activar)
            $service->grantPremium($user, Subscription::TRIAL_DAYS, Subscription::PLAN_TRIAL);
            $this->successMessage = "Trial activado para {$user->name} — 15 días.";
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /* ────────────────────────────────
     |  Render
     |──────────────────────────────── */

    public function render()
    {
        $subscriptions = Subscription::with('user')
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPlan, fn ($q) => $q->where('plan', $this->filterPlan))
            ->latest('starts_at')
            ->paginate($this->perPage);

        // Usuarios sin suscripción activa (para "Otorgar premium")
        // Excluir admins — ellos ya tienen acceso completo
        $usersWithoutSub = User::whereDoesntHave('subscriptions', function ($q) {
            $q->where('status', Subscription::STATUS_ACTIVE)
              ->where('ends_at', '>', now());
        })
        ->whereDoesntHave('roles', function ($q) {
            $q->where('slug', 'admin');
        })
        ->orderBy('name')->get(['id', 'name', 'email']);

        // Stats
        $stats = [
            'total_active'  => Subscription::active()->count(),
            'total_trial'   => Subscription::onTrial()->count(),
            'total_paid'    => Subscription::paid()->active()->count(),
            'total_expired' => Subscription::where('status', Subscription::STATUS_EXPIRED)->count(),
        ];

        return view('livewire.suscripciones-premium', [
            'subscriptions'   => $subscriptions,
            'usersWithoutSub' => $usersWithoutSub,
            'stats'           => $stats,
        ]);
    }
}
