<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">👥 Suscriptores Telegram</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Administra los Chat IDs que reciben notificaciones y compatibilidad.
        </p>
    </div>

    @if(session()->has('success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">❌ {{ session('error') }}</p>
        </div>
    @endif

    @if(!$telegramEnabled)
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">
                Configura el Bot de Telegram en la vista de Configuracion para habilitar notificaciones.
            </p>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Suscriptores que recibiran notificaciones
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Puedes registrar hasta {{ $maxSuscriptores }} suscriptores por cuenta. Cada uno recibira alertas en su chat de Telegram.
                </p>
            </div>

            @if(!$showForm && $canAddMore)
                <button wire:click="toggleForm"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar suscriptor
                </button>
            @elseif(!$showForm && !$canAddMore)
                <span class="flex-shrink-0 px-4 py-2 bg-neutral-100 text-neutral-500 rounded-full text-xs font-medium">
                    Limite alcanzado ({{ $maxSuscriptores }}/{{ $maxSuscriptores }})
                </span>
            @endif
        </div>

        @if($showForm || $editando_suscripcion_id)
        <div class="bg-neutral-50 rounded-2xl p-4 sm:p-6 mb-6"
             x-data
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-neutral-900">
                    {{ $editando_suscripcion_id ? '✏️ Editar Suscriptor' : '➕ Nuevo Suscriptor' }}
                </h3>
                <button wire:click="toggleForm"
                        class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600"
                        title="Cerrar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div x-data="{ showHelp: false }">
                    <label class="flex items-center gap-1.5 text-xs font-medium text-neutral-600 mb-2">
                        Chat ID <span class="text-red-500">*</span>
                        <button type="button" @click="showHelp = !showHelp" class="relative inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-500/15 hover:bg-primary-500/25 text-primary-600 transition-colors ring-1 ring-primary-500/30" title="Como obtener tu Chat ID">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/></svg>
                        </button>
                    </label>
                    <input type="text" wire:model="nuevo_chat_id"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Ej: 5203441622">
                    @error('nuevo_chat_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Modal: Como obtener tu Chat ID --}}
                    <div x-show="showHelp" x-cloak @click.outside="showHelp = false" @keydown.escape.window="showHelp = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4">
                        <div @click.stop class="bg-white rounded-3xl shadow-xl w-full max-w-sm p-6 relative">
                            <button @click="showHelp = false" class="absolute top-4 right-4 p-1 hover:bg-neutral-100 rounded-full transition-colors text-neutral-400 hover:text-neutral-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>

                            <div class="flex items-center gap-2.5 mb-5">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary-500/10">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/></svg>
                                </span>
                                <h3 class="text-sm font-bold text-neutral-900">Como obtener tu Chat ID</h3>
                            </div>

                            <ol class="space-y-4" x-data="{ copied: null }">
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-primary-500 text-white text-xs font-bold flex items-center justify-center">1</span>
                                    <div>
                                        <p class="text-xs font-semibold text-neutral-900">Abre Telegram</p>
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Busca al bot
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('@userinfobot'); copied = 'bot'; setTimeout(() => copied = null, 2000)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-neutral-100 hover:bg-primary-500/10 rounded text-[11px] font-mono text-primary-600 cursor-pointer transition-colors"
                                                title="Clic para copiar">
                                                <span x-text="copied === 'bot' ? '✓ Copiado!' : '@userinfobot'"></span>
                                                <svg x-show="copied !== 'bot'" class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                            </button>
                                        </p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-primary-500 text-white text-xs font-bold flex items-center justify-center">2</span>
                                    <div>
                                        <p class="text-xs font-semibold text-neutral-900">Inicia la conversacion</p>
                                        <p class="text-[11px] text-neutral-500 mt-0.5">Envia el comando
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('/start'); copied = 'cmd'; setTimeout(() => copied = null, 2000)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-neutral-100 hover:bg-primary-500/10 rounded text-[11px] font-mono text-primary-600 cursor-pointer transition-colors"
                                                title="Clic para copiar">
                                                <span x-text="copied === 'cmd' ? '✓ Copiado!' : '/start'"></span>
                                                <svg x-show="copied !== 'cmd'" class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                            </button>
                                        </p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="flex-shrink-0 w-7 h-7 rounded-full bg-secondary-500 text-white text-xs font-bold flex items-center justify-center">3</span>
                                    <div>
                                        <p class="text-xs font-semibold text-neutral-900">Copia tu Chat ID</p>
                                        <p class="text-[11px] text-neutral-500 mt-0.5">El bot respondera con un numero como <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-[11px] font-mono">5203441622</code>. Pegalo aqui.</p>
                                    </div>
                                </li>
                            </ol>

                            <button @click="showHelp = false" class="mt-5 w-full py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-full text-xs font-medium transition-colors">Entendido</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Nombre (opcional)
                    </label>
                    <input type="text" wire:model="nuevo_nombre"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Juan Perez">
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Username (opcional)
                    </label>
                    <input type="text" wire:model="nuevo_username"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="@juanperez">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-medium text-neutral-600 mb-2">
                    Perfil de tu empresa / rubro <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="nuevo_company_copy"
                          class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm min-h-[110px]"
                          placeholder="Ej: Somos una empresa de tecnologia especializada en cableado estructurado, redes LAN y desarrollo de software web. Atendemos entidades publicas en Lima y Callao."></textarea>
                <p class="text-[11px] text-neutral-500 mt-1">La IA usa esta descripcion para calcular que tan compatible es cada licitacion con tu empresa. Mientras mas clara y especifica sea, mejores resultados obtendras.</p>
                @error('nuevo_company_copy')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col gap-0.5">
                        <div class="flex items-center gap-2 text-xs font-medium text-neutral-600">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-500/10 text-primary-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </span>
                            <span>Palabras clave de interes</span>
                        </div>
                        <p class="text-[11px] text-neutral-400 ml-7">El bot te notificara solo los procesos cuya descripcion contenga alguna de estas palabras.</p>
                    </div>
                    <span class="text-[11px] text-neutral-500 font-semibold">{{ count($nuevo_keywords ?? []) }}/{{ $maxKeywords }} seleccionadas</span>
                </div>

                <div
                    class="relative space-y-4"
                    x-data="{
                        limit: {{ $maxKeywords }},
                        selected: @entangle('nuevo_keywords').live,
                        showLimitToast: false,
                        toastTimeout: null,
                        triggerLimitToast() {
                            this.showLimitToast = true;
                            clearTimeout(this.toastTimeout);
                            this.toastTimeout = setTimeout(() => this.showLimitToast = false, 4500);
                        }
                    }"
                >
                    <div
                        x-cloak
                        x-show="showLimitToast"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="translate-y-2 opacity-0"
                        x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute -top-5 right-0 z-10 flex items-start gap-3 rounded-2xl bg-white border border-primary-200 shadow-soft px-4 py-3 max-w-sm"
                    >
                        <div class="w-8 h-8 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5.5a7 7 0 100 14 7 7 0 000-14z" />
                            </svg>
                        </div>
                        <div class="text-xs text-neutral-700">
                            <p class="font-semibold text-neutral-900">Maximo {{ $maxKeywords }} palabras clave</p>
                            <p>Limitamos la seleccion para mantener los avisos enfocados y evitar spam del bot.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Catalogo general</p>
                                <div class="relative flex-1">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="keywordSearch"
                                           class="w-full pl-9 pr-3 py-2 text-xs rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none"
                                           placeholder="Buscar palabra clave">
                                    <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="max-h-60 overflow-y-auto pr-1 flex flex-wrap gap-2">
                                @forelse($filteredKeywords as $keyword)
                                    <label wire:key="keyword-pill-{{ $keyword['id'] }}" class="cursor-pointer">
                                        <input
                                            type="checkbox"
                                            class="sr-only"
                                            value="{{ $keyword['id'] }}"
                                            wire:model.live="nuevo_keywords"
                                            x-on:click="if(selected.length >= limit && !selected.includes({{ $keyword['id'] }})) { $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                            :disabled="selected.length >= limit && !selected.includes({{ $keyword['id'] }})"
                                        >
                                        <span class="px-3 py-1 rounded-full border text-xs font-semibold transition-all {{ in_array($keyword['id'], $nuevo_keywords ?? [], true) ? 'bg-secondary-500 text-white border-secondary-500 shadow' : 'bg-white text-neutral-600 border-neutral-200 hover:border-primary-400' }}">
                                            {{ $keyword['nombre'] }}
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-xs text-neutral-500">No encontramos coincidencias con "{{ $keywordSearch }}".</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Seleccionadas</p>
                                @if(count($nuevo_keywords ?? []))
                                    <span class="text-[11px] text-neutral-500">Haz clic para quitar</span>
                                @endif
                            </div>

                            <div class="min-h-[120px] max-h-60 overflow-y-auto pr-1 space-y-2">
                                @forelse($nuevo_keywords as $keywordId)
                                    @php($tag = $keywordDictionary->get($keywordId))
                                    <div wire:key="selected-keyword-{{ $keywordId }}"
                                         class="w-full rounded-2xl border border-secondary-500/50 bg-secondary-500/5 px-3 py-2 flex items-center justify-between gap-3">
                                        <div class="flex flex-col text-left">
                                            <p class="text-xs font-semibold text-neutral-800 leading-none">{{ $tag['nombre'] ?? 'Keyword #' . $keywordId }}</p>
                                            <span class="text-[10px] text-neutral-500">ID #{{ $keywordId }}</span>
                                        </div>
                                        <button type="button"
                                            wire:click="quitarKeyword({{ $keywordId }})"
                                            class="w-7 h-7 flex items-center justify-center rounded-full border border-primary-500/60 text-primary-500 hover:bg-primary-500/10 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-xs text-neutral-500">Selecciona al menos una palabra clave relevante.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @error('nuevo_keywords.*')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-col md:flex-row gap-2">
                        <input type="text" wire:model.live="nuevo_keyword_manual"
                               class="flex-1 px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                               placeholder="No existe en el catalogo? Agregala aqui">
                        <button type="button"
                                wire:click="agregarKeywordManual"
                                wire:loading.attr="disabled"
                                wire:target="agregarKeywordManual"
                                x-on:click="if(selected.length >= limit){ $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                class="px-4 py-2 bg-white border border-primary-500 text-primary-600 rounded-full text-xs font-semibold hover:bg-primary-50 transition-all disabled:opacity-60">
                            <span wire:loading.remove wire:target="agregarKeywordManual">Guardar en catalogo y seleccionar</span>
                            <span wire:loading wire:target="agregarKeywordManual">Guardando...</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-neutral-500">Las palabras nuevas quedaran disponibles para todos los suscriptores.</p>
                </div>
            </div>

                <div class="flex flex-col gap-4 mt-6">
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="nuevo_activo" class="sr-only peer">
                        <div class="w-11 h-6 bg-neutral-200 rounded-full peer-focus:ring-4 peer-focus:ring-primary-500/20 peer-checked:bg-primary-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                    <div>
                        <p class="text-xs font-semibold text-neutral-900">{{ $nuevo_activo ? 'Suscriptor activo' : 'Suscriptor inactivo' }}</p>
                        <p class="text-[11px] text-neutral-500">Puedes pausar notificaciones sin eliminar al contacto.</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-end">
                    <div class="flex gap-2">
                        <button wire:click="toggleForm"
                                class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-xs hover:bg-neutral-200 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="agregarSuscriptor"
                                class="px-6 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-xs hover:opacity-90 transition-all shadow-md">
                                {{ $editando_suscripcion_id ? '💾 Guardar cambios' : '➕ Registrar suscriptor' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($suscripciones->isEmpty())
            <div class="bg-neutral-50 rounded-2xl p-8 text-center border-2 border-dashed border-neutral-200">
                <svg class="w-16 h-16 mx-auto mb-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-bold text-neutral-900 mb-1">No hay suscriptores</p>
                <p class="text-xs text-neutral-400">Agrega el primer Chat ID para empezar a recibir notificaciones.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($suscripciones as $suscripcion)
                    <div class="bg-neutral-50 rounded-2xl p-3 sm:p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:bg-neutral-100 transition-colors {{ $isAdmin && $suscripcion->user ? 'ring-1 ring-neutral-200' : '' }}">
                        <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                            <div class="flex-shrink-0">
                                @if($suscripcion->activo)
                                    <div class="w-10 h-10 bg-secondary-500/20 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-neutral-200 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-neutral-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                @if($isAdmin && $suscripcion->user)
                                    <div class="flex items-center gap-1.5 mb-1.5">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="text-[11px] font-semibold text-primary-600">{{ $suscripcion->user->name }}</span>
                                        <span class="text-[11px] text-neutral-400">({{ $suscripcion->user->email }})</span>
                                    </div>
                                @endif
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1">
                                    <p class="text-sm font-bold text-neutral-900 font-mono">{{ $suscripcion->chat_id }}</p>
                                    @if($suscripcion->activo)
                                        <span class="px-2 py-0.5 bg-secondary-500/20 text-primary-500 border border-secondary-500/30 rounded-full text-xs font-semibold">✓ Activo</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-neutral-200 text-neutral-600 rounded-full text-xs font-semibold">✗ Inactivo</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:gap-4 text-xs text-neutral-600">
                                    @if($suscripcion->nombre)
                                        <span>👤 {{ $suscripcion->nombre }}</span>
                                    @endif
                                    @if($suscripcion->username)
                                        <span>📱 {{ $suscripcion->username }}</span>
                                    @endif
                                    <span>📊 {{ number_format($suscripcion->notificaciones_recibidas) }} notificaciones</span>
                                    @if($suscripcion->ultima_notificacion_at)
                                        <span>🕐 Ultima: {{ $suscripcion->ultima_notificacion_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                                @if($suscripcion->keywords->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        @foreach($suscripcion->keywords as $keyword)
                                            <span class="text-[11px] px-2 py-0.5 rounded-full border border-secondary-500/40 text-primary-500 bg-secondary-500/10">
                                                {{ $keyword->nombre }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if($suscripcion->company_copy)
                                    <p class="text-xs text-neutral-600 leading-relaxed mt-3 border-l-2 border-primary-200 pl-3">
                                        "{{ $suscripcion->company_copy }}"
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                            <button wire:click="toggleActivoSuscriptor({{ $suscripcion->id }})"
                                    title="{{ $suscripcion->activo ? 'Desactivar' : 'Activar' }}"
                                    class="p-2 hover:bg-white rounded-full transition-colors">
                                @if($suscripcion->activo)
                                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    </svg>
                                @endif
                            </button>

                            <button wire:click="probarNotificacionSuscriptor({{ $suscripcion->id }})"
                                    title="Enviar prueba"
                                    class="p-2 hover:bg-white rounded-full transition-colors">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>

                            <button wire:click="editarSuscriptor({{ $suscripcion->id }})"
                                    title="Editar"
                                    class="p-2 hover:bg-white rounded-full transition-colors">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            <button wire:click="eliminarSuscriptor({{ $suscripcion->id }})"
                                    onclick="return confirm('¿Eliminar este suscriptor?')"
                                    title="Eliminar"
                                    class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif


    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN: NOTIFICACIONES POR WHATSAPP
    ═══════════════════════════════════════════════════════════════ --}}

    @if(session()->has('wa_success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('wa_success') }}</p>
        </div>
    @endif

    @if(session()->has('wa_error'))
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('wa_error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    {{-- WhatsApp icon --}}
                    <svg class="w-6 h-6 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Notificaciones por WhatsApp
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Recibe alertas de nuevos procesos SEACE en tu WhatsApp con botones interactivos.
                    Solo se permite 1 numero por usuario.
                </p>
            </div>

            @if(!$whatsappSubscription && !$showWhatsAppForm)
                <button wire:click="toggleWhatsAppForm"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-[#25D366] to-[#128C7E] text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Activar WhatsApp
                </button>
            @endif
        </div>

        @if(!$whatsappEnabled)
            <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4 mb-6">
                <p class="text-sm text-neutral-900 font-medium">
                    El administrador debe configurar WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID en el servidor para habilitar este canal.
                </p>
            </div>
        @endif

        {{-- Formulario WhatsApp --}}
        @if($showWhatsAppForm)
            <div class="bg-neutral-50 rounded-2xl p-4 sm:p-6 mb-6"
                 x-data
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-neutral-900">
                        {{ $editando_wa_id ? '✏️ Editar suscripcion WhatsApp' : '📱 Configurar WhatsApp' }}
                    </h3>
                    <button wire:click="toggleWhatsAppForm"
                            class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600"
                            title="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Numero de telefono --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div x-data="{ showHelp: false }">
                        <label class="flex items-center gap-1.5 text-xs font-medium text-neutral-600 mb-2">
                            Numero de WhatsApp <span class="text-red-500">*</span>
                            <button type="button" @click="showHelp = !showHelp" class="relative inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#25D366]/15 hover:bg-[#25D366]/25 text-[#128C7E] transition-colors ring-1 ring-[#25D366]/30" title="Formato del numero">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/></svg>
                            </button>
                        </label>
                        <input type="text" wire:model="wa_phone_number"
                               class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 outline-none transition-all text-sm"
                               placeholder="Ej: 51987654321">
                        @error('wa_phone_number')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        {{-- Tooltip formato --}}
                        <div x-show="showHelp" x-cloak @click.outside="showHelp = false"
                             x-transition class="mt-2 bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft text-xs text-neutral-700 space-y-2">
                            <p class="font-bold text-neutral-900">Formato del numero</p>
                            <p>Ingresa el numero con codigo de pais, sin <code class="bg-neutral-100 px-1 rounded">+</code>, sin espacios ni guiones.</p>
                            <div class="space-y-1">
                                <p>🇵🇪 Peru: <code class="bg-neutral-100 px-1.5 py-0.5 rounded font-mono">51987654321</code></p>
                                <p>🇲🇽 Mexico: <code class="bg-neutral-100 px-1.5 py-0.5 rounded font-mono">521234567890</code></p>
                                <p>🇺🇸 USA: <code class="bg-neutral-100 px-1.5 py-0.5 rounded font-mono">11234567890</code></p>
                            </div>
                            <button @click="showHelp = false" class="mt-2 w-full py-1.5 bg-neutral-100 hover:bg-neutral-200 rounded-full text-xs font-medium transition-colors">Entendido</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-2">
                            Nombre (opcional)
                        </label>
                        <input type="text" wire:model="wa_nombre"
                               class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 outline-none transition-all text-sm"
                               placeholder="Ej: Juan Perez">
                    </div>
                </div>

                {{-- Company copy --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Perfil de tu empresa / rubro <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="wa_company_copy"
                              class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 outline-none transition-all text-sm min-h-[110px]"
                              placeholder="Ej: Somos una empresa de tecnologia especializada en cableado estructurado, redes LAN y desarrollo de software web. Atendemos entidades publicas en Lima y Callao."></textarea>
                    <p class="text-[11px] text-neutral-500 mt-1">La IA usa esta descripcion para calcular que tan compatible es cada licitacion con tu empresa.</p>
                    @error('wa_company_copy')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Keywords --}}
                <div class="mb-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center gap-2 text-xs font-medium text-neutral-600">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-[#25D366]/10 text-[#128C7E]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </span>
                                <span>Palabras clave de interes</span>
                            </div>
                            <p class="text-[11px] text-neutral-400 ml-7">Solo recibiras mensajes de WhatsApp de procesos que contengan alguna de estas palabras.</p>
                        </div>
                        <span class="text-[11px] text-neutral-500 font-semibold">{{ count($wa_keywords ?? []) }}/{{ $maxKeywords }} seleccionadas</span>
                    </div>

                    <div
                        class="relative space-y-4"
                        x-data="{
                            limit: {{ $maxKeywords }},
                            selected: @entangle('wa_keywords').live,
                            showLimitToast: false,
                            toastTimeout: null,
                            triggerLimitToast() {
                                this.showLimitToast = true;
                                clearTimeout(this.toastTimeout);
                                this.toastTimeout = setTimeout(() => this.showLimitToast = false, 4500);
                            }
                        }"
                    >
                        <div
                            x-cloak
                            x-show="showLimitToast"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="translate-y-2 opacity-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute -top-5 right-0 z-10 flex items-start gap-3 rounded-2xl bg-white border border-[#25D366]/30 shadow-soft px-4 py-3 max-w-sm"
                        >
                            <div class="w-8 h-8 rounded-full bg-[#25D366]/10 flex items-center justify-center text-[#128C7E]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5.5a7 7 0 100 14 7 7 0 000-14z" />
                                </svg>
                            </div>
                            <div class="text-xs text-neutral-700">
                                <p class="font-semibold text-neutral-900">Maximo {{ $maxKeywords }} palabras clave</p>
                                <p>Limitamos la seleccion para mantener los avisos enfocados.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            {{-- Catálogo general --}}
                            <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Catalogo general</p>
                                    <div class="relative flex-1">
                                        <input type="text"
                                               wire:model.live.debounce.300ms="wa_keyword_search"
                                               class="w-full pl-9 pr-3 py-2 text-xs rounded-full border border-neutral-200 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 outline-none"
                                               placeholder="Buscar palabra clave">
                                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="max-h-60 overflow-y-auto pr-1 flex flex-wrap gap-2">
                                    @forelse($filteredWaKeywords as $keyword)
                                        <label wire:key="wa-keyword-pill-{{ $keyword['id'] }}" class="cursor-pointer">
                                            <input
                                                type="checkbox"
                                                class="sr-only"
                                                value="{{ $keyword['id'] }}"
                                                wire:model.live="wa_keywords"
                                                x-on:click="if(selected.length >= limit && !selected.includes({{ $keyword['id'] }})) { $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                                :disabled="selected.length >= limit && !selected.includes({{ $keyword['id'] }})"
                                            >
                                            <span class="px-3 py-1 rounded-full border text-xs font-semibold transition-all {{ in_array($keyword['id'], $wa_keywords ?? [], true) ? 'bg-[#25D366] text-white border-[#25D366] shadow' : 'bg-white text-neutral-600 border-neutral-200 hover:border-[#25D366]' }}">
                                                {{ $keyword['nombre'] }}
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-neutral-500">No encontramos coincidencias con "{{ $wa_keyword_search }}".</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Seleccionadas --}}
                            <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Seleccionadas</p>
                                    @if(count($wa_keywords ?? []))
                                        <span class="text-[11px] text-neutral-500">Haz clic para quitar</span>
                                    @endif
                                </div>

                                <div class="min-h-[120px] max-h-60 overflow-y-auto pr-1 space-y-2">
                                    @forelse($wa_keywords as $keywordId)
                                        @php($tag = $keywordDictionary->get($keywordId))
                                        <div wire:key="wa-selected-keyword-{{ $keywordId }}"
                                             class="w-full rounded-2xl border border-[#25D366]/50 bg-[#25D366]/5 px-3 py-2 flex items-center justify-between gap-3">
                                            <div class="flex flex-col text-left">
                                                <p class="text-xs font-semibold text-neutral-800 leading-none">{{ $tag['nombre'] ?? 'Keyword #' . $keywordId }}</p>
                                                <span class="text-[10px] text-neutral-500">ID #{{ $keywordId }}</span>
                                            </div>
                                            <button type="button"
                                                wire:click="quitarWaKeyword({{ $keywordId }})"
                                                class="w-7 h-7 flex items-center justify-center rounded-full border border-[#128C7E]/60 text-[#128C7E] hover:bg-[#25D366]/10 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500">Selecciona al menos una palabra clave relevante.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @error('wa_keywords.*')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-col md:flex-row gap-2">
                            <input type="text" wire:model.live="wa_keyword_manual"
                                   wire:keydown.enter.prevent="agregarWaKeywordManual"
                                   class="flex-1 px-4 py-2 rounded-full border border-neutral-200 focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 outline-none text-sm"
                                   placeholder="No existe en el catalogo? Agregala aqui">
                            <button type="button"
                                    wire:click="agregarWaKeywordManual"
                                    wire:loading.attr="disabled"
                                    wire:target="agregarWaKeywordManual"
                                    x-on:click="if(selected.length >= limit){ $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                    class="px-4 py-2 bg-white border border-[#25D366] text-[#128C7E] rounded-full text-xs font-semibold hover:bg-[#25D366]/5 transition-all disabled:opacity-60">
                                <span wire:loading.remove wire:target="agregarWaKeywordManual">Guardar en catalogo y seleccionar</span>
                                <span wire:loading wire:target="agregarWaKeywordManual">Guardando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Toggle activo + Botones --}}
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="wa_activo" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 rounded-full peer-focus:ring-4 peer-focus:ring-[#25D366]/20 peer-checked:bg-[#25D366] after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <div>
                            <p class="text-xs font-semibold text-neutral-900">{{ $wa_activo ? 'Suscripcion activa' : 'Suscripcion inactiva' }}</p>
                            <p class="text-[11px] text-neutral-500">Puedes pausar notificaciones sin eliminar la configuracion.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="guardarWhatsAppSubscription"
                                class="px-5 py-2 bg-[#25D366] text-white rounded-full text-sm font-medium hover:bg-[#128C7E] transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar
                        </button>
                        <button wire:click="toggleWhatsAppForm"
                                class="px-4 py-2 bg-neutral-200 text-neutral-600 rounded-full text-sm font-medium hover:bg-neutral-300 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- WhatsApp existente --}}
        @if($whatsappSubscription)
            <div class="bg-neutral-50 rounded-2xl p-3 sm:p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:bg-neutral-100 transition-colors">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                    <div class="flex-shrink-0">
                        @if($whatsappSubscription->activo)
                            <div class="w-10 h-10 bg-[#25D366]/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#25D366]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-neutral-200 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-neutral-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1">
                            <p class="text-sm font-bold text-neutral-900 font-mono">+{{ $whatsappSubscription->phone_number }}</p>
                            @if($whatsappSubscription->activo)
                                <span class="px-2 py-0.5 bg-[#25D366]/20 text-[#128C7E] border border-[#25D366]/30 rounded-full text-xs font-semibold">✓ Activo</span>
                            @else
                                <span class="px-2 py-0.5 bg-neutral-200 text-neutral-600 rounded-full text-xs font-semibold">✗ Inactivo</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:gap-4 text-xs text-neutral-600">
                            @if($whatsappSubscription->nombre)
                                <span>👤 {{ $whatsappSubscription->nombre }}</span>
                            @endif
                            <span>📊 {{ number_format($whatsappSubscription->notificaciones_recibidas) }} notificaciones</span>
                            @if($whatsappSubscription->ultima_notificacion_at)
                                <span>🕐 Ultima: {{ $whatsappSubscription->ultima_notificacion_at->diffForHumans() }}</span>
                            @endif
                            <span>📅 Desde: {{ $whatsappSubscription->created_at->format('d/m/Y') }}</span>
                        </div>
                        @if($whatsappSubscription->keywords->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                @foreach($whatsappSubscription->keywords as $keyword)
                                    <span class="text-[11px] px-2 py-0.5 rounded-full border border-[#25D366]/40 text-[#128C7E] bg-[#25D366]/10">
                                        {{ $keyword->nombre }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($whatsappSubscription->company_copy)
                            <p class="text-xs text-neutral-600 leading-relaxed mt-3 border-l-2 border-[#25D366]/40 pl-3">
                                "{{ $whatsappSubscription->company_copy }}"
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                    {{-- Toggle activo --}}
                    <button wire:click="toggleWhatsAppActivo"
                            title="{{ $whatsappSubscription->activo ? 'Desactivar' : 'Activar' }}"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        @if($whatsappSubscription->activo)
                            <svg class="w-4 h-4 text-[#25D366]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        @endif
                    </button>

                    {{-- Enviar prueba --}}
                    <button wire:click="probarWhatsAppNotificacion"
                            wire:loading.attr="disabled"
                            wire:target="probarWhatsAppNotificacion"
                            title="Enviar mensaje de prueba"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-[#25D366]" wire:loading.class="animate-pulse" wire:target="probarWhatsAppNotificacion" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>

                    {{-- Editar --}}
                    <button wire:click="toggleWhatsAppForm"
                            title="Editar"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>

                    {{-- Eliminar --}}
                    <button wire:click="eliminarWhatsAppSubscription"
                            onclick="return confirm('¿Eliminar la suscripcion de WhatsApp?')"
                            title="Eliminar"
                            class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        @elseif(!$showWhatsAppForm)
            <div class="text-center py-8">
                <div class="w-14 h-14 mx-auto bg-[#25D366]/10 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-[#25D366]/40" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900 mb-1">Sin notificaciones de WhatsApp</h3>
                <p class="text-xs text-neutral-500">Activa las notificaciones para recibir alertas de nuevos procesos en tu WhatsApp.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN: NOTIFICACIONES POR CORREO
    ═══════════════════════════════════════════════════════════════ --}}

    @if(session()->has('email_success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('email_success') }}</p>
        </div>
    @endif

    @if(session()->has('email_error'))
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('email_error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Notificaciones por correo
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Recibe alertas de nuevos procesos SEACE directamente en tu correo electronico.
                    Solo se permite 1 correo por usuario.
                </p>
            </div>

            @if(!$emailSubscription && !$showEmailForm)
                <button wire:click="toggleEmailForm"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Activar notificaciones
                </button>
            @endif
        </div>

        {{-- Formulario de correo --}}
        @if($showEmailForm)
            <div class="bg-neutral-50 rounded-2xl p-4 sm:p-6 mb-6"
                 x-data="{ notificarTodo: @entangle('email_notificar_todo').live }"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-neutral-900">
                        {{ $editando_email_id ? '✏️ Editar correo de notificación' : '📧 Configurar correo' }}
                    </h3>
                    <button wire:click="toggleEmailForm"
                            class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600"
                            title="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Campo de correo --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Correo electronico <span class="text-red-500">*</span>
                    </label>
                    <input type="email" wire:model="email_notificacion"
                           class="w-full sm:max-w-md px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="tucorreo@ejemplo.com">
                    @error('email_notificacion')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-neutral-400 mt-1.5">
                        Por defecto se usa tu correo de cuenta. Puedes cambiarlo a otro si prefieres.
                    </p>
                </div>

                {{-- Toggle: Recibir todo vs Filtrar por keywords --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-neutral-600 mb-3">Tipo de notificación</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button"
                                @click="notificarTodo = true"
                                :class="notificarTodo
                                    ? 'bg-primary-500/10 border-primary-500 text-primary-500'
                                    : 'bg-white border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Recibir todos los procesos
                        </button>
                        <button type="button"
                                @click="notificarTodo = false"
                                :class="!notificarTodo
                                    ? 'bg-primary-500/10 border-primary-500 text-primary-500'
                                    : 'bg-white border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filtrar por palabras clave
                        </button>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-2">
                        <template x-if="notificarTodo">
                            <span>Recibiras un correo por cada nuevo proceso publicado en el SEACE.</span>
                        </template>
                        <template x-if="!notificarTodo">
                            <span>Solo recibiras correos de procesos cuya descripcion contenga alguna de tus palabras clave.</span>
                        </template>
                    </p>
                </div>

                {{-- Selector de keywords (solo si filtrar por keywords) --}}
                <div x-show="!notificarTodo" x-collapse class="mb-5">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center gap-2 text-xs font-medium text-neutral-600">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-500/10 text-primary-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </span>
                                <span>Palabras clave para notificaciones por correo</span>
                            </div>
                            <p class="text-[11px] text-neutral-400 ml-7">Solo recibiras correos de procesos cuya descripcion contenga alguna de estas palabras.</p>
                        </div>
                        <span class="text-[11px] text-neutral-500 font-semibold">{{ count($email_keywords ?? []) }}/{{ $maxKeywords }} seleccionadas</span>
                    </div>

                    <div
                        class="relative space-y-4 mt-4"
                        x-data="{
                            limit: {{ $maxKeywords }},
                            selected: @entangle('email_keywords').live,
                            showLimitToast: false,
                            toastTimeout: null,
                            triggerLimitToast() {
                                this.showLimitToast = true;
                                clearTimeout(this.toastTimeout);
                                this.toastTimeout = setTimeout(() => this.showLimitToast = false, 4500);
                            }
                        }"
                    >
                        {{-- Toast límite --}}
                        <div
                            x-cloak
                            x-show="showLimitToast"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="translate-y-2 opacity-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute -top-5 right-0 z-10 flex items-start gap-3 rounded-2xl bg-white border border-primary-200 shadow-soft px-4 py-3 max-w-sm"
                        >
                            <div class="w-8 h-8 rounded-full bg-primary-500/10 flex items-center justify-center text-primary-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5.5a7 7 0 100 14 7 7 0 000-14z" />
                                </svg>
                            </div>
                            <div class="text-xs text-neutral-700">
                                <p class="font-semibold text-neutral-900">Maximo {{ $maxKeywords }} palabras clave</p>
                                <p>Limitamos la seleccion para mantener los avisos enfocados y evitar spam.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            {{-- Catálogo general --}}
                            <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Catalogo general</p>
                                    <div class="relative flex-1">
                                        <input type="text"
                                               wire:model.live.debounce.300ms="email_keyword_search"
                                               class="w-full pl-9 pr-3 py-2 text-xs rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none"
                                               placeholder="Buscar palabra clave">
                                        <svg class="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="max-h-60 overflow-y-auto pr-1 flex flex-wrap gap-2">
                                    @forelse($filteredEmailKeywords as $keyword)
                                        <label wire:key="email-keyword-pill-{{ $keyword['id'] }}" class="cursor-pointer">
                                            <input
                                                type="checkbox"
                                                class="sr-only"
                                                value="{{ $keyword['id'] }}"
                                                wire:model.live="email_keywords"
                                                x-on:click="if(selected.length >= limit && !selected.includes({{ $keyword['id'] }})) { $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                                :disabled="selected.length >= limit && !selected.includes({{ $keyword['id'] }})"
                                            >
                                            <span class="px-3 py-1 rounded-full border text-xs font-semibold transition-all {{ in_array($keyword['id'], $email_keywords ?? [], true) ? 'bg-secondary-500 text-white border-secondary-500 shadow' : 'bg-white text-neutral-600 border-neutral-200 hover:border-primary-400' }}">
                                                {{ $keyword['nombre'] }}
                                            </span>
                                        </label>
                                    @empty
                                        <p class="text-xs text-neutral-500">No encontramos coincidencias con "{{ $email_keyword_search }}".</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Seleccionadas --}}
                            <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Seleccionadas</p>
                                    @if(count($email_keywords ?? []))
                                        <span class="text-[11px] text-neutral-500">Haz clic para quitar</span>
                                    @endif
                                </div>

                                <div class="min-h-[120px] max-h-60 overflow-y-auto pr-1 space-y-2">
                                    @forelse($email_keywords as $keywordId)
                                        @php($tag = $keywordDictionary->get($keywordId))
                                        <div wire:key="email-selected-keyword-{{ $keywordId }}"
                                             class="w-full rounded-2xl border border-secondary-500/50 bg-secondary-500/5 px-3 py-2 flex items-center justify-between gap-3">
                                            <div class="flex flex-col text-left">
                                                <p class="text-xs font-semibold text-neutral-800 leading-none">{{ $tag['nombre'] ?? 'Keyword #' . $keywordId }}</p>
                                                <span class="text-[10px] text-neutral-500">ID #{{ $keywordId }}</span>
                                            </div>
                                            <button type="button"
                                                wire:click="quitarEmailKeyword({{ $keywordId }})"
                                                class="w-7 h-7 flex items-center justify-center rounded-full border border-primary-500/60 text-primary-500 hover:bg-primary-500/10 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @empty
                                        <p class="text-xs text-neutral-500">Selecciona al menos una palabra clave relevante.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @error('email_keywords.*')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-col md:flex-row gap-2">
                            <input type="text" wire:model.live="email_keyword_manual"
                                   wire:keydown.enter.prevent="agregarEmailKeywordManual"
                                   class="flex-1 px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                                   placeholder="No existe en el catalogo? Agregala aqui">
                            <button type="button"
                                    wire:click="agregarEmailKeywordManual"
                                    wire:loading.attr="disabled"
                                    wire:target="agregarEmailKeywordManual"
                                    x-on:click="if(selected.length >= limit){ $event.preventDefault(); $event.stopImmediatePropagation(); triggerLimitToast(); }"
                                    class="px-4 py-2 bg-white border border-primary-500 text-primary-600 rounded-full text-xs font-semibold hover:bg-primary-50 transition-all disabled:opacity-60">
                                <span wire:loading.remove wire:target="agregarEmailKeywordManual">Guardar en catalogo y seleccionar</span>
                                <span wire:loading wire:target="agregarEmailKeywordManual">Guardando...</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-neutral-500">Las palabras nuevas quedaran disponibles para todos los suscriptores.</p>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="flex items-center gap-2">
                    <button wire:click="guardarEmailSubscription"
                            class="px-5 py-2 bg-primary-500 text-white rounded-full text-sm font-medium hover:bg-primary-400 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar
                    </button>
                    <button wire:click="toggleEmailForm"
                            class="px-4 py-2 bg-neutral-200 text-neutral-600 rounded-full text-sm font-medium hover:bg-neutral-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        @endif

        {{-- Correo existente --}}
        @if($emailSubscription)
            <div class="bg-neutral-50 rounded-2xl p-3 sm:p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:bg-neutral-100 transition-colors">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                    <div class="flex-shrink-0">
                        @if($emailSubscription->activo)
                            <div class="w-10 h-10 bg-secondary-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-neutral-200 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1">
                            <p class="text-sm font-bold text-neutral-900">{{ $emailSubscription->email }}</p>
                            @if($emailSubscription->activo)
                                <span class="px-2 py-0.5 bg-secondary-500/20 text-primary-500 border border-secondary-500/30 rounded-full text-xs font-semibold">✓ Activo</span>
                            @else
                                <span class="px-2 py-0.5 bg-neutral-200 text-neutral-600 rounded-full text-xs font-semibold">✗ Inactivo</span>
                            @endif
                            @if($emailSubscription->notificar_todo)
                                <span class="px-2 py-0.5 bg-primary-500/10 text-primary-500 rounded-full text-xs font-medium">📩 Todos los procesos</span>
                            @else
                                <span class="px-2 py-0.5 bg-primary-500/10 text-primary-500 rounded-full text-xs font-medium">🔍 Filtrado por keywords</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:gap-4 text-xs text-neutral-600">
                            <span>📊 {{ number_format($emailSubscription->notificaciones_enviadas) }} correos enviados</span>
                            @if($emailSubscription->ultima_notificacion_at)
                                <span>🕐 Ultimo: {{ $emailSubscription->ultima_notificacion_at->diffForHumans() }}</span>
                            @endif
                            <span>📅 Desde: {{ $emailSubscription->created_at->format('d/m/Y') }}</span>
                        </div>
                        @if(!$emailSubscription->notificar_todo && $emailSubscription->keywords->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                @foreach($emailSubscription->keywords as $keyword)
                                    <span class="text-[11px] px-2 py-0.5 rounded-full border border-secondary-500/40 text-primary-500 bg-secondary-500/10">
                                        {{ $keyword->nombre }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                    {{-- Toggle activo --}}
                    <button wire:click="toggleEmailActivo"
                            title="{{ $emailSubscription->activo ? 'Desactivar' : 'Activar' }}"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        @if($emailSubscription->activo)
                            <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        @endif
                    </button>

                    {{-- Enviar prueba --}}
                    <button wire:click="probarEmailNotificacion"
                            title="Enviar correo de prueba"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>

                    {{-- Editar --}}
                    <button wire:click="toggleEmailForm"
                            title="Editar correo"
                            class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>

                    {{-- Eliminar --}}
                    <button wire:click="eliminarEmailSubscription"
                            onclick="return confirm('¿Eliminar la suscripción de correo?')"
                            title="Eliminar"
                            class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        @elseif(!$showEmailForm)
            <div class="text-center py-8">
                <div class="w-14 h-14 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900 mb-1">Sin notificaciones por correo</h3>
                <p class="text-xs text-neutral-500">Activa las notificaciones para recibir alertas de nuevos procesos en tu email.</p>
            </div>
        @endif
    </div>
</div>
