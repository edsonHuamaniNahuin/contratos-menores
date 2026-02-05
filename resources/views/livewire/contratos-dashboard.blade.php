<div class="space-y-6">
    {{-- Estadísticas en Cards (Diseño Sequence) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Contratos --}}
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
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
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
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
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
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
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
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

    {{-- Filtros (Diseño Sequence) --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Búsqueda --}}
            <div class="md:col-span-2">
                <input
                    type="text"
                    wire:model.live.debounce.500ms="busqueda"
                    placeholder="Buscar por código, entidad o descripción..."
                    class="w-full px-6 py-3 rounded-full border border-neutral-100 bg-neutral-50 text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent"
                >
            </div>

            {{-- Filtro Estado --}}
            <div>
                <select
                    wire:model.live="filtroEstado"
                    class="w-full px-6 py-3 rounded-full border border-neutral-100 bg-neutral-50 text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent"
                >
                    @foreach($estados as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtro Objeto --}}
            <div>
                <select
                    wire:model.live="filtroObjeto"
                    class="w-full px-6 py-3 rounded-full border border-neutral-100 bg-neutral-50 text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent"
                >
                    @foreach($objetos as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($busqueda || $filtroEstado || $filtroObjeto)
            <div class="mt-4">
                <button
                    wire:click="limpiarFiltros"
                    class="px-6 py-2 rounded-full bg-neutral-100 text-neutral-600 hover:bg-neutral-200 transition-colors text-sm font-medium"
                >
                    Limpiar filtros
                </button>
            </div>
        @endif
    </div>

    {{-- Tabla de Contratos (Diseño Sequence) --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50 border-b border-neutral-100">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <button
                                wire:click="ordenarPor('codigo_proceso')"
                                class="flex items-center gap-2 text-sm font-semibold text-neutral-900 hover:text-primary-800 transition-colors"
                            >
                                Código
                                @if($ordenar === 'codigo_proceso')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $direccion === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <button
                                wire:click="ordenarPor('entidad')"
                                class="flex items-center gap-2 text-sm font-semibold text-neutral-900 hover:text-primary-800 transition-colors"
                            >
                                Entidad
                                @if($ordenar === 'entidad')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $direccion === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-neutral-900">Descripción</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-neutral-900">Objeto</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-neutral-900">Estado</th>
                        <th class="px-6 py-4 text-left">
                            <button
                                wire:click="ordenarPor('fecha_publicacion')"
                                class="flex items-center gap-2 text-sm font-semibold text-neutral-900 hover:text-primary-800 transition-colors"
                            >
                                Publicación
                                @if($ordenar === 'fecha_publicacion')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $direccion === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left">
                            <button
                                wire:click="ordenarPor('fin_cotizacion')"
                                class="flex items-center gap-2 text-sm font-semibold text-neutral-900 hover:text-primary-800 transition-colors"
                            >
                                Fin Cotización
                                @if($ordenar === 'fin_cotizacion')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $direccion === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($contratos as $contrato)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-primary-800">{{ $contrato->codigo_proceso }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-neutral-900">{{ Str::limit($contrato->entidad, 40) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-neutral-600">{{ Str::limit($contrato->descripcion, 60) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-4 py-1 rounded-full text-xs font-medium bg-secondary-200 text-neutral-900">
                                    {{ $contrato->objeto }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $colorEstado = match($contrato->id_estado_contrato) {
                                        2 => 'bg-secondary-500 text-white',
                                        3 => 'bg-primary-600 text-white',
                                        4 => 'bg-neutral-400 text-white',
                                        default => 'bg-neutral-100 text-neutral-900',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-4 py-1 rounded-full text-xs font-medium {{ $colorEstado }}">
                                    {{ $contrato->estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-neutral-600">{{ $contrato->fecha_publicacion?->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($contrato->fin_cotizacion)
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-neutral-600">{{ $contrato->fin_cotizacion->format('d/m/Y H:i') }}</span>
                                        @php
                                            $diasRestantes = now()->diffInDays($contrato->fin_cotizacion, false);
                                        @endphp
                                        @if($diasRestantes >= 0 && $diasRestantes <= 3)
                                            <span class="text-xs font-medium text-neutral-900 bg-secondary-200 px-2 py-0.5 rounded-full inline-flex items-center w-fit">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                {{ $diasRestantes }} días
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-neutral-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-neutral-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-neutral-400 text-lg">No se encontraron contratos</p>
                                    @if($busqueda || $filtroEstado || $filtroObjeto)
                                        <button
                                            wire:click="limpiarFiltros"
                                            class="mt-4 px-6 py-2 rounded-full bg-primary-800 text-white hover:bg-primary-900 transition-colors text-sm font-medium"
                                        >
                                            Limpiar filtros
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación (Diseño Sequence) --}}
        @if($contratos->hasPages())
            <div class="px-6 py-4 border-t border-neutral-100">
                {{ $contratos->links() }}
            </div>
        @endif
    </div>
</div>
