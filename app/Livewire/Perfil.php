<?php

namespace App\Livewire;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Perfil extends Component
{
    // ── Datos personales ─────────────────────────────────
    public string $name = '';
    public string $email = '';
    public string $telefono = '';
    public string $ruc = '';
    public string $razon_social = '';

    // ── Cambio de contraseña ─────────────────────────────
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';
    public bool $showPasswordSection = false;

    // ── Suscripción ──────────────────────────────────────
    public ?array $subscriptionData = null;
    public bool $isPremium = false;
    public bool $isOnTrial = false;
    public bool $canStartTrial = false;
    public bool $autoRenew = true;
    public int $daysRemaining = 0;
    public ?string $planLabel = null;
    public ?string $statusLabel = null;
    public ?string $endsAt = null;

    // ── Cancelación ──────────────────────────────────────
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
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->telefono = $user->telefono ?? '';
        $this->ruc = $user->ruc ?? '';
        $this->razon_social = $user->razon_social ?? '';

        $this->loadSubscriptionData();
    }

    public function loadSubscriptionData(): void
    {
        $user = Auth::user();
        $activeSub = $user->activeSubscription();

        $this->isPremium     = $user->isPremium();
        $this->isOnTrial     = $user->isOnTrial();
        $this->canStartTrial = $user->canStartTrial();
        $this->daysRemaining = $user->subscriptionDaysLeft();

        if ($activeSub) {
            $this->subscriptionData = $activeSub->toArray();
            $this->autoRenew = (bool) $activeSub->auto_renew;
            $this->endsAt    = $activeSub->ends_at?->format('d/m/Y');

            $this->planLabel = match ($activeSub->plan) {
                Subscription::PLAN_TRIAL   => 'Prueba gratuita',
                Subscription::PLAN_MONTHLY => 'Mensual — S/ 49',
                Subscription::PLAN_YEARLY  => 'Anual — S/ 470',
                default                    => ucfirst($activeSub->plan),
            };

            $this->statusLabel = match ($activeSub->status) {
                Subscription::STATUS_ACTIVE    => 'Activa',
                Subscription::STATUS_EXPIRED   => 'Expirada',
                Subscription::STATUS_CANCELLED => 'Cancelada',
                default                         => ucfirst($activeSub->status),
            };
        }
    }

    public function togglePasswordSection(): void
    {
        $this->showPasswordSection = !$this->showPasswordSection;
        $this->resetPasswordFields();
    }

    public function actualizarPerfil(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'ruc' => 'nullable|string|max:11',
            'razon_social' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo valido.',
            'email.unique' => 'Este correo ya esta en uso.',
            'ruc.max' => 'El RUC debe tener maximo 11 caracteres.',
        ]);

        $emailChanged = $user->email !== $this->email;

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'telefono' => $this->telefono ?: null,
            'ruc' => $this->ruc ?: null,
            'razon_social' => $this->razon_social ?: null,
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
            session()->flash('success', '✅ Perfil actualizado. Se envio un correo para verificar tu nueva direccion.');
        } else {
            session()->flash('success', '✅ Perfil actualizado exitosamente.');
        }

        Log::info('Perfil actualizado', ['user_id' => $user->id]);
    }

    public function cambiarPassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Ingresa tu contraseña actual.',
            'new_password.required' => 'Ingresa la nueva contraseña.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'La contraseña actual es incorrecta.');
            return;
        }

        $user->update([
            'password' => $this->new_password, // Se hashea automáticamente por cast
        ]);

        $this->resetPasswordFields();
        $this->showPasswordSection = false;

        session()->flash('success', '✅ Contraseña actualizada exitosamente.');
        Log::info('Contraseña actualizada', ['user_id' => $user->id]);
    }

    protected function resetPasswordFields(): void
    {
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->resetValidation(['current_password', 'new_password', 'new_password_confirmation']);
    }

    // ── Suscripción: acciones ───────────────────────────

    public function toggleAutoRenew(): void
    {
        $user    = Auth::user();
        $service = new SubscriptionService();

        $result = $service->toggleAutoRenew($user);

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
        $user    = Auth::user();
        $service = new SubscriptionService();
        $reason  = $this->cancellationReason ?: null;

        if ($service->cancel($user, $reason)) {
            $this->showCancelModal    = false;
            $this->cancellationReason = '';
            $this->loadSubscriptionData();
            session()->flash('success', '✅ Tu suscripción ha sido cancelada.');
        } else {
            session()->flash('error', 'No se pudo cancelar. Contacta soporte.');
        }
    }

    public function dismissCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function render()
    {
        return view('livewire.perfil');
    }
}
