<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- ── Header ── --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-neutral-900">📊 Consumo IA</h1>
                <p class="text-sm text-neutral-400 mt-2">Monitoreo de tokens y análisis realizados por usuario y canal</p>
            </div>
        </div>
    </div>

    {{-- ── Filtros ── --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Rango de fechas --}}
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Período</label>
                <select wire:model.live="rangoFechas" class="w-full rounded-full border border-neutral-200 px-4 py-2 text-sm text-neutral-600 focus:ring-primary-400 focus:border-primary-400">
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="90">Últimos 90 días</option>
                    <option value="365">Último año</option>
                    <option value="todo">Todo el historial</option>
                </select>
            </div>

            {{-- Canal de origen --}}
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Canal</label>
                <select wire:model.live="filtroOrigen" class="w-full rounded-full border border-neutral-200 px-4 py-2 text-sm text-neutral-600 focus:ring-primary-400 focus:border-primary-400">
                    <option value="">Todos los canales</option>
                    <option value="web">Web (Buscador)</option>
                    <option value="telegram">Telegram</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="job">Job (async)</option>
                </select>
            </div>

            {{-- Filtro por usuario --}}
            <div>
                <label class="block text-xs font-medium text-neutral-400 mb-1">Usuario</label>
                <select wire:model.live="filtroUsuario" class="w-full rounded-full border border-neutral-200 px-4 py-2 text-sm text-neutral-600 focus:ring-primary-400 focus:border-primary-400">
                    <option value="">Todos los usuarios</option>
                    @foreach ($usuariosParaFiltro as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- ── Resumen Global ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
            <p class="text-xs font-medium text-neutral-400 uppercase tracking-wider">Total Análisis</p>
            <p class="text-3xl font-bold text-neutral-900 mt-2">{{ number_format($resumen['total_analisis'] ?? 0) }}</p>
        </div>

        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
            <p class="text-xs font-medium text-neutral-400 uppercase tracking-wider">Tokens Prompt</p>
            <p class="text-3xl font-bold text-primary-800 mt-2">{{ number_format($resumen['total_tokens_prompt'] ?? 0) }}</p>
        </div>

        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
            <p class="text-xs font-medium text-neutral-400 uppercase tracking-wider">Tokens Respuesta</p>
            <p class="text-3xl font-bold text-primary-800 mt-2">{{ number_format($resumen['total_tokens_respuesta'] ?? 0) }}</p>
        </div>

        <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
            <p class="text-xs font-medium text-neutral-400 uppercase tracking-wider">Costo Estimado</p>
            <p class="text-3xl font-bold text-secondary-600 mt-2">${{ number_format($resumen['total_costo'] ?? 0, 4) }}</p>
        </div>
    </div>

    {{-- ── Tabla por usuario ── --}}
    <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-neutral-100">
            <h2 class="text-lg font-bold text-neutral-900">Consumo por usuario</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-50 text-left">
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-center">Análisis</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-right">Tokens Prompt</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-right">Tokens Respuesta</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-right">Total Tokens</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-right">Costo Est.</th>
                        <th class="px-6 py-3 text-xs font-medium text-neutral-400 uppercase tracking-wider text-center">Último Análisis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($porUsuario as $fila)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-6 py-4">
                                @if ($fila->requested_by_user_id)
                                    <span class="font-medium text-neutral-900">{{ $usuarios[$fila->requested_by_user_id] ?? 'Usuario #' . $fila->requested_by_user_id }}</span>
                                @else
                                    <span class="text-neutral-400 italic">Sin atribuir</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary-400/10 text-primary-800">
                                    {{ number_format($fila->total_analisis) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-neutral-600">{{ number_format($fila->tokens_prompt) }}</td>
                            <td class="px-6 py-4 text-right text-neutral-600">{{ number_format($fila->tokens_respuesta) }}</td>
                            <td class="px-6 py-4 text-right font-medium text-neutral-900">{{ number_format($fila->tokens_prompt + $fila->tokens_respuesta) }}</td>
                            <td class="px-6 py-4 text-right text-secondary-600 font-medium">${{ number_format($fila->costo_estimado, 4) }}</td>
                            <td class="px-6 py-4 text-center text-neutral-400 text-xs">
                                {{ $fila->ultimo_analisis ? \Carbon\Carbon::parse($fila->ultimo_analisis)->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-neutral-400">
                                No hay datos de consumo IA para el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($porUsuario->hasPages())
            <div class="px-6 py-4 border-t border-neutral-100">
                {{ $porUsuario->links() }}
            </div>
        @endif
    </div>
</div>
