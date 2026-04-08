<div
    class="p-6 flex flex-col gap-6 max-w-full w-full min-w-0 overflow-visible"
    x-data="{
        tooltip: { show: false, text: '', x: 0, y: 0 },
        showTooltip(text, event) {
            this.tooltip.show = true;
            this.tooltip.text = text ?? '';
            this.tooltip.x = event?.clientX ?? 0;
            this.tooltip.y = event?.clientY ?? 0;
        },
        moveTooltip(event) {
            if (!this.tooltip.show) {
                return;
            }
            this.tooltip.x = event?.clientX ?? this.tooltip.x;
            this.tooltip.y = event?.clientY ?? this.tooltip.y;
        },
        hideTooltip() {
            this.tooltip.show = false;
        }
    }"
>
    <!-- Tooltip Personalizado Global -->
    <div
        x-show="tooltip.show"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :style="`position: fixed; left: ${tooltip.x}px; top: ${tooltip.y}px; transform: translate(-50%, -100%); margin-top: -8px; z-index: 9999;`"
        class="pointer-events-none"
        style="display: none;"
    >
        <div class="bg-neutral-900 text-white text-xs font-medium px-3 py-2 rounded-lg shadow-lg max-w-sm">
            <div x-text="tooltip.text" class="break-words"></div>
            <svg class="absolute left-1/2 -translate-x-1/2 -bottom-1 text-neutral-900" width="8" height="4" viewBox="0 0 8 4">
                <path d="M0 0 L4 4 L8 0 Z" fill="currentColor"/>
            </svg>
        </div>
    </div>

    <!-- Panel de Filtros Compacto -->
    <div class="bg-white rounded-3xl shadow-soft p-4 lg:p-6 border border-neutral-200 ">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-lg lg:text-xl font-bold text-neutral-900">Filtros de Búsqueda</h2>
                @if($this->contarFiltrosActivos() > 0)
                    <span class="px-2.5 py-1 bg-secondary-500 text-white text-xs font-semibold rounded-full">
                        {{ $this->contarFiltrosActivos() }}
                    </span>
                @endif
            </div>
            <button
                wire:click="limpiarFiltros"
                class="text-xs lg:text-sm text-neutral-600 hover:text-primary-500 font-medium transition-colors"
            >
                Limpiar todo
            </button>
        </div>

        <!-- Grid de Filtros Responsive -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 lg:gap-4 pb-5">
            <!-- Palabra Clave - Ocupa más espacio en desktop -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($palabraClave) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Palabra Clave</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="palabraClave"
                        placeholder="Buscar..."
                        wire:keydown.enter="buscar"
                        class="w-full px-4 py-2.5 pl-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($palabraClave) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($palabraClave) ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <!-- Indicador de carga -->
                    <div wire:loading wire:target="palabraClave" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Filtrando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entidad con Autocompletado -->
            <div class="lg:col-span-4"
                 x-data="{ abierto: false }"
                 @click.away="abierto = false"
                 @abrir-dropdown-entidades.window="abierto = true"
            >
                <label class="block text-xs font-medium mb-1.5 {{ !empty($codigoEntidad) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Entidad</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="entidadTexto"
                        @focus="abierto = ($wire.entidadesSugeridas.length > 0)"
                        wire:keydown.enter="buscar"
                        placeholder="Buscar entidad (mín. 3 caracteres)..."
                        class="w-full px-4 py-2.5 pl-10 pr-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($codigoEntidad) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($codigoEntidad) ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>

                    <!-- Spinner de búsqueda interno (mientras busca entidades) -->
                    <div wire:loading wire:target="entidadTexto,buscarEntidades" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-brand-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Botón de limpiar (color intenso cuando hay contenido) -->
                    @if(!empty($codigoEntidad))
                        <button
                            wire:click="limpiarEntidad"
                            @click="abierto = false"
                            type="button"
                            wire:loading.remove
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 hover:text-red-700 transition-colors bg-red-50 hover:bg-red-100 rounded-full p-1"
                            title="Limpiar entidad seleccionada"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif

                    <!-- Dropdown de sugerencias -->
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
                                @click="abierto = false; $wire.seleccionarEntidad('{{ $entidad['razonSocial'] }}', '{{ $entidad['codConsucode'] }}')"
                                class="w-full px-4 py-2.5 text-left hover:bg-primary-50 transition-colors border-b border-neutral-100 last:border-b-0"
                            >
                                <div class="text-sm font-medium text-neutral-900">{{ $entidad['razonSocial'] }}</div>
                                <div class="text-xs text-neutral-500">Código: {{ $entidad['codConsucode'] }}</div>
                            </button>
                        @endforeach
                    </div>

                    <!-- Indicador de carga externo (solo al seleccionar entidad) -->
                    <div wire:loading wire:target="seleccionarEntidad,limpiarEntidad" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Filtrando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Objeto -->
            <div class="lg:col-span-2">
                <label for="select-objeto" class="block text-xs font-medium mb-1.5 {{ $objetoContrato > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Objeto</label>
                <div class="relative">
                    <select
                        id="select-objeto"
                        wire:model.live="objetoContrato"
                        class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $objetoContrato > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                        <option value="0">Todos</option>
                        @foreach($objetos as $objeto)
                            <option value="{{ $objeto['id'] }}">{{ $objeto['nom'] }}</option>
                        @endforeach
                    </select>
                    <!-- Indicador de carga -->
                    <div wire:loading wire:target="objetoContrato" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Filtrando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado -->
            <div class="lg:col-span-2">
                <label for="select-estado" class="block text-xs font-medium mb-1.5 {{ $estadoContrato > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Estado</label>
                <div class="relative">
                    <select
                        id="select-estado"
                        wire:model.live="estadoContrato"
                        class="w-full px-3 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $estadoContrato > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                        <option value="0">Todos</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado['id'] }}">{{ $estado['nom'] }}</option>
                        @endforeach
                    </select>
                    <!-- Indicador de carga -->
                    <div wire:loading wire:target="estadoContrato" class="loading-indicator">
                        <div class="loading-indicator-content">
                            <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Filtrando...</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros Geográficos (Colapsables) -->
        <div class="mt-3" x-data="{ mostrar: @entangle('mostrarFiltrosAvanzados') }">
            <button
                @click="mostrar = !mostrar"
                type="button"
                class="text-xs lg:text-sm text-neutral-600 hover:text-primary-500 font-medium transition-colors flex items-center gap-2"
            >
                <svg
                    class="w-4 h-4 transition-transform duration-200"
                    :class="{ 'rotate-180': mostrar }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <span x-text="mostrar ? 'Ocultar filtros geográficos' : 'Mostrar filtros geográficos'"></span>
            </button>

            <div
                x-show="mostrar"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-4 mt-3 pb-10"
            >
                    <!-- Departamento con búsqueda -->
                    <div x-data="{
                        abierto: false,
                        busqueda: '',
                        departamentos: @js($departamentos),
                        opcionesFiltradas() {
                            if (!this.busqueda) return this.departamentos;
                            return this.departamentos.filter(d =>
                                d.nom.toLowerCase().includes(this.busqueda.toLowerCase())
                            );
                        },
                        seleccionar(id, nombre) {
                            $wire.set('departamento', id);
                            this.busqueda = '';
                            this.abierto = false;
                        }
                    }"
                    x-init="$watch('$wire.departamento', () => abierto = false)"
                    @click.away="abierto = false"
                    class="relative"
                    wire:key="filter-departamento">
                        <label class="block text-xs font-medium mb-1.5 {{ $departamento > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Departamento</label>
                        <button
                            type="button"
                            @click="abierto = !abierto"
                            class="w-full px-3 py-2.5 rounded-xl text-sm text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $departamento > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                        >
                            <span class="truncate">{{ $departamento === 0 ? 'Todos' : collect($departamentos)->firstWhere('id', $departamento)['nom'] ?? 'Todos' }}</span>
                            <svg class="w-4 h-4 {{ $departamento > 0 ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="abierto" x-transition class="absolute z-50 w-full mt-1 bg-white border border-neutral-200 rounded-xl shadow-lg max-h-64 overflow-hidden">
                            <div class="p-2 border-b border-neutral-100">
                                <input
                                    type="text"
                                    x-model="busqueda"
                                    placeholder="Buscar departamento..."
                                    class="w-full px-3 py-2 bg-neutral-50 border border-neutral-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                            </div>
                            <div class="overflow-y-auto max-h-52">
                                <button type="button" @click="seleccionar(0, 'Todos')" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $departamento === 0 ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700' }}">
                                    Todos
                                </button>
                                <template x-for="depto in opcionesFiltradas()" :key="depto.id">
                                    <button type="button" @click="seleccionar(depto.id, depto.nom)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($departamento) === depto.id ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700'" x-text="depto.nom"></button>
                                </template>
                            </div>
                        </div>
                        <!-- Indicador de carga -->
                        <div wire:loading wire:target="departamento" class="loading-indicator">
                            <div class="loading-indicator-content">
                                <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Filtrando...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Provincia con búsqueda -->
                    <div x-data="{
                        abierto: false,
                        busqueda: '',
                        provincias: @js($provincias),
                        opcionesFiltradas() {
                            if (!this.busqueda) return this.provincias;
                            return this.provincias.filter(p =>
                                p.nom.toLowerCase().includes(this.busqueda.toLowerCase())
                            );
                        },
                        seleccionar(id) {
                            $wire.set('provincia', id);
                            this.busqueda = '';
                            this.abierto = false;
                        }
                    }"
                    x-init="
                        $watch('$wire.provincias', (value) => {
                            provincias = value || [];
                            busqueda = '';
                            abierto = false;
                        });
                        $watch('$wire.provincia', () => abierto = false);
                    "
                    @click.away="abierto = false"
                    class="relative"
                    wire:key="filter-provincia-{{ $departamento }}">
                        <label class="block text-xs font-medium mb-1.5 {{ $provincia > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Provincia</label>
                        <button
                            type="button"
                            @click="{{ $departamento === 0 ? '' : 'abierto = !abierto' }}"
                            class="w-full px-3 py-2.5 rounded-xl text-sm text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $departamento === 0 ? 'opacity-50 cursor-not-allowed bg-neutral-50 border border-neutral-100' : ($provincia > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100') }}"
                            {{ $departamento === 0 ? 'disabled' : '' }}
                        >
                            <span class="truncate">
                                @if($departamento === 0)
                                    Selecciona departamento
                                @elseif($provincia === 0)
                                    Todas
                                @else
                                    {{ collect($provincias)->firstWhere('id', $provincia)['nom'] ?? 'Todas' }}
                                @endif
                            </span>
                            <svg class="w-4 h-4 {{ $provincia > 0 ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="abierto" x-transition class="absolute z-50 w-full mt-1 bg-white border border-neutral-200 rounded-xl shadow-lg max-h-64 overflow-hidden">
                            <div class="p-2 border-b border-neutral-100">
                                <input
                                    type="text"
                                    x-model="busqueda"
                                    placeholder="Buscar provincia..."
                                    class="w-full px-3 py-2 bg-neutral-50 border border-neutral-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                            </div>
                            <div class="overflow-y-auto max-h-52">
                                <button type="button" @click="seleccionar(0)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $provincia === 0 ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700' }}">
                                    Todas
                                </button>
                                <template x-for="prov in opcionesFiltradas()" :key="prov.id">
                                    <button type="button" @click="seleccionar(prov.id)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($provincia) === prov.id ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700'" x-text="prov.nom"></button>
                                </template>
                            </div>
                        </div>
                        <!-- Indicador de carga -->
                        <div wire:loading wire:target="provincia" class="loading-indicator">
                            <div class="loading-indicator-content">
                                <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Filtrando...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Distrito con búsqueda -->
                    <div x-data="{
                        abierto: false,
                        busqueda: '',
                        distritos: @js($distritos),
                        opcionesFiltradas() {
                            if (!this.busqueda) return this.distritos;
                            return this.distritos.filter(d =>
                                d.nom.toLowerCase().includes(this.busqueda.toLowerCase())
                            );
                        },
                        seleccionar(id) {
                            $wire.set('distrito', id);
                            this.busqueda = '';
                            this.abierto = false;
                        }
                    }"
                    x-init="
                        $watch('$wire.distritos', (value) => {
                            distritos = value || [];
                            busqueda = '';
                            abierto = false;
                        });
                        $watch('$wire.distrito', () => abierto = false);
                    "
                    @click.away="abierto = false"
                    class="relative"
                    wire:key="filter-distrito-{{ $provincia }}">
                        <label class="block text-xs font-medium mb-1.5 {{ $distrito > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }} transition-colors">Distrito</label>
                        <button
                            type="button"
                            @click="{{ $provincia === 0 ? '' : 'abierto = !abierto' }}"
                            class="w-full px-3 py-2.5 rounded-xl text-sm text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $provincia === 0 ? 'opacity-50 cursor-not-allowed bg-neutral-50 border border-neutral-100' : ($distrito > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100') }}"
                            {{ $provincia === 0 ? 'disabled' : '' }}
                        >
                            <span class="truncate">
                                @if($provincia === 0)
                                    Selecciona provincia
                                @elseif($distrito === 0)
                                    Todos
                                @else
                                    {{ collect($distritos)->firstWhere('id', $distrito)['nom'] ?? 'Todos' }}
                                @endif
                            </span>
                            <svg class="w-4 h-4 {{ $distrito > 0 ? 'text-brand-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="abierto" x-transition class="absolute z-50 w-full mt-1 bg-white border border-neutral-200 rounded-xl shadow-lg max-h-64 overflow-hidden">
                            <div class="p-2 border-b border-neutral-100">
                                <input
                                    type="text"
                                    x-model="busqueda"
                                    placeholder="Buscar distrito..."
                                    class="w-full px-3 py-2 bg-neutral-50 border border-neutral-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                >
                            </div>
                            <div class="overflow-y-auto max-h-52">
                                <button type="button" @click="seleccionar(0)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $distrito === 0 ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700' }}">
                                    Todos
                                </button>
                                <template x-for="dist in opcionesFiltradas()" :key="dist.id">
                                    <button type="button" @click="seleccionar(dist.id)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($distrito) === dist.id ? 'bg-primary-50 text-brand-600 font-medium' : 'text-neutral-700'" x-text="dist.nom"></button>
                                </template>
                            </div>
                        </div>
                        <!-- Indicador de carga -->
                        <div wire:loading wire:target="distrito" class="loading-indicator">
                            <div class="loading-indicator-content">
                                <svg class="animate-spin h-3 w-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Filtrando...</span>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- Mensaje de Error -->
    @if($errorMensaje)
        <div class="bg-red-50 border-2 border-red-300 rounded-3xl p-5 shadow-soft">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-red-900 mb-1">Error al buscar</h3>
                    <p class="text-sm text-red-700 mb-3">{{ $errorMensaje }}</p>

                    <div class="p-3 bg-white rounded-xl border border-red-200">
                        <p class="text-xs font-semibold text-red-900 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Sugerencias:
                        </p>
                        <ul class="text-xs text-red-700 space-y-1.5 ml-5">
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>Verifica tu conexión a internet</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>Intenta con otro año o departamento</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>Reduce los filtros aplicados</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 mt-0.5">•</span>
                                <span>Recarga la página (F5)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($tdrNotificacion)
        @php
            $type = $tdrNotificacion['type'] ?? 'info';
            $styles = match($type) {
                'success' => 'bg-secondary-500/10 border-secondary-300 text-secondary-700',
                'warning' => 'bg-primary-50 border-primary-200 text-primary-700',
                'error' => 'bg-neutral-900 border-neutral-900 text-white',
                default => 'bg-neutral-50 border-neutral-200 text-neutral-700',
            };
            $closeClasses = $type === 'error'
                ? 'text-white/80 hover:text-white'
                : 'text-neutral-500 hover:text-neutral-900';
        @endphp
        <div class="rounded-2xl border {{ $styles }} px-5 py-4 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold">{{ $tdrNotificacion['message'] ?? '' }}</p>
                <p class="text-xs opacity-70">Actualizado {{ $tdrNotificacion['time'] ?? '' }}</p>
                @php
                    $reporteContratoId = $tdrNotificacion['contrato_id'] ?? null;
                    $reporteArchivoId = $tdrNotificacion['archivo_id'] ?? null;
                @endphp
                @if($reporteContratoId && $reporteArchivoId)
                    <div class="mt-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        <div class="flex items-start gap-2">
                            <div class="w-8 h-8 rounded-full bg-primary-500/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9.75c.549-1.31 2.131-2.25 3.772-2.25 2.18 0 3.75 1.342 3.75 3 0 1.03-.668 1.958-1.726 2.493-.69.35-1.274.996-1.274 1.757v.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-neutral-900">¿El archivo descargado NO ES EL TDR?, REPORTALO</p>
                                <p class="text-[11px] text-neutral-500">Ayudanos a corregir el repositorio local.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="reportarArchivoNoTdr({{ $reporteContratoId }}, {{ $reporteArchivoId }})"
                                class="px-4 py-2 text-xs font-semibold rounded-full border border-primary-200 text-primary-500 hover:bg-primary-500/10 transition-colors"
                            >
                                Reportar
                            </button>
                            @if(auth()->user()?->hasRole('admin'))
                                <button
                                    type="button"
                                    wire:click="redescargarTdr({{ $reporteContratoId }})"
                                    class="px-4 py-2 text-xs font-semibold rounded-full bg-neutral-900 text-white hover:bg-neutral-600 transition-colors"
                                >
                                    Re-descargar TDR
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <button
                wire:click="$set('tdrNotificacion', null)"
                class="text-xs font-semibold transition-colors {{ $closeClasses }}"
            >
                Cerrar
            </button>
        </div>
    @endif

    <!-- Resultados -->
    @if(count($resultados) > 0)
        @php
            $puedeAnalizar = auth()->user()?->hasPermission('analyze-tdr') ?? false;
            $puedeSeguimiento = auth()->user()?->hasPermission('follow-contracts') ?? false;
            $puedeCotizarPermiso = auth()->user()?->hasPermission('cotizar-seace') ?? false;
        @endphp
        <!-- Tabla de Resultados -->
        <div class="bg-white rounded-3xl shadow-soft overflow-hidden border border-neutral-200 w-full min-w-0">
            <!-- Header Compacto -->
            <div class="px-4 lg:px-6 py-4 border-b border-neutral-100 flex items-center justify-end gap-3">
                <div class="flex items-center gap-2">
                    <label for="select-por-pagina" class="text-xs text-neutral-600 hidden sm:inline">Mostrar:</label>
                    <select
                        id="select-por-pagina"
                        wire:model.live="registrosPorPagina"
                        class="px-2.5 py-1.5 bg-neutral-50 border border-neutral-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <!-- Tabla Desktop -->
            <div class="hidden lg:block overflow-x-auto px-6 py-4">
                <table class="w-full">
                    <thead class="bg-neutral-100 border-b border-neutral-200">
                        <tr>
                            <th class="px-4 lg:px-6 py-3.5 text-left">
                                <button
                                    wire:click="cambiarOrden('codigo')"
                                    class="group flex items-center gap-1.5 text-xs font-semibold text-neutral-800 uppercase tracking-wider hover:text-primary-500 transition-colors"
                                >
                                    <span>Código</span>
                                    <svg class="w-4 h-4 {{ $ordenarPor === 'codigo' ? 'text-primary-500' : 'text-neutral-400 opacity-0 group-hover:opacity-100' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($ordenarPor === 'codigo' && $direccionOrden === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                </button>
                            </th>
                            <th class="px-4 lg:px-6 py-3.5 text-left">
                                <button
                                    wire:click="cambiarOrden('entidad')"
                                    class="group flex items-center gap-1.5 text-xs font-semibold text-neutral-800 uppercase tracking-wider hover:text-primary-500 transition-colors"
                                >
                                    <span>Entidad</span>
                                    <svg class="w-4 h-4 {{ $ordenarPor === 'entidad' ? 'text-primary-500' : 'text-neutral-400 opacity-0 group-hover:opacity-100' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($ordenarPor === 'entidad' && $direccionOrden === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                </button>
                            </th>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Objeto</th>
                            <th class="px-4 lg:px-6 py-3.5 text-left">
                                <button
                                    wire:click="cambiarOrden('estado')"
                                    class="group flex items-center gap-1.5 text-xs font-semibold text-neutral-800 uppercase tracking-wider hover:text-primary-500 transition-colors"
                                >
                                    <span>Estado</span>
                                    <svg class="w-4 h-4 {{ $ordenarPor === 'estado' ? 'text-primary-500' : 'text-neutral-400 opacity-0 group-hover:opacity-100' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($ordenarPor === 'estado' && $direccionOrden === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                </button>
                            </th>
                            <th class="px-4 lg:px-6 py-3.5 text-left">
                                <button
                                    wire:click="cambiarOrden('fecha_publicacion')"
                                    class="group flex items-center gap-1.5 text-xs font-semibold text-neutral-800 uppercase tracking-wider hover:text-primary-500 transition-colors"
                                >
                                    <span>Publicación</span>
                                    <svg class="w-4 h-4 {{ $ordenarPor === 'fecha_publicacion' ? 'text-primary-500' : 'text-neutral-400 opacity-0 group-hover:opacity-100' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($ordenarPor === 'fecha_publicacion' && $direccionOrden === 'asc')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        @endif
                                    </svg>
                                </button>
                            </th>
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-100">
                        @foreach($resultados as $index => $contrato)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-neutral-50/50' }} hover:bg-neutral-100/70 transition-colors">
                                <td class="px-4 lg:px-6 py-3.5">
                                    <div class="text-sm font-semibold text-neutral-900">{{ $contrato['desContratacion'] ?? 'N/A' }}</div>
                                    <div class="text-xs text-neutral-500 mt-0.5">N° {{ $contrato['nroContratacion'] ?? '-' }}</div>
                                </td>
                                <td class="px-4 lg:px-6 py-3.5">
                                    <div
                                        class="text-sm text-neutral-900 font-medium max-w-xs truncate cursor-help"
                                        @mouseenter="showTooltip(@js($contrato['nomEntidad'] ?? 'N/A'), $event)"
                                        @mouseleave="hideTooltip()"
                                        @mousemove="moveTooltip($event)"
                                    >
                                        {{ \Illuminate\Support\Str::limit($contrato['nomEntidad'] ?? 'N/A', 50) }}
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-3.5">
                                    <div class="text-sm font-medium text-neutral-900">{{ $contrato['nomObjetoContrato'] ?? 'N/A' }}</div>
                                    <div
                                        class="text-xs text-neutral-500 max-w-md truncate mt-0.5 cursor-help"
                                        @mouseenter="showTooltip(@js($contrato['desObjetoContrato'] ?? '-'), $event)"
                                        @mouseleave="hideTooltip()"
                                        @mousemove="moveTooltip($event)"
                                    >
                                        {{ $contrato['desObjetoContrato'] ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-3.5">
                                    @php
                                        $estado = strtolower($contrato['nomEstadoContrato'] ?? '');
                                        $badgeClass = match(true) {
                                            str_contains($estado, 'vigente') => 'bg-secondary-500/10 border-secondary-500/40 text-primary-500',
                                            str_contains($estado, 'evaluación') => 'bg-yellow-50/80 border-yellow-400/40 text-yellow-800',
                                            str_contains($estado, 'culminado') => 'bg-neutral-100/80 border-neutral-400/40 text-neutral-600',
                                            default => 'bg-neutral-100/80 border-neutral-400/40 text-neutral-600',
                                        };
                                    @endphp
                                    <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full border {{ $badgeClass }}">
                                        {{ $contrato['nomEstadoContrato'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 lg:px-6 py-3.5">
                                    <div
                                        class="text-sm font-medium text-neutral-900 cursor-help"
                                        title="{{ $contrato['fecPublica_completa'] ?? $contrato['fecPublica'] ?? 'N/A' }}"
                                    >
                                        {{ $contrato['fecPublica_amigable'] ?? $contrato['fecPublica'] ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 lg:px-6 py-3.5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        {{-- Seguimiento --}}
                                        <button
                                            wire:click="hacerSeguimiento({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-neutral-200 text-neutral-600 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50 disabled:cursor-wait {{ $puedeSeguimiento ? '' : 'opacity-70' }}"
                                            title="Hacer seguimiento"
                                            @mouseenter="showTooltip('Hacer seguimiento', $event)"
                                            @mouseleave="hideTooltip()"
                                            @mousemove="moveTooltip($event)"
                                        >
                                            <svg wire:loading.remove wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <svg wire:loading wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        </button>
                                        {{-- Ver --}}
                                        <button
                                            wire:click="verContrato({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="verContrato({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-primary-500 hover:bg-primary-400 transition-colors disabled:opacity-50 disabled:cursor-wait"
                                            title="Ver detalle del proceso"
                                        >
                                            <svg wire:loading.remove wire:target="verContrato({{ $contrato['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg wire:loading wire:target="verContrato({{ $contrato['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            <span class="hidden xl:inline">Ver</span>
                                        </button>
                                        {{-- Descargar --}}
                                        <button
                                            wire:click="descargarTdr({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="descargarTdr({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50 disabled:cursor-wait"
                                            title="Descargar TDR"
                                        >
                                            <svg wire:loading.remove wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11" />
                                            </svg>
                                            <svg wire:loading wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            <span wire:loading.remove wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="hidden xl:inline">Descargar</span>
                                            <span wire:loading wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="hidden xl:inline">Descargando...</span>
                                        </button>
                                        {{-- Analizar --}}
                                        <button
                                            wire:click="analizarTdr({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="analizarTdr({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-primary-500/40 text-xs font-semibold text-primary-500 bg-primary-500/10 hover:bg-primary-500/20 transition-colors disabled:opacity-50 disabled:cursor-wait {{ $puedeAnalizar ? '' : 'opacity-70' }}"
                                            title="Analizar con IA"
                                        >
                                            <svg wire:loading.remove wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" />
                                            </svg>
                                            <svg wire:loading wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            <span wire:loading.remove wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="hidden xl:inline">Analizar</span>
                                            <span wire:loading wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="hidden xl:inline">Analizando...</span>
                                        </button>
                                        {{-- Cotizar --}}
                                        @if($contrato['cotizar'] ?? false)
                                            <button
                                                wire:click="cotizarEnSeace({{ $contrato['idContrato'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="cotizarEnSeace({{ $contrato['idContrato'] }})"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-secondary-500 text-white text-xs font-semibold hover:bg-secondary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-wait {{ $puedeCotizarPermiso ? '' : 'opacity-70' }}"
                                                title="Cotizar en el portal SEACE"
                                            >
                                                <svg wire:loading.remove wire:target="cotizarEnSeace({{ $contrato['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <svg wire:loading wire:target="cotizarEnSeace({{ $contrato['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                <span wire:loading.remove wire:target="cotizarEnSeace({{ $contrato['idContrato'] }})" class="hidden xl:inline">Cotizar</span>
                                                <span wire:loading wire:target="cotizarEnSeace({{ $contrato['idContrato'] }})" class="hidden xl:inline">Preparando...</span>
                                            </button>
                                        @endif
                                    </div>
                                    @if(isset($archivosErrores[$contrato['idContrato']]))
                                        <div class="text-[11px] text-primary-700 mt-2">
                                            {{ $archivosErrores[$contrato['idContrato']] }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile/Tablet -->
            <div class="lg:hidden px-4 py-4 space-y-3">
                @foreach($resultados as $index => $contrato)
                    <div class="border border-neutral-200 rounded-2xl p-4 space-y-3 {{ $index % 2 === 0 ? 'bg-white' : 'bg-neutral-50/50' }}">
                        {{-- Fila superior: Código + Estado --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-neutral-900 truncate">{{ $contrato['desContratacion'] ?? 'N/A' }}</p>
                                <p class="text-xs text-neutral-500 mt-0.5">N° {{ $contrato['nroContratacion'] ?? '-' }}</p>
                            </div>
                            @php
                                $estadoMobile = strtolower($contrato['nomEstadoContrato'] ?? '');
                                $badgeMobileClass = match(true) {
                                    str_contains($estadoMobile, 'vigente') => 'bg-secondary-500/10 border-secondary-500/40 text-primary-500',
                                    str_contains($estadoMobile, 'evaluación') => 'bg-yellow-50/80 border-yellow-400/40 text-yellow-800',
                                    str_contains($estadoMobile, 'culminado') => 'bg-neutral-100/80 border-neutral-400/40 text-neutral-600',
                                    default => 'bg-neutral-100/80 border-neutral-400/40 text-neutral-600',
                                };
                            @endphp
                            <span class="inline-flex px-2.5 py-1 text-[11px] font-semibold rounded-full border shrink-0 {{ $badgeMobileClass }}">
                                {{ $contrato['nomEstadoContrato'] ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Entidad --}}
                        <div>
                            <p class="text-[11px] text-neutral-500 font-semibold uppercase tracking-wider">Entidad</p>
                            <p class="text-sm text-neutral-900 font-medium leading-snug">{{ $contrato['nomEntidad'] ?? 'N/A' }}</p>
                        </div>

                        {{-- Objeto --}}
                        <div>
                            <p class="text-[11px] text-neutral-500 font-semibold uppercase tracking-wider">Objeto</p>
                            <p class="text-sm text-neutral-900 leading-snug">
                                <span class="font-medium">{{ $contrato['nomObjetoContrato'] ?? 'N/A' }}</span>
                                <span class="text-neutral-500"> — {{ \Illuminate\Support\Str::limit($contrato['desObjetoContrato'] ?? '-', 120) }}</span>
                            </p>
                        </div>

                        {{-- Fecha publicación --}}
                        <div class="flex items-center gap-1.5 text-xs text-neutral-500">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span title="{{ $contrato['fecPublica_completa'] ?? $contrato['fecPublica'] ?? '' }}">
                                {{ $contrato['fecPublica_amigable'] ?? $contrato['fecPublica'] ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-neutral-100">
                            {{-- Seguimiento --}}
                            <button
                                wire:click="hacerSeguimiento({{ $contrato['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-neutral-200 text-neutral-600 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50 {{ $puedeSeguimiento ? '' : 'opacity-70' }}"
                                title="Hacer seguimiento"
                            >
                                <svg wire:loading.remove wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg wire:loading wire:target="hacerSeguimiento({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </button>
                            {{-- Ver --}}
                            <button
                                wire:click="verContrato({{ $contrato['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="verContrato({{ $contrato['idContrato'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-primary-500 hover:bg-primary-400 transition-colors disabled:opacity-50"
                                title="Ver detalle"
                            >
                                <svg wire:loading.remove wire:target="verContrato({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg wire:loading wire:target="verContrato({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span>Ver</span>
                            </button>
                            {{-- Descargar --}}
                            <button
                                wire:click="descargarTdr({{ $contrato['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="descargarTdr({{ $contrato['idContrato'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50"
                                title="Descargar TDR"
                            >
                                <svg wire:loading.remove wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/>
                                </svg>
                                <svg wire:loading wire:target="descargarTdr({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span wire:loading.remove wire:target="descargarTdr({{ $contrato['idContrato'] }})">TDR</span>
                                <span wire:loading wire:target="descargarTdr({{ $contrato['idContrato'] }})">...</span>
                            </button>
                            {{-- Analizar --}}
                            <button
                                wire:click="analizarTdr({{ $contrato['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="analizarTdr({{ $contrato['idContrato'] }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-primary-500/40 text-xs font-semibold text-primary-500 bg-primary-500/10 hover:bg-primary-500/20 transition-colors disabled:opacity-50 {{ $puedeAnalizar ? '' : 'opacity-70' }}"
                                title="Analizar con IA"
                            >
                                <svg wire:loading.remove wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
                                </svg>
                                <svg wire:loading wire:target="analizarTdr({{ $contrato['idContrato'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span wire:loading.remove wire:target="analizarTdr({{ $contrato['idContrato'] }})">IA</span>
                                <span wire:loading wire:target="analizarTdr({{ $contrato['idContrato'] }})">...</span>
                            </button>
                        </div>
                        @if(isset($archivosErrores[$contrato['idContrato']]))
                            <div class="text-[11px] text-primary-700">
                                {{ $archivosErrores[$contrato['idContrato']] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Paginación -->
            @if(($paginacion['total_pages'] ?? 1) > 1)
                <div class="px-4 lg:px-6 py-5 bg-gradient-to-br from-neutral-50 to-neutral-100/50 border-t border-neutral-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs font-medium text-neutral-700">
                            Mostrando <span class="font-bold text-neutral-900">{{ ($pagina - 1) * $registrosPorPagina + 1 }}</span> a <span class="font-bold text-neutral-900">{{ min($pagina * $registrosPorPagina, $paginacion['total_elements']) }}</span> de <span class="font-bold text-neutral-900">{{ number_format($paginacion['total_elements']) }}</span>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <!-- Primera -->
                            <button
                                wire:click="irAPagina(1)"
                                class="w-10 h-10 flex items-center justify-center text-sm font-semibold rounded-xl transition-all {{ $pagina === 1 ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md' }}"
                                {{ $pagina === 1 ? 'disabled' : '' }}
                                aria-label="Primera página"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <!-- Anterior -->
                            <button
                                wire:click="irAPagina({{ $pagina - 1 }})"
                                class="px-4 py-2.5 text-sm font-semibold rounded-xl transition-all {{ $pagina === 1 ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md' }}"
                                {{ $pagina === 1 ? 'disabled' : '' }}
                                aria-label="Página anterior"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <!-- Páginas -->
                            @php
                                $totalPages = $paginacion['total_pages'];
                                $currentPage = $pagina;
                                $range = 2;
                                $start = max(1, $currentPage - $range);
                                $end = min($totalPages, $currentPage + $range);
                            @endphp

                            @foreach(range($start, $end) as $i)
                                <button
                                    wire:click="irAPagina({{ $i }})"
                                    class="w-10 h-10 text-sm font-bold rounded-xl transition-all {{ $i === $currentPage ? 'bg-primary-500 text-white shadow-lg scale-110 ring-2 ring-primary-300' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-50 hover:border-primary-500 hover:text-brand-600 shadow-sm' }}"
                                >
                                    {{ $i }}
                                </button>
                            @endforeach

                            <!-- Siguiente -->
                            <button
                                wire:click="irAPagina({{ $pagina + 1 }})"
                                class="px-4 py-2.5 text-sm font-semibold rounded-xl transition-all {{ $pagina === $totalPages ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md' }}"
                                {{ $pagina === $totalPages ? 'disabled' : '' }}
                                aria-label="Página siguiente"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>

                            <!-- Última -->
                            <button
                                wire:click="irAPagina({{ $totalPages }})"
                                class="w-10 h-10 flex items-center justify-center text-sm font-semibold rounded-xl transition-all {{ $pagina === $totalPages ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md' }}"
                                {{ $pagina === $totalPages ? 'disabled' : '' }}
                                aria-label="Última página"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal: Ver detalle del contrato --}}
        @if($contratoDetalle)
            @php
                $det = $contratoDetalle;
                $estadoDet = strtolower($det['nomEstadoContrato'] ?? '');
                $badgeDetalle = match(true) {
                    str_contains($estadoDet, 'vigente') => 'bg-secondary-500/10 border-secondary-500/40 text-primary-500',
                    str_contains($estadoDet, 'evaluación') => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                    str_contains($estadoDet, 'culminado') => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                    default => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                };
                $puedeCotizar = $det['cotizar'] ?? false;
            @endphp
            <div
                class="fixed inset-0 z-[120] flex items-center justify-center px-4 py-8"
                x-data
                x-on:keydown.escape.window="$wire.call('cerrarDetalle')"
            >
                <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarDetalle"></div>

                <div class="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[85vh]">
                    {{-- Header --}}
                    <div class="p-6 lg:p-8 flex items-start justify-between gap-4 bg-white border-b border-neutral-100 rounded-t-[2rem]">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em] mb-1">Detalle del proceso</p>
                            <h3 class="text-xl lg:text-2xl font-bold text-neutral-900 break-words">{{ $det['desContratacion'] ?? 'N/A' }}</h3>
                            <p class="text-sm text-neutral-500 mt-1">N° {{ $det['nroContratacion'] ?? '-' }}</p>
                        </div>
                        <button
                            type="button"
                            wire:click="cerrarDetalle"
                            class="flex-shrink-0 px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors"
                        >
                            Cerrar
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">
                        {{-- Entidad y estado --}}
                        <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-5">
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Entidad</dt>
                                    <dd class="text-base font-semibold text-neutral-900 mt-0.5">{{ $det['nomEntidad'] ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <dt class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Objeto de contratación</dt>
                                        <dd class="text-sm font-medium text-neutral-900 mt-0.5">{{ $det['nomObjetoContrato'] ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Estado</dt>
                                        <dd class="mt-0.5">
                                            <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full border {{ $badgeDetalle }}">
                                                {{ $det['nomEstadoContrato'] ?? 'N/A' }}
                                            </span>
                                        </dd>
                                    </div>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">Descripción</dt>
                                    <dd class="text-sm text-neutral-700 mt-0.5 leading-relaxed">{{ $det['desObjetoContrato'] ?? 'Sin descripción' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Fechas --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="border border-neutral-200 rounded-2xl p-4 text-center">
                                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Publicación</p>
                                <p class="text-sm font-bold text-neutral-900">{{ $det['fecPublica_completa'] ?? $det['fecPublica'] ?? 'N/D' }}</p>
                                @if(!empty($det['fecPublica_amigable']))
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $det['fecPublica_amigable'] }}</p>
                                @endif
                            </div>
                            <div class="border border-neutral-200 rounded-2xl p-4 text-center">
                                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Inicio cotización</p>
                                <p class="text-sm font-bold text-neutral-900">{{ $det['fecIniCotizacion_completa'] ?? $det['fecIniCotizacion'] ?? 'N/D' }}</p>
                                @if(!empty($det['fecIniCotizacion_amigable']))
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $det['fecIniCotizacion_amigable'] }}</p>
                                @endif
                            </div>
                            <div class="border border-neutral-200 rounded-2xl p-4 text-center">
                                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-1">Fin cotización</p>
                                <p class="text-sm font-bold text-neutral-900">{{ $det['fecFinCotizacion_completa'] ?? $det['fecFinCotizacion'] ?? 'N/D' }}</p>
                                @if(!empty($det['fecFinCotizacion_amigable']))
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $det['fecFinCotizacion_amigable'] }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Cotización --}}
                        <div class="flex items-center justify-between border border-neutral-200 rounded-2xl p-4">
                            <div>
                                <p class="text-xs font-semibold text-neutral-400 uppercase tracking-wider">¿Se puede cotizar?</p>
                            </div>
                            @if($puedeCotizar)
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-secondary-500/10 text-secondary-500 border border-secondary-200">
                                        <span class="w-2 h-2 rounded-full bg-secondary-500 animate-pulse"></span>
                                        Sí, abierto
                                    </span>
                                    <button
                                        wire:click="cerrarDetalle(); cotizarEnSeace({{ $det['idContrato'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="cotizarEnSeace({{ $det['idContrato'] }})"
                                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-secondary-500 text-white text-xs font-semibold hover:bg-secondary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-wait"
                                        title="Ir al portal SEACE a cotizar"
                                    >
                                        <svg wire:loading.remove wire:target="cotizarEnSeace({{ $det['idContrato'] }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <svg wire:loading wire:target="cotizarEnSeace({{ $det['idContrato'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <span wire:loading.remove wire:target="cotizarEnSeace({{ $det['idContrato'] }})">Cotizar ahora</span>
                                        <span wire:loading wire:target="cotizarEnSeace({{ $det['idContrato'] }})">Preparando...</span>
                                    </button>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-neutral-100 text-neutral-500 border border-neutral-200">
                                    <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                                    No disponible
                                </span>
                            @endif
                        </div>

                        {{-- Acciones rápidas --}}
                        <div class="flex flex-wrap gap-3 pt-2">
                            <button
                                wire:click="cerrarDetalle(); hacerSeguimiento({{ $det['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="hacerSeguimiento({{ $det['idContrato'] }})"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50 disabled:cursor-wait {{ $puedeSeguimiento ? '' : 'opacity-70' }}"
                            >
                                <svg wire:loading.remove wire:target="hacerSeguimiento({{ $det['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg wire:loading wire:target="hacerSeguimiento({{ $det['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span wire:loading.remove wire:target="hacerSeguimiento({{ $det['idContrato'] }})">Hacer seguimiento</span>
                                <span wire:loading wire:target="hacerSeguimiento({{ $det['idContrato'] }})">Guardando...</span>
                            </button>
                            <button
                                wire:click="cerrarDetalle(); descargarTdr({{ $det['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="descargarTdr({{ $det['idContrato'] }})"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-700 hover:text-brand-600 hover:border-primary-400 transition-colors disabled:opacity-50 disabled:cursor-wait"
                            >
                                <svg wire:loading.remove wire:target="descargarTdr({{ $det['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/>
                                </svg>
                                <svg wire:loading wire:target="descargarTdr({{ $det['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span wire:loading.remove wire:target="descargarTdr({{ $det['idContrato'] }})">Descargar TDR</span>
                                <span wire:loading wire:target="descargarTdr({{ $det['idContrato'] }})">Descargando...</span>
                            </button>
                            <button
                                wire:click="cerrarDetalle(); analizarTdr({{ $det['idContrato'] }})"
                                wire:loading.attr="disabled"
                                wire:target="analizarTdr({{ $det['idContrato'] }})"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-wait {{ $puedeAnalizar ? '' : 'opacity-70' }}"
                            >
                                <svg wire:loading.remove wire:target="analizarTdr({{ $det['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
                                </svg>
                                <svg wire:loading wire:target="analizarTdr({{ $det['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span wire:loading.remove wire:target="analizarTdr({{ $det['idContrato'] }})">Analizar con IA</span>
                                <span wire:loading wire:target="analizarTdr({{ $det['idContrato'] }})">Analizando...</span>
                            </button>
                            @auth
                                @can('create-proforma')
                                    <button
                                        wire:click="cerrarDetalle(); generarProformaTecnica({{ $det['idContrato'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="generarProformaTecnica({{ $det['idContrato'] }})"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-amber-500 text-white text-sm font-semibold hover:bg-amber-400 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-wait"
                                    >
                                        <svg wire:loading.remove wire:target="generarProformaTecnica({{ $det['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <svg wire:loading wire:target="generarProformaTecnica({{ $det['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <span wire:loading.remove wire:target="generarProformaTecnica({{ $det['idContrato'] }})">📋 Crear Proforma</span>
                                        <span wire:loading wire:target="generarProformaTecnica({{ $det['idContrato'] }})">Generando...</span>
                                    </button>
                                @endcan
                            @endauth
                            @if($puedeCotizar)
                                <button
                                    wire:click="cerrarDetalle(); cotizarEnSeace({{ $det['idContrato'] }})"
                                    wire:loading.attr="disabled"
                                    wire:target="cotizarEnSeace({{ $det['idContrato'] }})"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-secondary-500 text-white text-sm font-semibold hover:bg-secondary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-wait"
                                >
                                    <svg wire:loading.remove wire:target="cotizarEnSeace({{ $det['idContrato'] }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <svg wire:loading wire:target="cotizarEnSeace({{ $det['idContrato'] }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span wire:loading.remove wire:target="cotizarEnSeace({{ $det['idContrato'] }})">Cotizar en SEACE</span>
                                    <span wire:loading wire:target="cotizarEnSeace({{ $det['idContrato'] }})">Preparando...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($resultadoAnalisis)
            @php
                $analisisData = $resultadoAnalisis['analisis'] ?? [];
                $isCached = $resultadoAnalisis['cache'] ?? false;
                $resumenEjecutivo = $analisisData['resumen_ejecutivo'] ?? null;

                $normalizeList = function ($value) {
                    if (is_array($value)) {
                        return array_values(array_filter($value, fn ($item) => filled($item)));
                    }

                    if (is_string($value) && trim($value) !== '') {
                        $parts = preg_split('/[\r\n]+/', $value);
                        $parts = array_map('trim', $parts);

                        return array_values(array_filter($parts, fn ($item) => filled($item)));
                    }

                    return [];
                };

                $requisitosList = $normalizeList($analisisData['requisitos_tecnicos'] ?? $analisisData['requisitos_calificacion'] ?? []);
                $reglasList = $normalizeList($analisisData['reglas_de_negocio'] ?? $analisisData['reglas_ejecucion'] ?? []);
                $penalidadesList = $normalizeList($analisisData['politicas_y_penalidades'] ?? $analisisData['penalidades'] ?? []);
                $timestampAnalisis = $resultadoAnalisis['timestamp'] ?? null;
                $montoReferencial = $analisisData['monto_referencial'] ?? $analisisData['monto'] ?? null;
                $suscriptoresUsuario = $suscriptoresUsuario ?? [];
                $compatibilidadPorSuscriptor = $compatibilidadPorSuscriptor ?? [];
            @endphp
            <div
                class="fixed inset-0 z-[120] flex items-center justify-center px-4 py-8"
                x-data
                x-on:keydown.escape.window="$wire.call('limpiarAnalisis')"
            >
                <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="limpiarAnalisis"></div>

                <div class="relative w-full max-w-5xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[92vh]">
                    {{-- Header con acento visual --}}
                    <div class="sticky top-0 z-10 bg-white border-b border-neutral-100 rounded-t-[2rem]">
                        <div class="w-full border-t border-primary-200"></div>
                        <div class="p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-primary-500/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-xs font-semibold uppercase text-primary-500 tracking-[0.2em]">Análisis IA del TDR</p>
                                        @if($isCached)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-secondary-500/10 text-secondary-500 border border-secondary-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-secondary-500 animate-pulse"></span>
                                                Caché
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-xl lg:text-2xl font-bold text-neutral-900">{{ $analisisContrato['codigo'] ?? 'Proceso seleccionado' }}</h3>
                                    <p class="text-sm text-neutral-600 mt-0.5">{{ $analisisContrato['entidad'] ?? 'Entidad no disponible' }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="limpiarAnalisis"
                                class="flex-shrink-0 w-10 h-10 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Body con scroll --}}
                    <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

                        {{-- Resumen Ejecutivo - destacado --}}
                        @if($resumenEjecutivo)
                            <div class="relative bg-gradient-to-br from-primary-500/5 to-secondary-500/5 border border-primary-200/50 rounded-2xl p-5 lg:p-6">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-xl bg-primary-500/10 flex items-center justify-center mt-0.5">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-primary-500 uppercase tracking-[0.2em] mb-2">Resumen ejecutivo</p>
                                        <p class="text-sm lg:text-base text-neutral-800 leading-relaxed">{{ $resumenEjecutivo }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Grid principal: Contexto + Fechas + Compatibilidad --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                            {{-- Contexto del proceso --}}
                            <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-neutral-900">Contexto del proceso</h4>
                                </div>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Entidad</dt>
                                        <dd class="text-sm font-medium text-neutral-900 mt-0.5">{{ $analisisContrato['entidad'] ?? 'No disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Objeto</dt>
                                        <dd class="text-sm text-neutral-700 mt-0.5">{{ $analisisContrato['objeto'] ?? 'No disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Estado</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full bg-secondary-500/10 text-secondary-500 border border-secondary-200">
                                                {{ $analisisContrato['estado'] ?? 'No disponible' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] font-semibold text-neutral-400 uppercase tracking-wider">Archivo analizado</dt>
                                        <dd class="text-xs text-neutral-600 mt-0.5 break-all">{{ $analisisContrato['archivo'] ?? 'N/D' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Fechas clave --}}
                            <div class="border border-neutral-200 rounded-2xl p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-7 h-7 rounded-lg bg-secondary-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <h4 class="text-sm font-bold text-neutral-900">Fechas clave</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                        <span class="text-xs text-neutral-500">Publicación</span>
                                        <p class="text-xs font-semibold text-neutral-900 text-right max-w-[60%]">{{ $analisisContrato['fecha_publicacion'] ?? 'N/D' }}</p>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                        <span class="text-xs text-neutral-500">Cierre cotización</span>
                                        <p class="text-xs font-semibold text-neutral-900 text-right max-w-[60%]">{{ $analisisContrato['fecha_cierre'] ?? 'N/D' }}</p>
                                    </div>
                                    <div class="flex items-center justify-between py-2 border-b border-neutral-100">
                                        <span class="text-xs text-neutral-500">Etapa actual</span>
                                        <span class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full bg-primary-500/10 text-primary-500">
                                            {{ $analisisContrato['etapa'] ?? 'No disponible' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between py-2">
                                        <span class="text-xs text-neutral-500">Monto referencial</span>
                                        <p class="text-sm font-bold text-neutral-900">{{ $montoReferencial ?? 'N/D' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Compatibilidad (solo auth) --}}
                            @auth
                                <div class="rounded-2xl p-5 bg-white border border-neutral-200 shadow-soft space-y-4">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                        </div>
                                        <h4 class="text-sm font-bold text-neutral-900">Compatibilidad</h4>
                                    </div>

                                    @if(empty($suscriptoresUsuario))
                                        <div class="rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-4 text-center">
                                            <p class="text-xs text-neutral-500">No tienes suscriptores activos asociados a tu cuenta.</p>
                                        </div>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($suscriptoresUsuario as $suscriptor)
                                                @php
                                                    $compat = $compatibilidadPorSuscriptor[$suscriptor['id']] ?? null;
                                                    $score = $compat['score'] ?? null;
                                                    $nivel = $compat['nivel'] ?? null;
                                                    $actualizado = $compat['actualizado'] ?? null;
                                                    $hasCopy = $suscriptor['has_copy'] ?? false;
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-neutral-900 truncate">{{ $suscriptor['label'] }}</p>
                                                        @if(!is_null($score))
                                                            <p class="text-[11px] text-neutral-500">{{ $actualizado ?? '' }}</p>
                                                        @elseif(!$hasCopy)
                                                            <p class="text-[11px] text-neutral-400">Falta copy del suscriptor</p>
                                                        @endif
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        @if(!is_null($score))
                                                            <div class="text-right">
                                                                <p class="text-2xl font-black text-neutral-900 leading-none">{{ number_format($score, 1) }}<span class="text-xs font-semibold text-neutral-400 align-top">/10</span></p>
                                                                @if($nivel)
                                                                    <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-primary-100 text-primary-500">{{ strtoupper($nivel) }}</span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <button
                                                                type="button"
                                                                wire:click="calcularCompatibilidad({{ $suscriptor['id'] }})"
                                                                wire:loading.attr="disabled"
                                                                wire:target="calcularCompatibilidad({{ $suscriptor['id'] }})"
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border border-primary-200 text-primary-500 hover:bg-primary-500/5 transition-colors disabled:opacity-50 disabled:cursor-wait"
                                                                @if(!$hasCopy) disabled @endif
                                                            >
                                                                <svg wire:loading wire:target="calcularCompatibilidad({{ $suscriptor['id'] }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                                <span wire:loading.remove wire:target="calcularCompatibilidad({{ $suscriptor['id'] }})">Score</span>
                                                                <span wire:loading wire:target="calcularCompatibilidad({{ $suscriptor['id'] }})">...</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endauth
                        </div>

                        {{-- Secciones de detalle: Requisitos, Reglas, Penalidades --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {{-- Requisitos técnicos --}}
                            <div class="border border-neutral-200 rounded-2xl p-5 flex flex-col">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-7 h-7 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 flex items-center justify-between">
                                        <h4 class="text-sm font-bold text-neutral-900">Requisitos técnicos</h4>
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-primary-500/10 text-primary-500">{{ count($requisitosList) }}</span>
                                    </div>
                                </div>
                                <ul class="space-y-2.5 flex-1">
                                    @forelse($requisitosList as $item)
                                        <li class="flex gap-2.5">
                                            <span class="mt-2 w-1.5 h-1.5 rounded-full bg-primary-400 flex-shrink-0"></span>
                                            <span class="text-sm leading-relaxed text-neutral-700">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-neutral-400 italic">No se encontraron requisitos específicos.</li>
                                    @endforelse
                                </ul>
                            </div>

                            {{-- Reglas de negocio --}}
                            <div class="border border-neutral-200 rounded-2xl p-5 flex flex-col">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-7 h-7 rounded-lg bg-secondary-500/10 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 flex items-center justify-between">
                                        <h4 class="text-sm font-bold text-neutral-900">Reglas de negocio</h4>
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-secondary-500/10 text-secondary-500">{{ count($reglasList) }}</span>
                                    </div>
                                </div>
                                <ul class="space-y-2.5 flex-1">
                                    @forelse($reglasList as $item)
                                        <li class="flex gap-2.5">
                                            <span class="mt-2 w-1.5 h-1.5 rounded-full bg-secondary-400 flex-shrink-0"></span>
                                            <span class="text-sm leading-relaxed text-neutral-700">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-neutral-400 italic">No se detallaron reglas adicionales.</li>
                                    @endforelse
                                </ul>
                                @if(($analisisData['reglas_ejecucion'] ?? null) && count($reglasList) === 0)
                                    <p class="text-sm text-neutral-600 mt-3 border-t border-neutral-100 pt-3">{{ $analisisData['reglas_ejecucion'] }}</p>
                                @endif
                            </div>

                            {{-- Penalidades --}}
                            <div class="border border-neutral-200 rounded-2xl p-5 flex flex-col">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-7 h-7 rounded-lg bg-primary-300/20 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 flex items-center justify-between">
                                        <h4 class="text-sm font-bold text-neutral-900">Penalidades</h4>
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-primary-300/20 text-primary-400">{{ count($penalidadesList) }}</span>
                                    </div>
                                </div>
                                <ul class="space-y-2.5 flex-1">
                                    @forelse($penalidadesList as $item)
                                        <li class="flex gap-2.5">
                                            <span class="mt-2 w-1.5 h-1.5 rounded-full bg-primary-300 flex-shrink-0"></span>
                                            <span class="text-sm leading-relaxed text-neutral-700">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-neutral-400 italic">No se encontraron penalidades explícitas.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        {{-- Timestamp del análisis --}}
                        @if($timestampAnalisis)
                            <div class="text-center pt-2">
                                <p class="text-[11px] text-neutral-400">Análisis generado: {{ $timestampAnalisis }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    {{-- Panel: Proforma Técnica --}}
    @if($resultadoProforma)
        @php
            $proformaItems = $resultadoProforma['items'] ?? [];
            $proformaTotal = array_sum(array_map(fn($i) => (float)($i['subtotal'] ?? 0), $proformaItems));
            if ($proformaTotal <= 0) {
                $proformaTotal = (float) preg_replace('/[^0-9.]/', '', $resultadoProforma['total_estimado'] ?? '');
            }
            $proformaViabilidad = $resultadoProforma['analisis_viabilidad'] ?? '';
            $proformaCondiciones = $resultadoProforma['condiciones'] ?? [];
            $proformaTitulo = $resultadoProforma['titulo_proceso'] ?? 'Proceso';
            $proformaEmpresa = $resultadoProforma['empresa_nombre'] ?? '';
        @endphp
        <div
            class="fixed inset-0 z-[125] flex items-center justify-center px-4 py-8"
            x-data
            x-on:keydown.escape.window="$wire.set('resultadoProforma', null)"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="$set('resultadoProforma', null)"></div>

            <div class="relative w-full max-w-5xl bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col max-h-[92vh]">
                {{-- Header --}}
                <div class="sticky top-0 z-10 bg-white border-b border-neutral-100 rounded-t-[2rem]">
                    <div class="w-full border-t-4 border-amber-400 rounded-t-[2rem]"></div>
                    <div class="p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-amber-400/15 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-amber-500 tracking-[0.2em]">Proforma Técnica Generada con IA</p>
                                <h3 class="text-xl lg:text-2xl font-bold text-neutral-900">{{ Str::limit($proformaTitulo, 70) }}</h3>
                                @if($proformaEmpresa)
                                    <p class="text-sm text-neutral-500 mt-0.5">{{ $proformaEmpresa }}</p>
                                @endif
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="$set('resultadoProforma', null)"
                            class="flex-shrink-0 w-10 h-10 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto flex-1 p-6 lg:p-8 space-y-6">
                    {{-- Tabla de ítems --}}
                    @if(count($proformaItems) > 0)
                        <div>
                            <h4 class="text-sm font-bold text-neutral-700 mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-md bg-amber-100 flex items-center justify-center text-amber-600 text-xs font-bold">$</span>
                                Tabla de Precios Referenciales
                            </h4>
                            <div class="overflow-x-auto rounded-2xl border border-neutral-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-neutral-50 border-b border-neutral-200">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider w-8">#</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-neutral-500 uppercase tracking-wider">Descripción</th>
                                            <th class="px-4 py-3 text-center text-xs font-bold text-neutral-500 uppercase tracking-wider w-20">Unidad</th>
                                            <th class="px-4 py-3 text-center text-xs font-bold text-neutral-500 uppercase tracking-wider w-20">Cant.</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-neutral-500 uppercase tracking-wider w-32">P. Unit.</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-neutral-500 uppercase tracking-wider w-32">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-100">
                                        @foreach($proformaItems as $idx => $pitem)
                                            <tr class="{{ $idx % 2 === 0 ? 'bg-white' : 'bg-neutral-50/50' }} hover:bg-amber-50/40 transition-colors">
                                                <td class="px-4 py-3 text-neutral-400 text-xs">{{ $pitem['item'] ?? ($idx + 1) }}</td>
                                                <td class="px-4 py-3 text-neutral-700">{{ $pitem['descripcion'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-center text-neutral-600">{{ $pitem['unidad'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-center text-neutral-600">{{ $pitem['cantidad'] ?? '-' }}</td>
                                                <td class="px-4 py-3 text-right text-neutral-700 font-mono">S/ {{ number_format((float)($pitem['precio_unitario'] ?? 0), 2) }}</td>
                                                <td class="px-4 py-3 text-right text-neutral-800 font-semibold font-mono">S/ {{ number_format((float)($pitem['subtotal'] ?? 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-amber-50 border-t-2 border-amber-200">
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-right text-sm font-bold text-amber-800">TOTAL ESTIMADO</td>
                                            <td class="px-4 py-3 text-right text-base font-bold text-amber-700 font-mono">S/ {{ number_format((float)$proformaTotal, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Viabilidad --}}
                    @if($proformaViabilidad)
                        <div class="bg-primary-50 border border-primary-200 rounded-2xl p-5">
                            <h4 class="text-sm font-bold text-primary-700 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Análisis de Viabilidad
                            </h4>
                            <p class="text-sm text-primary-800 leading-relaxed">{{ $proformaViabilidad }}</p>
                        </div>
                    @endif

                    {{-- Condiciones --}}
                    @if(count($proformaCondiciones) > 0)
                        <div class="border border-neutral-200 rounded-2xl p-5">
                            <h4 class="text-sm font-bold text-neutral-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Condiciones y Supuestos
                            </h4>
                            <ul class="space-y-2">
                                @foreach($proformaCondiciones as $cond)
                                    <li class="flex gap-2.5 text-sm text-neutral-600">
                                        <span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                        {{ $cond }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Aviso importante --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-4">
                        <p class="text-[11px] text-neutral-400 text-center">
                            ⚠️ Esta proforma es estimativa y generada con inteligencia artificial. Los precios son referenciales y deben validarse con cotizaciones formales antes de presentar oferta.
                        </p>
                    </div>
                </div>

                {{-- Footer: botones de descarga --}}
                <div class="sticky bottom-0 z-10 bg-white border-t border-neutral-100 rounded-b-[2rem] p-4 lg:p-6 flex flex-wrap items-center justify-between gap-3">
                    <button
                        type="button"
                        wire:click="$set('resultadoProforma', null)"
                        class="text-sm text-neutral-500 hover:text-neutral-700 transition-colors px-4 py-2"
                    >
                        ← Nueva Proforma
                    </button>
                    <div class="flex flex-wrap gap-3">
                        @if($proformaToken)
                            {{-- Botón Word oculto — disponible via enlace directo si se necesita:
                            <a href="{{ route('proforma.word', $proformaToken) }}" target="_blank">
                                Descargar Word
                            </a>
                            --}}
                            <a
                                href="{{ route('proforma.excel', $proformaToken) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500 transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M6 3h12a3 3 0 013 3v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6a3 3 0 013-3z"/>
                                </svg>
                                Descargar Excel
                            </a>
                            <a
                                href="{{ route('proforma.print', $proformaToken) }}"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-neutral-800 text-white text-sm font-semibold hover:bg-neutral-700 transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Ver / Imprimir PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @elseif(!$buscando && $this->tieneFiltrosActivos())
        <!-- Sin resultados -->
        <div class="bg-gradient-to-br from-white to-neutral-50 rounded-3xl shadow-soft p-12 text-center border-2 border-neutral-200">
            <div class="max-w-md mx-auto">
                <svg class="w-20 h-20 mx-auto text-neutral-300 mb-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 10h.01"/>
                </svg>
                <h3 class="text-xl font-bold text-neutral-900 mb-2">No se encontraron resultados</h3>
                <p class="text-sm text-neutral-600 mb-8">Intenta modificar los filtros o ampliar tu búsqueda para obtener más resultados</p>
                <button
                    wire:click="limpiarFiltros"
                    class="px-8 py-3 bg-primary-500 text-white rounded-full font-semibold hover:bg-primary-400 transition-all shadow-sm hover:shadow-md inline-flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Limpiar Filtros
                </button>
            </div>
        </div>
    @endif

    {{-- Modal: Login requerido --}}
    @if($mostrarLoginModal)
        <div
            class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8"
            x-data
            x-on:keydown.escape.window="$wire.call('cerrarLoginModal')"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarLoginModal"></div>

            <div class="relative w-full max-w-md bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-6 lg:p-7">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Acceso requerido</p>
                        <h3 class="text-xl font-bold text-neutral-900 mt-1">Inicia sesion para continuar</h3>
                        <p class="text-sm text-neutral-500 mt-1">{{ $loginModalMensaje }}</p>
                    </div>
                    <button
                        type="button"
                        wire:click="cerrarLoginModal"
                        class="w-9 h-9 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold text-neutral-500">Correo</label>
                        <input
                            type="email"
                            wire:model.defer="loginEmail"
                            required
                            class="mt-1 w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                            placeholder="correo@empresa.com"
                        />
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-neutral-500">Contrasena</label>
                        <input
                            type="password"
                            wire:model.defer="loginPassword"
                            wire:keydown.enter="login"
                            required
                            class="mt-1 w-full px-4 py-2.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                            placeholder="********"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-xs text-neutral-500">
                        <input type="checkbox" wire:model.defer="loginRemember" class="rounded border-neutral-300 text-primary-500 focus:ring-primary-500" />
                        Mantener sesion
                    </label>
                    @if($loginError)
                        <div class="bg-primary-500/10 border border-primary-200 text-brand-600 text-xs font-semibold rounded-2xl px-4 py-2">
                            {{ $loginError }}
                        </div>
                    @endif
                    <button
                        type="button"
                        wire:click="login"
                        class="w-full py-3 rounded-full bg-primary-500 text-white text-sm font-semibold hover:bg-primary-400 transition-colors"
                    >
                        Iniciar sesion
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Funcionalidad Premium --}}
    @if($mostrarAccesoRestringido)
        <div
            class="fixed inset-0 z-[130] flex items-center justify-center px-4 py-8"
            x-data
            x-on:keydown.escape.window="$wire.call('cerrarAccesoRestringido')"
        >
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cerrarAccesoRestringido"></div>

            <div class="relative w-full max-w-md bg-white rounded-[2rem] shadow-soft border border-neutral-200 overflow-hidden">
                {{-- Franja superior decorativa --}}
                <div class="h-1.5 w-full bg-gradient-to-r from-secondary-500 via-secondary-400 to-secondary-200"></div>

                {{-- Contenido --}}
                <div class="p-6 lg:p-8">
                    {{-- Ícono + cerrar --}}
                    <div class="flex items-start justify-between gap-4 mb-5">
                        <div class="w-14 h-14 rounded-2xl bg-secondary-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="w-7 h-7 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                            </svg>
                        </div>
                        <button
                            type="button"
                            wire:click="cerrarAccesoRestringido"
                            class="w-9 h-9 rounded-full border border-neutral-200 text-neutral-400 hover:text-neutral-900 hover:border-neutral-400 transition-colors flex items-center justify-center flex-shrink-0"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Texto principal --}}
                    <p class="text-xs font-bold uppercase text-secondary-500 tracking-[0.18em] mb-1">Función Premium</p>
                    <h3 class="text-xl font-bold text-neutral-900 leading-snug mb-3">
                        ¡Encontraste una funcionalidad exclusiva!
                    </h3>
                    <p class="text-sm text-neutral-500 leading-relaxed">
                        {{ $accesoRestringidoMensaje }}
                    </p>

                    {{-- Beneficios rápidos --}}
                    <ul class="mt-5 space-y-2">
                        @foreach([
                            'Análisis de TDR con inteligencia artificial',
                            'Generación automática de proformas técnicas',
                            'Seguimiento personalizado de procesos',
                        ] as $benefit)
                            <li class="flex items-center gap-2.5 text-sm text-neutral-600">
                                <span class="w-5 h-5 rounded-full bg-secondary-500/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                {{ $benefit }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- CTAs --}}
                    <div class="mt-7 flex flex-col sm:flex-row items-center gap-3">
                        <a
                            href="{{ route('planes') }}"
                            class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-secondary-500 text-white text-sm font-bold hover:bg-secondary-400 transition-colors shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Quiero ser Premium
                        </a>
                        <button
                            type="button"
                            wire:click="cerrarAccesoRestringido"
                            class="w-full sm:w-auto px-6 py-3 rounded-full border border-neutral-200 text-sm font-semibold text-neutral-500 hover:text-neutral-900 hover:border-neutral-400 transition-colors"
                        >
                            Ahora no
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         Modal Cotizador SEACE — Instrucciones paso a paso
         El portal SEACE usa navegación single-tab (sessionStorage):
         abrir una URL en nueva pestaña pierde la sesión.
         El usuario debe buscar el contrato DENTRO del portal.
         ═══════════════════════════════════════════════════════════════ --}}
    <div
        x-data="cotizadorSeace()"
        x-show="abierto"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4"
        @keydown.escape.window="cerrar()"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cerrar()"></div>

        {{-- Panel --}}
        <div
            x-show="abierto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden"
        >
            {{-- Header --}}
            <div class="bg-gradient-to-r from-primary-800 to-primary-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg">Cotizar en SEACE</h3>
                            <p class="text-white/70 text-xs" x-text="codigo"></p>
                        </div>
                    </div>
                    <button @click="cerrar()" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5 max-h-[65vh] overflow-y-auto">

                {{-- Banner independencia --}}
                <div class="bg-blue-50 border border-blue-200/60 rounded-2xl px-4 py-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            <strong>Somos un servicio independiente.</strong> No somos el SEACE ni el OSCE. No recopilamos tus credenciales del portal del Estado.
                        </p>
                    </div>
                </div>

                {{-- Banner explicativo amigable --}}
                <div class="bg-gradient-to-r from-secondary-500/10 to-secondary-500/5 border border-secondary-200/40 rounded-2xl px-4 py-3">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-secondary-500/15 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">Estamos casi allí</p>
                            <p class="text-xs text-neutral-600 mt-0.5 leading-relaxed">
                                El portal SEACE requiere que la cotización se realice desde su propia plataforma por razones de seguridad.
                                Te dejamos todo listo para que sea rápido: <b class="text-neutral-900">solo copia, pega y cotiza</b>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Datos del contrato para buscar en el portal --}}
                <div class="bg-neutral-50 rounded-2xl p-4 space-y-3">
                    {{-- Código de proceso (copiable) --}}
                    <div>
                        <p class="text-xs text-neutral-400 font-medium mb-1">Código de proceso</p>
                        <div class="flex items-center gap-2">
                            <span class="flex-1 bg-white border border-neutral-200 rounded-full px-4 py-2 text-sm text-neutral-900 font-bold font-mono truncate" x-text="codigo"></span>
                            <button
                                @click="copiarCodigo()"
                                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-xs font-semibold transition-all duration-200"
                                :class="copiadoCodigo ? 'bg-secondary-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-100'"
                            >
                                <template x-if="!copiadoCodigo">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                                <template x-if="copiadoCodigo">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <span x-text="copiadoCodigo ? '¡Copiado!' : 'Copiar'"></span>
                            </button>
                        </div>
                    </div>
                    {{-- Entidad --}}
                    <template x-if="entidad">
                        <div>
                            <p class="text-xs text-neutral-400 font-medium mb-0.5">Entidad</p>
                            <p class="text-sm text-neutral-900 font-semibold" x-text="entidad"></p>
                        </div>
                    </template>
                </div>

                {{-- Pasos guiados --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-neutral-900">3 pasos rápidos:</h4>

                    {{-- Paso 1 --}}
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-secondary-500 text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                        <div class="flex-1">
                            <p class="text-sm text-neutral-900 font-semibold">Abre el portal oficial SEACE</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Se abrirá en una nueva pestaña. El portal SEACE (seace.gob.pe) gestionará tu sesión de forma segura en su propia plataforma.</p>
                        </div>
                    </div>

                    {{-- Paso 2 --}}
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-secondary-500 text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                        <div class="flex-1">
                            <p class="text-sm text-neutral-900 font-semibold">Pega el código en el buscador</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Ya lo copiamos por ti. Solo haz <b class="text-neutral-600">Ctrl+V</b> en el buscador del portal y presiona buscar.</p>
                        </div>
                    </div>

                    {{-- Paso 3 --}}
                    <div class="flex items-start gap-3">
                        <span class="w-7 h-7 rounded-full bg-secondary-500 text-white text-xs font-bold flex items-center justify-center shrink-0">3</span>
                        <div class="flex-1">
                            <p class="text-sm text-neutral-900 font-semibold">Cotiza directamente</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Encontrarás el proceso y podrás enviar tu cotización desde ahí.</p>
                        </div>
                    </div>
                </div>

                {{-- Nota informativa --}}
                <div class="bg-primary-900/5 border border-primary-400/20 rounded-2xl px-4 py-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-primary-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <p class="text-xs text-primary-800">
                            <b>¿Por qué no te llevamos directo?</b> El portal SEACE protege cada sesión de usuario de forma individual. Las cotizaciones solo pueden enviarse navegando dentro de su plataforma — esto garantiza la seguridad de tu cuenta y la validez de tu oferta.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-neutral-50 border-t border-neutral-100 flex items-center justify-between gap-3">
                <button
                    @click="cerrar()"
                    class="px-5 py-2.5 rounded-full text-sm font-semibold text-neutral-600 hover:bg-neutral-200 transition-colors"
                >
                    Cancelar
                </button>
                <button
                    @click="abrirSeace()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-secondary-500 text-white text-sm font-bold hover:bg-secondary-600 transition-colors shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Ir al portal SEACE
                </button>
            </div>
        </div>
    </div>

    <script>
        function cotizadorSeace() {
            return {
                abierto: false,
                urlLogin: '',
                idContrato: null,
                codigo: '',
                entidad: '',
                copiadoCodigo: false,

                init() {
                    window.addEventListener('abrir-cotizador-seace', (e) => {
                        const d = e.detail;
                        this.urlLogin = d.urlLogin || '';
                        this.idContrato = d.idContrato || null;
                        this.codigo = d.desContratacion || '';
                        this.entidad = d.entidad || '';
                        this.copiadoCodigo = false;
                        this.abierto = true;
                        // Copiar código de proceso automáticamente al abrir
                        this.$nextTick(() => this.copiarCodigo());
                    });
                },

                copiarCodigo() {
                    if (!this.codigo) return;
                    navigator.clipboard.writeText(this.codigo).then(() => {
                        this.copiadoCodigo = true;
                        setTimeout(() => { this.copiadoCodigo = false; }, 3000);
                    }).catch(() => {
                        const ta = document.createElement('textarea');
                        ta.value = this.codigo;
                        ta.style.position = 'fixed';
                        ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        this.copiadoCodigo = true;
                        setTimeout(() => { this.copiadoCodigo = false; }, 3000);
                    });
                },

                abrirSeace() {
                    // Asegurar que el código está copiado antes de abrir
                    this.copiarCodigo();
                    window.open(this.urlLogin, '_blank');
                },

                cerrar() {
                    this.abierto = false;
                }
            };
        }
    </script>

    {{-- ============================================================
         Overlay de carga – Análisis TDR con IA
         Muestra mensajes progresivos según el tiempo transcurrido
         para que el usuario sepa que el sistema sigue trabajando.
         ============================================================ --}}
    <div
        wire:loading.flex
        wire:target="analizarTdr"
        class="fixed inset-0 z-[200] items-center justify-center px-4"
        style="display: none"
        x-data="{
            seconds: 0,
            timer: null,
            init() {
                new MutationObserver(() => {
                    if (this.$el.style.display !== 'none') {
                        this.seconds = 0;
                        if (this.timer) clearInterval(this.timer);
                        this.timer = setInterval(() => this.seconds++, 1000);
                    } else if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                }).observe(this.$el, { attributes: true, attributeFilter: ['style'] });
            },
            get message() {
                if (this.seconds >= 45) return 'El análisis está tomando más de lo esperado. Por favor no cierre esta página.';
                if (this.seconds >= 20) return 'Procesando documento extenso… Esto puede tardar hasta 2 minutos.';
                if (this.seconds >= 8)  return 'Extrayendo y analizando el contenido del documento…';
                return 'Preparando análisis con IA…';
            },
            get sub() {
                if (this.seconds >= 20) return 'Los documentos con muchas páginas o imágenes requieren más tiempo de procesamiento.';
                if (this.seconds >= 8)  return 'Estamos leyendo cada sección del TDR para darte un resumen preciso.';
                return 'Conectando con el servicio de inteligencia artificial…';
            }
        }"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-[2rem] shadow-soft p-8 max-w-sm w-full text-center">
            {{-- Icono animado --}}
            <div class="mx-auto w-16 h-16 rounded-full bg-primary-500/10 flex items-center justify-center mb-5">
                <svg class="w-8 h-8 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            {{-- Mensaje principal --}}
            <p class="text-base font-semibold text-neutral-900" x-text="message"></p>

            {{-- Submensaje explicativo --}}
            <p class="text-sm text-neutral-400 mt-2 leading-relaxed" x-text="sub"></p>

            {{-- Tiempo transcurrido --}}
            <p class="text-xs text-neutral-400/70 mt-4">
                Tiempo: <span x-text="seconds + 's'"></span>
            </p>

            {{-- Puntos animados --}}
            <div class="flex justify-center gap-1.5 mt-4">
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0s"></span>
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.15s"></span>
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-bounce" style="animation-delay: 0.3s"></span>
            </div>
        </div>
    </div>

</div>

@script
<script>
    $wire.on('descargar-archivo', (event) => {
        if (!event?.url) {
            return;
        }

        const link = document.createElement('a');
        link.href = event.url;
        link.download = '';
        link.rel = 'noopener';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    $wire.on('login-redirect', (event) => {
        if (!event?.url) {
            return;
        }

        window.location.href = event.url;
    });

    /**
     * Cotizar en SEACE: abre modal guiado con pasos e instrucciones.
     * El portal SEACE usa navegación single-tab (sessionStorage),
     * así que el usuario debe buscar el contrato dentro del portal.
     */
    $wire.on('cotizar-seace-modal', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        if (!data?.desContratacion) {
            console.error('cotizar-seace-modal: faltan datos del contrato');
            return;
        }
        window.dispatchEvent(new CustomEvent('abrir-cotizador-seace', { detail: data }));
    });
</script>
@endscript
