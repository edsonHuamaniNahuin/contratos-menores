@extends('layouts.guest')

@section('content')
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 space-y-8">
        <div class="space-y-2 text-center">
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Restablecer contraseña</p>
            <h2 class="text-2xl font-bold text-neutral-900">Elige una nueva clave</h2>
            <p class="text-sm text-neutral-500">Reemplaza tu contraseña con una combinación segura para volver al dashboard.</p>
        </div>

        @if(session('status'))
            <div class="bg-secondary-500/10 border border-secondary-500 rounded-2xl px-4 py-3 text-sm text-neutral-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-xs font-semibold text-neutral-600 mb-2">Correo institucional</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900" placeholder="operaciones@empresa.com"/>
                @error('email')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-neutral-600 mb-2">Nueva contraseña</label>
                <input id="password" type="password" name="password" required class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900" placeholder="••••••••"/>
                @error('password')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-neutral-600 mb-2">Confirma tu nueva contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900" placeholder="••••••••"/>
            </div>

            <button type="submit" class="w-full py-3 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white font-semibold text-sm tracking-wide shadow-lg shadow-secondary-500/20 hover:opacity-95 transition">
                Guardar nueva contraseña
            </button>
        </form>

        <div class="flex flex-col gap-2 text-center text-xs text-neutral-500">
            <a href="{{ route('login') }}" class="text-primary-500 font-semibold hover:underline">Volver al login</a>
        </div>
    </div>
@endsection
