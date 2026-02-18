<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">📋 Mis Procesos Notificados</h1>
                <p class="text-sm text-neutral-400 mt-2">
                    Historial de procesos SEACE que te fueron notificados. Puedes re-enviar a cualquier canal.
                </p>
            </div>
            @if($totalProcesos > 0)
                <span class="px-4 py-1.5 bg-primary-500/10 text-primary-800 rounded-full text-sm font-semibold whitespace-nowrap">
                    {{ $totalProcesos }} proceso(s)
                </span>
            @endif
        </div>
    </div>

    {{-- Feedback --}}
    @if($successMessage)
        <div class="bg-secondary-500/10 border border-secondary-500/30 text-secondary-600 px-6 py-3 rounded-3xl text-sm font-medium">
            ✅ {{ $successMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-3 rounded-3xl text-sm font-medium">
            ❌ {{ $errorMessage }}
        </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-neutral-900">{{ $totalProcesos }}</p>
            <p class="text-xs text-neutral-400 mt-1">Procesos Únicos</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-neutral-900">{{ $totalTelegram }}</p>
            <p class="text-xs text-neutral-400 mt-1">✈️ Telegram</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-neutral-900">{{ $totalWhatsapp }}</p>
            <p class="text-xs text-neutral-400 mt-1">📱 WhatsApp</p>
        </div>
        <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-neutral-900">{{ $totalEmail }}</p>
            <p class="text-xs text-neutral-400 mt-1">📧 Email</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Búsqueda por texto --}}
            <div class="md:col-span-2">
                <label class="block text-xs text-neutral-400 mb-1">Buscar proceso</label>
                <input
                    wire:model.live.debounce.300ms="busqueda"
                    type="text"
                    placeholder="Código, entidad, descripción..."
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
            </div>

            {{-- Filtro por canal --}}
            <div>
                <label class="block text-xs text-neutral-400 mb-1">Canal</label>
                <select
                    wire:model.live="filtroCanal"
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                    <option value="">Todos</option>
                    <option value="telegram">✈️ Telegram</option>
                    <option value="whatsapp">📱 WhatsApp</option>
                    <option value="email">📧 Email</option>
                </select>
            </div>

            {{-- Limpiar filtros --}}
            <div class="flex items-end">
                <button
                    wire:click="limpiarFiltros"
                    class="w-full px-4 py-2.5 bg-neutral-100 text-neutral-600 rounded-full text-sm font-medium hover:bg-neutral-200 transition-colors"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>

        {{-- Filtros de fecha --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div>
                <label class="block text-xs text-neutral-400 mb-1">Desde</label>
                <input
                    wire:model.live="filtroFechaDesde"
                    type="date"
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
            </div>
            <div>
                <label class="block text-xs text-neutral-400 mb-1">Hasta</label>
                <input
                    wire:model.live="filtroFechaHasta"
                    type="date"
                    class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
            </div>
        </div>
    </div>

    {{-- Lista de Procesos --}}
    @if($procesos->isEmpty())
        <div class="bg-white rounded-3xl shadow-soft p-12 border border-neutral-100 text-center">
            <div class="text-5xl mb-4">📭</div>
            <h3 class="text-lg font-semibold text-neutral-900">No hay procesos notificados</h3>
            <p class="text-sm text-neutral-400 mt-2">
                @if($busqueda || $filtroCanal || $filtroFechaDesde || $filtroFechaHasta)
                    No se encontraron procesos con los filtros aplicados.
                @else
                    Aún no has recibido notificaciones de procesos SEACE.
                    <br>Configura tus palabras clave en <a href="{{ route('suscriptores') }}" class="text-primary-500 hover:underline">Suscriptores</a>.
                @endif
            </p>
        </div>
    @else
        <div class="flex flex-col gap-4">
            @foreach($procesos as $proceso)
                <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-6 border border-neutral-100 hover:border-primary-400/30 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                        {{-- Info del proceso --}}
                        <div class="flex-1 min-w-0">
                            {{-- Código --}}
                            <div class="flex items-start gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-neutral-900 break-all leading-tight">
                                    {{ $proceso->codigo ?? 'Sin código' }}
                                </h3>
                                @if($proceso->objeto_contratacion)
                                    <span class="px-2.5 py-0.5 bg-primary-500/10 text-primary-800 rounded-full text-xs font-medium whitespace-nowrap">
                                        {{ $proceso->objeto_contratacion }}
                                    </span>
                                @endif
                            </div>

                            {{-- Entidad --}}
                            @if($proceso->entidad)
                                <p class="text-sm text-neutral-600 mt-1 truncate" title="{{ $proceso->entidad }}">
                                    🏛️ {{ $proceso->entidad }}
                                </p>
                            @endif

                            {{-- Descripción --}}
                            @if($proceso->descripcion)
                                <p class="text-xs text-neutral-400 mt-1 line-clamp-2">
                                    {{ $proceso->descripcion }}
                                </p>
                            @endif

                            {{-- Monto y Fecha --}}
                            <div class="flex flex-wrap gap-3 mt-2 text-xs text-neutral-400">
                                @if($proceso->monto_referencial)
                                    <span>💰 {{ $proceso->monto_referencial }}</span>
                                @endif
                                @if($proceso->fecha_publicacion)
                                    <span>📅 {{ $proceso->fecha_publicacion }}</span>
                                @endif
                            </div>

                            {{-- Canales notificados --}}
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($proceso->sends as $send)
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-neutral-50 border border-neutral-100 rounded-full text-xs">
                                        <span>{{ $send->canal_icon }}</span>
                                        <span class="font-medium text-neutral-700">{{ $send->canal_label }}</span>
                                        @if($send->subscription_label)
                                            <span class="text-neutral-400">→ {{ $send->subscription_label }}</span>
                                        @endif
                                        @if($send->recipient_id)
                                            <span class="text-neutral-300 font-mono text-[10px]">({{ Str::limit($send->recipient_id, 20) }})</span>
                                        @endif
                                        <span class="text-neutral-300">·</span>
                                        <span class="text-neutral-400">{{ $send->notified_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Keywords coincidentes (del primer envío) --}}
                            @php
                                $allKeywords = collect($proceso->sends)
                                    ->pluck('keywords_matched')
                                    ->filter()
                                    ->flatten()
                                    ->unique()
                                    ->values();
                            @endphp
                            @if($allKeywords->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($allKeywords as $kw)
                                        <span class="px-2 py-0.5 bg-secondary-500/10 text-secondary-600 rounded-full text-[10px] font-semibold">
                                            {{ $kw }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Botones de re-notificación --}}
                        <div class="flex flex-row lg:flex-col gap-2 shrink-0">
                            <button
                                wire:click="renotificarTelegram({{ $proceso->id }})"
                                wire:loading.attr="disabled"
                                wire:target="renotificarTelegram({{ $proceso->id }})"
                                class="flex items-center gap-1.5 px-3 py-2 bg-primary-500/10 text-primary-800 rounded-full text-xs font-medium hover:bg-primary-500/20 transition-colors disabled:opacity-50"
                                title="Re-enviar por Telegram"
                            >
                                <span wire:loading.remove wire:target="renotificarTelegram({{ $proceso->id }})">✈️</span>
                                <span wire:loading wire:target="renotificarTelegram({{ $proceso->id }})">
                                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                                <span class="hidden sm:inline">Telegram</span>
                            </button>

                            <button
                                wire:click="renotificarWhatsapp({{ $proceso->id }})"
                                wire:loading.attr="disabled"
                                wire:target="renotificarWhatsapp({{ $proceso->id }})"
                                class="flex items-center gap-1.5 px-3 py-2 bg-secondary-500/10 text-secondary-600 rounded-full text-xs font-medium hover:bg-secondary-500/20 transition-colors disabled:opacity-50"
                                title="Re-enviar por WhatsApp"
                            >
                                <span wire:loading.remove wire:target="renotificarWhatsapp({{ $proceso->id }})">📱</span>
                                <span wire:loading wire:target="renotificarWhatsapp({{ $proceso->id }})">
                                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                                <span class="hidden sm:inline">WhatsApp</span>
                            </button>

                            <button
                                wire:click="renotificarEmail({{ $proceso->id }})"
                                wire:loading.attr="disabled"
                                wire:target="renotificarEmail({{ $proceso->id }})"
                                class="flex items-center gap-1.5 px-3 py-2 bg-neutral-100 text-neutral-600 rounded-full text-xs font-medium hover:bg-neutral-200 transition-colors disabled:opacity-50"
                                title="Re-enviar por Email"
                            >
                                <span wire:loading.remove wire:target="renotificarEmail({{ $proceso->id }})">📧</span>
                                <span wire:loading wire:target="renotificarEmail({{ $proceso->id }})">
                                    <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                                <span class="hidden sm:inline">Email</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        <div class="mt-2">
            {{ $procesos->links() }}
        </div>
    @endif
</div>
