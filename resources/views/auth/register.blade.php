@extends('layouts.guest')

@section('content')
    <div x-data="{ accountType: '{{ old('account_type', 'personal') }}' }"
         class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-8 space-y-8">
        <div class="space-y-2 text-center">
            <p class="text-xs font-semibold tracking-[0.3em] text-primary-400 uppercase">Crear cuenta</p>
            <h2 class="text-2xl font-bold text-neutral-900">Regístrate en Vigilante SEACE</h2>
            <p class="text-sm text-neutral-500">Accede al dashboard y coordina la ingesta de contratos junto al equipo.</p>
        </div>

        @if(session('status'))
            <div class="bg-secondary-500/10 border border-secondary-500 rounded-2xl px-4 py-3 text-sm text-neutral-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
            @csrf

            {{-- Selector de tipo de cuenta --}}
            <div>
                <label class="block text-xs font-semibold text-neutral-600 mb-2">Tipo de cuenta</label>
                <input type="hidden" name="account_type" :value="accountType">
                <div class="flex rounded-full border border-neutral-200 overflow-hidden">
                    <button type="button"
                            @click="accountType = 'personal'"
                            :class="accountType === 'personal'
                                ? 'bg-gradient-to-r from-primary-500 to-primary-400 text-white'
                                : 'bg-white text-neutral-600 hover:bg-neutral-50'"
                            class="flex-1 py-2.5 text-sm font-semibold transition-all duration-200">
                        <span class="flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Personal
                        </span>
                    </button>
                    <button type="button"
                            @click="accountType = 'empresa'"
                            :class="accountType === 'empresa'
                                ? 'bg-gradient-to-l from-primary-500 to-primary-400 text-white'
                                : 'bg-white text-neutral-600 hover:bg-neutral-50'"
                            class="flex-1 py-2.5 text-sm font-semibold transition-all duration-200">
                        <span class="flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Empresa
                        </span>
                    </button>
                </div>
                @error('account_type')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nombre --}}
            <div>
                <label for="name" class="block text-xs font-semibold text-neutral-600 mb-2">
                    <span x-text="accountType === 'empresa' ? 'Nombre del representante' : 'Nombre completo'"></span>
                </label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                       placeholder="María Fernanda"/>
                @error('name')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Campos empresa --}}
            <template x-if="accountType === 'empresa'">
                <div class="space-y-5" x-transition>
                    <div>
                        <label for="ruc" class="block text-xs font-semibold text-neutral-600 mb-2">RUC</label>
                        <input id="ruc" type="text" name="ruc" value="{{ old('ruc') }}" maxlength="11"
                               class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                               placeholder="20XXXXXXXXX"/>
                        @error('ruc')
                            <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="razon_social" class="block text-xs font-semibold text-neutral-600 mb-2">Razón social</label>
                        <input id="razon_social" type="text" name="razon_social" value="{{ old('razon_social') }}"
                               class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                               placeholder="EMPRESA S.A.C."/>
                        @error('razon_social')
                            <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="telefono" class="block text-xs font-semibold text-neutral-600 mb-2">Teléfono <span class="text-neutral-400 font-normal">(opcional)</span></label>
                        <input id="telefono" type="tel" name="telefono" value="{{ old('telefono') }}" maxlength="20"
                               class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                               placeholder="999 888 777"/>
                        @error('telefono')
                            <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </template>

            {{-- Correo --}}
            <div>
                <label for="email" class="block text-xs font-semibold text-neutral-600 mb-2">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                       placeholder="usuario@correo.com"/>
                <p class="mt-1.5 text-xs text-neutral-400" x-show="accountType === 'personal'">
                    Dominios permitidos: Gmail, Outlook, Yahoo, .com.pe, .edu.pe, .gob.pe
                </p>
                @error('email')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contraseña --}}
            <div>
                <label for="password" class="block text-xs font-semibold text-neutral-600 mb-2">Contraseña</label>
                <input id="password" type="password" name="password" required
                       class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                       placeholder="••••••••"/>
                @error('password')
                    <p class="mt-2 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmar contraseña --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-neutral-600 mb-2">Confirma tu contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm text-neutral-900"
                       placeholder="••••••••"/>
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white font-semibold text-sm tracking-wide shadow-lg shadow-secondary-500/20 hover:opacity-95 transition">
                Crear cuenta
            </button>
        </form>

        <p class="text-center text-xs text-neutral-500">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="text-primary-500 font-semibold hover:underline">Inicia sesión</a>
        </p>
    </div>
@endsection
