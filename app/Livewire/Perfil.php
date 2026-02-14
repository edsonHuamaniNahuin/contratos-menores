<?php

namespace App\Livewire;

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

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->telefono = $user->telefono ?? '';
        $this->ruc = $user->ruc ?? '';
        $this->razon_social = $user->razon_social ?? '';
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

    public function render()
    {
        return view('livewire.perfil');
    }
}
