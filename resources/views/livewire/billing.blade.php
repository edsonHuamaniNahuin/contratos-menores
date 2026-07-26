<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex items-center gap-3 mb-6">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary-500/10">
                <svg class="w-5 h-5 text-primary-800" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </span>
            <div>
                <h1 class="text-base font-bold text-neutral-900">Facturación</h1>
                <p class="text-xs text-neutral-500">Método de pago, historial y renovación automática.</p>
            </div>
        </div>

        {{-- Flash messages --}}
        @if(session()->has('success'))
            <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4 mb-6">
                <p class="text-sm text-neutral-900 font-medium">{{ session('success') }}</p>
            </div>
        @endif
        @if(session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 rounded-2xl p-4 mb-6">
                <p class="text-sm text-neutral-900 font-medium">{{ session('error') }}</p>
            </div>
        @endif

        @if($isPremium && $subscription)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Columna 1: Plan actual --}}
                <div class="bg-brand-800 rounded-2xl p-6 text-white">
                    <p class="text-xs font-semibold text-brand-200/80 uppercase tracking-wider">Plan actual</p>
                    <p class="text-2xl font-bold mt-1">{{ $planLabel }}</p>
                    <div class="mt-5 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-brand-200">Inicio</span>
                            <span class="font-medium text-white">{{ $startsAt }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-brand-200">Vence</span>
                            <span class="font-medium text-white">{{ $endsAt }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-brand-200">Días restantes</span>
                            <span class="font-bold text-white">{{ $daysRemaining }} días</span>
                        </div>
                    </div>
                </div>

                {{-- Columna 2: Método de pago --}}
                <div class="bg-neutral-50 rounded-2xl p-6 border border-neutral-100">
                    <h3 class="text-sm font-bold text-neutral-900 mb-4">Método de pago</h3>
                    @if($savedCard)
                        <div class="bg-white rounded-xl p-4 border border-neutral-200">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-7 bg-gradient-to-r from-neutral-700 to-neutral-900 rounded-md flex items-center justify-center text-white text-[10px] font-bold">
                                    {{ strtoupper(substr($savedCard['brand'], 0, 4)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-neutral-900">{{ $savedCard['brand'] }}</p>
                                    <p class="text-xs text-neutral-400">{{ $savedCard['type'] === 'debit_card' ? 'Débito' : 'Crédito' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <span class="text-neutral-400 font-mono">•••• •••• ••••</span>
                                <span class="text-neutral-900 font-bold font-mono text-lg">{{ $savedCard['last_four'] }}</span>
                            </div>
                            <div class="mt-2 flex items-center gap-4 text-xs text-neutral-400">
                                <span>Vence {{ str_pad($savedCard['exp_month'] ?? '?', 2, '0', STR_PAD_LEFT) }}/{{ $savedCard['exp_year'] ?? '?' }}</span>
                                @if($savedCard['issuer'])
                                    <span>{{ $savedCard['issuer'] }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6 text-neutral-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-neutral-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <p class="text-sm">Sin tarjeta registrada</p>
                            <a href="{{ route('planes') }}" class="inline-block mt-2 text-xs font-semibold text-primary-500 hover:underline">
                                Agregar método de pago
                            </a>
                        </div>
                    @endif
                </div>

                {{-- Columna 3: Pago Yape / Renovación manual --}}
                <div class="flex flex-col gap-4">
                    <div class="bg-neutral-50 rounded-2xl p-6 border border-neutral-100 flex-1">
                        <h3 class="text-sm font-bold text-neutral-900 mb-3">Renovar con Yape</h3>
                        <p class="text-xs text-neutral-500 mb-4 leading-relaxed">
                            Los pagos son manuales vía Yape. Cuando tu plan esté por vencer — o si deseas renovarlo anticipadamente — escanea el QR desde
                            <a href="{{ route('pago.yape.show', $subscription['plan'] ?? 'monthly') }}" class="text-primary-500 underline">la página de pago</a>
                            y sube tu comprobante. El administrador validará el pago y extenderá tu vigencia.
                        </p>

                        @if($hasPendingYape)
                            <div class="flex items-center gap-2 px-4 py-3 bg-amber-50 text-amber-800 border border-amber-200 rounded-xl text-xs font-medium">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Tienes un comprobante pendiente de validación.
                            </div>
                        @elseif($showYapeForm)
                            <form wire:submit="submitYapePago" enctype="multipart/form-data" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-neutral-600 mb-1">Screenshot del pago <span class="text-red-400">*</span></label>
                                    <input type="file" wire:model="comprobante" accept="image/jpeg,image/png,image/webp"
                                        class="w-full text-xs text-neutral-600 file:mr-3 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:transition-colors file:cursor-pointer">
                                    @error('comprobante') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <input type="tel" wire:model="telefonoRenovacion" maxlength="9" inputmode="numeric"
                                        placeholder="Teléfono (opcional)"
                                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div>
                                    <input type="text" wire:model="referenciaAdicional" maxlength="200"
                                        placeholder="Referencia adicional (opcional)"
                                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit"
                                        class="flex-1 py-2 bg-primary-500 text-white text-xs font-bold rounded-full hover:bg-primary-400 transition-colors">
                                        Enviar comprobante
                                    </button>
                                    <button type="button" wire:click="toggleYapeForm"
                                        class="px-3 py-2 text-xs text-neutral-500 hover:text-neutral-700 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        @else
                            <button wire:click="toggleYapeForm"
                                class="w-full py-2.5 bg-primary-500 text-white text-xs font-bold rounded-full hover:bg-primary-400 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                                Subir comprobante de pago
                            </button>
                        @endif
                    </div>

                    <button wire:click="confirmCancel"
                            class="w-full py-3 px-4 bg-red-50 text-red-600 rounded-full text-sm font-medium hover:bg-red-100 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancelar suscripción
                    </button>
                </div>
            </div>

            {{-- Historial de pagos --}}
            @if(!empty($billingHistory))
                <div class="mt-8">
                    <h3 class="text-sm font-bold text-neutral-900 mb-4">Historial de pagos</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-neutral-100">
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-neutral-500 uppercase">Fecha</th>
                                    <th class="text-left py-3 px-3 text-xs font-semibold text-neutral-500 uppercase">Plan</th>
                                    <th class="text-right py-3 px-3 text-xs font-semibold text-neutral-500 uppercase">Monto</th>
                                    <th class="text-center py-3 px-3 text-xs font-semibold text-neutral-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billingHistory as $payment)
                                    <tr class="border-b border-neutral-50 hover:bg-neutral-50/50">
                                        <td class="py-3 px-3 text-neutral-700">{{ $payment['date'] }}</td>
                                        <td class="py-3 px-3 text-neutral-700">{{ $payment['plan'] }}</td>
                                        <td class="py-3 px-3 text-right text-neutral-900 font-bold">S/ {{ $payment['amount'] }}</td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                                {{ $payment['status'] === 'Pagado' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-neutral-100 text-neutral-600 border border-neutral-200' }}">
                                                {{ $payment['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        @else
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-neutral-100 mb-4">
                    <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-neutral-900 mb-2">Sin suscripción activa</h3>
                <p class="text-sm text-neutral-400 mb-6">Elige un plan para empezar.</p>
                <a href="{{ route('planes') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white rounded-full text-sm font-semibold hover:bg-primary-600 transition-colors">
                    Ver planes
                </a>
            </div>
        @endif
    </div>

    {{-- Modal de cancelación --}}
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
                    <p class="text-sm text-neutral-400 mt-2">Perderás acceso inmediato a las funcionalidades premium.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold text-neutral-600 mb-3">
                        ¿Por qué cancelas? <span class="text-red-400">(opcional)</span>
                    </label>
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
                    <button wire:click="dismissCancelModal"
                            class="flex-1 py-3 px-4 bg-neutral-100 text-neutral-700 rounded-full text-sm font-semibold hover:bg-neutral-200 transition-colors">
                        Mantener plan
                    </button>
                    <button wire:click="cancelSubscription" wire:loading.attr="disabled"
                            class="flex-1 py-3 px-4 bg-red-500 text-white rounded-full text-sm font-semibold hover:bg-red-600 transition-colors">
                        <span wire:loading.remove wire:target="cancelSubscription">Sí, cancelar</span>
                        <span wire:loading wire:target="cancelSubscription">Cancelando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
