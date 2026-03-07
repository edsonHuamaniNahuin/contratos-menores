<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">⭐ Mi Suscripción</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Gestiona tu plan premium, renovación automática e historial.
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
             ESTADO ACTUAL
             ═══════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
            <div class="flex items-center gap-3 mb-6">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full {{ $isPremium ? 'bg-secondary-500/10' : 'bg-neutral-100' }}">
                    @if($isPremium)
                        <svg class="w-5 h-5 text-secondary-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </span>
                <div>
                    <h2 class="text-base font-bold text-neutral-900">Estado de tu cuenta</h2>
                    <p class="text-xs text-neutral-400">Información de tu plan actual.</p>
                </div>
            </div>

            @if($isPremium && $subscription)
                {{-- Tarjeta de plan activo --}}
                <div class="bg-gradient-to-br from-primary-800 to-primary-900 rounded-2xl p-6 text-white mb-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-primary-400 font-medium">Plan actual</p>
                            <p class="text-xl font-bold mt-1">{{ $planLabel }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                            {{ $isOnTrial ? 'bg-amber-400 text-neutral-900' : 'bg-secondary-500 text-white' }}">
                            {{ $isOnTrial ? 'TRIAL' : $statusLabel }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div>
                            <p class="text-xs text-primary-400">Inicio</p>
                            <p class="text-sm font-semibold">{{ $startsAt }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-primary-400">Vence</p>
                            <p class="text-sm font-semibold">{{ $endsAt }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-primary-400">Días restantes</p>
                            <p class="text-sm font-semibold">{{ $daysRemaining }} días</p>
                        </div>
                        <div>
                            <p class="text-xs text-primary-400">Método de pago</p>
                            <p class="text-sm font-semibold">{{ $paymentMethodLabel }}</p>
                        </div>
                    </div>
                </div>

                {{-- Barra de progreso --}}
                @php
                    $totalDays = match($subscription['plan'] ?? '') {
                        'trial' => 15,
                        'yearly' => 365,
                        default => 30,
                    };
                    $progress = $totalDays > 0 ? max(0, min(100, (($totalDays - $daysRemaining) / $totalDays) * 100)) : 0;
                @endphp
                <div class="mb-6">
                    <div class="flex justify-between text-xs text-neutral-400 mb-1">
                        <span>Progreso del periodo</span>
                        <span>{{ round($progress) }}% consumido</span>
                    </div>
                    <div class="w-full bg-neutral-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500
                            {{ $progress > 80 ? 'bg-amber-500' : 'bg-secondary-500' }}"
                            style="width: {{ $progress }}%">
                        </div>
                    </div>
                </div>

                {{-- Detalles --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-neutral-50 rounded-2xl p-4">
                        <p class="text-xs text-neutral-400 mb-1">Pasarela de pago</p>
                        <p class="font-medium text-neutral-900">{{ $gatewayLabel }}</p>
                    </div>
                    <div class="bg-neutral-50 rounded-2xl p-4">
                        <p class="text-xs text-neutral-400 mb-1">Estado</p>
                        <p class="font-medium text-neutral-900">{{ $statusLabel }}</p>
                    </div>
                </div>

            @else
                {{-- Sin suscripción activa --}}
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                        <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-2">No tienes una suscripción activa</h3>
                    <p class="text-sm text-neutral-400 mb-6">
                        @if($canStartTrial)
                            Activa tu prueba gratuita de 15 días o elige un plan premium.
                        @else
                            Elige un plan premium para acceder a todas las funcionalidades.
                        @endif
                    </p>
                    <a href="{{ route('planes') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-primary-800 text-white rounded-full text-sm font-semibold hover:bg-primary-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/>
                        </svg>
                        Ver planes
                    </a>
                </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════
             PANEL LATERAL: Renovación + Acciones
             ═══════════════════════════════════════════════════════ --}}
        <div class="flex flex-col gap-6">

            {{-- Renovación automática --}}
            @if($isPremium && $subscription)
                <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-500/10">
                            <svg class="w-4 h-4 text-primary-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </span>
                        <h3 class="text-sm font-bold text-neutral-900">Renovación automática</h3>
                    </div>

                    <p class="text-xs text-neutral-400 mb-4">
                        @if($autoRenew)
                            Tu suscripción se renovará automáticamente al vencer. Se cobrará con tu método de pago registrado.
                        @else
                            La renovación automática está desactivada. Tu plan se cancelará al vencer.
                        @endif
                    </p>

                    <div class="flex items-center justify-between py-3 px-4 bg-neutral-50 rounded-2xl">
                        <span class="text-sm font-medium text-neutral-900">
                            {{ $autoRenew ? 'Activada' : 'Desactivada' }}
                        </span>
                        <button wire:click="toggleAutoRenew"
                                wire:loading.attr="disabled"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                    {{ $autoRenew ? 'bg-secondary-500' : 'bg-neutral-300' }}"
                                role="switch"
                                aria-checked="{{ $autoRenew ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                {{ $autoRenew ? 'translate-x-5' : 'translate-x-0' }}">
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
                    <h3 class="text-sm font-bold text-neutral-900 mb-4">Acciones</h3>

                    <div class="flex flex-col gap-3">
                        <a href="{{ route('planes') }}"
                           class="flex items-center gap-2 px-4 py-3 bg-primary-800 text-white rounded-full text-sm font-semibold hover:bg-primary-900 transition-colors text-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                            </svg>
                            Mejorar plan
                        </a>

                        <button wire:click="confirmCancel"
                                class="flex items-center gap-2 px-4 py-3 bg-white border border-neutral-200 text-neutral-600 rounded-full text-sm font-medium hover:bg-neutral-50 transition-colors justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar suscripción
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         HISTORIAL DE AUDITORÍA
         ═══════════════════════════════════════════════════════ --}}
    @if(count($auditHistory) > 0)
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
            <div class="flex items-center gap-3 mb-6">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-500/10">
                    <svg class="w-5 h-5 text-primary-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-neutral-900">Historial Premium</h2>
                    <p class="text-xs text-neutral-400">Registro de cambios en tu suscripción.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-100">
                            <th class="text-left py-3 px-2 text-xs font-semibold text-neutral-400 uppercase">Fecha</th>
                            <th class="text-left py-3 px-2 text-xs font-semibold text-neutral-400 uppercase">Acción</th>
                            <th class="text-left py-3 px-2 text-xs font-semibold text-neutral-400 uppercase">Origen</th>
                            <th class="text-left py-3 px-2 text-xs font-semibold text-neutral-400 uppercase">Plan</th>
                            <th class="text-right py-3 px-2 text-xs font-semibold text-neutral-400 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditHistory as $log)
                            <tr class="border-b border-neutral-50 hover:bg-neutral-50/50">
                                <td class="py-3 px-2 text-neutral-600">{{ $log['created_at'] }}</td>
                                <td class="py-3 px-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                        {{ $log['action'] === 'granted' ? 'bg-secondary-500/10 text-secondary-600' : 'bg-neutral-100 text-neutral-600' }}">
                                        {{ $log['action_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-neutral-600">{{ $log['source'] }}</td>
                                <td class="py-3 px-2 text-neutral-600">{{ $log['plan'] ? ucfirst($log['plan']) : '—' }}</td>
                                <td class="py-3 px-2 text-right text-neutral-900 font-medium">
                                    {{ $log['amount'] > 0 ? 'S/ ' . number_format($log['amount'], 2) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         MODAL DE CANCELACIÓN
         ═══════════════════════════════════════════════════════ --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="dismissCancelModal">
            <div class="bg-white rounded-3xl shadow-lg p-6 sm:p-8 max-w-md w-full mx-4">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900">¿Cancelar suscripción?</h3>
                    <p class="text-sm text-neutral-400 mt-2">
                        Perderás acceso inmediato a las funcionalidades premium:
                        análisis de TDR con IA, seguimiento de contratos y más.
                    </p>
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
