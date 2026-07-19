<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Seguimiento de procesos</p>
                <h1 class="text-2xl lg:text-3xl font-bold text-neutral-900 mt-1">Calendario de vencimientos</h1>
                <p class="text-sm text-neutral-500 mt-1">Visualiza rangos de fecha y prioriza los procesos con cierre cercano.</p>
            </div>
            <div x-data="{ currentView: 'dayGridMonth' }" class="flex items-center gap-2 flex-wrap">
                <button
                    @click="window.__fcSeguimientos?.today()"
                    class="px-4 py-2 rounded-full text-sm font-semibold bg-brand-600 text-white hover:bg-brand-500 transition-colors shadow-sm"
                >
                    Hoy
                </button>
                <div class="inline-flex items-center rounded-full border border-neutral-300 bg-neutral-100 p-1">
                    <button
                        @click="currentView = 'dayGridMonth'; window.__fcSeguimientos?.changeView('dayGridMonth')"
                        :class="currentView === 'dayGridMonth'
                            ? 'bg-white text-neutral-900 shadow-sm font-bold'
                            : 'text-neutral-500 hover:text-neutral-700 font-medium'"
                        class="px-4 py-1.5 rounded-full text-xs transition-all"
                    >Mes</button>
                    <button
                        @click="currentView = 'dayGridWeek'; window.__fcSeguimientos?.changeView('dayGridWeek')"
                        :class="currentView === 'dayGridWeek'
                            ? 'bg-white text-neutral-900 shadow-sm font-bold'
                            : 'text-neutral-500 hover:text-neutral-700 font-medium'"
                        class="px-4 py-1.5 rounded-full text-xs transition-all"
                    >Semana</button>
                    <button
                        @click="currentView = 'listMonth'; window.__fcSeguimientos?.changeView('listMonth')"
                        :class="currentView === 'listMonth'
                            ? 'bg-white text-neutral-900 shadow-sm font-bold'
                            : 'text-neutral-500 hover:text-neutral-700 font-medium'"
                        class="px-4 py-1.5 rounded-full text-xs transition-all"
                    >Lista</button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <div class="absolute top-0 left-0 w-1 h-full bg-brand-600"></div>
            <p class="text-3xl font-black text-neutral-900">{{ $totalSeguimientos }}</p>
            <p class="text-xs text-neutral-400 mt-1 font-medium">Total Seguimientos</p>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
            <div class="flex items-center gap-2">
                <p class="text-3xl font-black" style="color: #C0392B;">{{ $totalCriticos }}</p>
            </div>
            <p class="text-xs text-neutral-400 mt-1 font-medium">Críticos (&le;2d)</p>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
            <p class="text-3xl font-black" style="color: #E67E22;">{{ $totalAltos }}</p>
            <p class="text-xs text-neutral-400 mt-1 font-medium">Urgentes (3-5d)</p>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-soft border border-neutral-100 p-5">
            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
            <p class="text-3xl font-black" style="color: #27AE60;">{{ $totalEstables }}</p>
            <p class="text-xs text-neutral-400 mt-1 font-medium">Estables</p>
        </div>
    </div>

    {{-- Calendar (full width) --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-3 sm:p-6">
        <div id="fc-seguimientos" wire:ignore class="fc-sequence"></div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap items-center gap-3 mt-5 pt-4 border-t border-neutral-100 text-xs text-neutral-600">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full semaforo-dot-red"></span>
                Vence en &le;2 dias
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full semaforo-dot-orange"></span>
                Vence en 3-5 dias
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full semaforo-dot-yellow"></span>
                Vence esta semana
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full semaforo-dot-green"></span>
                Fecha estable
            </div>
        </div>
    </div>

    {{-- Modal Detalle de Proceso --}}
    @if($detalleSeguimiento)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.cerrarDetalle()"
        >
            <div
                class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"
                wire:click="cerrarDetalle"
            ></div>

            <div class="relative bg-white rounded-[2rem] shadow-soft border border-neutral-200 w-full max-w-lg max-h-[90vh] flex flex-col z-10">
                {{-- Header --}}
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-neutral-100">
                    @php
                        $urgColor = match($detalleSeguimiento['urgencia'] ?? 'estable') {
                            'critico' => 'bg-red-500',
                            'alto'    => 'bg-amber-500',
                            'medio'   => 'bg-yellow-500',
                            default   => 'bg-emerald-500',
                        };
                    @endphp
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $urgColor }}"></span>
                        <div class="min-w-0">
                            <h3 class="text-base font-bold text-neutral-900 truncate">{{ $detalleSeguimiento['codigo'] }}</h3>
                            <p class="text-xs text-neutral-400 truncate">{{ $detalleSeguimiento['entidad'] ?? '' }}</p>
                        </div>
                    </div>
                    <button wire:click="cerrarDetalle"
                        class="flex-shrink-0 w-8 h-8 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto px-6 py-5 space-y-4 text-sm">
                    {{-- Risk + State badges --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        @php
                            $urgLabel = match($detalleSeguimiento['urgencia'] ?? 'estable') {
                                'critico' => 'Crítico',
                                'alto'    => 'Urgente',
                                'medio'   => 'Atención',
                                default   => 'Estable',
                            };
                            $urgBadge = match($detalleSeguimiento['urgencia'] ?? 'estable') {
                                'critico' => 'bg-red-50 text-red-700 border-red-200',
                                'alto'    => 'bg-amber-50 text-amber-700 border-amber-200',
                                'medio'   => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                default   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            };
                        @endphp
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $urgBadge }}">{{ $urgLabel }}</span>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold bg-primary-50 text-primary-700 border border-primary-200">{{ $detalleSeguimiento['tipo'] === 'mayores' ? 'Contrato Mayor' : 'Contrato Menor' }}</span>
                        @if(!empty($detalleSeguimiento['estado']))
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200">{{ $detalleSeguimiento['estado'] }}</span>
                        @endif
                        @if(isset($detalleSeguimiento['vigente']))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $detalleSeguimiento['vigente'] ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $detalleSeguimiento['vigente'] ? 'bg-green-500 animate-pulse' : 'bg-red-400' }}"></span>
                                {{ $detalleSeguimiento['vigente'] ? 'VIGENTE' : ($detalleSeguimiento['estado_vigencia'] ?? 'NO VIGENTE') }}
                            </span>
                        @endif
                    </div>

                    {{-- Info Grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        @if(!empty($detalleSeguimiento['entidad']))
                            <div class="col-span-2">
                                <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Entidad</p>
                                <p class="text-sm text-neutral-800 mt-0.5">{{ $detalleSeguimiento['entidad'] }}</p>
                            </div>
                        @endif
                        @if(!empty($detalleSeguimiento['entidad_ruc']))
                            <div>
                                <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">RUC</p>
                                <p class="text-sm font-medium text-neutral-700 mt-0.5">{{ $detalleSeguimiento['entidad_ruc'] }}</p>
                            </div>
                        @endif
                        @if(!empty($detalleSeguimiento['objeto']))
                            <div>
                                <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Objeto</p>
                                <p class="text-sm font-medium text-neutral-700 mt-0.5">{{ $detalleSeguimiento['objeto'] }}</p>
                            </div>
                        @endif
                        @if(!empty($detalleSeguimiento['metodo']))
                            <div>
                                <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Método</p>
                                <p class="text-sm font-medium text-neutral-700 mt-0.5">{{ $detalleSeguimiento['metodo'] }}</p>
                            </div>
                        @endif
                        @if(!empty($detalleSeguimiento['moneda']) || !empty($detalleSeguimiento['monto']))
                            <div>
                                <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Moneda</p>
                                <p class="text-sm font-medium text-neutral-700 mt-0.5">{{ $detalleSeguimiento['moneda'] ?? '---' }}</p>
                            </div>
                            @if(!empty($detalleSeguimiento['monto']))
                                <div>
                                    <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Monto Ref.</p>
                                    <p class="text-sm font-bold text-neutral-900 mt-0.5">{{ $detalleSeguimiento['monto'] }}</p>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- Fechas --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-neutral-50 rounded-xl p-3 text-center border border-neutral-100">
                            <p class="text-[10px] font-semibold text-neutral-400 uppercase">Publicación</p>
                            <p class="text-xs font-semibold text-neutral-800 mt-1">{{ $detalleSeguimiento['fecha_publicacion'] ?? $detalleSeguimiento['inicio_label'] ?? 'N/D' }}</p>
                        </div>
                        <div class="bg-neutral-50 rounded-xl p-3 text-center border border-neutral-100">
                            <p class="text-[10px] font-semibold text-neutral-400 uppercase">Inicio</p>
                            <p class="text-xs font-semibold text-neutral-800 mt-1">{{ $detalleSeguimiento['inicio_label'] ?? 'N/D' }}</p>
                        </div>
                        <div class="bg-neutral-50 rounded-xl p-3 text-center border border-neutral-100">
                            <p class="text-[10px] font-semibold text-neutral-400 uppercase">Fin</p>
                            <p class="text-xs font-semibold text-neutral-800 mt-1">{{ $detalleSeguimiento['fin_label'] ?? 'N/D' }}</p>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    @if(!empty($detalleSeguimiento['descripcion']))
                        <div>
                            <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Descripción</p>
                            <p class="text-xs text-neutral-600 leading-relaxed line-clamp-3">{{ $detalleSeguimiento['descripcion'] }}</p>
                        </div>
                    @endif

                    {{-- Dirección --}}
                    @if(!empty($detalleSeguimiento['entidad_direccion']))
                        <div>
                            <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Dirección</p>
                            <p class="text-xs text-neutral-600">{{ $detalleSeguimiento['entidad_direccion'] }}</p>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex flex-wrap gap-2 pt-2 border-t border-neutral-100">
                        @if(!empty($detalleSeguimiento['url_documento']))
                            <a href="{{ $detalleSeguimiento['url_documento'] }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-600 hover:text-brand-600 hover:border-primary-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                                Descargar TDR
                            </a>
                        @endif
                        @if(!empty($detalleSeguimiento['ocid']))
                            <a href="{{ url('/buscador-contratos-mayores?query=' . urlencode($detalleSeguimiento['ocid'])) }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-primary-500 text-white text-xs font-semibold hover:bg-primary-400 transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Ver en bandeja
                            </a>
                        @endif
                    </div>

                    {{-- Dejar de seguir --}}
                    <div class="pt-1">
                        <button
                            type="button"
                            wire:click="eliminarSeguimiento({{ is_string($detalleSeguimiento['id']) ? "'".$detalleSeguimiento['id']."'" : $detalleSeguimiento['id'] }})"
                            wire:confirm="¿Seguro que deseas dejar de seguir este proceso?"
                            class="w-full px-4 py-2 rounded-full border border-neutral-200 text-xs font-medium text-neutral-400 hover:border-red-300 hover:text-red-600 transition-colors"
                        >
                            Dejar de seguir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FullCalendar: Sequence Dashboard Theme --}}
    <style>
    .fc-sequence {
    --fc-border-color: #F3F4F6;
    --fc-today-bg-color: rgba(2, 89, 100, 0.04);
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: #F9FAFB;
    --fc-event-border-color: transparent;
    font-family: inherit;
}
.fc-sequence .fc-toolbar-title {
    font-size: 1.125rem !important;
    font-weight: 700 !important;
    color: #111827 !important;
}
.fc-sequence .fc-button {
    border-radius: 9999px !important;
    border: 1px solid #E5E7EB !important;
    background: #FFFFFF !important;
    color: #4B5563 !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    padding: 0.4rem 0.75rem !important;
    box-shadow: none !important;
    text-transform: none !important;
    transition: all 0.15s !important;
}
.fc-sequence .fc-button:hover {
    border-color: #7BA8AD !important;
    color: #025964 !important;
}
.fc-sequence .fc-button-active,
.fc-sequence .fc-button:active {
    background: #025964 !important;
    color: #FFFFFF !important;
    border-color: #025964 !important;
}
.fc-sequence .fc-button:focus {
    box-shadow: 0 0 0 2px rgba(2, 89, 100, 0.2) !important;
}
.fc-sequence .fc-prev-button,
.fc-sequence .fc-next-button {
    width: 2.25rem !important;
    height: 2.25rem !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.fc-sequence .fc-toolbar.fc-header-toolbar {
    margin-bottom: 1.25rem !important;
}
.fc-sequence .fc-col-header-cell {
    padding: 0.625rem 0 !important;
    background: transparent !important;
}
.fc-sequence .fc-col-header-cell-cushion {
    font-size: 0.6875rem !important;
    font-weight: 700 !important;
    color: #025964 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.06em !important;
    text-decoration: none !important;
}
.fc-sequence .fc-scrollgrid {
    border: none !important;
}
.fc-sequence .fc-scrollgrid td,
.fc-sequence .fc-scrollgrid th {
    border-color: #F3F4F6 !important;
}
.fc-sequence .fc-daygrid-day {
    transition: background-color 0.1s !important;
}
.fc-sequence .fc-daygrid-day:hover {
    background-color: rgba(2, 89, 100, 0.02) !important;
}
.fc-sequence .fc-daygrid-day-number {
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #6B7280 !important;
    padding: 0.5rem !important;
    text-decoration: none !important;
}
.fc-sequence .fc-day-today .fc-daygrid-day-number {
    background: #025964 !important;
    color: #FFFFFF !important;
    border-radius: 9999px !important;
    width: 1.75rem !important;
    height: 1.75rem !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.fc-sequence .fc-daygrid-day-frame {
    min-height: 100px !important;
}
.fc-sequence .fc-daygrid-event {
    border-radius: 0.5rem !important;
    font-size: 0.6875rem !important;
    font-weight: 600 !important;
    padding: 2px 6px !important;
    margin: 1px 2px !important;
    border: none !important;
    cursor: pointer !important;
    transition: opacity 0.15s, transform 0.15s !important;
}
.fc-sequence .fc-daygrid-event:hover {
    opacity: 0.85 !important;
    transform: scale(1.02) !important;
}
.fc-sequence .fc-daygrid-more-link {
    font-size: 0.6875rem !important;
    font-weight: 700 !important;
    color: #025964 !important;
    padding: 2px 4px !important;
}
.fc-sequence .fc-popover {
    border-radius: 1rem !important;
    border: 1px solid #E5E7EB !important;
    box-shadow: 0 4px 20px -2px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
}
.fc-sequence .fc-popover-header {
    background: #F9FAFB !important;
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    color: #025964 !important;
    padding: 0.5rem 0.75rem !important;
}
.fc-sequence .fc-list {
    border: none !important;
}
.fc-sequence .fc-list-day-cushion {
    background: #F9FAFB !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    color: #025964 !important;
}
.fc-sequence .fc-list-event:hover td {
    background-color: rgba(2, 89, 100, 0.04) !important;
}
.fc-sequence .fc-list-event-dot {
    border-radius: 9999px !important;
}
.fc-sequence .fc-list-event-title a,
.fc-sequence .fc-list-event-title {
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #111827 !important;
}
.fc-sequence .fc-list-empty-cushion {
    font-size: 0.875rem !important;
    color: #9CA3AF !important;
    padding: 2rem !important;
}
.fc-sequence .fc-day-other .fc-daygrid-day-number {
    color: #D1D5DB !important;
}
.fc-sequence .fc-scroller {
    overflow: hidden auto !important;
}
</style>

{{-- FullCalendar CDN --}}
@assets
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
@endassets

{{-- Calendar initialization --}}
@script
<script>
    const calendarEl = document.getElementById('fc-seguimientos');
    if (!calendarEl) return;

    function mapSeguimientos(data) {
        return (data || []).map(function(s) {
            const end = s.fin || s.inicio;
            let endDate = null;
            if (end) {
                const d = new Date(end + 'T00:00:00');
                d.setDate(d.getDate() + 1);
                endDate = d.toISOString().split('T')[0];
            }
            const colors = {
                critico: '#C0392B',
                alto: '#E67E22',
                medio: '#F1C40F',
                estable: '#27AE60',
                normal: '#27AE60'
            };
            return {
                id: String(s.id),
                title: s.codigo || 'Sin codigo',
                start: s.inicio,
                end: endDate,
                backgroundColor: colors[s.urgencia] || '#27AE60',
                borderColor: colors[s.urgencia] || '#27AE60',
                textColor: s.urgencia === 'medio' ? '#1F2937' : '#FFFFFF',
            };
        });
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: ''
        },
        height: 'auto',
        firstDay: 1,
        fixedWeekCount: false,
        dayMaxEvents: 3,
        moreLinkText: 'mas',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            list: 'Lista'
        },
        noEventsText: 'No hay procesos en este periodo',
        events: mapSeguimientos($wire.seguimientos),
        eventClick: function(info) {
            $wire.verDetalle(info.event.id);
        },
    });

    calendar.render();
    window.__fcSeguimientos = calendar;

    $wire.on('seguimientos-updated', () => {
        calendar.removeAllEvents();
        calendar.addEventSource(mapSeguimientos($wire.seguimientos));
    });
</script>
    @endscript
</div>
