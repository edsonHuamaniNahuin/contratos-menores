<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email']
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status === Password::RESET_LINK_SENT
                ? back()->with('status', 'Te hemos enviado un enlace para restablecer tu contraseña. Revisa tu bandeja de entrada.')
                : back()->withErrors(['email' => __($status)]);
        } catch (\Exception $e) {
            Log::error('Error al enviar correo de reset de contraseña', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'No se pudo enviar el correo. Por favor intenta de nuevo en unos minutos.',
            ]);
        }
    }
}
