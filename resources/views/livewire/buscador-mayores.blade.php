<div
    class="p-4 lg:p-6 flex flex-col gap-6 max-w-full w-full min-w-0 overflow-visible"
    x-data="{
        tooltip: { show: false, text: '', x: 0, y: 0 },
        showTooltip(text, event) { this.tooltip.show = true; this.tooltip.text = text ?? ''; this.tooltip.x = event?.clientX ?? 0; this.tooltip.y = event?.clientY ?? 0; },
        moveTooltip(event) { if (!this.tooltip.show) return; this.tooltip.x = event?.clientX ?? this.tooltip.x; this.tooltip.y = event?.clientY ?? this.tooltip.y; },
        hideTooltip() { this.tooltip.show = false; }
    }"
>
    <div
        x-show="tooltip.show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        :style="'position: fixed; left: ' + tooltip.x + 'px; top: ' + tooltip.y + 'px; transform: translate(-50%, -100%); margin-top: -8px; z-index: 9999;'"
        class="pointer-events-none"
        style="display: none;"
    >
        <div class="bg-neutral-900 text-white text-xs font-medium px-3 py-2 rounded-lg shadow-lg max-w-sm break-words" x-text="tooltip.text"></div>
    </div>

    <div>
        <h1 class="text-xl lg:text-2xl font-bold text-neutral-900" data-ga-event="page_view_mayores" data-ga-category="mayores">Contratos Mayores</h1>
    </div>

    {{-- Notificación toast --}}
    @if($notificacion)
        @php
            $bgMap = ['success' => 'bg-green-50 border-green-300 text-green-800', 'error' => 'bg-red-50 border-red-300 text-red-800', 'warning' => 'bg-amber-50 border-amber-300 text-amber-800', 'info' => 'bg-blue-50 border-blue-300 text-blue-800'];
            $bg = $bgMap[$notificacion['type'] ?? 'info'] ?? $bgMap['info'];
        @endphp
        <div class="{{ $bg }} border rounded-2xl p-4 text-sm font-medium" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false, 6000)">
            {{ $notificacion['message'] }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 lg:p-6 border border-neutral-200">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-lg lg:text-xl font-bold text-neutral-900">Filtros de Búsqueda</h2>
                @if($this->contarFiltrosActivos() > 0)
                    <span class="px-2.5 py-1 bg-secondary-500 text-white text-xs font-semibold rounded-full">{{ $this->contarFiltrosActivos() }}</span>
                @endif
            </div>
            <button wire:click="limpiarFiltros" class="text-xs lg:text-sm text-neutral-600 hover:text-primary-500 font-medium transition-colors">Limpiar todo</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-4">
            <div class="lg:col-span-4">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($palabraClave) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Palabra Clave</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.400ms="palabraClave" placeholder="Buscar contratos mayores..." class="w-full px-4 py-2.5 pl-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($palabraClave) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($palabraClave) ? 'text-brand-600' : 'text-neutral-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <div wire:loading wire:target="palabraClave" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            <span>Buscando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-3" x-data="{ abierto: false }" @click.away="abierto = false">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($entidadFiltro) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Entidad</label>
                <div class="relative">
                    <input type="text"
                        wire:model.live.debounce.400ms="entidadTexto"
                        @input="abierto = true"
                        @focus="abierto = true"
                        wire:keydown.escape="cerrarSugerenciasEntidades"
                        placeholder="Buscar entidad..."
                        autocomplete="off"
                        class="w-full px-4 py-2.5 pl-10 pr-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($entidadFiltro) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                    {{-- Icono edificio --}}
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($entidadFiltro) ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>

                    {{-- Spinner de búsqueda interno --}}
                    <div wire:loading wire:target="entidadTexto,buscarEntidades" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                        </svg>
                    </div>

                    {{-- Botón X para limpiar --}}
                    @if(!empty($entidadFiltro))
                        <button
                            wire:click="limpiarEntidad"
                            wire:loading.remove
                            wire:target="entidadTexto,limpiarEntidad"
                            @click="abierto = false"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 hover:text-red-700 transition-colors bg-red-50 hover:bg-red-100 rounded-full p-1"
                            title="Limpiar entidad"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif

                    {{-- Dropdown de sugerencias --}}
                    <div
                        x-show="abierto && $wire.entidadesSugeridas.length > 0"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute z-50 w-full mt-1 bg-white border border-neutral-200 rounded-xl shadow-lg max-h-60 overflow-y-auto"
                        style="display: none;"
                    >
                        @foreach($entidadesSugeridas as $entidad)
                            <button
                                type="button"
                                @click="abierto = false; $wire.seleccionarEntidad('{{ e($entidad['nombre']) }}', '{{ e($entidad['ruc'] ?? '') }}')"
                                class="w-full px-4 py-2.5 text-left hover:bg-primary-50 transition-colors border-b border-neutral-100 last:border-b-0"
                            >
                                <div class="text-sm font-medium text-neutral-900">{{ $entidad['nombre'] }}</div>
                                @if(!empty($entidad['ruc']))
                                    <div class="text-xs text-neutral-500">RUC: {{ $entidad['ruc'] }}</div>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Indicador de carga entidad --}}
                    <div wire:loading wire:target="entidadTexto,seleccionarEntidad,limpiarEntidad" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            <span>Buscando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($objetoContratacion) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Objeto</label>
                <div class="relative">
                    <select wire:model.live="objetoContratacion" class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($objetoContratacion) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}">
                        <option value="">Todos</option>
                        <option value="goods">Bien</option>
                        <option value="services">Servicio</option>
                        <option value="works">Obra</option>
                    </select>
                    <div wire:loading wire:target="objetoContratacion" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            <span>Buscando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($estado) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Estado</label>
                <div class="relative">
                    <select wire:model.live="estado" class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($estado) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}">
                        <option value="">Todos</option>
                        @foreach($estadosDisponibles as $edo)
                            <option value="{{ $edo }}">{{ ucfirst(strtolower($edo)) }}</option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="estado" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            <span>Buscando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <span class="text-xs text-neutral-400">Los resultados se actualizan automáticamente al escribir</span>
        </div>
    </div>

    @if($mensajeError)
        <div class="bg-red-50 border-2 border-red-300 rounded-3xl p-5 shadow-soft">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <h3 class="text-sm font-bold text-red-900">{{ $mensajeError }}</h3>
            </div>
        </div>
    @endif

    @if($buscando)
        <div class="flex items-center justify-center py-16">
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin w-10 h-10 text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <p class="text-sm text-neutral-500 font-medium">Consultando API OCDS...</p>
            </div>
        </div>
    @endif

    @php
        $logueado = auth()->check();
        $puedeSeguimiento = $logueado && (auth()->user()?->hasPermission('follow-contracts') ?? false);
        $puedeAnalizar = $logueado && (auth()->user()?->hasPermission('analyze-tdr') ?? false);
        $puedeCotizar = $logueado && (auth()->user()?->hasPermission('cotizar-seace') ?? false);
    @endphp

    @if(!$buscando && !empty($resultados))
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 w-full min-w-0" style="overflow: visible;">
            <div class="px-4 lg:px-6 py-4 border-b border-neutral-100 flex items-center justify-end gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-neutral-600 hidden sm:inline">Mostrar:</label>
                    <select wire:model.live="registrosPorPagina" class="px-2.5 py-1.5 bg-neutral-50 border border-neutral-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                    </select>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="hidden lg:block" style="overflow: visible;">
                <table class="w-full">
                    <thead class="bg-neutral-100 border-b border-neutral-200">
                        <tr>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Entidad</th>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Nomenclatura</th>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Objeto</th>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 lg:px-6 py-3.5 text-right text-xs font-semibold text-neutral-800 uppercase tracking-wider">Monto</th>
                            <th class="px-4 lg:px-6 py-3.5 text-center text-xs font-semibold text-neutral-800 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 lg:px-6 py-3.5 text-center text-xs font-semibold text-neutral-800 uppercase tracking-wider">Vigencia</th>
                            <th class="px-4 lg:px-6 py-3.5 text-center text-xs font-semibold text-neutral-800 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach($resultados as $i => $c)
                            <tr class="hover:bg-neutral-50/50 transition-colors">
                                <td class="px-4 lg:px-6 py-3 text-sm text-neutral-800 max-w-[160px] truncate" @mouseenter="showTooltip('{{ e($c['entidad_nombre'] ?? '') }}{{ !empty($c['entidad_ruc']) ? ' | RUC: '.e($c['entidad_ruc']) : '' }}', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">{{ $c['entidad_nombre'] ?? '' }}</td>
                                 <td class="px-4 lg:px-6 py-3 text-sm font-mono text-neutral-700 whitespace-nowrap max-w-[200px] truncate" @mouseenter="showTooltip('{{ e($c['nomenclatura'] ?? '') }}', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">{{ $c['nomenclatura'] ?? '' }}</td>
                                <td class="px-4 lg:px-6 py-3 text-sm whitespace-nowrap">@if($c['objeto_contratacion'] ?? '')<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700">{{ $c['objeto_contratacion'] }}</span>@endif</td>
                                <td class="px-4 lg:px-6 py-3 text-sm text-neutral-600 max-w-[250px] truncate" @mouseenter="showTooltip('{{ e($c['descripcion_objeto'] ?? '') }}', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">{{ $c['descripcion_objeto'] ?? '' }}</td>
                                <td class="px-4 lg:px-6 py-3 text-sm text-neutral-700 text-right whitespace-nowrap font-medium tabular-nums">{{ $c['monto_formateado'] }}</td>
                                <td class="px-4 lg:px-6 py-3 text-sm text-neutral-500 text-center whitespace-nowrap">{{ $c['fecha_formateada'] }}</td>
                                <td class="px-4 lg:px-6 py-3 text-center whitespace-nowrap">
                                    @if(isset($c['vigente']))
                                        @if($c['vigente'])
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                                Vigente
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-600 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                                {{ $c['estado_vigencia'] }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-xs text-neutral-400">---</span>
                                    @endif
                                </td>
                                 <td class="px-4 lg:px-6 py-3 text-center whitespace-nowrap">
                                    <div class="relative" x-data="{ open: false }">
                                        <button
                                            @click="
                                                open = !open;
                                                if (open) {
                                                    const abajo = {{ $i }} < {{ intval(count($resultados) / 2) }};
                                                    const dd = $el.nextElementSibling;
                                                    dd.classList.toggle('top-full', abajo);
                                                    dd.classList.toggle('mt-2', abajo);
                                                    dd.classList.toggle('bottom-full', !abajo);
                                                    dd.classList.toggle('mb-2', !abajo);
                                                }
                                            "
                                            @click.away="open = false"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 text-neutral-600 hover:text-brand-600 hover:border-primary-400 transition-colors"
                                            title="Acciones"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                        </button>
                                        <div
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 scale-90"
                                            x-transition:enter-end="opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-90"
                                            class="absolute right-0 z-[100] w-48 bg-white rounded-2xl shadow-lg border border-neutral-200 py-1.5"
                                            style="display:none"
                                        >
                                            {{-- Ver --}}
                                            
                                            <button wire:click="verDetalle({{ $i }}); open = false" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-neutral-700 hover:bg-primary-50 hover:text-brand-600 transition-colors"
                                                @mouseenter="showTooltip('Ver todos los datos del proceso: Entidad, RUC, fechas, proveedores y mas', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Ver detalle</span>
                                            </button>
                                            {{-- Descargar --}}
                                            @if(!empty($c['url_documento']))
                                                
                                                <a href="{{ $c['url_documento'] }}" target="_blank" rel="noopener" @click="open = false" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-neutral-700 hover:bg-primary-50 hover:text-brand-600 transition-colors"
                                                    @mouseenter="showTooltip('Descarga el documento TDR original en PDF desde el portal SEACE', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                                                    <span>Descargar TDR</span>
                                                </a>
                                            @endif
                                            {{-- Seguimiento --}}
                                            
                                            <button wire:click="hacerSeguimiento('{{ $c['ocid'] }}'); open = false" wire:loading.attr="disabled" wire:target="hacerSeguimiento('{{ $c['ocid'] }}')"
                                                class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm {{ !empty($seguimientosActivos[$c['ocid']]) ? 'text-primary-600 bg-primary-50' : 'text-neutral-700 hover:bg-primary-50 hover:text-brand-600' }} transition-colors"
                                                @mouseenter="showTooltip('{{ !empty($seguimientosActivos[$c['ocid']]) ? 'Ya estás siguiendo este proceso' : 'Activa notificaciones y seguimiento de este proceso' }}', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ !empty($seguimientosActivos[$c['ocid']]) ? 'Siguiendo' : 'Seguimiento' }}</span>
                                                @if(!empty($seguimientosActivos[$c['ocid']]))
                                                    <svg class="w-3.5 h-3.5 ml-auto text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                @endif
                                            </button>
                                            @if(!empty($c['url_documento']))
                                                <div class="border-t border-neutral-100 my-0.5"></div>
                                                <button wire:click="analizarTdr('{{ $c['url_documento'] }}'); open = false" wire:loading.attr="disabled" wire:target="analizarTdr('{{ $c['url_documento'] }}')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 transition-colors"
                                                    data-ga-event="mayores_analizar_click"
                                                    @mouseenter="showTooltip('Analiza el documento con IA para extraer requisitos, plazos, penalidades y mas', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/></svg>
                                                    <span>Analizar con IA</span>
                                                </button>
                                                <button wire:click="detectarDireccionamiento('{{ $c['url_documento'] }}'); open = false" wire:loading.attr="disabled" wire:target="detectarDireccionamiento('{{ $c['url_documento'] }}')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                    @mouseenter="showTooltip('Audita el documento en busca de indicios de direccionamiento, barreras tecnicas y restricciones a la competencia', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                                    <span>Direccionamiento</span>
                                                </button>
                                                <button wire:click="generarProformaTecnicaMayor('{{ $c['url_documento'] }}'); open = false" wire:loading.attr="disabled" wire:target="generarProformaTecnicaMayor('{{ $c['url_documento'] }}')" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-secondary-600 hover:bg-secondary-50 transition-colors"
                                                    @mouseenter="showTooltip('Genera una proforma tecnica de cotizacion con items, precios y analisis de viabilidad', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    <span>Crear Proforma</span>
                                                </button>
                                            @endif
                                            <button wire:click="verPartesMayor('{{ $c['ocid'] }}'); open = false" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-indigo-600 hover:bg-indigo-50 transition-colors"
                                                @mouseenter="showTooltip('Entidades involucradas: comprador, proveedores y sus datos', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                <span>Ver Partes</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="lg:hidden px-4 py-4 space-y-3">
                @foreach($resultados as $i => $c)
                    <div class="border border-neutral-200 rounded-2xl p-4 space-y-3 {{ $i % 2 === 0 ? 'bg-white' : 'bg-neutral-50/50' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-neutral-900 truncate flex-1">{{ $c['nomenclatura'] ?? 'Sin código' }}</p>
                            @if($c['objeto_contratacion'] ?? '')<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 shrink-0">{{ $c['objeto_contratacion'] }}</span>@endif
                        </div>
                        <p class="text-xs text-neutral-500">{{ $c['entidad_nombre'] ?? '' }}</p>
                        <p class="text-xs text-neutral-600 line-clamp-2">{{ $c['descripcion_objeto'] ?? '' }}</p>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-500">{{ $c['fecha_formateada'] }}</span>
                            <span class="font-semibold text-neutral-700 tabular-nums">{{ $c['monto_formateado'] }}</span>
                        </div>
                        @if(isset($c['vigente']))
                            <div class="flex items-center gap-1.5 text-xs pt-1">
                                @if($c['vigente'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 border border-green-200 font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Vigente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200 font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> {{ $c['estado_vigencia'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-neutral-100">
                            
                            <button wire:click="verDetalle({{ $i }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-primary-500 hover:bg-primary-400 transition-colors" title="Ver detalle">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Ver</span>
                            </button>
                            @if(!empty($c['url_documento']))
                                
                                <a href="{{ $c['url_documento'] }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors" title="Descargar TDR">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>
                                    <span>TDR</span>
                                </a>
                            @endif
                            <div class="relative ml-auto" x-data="{ open: false }">
                                <button @click="
                                    open = !open;
                                    if (open) {
                                        const abajo = {{ $i }} < {{ intval(count($resultados) / 2) }};
                                        const dd = $el.nextElementSibling;
                                        dd.classList.toggle('top-full', abajo);
                                        dd.classList.toggle('mt-2', abajo);
                                        dd.classList.toggle('bottom-full', !abajo);
                                        dd.classList.toggle('mb-2', !abajo);
                                    }
                                " @click.away="open = false"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-neutral-200 text-neutral-600 hover:text-brand-600 transition-colors" title="Mas acciones">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                                </button>
                                <div x-show="open" x-transition
                                    class="absolute right-0 z-[100] w-44 bg-white rounded-2xl shadow-lg border border-neutral-200 py-1.5"
                                    style="display:none">
                                    
                                    <button wire:click="hacerSeguimiento('{{ $c['ocid'] }}'); open = false"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs {{ !empty($seguimientosActivos[$c['ocid']]) ? 'text-primary-600 bg-primary-50' : 'text-neutral-700 hover:bg-primary-50' }} transition-colors"
                                        @mouseenter="showTooltip('{{ !empty($seguimientosActivos[$c['ocid']]) ? 'Ya estás siguiendo este proceso' : 'Activa notificaciones y seguimiento' }}', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ !empty($seguimientosActivos[$c['ocid']]) ? 'Siguiendo' : 'Seguimiento' }}
                                        @if(!empty($seguimientosActivos[$c['ocid']]))
                                            <svg class="w-3 h-3 ml-auto text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @endif
                                    </button>
                                    @if(!empty($c['url_documento']))
                                        <button wire:click="analizarTdr('{{ $c['url_documento'] }}'); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-primary-600 hover:bg-primary-50 transition-colors"
                                            @mouseenter="showTooltip('Analiza con IA: requisitos, plazos, penalidades', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/></svg> Analizar IA
                                        </button>
                                        <button wire:click="detectarDireccionamiento('{{ $c['url_documento'] }}'); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors"
                                            @mouseenter="showTooltip('Audita el documento en busca de direccionamiento', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg> Direccionamiento
                                        </button>
                                        <button wire:click="generarProformaTecnicaMayor('{{ $c['url_documento'] }}'); open = false" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-secondary-600 hover:bg-secondary-50 transition-colors"
                                            @mouseenter="showTooltip('Genera proforma tecnica de cotizacion', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> Proforma
                                        </button>
                                    @endif
                                    <button wire:click="verPartesMayor('{{ $c['ocid'] }}'); open = false"
                                        class="w-full flex items-center gap-2 px-3 py-2 text-xs text-indigo-600 hover:bg-indigo-50 transition-colors"
                                        @mouseenter="showTooltip('Entidades involucradas en este proceso', $event)" @mouseleave="hideTooltip()" @mousemove="moveTooltip($event)">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg> Ver Partes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Paginación --}}
            @if(($paginacion['total_pages'] ?? 1) > 1)
                <div class="px-4 lg:px-6 py-5 bg-gradient-to-br from-neutral-50 to-neutral-100/50 border-t border-neutral-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs font-medium text-neutral-700">
                            Página <span class="font-bold text-neutral-900">{{ $paginacion['current_page'] }}</span> de <span class="font-bold text-neutral-900">{{ $paginacion['total_pages'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($paginacion['has_prev'])
                                <button wire:click="irPagina(1)" class="w-10 h-10 flex items-center justify-center text-sm font-semibold rounded-xl transition-all bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md" title="Primera">&#171;</button>
                                <button wire:click="irPagina({{ $paginacion['current_page'] - 1 }})" class="px-4 py-2.5 text-sm font-semibold rounded-xl transition-all bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md">Anterior</button>
                            @else
                                <span class="w-10 h-10 flex items-center justify-center text-sm font-semibold rounded-xl bg-neutral-200 text-neutral-400 cursor-not-allowed">&#171;</span>
                                <span class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-neutral-200 text-neutral-400 cursor-not-allowed">Anterior</span>
                            @endif

                            @foreach(range(max(1, $paginacion['current_page'] - 2), min($paginacion['total_pages'], $paginacion['current_page'] + 2)) as $pg)
                                @if($pg === $paginacion['current_page'])
                                    <span class="w-10 h-10 text-sm font-bold rounded-xl transition-all bg-primary-500 text-white shadow-lg scale-110 ring-2 ring-primary-300 flex items-center justify-center">{{ $pg }}</span>
                                @else
                                    <button wire:click="irPagina({{ $pg }})" class="w-10 h-10 text-sm font-bold rounded-xl transition-all bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-50 hover:border-primary-500 hover:text-brand-600 shadow-sm flex items-center justify-center">{{ $pg }}</button>
                                @endif
                            @endforeach

                            @if($paginacion['has_next'])
                                <button wire:click="irPagina({{ $paginacion['current_page'] + 1 }})" class="px-4 py-2.5 text-sm font-semibold rounded-xl transition-all bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md">Siguiente</button>
                            @else
                                <span class="px-4 py-2.5 text-sm font-semibold rounded-xl bg-neutral-200 text-neutral-400 cursor-not-allowed">Siguiente</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Overlay de carga síncrono (wire:loading — visible durante el POST) --}}
    <div
        wire:loading.flex
        wire:target="analizarTdr,detectarDireccionamiento,generarProformaTecnicaMayor"
        class="fixed inset-0 z-[200] items-center justify-center px-4"
        style="display: none"
        x-data="{ seconds: 0, timer: null, init() {
            new MutationObserver(() => {
                if (this.$el.style.display !== 'none') { this.seconds = 0; this.timer = setInterval(() => this.seconds++, 1000); }
                else if (this.timer) { clearInterval(this.timer); this.timer = null; }
            }).observe(this.$el, { attributes: true, attributeFilter: ['style'] });
        }}"
    >
        <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-[2rem] shadow-soft p-8 max-w-sm w-full text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-primary-500/10 flex items-center justify-center mb-5">
                <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <p class="text-base font-semibold text-neutral-900" x-text="seconds >= 60 ? 'El analisis esta tomando mas de lo esperado...' : seconds >= 25 ? 'Procesando documento extenso...' : seconds >= 10 ? 'Extrayendo y analizando el contenido...' : 'Preparando analisis con IA...'"></p>
            <p class="text-sm text-neutral-400 mt-2 leading-relaxed" x-text="seconds >= 60 ? 'Por favor no cierre esta pagina.' : seconds >= 25 ? 'Los documentos extensos requieren mas tiempo.' : seconds >= 10 ? 'Estamos leyendo cada seccion del TDR.' : 'Conectando con el servicio de inteligencia artificial...'"></p>
            <p class="text-xs text-neutral-400/70 mt-4">Tiempo: <span x-text="seconds + 's'"></span></p>
            <div class="flex justify-center gap-1.5 mt-4">
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0s"></span>
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.15s"></span>
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.3s"></span>
            </div>
        </div>
    </div>

    {{-- Extrayendo ZIP/RAR --}}
    @if($extrayendoOcid)
        <div class="fixed inset-0 z-[200] flex items-center justify-center px-4"
             x-data="{ seconds: 0, timer: null, init() { this.timer = setInterval(() => this.seconds++, 1000); } }"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-[2rem] shadow-soft p-8 max-w-sm w-full text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-amber-500/10 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <p class="text-base font-semibold text-neutral-900" x-text="seconds >= 40 ? 'Verificando documentos extraidos...' : seconds >= 20 ? 'Archivo comprimido detectado. Extrayendo PDFs...' : seconds >= 8 ? 'Analizando formato del documento...' : 'Descargando documento TDR...'"></p>
                <p class="text-sm text-neutral-400 mt-2 leading-relaxed" x-text="seconds >= 40 ? 'Revisando todos los PDFs encontrados dentro del archivo.' : seconds >= 20 ? 'El TDR esta empaquetado en ZIP/RAR. Descomprimiendo contenido...' : seconds >= 8 ? 'Verificando si el documento es PDF, ZIP o RAR...' : 'Conectando con el servidor para obtener el documento.'"></p>
                <p class="text-xs text-neutral-400/70 mt-4">Tiempo: <span x-text="seconds + 's'"></span></p>
                <div class="flex justify-center gap-1.5 mt-4">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-bounce" style="animation-delay: 0s"></span>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-bounce" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>
    @endif

    {{-- Analisis en progreso --}}
    @if($analizandoOcid)
        <div class="fixed inset-0 z-[200] flex items-center justify-center px-4"
             wire:poll.3s="checkAnalisisMayor"
             x-data="{ seconds: 0, timer: null, init() { this.timer = setInterval(() => this.seconds++, 1000); } }"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-[2rem] shadow-soft p-8 max-w-sm w-full text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-primary-500/10 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <p class="text-base font-semibold text-neutral-900" x-text="seconds >= 60 ? 'El analisis esta tomando mas de lo esperado...' : seconds >= 25 ? 'Procesando documento extenso...' : seconds >= 10 ? 'Extrayendo y analizando el contenido...' : 'Preparando analisis con IA...'"></p>
                <p class="text-sm text-neutral-400 mt-2 leading-relaxed" x-text="seconds >= 60 ? 'Por favor no cierre esta pagina. El resultado aparecera pronto.' : seconds >= 25 ? 'Los documentos extensos requieren mas tiempo de procesamiento.' : seconds >= 10 ? 'Estamos leyendo cada seccion del TDR para darte un analisis preciso.' : 'Conectando con el servicio de inteligencia artificial...'"></p>
                <p class="text-xs text-neutral-400/70 mt-4">Tiempo: <span x-text="seconds + 's'"></span></p>
                <div class="flex justify-center gap-1.5 mt-4">
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0s"></span>
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>
    @endif

    {{-- Resultado del análisis --}}
    @if($resultadoAnalisis)
        @php
            $esMayores = ($resultadoAnalisis['_formato'] ?? '') === 'mayores';
            $resumenEjecutivo = $resultadoAnalisis['resumen_ejecutivo'] ?? null;
            $meta = $resultadoAnalisis['metadatos_proceso'] ?? null;
            $calificacion = $resultadoAnalisis['requisitos_admisibilidad_y_calificacion'] ?? null;
            $factores = $resultadoAnalisis['factores_puntaje_evaluacion'] ?? [];
            $consorcio = $resultadoAnalisis['parametros_consorcio'] ?? null;
            $garantias = $resultadoAnalisis['garantias_y_penalidades'] ?? null;
            $normalizeList = function ($value) {
                if (is_array($value)) {
                    return array_values(array_filter($value, fn ($item) => filled($item)));
                }
                if (is_string($value) && trim($value) !== '') {
                    return array_filter(array_map('trim', preg_split('/[\r\n]+/', $value)));
                }
                return [];
            };
            $requisitosList = $normalizeList($resultadoAnalisis['requisitos_calificacion'] ?? []);
            $reglasList = $normalizeList($resultadoAnalisis['reglas_ejecucion'] ?? []);
            $penalidadesList = $normalizeList($resultadoAnalisis['penalidades'] ?? []);
            $montoReferencial = $resultadoAnalisis['presupuesto_referencial'] ?? null;
        @endphp
        <div class="fixed inset-0 z-[120] flex items-center justify-center px-4 py-8"
             x-data
             x-on:keydown.escape.window="$wire.call('cerrarAnalisis')"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarAnalisis"></div>
            <div class="relative w-full max-w-4xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[92vh]">
                {{-- Header --}}
                <div class="sticky top-0 z-10 bg-white border-b border-neutral-100 rounded-t-[2rem]">
                    <div class="w-full border-t border-primary-200"></div>
                    <div class="p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-primary-500/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-xs font-semibold uppercase text-primary-500 tracking-[0.2em]">Análisis IA del TDR</p>
                                    @if($analisisDisponiblesMayor && count($analisisDisponiblesMayor) > 1)
                                        <select wire:model.live="analisisSeleccionadoIdMayor" wire:change="seleccionarAnalisisMayor($event.target.value)" class="ml-2 px-2.5 py-1 text-[11px] font-semibold rounded-full border border-neutral-200 bg-neutral-50 text-neutral-700 focus:outline-none focus:ring-2 focus:ring-primary-500">
                                            @foreach($analisisDisponiblesMayor as $a)
                                                <option value="{{ $a['id'] }}">Análisis {{ $loop->iteration }} &mdash; {{ $a['analizado_en'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                <h3 class="text-xl lg:text-2xl font-bold text-neutral-900">{{ $contratoAnalizado['nomenclatura'] ?? 'Proceso' }}</h3>
                                <p class="text-sm text-neutral-600 mt-0.5">{{ $contratoAnalizado['entidad_nombre'] ?? 'Entidad no disponible' }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="cerrarAnalisis" class="flex-shrink-0 w-10 h-10 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">
                    @if($analisisDisponiblesMayor && count($analisisDisponiblesMayor) > 1)
                        <p class="text-xs text-primary-600 bg-primary-50 rounded-xl px-3 py-2">
                            Este proceso tiene {{ count($analisisDisponiblesMayor) }} análisis. Usá el selector para cambiar entre ellos.
                        </p>
                    @endif

                    {{-- Resumen Ejecutivo --}}
                    @if($resumenEjecutivo)
                        <div class="relative bg-gradient-to-br from-primary-500/5 to-secondary-500/5 border border-primary-200/50 rounded-2xl p-5 lg:p-6">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-primary-500/10 flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-primary-500 uppercase tracking-[0.2em] mb-2">Resumen ejecutivo</p>
                                    <p class="text-sm lg:text-base text-neutral-800 leading-relaxed">{{ $resumenEjecutivo }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Grid: contexto + monto + requisitos --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        {{-- Contexto --}}
                        <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Contexto del proceso</h4>
                            </div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Entidad</dt>
                                    <dd class="text-sm font-medium text-neutral-900 mt-0.5">{{ $contratoAnalizado['entidad_nombre'] ?? '---' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Objeto</dt>
                                    <dd class="text-sm text-neutral-700 mt-0.5">{{ $contratoAnalizado['objeto_contratacion'] ?? '---' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Estado</dt>
                                    <dd class="mt-1 flex items-center gap-2">
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-secondary-500/10 text-secondary-500 border border-secondary-200">{{ $contratoAnalizado['estado'] ?? '---' }}</span>
                                        @if(isset($contratoAnalizado['vigente']))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full {{ $contratoAnalizado['vigente'] ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-50 text-red-600 border border-red-200' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $contratoAnalizado['vigente'] ? 'bg-green-500 animate-pulse' : 'bg-red-400' }}"></span>
                                                {{ $contratoAnalizado['vigente'] ? 'VIGENTE' : strtoupper($contratoAnalizado['estado_vigencia']) }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Método</dt>
                                    <dd class="text-xs text-neutral-600 mt-0.5">{{ $contratoAnalizado['metodo_contratacion'] ?? '---' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Fechas + Monto --}}
                        <div class="border border-neutral-200 rounded-2xl p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-secondary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Datos del proceso</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                    <span class="text-xs text-neutral-500">Publicación</span>
                                    <p class="text-xs font-semibold text-neutral-900">{{ $contratoAnalizado['fecha_formateada'] ?? 'N/D' }}</p>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                    <span class="text-xs text-neutral-500">Moneda</span>
                                    <p class="text-xs font-semibold text-neutral-900">{{ $contratoAnalizado['moneda'] ?? '---' }}</p>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                    <span class="text-xs text-neutral-500">RUC</span>
                                    <p class="text-xs font-semibold text-neutral-900">{{ $contratoAnalizado['entidad_ruc'] ?? '---' }}</p>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-xs text-neutral-500">Monto referencial</span>
                                    <p class="text-sm font-bold text-neutral-900">{{ $montoReferencial ?? ($contratoAnalizado['monto_formateado'] ?? 'N/D') }}</p>
                                </div>

                                @if(!empty($documentosCarpeta))
                                    <div class="mt-4 pt-3 border-t border-neutral-200">
                                        <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-2">Documentos del proceso</p>
                                        <div class="space-y-1.5 max-h-40 overflow-y-auto">
                                            @foreach($documentosCarpeta as $doc)
                                                <a href="{{ route('tdr.extracted.view', ['tipo' => 'mayores', 'ocid' => $contratoAnalizado['ocid'], 'filename' => $doc['filename']]) }}"
                                                   target="_blank"
                                                   class="flex items-center gap-2 text-xs text-primary-600 hover:text-primary-800 hover:underline group">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    <span class="truncate group-hover:text-primary-700">{{ $doc['filename'] }}</span>
                                                    <span class="text-[9px] text-neutral-400 ml-auto flex-shrink-0">{{ round($doc['size'] / 1024, 1) }} KB</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Compatibilidad (solo auth) --}}
                        @auth
                            <div class="rounded-2xl p-5 bg-white border border-neutral-200 shadow-soft space-y-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-neutral-900">Compatibilidad</h4>
                                </div>
                                @if(empty($suscriptoresUsuarioMayor))
                                    <div class="rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-4 text-center">
                                        <p class="text-xs text-neutral-500">No tienes suscriptores activos.</p>
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        @foreach($suscriptoresUsuarioMayor as $suscriptor)
                                            @php
                                                $compat = $compatibilidadPorSuscriptorMayor[$suscriptor['id']] ?? null;
                                                $score = $compat['score'] ?? null;
                                                $nivel = $compat['nivel'] ?? null;
                                            @endphp
                                            <button wire:click="calcularCompatibilidadMayor({{ $suscriptor['id'] }})" wire:loading.attr="disabled" wire:target="calcularCompatibilidadMayor({{ $suscriptor['id'] }})" class="w-full flex items-center justify-between gap-3 rounded-xl border border-neutral-100 bg-neutral-50 hover:bg-primary-50/50 px-4 py-3 transition-colors disabled:opacity-50">
                                                <div class="min-w-0 text-left">
                                                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ $suscriptor['label'] }}</p>
                                                    @if(!is_null($score))
                                                        <p class="text-[11px] text-neutral-500">{{ $compat['actualizado'] ?? '' }}</p>
                                                    @elseif(!$suscriptor['has_copy'])
                                                        <p class="text-[11px] text-neutral-400">Falta copy del suscriptor</p>
                                                    @endif
                                                </div>
                                                <div class="flex-shrink-0">
                                                    @if(!is_null($score))
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full {{ ($score >= 75) ? 'bg-green-100 text-green-700' : (($score >= 50) ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                                            {{ round($score) }}% {{ $nivel ? '- ' . $nivel : '' }}
                                                        </span>
                                                    @elseif($compatibilidadEnCursoMayor === $suscriptor['id'])
                                                        <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    @else
                                                        <span class="text-xs font-semibold text-primary-500">Calcular</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endauth
                    </div>

                    @if(!$esMayores)
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                        {{-- Requisitos de Calificación --}}
                    @if(!empty($requisitosList))
                        <div class="border border-neutral-200 rounded-2xl p-5 lg:p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Requisitos de calificación</h4>
                            </div>
                            <ul class="space-y-2">
                                @foreach($requisitosList as $req)
                                    <li class="flex items-start gap-2.5 text-sm text-neutral-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mt-2 flex-shrink-0"></span>
                                        <span>{{ is_array($req) ? ($req['descripcion'] ?? json_encode($req)) : $req }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Reglas de Ejecución --}}
                    @if(!empty($reglasList))
                        <div class="border border-neutral-200 rounded-2xl p-5 lg:p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-secondary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Reglas de ejecución</h4>
                            </div>
                            <ul class="space-y-2">
                                @foreach($reglasList as $regla)
                                    <li class="flex items-start gap-2.5 text-sm text-neutral-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-secondary-500 mt-2 flex-shrink-0"></span>
                                        <span>{{ is_array($regla) ? ($regla['descripcion'] ?? json_encode($regla)) : $regla }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Penalidades --}}
                    @if(!empty($penalidadesList))
                        <div class="border border-neutral-200 rounded-2xl p-5 lg:p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Penalidades</h4>
                            </div>
                            <ul class="space-y-2">
                                @foreach($penalidadesList as $pen)
                                    <li class="flex items-start gap-2.5 text-sm text-neutral-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 mt-2 flex-shrink-0"></span>
                                        <span>{{ is_array($pen) ? ($pen['descripcion'] ?? json_encode($pen)) : $pen }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                        </div>
                    @endif {{-- end !$esMayores --}}

                    {{-- MAYORES: Requisitos de Calificación --}}
                    @if($esMayores && $calificacion)
                        <div class="border border-primary-200 rounded-2xl p-5 lg:p-6 bg-primary-50/30">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Requisitos de Calificación (Pasa/No Pasa)</h4>
                            </div>
                            <dl class="space-y-3 text-sm">
                                @if(!empty($calificacion['habilitaciones_legales_obligatorias']))
                                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Habilitaciones Legales</dt><dd class="text-neutral-700 mt-0.5">{{ implode(', ', $calificacion['habilitaciones_legales_obligatorias']) }}</dd></div>
                                @endif
                                @if(!empty($calificacion['equipamiento_infraestructura']))
                                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Equipamiento</dt><dd class="text-neutral-700 mt-0.5">{{ implode(', ', $calificacion['equipamiento_infraestructura']) }}</dd></div>
                                @endif
                                @if(!empty($calificacion['experiencia_financiera_postor']))
                                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Experiencia Financiera</dt><dd class="text-neutral-700 mt-0.5">{{ $calificacion['experiencia_financiera_postor'] }}</dd></div>
                                @endif
                                @if(!empty($calificacion['perfil_personal_clave']))
                                    <div><dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider mb-2">Personal Clave</dt>
                                        @foreach($calificacion['perfil_personal_clave'] as $p)
                                            <dd class="bg-white rounded-xl p-3 mb-2 border border-neutral-100">
                                                <p class="font-semibold text-neutral-800">{{ $p['cargo'] ?? '---' }}</p>
                                                <p class="text-xs text-neutral-500">{{ $p['formacion_academica'] ?? '' }}</p>
                                                <p class="text-xs text-neutral-500">{{ $p['experiencia_especifica_obligatoria'] ?? '' }}</p>
                                            </dd>
                                        @endforeach
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- MAYORES: Factores de Evaluación --}}
                    @if($esMayores && !empty($factores))
                        <div class="border border-secondary-200 rounded-2xl p-5 lg:p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-7 h-7 rounded-lg bg-secondary-500/10 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Factores de Evaluación (0-100 pts)</h4>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm"><thead class="bg-secondary-50 border-b border-secondary-100"><tr><th class="px-3 py-2 text-left text-xs font-semibold text-secondary-600">Factor</th><th class="px-3 py-2 text-center text-xs font-semibold text-secondary-600">Puntaje Máx</th><th class="px-3 py-2 text-left text-xs font-semibold text-secondary-600">Criterio</th></tr></thead>
                                    <tbody class="divide-y divide-neutral-100">
                                        @foreach($factores as $f)
                                            <tr><td class="px-3 py-2 font-medium text-neutral-800">{{ $f['factor_nombre'] ?? '---' }}</td><td class="px-3 py-2 text-center font-bold text-neutral-900">{{ $f['puntaje_maximo_asignado'] ?? 0 }}</td><td class="px-3 py-2 text-neutral-600">{{ $f['criterio_evaluacion'] ?? '' }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- MAYORES: Consorcio + Garantías --}}
                    @if($esMayores)
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @if($consorcio)
                                <div class="border border-neutral-200 rounded-2xl p-5">
                                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div><h4 class="text-sm font-bold text-neutral-900">Consorcio</h4></div>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between"><dt class="text-xs text-neutral-500">Permitido</dt><dd class="font-semibold {{ $consorcio['permite_consorcio'] ? 'text-green-600' : 'text-red-600' }}">{{ $consorcio['permite_consorcio'] ? 'Sí' : 'No' }}</dd></div>
                                        @if($consorcio['limite_maximo_integrantes'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">Límite integrantes</dt><dd class="font-semibold">{{ $consorcio['limite_maximo_integrantes'] }}</dd></div>@endif
                                        @if($consorcio['porcentaje_minimo_individual'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">% Mín x miembro</dt><dd class="font-semibold">{{ $consorcio['porcentaje_minimo_individual'] }}</dd></div>@endif
                                        @if($consorcio['porcentaje_minimo_mayor_experiencia'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">% Mín mayor exp</dt><dd class="font-semibold">{{ $consorcio['porcentaje_minimo_mayor_experiencia'] }}</dd></div>@endif
                                    </dl>
                                </div>
                            @endif
                            @if($garantias)
                                <div class="border border-neutral-200 rounded-2xl p-5">
                                    <div class="flex items-center gap-2 mb-3"><div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div><h4 class="text-sm font-bold text-neutral-900">Garantías y Penalidades</h4></div>
                                    <dl class="space-y-2 text-sm">
                                        @if($garantias['porcentaje_garantia_fiel_cumplimiento'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">Garantía Fiel Cumpl.</dt><dd class="font-semibold">{{ $garantias['porcentaje_garantia_fiel_cumplimiento'] }}</dd></div>@endif
                                        <div class="flex justify-between"><dt class="text-xs text-neutral-500">Retención MYPE</dt><dd class="font-semibold {{ ($garantias['permite_retencion_mype'] ?? false) ? 'text-green-600' : 'text-red-600' }}">{{ ($garantias['permite_retencion_mype'] ?? false) ? 'Sí' : 'No mencionada' }}</dd></div>
                                        @if($garantias['penalidad_mora_tope_maximo'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">Penalidad mora</dt><dd class="font-semibold">{{ $garantias['penalidad_mora_tope_maximo'] }}</dd></div>@endif
                                        @if($garantias['otras_penalidades_tope'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">Otras penalidades</dt><dd class="font-semibold">{{ $garantias['otras_penalidades_tope'] }}</dd></div>@endif
                                        @if($garantias['plazo_estimado_ejecucion'])<div class="flex justify-between"><dt class="text-xs text-neutral-500">Plazo ejecución</dt><dd class="font-semibold">{{ $garantias['plazo_estimado_ejecucion'] }}</dd></div>@endif
                                    </dl>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Direccionamiento en progreso --}}
    @if($analizandoDireccOcid)
        <div class="fixed inset-0 z-[200] flex items-center justify-center px-4"
             x-data="{ seconds: 0, timer: null, init() { this.timer = setInterval(() => this.seconds++, 1000); } }"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-[2rem] shadow-soft p-8 max-w-sm w-full text-center">
                <div class="mx-auto w-16 h-16 rounded-full bg-red-500/10 flex items-center justify-center mb-5">
                    <svg class="w-8 h-8 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <p class="text-base font-semibold text-neutral-900" x-text="seconds >= 45 ? 'Analisis forense avanzado en proceso...' : seconds >= 15 ? 'Examinando clausulas del documento...' : 'Analizando direccionamiento...'"></p>
                <p class="text-sm text-neutral-400 mt-2 leading-relaxed" x-text="seconds >= 45 ? 'El documento es extenso. Se esta realizando un analisis profundo.' : seconds >= 15 ? 'Buscando indicadores de direccionamiento y restricciones indebidas.' : 'Detectando posibles indicadores de riesgo en el documento...'"></p>
                <p class="text-xs text-neutral-400/70 mt-4">Tiempo: <span x-text="seconds + 's'"></span></p>
                <div class="flex justify-center gap-1.5 mt-4">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-bounce" style="animation-delay: 0s"></span>
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-bounce" style="animation-delay: 0.15s"></span>
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-bounce" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>
    @endif

    {{-- Resultado Direccionamiento --}}
    @if($resultadoDireccionamiento)
        <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarDireccionamiento()">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarDireccionamiento"></div>
            <div class="relative w-full max-w-3xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[90vh]">
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-neutral-100">
                    <div class="flex items-center gap-3">
                        @php
                            $score = ($resultadoDireccionamiento['score_probabilidad_direccionamiento'] ?? $resultadoDireccionamiento['score_riesgo_corrupcion'] ?? 0);
                            $score = is_numeric($score) ? intval($score) : 0;
                            $estado = $resultadoDireccionamiento['estado_proceso'] ?? $resultadoDireccionamiento['veredicto_flash'] ?? '';
                            $esMayor = isset($resultadoDireccionamiento['anomalias_detectadas']) || isset($resultadoDireccionamiento['score_probabilidad_direccionamiento']);
                            $hallazgos = $resultadoDireccionamiento['anomalias_detectadas'] ?? $resultadoDireccionamiento['hallazgos_criticos'] ?? [];
                            if ($score <= 25) { $colorClase = 'green'; $colorHex = '#10b981'; $colorBg = 'bg-green-500'; }
                            elseif ($score <= 65) { $colorClase = 'amber'; $colorHex = '#f59e0b'; $colorBg = 'bg-amber-500'; }
                            else { $colorClase = 'red'; $colorHex = '#ef4444'; $colorBg = 'bg-red-500'; }
                        @endphp
                        <div class="w-10 h-10 rounded-full bg-{{ $colorClase }}-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-{{ $colorClase }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-neutral-900">Auditoría de Direccionamiento</h3>
                            <p class="text-xs text-neutral-400">{{ $contratoAnalizado['nomenclatura'] ?? '' }}</p>
                        </div>
                    </div>
                    <button wire:click="cerrarDireccionamiento" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors">Cerrar</button>
                </div>
                <div class="overflow-y-auto px-6 py-6 space-y-6">
                    {{-- Score Gauge --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1 flex flex-col items-center justify-center bg-neutral-50 rounded-2xl p-6 border border-neutral-200">
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Probabilidad de Direccionamiento</p>
                            <div class="relative w-28 h-28 mb-3">
                                <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $colorHex }}" stroke-width="8"
                                        stroke-dasharray="{{ $score * 3.267 }} 327"
                                        stroke-linecap="round"/>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-2xl font-black text-neutral-900">{{ $score }}<span class="text-xs font-medium text-neutral-400">%</span></span>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-{{ $colorClase }}-100 text-{{ $colorClase }}-700 border border-{{ $colorClase }}-200">
                                <span class="w-1.5 h-1.5 rounded-full {{ $colorBg }}"></span>
                                {{ $estado }}
                            </span>
                        </div>
                        <div class="md:col-span-2 bg-neutral-50 rounded-2xl p-5 border border-neutral-200">
                            <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Fundamento Analítico</p>
                            <p class="text-sm text-neutral-700 leading-relaxed">{{ $resultadoDireccionamiento['fundamento_analitico_general'] ?? $resultadoDireccionamiento['detalle'] ?? $resultadoDireccionamiento['resumen_ejecutivo'] ?? 'Sin fundamento disponible.' }}</p>
                        </div>
                    </div>

                    {{-- Anomalías / Hallazgos --}}
                    @if(!empty($hallazgos))
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-6 h-6 rounded-lg bg-red-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h1m16 0h1M4 4v16a2 2 0 002 2h12a2 2 0 002-2V4M4 4h16M9 8h6m-6 4h6m-6 4h6"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Anomalías Detectadas <span class="text-xs font-normal text-neutral-400">({{ count($hallazgos) }})</span></h4>
                            </div>
                            <div class="space-y-3">
                                @foreach($hallazgos as $idx => $h)
                                    @php
                                        $clasif = $h['clasificacion_riesgo'] ?? $h['categoria'] ?? '';
                                        $impacto = $h['nivel_impacto'] ?? $h['nivel_de_gravedad'] ?? 'Medio';
                                        $impactoColor = match(strtolower($impacto)) { 'critico', 'alto' => 'red', 'medio' => 'amber', default => 'neutral' };
                                    @endphp
                                    <div class="rounded-2xl border border-neutral-200 bg-white overflow-hidden">
                                        <div class="flex items-center justify-between px-5 py-3 bg-neutral-50 border-b border-neutral-100">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-bold text-neutral-800">#{{ $idx + 1 }}</span>
                                                <span class="text-xs font-semibold text-neutral-500">{{ $clasif }}</span>
                                            </div>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-{{ $impactoColor }}-100 text-{{ $impactoColor }}-700 border border-{{ $impactoColor }}-200">
                                                <span class="w-1 h-1 rounded-full bg-{{ $impactoColor }}-500"></span>
                                                {{ strtoupper($impacto) }}
                                            </span>
                                        </div>
                                        <div class="px-5 py-4 space-y-3">
                                            @if(!empty($h['extracto_base_sospechoso'] ?? $h['descripcion_hallazgo']))
                                                <div>
                                                    <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Extracto Sospechoso</p>
                                                    <p class="text-sm text-neutral-800 bg-neutral-50 rounded-xl p-3 italic border border-neutral-100">"{{ $h['extracto_base_sospechoso'] ?? $h['descripcion_hallazgo'] }}"</p>
                                                </div>
                                            @endif
                                            @if(!empty($h['analisis_proporcionalidad'] ?? $h['red_flag_detectada']))
                                                <div>
                                                    <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Análisis de Proporcionalidad</p>
                                                    <p class="text-sm text-neutral-600 leading-relaxed">{{ $h['analisis_proporcionalidad'] ?? $h['red_flag_detectada'] }}</p>
                                                </div>
                                            @endif
                                            @if(!empty($h['argumento_legal_observacion'] ?? ($esMayor ? null : $resultadoDireccionamiento['argumento_para_observacion'] ?? null)))
                                                <div class="rounded-xl bg-{{ $colorClase }}-50 border border-{{ $colorClase }}-100 p-4">
                                                    <p class="text-[10px] font-bold text-{{ $colorClase }}-600 uppercase tracking-wider mb-1">Argumento Legal para Observación</p>
                                                    <p class="text-sm text-neutral-800 leading-relaxed">{{ $h['argumento_legal_observacion'] ?? $resultadoDireccionamiento['argumento_para_observacion'] }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl bg-green-50 border border-green-200 p-6 text-center">
                            <svg class="w-10 h-10 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-semibold text-green-800">No se detectaron anomalías</p>
                            <p class="text-xs text-green-600 mt-1">El proceso parece conforme bajo los principios de la Ley N° 32069.</p>
                        </div>
                    @endif

                    {{-- Recomendaciones (Menores format) --}}
                    @if(!empty($resultadoDireccionamiento['recomendaciones']))
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">Recomendaciones</p>
                            <p class="text-sm text-blue-800 leading-relaxed">{{ $resultadoDireccionamiento['recomendaciones'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Resultado Proforma --}}
    @if($resultadoProformaMayor)
        <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarProformaMayor()">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarProformaMayor"></div>
            <div class="relative w-full max-w-4xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[90vh]">
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-neutral-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-neutral-900">Proforma Técnica</h3>
                            <p class="text-xs text-neutral-400">{{ $resultadoProformaMayor['titulo_proceso'] ?? $resultadoProformaMayor['empresa_nombre'] ?? 'Proforma de Cotización' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('proforma.word', $proformaTokenMayor) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-full bg-violet-500 text-white hover:bg-violet-600 transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Descargar PDF
                        </a>
                        <button wire:click="cerrarProformaMayor" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors">Cerrar</button>
                    </div>
                </div>
                <div class="overflow-y-auto px-6 py-6 space-y-6">
                    {{-- Header: Empresa + Rubro --}}
                    @if(!empty($resultadoProformaMayor['empresa_nombre']) || !empty($resultadoProformaMayor['empresa_rubro']))
                        <div class="rounded-2xl bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200 p-5">
                            <p class="text-sm font-bold text-violet-900">{{ $resultadoProformaMayor['empresa_nombre'] ?? '' }}</p>
                            @if(!empty($resultadoProformaMayor['empresa_rubro']))
                                <p class="text-xs text-violet-600 mt-0.5">{{ $resultadoProformaMayor['empresa_rubro'] }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Items Table --}}
                    @if(!empty($resultadoProformaMayor['items']))
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-6 h-6 rounded-lg bg-violet-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <h4 class="text-sm font-bold text-neutral-900">Items de Cotización</h4>
                            </div>
                            <div class="overflow-hidden rounded-2xl border border-neutral-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-neutral-50 border-b border-neutral-200">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-neutral-400 uppercase tracking-wider">#</th>
                                            <th class="px-4 py-3 text-left text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Descripción</th>
                                            <th class="px-4 py-3 text-center text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Unidad</th>
                                            <th class="px-4 py-3 text-center text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Cant.</th>
                                            <th class="px-4 py-3 text-right text-[11px] font-bold text-neutral-400 uppercase tracking-wider">P. Unitario</th>
                                            <th class="px-4 py-3 text-right text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-100">
                                        @foreach($resultadoProformaMayor['items'] as $j => $item)
                                            @php
                                                $itemData = is_array($item) ? $item : ['descripcion' => $item];
                                                $precioUnit = $itemData['precio_unitario'] ?? null;
                                                $subtotal = $itemData['subtotal'] ?? null;
                                            @endphp
                                            <tr class="{{ $j % 2 === 0 ? 'bg-white' : 'bg-neutral-50/50' }}">
                                                <td class="px-4 py-3 text-neutral-400 font-medium">{{ $j + 1 }}</td>
                                                <td class="px-4 py-3 text-neutral-800 font-medium">{{ $itemData['descripcion'] ?? '---' }}</td>
                                                <td class="px-4 py-3 text-center text-neutral-500 text-xs">{{ $itemData['unidad'] ?? 'Und' }}</td>
                                                <td class="px-4 py-3 text-center text-neutral-700">{{ $itemData['cantidad'] ?? 1 }}</td>
                                                <td class="px-4 py-3 text-right text-neutral-700 tabular-nums">{{ is_numeric($precioUnit) ? 'S/ ' . number_format((float)$precioUnit, 2) : ($precioUnit ?? '---') }}</td>
                                                <td class="px-4 py-3 text-right font-semibold text-neutral-800 tabular-nums">{{ is_numeric($subtotal) ? 'S/ ' . number_format((float)$subtotal, 2) : ($subtotal ?? '---') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Total + Estructura Costos --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(!empty($resultadoProformaMayor['estructura_costos']))
                            @php $ec = $resultadoProformaMayor['estructura_costos']; @endphp
                            <div class="rounded-2xl border border-neutral-200 p-5">
                                <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-4">Estructura de Costos</p>
                                <div class="space-y-3">
                                    @php
                                        $costosLabels = [
                                            'costo_directo' => ['label' => 'Costo Directo', 'color' => 'bg-violet-500'],
                                            'gastos_generales' => ['label' => 'Gastos Generales', 'color' => 'bg-fuchsia-500'],
                                            'utilidad' => ['label' => 'Utilidad', 'color' => 'bg-emerald-500'],
                                            'igv' => ['label' => 'IGV', 'color' => 'bg-amber-500'],
                                        ];
                                    @endphp
                                    @foreach($costosLabels as $key => $cfg)
                                        @if(!empty($ec[$key]))
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $cfg['color'] }}"></span>
                                                    <span class="text-sm text-neutral-600 truncate">{{ $cfg['label'] }}</span>
                                                </div>
                                                <span class="text-sm font-semibold text-neutral-800 tabular-nums flex-shrink-0">{{ $ec[$key] }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($resultadoProformaMayor['total_estimado']))
                            <div class="rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 p-5 flex flex-col items-center justify-center text-white">
                                <p class="text-xs font-semibold text-violet-100 uppercase tracking-wider mb-1">Total Estimado</p>
                                <p class="text-3xl font-black tabular-nums">{{ $resultadoProformaMayor['total_estimado'] }}</p>
                                <p class="text-[11px] text-violet-200 mt-1">Incluye IGV</p>
                            </div>
                        @endif
                    </div>

                    {{-- Advertencias Financieras --}}
                    @if(!empty($resultadoProformaMayor['advertencias_financieras']))
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <h4 class="text-sm font-bold text-amber-800">Advertencias Financieras</h4>
                            </div>
                            <ul class="space-y-2">
                                @foreach($resultadoProformaMayor['advertencias_financieras'] as $adv)
                                    <li class="flex items-start gap-2 text-sm text-amber-700">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                        <span>{{ $adv }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Análisis de Viabilidad --}}
                    @if(!empty($resultadoProformaMayor['analisis_viabilidad']))
                        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5">
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Análisis de Viabilidad</p>
                            <p class="text-sm text-neutral-700 leading-relaxed whitespace-pre-line">{{ $resultadoProformaMayor['analisis_viabilidad'] }}</p>
                        </div>
                    @endif

                    {{-- Recomendación Consorcio --}}
                    @if(!empty($resultadoProformaMayor['recomendacion_consorcio']))
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <h4 class="text-sm font-bold text-blue-800">Recomendación de Consorcio</h4>
                            </div>
                            <p class="text-sm text-blue-700 leading-relaxed">{{ $resultadoProformaMayor['recomendacion_consorcio'] }}</p>
                        </div>
                    @endif

                    {{-- Condiciones --}}
                    @if(!empty($resultadoProformaMayor['condiciones']))
                        <div class="rounded-2xl border border-neutral-200 p-5">
                            <p class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Condiciones y Supuestos</p>
                            <ul class="space-y-1.5">
                                @foreach($resultadoProformaMayor['condiciones'] as $cond)
                                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                                        <svg class="w-3.5 h-3.5 text-neutral-300 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <span>{{ $cond }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Footer: Footer actions --}}
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Imprimir
                        </button>
                        <a href="{{ route('proforma.word', $proformaTokenMayor) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-full bg-violet-500 text-white hover:bg-violet-600 transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Descargar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Empty --}}
    @if(!$buscando && empty($resultados) && !$mensajeError)
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-200 p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-neutral-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-neutral-600 mb-1">Contratos Mayores</h3>
            <p class="text-sm text-neutral-400 max-w-md mx-auto">Seleccioná un año y hacé clic en Buscar. Los datos provienen de la API OCDS del OECE.</p>
        </div>
    @endif

    {{-- Modal Detalle --}}
    @if($detalleContrato)
        <div class="fixed inset-0 z-[120] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarDetalle()">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarDetalle"></div>
            <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[85vh]">
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-5 border-b border-neutral-100">
                    <div>
                        <h3 class="text-lg font-bold text-neutral-900">{{ $detalleContrato['nomenclatura'] ?? 'Detalle del Proceso' }}</h3>
                        <p class="text-xs text-neutral-500 mt-0.5">{{ $detalleContrato['entidad_nombre'] ?? '' }}</p>
                    </div>
                    <button wire:click="cerrarDetalle" class="flex-shrink-0 px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors">Cerrar</button>
                </div>
                <div class="overflow-y-auto px-6 py-5 space-y-5 text-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><span class="text-xs text-neutral-500">RUC:</span> <span class="font-medium">{{ $detalleContrato['entidad_ruc'] ?? '---' }}</span></div>
                        <div><span class="text-xs text-neutral-500">Dirección:</span> <span class="font-medium">{{ $detalleContrato['entidad_direccion'] ?? '---' }}</span></div>
                        <div><span class="text-xs text-neutral-500">Objeto:</span> <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700">{{ $detalleContrato['objeto_contratacion'] ?? '' }}</span></div>
                        <div><span class="text-xs text-neutral-500">Método:</span> <span class="font-medium">{{ $detalleContrato['metodo_contratacion'] ?? '---' }}</span></div>
                        <div><span class="text-xs text-neutral-500">Estado:</span>
                            <span class="font-medium">{{ $detalleContrato['estado'] ?? '---' }}</span>
                            @if(isset($detalleContrato['vigente']))
                                <span class="ml-1 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $detalleContrato['vigente'] ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-neutral-100 text-neutral-500 border border-neutral-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $detalleContrato['vigente'] ? 'bg-green-500 animate-pulse' : 'bg-neutral-400' }}"></span>
                                    {{ $detalleContrato['vigente'] ? 'Vigente' : $detalleContrato['estado_vigencia'] }}
                                </span>
                            @endif
                        </div>
                        <div><span class="text-xs text-neutral-500">Moneda:</span> <span class="font-medium">{{ $detalleContrato['moneda'] ?? '' }}</span></div>
                        <div><span class="text-xs text-neutral-500">Monto:</span> <span class="font-semibold text-neutral-900">{{ $detalleContrato['monto_formateado'] }}</span></div>
                        <div><span class="text-xs text-neutral-500">Publicación:</span> <span class="font-medium">{{ $detalleContrato['fecha_formateada'] }}</span></div>
                        @if($detalleContrato['fecha_inicio_fmt'] === $detalleContrato['fecha_fin_fmt'])
                            <div><span class="text-xs text-neutral-500">Vigencia:</span> <span class="font-medium">{{ $detalleContrato['fecha_inicio_fmt'] }}</span></div>
                        @else
                            <div><span class="text-xs text-neutral-500">Inicio:</span> <span class="font-medium">{{ $detalleContrato['fecha_inicio_fmt'] }}</span></div>
                            <div><span class="text-xs text-neutral-500">Fin:</span> <span class="font-medium">{{ $detalleContrato['fecha_fin_fmt'] }}</span></div>
                        @endif
                    </div>
                    @if(!empty($detalleContrato['descripcion_objeto']))
                        <div><p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">Descripción</p><p class="text-sm text-neutral-700 leading-relaxed">{{ $detalleContrato['descripcion_objeto'] }}</p></div>
                    @endif
                    @if(!empty($detalleContrato['proveedores']))
                        <div><p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">Proveedores</p><p class="text-sm text-neutral-700">{{ implode(', ', $detalleContrato['proveedores']) }}</p></div>
                    @endif
                    @if(!empty($documentosCarpeta))
                        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4">
                            <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-3">Documentos del proceso</p>
                            <div class="space-y-1.5 max-h-44 overflow-y-auto">
                                @foreach($documentosCarpeta as $doc)
                                    <a href="{{ route('tdr.extracted.view', ['tipo' => 'mayores', 'ocid' => $detalleContrato['ocid'], 'filename' => $doc['filename']]) }}"
                                       target="_blank"
                                       class="flex items-center gap-2 text-xs text-primary-600 hover:text-primary-800 hover:underline group">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <span class="truncate group-hover:text-primary-700">{{ $doc['filename'] }}</span>
                                        <span class="text-[9px] text-neutral-400 ml-auto flex-shrink-0">{{ round($doc['size'] / 1024, 1) }} KB</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3 pt-2">
                        @if(!empty($detalleContrato['url_documento']))
                            <a href="{{ $detalleContrato['url_documento'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors">Descargar TDR</a>
                            <button wire:click="analizarTdr('{{ $detalleContrato['url_documento'] }}')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors shadow-sm">Analizar con IA</button>
                            <button wire:click="detectarDireccionamiento('{{ $detalleContrato['url_documento'] }}')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-red-500/40 text-red-600 bg-red-50 hover:bg-red-100 text-sm font-semibold transition-colors">Direccionamiento</button>
                            <button wire:click="generarProformaTecnicaMayor('{{ $detalleContrato['url_documento'] }}')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-secondary-500/40 text-secondary-600 bg-secondary-50 hover:bg-secondary-100 text-sm font-semibold transition-colors">Crear Proforma</button>
                        @endif
                        <button wire:click="verPartesMayor('{{ $detalleContrato['ocid'] ?? '' }}')" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-indigo-500/40 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 text-sm font-semibold transition-colors">Ver Partes</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Login requerido --}}
    @if($mostrarLoginModal)
        <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.call('cerrarLoginModal')">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarLoginModal"></div>
            <div class="relative w-full max-w-md bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-6 lg:p-7">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Acceso requerido</p>
                        <h3 class="text-xl font-bold text-neutral-900 mt-1">Inicia sesion para continuar</h3>
                        <p class="text-sm text-neutral-500 mt-1">{{ $loginModalMensaje }}</p>
                    </div>
                    <button type="button" wire:click="cerrarLoginModal" class="w-9 h-9 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-neutral-500">Correo</label>
                        <input type="email" wire:model="loginEmail" required class="mt-1 w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm" placeholder="correo@empresa.com"/>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-neutral-500">Contrasena</label>
                        <input type="password" wire:model="loginPassword" wire:keydown.enter="login" required class="mt-1 w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm" placeholder="********"/>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-neutral-500">
                        <input type="checkbox" wire:model="loginRemember" class="rounded border-neutral-300 text-primary-500 focus:ring-primary-500"/> Mantener sesion
                    </label>
                    @if($loginError)
                        <div class="bg-primary-500/10 border border-primary-200 text-brand-600 text-xs font-semibold rounded-2xl px-4 py-2">{{ $loginError }}</div>
                    @endif
                    <button type="button" wire:click="login" class="w-full py-3 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors">Iniciar sesion</button>
                    <p class="text-center text-xs text-neutral-400 mt-3">
                        ¿No tienes cuenta? <a href="{{ url('/register') }}" class="text-primary-500 hover:text-primary-600 font-semibold">Registrarse</a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Premium --}}
    {{-- Modal: Partes Involucradas --}}
    @if($mostrandoPartesOcid)
        <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarPartesMayor()">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarPartesMayor"></div>
            <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[85vh]">
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-neutral-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-neutral-900">Partes Involucradas</h3>
                            <p class="text-xs text-neutral-400">{{ $partesEntidadNombre }}</p>
                        </div>
                    </div>
                    <button wire:click="cerrarPartesMayor" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors">Cerrar</button>
                </div>
                <div class="overflow-y-auto px-6 py-5 space-y-4">
                    @forelse($partesProceso as $parte)
                        <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-neutral-900">{{ $parte['nombre'] }}</p>
                                    @if(!empty($parte['ruc']))
                                        <a href="https://e-consultaruc.sunat.gob.pe/cl-ti-itmrconsruc/jcrS00Alias?nroRuc={{ $parte['ruc'] }}" target="_blank" rel="noopener" class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline mt-0.5 inline-flex items-center gap-1">
                                            RUC: {{ $parte['ruc'] }}
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                                @if(!empty($parte['roles']))
                                    <div class="flex flex-wrap gap-1 justify-end">
                                        @foreach($parte['roles'] as $rol)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold
                                                {{ $rol === 'Ganador' ? 'bg-emerald-100 text-emerald-700 border border-emerald-300' : ($rol === 'Postor' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200') }}">
                                                {{ $rol }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                @if(!empty($parte['direccion']))
                                    <div class="col-span-2">
                                        <span class="text-neutral-400">Dirección:</span>
                                        <span class="text-neutral-700 ml-1">{{ $parte['direccion'] }}</span>
                                    </div>
                                @endif
                                @if(!empty($parte['localidad']) || !empty($parte['region']))
                                    <div>
                                        <span class="text-neutral-400">Ubicación:</span>
                                        <span class="text-neutral-700 ml-1">{{ implode(', ', array_filter([$parte['localidad'], $parte['region'], $parte['departamento']])) }}</span>
                                    </div>
                                @endif
                                @if(!empty($parte['telefono']))
                                    <div>
                                        <span class="text-neutral-400">Teléfono:</span>
                                        <span class="text-neutral-700 font-medium ml-1">{{ $parte['telefono'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-sm text-neutral-500">No hay información de partes disponible.</p>
                            <p class="text-xs text-neutral-400 mt-1">Los datos de postores aparecerán cuando el proceso avance a etapa de adjudicación.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Funcionalidad Premium --}}
    @if($mostrarFuncionalidadPremium)
        <div class="fixed inset-0 z-[140] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.cerrarFuncionalidadPremium()">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarFuncionalidadPremium"></div>
            <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-neutral-900 mb-2">Has descubierto una funcionalidad Premium</h3>
                <p class="text-sm text-neutral-600 mb-6">Esta funcionalidad está disponible en el plan <b>Proveedor Premium</b>. Mejora tu plan para acceder a análisis IA, direccionamiento, proformas y más.</p>
                <div class="space-y-2">
                    <a href="{{ url('/planes') }}" class="block w-full px-5 py-3 rounded-full bg-gradient-to-r from-amber-500 to-yellow-500 text-white text-sm font-bold hover:from-amber-600 hover:to-yellow-600 transition-colors shadow-md">Ver Planes</a>
                    <button wire:click="cerrarFuncionalidadPremium" class="w-full px-5 py-2.5 rounded-full border border-neutral-200 text-sm text-neutral-500 hover:text-neutral-800 hover:border-neutral-400 transition-colors">Cerrar</button>
                </div>
            </div>
        </div>
    @endif

    @if($mostrarAccesoRestringido)
        <div class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.call('cerrarAccesoRestringido')">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarAccesoRestringido"></div>
            <div class="relative w-full max-w-md bg-white rounded-[2rem] shadow-soft border border-neutral-200 overflow-hidden">
                <div class="h-1.5 w-full bg-gradient-to-r from-secondary-500 via-secondary-400 to-secondary-200"></div>
                <div class="p-6 lg:p-8">
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div class="w-14 h-14 rounded-2xl bg-secondary-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                        </div>
                        <button type="button" wire:click="cerrarAccesoRestringido" class="w-9 h-9 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <p class="text-xs font-bold uppercase text-secondary-500 tracking-[0.18em] mb-1">Función Premium</p>
                    <h3 class="text-xl font-bold text-neutral-900 leading-snug mb-3">Funcionalidad exclusiva</h3>
                    <p class="text-sm text-neutral-500 leading-relaxed">{{ $accesoRestringidoMensaje }}</p>
                    <ul class="mt-5 space-y-2">
                        <li class="flex items-center gap-2.5 text-sm text-neutral-600"><span class="w-5 h-5 rounded-full bg-secondary-500/10 flex items-center justify-center"><svg class="w-3 h-3 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>Análisis de TDR con inteligencia artificial</li>
                        <li class="flex items-center gap-2.5 text-sm text-neutral-600"><span class="w-5 h-5 rounded-full bg-secondary-500/10 flex items-center justify-center"><svg class="w-3 h-3 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>Generación automática de proformas técnicas</li>
                        <li class="flex items-center gap-2.5 text-sm text-neutral-600"><span class="w-5 h-5 rounded-full bg-secondary-500/10 flex items-center justify-center"><svg class="w-3 h-3 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>Seguimiento personalizado de procesos</li>
                    </ul>
                    <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
                        <a href="{{ route('planes') }}" class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-secondary-500 text-white text-sm font-bold hover:bg-secondary-400 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>Quiero ser Premium
                        </a>
                        <button type="button" wire:click="cerrarAccesoRestringido" class="w-full sm:w-auto px-6 py-3 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-500 hover:text-neutral-900 hover:border-neutral-400 transition-colors">Ahora no</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Seleccionar documento (ZIP con múltiples PDFs) --}}
    @if($mostrarSelectorDocumentos)
        <div class="fixed inset-0 z-[140] flex items-center justify-center px-4 py-8" x-data x-on:keydown.escape.window="$wire.call('cancelarSelectorDocumentos')">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelarSelectorDocumentos"></div>
            <div class="relative w-full max-w-lg bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[80vh]">
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-5 border-b border-neutral-100">
                    <div>
                        <p class="text-xs font-semibold uppercase text-amber-600 tracking-[0.2em] mb-1">Documentos múltiples</p>
                        <h3 class="text-lg font-bold text-neutral-900">Seleccioná el documento a analizar</h3>
                    </div>
                    <button wire:click="cancelarSelectorDocumentos" class="w-9 h-9 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto p-5 space-y-2">
                    <p class="text-sm text-neutral-500 mb-3">El archivo contiene {{ count($documentosExtraidos) }} documentos PDF. Elegí cuál querés analizar:</p>
                    @foreach($documentosExtraidos as $idx => $doc)
                        <button
                            wire:click="analizarDocumentoExtraidoMayor({{ $idx }})"
                            wire:loading.attr="disabled"
                            wire:target="analizarDocumentoExtraidoMayor({{ $idx }})"
                            class="w-full flex items-center gap-4 px-4 py-3.5 rounded-2xl border border-neutral-200 hover:border-primary-400 hover:bg-primary-50/50 transition-all text-left group"
                        >
                            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center shrink-0 group-hover:bg-primary-100 transition-colors">
                                <svg class="w-5 h-5 text-red-500 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-neutral-800 truncate">{{ $doc['filename'] }}</p>
                                <p class="text-xs text-neutral-400 mt-0.5">{{ number_format($doc['size'] / 1024, 0) }} KB</p>
                            </div>
                            <svg class="w-5 h-5 text-neutral-300 group-hover:text-primary-500 shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('login-redirect', (event) => {
            if (event?.url) window.location.href = event.url;
        });

        $wire.on('cotizar-seace-modal', (payload) => {
            const data = Array.isArray(payload) ? payload[0] : payload;
            if (!data?.desContratacion) {
                console.error('cotizar-seace-modal: faltan datos del contrato');
                return;
            }
            window.dispatchEvent(new CustomEvent('abrir-cotizador-seace', { detail: data }));
        });

        $wire.on('scroll-to-analisis-mayor', () => {
            const el = document.getElementById('resultado-analisis-mayor');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    </script>
    @endscript
</div>
