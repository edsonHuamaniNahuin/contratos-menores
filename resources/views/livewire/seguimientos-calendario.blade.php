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
                    class="px-4 py-2 rounded-full bg-white border border-neutral-200 text-sm font-medium text-neutral-600 hover:border-primary-400 hover:text-primary-800 transition-colors shadow-sm"
                >
                    Hoy
                </button>
                <div class="inline-flex items-center gap-1 rounded-full border border-neutral-200 bg-neutral-50 p-1">
                    <button
                        @click="currentView = 'dayGridMonth'; window.__fcSeguimientos?.changeView('dayGridMonth')"
                        :class="currentView === 'dayGridMonth' ? 'bg-primary-800 text-white shadow-sm' : 'bg-white text-neutral-600 hover:text-primary-800'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all"
                    >Mes</button>
                    <button
                        @click="currentView = 'dayGridWeek'; window.__fcSeguimientos?.changeView('dayGridWeek')"
                        :class="currentView === 'dayGridWeek' ? 'bg-primary-800 text-white shadow-sm' : 'bg-white text-neutral-600 hover:text-primary-800'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all"
                    >Semana</button>
                    <button
                        @click="currentView = 'listMonth'; window.__fcSeguimientos?.changeView('listMonth')"
                        :class="currentView === 'listMonth' ? 'bg-primary-800 text-white shadow-sm' : 'bg-white text-neutral-600 hover:text-primary-800'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all"
                    >Lista</button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-neutral-900">{{ $totalSeguimientos }}</p>
            <p class="text-xs text-neutral-400 mt-1">Total Seguimientos</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold" style="color: #C0392B;">{{ $totalCriticos }}</p>
            <p class="text-xs text-neutral-400 mt-1">Criticos (&le;2d)</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold" style="color: #E67E22;">{{ $totalAltos }}</p>
            <p class="text-xs text-neutral-400 mt-1">Urgentes (3-5d)</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold" style="color: #27AE60;">{{ $totalEstables }}</p>
            <p class="text-xs text-neutral-400 mt-1">Estables</p>
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
            {{-- Overlay --}}
            <div
                class="absolute inset-0 bg-neutral-900/40 backdrop-blur-sm"
                wire:click="cerrarDetalle"
            ></div>

            {{-- Panel --}}
            <div class="relative bg-white rounded-3xl shadow-soft border border-neutral-100 w-full max-w-md p-6 sm:p-8 z-10">
                {{-- Cerrar --}}
                <button
                    type="button"
                    wire:click="cerrarDetalle"
                    class="absolute top-4 right-4 w-8 h-8 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                @php
                    $urgLabel = match($detalleSeguimiento['urgencia'] ?? 'estable') {
                        'critico' => ['Critico', 'semaforo-card-red'],
                        'alto'    => ['Urgente', 'semaforo-card-orange'],
                        'medio'   => ['Medio', 'semaforo-card-yellow'],
                        default   => ['Estable', 'semaforo-card-green'],
                    };
                @endphp

                <div class="space-y-4">
                    <div class="inline-flex px-3 py-1 rounded-full border text-xs font-semibold {{ $urgLabel[1] }}">
                        {{ $urgLabel[0] }}
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Proceso</p>
                        <p class="text-lg font-bold text-neutral-900 mt-0.5">{{ $detalleSeguimiento['codigo'] }}</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Entidad</p>
                        <p class="text-sm text-neutral-700 mt-0.5">{{ $detalleSeguimiento['entidad'] ?? 'No disponible' }}</p>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Objeto</p>
                        <p class="text-sm text-neutral-700 mt-0.5">{{ $detalleSeguimiento['objeto'] ?? 'No disponible' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-neutral-50 border border-neutral-100 rounded-2xl p-3">
                            <p class="text-[10px] font-semibold text-neutral-400 uppercase">Inicio</p>
                            <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $detalleSeguimiento['inicio_label'] ?? 'N/D' }}</p>
                        </div>
                        <div class="bg-neutral-50 border border-neutral-100 rounded-2xl p-3">
                            <p class="text-[10px] font-semibold text-neutral-400 uppercase">Fin</p>
                            <p class="text-sm font-semibold text-neutral-900 mt-1">{{ $detalleSeguimiento['fin_label'] ?? 'N/D' }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Estado</p>
                        <p class="text-sm text-neutral-700 mt-0.5">{{ $detalleSeguimiento['estado'] ?? 'No disponible' }}</p>
                    </div>

                    <div class="pt-3 border-t border-neutral-100">
                        <button
                            type="button"
                            wire:click="eliminarSeguimiento({{ $detalleSeguimiento['id'] }})"
                            wire:confirm="Seguro que deseas dejar de seguir este proceso?"
                            class="w-full px-4 py-2.5 rounded-full border border-neutral-200 text-xs font-medium text-neutral-500 hover:border-red-300 hover:text-red-600 transition-colors"
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
            $wire.verDetalle(parseInt(info.event.id));
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
