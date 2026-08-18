<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase text-neutral-400 tracking-[0.2em]">Vigilancia de Adjudicaciones</p>
        <h1 class="text-3xl font-black text-neutral-900">Procesos en seguimiento</h1>
        <p class="text-sm text-neutral-500 mt-2">Procesos mayores a S/ {{ number_format((float) $umbral, 0) }} monitoreados cada 5 horas. Al detectar buena pro se notifica a los destinatarios configurados.</p>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">En vigilancia</p>
            <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($stats['vigilados']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">Procesos pendientes de buena pro</p>
        </div>
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">Procesos &gt;= S/ {{ number_format((float) $umbral, 0) }}</p>
            <p class="text-3xl font-black text-amber-600 mt-1">{{ number_format($stats['sobre_umbral']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">Universo total del SEACE</p>
        </div>
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-200 p-5">
            <p class="text-xs font-semibold uppercase text-neutral-400">Buena pro (histórico)</p>
            <p class="text-3xl font-black text-emerald-600 mt-1">{{ number_format($stats['buena_pro']) }}</p>
            <p class="text-xs text-neutral-400 mt-1">Procesos &gt;= umbral adjudicados</p>
        </div>
    </div>

    {{-- ═════════ BANDEJA ═════════ --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 lg:p-6 border border-neutral-200 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-neutral-900">Bandeja de procesos vigilados</h2>
            @php
                $filtrosActivos = array_filter([
                    $palabraClave, $estado, $departamentoId > 0 ? '1' : '',
                    $montoMin > 0 ? '1' : '', $montoMax > 0 ? '1' : '',
                    $fechaDesde, $fechaHasta,
                ]);
            @endphp
            @if(count($filtrosActivos) > 0)
                <button wire:click="limpiarFiltros" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpiar filtros
                </button>
            @endif
        </div>

        {{-- Filtros --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 mb-5">
            <div class="lg:col-span-3">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($palabraClave) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Palabra clave</label>
                <input type="text" wire:model.live.debounce.400ms="palabraClave" placeholder="Entidad, nomenclatura, descripción..." class="w-full px-4 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($estado) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Estado del proceso</label>
                <select wire:model.live="estado" class="w-full px-3 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Todos</option>
                    @foreach($estadosDisponibles as $edo)
                        <option value="{{ $edo }}">{{ ucfirst(strtolower($edo)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium mb-1.5 {{ $departamentoId > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Departamento</label>
                <select wire:model.live="departamentoId" class="w-full px-3 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="0">Todos</option>
                    @foreach($departamentosDisponibles as $dep)
                        <option value="{{ $dep['id'] }}">{{ $dep['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-medium mb-1.5 {{ $montoMin > 0 || $montoMax > 0 ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Monto (S/)</label>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" step="1000" wire:model.live.debounce.500ms="montoMin" placeholder="Mín" class="w-full px-3 py-2.5 rounded-xl text-xs bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <span class="text-neutral-400 text-xs">→</span>
                    <input type="number" min="0" step="1000" wire:model.live.debounce.500ms="montoMax" placeholder="Máx" class="w-full px-3 py-2.5 rounded-xl text-xs bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium mb-1.5 {{ !empty($fechaDesde) || !empty($fechaHasta) ? 'text-brand-600 font-semibold' : 'text-neutral-600' }}">Publicado (rango)</label>
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="fechaDesde" class="w-full px-3 py-2.5 rounded-xl text-xs bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <span class="text-neutral-400 text-xs">→</span>
                    <input type="date" wire:model.live="fechaHasta" class="w-full px-3 py-2.5 rounded-xl text-xs bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-100 text-left text-xs uppercase text-neutral-400">
                        <th class="py-2 pr-4">Entidad</th>
                        <th class="py-2 pr-4">Nomenclatura</th>
                        <th class="py-2 pr-4">Objeto</th>
                        <th class="py-2 pr-4">Monto</th>
                        <th class="py-2 pr-4">Estado</th>
                        <th class="py-2 pr-4">Departamento</th>
                        <th class="py-2 pr-4">Publicado</th>
                        <th class="py-2">Vigilancia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($procesos as $p)
                        <tr class="border-b border-neutral-50 hover:bg-neutral-50/50">
                            <td class="py-3 pr-4 text-neutral-800 max-w-[220px] truncate">{{ $p->entidad_nombre }}</td>
                            <td class="py-3 pr-4 font-semibold text-neutral-900">{{ $p->nomenclatura }}</td>
                            <td class="py-3 pr-4 text-neutral-600">{{ $p->objeto_contratacion }}</td>
                            <td class="py-3 pr-4 text-neutral-800">{{ $p->valor_referencial > 0 ? 'S/ ' . number_format($p->valor_referencial, 2) : '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ in_array(strtoupper($p->estado ?? ''), ['ADJUDICADO', 'CONSENTIDO', 'OTORGADO', 'CONTRATADO']) ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-600' }}">
                                    {{ ucfirst(strtolower($p->estado ?? '—')) }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-neutral-600">{{ $p->departamento?->nombre ?? '—' }}</td>
                            <td class="py-3 pr-4 text-neutral-600">{{ $p->fecha_publicacion?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-600">En vigilancia</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-neutral-400">No hay procesos en vigilancia con los filtros actuales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $procesos->links() }}
        </div>
    </div>
</div>
