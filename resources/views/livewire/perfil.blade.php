<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">👤 Mi Perfil</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Actualiza tu informacion personal y gestiona tu contraseña.
        </p>
    </div>

    {{-- Flash messages --}}
    @if(session()->has('success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="bg-red-50 border-l-4 border-red-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ═══════════════════════════════════════════════════════
             DATOS PERSONALES
             ═══════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
            <div class="flex items-center gap-3 mb-6">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-500/10">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-neutral-900">Informacion personal</h2>
                    <p class="text-xs text-neutral-400">Estos datos se usan para identificarte en el sistema.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Nombre --}}
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name"
                           class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Tu nombre completo">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Correo electronico <span class="text-red-500">*</span>
                    </label>
                    <input type="email" wire:model="email"
                           class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="tu@correo.com">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    @if(auth()->user() && !auth()->user()->hasVerifiedEmail())
                        <p class="mt-1 text-xs text-amber-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/></svg>
                            Correo no verificado. Revisa tu bandeja de entrada.
                        </p>
                    @endif
                </div>

                {{-- Telefono --}}
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Telefono
                    </label>
                    <input type="text" wire:model="telefono"
                           class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="999 888 777">
                    @error('telefono')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- RUC --}}
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        RUC
                    </label>
                    <input type="text" wire:model="ruc" maxlength="11"
                           class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="20123456789">
                    @error('ruc')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Razon Social --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Razon social
                    </label>
                    <input type="text" wire:model="razon_social"
                           class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Mi Empresa S.A.C.">
                    @error('razon_social')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="actualizarPerfil"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             SIDEBAR: Resumen + Suscripción + Contraseña
             ═══════════════════════════════════════════════════════ --}}
        <div class="space-y-6">
            {{-- Tarjeta resumen --}}
            <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100 text-center">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-500 to-secondary-500 flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                    {{ strtoupper(substr($name, 0, 1)) }}
                </div>
                <h3 class="text-sm font-bold text-neutral-900 truncate">{{ $name }}</h3>
                <p class="text-xs text-neutral-400 truncate mt-0.5">{{ $email }}</p>

                @if(auth()->user())
                    <div class="mt-4 flex flex-wrap justify-center gap-1.5">
                        @foreach(auth()->user()->roles as $role)
                            <span class="px-2.5 py-0.5 bg-primary-500/10 text-brand-600 border border-primary-500/20 rounded-full text-[11px] font-semibold">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 pt-4 border-t border-neutral-100 space-y-2 text-left">
                    @if($ruc)
                        <div class="flex items-center gap-2 text-xs text-neutral-600">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            RUC: {{ $ruc }}
                        </div>
                    @endif
                    @if($telefono)
                        <div class="flex items-center gap-2 text-xs text-neutral-600">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $telefono }}
                        </div>
                    @endif
                    <div class="flex items-center gap-2 text-xs text-neutral-600">
                        <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Miembro desde {{ auth()->user()?->created_at?->format('d/m/Y') }}
                    </div>
                </div>
            </div>

            {{-- ═══ SECCIÓN SUSCRIPCIÓN ═══ --}}
            <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
                @if($isPremium && $subscriptionData)
                    {{-- Suscripción activa --}}
                    <div class="text-center mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold
                            {{ $isOnTrial ? 'bg-amber-400 text-neutral-900' : 'bg-secondary-500 text-white' }}">
                            {{ $isOnTrial ? 'TRIAL' : 'PREMIUM' }}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-neutral-900 text-center">{{ $planLabel }}</h3>
                    <p class="text-xs text-neutral-400 text-center mt-1">{{ $statusLabel }} — Vence {{ $endsAt }}</p>

                    <div class="mt-3 flex justify-center">
                        <span class="text-xs font-semibold {{ $daysRemaining <= 3 ? 'text-amber-600' : 'text-secondary-600' }}">
                            {{ $daysRemaining }} día(s) restante(s)
                        </span>
                    </div>

                    {{-- Auto-renew toggle --}}
                    <div class="mt-4 flex items-center justify-between py-2.5 px-3 bg-neutral-50 rounded-2xl">
                        <span class="text-xs font-medium text-neutral-600">Renovación auto.</span>
                        <button wire:click="toggleAutoRenew"
                                wire:loading.attr="disabled"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                    {{ $autoRenew ? 'bg-secondary-500' : 'bg-neutral-300' }}"
                                role="switch">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                {{ $autoRenew ? 'translate-x-4' : 'translate-x-0' }}">
                            </span>
                        </button>
                    </div>

                    {{-- Acciones --}}
                    <div class="mt-3 flex flex-col gap-2">
                        <a href="{{ route('mi.suscripcion') }}"
                           class="flex items-center justify-center gap-1.5 px-3 py-2 bg-primary-500/10 text-primary-800 rounded-full text-xs font-semibold hover:bg-primary-500/20 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Gestionar suscripción
                        </a>
                        <button wire:click="confirmCancel"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 bg-red-50 text-red-600 rounded-full text-xs font-medium hover:bg-red-100 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancelar plan
                        </button>
                    </div>

                @elseif($canStartTrial)
                    {{-- Puede iniciar trial --}}
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 mb-3">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900">Prueba gratuita</h3>
                        <p class="text-xs text-neutral-400 mt-1 mb-3">15 días de acceso Premium</p>
                        <a href="{{ route('planes.checkout', ['plan' => 'monthly', 'trial' => 1]) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 text-white rounded-full text-xs font-semibold hover:bg-amber-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            Activar trial
                        </a>
                    </div>
                @else
                    {{-- No premium --}}
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-neutral-100 mb-3">
                            <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-bold text-neutral-900">Plan gratuito</h3>
                        <p class="text-xs text-neutral-400 mt-1 mb-3">Acceso limitado</p>
                        <a href="{{ route('planes') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-500 text-white rounded-full text-xs font-semibold hover:bg-primary-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Ver planes
                        </a>
                    </div>
                @endif
            </div>

            {{-- Cambiar contraseña --}}
            <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
                <button wire:click="togglePasswordSection"
                        class="w-full flex items-center justify-between text-left">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-500/10">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900">Cambiar contraseña</h3>
                            <p class="text-[11px] text-neutral-400">Actualiza tu contraseña de acceso</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-neutral-400 transition-transform {{ $showPasswordSection ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                @if($showPasswordSection)
                    <div class="mt-5 pt-5 border-t border-neutral-100 space-y-4"
                         x-data
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        {{-- Contraseña actual --}}
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-2">
                                Contraseña actual <span class="text-red-500">*</span>
                            </label>
                            <input type="password" wire:model="current_password"
                                   class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                                   placeholder="••••••••">
                            @error('current_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Nueva contraseña --}}
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-2">
                                Nueva contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" wire:model="new_password"
                                   class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                                   placeholder="Minimo 8 caracteres">
                            @error('new_password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirmar contraseña --}}
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-2">
                                Confirmar nueva contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" wire:model="new_password_confirmation"
                                   class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                                   placeholder="Repite la nueva contraseña">
                        </div>

                        <button wire:click="cambiarPassword"
                                class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-full font-medium text-sm transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Actualizar contraseña
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         MODAL DE CANCELACIÓN
         ═══════════════════════════════════════════════════════ --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="dismissCancelModal">
            <div class="bg-white rounded-3xl shadow-lg p-6 sm:p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900">¿Cancelar suscripción?</h3>
                    <p class="text-sm text-neutral-400 mt-2">
                        Perderás acceso inmediato a las funcionalidades premium.
                    </p>
                </div>

                {{-- Motivo de cancelación --}}
                <div class="mb-6">
                    <label class="block text-xs font-semibold text-neutral-600 mb-3">
                        ¿Por qué cancelas? <span class="text-red-400">(opcional)</span>
                    </label>
                    <div class="space-y-2">
                        @foreach($cancellationReasons as $label)
                            <button type="button"
                                    wire:click="setCancellationReason('{{ $label }}')"
                                    class="w-full text-left px-4 py-2.5 rounded-2xl border text-sm transition-all
                                        {{ $cancellationReason === $label
                                            ? 'border-primary-500 bg-primary-500/10 text-primary-800 font-medium'
                                            : 'border-neutral-200 text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <input type="text"
                           wire:model="cancellationReason"
                           placeholder="O escribe tu propio motivo..."
                           class="mt-2 w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                           maxlength="500">
                </div>

                <div class="flex gap-3">
                    <button wire:click="dismissCancelModal"
                            class="flex-1 py-3 px-4 bg-neutral-100 text-neutral-700 rounded-full text-sm font-semibold hover:bg-neutral-200 transition-colors">
                        Mantener plan
                    </button>
                    <button wire:click="cancelSubscription"
                            wire:loading.attr="disabled"
                            class="flex-1 py-3 px-4 bg-red-500 text-white rounded-full text-sm font-semibold hover:bg-red-600 transition-colors">
                        <span wire:loading.remove wire:target="cancelSubscription">Sí, cancelar</span>
                        <span wire:loading wire:target="cancelSubscription">Cancelando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
