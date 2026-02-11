<div
    class="p-6 flex flex-col gap-6 max-w-full"
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
                <label class="block text-xs font-medium mb-1.5 {{ !empty($palabraClave) ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Palabra Clave</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="palabraClave"
                        placeholder="Buscar..."
                        wire:keydown.enter="buscar"
                        class="w-full px-4 py-2.5 pl-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($palabraClave) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($palabraClave) ? 'text-primary-600' : 'text-neutral-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <label class="block text-xs font-medium mb-1.5 {{ !empty($codigoEntidad) ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Entidad</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="entidadTexto"
                        @focus="abierto = ($wire.entidadesSugeridas.length > 0)"
                        wire:keydown.enter="buscar"
                        placeholder="Buscar entidad (mín. 3 caracteres)..."
                        class="w-full px-4 py-2.5 pl-10 pr-10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ !empty($codigoEntidad) ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                    >
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 {{ !empty($codigoEntidad) ? 'text-primary-600' : 'text-neutral-400' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>

                    <!-- Spinner de búsqueda interno (mientras busca entidades) -->
                    <div wire:loading wire:target="entidadTexto,buscarEntidades" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
                <label class="block text-xs font-medium mb-1.5 {{ $objetoContrato > 0 ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Objeto</label>
                <div class="relative">
                    <select
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
                <label class="block text-xs font-medium mb-1.5 {{ $estadoContrato > 0 ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Estado</label>
                <div class="relative">
                    <select
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
                        <label class="block text-xs font-medium mb-1.5 {{ $departamento > 0 ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Departamento</label>
                        <button
                            type="button"
                            @click="abierto = !abierto"
                            class="w-full px-3 py-2.5 rounded-xl text-sm text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all {{ $departamento > 0 ? 'bg-primary-50 border-2 border-primary-500 ring-2 ring-primary-100 font-medium' : 'bg-neutral-50 border border-neutral-100' }}"
                        >
                            <span class="truncate">{{ $departamento === 0 ? 'Todos' : collect($departamentos)->firstWhere('id', $departamento)['nom'] ?? 'Todos' }}</span>
                            <svg class="w-4 h-4 {{ $departamento > 0 ? 'text-primary-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <button type="button" @click="seleccionar(0, 'Todos')" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $departamento === 0 ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700' }}">
                                    Todos
                                </button>
                                <template x-for="depto in opcionesFiltradas()" :key="depto.id">
                                    <button type="button" @click="seleccionar(depto.id, depto.nom)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($departamento) === depto.id ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700'" x-text="depto.nom"></button>
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
                        <label class="block text-xs font-medium mb-1.5 {{ $provincia > 0 ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Provincia</label>
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
                            <svg class="w-4 h-4 {{ $provincia > 0 ? 'text-primary-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <button type="button" @click="seleccionar(0)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $provincia === 0 ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700' }}">
                                    Todas
                                </button>
                                <template x-for="prov in opcionesFiltradas()" :key="prov.id">
                                    <button type="button" @click="seleccionar(prov.id)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($provincia) === prov.id ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700'" x-text="prov.nom"></button>
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
                        <label class="block text-xs font-medium mb-1.5 {{ $distrito > 0 ? 'text-primary-600 font-semibold' : 'text-neutral-600' }} transition-colors">Distrito</label>
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
                            <svg class="w-4 h-4 {{ $distrito > 0 ? 'text-primary-600' : 'text-neutral-400' }} transition-colors" :class="{'rotate-180': abierto}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <button type="button" @click="seleccionar(0)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors {{ $distrito === 0 ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700' }}">
                                    Todos
                                </button>
                                <template x-for="dist in opcionesFiltradas()" :key="dist.id">
                                    <button type="button" @click="seleccionar(dist.id)" class="w-full px-3 py-2 text-left text-sm hover:bg-neutral-50 transition-colors" :class="@js($distrito) === dist.id ? 'bg-primary-50 text-primary-600 font-medium' : 'text-neutral-700'" x-text="dist.nom"></button>
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
        <!-- Tabla de Resultados -->
        <div class="bg-white rounded-3xl shadow-soft overflow-hidden border border-neutral-200">
            <!-- Header Compacto -->
            <div class="px-4 lg:px-6 py-4 border-b border-neutral-100 flex items-center justify-end gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-neutral-600 hidden sm:inline">Mostrar:</label>
                    <select
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

            <!-- Tabla -->
            <div class="overflow-x-auto px-4 lg:px-6 py-4">
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
                            <th class="px-4 lg:px-6 py-3.5 text-left text-xs font-semibold text-neutral-800 uppercase tracking-wider">TDR</th>
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
                                            str_contains($estado, 'vigente') => 'bg-secondary-500/10 text-secondary-600 border-secondary-200',
                                            str_contains($estado, 'evaluación') => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                            str_contains($estado, 'culminado') => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                                            default => 'bg-neutral-100 text-neutral-700 border-neutral-200',
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
                                        <button
                                            wire:click="descargarTdr({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="descargarTdr({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-neutral-200 text-xs font-semibold text-neutral-700 hover:text-primary-600 hover:border-primary-400 transition-colors"
                                            title="Descargar TDR"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11" />
                                            </svg>
                                            <span class="hidden xl:inline">Descargar</span>
                                        </button>
                                        <button
                                            wire:click="analizarTdr({{ $contrato['idContrato'] }})"
                                            wire:loading.attr="disabled"
                                            wire:target="analizarTdr({{ $contrato['idContrato'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-primary-200 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 transition-colors"
                                            title="Analizar con IA"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-.75-3m6.75 0L15 20l-.75-3M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" />
                                            </svg>
                                            <span class="hidden xl:inline">Analizar</span>
                                        </button>
                                    </div>
                                    <div
                                        wire:loading
                                        wire:target="descargarTdr({{ $contrato['idContrato'] }})"
                                        class="text-[11px] text-primary-600 mt-2"
                                    >
                                        Preparando descarga...
                                    </div>
                                    <div
                                        wire:loading
                                        wire:target="analizarTdr({{ $contrato['idContrato'] }})"
                                        class="text-[11px] text-primary-600 mt-1"
                                    >
                                        Analizando documento con IA...
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
                                    class="w-10 h-10 text-sm font-bold rounded-xl transition-all {{ $i === $currentPage ? 'bg-primary-500 text-white shadow-lg scale-110 ring-2 ring-primary-300' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-50 hover:border-primary-500 hover:text-primary-600 shadow-sm' }}"
                                >
                                    {{ $i }}
                                </button>
                            @endforeach

                            <!-- Siguiente -->
                            <button
                                wire:click="irAPagina({{ $pagina + 1 }})"
                                class="px-4 py-2.5 text-sm font-semibold rounded-xl transition-all {{ $pagina === $totalPages ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-white border-2 border-neutral-300 text-neutral-700 hover:bg-primary-500 hover:border-primary-500 hover:text-white shadow-sm hover:shadow-md' }}"
                                {{ $pagina === $totalPages ? 'disabled' : '' }}
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
                    <div class="sticky top-0 z-10 p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white border-b border-neutral-100">
                        <div>
                            <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Análisis IA del TDR</p>
                            <h3 class="text-2xl font-bold text-neutral-900">{{ $analisisContrato['codigo'] ?? 'Proceso seleccionado' }}</h3>
                            <p class="text-sm text-neutral-600">{{ $analisisContrato['entidad'] ?? 'Entidad no disponible' }} · {{ $analisisContrato['archivo'] ?? 'Archivo' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($isCached)
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-500 border border-primary-200">
                                    <span class="w-2 h-2 rounded-full bg-primary-400 animate-pulse"></span>
                                    Resultado en caché
                                </span>
                            @endif
                            <button
                                type="button"
                                wire:click="limpiarAnalisis"
                                class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:border-neutral-400 transition-colors"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">
                        @if($resumenEjecutivo)
                            <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-5">
                                <p class="text-xs font-semibold text-primary-500 uppercase tracking-[0.2em] mb-2">Resumen ejecutivo</p>
                                <p class="text-base text-neutral-800 leading-relaxed">{{ $resumenEjecutivo }}</p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-4">
                                <h4 class="text-sm font-semibold text-neutral-900 mb-3">Contexto del proceso</h4>
                                <dl class="space-y-2 text-xs text-neutral-600">
                                    <div>
                                        <dt class="font-semibold text-neutral-500">Entidad</dt>
                                        <dd>{{ $analisisContrato['entidad'] ?? 'No disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-neutral-500">Objeto</dt>
                                        <dd>{{ $analisisContrato['objeto'] ?? 'No disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-neutral-500">Estado</dt>
                                        <dd>{{ $analisisContrato['estado'] ?? 'No disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold text-neutral-500">Archivo analizado</dt>
                                        <dd>{{ $analisisContrato['archivo'] ?? 'N/D' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="border border-neutral-200 rounded-2xl p-4 space-y-3">
                                <h4 class="text-sm font-semibold text-neutral-900">Fechas clave</h4>
                                <div class="flex items-center justify-between text-xs text-neutral-500">
                                    <span>Publicación</span>
                                    <p class="text-neutral-900 font-semibold">{{ $analisisContrato['fecha_publicacion'] ?? 'N/D' }}</p>
                                </div>
                                <div class="flex items-center justify-between text-xs text-neutral-500">
                                    <span>Cierre / Fin de cotización</span>
                                    <p class="text-neutral-900 font-semibold">{{ $analisisContrato['fecha_cierre'] ?? 'N/D' }}</p>
                                </div>
                                <div class="flex items-center justify-between text-xs text-neutral-500">
                                    <span>Etapa</span>
                                    <p class="text-neutral-900 font-semibold">{{ $analisisContrato['etapa'] ?? 'No disponible' }}</p>
                                </div>
                                <div class="flex items-center justify-between text-xs text-neutral-500">
                                    <span>Monto referencial</span>
                                    <p class="text-neutral-900 font-semibold">{{ $montoReferencial ?? 'N/D' }}</p>
                                </div>
                            </div>

                            @auth
                                <div class="rounded-2xl p-4 bg-white border border-neutral-200 shadow-soft space-y-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.2em] text-neutral-400">Nivel de compatibilidad</p>
                                            <p class="text-sm font-semibold text-neutral-900">Puntajes por suscriptor</p>
                                        </div>
                                        <div class="text-right text-xs text-neutral-500">
                                            <p class="uppercase tracking-wide text-[0.65rem]">Proceso</p>
                                            <p class="font-semibold text-neutral-800">{{ $analisisContrato['codigo'] ?? 'N/D' }}</p>
                                        </div>
                                    </div>

                                    @if(empty($suscriptoresUsuario))
                                        <div class="rounded-xl border border-neutral-100 bg-neutral-50 px-3 py-3 text-sm text-neutral-600">
                                            No tienes suscriptores activos asociados a tu cuenta.
                                        </div>
                                    @else
                                        <div class="space-y-3">
                                            @foreach($suscriptoresUsuario as $suscriptor)
                                                @php
                                                    $compat = $compatibilidadPorSuscriptor[$suscriptor['id']] ?? null;
                                                    $score = $compat['score'] ?? null;
                                                    $nivel = $compat['nivel'] ?? null;
                                                    $actualizado = $compat['actualizado'] ?? null;
                                                    $hasCopy = $suscriptor['has_copy'] ?? false;
                                                @endphp
                                                <div class="flex flex-col gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="text-sm font-semibold text-neutral-900">{{ $suscriptor['label'] }}</p>
                                                            @if(!is_null($score))
                                                                <p class="text-xs text-neutral-600">Actualizado: <span class="font-semibold text-neutral-800">{{ $actualizado ?? 'N/D' }}</span></p>
                                                            @elseif(!$hasCopy)
                                                                <p class="text-xs text-neutral-500">Completa el copy del suscriptor para habilitar el puntaje.</p>
                                                            @else
                                                                <p class="text-xs text-neutral-500">Sin puntaje para este proceso.</p>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            @if(!is_null($score))
                                                                <div class="text-right">
                                                                    <p class="text-2xl font-black text-neutral-900 leading-none">{{ number_format($score, 1) }}<span class="text-xs font-semibold text-neutral-500 align-top">/10</span></p>
                                                                    @if($nivel)
                                                                        <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-primary-100 text-primary-600">{{ strtoupper($nivel) }}</span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <button
                                                                    type="button"
                                                                    wire:click="calcularCompatibilidad({{ $suscriptor['id'] }})"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="calcularCompatibilidad({{ $suscriptor['id'] }})"
                                                                    class="px-3 py-2 text-xs font-semibold rounded-full border border-primary-200 text-primary-600 hover:bg-primary-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                                    @if(!$hasCopy) disabled @endif
                                                                >
                                                                    @if($compatibilidadEnCurso === $suscriptor['id'])
                                                                        Calculando...
                                                                    @else
                                                                        Obtener score
                                                                    @endif
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <p class="text-[11px] text-neutral-500">Selecciona un suscriptor para obtener un puntaje personalizado antes de decidir si postular.</p>
                                </div>
                            @endauth
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div class="border border-neutral-200 rounded-2xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-neutral-900">Requisitos técnicos</p>
                                    <span class="text-xs text-neutral-400">{{ count($requisitosList) }} items</span>
                                </div>
                                <ul class="space-y-2 text-sm text-neutral-800">
                                    @forelse($requisitosList as $item)
                                        <li class="flex gap-3">
                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary-400 flex-shrink-0"></span>
                                            <span class="leading-relaxed text-neutral-800">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-neutral-500">No se encontraron requisitos específicos.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <div class="border border-neutral-200 rounded-2xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-neutral-900">Reglas de negocio y ejecución</p>
                                    <span class="text-xs text-neutral-400">{{ count($reglasList) }} items</span>
                                </div>
                                <ul class="space-y-2 text-sm text-neutral-800">
                                    @forelse($reglasList as $item)
                                        <li class="flex gap-3">
                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary-400 flex-shrink-0"></span>
                                            <span class="leading-relaxed text-neutral-800">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-neutral-500">No se detallaron reglas adicionales.</li>
                                    @endforelse
                                </ul>
                                @if(($analisisData['reglas_ejecucion'] ?? null) && count($reglasList) === 0)
                                    <p class="text-sm text-neutral-700 mt-3">{{ $analisisData['reglas_ejecucion'] }}</p>
                                @endif
                            </div>

                            <div class="border border-neutral-200 rounded-2xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-neutral-900">Políticas y penalidades</p>
                                    <span class="text-xs text-neutral-400">{{ count($penalidadesList) }} items</span>
                                </div>
                                <ul class="space-y-2 text-sm text-neutral-800">
                                    @forelse($penalidadesList as $item)
                                        <li class="flex gap-3">
                                            <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-primary-400 flex-shrink-0"></span>
                                            <span class="leading-relaxed text-neutral-800">{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-neutral-500">No se encontraron penalidades explícitas.</li>
                                    @endforelse
                                </ul>
                            </div>
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
</script>
@endscript
