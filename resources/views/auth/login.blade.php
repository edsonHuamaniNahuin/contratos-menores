@extends('layouts.guest')

@section('content')
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 space-y-8">
        <div class="space-y-2 text-center">
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Acceso Seguro</p>
            <h2 class="text-2xl font-bold text-neutral-900">Inicia sesión</h2>
            <p class="text-sm text-neutral-500">Usa las credenciales administradas por el equipo para ingresar al dashboard.</p>
        </div>

        @if(session('status'))
            <div class="bg-secondary-500/10 border border-secondary-500 rounded-2xl px-4 py-3 text-sm text-neutral-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-xs font-semibold text-neutral-600 mb-2">Correo institucional</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                    placeholder="operaciones@empresa.com"
                >
                @error('email')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-neutral-600 mb-2">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                    placeholder="••••••••"
                >
                @error('password')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 text-neutral-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-neutral-300 text-primary-500 focus:ring-primary-500">
                    <span>Recordarme</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-primary-500 font-semibold hover:underline">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="w-full py-3 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white font-semibold text-sm tracking-wide shadow-lg shadow-secondary-500/20 hover:opacity-95 transition">
                Entrar al Dashboard
            </button>
        </form>

        <p class="text-center text-xs text-neutral-500">
            ¿Aún no tienes cuenta? <a href="{{ route('register') }}" class="text-primary-500 font-semibold hover:underline">Crear cuenta</a>
        </p>
    </div>
@endsection
