<div class="p-3 sm:p-6 flex flex-col gap-4 sm:gap-6">
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-4 sm:p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Seguimiento de procesos</p>
            <h1 class="text-2xl lg:text-3xl font-bold text-neutral-900 mt-1">Calendario de vencimientos</h1>
            <p class="text-sm text-neutral-500 mt-1">Visualiza el rango de fechas y prioriza los procesos con cierre cercano.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 p-1">
                <button
                    type="button"
                    wire:click="mesAnterior"
                    class="w-10 h-10 rounded-full bg-white border border-neutral-200 text-neutral-600 hover:text-primary-600 hover:border-primary-400 shadow-sm transition-colors"
                    title="Mes anterior"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div class="px-5 py-2.5 rounded-full bg-white border border-neutral-200 text-sm font-semibold text-primary-600">
                    {{ $this->mesLabel }}
                </div>
                <button
                    type="button"
                    wire:click="mesSiguiente"
                    class="w-10 h-10 rounded-full bg-white border border-neutral-200 text-neutral-600 hover:text-primary-600 hover:border-primary-400 shadow-sm transition-colors"
                    title="Mes siguiente"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6">
        <div class="space-y-6">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-3 sm:p-6">
                <div class="overflow-x-auto -mx-1 px-1 pb-2">
                <div class="grid grid-cols-7 gap-1 sm:gap-2 text-xs font-semibold text-primary-500 mb-3 min-w-[600px]">
                    <div class="text-center">Lun</div>
                    <div class="text-center">Mar</div>
                    <div class="text-center">Mie</div>
                    <div class="text-center">Jue</div>
                    <div class="text-center">Vie</div>
                    <div class="text-center">Sab</div>
                    <div class="text-center">Dom</div>
                </div>

                <div class="grid grid-cols-7 gap-1 sm:gap-2 min-w-[600px]">
                    @foreach($dias as $dia)
                        @php
                            $dayClasses = $dia['mesActual']
                                ? 'bg-white border-primary-200'
                                : 'bg-neutral-50 border-neutral-100 text-neutral-400';
                            $dayText = $dia['mesActual'] ? 'text-primary-500' : 'text-neutral-400';
                        @endphp
                        <div class="min-h-[90px] sm:min-h-[128px] rounded-2xl border {{ $dayClasses }} p-1.5 sm:p-2.5 flex flex-col">
                            <div class="text-xs font-semibold {{ $dayText }} mb-2">
                                {{ $dia['numero'] }}
                            </div>
                            <div class="space-y-1.5 flex-1">
                                @foreach($dia['eventos'] as $evento)
                                    @php
                                        $badgeClass = match($evento['urgencia']) {
                                            'critico' => 'semaforo-red',
                                            'alto' => 'semaforo-orange',
                                            'medio' => 'semaforo-yellow',
                                            default => 'semaforo-green',
                                        };
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="verDetalle({{ $evento['id'] }})"
                                        class="w-full text-left px-2 py-1 rounded-lg border {{ $badgeClass }} text-[10px] font-semibold leading-tight truncate"
                                        title="{{ $evento['codigo'] }}"
                                    >
                                        {{ $evento['codigo'] }}
                                    </button>
                                @endforeach
                                @if($dia['extra'] > 0)
                                    <div class="text-[10px] text-neutral-400">+{{ $dia['extra'] }} mas</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-5 text-xs text-neutral-600">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full semaforo-dot-red"></span>
                        Vence en 2 dias o menos
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full semaforo-dot-orange"></span>
                        Vence en 3-5 dias
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full semaforo-dot-yellow"></span>
                        Vence en la semana
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full semaforo-dot-green"></span>
                        Fecha estable
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900">Procesos en seguimiento</h2>
                        <p class="text-xs text-neutral-400 mt-1">Mantiene la lista activa sin saturar el calendario.</p>
                    </div>
                </div>

                @if(empty($seguimientos))
                    <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-6 text-center text-sm text-neutral-500">
                        Aun no tienes procesos en seguimiento. Vuelve al buscador publico para agregar uno.
                    </div>
                @else
                    <div class="space-y-3 max-h-[520px] overflow-y-auto pr-1">
                        @foreach($seguimientos as $seguimiento)
                            @php
                                $cardClass = match($seguimiento['urgencia']) {
                                    'critico' => 'semaforo-card-red',
                                    'alto' => 'semaforo-card-orange',
                                    'medio' => 'semaforo-card-yellow',
                                    default => 'semaforo-card-green',
                                };
                            @endphp
                            <button
                                type="button"
                                wire:click="verDetalle({{ $seguimiento['id'] }})"
                                class="w-full text-left rounded-2xl border {{ $cardClass }} p-4 transition-colors hover:border-primary-400"
                            >
                                <p class="text-sm font-bold text-neutral-900">{{ $seguimiento['codigo'] }}</p>
                                <p class="text-xs text-neutral-500 mt-1">{{ $seguimiento['entidad'] ?? 'Entidad no disponible' }}</p>
                                <div class="mt-3 flex items-center justify-between text-xs text-neutral-600">
                                    <span>Inicio: {{ $seguimiento['inicio_label'] ?? 'N/D' }}</span>
                                    <span>Fin: {{ $seguimiento['fin_label'] ?? 'N/D' }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900">Detalle rapido</h2>
                        <p class="text-xs text-neutral-400 mt-1">Selecciona un item del calendario para ver mas.</p>
                    </div>
                    @if($detalleSeguimiento)
                        <button
                            type="button"
                            wire:click="cerrarDetalle"
                            class="w-8 h-8 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                @if(!$detalleSeguimiento)
                    <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-5 text-sm text-neutral-500">
                        Haz click en un proceso del calendario o de la lista para ver su informacion completa.
                    </div>
                @else
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Proceso</p>
                            <p class="text-base font-bold text-neutral-900 mt-1">{{ $detalleSeguimiento['codigo'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Entidad</p>
                            <p class="text-sm text-neutral-700 mt-1">{{ $detalleSeguimiento['entidad'] ?? 'Entidad no disponible' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Objeto</p>
                            <p class="text-sm text-neutral-700 mt-1">{{ $detalleSeguimiento['objeto'] ?? 'Objeto no disponible' }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-3">
                                <p class="text-[11px] font-semibold text-neutral-400 uppercase">Inicio</p>
                                <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $detalleSeguimiento['inicio_label'] ?? 'N/D' }}</p>
                            </div>
                            <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-3">
                                <p class="text-[11px] font-semibold text-neutral-400 uppercase">Fin</p>
                                <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $detalleSeguimiento['fin_label'] ?? 'N/D' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Estado</p>
                            <p class="text-sm text-neutral-700 mt-1">{{ $detalleSeguimiento['estado'] ?? 'No disponible' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
