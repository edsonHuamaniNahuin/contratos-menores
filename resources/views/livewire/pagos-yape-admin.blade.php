<div class="p-4 lg:p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl lg:text-2xl font-bold text-neutral-900">Pagos Yape</h1>
            <p class="text-sm text-neutral-500">Validación manual de comprobantes de pago</p>
        </div>
        @if($conteoPendientes > 0)
            <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full animate-pulse">
                {{ $conteoPendientes }} pendiente(s)
            </span>
        @endif
    </div>

    {{-- Filter tabs --}}
    <div class="flex items-center gap-1 bg-neutral-100 rounded-xl p-1 w-fit">
        @foreach(['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados', 'todas' => 'Todas'] as $val => $label)
            <button wire:click="$set('estadoFiltro', '{{ $val }}')"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $estadoFiltro === $val ? 'bg-white shadow-sm text-primary-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if(count($pagos) === 0)
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 p-12 text-center">
            <p class="text-neutral-400">No hay pagos en este estado.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 border-b border-neutral-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Monto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-400 uppercase">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50">
                    @foreach($pagos as $pago)
                        <tr class="hover:bg-neutral-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600 text-xs font-bold">
                                        {{ strtoupper(substr($pago->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-neutral-900">{{ $pago->user->name ?? 'N/A' }}</p>
                                        <p class="text-xs text-neutral-400">{{ $pago->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $planLabels = [
                                        'monthly' => 'Mensual',
                                        'yearly' => 'Anual',
                                        'mayores-premium' => 'Mayores Premium',
                                    ];
                                @endphp
                                <span class="text-xs font-medium text-neutral-700">{{ $planLabels[$pago->plan] ?? $pago->plan }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $tipoColors = [
                                        'nuevo' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'renovacion' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $tipoColors[$pago->tipo] ?? 'bg-neutral-50 text-neutral-500 border-neutral-200' }}">
                                    {{ $pago->tipo === 'renovacion' ? 'Renovación' : 'Nuevo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-neutral-900">S/ {{ number_format($pago->monto, 2) }}</td>
                            <td class="px-4 py-3 text-xs text-neutral-500">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $estadoColors = [
                                        'pendiente' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'aprobado' => 'bg-green-50 text-green-700 border-green-200',
                                        'rechazado' => 'bg-red-50 text-red-600 border-red-200',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $estadoColors[$pago->estado] ?? '' }}">
                                    {{ ucfirst($pago->estado) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="verComprobante({{ $pago->id }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-500 hover:text-primary-600 hover:border-primary-300 transition-colors" title="Ver comprobante">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @if($pago->isPendiente())
                                        <button wire:click="confirmar({{ $pago->id }}, 'aprobar')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-green-500 text-white hover:bg-green-400 transition-colors" title="Aprobar y activar Premium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button wire:click="confirmar({{ $pago->id }}, 'rechazar')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors" title="Rechazar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Comprobante Modal --}}
    @if($selectedPagoId)
        @php $selectedPago = $pagos->firstWhere('id', $selectedPagoId); @endphp
        @if($selectedPago)
            <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarComprobante()">
                <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarComprobante"></div>
                <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
                        <div>
                            <h3 class="font-bold text-neutral-900">Comprobante — {{ $selectedPago->user->name }}</h3>
                            <p class="text-xs text-neutral-400">{{ $selectedPago->created_at->format('d/m/Y H:i') }} · S/ {{ number_format($selectedPago->monto, 2) }}</p>
                        </div>
                        <button wire:click="cerrarComprobante" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 transition-colors">Cerrar</button>
                    </div>
                    <div class="p-6 overflow-y-auto space-y-4">
                        <img src="{{ route('comprobante.yape', $selectedPago) }}" alt="Comprobante" class="w-full rounded-2xl border border-neutral-200 shadow-sm">
                        @if($selectedPago->nombre_original)
                            <p class="text-[10px] text-neutral-400 text-center">Archivo original: {{ $selectedPago->nombre_original }}</p>
                        @endif
                        @if($selectedPago->referencia_adicional)
                            <div class="bg-neutral-50 rounded-xl p-3">
                                <p class="text-xs font-semibold text-neutral-400 uppercase mb-1">Referencia adicional</p>
                                <p class="text-sm text-neutral-700">{{ $selectedPago->referencia_adicional }}</p>
                            </div>
                        @endif
                        @if($selectedPago->telefono)
                            <div class="bg-neutral-50 rounded-xl p-3">
                                <p class="text-xs font-semibold text-neutral-400 uppercase mb-1">Teléfono</p>
                                <p class="text-sm text-neutral-700">{{ $selectedPago->telefono }}</p>
                            </div>
                        @endif
                        @if($selectedPago->isPendiente())
                            <div class="flex items-center gap-2 pt-2">
                                <button wire:click="confirmar({{ $selectedPago->id }}, 'aprobar')"
                                    class="flex-1 py-2.5 bg-green-500 text-white text-sm font-bold rounded-full hover:bg-green-400 transition-colors">
                                    Aprobar y Activar Premium
                                </button>
                                <button wire:click="confirmar({{ $selectedPago->id }}, 'rechazar')"
                                    class="flex-1 py-2.5 border border-red-200 text-red-500 text-sm font-bold rounded-full hover:bg-red-50 transition-colors">
                                    Rechazar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Confirmation Modal --}}
    @if($confirmPagoId)
        @php $cfPago = $pagos->firstWhere('id', $confirmPagoId); @endphp
        @if($cfPago)
            <div class="fixed inset-0 z-[140] flex items-center justify-center px-4" x-data x-on:keydown.escape.window="$wire.cancelarConfirm()">
                <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelarConfirm"></div>
                <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-8 text-center">
                    @if($confirmAction === 'aprobar')
                        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-neutral-900 mb-1">¿Confirmar aprobación?</h3>
                        <p class="text-sm text-neutral-500 mb-2">Se activará Premium para <strong>{{ $cfPago->user->name }}</strong></p>
                        <p class="text-xs text-neutral-400 mb-5">Plan: {{ $cfPago->plan }} · S/ {{ number_format($cfPago->monto, 2) }}</p>
                        <div class="flex items-center gap-3">
                            <button wire:click="cancelarConfirm" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50 transition-colors">Cancelar</button>
                            <button wire:click="aprobar({{ $cfPago->id }})" class="flex-1 py-2.5 bg-green-500 text-white rounded-full text-sm font-bold hover:bg-green-400 transition-colors">Confirmar</button>
                        </div>
                    @else
                        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-neutral-900 mb-1">¿Confirmar rechazo?</h3>
                        <p class="text-sm text-neutral-500 mb-4">Se rechazará el pago de <strong>{{ $cfPago->user->name }}</strong></p>
                        <div class="text-left mb-4">
                            <label class="block text-xs font-semibold text-neutral-500 mb-1">Motivo del rechazo <span class="text-red-400">*</span></label>
                            <textarea wire:model="justificacionRechazo" rows="3" maxlength="500"
                                placeholder="Explica por qué se rechaza este pago. Se enviará al usuario por correo."
                                class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"></textarea>
                        </div>
                        <div class="flex items-center gap-3">
                            <button wire:click="cancelarConfirm" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50 transition-colors">Cancelar</button>
                            <button wire:click="rechazar({{ $cfPago->id }})" class="flex-1 py-2.5 bg-red-500 text-white rounded-full text-sm font-bold hover:bg-red-400 transition-colors">Rechazar</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
