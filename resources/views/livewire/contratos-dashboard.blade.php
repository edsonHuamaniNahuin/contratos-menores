<div class="p-4 lg:p-6 flex flex-col gap-6 w-full">
    {{-- Ingesta dirigida por departamento y fecha --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-5 lg:p-6 space-y-4">
         @can('import-tdr')
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <div>
                <p class="text-sm font-medium text-neutral-400">Ingesta focalizada</p>
                <h3 class="text-xl font-bold text-neutral-900 mt-1">Cargar contratos por departamento y fecha</h3>
                <p class="text-sm text-neutral-600 mt-1">Consulta el buscador público con page_size=150, filtra por fecha de publicación y almacena/actualiza solo esos contratos.</p>
            </div>


            <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-neutral-600">Departamento</label>
                    <select wire:model="departamentoSeleccionado" class="border border-neutral-200 rounded-full px-4 py-2 text-sm text-neutral-900 bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-400">
                        <option value="">Selecciona</option>
                        <option value="all">Todos (loop)</option>
                        @foreach($departamentos as $dep)
                            <option value="{{ $dep['id'] }}">{{ $dep['nom'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-neutral-600">Fecha de publicación</label>
                    <input type="date" wire:model="fechaFiltro" class="border border-neutral-200 rounded-full px-4 py-2 text-sm text-neutral-900 bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-400" />
                </div>


                    <button wire:click="importarPorDepartamento" class="bg-primary-500 text-white text-sm font-semibold px-5 py-2 rounded-full shadow-soft hover:bg-primary-400 transition-colors">Importar</button>

            </div>

        </div>
        @endcan
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-neutral-600">Filtrar gráficos por departamento</label>
                <select wire:model.live="filtroDepartamento" class="border border-neutral-200 rounded-full px-4 py-2 text-sm text-neutral-900 bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <option value="">Todos</option>
                    @foreach($departamentos as $dep)
                        <option value="{{ $dep['id'] }}">{{ $dep['nom'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-600">
                <span class="text-xs font-semibold text-neutral-600">Rango de fechas (publicación)</span>
                <input type="date" wire:model.live="chartFechaDesde" class="border border-neutral-200 rounded-full px-3 py-2 text-sm text-neutral-900 bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-400" />
                <span class="text-neutral-400 text-xs">→</span>
                <input type="date" wire:model.live="chartFechaHasta" class="border border-neutral-200 rounded-full px-3 py-2 text-sm text-neutral-900 bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-400" />
                <span class="text-xs text-neutral-500">Aplica a todos los gráficos y mapa</span>
            </div>

            @if($mensajeCarga)
                <div class="text-sm text-primary-500 font-semibold">{{ $mensajeCarga }}</div>
            @endif
        </div>

        @if(!empty($resumenCarga))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                    <p class="text-xs text-neutral-400">Recibidos (API)</p>
                    <p class="text-lg font-bold text-neutral-900">{{ $resumenCarga['total_recibidos'] ?? 0 }}</p>
                </div>
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                    <p class="text-xs text-neutral-400">Filtrados por fecha</p>
                    <p class="text-lg font-bold text-neutral-900">{{ $resumenCarga['filtrados_fecha'] ?? 0 }}</p>
                </div>
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                    <p class="text-xs text-neutral-400">Nuevos</p>
                    <p class="text-lg font-bold text-neutral-900">{{ $resumenCarga['nuevos'] ?? 0 }}</p>
                </div>
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-4">
                    <p class="text-xs text-neutral-400">Actualizados</p>
                    <p class="text-lg font-bold text-neutral-900">{{ $resumenCarga['actualizados'] ?? 0 }}</p>
                </div>
            </div>
        @endif
    </div>
    {{-- Estadísticas en Cards (Diseño Sequence) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
        {{-- Total Contratos --}}
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-400">Total Contratos</p>
                    <p class="text-3xl font-bold text-neutral-900 mt-2">{{ number_format($estadisticas['total']) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-800 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Vigentes --}}
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-400">Vigentes</p>
                    <p class="text-3xl font-bold text-neutral-900 mt-2">{{ number_format($estadisticas['vigentes']) }}</p>
                </div>
                <div class="w-12 h-12 bg-secondary-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- En Evaluación --}}
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-400">En Evaluación</p>
                    <p class="text-3xl font-bold text-neutral-900 mt-2">{{ number_format($estadisticas['en_evaluacion']) }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Próximos a Vencer --}}
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-neutral-400">Por Vencer (3 días)</p>
                    <p class="text-3xl font-bold text-neutral-900 mt-2">{{ number_format($estadisticas['por_vencer']) }}</p>
                </div>
                <div class="w-12 h-12 bg-neutral-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Bloque de gráficos --}}
    <div
        class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-6 space-y-6"
        x-data="{ chartsUpdating: false }"
        wire:updated="window.dispatchEvent(new CustomEvent('charts-refresh'))"
    >
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-neutral-400">Inteligencia de mercado</p>
                <h3 class="text-2xl font-bold text-neutral-900 mt-1">Contratos en SEACE</h3>
            </div>
            <span class="text-xs font-medium text-neutral-600 bg-neutral-50 border border-neutral-100 px-3 py-1 rounded-full">Actualizado al refrescar</span>
        </div>

        <div
            id="chart-data"
            data-charts='@json($chartData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)'
            data-selected-dept="{{ e($chartData['departamento_nombre'] ?? '') }}"
            data-signature="{{ md5(json_encode($chartData)) }}"
            class="hidden"
        ></div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Por estado</h4>
                    <span class="text-xs text-neutral-400">Distribución</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-estado" class="w-full h-full"></canvas>
                </div>
            </div>

            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Publicaciones por mes</h4>
                    <span class="text-xs text-neutral-400">Últimos 6 meses</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-mes" class="w-full h-full"></canvas>
                </div>
            </div>

            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Por objeto de contratación</h4>
                    <span class="text-xs text-neutral-400">Volumen</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-objeto" class="w-full h-full"></canvas>
                </div>
            </div>

            <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-neutral-900">Entidades con más convocatorias</h4>
                    <span class="text-xs text-neutral-400">Top 8</span>
                </div>
                <div class="h-64" wire:ignore>
                    <canvas id="chart-entidades" class="w-full h-full"></canvas>
                </div>
            </div>

            @if(!$filtroDepartamento)
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft xl:row-span-2">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-neutral-900">Mapa de calor por departamento</h4>
                        <span class="text-xs text-neutral-400">BD</span>
                    </div>
                    <div id="map-heat" class="w-full h-[36rem] rounded-3xl overflow-hidden border border-neutral-100"></div>
                </div>
            @else
                <div class="bg-neutral-50 border border-neutral-200 rounded-3xl p-6 shadow-soft xl:row-span-2 flex flex-col gap-3 justify-center">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-500 font-semibold">i</span>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">Vista departamental activa</p>
                            <p class="text-xs text-neutral-500">El mapa se muestra solo con el filtro "Todos". Gráficos ya reflejan el departamento seleccionado.</p>
                        </div>
                    </div>
                    <div class="text-sm text-neutral-700">
                        Departamento seleccionado: <span class="font-semibold">{{ $chartData['departamento_nombre'] ?? 'N/D' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const chartPalette = {
            primary: '#025964',
            primaryLight: '#2A737D',
            secondary: '#00D47E',
            neutral: '#7BA8AD',
            muted: '#9CA3AF'
        };

        const chartStore = {};
        const geoStore = { data: null, loading: null };
        let heatMap = null;
        let heatLayer = null;
        let selectedLayer = null;
        const hoverState = { active: null, timeoutId: null };
        let lastHoverLayer = null;

        function makeChart(id, config) {
            const ctx = document.getElementById(id);
            if (!ctx) {
                console.warn(`[chart] canvas #${id} not found in DOM`);
                return false;
            }

            // Verificar que el canvas está en el DOM y es visible
            if (!ctx.offsetParent && ctx.offsetWidth === 0) {
                console.warn(`[chart] canvas #${id} not visible or not in DOM`);
                return false;
            }

            // Si ya existe, actualizar datos en lugar de destruir/recrear
            if (chartStore[id]) {
                try {
                    const chart = chartStore[id];
                    chart.data.labels = config.data.labels;
                    chart.data.datasets = config.data.datasets;
                    chart.update('none'); // 'none' evita animación para actualización más rápida
                    console.info(`[chart] updated #${id}`, { labels: config.data.labels?.length || 0, values: config.data.datasets?.[0]?.data?.length || 0 });
                    return true;
                } catch (e) {
                    console.warn(`[chart] error updating #${id}, recreating:`, e);
                    // Si falla la actualización, destruir y recrear
                    try {
                        chartStore[id].destroy();
                    } catch (destroyErr) {
                        console.warn(`[chart] error destroying #${id}:`, destroyErr);
                    }
                    delete chartStore[id];
                }
            }

            // Crear nueva instancia solo si no existe o falló la actualización
            try {
                chartStore[id] = new window.Chart(ctx, config);
                console.info(`[chart] created #${id}`, { labels: config.data.labels?.length || 0, values: config.data.datasets?.[0]?.data?.length || 0 });
                return true;
            } catch (e) {
                console.error(`[chart] error creating #${id}:`, e);
                return false;
            }
        }

        function normalizeDeptName(name) {
            return (name || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toUpperCase();
        }

        function heatColor(value, maxValue) {
            if (!maxValue || maxValue <= 0) {
                return 'rgba(123, 168, 173, 0.35)';
            }

            const intensity = Math.min(value / maxValue, 1);
            const start = [123, 168, 173];
            const end = [2, 89, 100];
            const rgb = start.map((c, i) => Math.round(c + (end[i] - c) * intensity));

            return `rgba(${rgb[0]}, ${rgb[1]}, ${rgb[2]}, 0.85)`;
        }

        function resetHeatMap() {
            if (heatLayer) {
                heatLayer.remove();
                heatLayer = null;
            }

            if (heatMap) {
                heatMap.remove();
                heatMap = null;
            }
        }

        function buildHeatMap(geojson, deptLabels, values, selectedDeptName = null) {
            const mapEl = document.getElementById('map-heat');
            if (!mapEl) return;

            mapEl.innerHTML = '';

            const targetDept = selectedDeptName ? normalizeDeptName(selectedDeptName) : null;

            // Contenedor relativo para posicionar la etiqueta flotante
            mapEl.style.position = 'relative';

            // Etiqueta flotante que muestra nombre y conteo en hover/click
            let hoverLabel = mapEl.querySelector('.heatmap-hover-label');
            if (!hoverLabel) {
                hoverLabel = document.createElement('div');
                hoverLabel.className = 'heatmap-hover-label absolute top-3 left-3 z-[500] bg-neutral-900/90 text-white text-sm font-semibold px-4 py-2 rounded-full shadow-soft pointer-events-none';
                hoverLabel.style.display = 'none';
                mapEl.appendChild(hoverLabel);
            }

            let filterBadge = mapEl.querySelector('.heatmap-filter-badge');
            if (!filterBadge) {
                filterBadge = document.createElement('div');
                filterBadge.className = 'heatmap-filter-badge absolute top-3 right-3 z-[500] bg-neutral-50 text-neutral-700 text-xs font-semibold px-3 py-1 rounded-full border border-neutral-200 shadow-soft';
                filterBadge.style.display = 'none';
                mapEl.appendChild(filterBadge);
            }

            if (selectedDeptName) {
                filterBadge.textContent = `Filtro: ${selectedDeptName}`;
                filterBadge.style.display = 'block';
            } else {
                filterBadge.style.display = 'none';
            }

            const showHoverLabel = (text) => {
                hoverLabel.textContent = text;
                hoverLabel.style.display = 'block';
            };

            const hideHoverLabel = () => {
                hoverLabel.style.display = 'none';
            };

            console.info('[heatmap] build start', {
                labels: deptLabels?.length || 0,
                values: values?.length || 0,
                features: geojson?.features?.length || 0,
            });

            resetHeatMap();

            const deptMap = new Map();
            deptLabels.forEach((label, index) => {
                deptMap.set(normalizeDeptName(label), Number(values[index] || 0));
            });

            const features = (geojson.features || []).filter((feature) => {
                const coords = feature.geometry?.coordinates;
                const hasCoords = Array.isArray(coords) && coords.length > 0;
                if (!hasCoords) return false;

                if (targetDept) {
                    return normalizeDeptName(feature.properties?.NOMBDEP) === targetDept;
                }

                return true;
            });

            if (!features.length) {
                resetHeatMap();
                mapEl.innerHTML = '<div class="w-full h-full flex items-center justify-center text-sm text-neutral-500">Sin geometría para el filtro seleccionado</div>';
                return;
            }

            console.info('[heatmap] features filtered', features.length);

            const dataValues = features.map((feature) => {
                const name = normalizeDeptName(feature.properties?.NOMBDEP);
                return deptMap.get(name) || 0;
            });

            const maxValue = Math.max(1, ...dataValues);
            const totalValue = dataValues.reduce((acc, value) => acc + value, 0);

            heatMap = L.map('map-heat', {
                zoomControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                touchZoom: false,
                tap: false,
                attributionControl: false,
                preferCanvas: false,
            });

            heatMap.dragging.disable();
            heatMap.scrollWheelZoom.disable();
            heatMap.doubleClickZoom.disable();
            heatMap.boxZoom.disable();
            heatMap.keyboard.disable();
            if (heatMap.touchZoom) {
                heatMap.touchZoom.disable();
            }
            if (heatMap.tap) {
                heatMap.tap.disable();
            }

            const svgRenderer = L.svg({ interactive: true, padding: 0.5 });
            svgRenderer.addTo(heatMap);
            const overlayPane = heatMap.getPanes()?.overlayPane;
            if (overlayPane) {
                overlayPane.style.pointerEvents = 'auto';
                overlayPane.style.zIndex = '450';
            }
            if (svgRenderer._container) {
                svgRenderer._container.style.pointerEvents = 'auto';
            }

            heatLayer = L.geoJSON(features, {
                interactive: true,
                bubblingMouseEvents: true,
                renderer: svgRenderer,
                style: (feature) => {
                    const name = normalizeDeptName(feature.properties?.NOMBDEP);
                    const value = deptMap.get(name) || 0;
                    return {
                        fillColor: heatColor(value, maxValue),
                        weight: 0.6,
                        opacity: 0.8,
                        color: '#FFFFFF',
                        fillOpacity: 0.85,
                    };
                },
                onEachFeature: (feature, layer) => {
                    const deptName = feature.properties?.NOMBDEP || 'Departamento';
                    const provName = feature.properties?.NOMBPROV || null;
                    const displayName = provName ? `${provName} (${deptName})` : deptName;
                    const value = deptMap.get(normalizeDeptName(deptName)) || 0;
                    const percent = totalValue > 0 ? ((value / totalValue) * 100).toFixed(1) : '0.0';
                    const tooltip = `${displayName}\nContratos: ${value}\nParticipacion: ${percent}%`;
                    layer.bindTooltip(tooltip, { sticky: true, direction: 'top', opacity: 0.95 });

                    layer.on('mouseover', () => {
                        if (hoverState.timeoutId) {
                            clearTimeout(hoverState.timeoutId);
                            hoverState.timeoutId = null;
                        }
                        if (lastHoverLayer && lastHoverLayer !== layer && lastHoverLayer !== selectedLayer) {
                            heatLayer.resetStyle(lastHoverLayer);
                            if (lastHoverLayer._path) {
                                lastHoverLayer._path.style.transform = 'scale(1)';
                            }
                        }
                        if (hoverState.active === displayName) {
                            lastHoverLayer = layer;
                            return;
                        }
                        hoverState.active = displayName;
                        lastHoverLayer = layer;
                        layer.setStyle({ weight: 1.6, fillOpacity: 0.97 });
                        if (layer._path) {
                            layer._path.style.transform = 'scale(1.01)';
                            layer._path.style.transformOrigin = 'center center';
                            layer._path.style.transition = 'transform 120ms ease-out, fill-opacity 120ms ease-out, stroke-width 120ms ease-out';
                        }
                        layer.openTooltip();
                        showHoverLabel(`${displayName} • ${value} contratos (${percent}%)`);
                    });
                    layer.on('mouseout', () => {
                        if (hoverState.timeoutId) {
                            clearTimeout(hoverState.timeoutId);
                        }
                        hoverState.timeoutId = setTimeout(() => {
                            if (hoverState.active !== displayName) return;
                            if (selectedLayer !== layer) {
                                heatLayer.resetStyle(layer);
                                if (layer._path) {
                                    layer._path.style.transform = 'scale(1)';
                                }
                            }
                            if (selectedLayer && selectedLayer !== layer && selectedLayer._path) {
                                selectedLayer._path.style.transform = 'scale(1)';
                            }
                            layer.closeTooltip();
                            hideHoverLabel();
                            hoverState.active = null;
                            hoverState.timeoutId = null;
                            if (lastHoverLayer === layer) {
                                lastHoverLayer = null;
                            }
                        }, 80);
                    });
                    layer.on('click', () => {
                        console.debug('[heatmap] click', displayName, { value, percent });
                        if (selectedLayer && selectedLayer._path) {
                            selectedLayer._path.style.transform = 'scale(1)';
                            heatLayer.resetStyle(selectedLayer);
                        }
                        selectedLayer = layer;
                        layer.setStyle({ weight: 2, fillOpacity: 1 });
                        if (layer._path) {
                            layer._path.style.transform = 'scale(1.02)';
                            layer._path.style.transformOrigin = 'center center';
                        }
                        layer.openTooltip();
                        hoverState.active = displayName;
                        if (hoverState.timeoutId) {
                            clearTimeout(hoverState.timeoutId);
                            hoverState.timeoutId = null;
                        }
                        showHoverLabel(`${displayName} • ${value} contratos (${percent}%)`);
                        mapEl.dispatchEvent(new CustomEvent('dept:click', {
                            detail: { name: displayName, value, percent: Number(percent) },
                        }));
                        layer.bringToFront?.();
                    });
                },
            }).addTo(heatMap);

            const layerCount = heatLayer.getLayers().length;
            const rendererType = heatLayer.options?.renderer instanceof L.SVG ? 'svg' : 'unknown';
            console.info('[heatmap] layers added', { layerCount, rendererType });
            const enablePathInteractivity = (label) => {
                const svgEl = mapEl.querySelector('svg');
                if (svgEl) {
                    svgEl.setAttribute('pointer-events', 'auto');
                }
                mapEl.querySelectorAll('path.leaflet-interactive').forEach((pathEl) => {
                    pathEl.style.pointerEvents = 'auto';
                    pathEl.style.cursor = 'pointer';
                });
                mapEl.style.pointerEvents = 'auto';
                if (heatLayer && typeof heatLayer.eachLayer === 'function') {
                    heatLayer.eachLayer((layer) => {
                        if (layer._path) {
                            layer._path.setAttribute('pointer-events', 'auto');
                            layer._path.style.cursor = 'pointer';
                        }
                    });
                }
                const livePaths = mapEl.querySelectorAll('path.leaflet-interactive').length;
                console.info('[heatmap] interactivity applied', { label, livePaths });
            };

            enablePathInteractivity('immediate');

            const pathCount = mapEl.querySelectorAll('path.leaflet-interactive').length;
            console.info('[heatmap] paths ready', { pathCount });

            const pathBBox = mapEl.querySelector('path.leaflet-interactive')?.getBBox?.();
            if (pathBBox) {
                console.info('[heatmap] sample bbox', {
                    x: Math.round(pathBBox.x),
                    y: Math.round(pathBBox.y),
                    width: Math.round(pathBBox.width),
                    height: Math.round(pathBBox.height),
                });
            }

            // Recalcular tamaño del mapa y forzar re-render de SVG
            setTimeout(() => {
                heatMap.invalidateSize();
                enablePathInteractivity('timeout-50ms');
                const asyncPathCount = mapEl.querySelectorAll('path.leaflet-interactive').length;
                console.info('[heatmap] paths ready (async)', { asyncPathCount });
            }, 50);

            requestAnimationFrame(() => {
                enablePathInteractivity('raf');
                const rafPathCount = mapEl.querySelectorAll('path.leaflet-interactive').length;
                console.info('[heatmap] paths ready (raf)', { rafPathCount });
            });

            heatMap.on('click', (e) => {
                console.debug('[heatmap] map click', e.latlng);
            });
            mapEl.addEventListener('click', (e) => {
                console.debug('[heatmap] container click', { x: e.clientX, y: e.clientY });
            });
            mapEl.addEventListener('mousemove', () => {
                // lightweight heartbeat to confirm pointer events
            }, { once: false });

            mapEl.addEventListener('dept:click', (e) => {
                console.info('[heatmap] dept:click event', e.detail);
            });

            try {
                heatMap.fitBounds(heatLayer.getBounds(), { padding: [12, 12] });
            } catch (e) {
                console.warn('No se pudieron ajustar los limites del mapa', e);
            }
        }

        function loadGeoData() {
            if (geoStore.data) {
                return Promise.resolve(geoStore.data);
            }

            if (!geoStore.loading) {
                geoStore.loading = fetch('/geo/limites_provincial_peru_min.json')
                    .then((response) => response.json())
                    .then((data) => {
                        geoStore.data = data;
                        return data;
                    })
                    .catch((error) => {
                        console.error('Error loading geojson', error);
                        return null;
                    });
            }

            return geoStore.loading;
        }

        function decodeHtmlEntities(str) {
            if (!str) return str;
            return str
                .replace(/&quot;/g, '"')
                .replace(/&#039;/g, "'")
                .replace(/&apos;/g, "'")
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&amp;/g, '&');
        }

        function buildCharts() {
            console.info('[charts] buildCharts() called');
            const dataEl = document.getElementById('chart-data');
            if (!dataEl) {
                console.warn('[charts] #chart-data element not found');
                return;
            }

            let charts = {};

            try {
                const raw = dataEl.dataset.charts || dataEl.getAttribute('data-charts') || '{}';
                const decoded = decodeHtmlEntities(raw);
                charts = JSON.parse(decoded || '{}');
                console.debug('[charts] parsed data', {
                    hasEstado: !!charts.por_estado,
                    hasMes: !!charts.por_mes,
                    hasObjeto: !!charts.por_objeto,
                    hasEntidades: !!charts.top_entidades
                });
            } catch (e) {
                console.error('[charts] error parsing chart data', e);
                console.debug('[charts] raw data', dataEl.dataset.charts?.substring(0, 200));
                return;
            }

            // Verificar que tenemos datos válidos
            if (!charts.por_estado && !charts.por_mes && !charts.por_objeto && !charts.top_entidades) {
                console.warn('[charts] no chart data available');
                return;
            }

            // NO limpiar instancias previas - makeChart() se encarga de actualizar
            let successCount = 0;

            // Por estado (doughnut)
            if (makeChart('chart-estado', {
                type: 'doughnut',
                data: {
                    labels: charts.por_estado?.labels || [],
                    datasets: [{
                        data: charts.por_estado?.values || [],
                        backgroundColor: [chartPalette.primary, chartPalette.secondary, chartPalette.primaryLight, chartPalette.neutral],
                        borderWidth: 0,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    radius: '75%',
                    layout: { padding: { top: 8, bottom: 8 } },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#111827', font: { size: 12 } } },
                    },
                },
            })) {
                successCount++;
            }

            // Publicaciones por mes (line)
            if (makeChart('chart-mes', {
                type: 'line',
                data: {
                    labels: charts.por_mes?.labels || [],
                    datasets: [{
                        label: 'Contratos',
                        data: charts.por_mes?.values || [],
                        borderColor: chartPalette.primary,
                        backgroundColor: chartPalette.primaryLight,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#FFFFFF',
                        pointBorderColor: chartPalette.primary,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: '#4B5563' }, grid: { display: false } },
                        y: { ticks: { color: '#4B5563' }, grid: { color: '#E5E7EB' }, beginAtZero: true },
                    },
                    plugins: { legend: { display: false } },
                },
            })) {
                successCount++;
            }

            // Por objeto (bar)
            if (makeChart('chart-objeto', {
                type: 'bar',
                data: {
                    labels: charts.por_objeto?.labels || [],
                    datasets: [{
                        label: 'Contratos',
                        data: charts.por_objeto?.values || [],
                        backgroundColor: chartPalette.secondary,
                        borderRadius: 8,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: '#4B5563' }, grid: { display: false } },
                        y: { ticks: { color: '#4B5563' }, grid: { color: '#E5E7EB' }, beginAtZero: true },
                    },
                    plugins: { legend: { display: false } },
                },
            })) {
                successCount++;
            }

            // Top entidades (horizontal bar)
            if (makeChart('chart-entidades', {
                type: 'bar',
                data: {
                    labels: charts.top_entidades?.labels || [],
                    datasets: [{
                        label: 'Convocatorias',
                        data: charts.top_entidades?.values || [],
                        backgroundColor: chartPalette.primaryLight,
                        borderRadius: 8,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: { ticks: { color: '#4B5563' }, grid: { color: '#E5E7EB' }, beginAtZero: true },
                        y: { ticks: { color: '#4B5563' }, grid: { display: false } },
                    },
                    plugins: { legend: { display: false } },
                },
            })) {
                successCount++;
            }

            console.info(`[charts] created ${successCount}/4 charts successfully`);

            // Solo intentar mapa si el contenedor existe (filtro "Todos")
            if (document.getElementById('map-heat')) {
                loadGeoData().then((geojson) => {
                    if (!geojson || !geojson.features) {
                        return;
                    }

                    const deptLabels = charts.por_departamento?.labels || [];
                    const values = charts.por_departamento?.values || [];
                    buildHeatMap(geojson, deptLabels, values, charts.departamento_nombre || null);
                });
            }
        }

        // Build inicial cuando la página carga
        document.addEventListener('livewire:init', () => {
            console.info('[charts] livewire:init - initial build');
            setTimeout(buildCharts, 100);
        });

        // Escuchar actualizaciones de Livewire (cuando cambian filtros)
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', ({ el, component }) => {
                console.info('[charts] livewire morph.updated - refreshing charts');
                setTimeout(buildCharts, 80);
            });
        });

        // Listener adicional para actualizaciones manuales
        window.addEventListener('charts-refresh', () => {
            console.info('[charts] charts-refresh event - rebuilding charts');
            setTimeout(buildCharts, 100);
        });
    </script>
@endpush
