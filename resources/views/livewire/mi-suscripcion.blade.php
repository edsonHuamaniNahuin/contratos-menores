<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">⭐ Mi Suscripción</h1>
        <p class="text-sm text-neutral-400 mt-2">Gestiona tu plan premium e historial de cambios.</p>
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

    @if($isPremium && $subscription)
        {{-- Plan activo --}}
        <div class="bg-gradient-to-br from-primary-800 to-primary-900 rounded-2xl p-6 sm:p-8 text-white">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <p class="text-sm text-primary-400 font-medium">Plan actual</p>
                    <p class="text-2xl font-bold mt-1">{{ $planLabel }}</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                    {{ $isOnTrial ? 'bg-amber-400 text-neutral-900' : 'bg-secondary-500 text-white' }}">
                    {{ $isOnTrial ? 'TRIAL' : $statusLabel }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
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
                    <p class="text-xs text-primary-400">Pasarela</p>
                    <p class="text-sm font-semibold">{{ $gatewayLabel }}</p>
                </div>
            </div>

            {{-- Barra de progreso --}}
            @php
                $totalDays = match($subscription['plan'] ?? '') {
                    'trial' => 15, 'yearly' => 365, default => 30,
                };
                $progress = $totalDays > 0 ? max(0, min(100, (($totalDays - $daysRemaining) / $totalDays) * 100)) : 0;
            @endphp
            <div class="mt-6">
                <div class="flex justify-between text-xs text-primary-400 mb-1">
                    <span>Progreso del periodo</span>
                    <span>{{ round($progress) }}%</span>
                </div>
                <div class="w-full bg-primary-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-500 {{ $progress > 80 ? 'bg-amber-400' : 'bg-secondary-500' }}"
                         style="width: {{ $progress }}%"></div>
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('planes') }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-primary-500 text-white rounded-full text-sm font-semibold hover:bg-primary-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Cambiar plan
            </a>
            <a href="{{ route('billing') }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-white border border-neutral-200 text-neutral-700 rounded-full text-sm font-medium hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Facturación
            </a>
            <button wire:click="confirmCancel"
                    class="inline-flex items-center gap-2 px-5 py-3 bg-red-50 text-red-600 rounded-full text-sm font-medium hover:bg-red-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Cancelar suscripción
            </button>
        </div>

    @else
        {{-- Sin suscripción --}}
        <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-neutral-900 mb-2">Sin suscripción activa</h3>
            <p class="text-sm text-neutral-400 mb-6">
                @if($canStartTrial) Activa tu prueba gratuita de 15 días o elige un plan premium.
                @else Elige un plan premium para acceder a todas las funcionalidades. @endif
            </p>
            <a href="{{ route('planes') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white rounded-full text-sm font-semibold hover:bg-primary-600 transition-colors">
                Ver planes
            </a>
        </div>
    @endif

    {{-- Historial de auditoría --}}
    @if(!empty($auditHistory))
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
            <h2 class="text-base font-bold text-neutral-900 mb-4">Historial Premium</h2>
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
                            <tr class="border-b border-neutral-50">
                                <td class="py-3 px-2 text-neutral-600">{{ $log['created_at'] }}</td>
                                <td class="py-3 px-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                        {{ $log['action'] === 'granted' ? 'bg-secondary-500/10 text-secondary-600' : 'bg-neutral-100 text-neutral-600' }}">
                                        {{ $log['action_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-neutral-600">{{ $log['source'] }}</td>
                                <td class="py-3 px-2 text-neutral-600">{{ $log['plan'] ? ucfirst($log['plan']) : '—' }}</td>
                                <td class="py-3 px-2 text-right font-medium">{{ $log['amount'] > 0 ? 'S/ ' . number_format($log['amount'], 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Modal de cancelación --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="dismissCancelModal">
            <div class="bg-white rounded-3xl shadow-lg p-6 sm:p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 mb-4">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900">¿Cancelar suscripción?</h3>
                    <p class="text-sm text-neutral-400 mt-2">Perderás acceso inmediato a las funcionalidades premium.</p>
                </div>
                <div class="mb-6">
                    <label class="block text-xs font-semibold text-neutral-600 mb-3">¿Por qué cancelas? <span class="text-red-400">(opcional)</span></label>
                    <div class="space-y-2">
                        @foreach($cancellationReasons as $label)
                            <button type="button" wire:click="setCancellationReason('{{ $label }}')"
                                    class="w-full text-left px-4 py-2.5 rounded-2xl border text-sm transition-all
                                        {{ $cancellationReason === $label ? 'border-primary-500 bg-primary-500/10 text-primary-800 font-medium' : 'border-neutral-200 text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <input type="text" wire:model="cancellationReason" placeholder="O escribe tu propio motivo..."
                           class="mt-2 w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm" maxlength="500">
                </div>
                <div class="flex gap-3">
                    <button wire:click="dismissCancelModal" class="flex-1 py-3 px-4 bg-neutral-100 text-neutral-700 rounded-full text-sm font-semibold hover:bg-neutral-200 transition-colors">Mantener plan</button>
                    <button wire:click="cancelSubscription" wire:loading.attr="disabled" class="flex-1 py-3 px-4 bg-red-500 text-white rounded-full text-sm font-semibold hover:bg-red-600 transition-colors">
                        <span wire:loading.remove wire:target="cancelSubscription">Sí, cancelar</span>
                        <span wire:loading wire:target="cancelSubscription">Cancelando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
