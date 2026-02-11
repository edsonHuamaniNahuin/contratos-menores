<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <h1 class="text-3xl font-bold text-neutral-900">👥 Suscriptores Telegram</h1>
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

    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-neutral-900 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Suscriptores que recibiran notificaciones
            </h2>
            <p class="text-xs text-neutral-400 mt-1">
                Solo estos Chat IDs recibiran las notificaciones del boton "📤 Enviar al Bot".
            </p>
        </div>

        <div class="bg-neutral-50 rounded-2xl p-6 mb-6">
            <h3 class="text-base font-bold text-neutral-900 mb-4">
                {{ $editando_suscripcion_id ? '✏️ Editar Suscriptor' : '➕ Agregar Nuevo Suscriptor' }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">
                        Chat ID <span class="text-primary-500">*</span>
                    </label>
                    <input type="text" wire:model="nuevo_chat_id"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="5203441622">
                    @error('nuevo_chat_id')
                        <p class="mt-1 text-xs text-primary-500">{{ $message }}</p>
                    @enderror
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
                    Copy del rubro <span class="text-primary-500">*</span>
                </label>
                <textarea wire:model="nuevo_company_copy"
                          class="w-full px-4 py-3 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm min-h-[110px]"
                          placeholder="Describe en pocas lineas el diferencial de tu empresa para que la IA calcule la compatibilidad"></textarea>
                <p class="text-[11px] text-neutral-500 mt-1">Esta descripcion se envia al analizador para contextualizar el score.</p>
                @error('nuevo_company_copy')
                    <p class="mt-1 text-xs text-primary-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-medium text-neutral-600">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-500/10 text-primary-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 5.5a7 7 0 100 14 7 7 0 000-14z" />
                            </svg>
                        </span>
                        <span>Palabras clave estrategicas</span>
                    </div>
                    <span class="text-[11px] text-neutral-500 font-semibold">{{ count($nuevo_keywords ?? []) }}/3 seleccionadas</span>
                </div>

                <div
                    class="relative space-y-4"
                    x-data="{
                        limit: 3,
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
                            <p class="font-semibold text-neutral-900">Maximo 3 palabras clave</p>
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
                        <p class="mt-1 text-xs text-primary-500">{{ $message }}</p>
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

                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="text-[11px] text-neutral-500">
                        Los cambios del suscriptor se guardan con el boton
                        "{{ $editando_suscripcion_id ? 'Guardar cambios del suscriptor' : 'Registrar suscriptor' }}".
                    </div>
                    <div class="flex gap-2">
                        @if($editando_suscripcion_id)
                            <button wire:click="resetSuscriptorForm"
                                    class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-xs hover:bg-neutral-200 transition-colors">
                                Cancelar edicion
                            </button>
                        @endif
                        <button wire:click="agregarSuscriptor"
                                class="px-6 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-xs hover:opacity-90 transition-all shadow-md">
                                {{ $editando_suscripcion_id ? '💾 Guardar cambios del suscriptor' : '➕ Registrar suscriptor' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

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
                    <div class="bg-neutral-50 rounded-2xl p-4 flex items-center justify-between hover:bg-neutral-100 transition-colors">
                        <div class="flex items-center gap-4">
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
                            <div class="flex-grow">
                                <div class="flex items-center gap-3 mb-1">
                                    <p class="text-sm font-bold text-neutral-900 font-mono">{{ $suscripcion->chat_id }}</p>
                                    @if($suscripcion->activo)
                                        <span class="px-2 py-0.5 bg-secondary-500/20 text-primary-500 border border-secondary-500/30 rounded-full text-xs font-semibold">✓ Activo</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-neutral-200 text-neutral-600 rounded-full text-xs font-semibold">✗ Inactivo</span>
                                    @endif
                                    @if($isAdmin && $suscripcion->user)
                                        <span class="px-2 py-0.5 bg-neutral-900/5 text-neutral-600 rounded-full text-xs font-semibold">
                                            {{ $suscripcion->user->name }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4 text-xs text-neutral-600">
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
                        <div class="flex items-center gap-2">
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

        <div class="mt-6 bg-primary-500/5 border-l-4 border-primary-500 rounded-r-2xl p-4">
            <h3 class="text-xs font-bold text-neutral-900 mb-2">📌 Como obtener tu Chat ID de Telegram</h3>
            <ol class="space-y-1 text-xs text-neutral-600">
                <li class="flex items-start gap-2">
                    <span class="font-bold text-primary-500">1.</span>
                    <span>Abre Telegram y busca <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">@userinfobot</code></span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="font-bold text-primary-500">2.</span>
                    <span>Envia <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">/start</code></span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="font-bold text-primary-500">3.</span>
                    <span>El bot respondera con tu Chat ID (ej: <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">5203441622</code>)</span>
                </li>
            </ol>
        </div>
    </div>
</div>
