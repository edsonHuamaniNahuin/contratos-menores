<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">

    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN 1: PERFIL DE EMPRESA (company_copy + keywords unificados)
    ═══════════════════════════════════════════════════════════════ --}}

    @if(session()->has('profile_success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('profile_success') }}</p>
        </div>
    @endif
    @if(session()->has('profile_error'))
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('profile_error') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Perfil de empresa
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Describe tu empresa y elige palabras clave. Esta configuracion se comparte entre todos los canales (Telegram, WhatsApp, Correo).
                </p>
            </div>
            <button wire:click="toggleProfileForm"
                    class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ $subscriberProfile ? 'Editar perfil' : 'Configurar perfil' }}
            </button>
        </div>

        {{-- Vista resumen del perfil --}}
        @if($subscriberProfile)
            <div class="bg-neutral-50 rounded-2xl p-4">
                @if($subscriberProfile->company_copy)
                    <p class="text-sm text-neutral-700 leading-relaxed border-l-2 border-primary-200 pl-3 mb-3">
                        "{{ $subscriberProfile->company_copy }}"
                    </p>
                @else
                    <p class="text-xs text-neutral-400 italic mb-3">Sin descripcion de empresa configurada.</p>
                @endif
                @if($subscriberProfile->keywords->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($subscriberProfile->keywords as $keyword)
                            <span class="text-[11px] px-2 py-0.5 rounded-full border border-secondary-500/40 text-primary-500 bg-secondary-500/10">
                                {{ $keyword->nombre }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-neutral-400 italic">Sin palabras clave seleccionadas.</p>
                @endif
            </div>
        @else
            <div class="text-center py-6">
                <div class="w-12 h-12 mx-auto bg-primary-500/10 rounded-full flex items-center justify-center mb-2">
                    <svg class="w-6 h-6 text-primary-500/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-neutral-900 mb-1">Configura tu perfil de empresa</p>
                <p class="text-xs text-neutral-500">Necesario para recibir notificaciones filtradas y calcular compatibilidad IA.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL: Editar Perfil de Empresa
    ═══════════════════════════════════════════════════════════════ --}}
    @if($showProfileForm)
    <div x-data x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4" @keydown.escape.window="$wire.toggleProfileForm()">
        <div @click.outside="$wire.toggleProfileForm()" class="bg-white rounded-3xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 relative"
             x-data="{
                 limit: {{ $maxKeywords }},
                 selected: @entangle('profile_keywords').live,
                 search: @entangle('profile_keyword_search').live,
                 triggerLimitToast() {
                     $dispatch('keyword-limit-reached');
                 }
             }">

            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-neutral-900">🏢 Perfil de empresa</h3>
                <button wire:click="toggleProfileForm" class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Company Name --}}
            <div class="mb-4">
                <label class="block text-xs font-medium text-neutral-600 mb-2">
                    Nombre de empresa <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="profile_company_name"
                    class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm @error('profile_company_name') border-red-400 @enderror"
                    placeholder="Ej: SunquPACHA SAC - SOCIOS LIMA"
                    minlength="15"
                    maxlength="50">
                @error('profile_company_name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-[11px] text-neutral-400 mt-1">Obligatorio. Mínimo 15 caracteres, máximo 50. Se usará en los documentos de proforma técnica generados con IA.</p>
            </div>

            {{-- Company Copy --}}
            <div class="mb-5">
                <label class="block text-xs font-medium text-neutral-600 mb-2">
                    Descripcion de tu empresa <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="profile_company_copy"
                    rows="3"
                    class="w-full px-4 py-2.5 rounded-2xl border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm resize-none"
                    placeholder="Ej: Empresa de consultoria tecnologica especializada en desarrollo de software, cloud computing y ciberseguridad para el sector publico."></textarea>
                @error('profile_company_copy')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <p class="text-[11px] text-neutral-400 mt-1">Minimo 30 caracteres. Describe a que se dedica tu empresa para el matching de contratos IA.</p>
            </div>

            {{-- Keywords --}}
            <div class="mb-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="flex items-center gap-1.5 text-xs font-medium text-neutral-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            Palabras clave de interes
                        </label>
                        <p class="text-[11px] text-neutral-400 mt-0.5">Solo recibiras notificaciones de procesos que contengan alguna de estas palabras.</p>
                    </div>
                    <span class="text-[11px] text-neutral-500 font-semibold">{{ count($profile_keywords ?? []) }}/{{ $maxKeywords }} seleccionadas</span>
                </div>

                {{-- Limite alcanzado toast --}}
                <div x-show="false" x-ref="limitToast"
                     @keyword-limit-reached.window="$el.style.display='flex'; setTimeout(() => $el.style.display='none', 3000)"
                     class="items-center gap-2 bg-primary-500/10 border border-primary-400/30 rounded-2xl px-4 py-2.5 mb-3">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="font-semibold text-neutral-900 text-xs">Maximo {{ $maxKeywords }} palabras clave</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Catalogo --}}
                    <div class="bg-neutral-50 border border-neutral-200 rounded-2xl p-4">
                        <div class="mb-3">
                            <input type="text" x-model="search"
                                   wire:model.live.debounce.300ms="profile_keyword_search"
                                   class="w-full px-3 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20 outline-none text-xs"
                                   placeholder="🔍 Buscar en catalogo...">
                        </div>
                        <div class="min-h-[120px] max-h-60 overflow-y-auto pr-1 space-y-1.5">
                            @forelse($filteredKeywords as $keyword)
                                <label wire:key="kw-{{ $keyword['id'] }}"
                                       class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-white transition-colors cursor-pointer"
                                       x-on:click="if(selected.length >= limit && !selected.includes({{ $keyword['id'] }})){ $event.preventDefault(); triggerLimitToast(); }">
                                    <input type="checkbox" value="{{ $keyword['id'] }}"
                                           wire:model.live="profile_keywords"
                                           x-on:click="if(selected.length >= limit && !selected.includes({{ $keyword['id'] }})){ $event.preventDefault(); triggerLimitToast(); }"
                                           class="rounded border-neutral-300 text-primary-500 focus:ring-primary-500/30">
                                    <span class="px-3 py-1 rounded-full border text-xs font-semibold transition-all {{ in_array($keyword['id'], $profile_keywords ?? [], true) ? 'bg-secondary-500 text-white border-secondary-500 shadow' : 'bg-white text-neutral-600 border-neutral-200 hover:border-primary-400' }}">
                                        {{ $keyword['nombre'] }}
                                    </span>
                                </label>
                            @empty
                                <p class="text-xs text-neutral-500">No encontramos coincidencias con "{{ $profile_keyword_search }}".</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Seleccionadas --}}
                    <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-soft">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-neutral-600 uppercase tracking-wide">Seleccionadas</p>
                            @if(count($profile_keywords ?? []))
                                <span class="text-[11px] text-neutral-500">Haz clic para quitar</span>
                            @endif
                        </div>
                        <div class="min-h-[120px] max-h-60 overflow-y-auto pr-1 space-y-2">
                            @forelse($profile_keywords as $keywordId)
                                @php
                                    $tag = $keywordDictionary->get($keywordId);
                                @endphp
                                <div wire:key="selected-keyword-{{ $keywordId }}"
                                     class="w-full rounded-2xl border border-secondary-500/50 bg-secondary-500/5 px-3 py-2 flex items-center justify-between gap-3">
                                    <div class="flex flex-col text-left">
                                        <p class="text-xs font-semibold text-neutral-800 leading-none">{{ $tag['nombre'] ?? 'Keyword #' . $keywordId }}</p>
                                        <span class="text-[10px] text-neutral-500">ID #{{ $keywordId }}</span>
                                    </div>
                                    <button type="button"
                                        wire:click="quitarKeyword({{ $keywordId }})"
                                        class="w-7 h-7 flex items-center justify-center rounded-full border border-primary-500/60 text-primary-500 hover:bg-primary-500/10 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @empty
                                <p class="text-xs text-neutral-500">Selecciona palabras clave del catalogo.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                @error('profile_keywords.*')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                {{-- Agregar keyword manual --}}
                <div class="flex flex-col md:flex-row gap-2 mt-3">
                    <input type="text" wire:model.live="profile_keyword_manual"
                           wire:keydown.enter.prevent="agregarKeywordManual"
                           class="flex-1 px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                           placeholder="No existe en el catalogo? Agregala aqui">
                    <button type="button"
                            wire:click="agregarKeywordManual"
                            wire:loading.attr="disabled"
                            wire:target="agregarKeywordManual"
                            class="px-4 py-2 bg-white border border-primary-500 text-primary-500 rounded-full text-xs font-semibold hover:bg-primary-50 transition-all disabled:opacity-60">
                        <span wire:loading.remove wire:target="agregarKeywordManual">Guardar en catalogo y seleccionar</span>
                        <span wire:loading wire:target="agregarKeywordManual">Guardando...</span>
                    </button>
                </div>
                <p class="text-[11px] text-neutral-500 mt-1">Las palabras nuevas quedaran disponibles para todos los usuarios.</p>
            </div>

            {{-- Botones --}}
            <div class="flex items-center gap-2 pt-2">
                <button wire:click="guardarProfile"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md">
                    💾 Guardar perfil
                </button>
                <button wire:click="toggleProfileForm"
                        class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-sm hover:bg-neutral-200 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN 2: CANALES DE NOTIFICACIÓN (toggles unificados)
    ═══════════════════════════════════════════════════════════════ --}}

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
        <div class="mb-5">
            <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Canales de notificacion
            </h2>
            <p class="text-xs text-neutral-400 mt-1">Activa o desactiva los canales por donde deseas recibir alertas de nuevos procesos SEACE.</p>
        </div>

        <div class="space-y-3">
            {{-- Toggle Telegram (optimistic UI con Alpine) --}}
            @php
                $telegramHasSubs = $suscripciones->isNotEmpty();
                $telegramAllActive = $telegramHasSubs && $suscripciones->every(fn($s) => $s->activo);
            @endphp
            <div wire:key="tg-toggle-{{ $telegramAllActive ? '1' : '0' }}"
                 x-data="{ active: {{ $telegramAllActive ? 'true' : 'false' }}, hasSubs: {{ $telegramHasSubs ? 'true' : 'false' }} }"
                 class="flex items-center justify-between bg-neutral-50 rounded-2xl px-4 py-3"
                 :class="{ 'opacity-50': !hasSubs }">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center transition-colors duration-200"
                         :class="active ? 'bg-secondary-500/20' : 'bg-neutral-200'">
                        <svg class="w-4.5 h-4.5 transition-colors duration-200" :class="active ? 'text-primary-500' : 'text-neutral-400'" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">Telegram</p>
                        <p class="text-[11px] text-neutral-400">
                            @if($telegramHasSubs)
                                {{ $suscripciones->count() }} suscriptor{{ $suscripciones->count() > 1 ? 'es' : '' }} registrado{{ $suscripciones->count() > 1 ? 's' : '' }}
                            @else
                                Sin suscriptores registrados
                            @endif
                        </p>
                    </div>
                </div>
                @if($telegramHasSubs)
                    <button @click="active = !active; $wire.toggleTelegramNotificaciones()"
                            class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none"
                            :class="active ? 'bg-secondary-500' : 'bg-neutral-300'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                              :class="active ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                @else
                    <span class="text-[11px] text-neutral-400 italic">Agrega un Chat ID primero</span>
                @endif
            </div>

            {{-- Toggle WhatsApp (optimistic UI con Alpine) --}}
            @php
                $waExists = $whatsappSubscription !== null;
                $waActive = $waExists && $whatsappSubscription->activo;
            @endphp
            <div wire:key="wa-toggle-{{ $waActive ? '1' : '0' }}"
                 x-data="{ active: {{ $waActive ? 'true' : 'false' }}, exists: {{ $waExists ? 'true' : 'false' }} }"
                 class="flex items-center justify-between bg-neutral-50 rounded-2xl px-4 py-3"
                 :class="{ 'opacity-50': !exists }">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center transition-colors duration-200"
                         :class="active ? 'bg-secondary-500/20' : 'bg-neutral-200'">
                        <svg class="w-4.5 h-4.5 transition-colors duration-200" :class="active ? 'text-primary-500' : 'text-neutral-400'" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">WhatsApp</p>
                        <p class="text-[11px] text-neutral-400">
                            @if($waExists)
                                +{{ $whatsappSubscription->phone_number }}
                            @else
                                Sin numero registrado
                            @endif
                        </p>
                    </div>
                </div>
                @if($waExists)
                    <button @click="active = !active; $wire.toggleWhatsAppActivo()"
                            class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none"
                            :class="active ? 'bg-secondary-500' : 'bg-neutral-300'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                              :class="active ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                @else
                    <span class="text-[11px] text-neutral-400 italic">Configura un numero primero</span>
                @endif
            </div>

            {{-- Toggle Email (optimistic UI con Alpine) --}}
            @php
                $emailExists = $emailSubscription !== null;
                $emailActive = $emailExists && $emailSubscription->activo;
            @endphp
            <div wire:key="email-toggle-{{ $emailActive ? '1' : '0' }}"
                 x-data="{ active: {{ $emailActive ? 'true' : 'false' }}, exists: {{ $emailExists ? 'true' : 'false' }} }"
                 class="flex items-center justify-between bg-neutral-50 rounded-2xl px-4 py-3"
                 :class="{ 'opacity-50': !exists }">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center transition-colors duration-200"
                         :class="active ? 'bg-secondary-500/20' : 'bg-neutral-200'">
                        <svg class="w-4.5 h-4.5 transition-colors duration-200" :class="active ? 'text-primary-500' : 'text-neutral-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-neutral-900">Correo electronico</p>
                        <p class="text-[11px] text-neutral-400">
                            @if($emailExists)
                                {{ $emailSubscription->email }}
                                @if($emailSubscription->notificar_todo)
                                    · Todos los procesos
                                @else
                                    · Filtrado por keywords
                                @endif
                            @else
                                Sin correo registrado
                            @endif
                        </p>
                    </div>
                </div>
                @if($emailExists)
                    <button @click="active = !active; $wire.toggleEmailActivo()"
                            class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none"
                            :class="active ? 'bg-secondary-500' : 'bg-neutral-300'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                              :class="active ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                @else
                    <span class="text-[11px] text-neutral-400 italic">Configura un correo primero</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN 3: SUSCRIPTORES TELEGRAM (registros)
    ═══════════════════════════════════════════════════════════════ --}}

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Suscriptores Telegram
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Puedes registrar hasta {{ $maxSuscriptores }} suscriptores por cuenta. Cada uno recibira alertas en su chat de Telegram.
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap justify-end">
            @if($telegramBotUrl && $suscripciones->isNotEmpty())
                <a href="{{ $telegramBotUrl }}" target="_blank" rel="noopener noreferrer"
                   class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-primary-500/10 border border-primary-500/30 text-primary-600 rounded-full text-xs font-medium hover:bg-primary-500 hover:text-white transition-colors">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/></svg>
                    Abrir bot de alertas
                </a>
            @endif
            @if($canAddTelegram && $canAddMore)
                <button wire:click="toggleTelegramModal"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar suscriptor
                </button>
            @elseif(!$canAddTelegram)
                <span class="flex-shrink-0 px-4 py-2 bg-neutral-100 text-neutral-400 rounded-full text-xs font-medium italic">
                    Sin permiso para agregar
                </span>
            @else
                <span class="flex-shrink-0 px-4 py-2 bg-neutral-100 text-neutral-500 rounded-full text-xs font-medium">
                    Limite alcanzado ({{ $maxSuscriptores }}/{{ $maxSuscriptores }})
                </span>
            @endif
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
                                        <span class="text-[11px] font-semibold text-primary-500">{{ $suscripcion->user->name }}</span>
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
                            </div>
                        </div>
                        <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                            <button wire:click="probarNotificacionSuscriptor({{ $suscripcion->id }})" title="Enviar prueba" class="p-2 hover:bg-white rounded-full transition-colors">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </button>
                            <button wire:click="editarSuscriptor({{ $suscripcion->id }})" title="Editar" class="p-2 hover:bg-white rounded-full transition-colors">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="eliminarSuscriptor({{ $suscripcion->id }})" onclick="return confirm('¿Eliminar este suscriptor?')" title="Eliminar" class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL: Agregar/Editar Suscriptor Telegram
    ═══════════════════════════════════════════════════════════════ --}}
    @if($showTelegramModal)
    <div x-data x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4" @keydown.escape.window="$wire.toggleTelegramModal()">
        <div @click.outside="$wire.toggleTelegramModal()" class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 relative">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ $editando_suscripcion_id ? '✏️ Editar Suscriptor' : '➕ Nuevo Suscriptor Telegram' }}
                </h3>
                <button wire:click="toggleTelegramModal" class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                @if(!$editando_suscripcion_id)
                <div class="bg-primary-50 border border-primary-200 rounded-2xl p-4">
                    <p class="text-xs font-bold text-primary-700 mb-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ¿Cómo obtener tu Chat ID de Telegram?
                    </p>
                    <ol class="text-xs text-neutral-700 space-y-2 mb-3">
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 bg-primary-500 text-white rounded-full flex items-center justify-center font-bold text-[10px] mt-0.5">1</span>
                            <span>Haz clic en el botón de abajo para abrir <strong class="text-neutral-900">@userinfobot</strong> — un bot de Telegram que te dice tu ID en segundos.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 bg-primary-500 text-white rounded-full flex items-center justify-center font-bold text-[10px] mt-0.5">2</span>
                            <span>Envíale cualquier mensaje o el comando <code class="bg-white border border-primary-200 px-1 py-0.5 rounded text-primary-700 font-mono">/start</code>. El bot responderá con tu información.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="flex-shrink-0 w-5 h-5 bg-primary-500 text-white rounded-full flex items-center justify-center font-bold text-[10px] mt-0.5">3</span>
                            <span>Copia el número que aparece en el campo <strong class="text-neutral-900">Id</strong> de la respuesta y pégalo en el campo de abajo.</span>
                        </li>
                    </ol>
                    <a href="https://t.me/userinfobot" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-500 text-white rounded-full text-xs font-semibold hover:bg-primary-400 transition-colors shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/></svg>
                        Obtener mi Chat ID con @userinfobot →
                    </a>
                </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Chat ID <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nuevo_chat_id"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Ej: 5203441622">
                    @error('nuevo_chat_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Nombre</label>
                    <input type="text" wire:model="nuevo_nombre"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Nombre del suscriptor">
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Username</label>
                    <input type="text" wire:model="nuevo_username"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="@username">
                </div>
            </div>

            <div class="flex items-center gap-2 mt-6">
                <button wire:click="agregarSuscriptor"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md">
                    {{ $editando_suscripcion_id ? '💾 Guardar cambios' : '➕ Registrar suscriptor' }}
                </button>
                <button wire:click="toggleTelegramModal"
                        class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-sm hover:bg-neutral-200 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN 4: NOTIFICACIONES POR WHATSAPP
    ═══════════════════════════════════════════════════════════════ --}}

    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Notificaciones por WhatsApp
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Recibe alertas de nuevos procesos SEACE en tu WhatsApp con botones interactivos. Solo se permite 1 numero por usuario.
                </p>
            </div>
            @if($canAddWhatsApp && !$whatsappSubscription && !$showWhatsAppModal)
                <button wire:click="toggleWhatsAppModal"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar WhatsApp
                </button>
            @elseif(!$canAddWhatsApp && !$whatsappSubscription)
                <span class="flex-shrink-0 px-4 py-2 bg-neutral-100 text-neutral-400 rounded-full text-xs font-medium italic">
                    Sin permiso para agregar
                </span>
            @endif
        </div>

        @if(!$whatsappEnabled)
            <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4 mb-6">
                <p class="text-sm text-neutral-900 font-medium">
                    El administrador debe configurar WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID en el servidor para habilitar este canal.
                </p>
            </div>
        @endif

        @if($whatsappSubscription)
            <div class="bg-neutral-50 rounded-2xl p-3 sm:p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:bg-neutral-100 transition-colors">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                    <div class="flex-shrink-0">
                        @if($whatsappSubscription->activo)
                            <div class="w-10 h-10 bg-secondary-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-neutral-200 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-neutral-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-1">
                            <p class="text-sm font-bold text-neutral-900 font-mono">+{{ $whatsappSubscription->phone_number }}</p>
                            @if($whatsappSubscription->activo)
                                <span class="px-2 py-0.5 bg-secondary-500/20 text-primary-500 border border-secondary-500/30 rounded-full text-xs font-semibold">✓ Activo</span>
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
                    </div>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                    <button wire:click="probarWhatsAppNotificacion" wire:loading.attr="disabled" wire:target="probarWhatsAppNotificacion" title="Enviar mensaje de prueba" class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" wire:loading.class="animate-pulse" wire:target="probarWhatsAppNotificacion" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                    <button wire:click="toggleWhatsAppModal" title="Editar" class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="eliminarWhatsAppSubscription" onclick="return confirm('¿Eliminar la suscripcion de WhatsApp?')" title="Eliminar" class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        @elseif(!$showWhatsAppModal)
            <div class="text-center py-8">
                <div class="w-14 h-14 mx-auto bg-primary-500/10 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-primary-500/40" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900 mb-1">Sin notificaciones de WhatsApp</h3>
                <p class="text-xs text-neutral-500">Activa las notificaciones para recibir alertas de nuevos procesos en tu WhatsApp.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL: Agregar/Editar WhatsApp
    ═══════════════════════════════════════════════════════════════ --}}
    @if($showWhatsAppModal)
    <div x-data x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4" @keydown.escape.window="$wire.toggleWhatsAppModal()">
        <div @click.outside="$wire.toggleWhatsAppModal()" class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 relative">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ $editando_wa_id ? '✏️ Editar WhatsApp' : '📱 Configurar WhatsApp' }}
                </h3>
                <button wire:click="toggleWhatsAppModal" class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Numero de WhatsApp <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="wa_phone_number"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="51987654321">
                    @error('wa_phone_number')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-neutral-400 mt-1">Codigo de pais + numero, sin espacios ni simbolos. Ej: 51987654321</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Nombre (opcional)</label>
                    <input type="text" wire:model="wa_nombre"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="Nombre para identificar">
                </div>
            </div>

            <div class="flex items-center gap-2 mt-6">
                <button wire:click="guardarWhatsAppSubscription"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md">
                    💾 Guardar
                </button>
                <button wire:click="toggleWhatsAppModal"
                        class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-sm hover:bg-neutral-200 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════
         SECCIÓN 5: NOTIFICACIONES POR CORREO
    ═══════════════════════════════════════════════════════════════ --}}

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
                    Recibe alertas de nuevos procesos SEACE directamente en tu correo electronico. Solo se permite 1 correo por usuario.
                </p>
            </div>
            @if($canAddEmail && !$emailSubscription && !$showEmailModal)
                <button wire:click="toggleEmailModal"
                        class="flex-shrink-0 px-5 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Agregar correo
                </button>
            @elseif(!$canAddEmail && !$emailSubscription)
                <span class="flex-shrink-0 px-4 py-2 bg-neutral-100 text-neutral-400 rounded-full text-xs font-medium italic">
                    Sin permiso para agregar
                </span>
            @endif
        </div>

        @if($emailSubscription)
            <div class="bg-neutral-50 rounded-2xl p-3 sm:p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between hover:bg-neutral-100 transition-colors">
                <div class="flex items-start gap-3 sm:gap-4 min-w-0 flex-1">
                    <div class="flex-shrink-0">
                        @if($emailSubscription->activo)
                            <div class="w-10 h-10 bg-secondary-500/20 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                        @else
                            <div class="w-10 h-10 bg-neutral-200 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
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
                    </div>
                </div>
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 self-end md:self-auto">
                    <button wire:click="probarEmailNotificacion" title="Enviar correo de prueba" class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                    <button wire:click="toggleEmailModal" title="Editar correo" class="p-2 hover:bg-white rounded-full transition-colors">
                        <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="eliminarEmailSubscription" onclick="return confirm('¿Eliminar la suscripción de correo?')" title="Eliminar" class="p-2 hover:bg-primary-50 rounded-full transition-colors">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        @elseif(!$showEmailModal)
            <div class="text-center py-8">
                <div class="w-14 h-14 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900 mb-1">Sin notificaciones por correo</h3>
                <p class="text-xs text-neutral-500">Activa las notificaciones para recibir alertas de nuevos procesos en tu email.</p>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL: Agregar/Editar Email
    ═══════════════════════════════════════════════════════════════ --}}
    @if($showEmailModal)
    <div x-data="{ notificarTodo: @entangle('email_notificar_todo').live }"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm p-4" @keydown.escape.window="$wire.toggleEmailModal()">
        <div @click.outside="$wire.toggleEmailModal()" class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 relative">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ $editando_email_id ? '✏️ Editar correo' : '📧 Configurar correo' }}
                </h3>
                <button wire:click="toggleEmailModal" class="p-1.5 hover:bg-neutral-200 rounded-full transition-colors text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Correo electronico <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="email_notificacion"
                           class="w-full px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all text-sm"
                           placeholder="tucorreo@ejemplo.com">
                    @error('email_notificacion')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-neutral-400 mt-1">Por defecto se usa tu correo de cuenta. Puedes cambiarlo a otro si prefieres.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-neutral-600 mb-3">Tipo de notificacion</label>
                    <div class="flex flex-col gap-3">
                        <button type="button" @click="notificarTodo = true"
                                :class="notificarTodo ? 'bg-primary-500/10 border-primary-500 text-primary-500' : 'bg-white border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Recibir todos los procesos
                        </button>
                        <button type="button" @click="notificarTodo = false"
                                :class="!notificarTodo ? 'bg-primary-500/10 border-primary-500 text-primary-500' : 'bg-white border-neutral-200 text-neutral-600 hover:border-neutral-300'"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-2xl border text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filtrar por palabras clave del perfil
                        </button>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-2">
                        Si eliges "Filtrar", se usaran las palabras clave configuradas en tu perfil de empresa.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-6">
                <button wire:click="guardarEmailSubscription"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-sm hover:opacity-90 transition-all shadow-md">
                    💾 Guardar
                </button>
                <button wire:click="toggleEmailModal"
                        class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-sm hover:bg-neutral-200 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
