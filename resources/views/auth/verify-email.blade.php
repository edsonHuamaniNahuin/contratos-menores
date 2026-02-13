@extends('layouts.guest')

@section('content')
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 space-y-8">
        <div class="space-y-2 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-secondary-500/10 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Verificación</p>
            <h2 class="text-2xl font-bold text-neutral-900">Confirma tu correo</h2>
            <p class="text-sm text-neutral-500 leading-relaxed">
                Te hemos enviado un enlace de verificación a <strong class="text-neutral-900">{{ Auth::user()->email }}</strong>.
                Revisa tu bandeja de entrada (y la carpeta de spam) para activar tu cuenta.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="bg-secondary-500/10 border border-secondary-500 rounded-2xl px-4 py-3 text-sm text-neutral-900">
                <span class="font-semibold">¡Enlace reenviado!</span> Se ha enviado un nuevo enlace de verificación a tu correo.
            </div>
        @endif

        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                        class="w-full py-3 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white font-semibold text-sm tracking-wide shadow-lg shadow-secondary-500/20 hover:opacity-95 transition">
                    Reenviar enlace de verificación
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full py-3 rounded-full border border-neutral-200 text-neutral-600 font-semibold text-sm tracking-wide hover:bg-neutral-50 transition">
                    Cerrar sesión
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-neutral-400">
            Si no recibes el correo en 5 minutos, intenta reenviarlo o contacta al administrador.
        </p>
    </div>
@endsection
