<div>
    {{-- ═══════════════════════════════════════════════════════════════
         FILTROS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            {{-- Departamento --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-neutral-500 mb-1">Departamento</label>
                <select wire:model.live="filtroDepartamento" class="w-full text-sm border border-neutral-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">Todos</option>
                    @foreach($departamentos as $dep)
                        <option value="{{ $dep['id'] }}">{{ $dep['nom'] }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Entidad --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-neutral-500 mb-1">Entidad</label>
                <input type="text" wire:model.live.debounce.400ms="filtroEntidad" placeholder="Buscar entidad..."
                       class="w-full text-sm border border-neutral-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            {{-- Fecha desde --}}
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-neutral-500 mb-1">Desde</label>
                <input type="date" wire:model.live="fechaDesde"
                       class="w-full text-sm border border-neutral-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            {{-- Fecha hasta --}}
            <div class="min-w-[140px]">
                <label class="block text-xs font-medium text-neutral-500 mb-1">Hasta</label>
                <input type="date" wire:model.live="fechaHasta"
                       class="w-full text-sm border border-neutral-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            {{-- Limpiar --}}
            <button wire:click="limpiarFiltros" class="text-xs font-medium text-neutral-500 hover:text-red-500 transition-colors whitespace-nowrap px-3 py-2">
                Limpiar filtros
            </button>
        </div>

        @if($nombreDepartamento || $filtroEntidad)
        <div class="mt-3 flex flex-wrap gap-2">
            @if($nombreDepartamento)
            <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs font-medium px-3 py-1 rounded-full">
                {{ $nombreDepartamento }}
                <button wire:click="$set('filtroDepartamento', null)" class="hover:text-red-500">&times;</button>
            </span>
            @endif
            @if($filtroEntidad)
            <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs font-medium px-3 py-1 rounded-full">
                "{{ $filtroEntidad }}"
                <button wire:click="$set('filtroEntidad', null)" class="hover:text-red-500">&times;</button>
            </span>
            @endif
        </div>
        @endif
    </div>

    @if($counters['total'] > 0)
    {{-- ═══════════════════════════════════════════════════════════════
         CARDS RESUMEN
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-neutral-400">Inteligencia anticorrupción</p>
                <h3 class="text-2xl font-bold text-neutral-900 mt-1">Análisis de Direccionamiento eTDR</h3>
                <p class="text-xs text-neutral-500 mt-1">Resultados de {{ $counters['total'] }} análisis de direccionamiento con IA</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-neutral-600 bg-neutral-50 border border-neutral-100 px-3 py-1 rounded-full">
                    Score promedio: {{ $counters['score_promedio'] }}/100
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                <p class="text-xs text-neutral-400">Total analizados</p>
                <p class="text-2xl font-bold text-neutral-900">{{ $counters['total'] }}</p>
            </div>
            <div class="bg-neutral-50 border border-secondary-200 rounded-3xl p-4">
                <p class="text-xs text-neutral-400">✅ Limpios</p>
                <p class="text-2xl font-bold text-neutral-900">{{ $counters['limpio'] }}</p>
            </div>
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                <p class="text-xs text-neutral-400">⚠️ Sospechosos</p>
                <p class="text-2xl font-bold text-neutral-900">{{ $counters['sospechoso'] }}</p>
            </div>
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                <p class="text-xs text-neutral-400">🚨 Direccionados</p>
                <p class="text-2xl font-bold text-neutral-900">{{ $counters['direccionado'] }}</p>
            </div>
        </div>

        {{-- ═══ CHARTS ═══ --}}
        <div id="tdr-chart-data" data-charts='@json($chartData)' class="hidden"></div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
            {{-- Veredictos (doughnut) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Veredicto de direccionamiento</h4>
                    <span class="text-xs text-neutral-400">Distribución</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-veredictos" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Score por rangos (bar) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Score de riesgo por rango</h4>
                    <span class="text-xs text-neutral-400">0 = limpio, 100 = direccionado</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-scores" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Hallazgos por categoría (bar) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Red flags por categoría</h4>
                    <span class="text-xs text-neutral-400">Hallazgos críticos detectados</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-categorias" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Gravedad de hallazgos (doughnut) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Gravedad de hallazgos</h4>
                    <span class="text-xs text-neutral-400">Nivel de severidad</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-gravedad" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Top Entidades (horizontal bar) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Top entidades analizadas</h4>
                    <span class="text-xs text-neutral-400">Mayor cantidad de análisis</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-entidades" class="w-full h-full"></canvas>
                </div>
            </div>

            {{-- Score promedio por mes (line + bar combo) --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Tendencia de riesgo mensual</h4>
                    <span class="text-xs text-neutral-400">Score promedio y cantidad de análisis</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-tdr-tendencia" class="w-full h-full"></canvas>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- Sin datos --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-12 text-center">
        <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-neutral-900 mb-2">Sin análisis de direccionamiento</h3>
        <p class="text-sm text-neutral-500 max-w-md mx-auto">
            No se encontraron análisis de direccionamiento para los filtros seleccionados. Ajusta los filtros o espera a que se procesen más análisis.
        </p>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('livewire:navigated', () => initTdrCharts());
document.addEventListener('DOMContentLoaded', () => initTdrCharts());

// Reconstruir charts cuando Livewire actualiza el DOM
if (typeof Livewire !== 'undefined') {
    Livewire.hook('morph.updated', ({ el }) => {
        if (el.id === 'tdr-chart-data') {
            setTimeout(() => initTdrCharts(), 50);
        }
    });
}

const tdrChartStore = {};

function tdrMakeChart(id, config) {
    const ctx = document.getElementById(id);
    if (!ctx) return false;

    if (tdrChartStore[id]) {
        try {
            const chart = tdrChartStore[id];
            chart.data.labels = config.data.labels;
            chart.data.datasets = config.data.datasets;
            chart.update('none');
            return true;
        } catch (e) {
            try { tdrChartStore[id].destroy(); } catch (_) {}
            delete tdrChartStore[id];
        }
    }

    try {
        tdrChartStore[id] = new Chart(ctx, config);
        return true;
    } catch (e) {
        console.error(`[tdr-chart] error creating #${id}`, e);
        return false;
    }
}

function decodeHtml(str) {
    if (!str) return str;
    return str.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&apos;/g, "'")
              .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
}

function initTdrCharts() {
    const dataEl = document.getElementById('tdr-chart-data');
    if (!dataEl) return;

    let charts = {};
    try {
        charts = JSON.parse(decodeHtml(dataEl.dataset.charts || '{}'));
    } catch (e) {
        console.error('[tdr-charts] parse error', e);
        return;
    }

    const tdrPalette = {
        limpio: '#00D47E',
        sospechoso: '#F59E0B',
        direccionado: '#EF4444',
        alto: '#EF4444',
        medio: '#F59E0B',
        bajo: '#00D47E',
    };

    const chartPalette = {
        primary: '#025964',
        primaryLight: 'rgba(2, 89, 100, 0.15)',
        secondary: '#00D47E',
    };

    let count = 0;

    // Veredictos (doughnut)
    if (charts.tdr_veredictos && tdrMakeChart('chart-tdr-veredictos', {
        type: 'doughnut',
        data: {
            labels: charts.tdr_veredictos.labels || [],
            datasets: [{
                data: charts.tdr_veredictos.values || [],
                backgroundColor: (charts.tdr_veredictos.labels || []).map(l => {
                    if (l === 'LIMPIO') return tdrPalette.limpio;
                    if (l === 'SOSPECHOSO') return tdrPalette.sospechoso;
                    return tdrPalette.direccionado;
                }),
                borderWidth: 0,
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '75%',
            plugins: { legend: { position: 'bottom', labels: { color: '#111827', font: { size: 12 } } } },
        },
    })) count++;

    // Score por rangos (bar)
    if (charts.tdr_score_ranges && tdrMakeChart('chart-tdr-scores', {
        type: 'bar',
        data: {
            labels: charts.tdr_score_ranges.labels || [],
            datasets: [{
                label: 'Análisis',
                data: charts.tdr_score_ranges.values || [],
                backgroundColor: ['#00D47E', '#29DA93', '#F59E0B', '#EF8C44', '#EF4444'],
                borderRadius: 8,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#4B5563' }, grid: { display: false } },
                y: { ticks: { color: '#4B5563', stepSize: 1 }, grid: { color: '#E5E7EB' }, beginAtZero: true },
            },
            plugins: { legend: { display: false } },
        },
    })) count++;

    // Hallazgos por categoría (bar horizontal)
    if (charts.tdr_hallazgos_categoria && tdrMakeChart('chart-tdr-categorias', {
        type: 'bar',
        data: {
            labels: charts.tdr_hallazgos_categoria.labels || [],
            datasets: [{
                label: 'Red flags',
                data: charts.tdr_hallazgos_categoria.values || [],
                backgroundColor: chartPalette.primary,
                borderRadius: 8,
            }],
        },
        options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { ticks: { color: '#4B5563', stepSize: 1 }, grid: { color: '#E5E7EB' }, beginAtZero: true },
                y: { ticks: { color: '#4B5563' }, grid: { display: false } },
            },
            plugins: { legend: { display: false } },
        },
    })) count++;

    // Gravedad (doughnut)
    if (charts.tdr_gravedad && tdrMakeChart('chart-tdr-gravedad', {
        type: 'doughnut',
        data: {
            labels: charts.tdr_gravedad.labels || [],
            datasets: [{
                data: charts.tdr_gravedad.values || [],
                backgroundColor: (charts.tdr_gravedad.labels || []).map(l => {
                    if (l === 'Alto') return tdrPalette.alto;
                    if (l === 'Medio') return tdrPalette.medio;
                    return tdrPalette.bajo;
                }),
                borderWidth: 0,
            }],
        },
        options: {
            maintainAspectRatio: false,
            cutout: '62%',
            radius: '75%',
            plugins: { legend: { position: 'bottom', labels: { color: '#111827', font: { size: 12 } } } },
        },
    })) count++;

    // Top entidades (bar horizontal)
    if (charts.tdr_top_entidades && tdrMakeChart('chart-tdr-entidades', {
        type: 'bar',
        data: {
            labels: charts.tdr_top_entidades.labels || [],
            datasets: [{
                label: 'Análisis',
                data: charts.tdr_top_entidades.values || [],
                backgroundColor: chartPalette.primaryLight,
                borderRadius: 8,
            }],
        },
        options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: { ticks: { color: '#4B5563', stepSize: 1 }, grid: { color: '#E5E7EB' }, beginAtZero: true },
                y: { ticks: { color: '#4B5563', font: { size: 10 } }, grid: { display: false } },
            },
            plugins: { legend: { display: false } },
        },
    })) count++;

    // Tendencia mensual (line + bar combo)
    if (charts.tdr_score_mes && tdrMakeChart('chart-tdr-tendencia', {
        type: 'bar',
        data: {
            labels: charts.tdr_score_mes.labels || [],
            datasets: [
                {
                    type: 'line',
                    label: 'Score promedio',
                    data: charts.tdr_score_mes.scores || [],
                    borderColor: tdrPalette.direccionado,
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: tdrPalette.direccionado,
                    pointBorderWidth: 2,
                    yAxisID: 'y',
                    order: 1,
                },
                {
                    type: 'bar',
                    label: 'Análisis realizados',
                    data: charts.tdr_score_mes.counts || [],
                    backgroundColor: chartPalette.primaryLight,
                    borderRadius: 8,
                    yAxisID: 'y1',
                    order: 2,
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { color: '#4B5563' }, grid: { display: false } },
                y: {
                    position: 'left',
                    min: 0, max: 100,
                    ticks: { color: '#EF4444', callback: v => v + '%' },
                    grid: { color: '#E5E7EB' },
                    title: { display: true, text: 'Score riesgo', color: '#EF4444' },
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    ticks: { color: '#025964', stepSize: 1 },
                    grid: { display: false },
                    title: { display: true, text: 'Cantidad', color: '#025964' },
                },
            },
            plugins: {
                legend: { position: 'bottom', labels: { color: '#111827', font: { size: 12 } } },
            },
        },
    })) count++;

    console.info(`[tdr-charts] created ${count}/6 charts`);
}
</script>
@endpush
