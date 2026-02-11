<div class="space-y-6 w-full max-w-none">
    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-primary-500 uppercase tracking-wider">Repositorio Inteligente</p>
            <h1 class="text-3xl font-bold text-neutral-900 mt-1">TDR Cache Manager</h1>
            <p class="text-sm text-neutral-500 mt-2">Controla los TDR descargados, evita gastos innecesarios en el LLM y vuelve a analizarlos cuando lo necesites.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full lg:w-auto">
            <div class="bg-primary-500/10 rounded-2xl px-5 py-3 text-center">
                <p class="text-xs text-primary-500 font-medium uppercase tracking-wider">Archivos Cacheados</p>
                <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="bg-secondary-500/10 rounded-2xl px-5 py-3 text-center">
                <p class="text-xs text-neutral-900 font-medium uppercase tracking-wider">Con IA</p>
                <p class="text-2xl font-bold text-neutral-900">{{ number_format($stats['analizados']) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-soft p-6 border border-neutral-100">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-5">
                <label class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Buscar</label>
                <div class="mt-2 relative">
                    <input type="text" wire:model.live.debounce.500ms="busqueda" placeholder="Entidad, proceso o nombre de archivo" class="w-full rounded-full border border-neutral-200 bg-white px-4 py-2.5 text-sm text-neutral-700 focus:border-primary-500 focus:ring-primary-500" />
                    <svg class="w-4 h-4 text-neutral-400 absolute right-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div class="lg:col-span-3">
                <label class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Estado del análisis</label>
                <select wire:model.live="estadoAnalisis" class="mt-2 w-full rounded-full border border-neutral-200 bg-white px-4 py-2.5 text-sm text-neutral-700 focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Todos</option>
                    <option value="exitoso">Exitoso</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="fallido">Fallido</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Resultados</label>
                <select wire:model.live="perPage" class="mt-2 w-full rounded-full border border-neutral-200 bg-white px-4 py-2.5 text-sm text-neutral-700 focus:border-primary-500 focus:ring-primary-500">
                    <option value="10">10 por pagina</option>
                    <option value="20">20 por pagina</option>
                    <option value="30">30 por pagina</option>
                    <option value="50">50 por pagina</option>
                </select>
            </div>
            <div class="lg:col-span-2 bg-neutral-50 rounded-2xl px-4 py-3 flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Solo con IA</p>
                    <p class="text-xs text-neutral-600">Mostrar archivos con analisis guardado</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="soloConAnalisis" class="sr-only peer">
                    <span class="w-11 h-6 bg-neutral-200 rounded-full peer peer-checked:bg-primary-500 transition"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 bg-white rounded-full shadow-soft transition peer-checked:translate-x-5"></span>
                </label>
            </div>
        </div>
    </div>

    @if (session()->has('tdr_repo_success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4 text-sm text-neutral-900">
            ✅ {{ session('tdr_repo_success') }}
        </div>
    @endif

    @error('tdrRepo')
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4 text-sm text-neutral-900">
            ❌ {{ $message }}
        </div>
    @enderror

    <div class="space-y-4">
        @forelse ($archivos as $archivo)
            @php
                $analisis = $archivo->ultimoAnalisisExitoso;
                $tamano = $archivo->tamano_bytes ? number_format($archivo->tamano_bytes / 1024, 2) . ' KB' : '—';
            @endphp
            <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-6 space-y-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">{{ $archivo->codigo_proceso ?? 'Proceso no registrado' }}</p>
                        <h2 class="text-xl font-bold text-neutral-900">{{ $archivo->nombre_original }}</h2>
                        <p class="text-sm text-neutral-500 mt-1">{{ $archivo->entidad ?? 'Entidad no registrada' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $archivo->hasStoredFile() ? 'bg-secondary-500/10 text-secondary-500' : 'bg-primary-500/10 text-primary-500' }}">
                            {{ $archivo->hasStoredFile() ? 'En caché' : 'Sin archivo' }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ match($analisis->estado ?? null) {
                                'exitoso' => 'bg-secondary-500/10 text-secondary-500',
                                'fallido' => 'bg-primary-500/10 text-primary-500',
                                'pendiente' => 'bg-neutral-200 text-neutral-600',
                                default => 'bg-neutral-200 text-neutral-600'
                            } }}">
                            {{ ucfirst($analisis->estado ?? 'Sin análisis') }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm text-neutral-600">
                    <div>
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Tamaño</p>
                        <p class="font-semibold text-neutral-900">{{ $tamano }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Descargado</p>
                        <p class="font-semibold text-neutral-900">{{ optional($archivo->descargado_en)->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Último análisis</p>
                        <p class="font-semibold text-neutral-900">{{ optional($analisis?->analizado_en)->format('d/m/Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-neutral-400 uppercase tracking-wider">Ubicación</p>
                        <p class="font-semibold text-neutral-900 break-all text-xs">{{ $archivo->storage_path ?? '—' }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm text-neutral-500">
                        {{ $analisis?->monto_referencial_text ? '💰 ' . $analisis->monto_referencial_text : 'Sin monto referencial detectado' }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="descargar({{ $archivo->id }})" wire:loading.attr="disabled" class="px-4 py-2 rounded-full text-sm font-semibold bg-neutral-900 text-white hover:bg-neutral-700 transition disabled:opacity-40">📥 Descargar</button>
                        <button wire:click="reanalizar({{ $archivo->id }})" wire:loading.attr="disabled" class="px-4 py-2 rounded-full text-sm font-semibold bg-primary-500 text-white hover:bg-primary-400 transition disabled:opacity-40">🔁 Reanalizar</button>
                    </div>
                </div>

                @if ($analisis?->resumen)
                    <details class="bg-neutral-50 rounded-2xl p-4 text-sm text-neutral-600 w-full max-w-full">
                        <summary class="cursor-pointer font-semibold text-neutral-900">Ver resumen IA</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-neutral-100 p-4 rounded-2xl w-full max-w-full overflow-x-auto whitespace-pre-wrap break-words">{{ json_encode($analisis->resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-3xl shadow-soft border border-dashed border-neutral-200 p-8 text-center">
                <p class="text-xl font-semibold text-neutral-900">Aún no hay archivos cacheados</p>
                <p class="text-sm text-neutral-500 mt-2">Ejecuta un análisis desde el dashboard o Telegram para comenzar a llenar este repositorio.</p>
            </div>
        @endforelse
    </div>

    @if ($archivos->hasPages())
        @php
            $currentPage = $archivos->currentPage();
            $lastPage = $archivos->lastPage();
            $startPage = max($currentPage - 1, 1);
            $endPage = min($currentPage + 1, $lastPage);
        @endphp
        <div class="bg-white rounded-3xl shadow-soft border border-neutral-100 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <p class="text-xs text-neutral-500">
                Mostrando {{ $archivos->firstItem() ?? 0 }} - {{ $archivos->lastItem() ?? 0 }} de {{ $archivos->total() }} archivos
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    wire:click="previousPage"
                    @disabled($currentPage === 1)
                    class="px-3 py-2 rounded-full text-xs font-semibold border border-neutral-200 text-neutral-600 hover:bg-neutral-100 transition disabled:opacity-40"
                >Anterior</button>

                @if ($startPage > 1)
                    <button type="button" wire:click="gotoPage(1)" class="px-3 py-2 rounded-full text-xs font-semibold border border-neutral-200 text-neutral-600 hover:bg-neutral-100 transition">1</button>
                    @if ($startPage > 2)
                        <span class="px-2 text-xs text-neutral-400">...</span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    <button
                        type="button"
                        wire:click="gotoPage({{ $page }})"
                        class="px-3 py-2 rounded-full text-xs font-semibold border transition {{ $page === $currentPage ? 'border-primary-500 bg-primary-500 text-white' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-100' }}"
                    >{{ $page }}</button>
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="px-2 text-xs text-neutral-400">...</span>
                    @endif
                    <button type="button" wire:click="gotoPage({{ $lastPage }})" class="px-3 py-2 rounded-full text-xs font-semibold border border-neutral-200 text-neutral-600 hover:bg-neutral-100 transition">{{ $lastPage }}</button>
                @endif

                <button
                    type="button"
                    wire:click="nextPage"
                    @disabled($currentPage === $lastPage)
                    class="px-3 py-2 rounded-full text-xs font-semibold border border-neutral-200 text-neutral-600 hover:bg-neutral-100 transition disabled:opacity-40"
                >Siguiente</button>
            </div>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('descargar-archivo', (event) => {
        const link = document.createElement('a');
        link.href = event.url;
        link.download = '';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>
@endscript
