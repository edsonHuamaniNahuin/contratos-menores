<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $isEmpresa = $request->input('account_type') === 'empresa';

        // Reglas base
        $rules = [
            'account_type' => ['required', 'in:personal,empresa'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'confirmed', 'min:8'],
        ];

        // Whitelist de dominio solo para cuentas personales
        if (! $isEmpresa) {
            $rules['email'][] = new AllowedEmailDomain();
        }

        // Campos adicionales para empresas
        if ($isEmpresa) {
            $rules['ruc']          = ['required', 'string', 'size:11', 'regex:/^(10|20)\d{9}$/'];
            $rules['razon_social'] = ['required', 'string', 'max:255'];
            $rules['telefono']     = ['nullable', 'string', 'max:20'];
        }

        $messages = [
            'ruc.size'  => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.regex' => 'El RUC debe iniciar con 10 (persona natural) o 20 (persona jurídica).',
            'account_type.in' => 'El tipo de cuenta debe ser "personal" o "empresa".',
        ];

        $validated = $request->validate($rules, $messages);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'account_type' => $validated['account_type'],
            'ruc'          => $validated['ruc'] ?? null,
            'razon_social' => $validated['razon_social'] ?? null,
            'telefono'     => $validated['telefono'] ?? null,
        ]);

        $providerRole = Role::firstOrCreate(
            ['slug' => 'proveedor'],
            [
                'name'        => 'PROVEEDORES',
                'description' => 'Proveedores y usuarios operativos',
            ]
        );

        $user->roles()->syncWithoutDetaching([$providerRole->id]);

        // Dispara el evento que envía el email de verificación
        event(new Registered($user));

        // Login para que pueda ver la pantalla de verificación
        Auth::login($user);

        // Redirigir a la pantalla de verificación (NO al home)
        return redirect()->route('verification.notice');
    }
}
