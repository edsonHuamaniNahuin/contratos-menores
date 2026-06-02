<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">Clientes en Mercado Pago</h1>
                <p class="text-sm text-neutral-400 mt-2">
                    {{ $totalClientes }} cliente(s) · {{ $totalTarjetas }} tarjeta(s) guardada(s)
                </p>
            </div>
            <button wire:click="cargarClientes" wire:loading.attr="disabled"
                    class="px-5 py-2.5 bg-primary-500 text-white rounded-full text-sm font-semibold hover:bg-primary-600 transition-colors flex items-center gap-2">
                <svg wire:loading wire:target="cargarClientes" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Actualizar</span>
            </button>
        </div>

        @if($error)
            <div class="bg-red-50 border-l-4 border-red-500 rounded-2xl p-4 mb-6">
                <p class="text-sm text-red-700">{{ $error }}</p>
            </div>
        @endif

        @if($cargando)
            <div class="text-center py-12 text-neutral-400">
                <svg class="animate-spin h-8 w-8 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm">Consultando Mercado Pago...</p>
            </div>
        @elseif(empty($clientes))
            <div class="text-center py-12 text-neutral-400">
                <p class="text-sm">No hay clientes registrados en Mercado Pago aún.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($clientes as $cliente)
                    <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-100">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-800 font-bold text-sm">
                                    {{ strtoupper(substr($cliente['nombre'], 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-neutral-900">{{ $cliente['nombre'] }}</p>
                                    <p class="text-xs text-neutral-400">{{ $cliente['email'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold
                                    {{ $cliente['estado'] === 'active' ? 'bg-secondary-500/10 text-secondary-600' : 'bg-neutral-200 text-neutral-600' }}">
                                    {{ $cliente['estado'] === 'active' ? 'Activo' : ucfirst($cliente['estado']) }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-primary-500/10 text-primary-800">
                                    {{ match($cliente['plan']) { 'trial' => 'Trial', 'monthly' => 'Mensual', 'yearly' => 'Anual', default => ucfirst($cliente['plan']) } }}
                                </span>
                                @if($cliente['vence'])
                                    <span class="text-xs text-neutral-400">Vence {{ $cliente['vence'] }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Customer ID de MP --}}
                        <div class="text-xs text-neutral-400 mb-2 font-mono">
                            MP ID: {{ $cliente['mp_customer_id'] }}
                        </div>

                        {{-- Tarjetas --}}
                        @if(!empty($cliente['tarjetas']))
                            <div class="flex flex-wrap gap-2">
                                @foreach($cliente['tarjetas'] as $tarjeta)
                                    <div class="inline-flex items-center gap-2 bg-white rounded-xl px-3 py-2 border border-neutral-200 text-xs">
                                        <span class="font-semibold text-neutral-900">{{ $tarjeta['marca'] }}</span>
                                        <span class="text-neutral-500 font-mono">••••{{ $tarjeta['ultimos_digitos'] }}</span>
                                        <span class="text-neutral-400">{{ $tarjeta['vencimiento'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-neutral-400">Sin tarjetas guardadas</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
