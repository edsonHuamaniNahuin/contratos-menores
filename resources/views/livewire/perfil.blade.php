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
                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
             SIDEBAR: Resumen + Contraseña
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
                            <span class="px-2.5 py-0.5 bg-primary-500/10 text-primary-600 border border-primary-500/20 rounded-full text-[11px] font-semibold">
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
</div>
