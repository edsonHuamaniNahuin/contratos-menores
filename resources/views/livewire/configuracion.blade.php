<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <h1 class="text-3xl font-bold text-neutral-900">⚙️ Configuración del Sistema</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Configura las integraciones del sistema Vigilante SEACE
        </p>
    </div>

    {{-- Mensajes de éxito/error --}}
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

    {{-- Configuración Telegram Bot --}}
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                    </svg>
                    Bot de Telegram
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Recibe notificaciones automáticas de nuevos contratos SEACE
                </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.live="telegram_enabled" class="sr-only peer">
                <div class="w-14 h-7 bg-neutral-200 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-secondary-500/20 peer-checked:bg-secondary-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all after:shadow-md peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
            </label>
        </div>

        @if($telegram_enabled)
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-600 mb-2">
                        Bot Token <span class="text-primary-500">*</span>
                    </label>
                    <input type="text" wire:model="telegram_bot_token"
                           class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all"
                           placeholder="8587965283:AAHwTFr-59rpnmAokTZuK8PchVsx-lPyVL0"
                           value="8587965283:AAHwTFr-59rpnmAokTZuK8PchVsx-lPyVL0">
                    <p class="text-xs text-neutral-400 mt-1">
                        Obtén tu token desde <a href="https://t.me/BotFather" target="_blank" class="text-primary-500 hover:underline">@BotFather</a>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-600 mb-2">
                        Chat ID <span class="text-primary-500">*</span>
                    </label>
                    <input type="text" wire:model="telegram_chat_id"
                           class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all"
                           placeholder="5203441622"
                           value="5203441622">
                    <p class="text-xs text-neutral-400 mt-1">
                        Tu Chat ID personal o de grupo (Ej: 5203441622 para mensajes directos)
                    </p>
                </div>

                <button wire:click="probarTelegram"
                        wire:loading.attr="disabled"
                        wire:target="probarTelegram"
                        class="px-6 py-3 bg-primary-500 text-white rounded-full hover:bg-primary-400 font-medium text-sm transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                    <svg wire:loading.remove wire:target="probarTelegram" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span wire:loading.remove wire:target="probarTelegram">Probar Conexión</span>
                    <span wire:loading wire:target="probarTelegram">Probando...</span>
                </button>

                @if($telegramTestResult)
                    <div class="mt-4 p-4 rounded-2xl border {{ $telegramTestResult['success'] ? 'bg-secondary-500/10 border-secondary-500' : 'bg-primary-500/10 border-primary-500' }}">
                        <p class="text-sm font-medium text-neutral-900">
                            {{ $telegramTestResult['success'] ? '✅' : '❌' }}
                            {{ $telegramTestResult['success'] ? $telegramTestResult['message'] : $telegramTestResult['error'] }}
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Gestión de Suscriptores Telegram --}}
    @if($telegram_enabled)
        <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Suscriptores que recibirán notificaciones
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    Solo estos Chat IDs recibirán las notificaciones del botón "📤 Enviar al Bot"
                </p>
            </div>

            {{-- Formulario Agregar Suscriptor --}}
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
                               placeholder="Juan Pérez">
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

                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="nuevo_activo" class="sr-only peer">
                            <div class="w-11 h-6 bg-neutral-200 rounded-full peer-focus:ring-4 peer-focus:ring-secondary-500/20 peer-checked:bg-secondary-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <span class="text-xs font-medium text-neutral-900">
                            {{ $nuevo_activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="flex gap-2">
                        @if($editando_suscripcion_id)
                            <button wire:click="resetSuscriptorForm"
                                    class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded-full font-medium text-xs hover:bg-neutral-200 transition-colors">
                                Cancelar
                            </button>
                        @endif
                        <button wire:click="agregarSuscriptor"
                                class="px-6 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full font-medium text-xs hover:opacity-90 transition-all shadow-md">
                                {{ $editando_suscripcion_id ? '💾 Actualizar' : '➕ Agregar' }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Lista de Suscriptores --}}
            @if($suscripciones->isEmpty())
                <div class="bg-neutral-50 rounded-2xl p-8 text-center border-2 border-dashed border-neutral-200">
                    <svg class="w-16 h-16 mx-auto mb-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm font-bold text-neutral-900 mb-1">No hay suscriptores</p>
                    <p class="text-xs text-neutral-400">Agrega el primer Chat ID para empezar a recibir notificaciones</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($suscripciones as $suscripcion)
                        <div class="bg-neutral-50 rounded-2xl p-4 flex items-center justify-between hover:bg-neutral-100 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    @if($suscripcion->activo)
                                        <div class="w-10 h-10 bg-secondary-500/20 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-secondary-500" fill="currentColor" viewBox="0 0 24 24">
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
                                            <span class="px-2 py-0.5 bg-secondary-500/20 text-secondary-500 border border-secondary-500/30 rounded-full text-xs font-semibold">✓ Activo</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-neutral-200 text-neutral-600 rounded-full text-xs font-semibold">✗ Inactivo</span>
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
                                            <span>🕐 Última: {{ $suscripcion->ultima_notificacion_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button wire:click="toggleActivoSuscriptor({{ $suscripcion->id }})"
                                        title="{{ $suscripcion->activo ? 'Desactivar' : 'Activar' }}"
                                        class="p-2 hover:bg-white rounded-full transition-colors">
                                    @if($suscripcion->activo)
                                        <svg class="w-4 h-4 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            {{-- Ayuda --}}
            <div class="mt-6 bg-primary-500/5 border-l-4 border-primary-500 rounded-r-2xl p-4">
                <h3 class="text-xs font-bold text-neutral-900 mb-2">📌 Cómo obtener tu Chat ID de Telegram</h3>
                <ol class="space-y-1 text-xs text-neutral-600">
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-primary-500">1.</span>
                        <span>Abre Telegram y busca <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">@userinfobot</code></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-primary-500">2.</span>
                        <span>Envía <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">/start</code></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="font-bold text-primary-500">3.</span>
                        <span>El bot responderá con tu Chat ID (ej: <code class="px-1.5 py-0.5 bg-neutral-100 rounded text-xs">5203441622</code>)</span>
                    </li>
                </ol>
            </div>
        </div>
    @endif

    {{-- Configuración Analizador TDR --}}
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-neutral-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Analizador TDR con IA
                </h2>
                <p class="text-xs text-neutral-400 mt-1">
                    API de análisis automático de documentos TDR usando Gemini/GPT-4
                </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.live="analizador_enabled" class="sr-only peer">
                <div class="w-14 h-7 bg-neutral-200 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-secondary-500/20 peer-checked:bg-secondary-500 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all after:shadow-md peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
            </label>
        </div>

        @if($analizador_enabled)
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-600 mb-2">
                        URL del Servicio <span class="text-primary-500">*</span>
                    </label>
                    <input type="url" wire:model="analizador_url"
                           class="w-full px-4 py-3 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all"
                           placeholder="http://127.0.0.1:8001">
                    <p class="text-xs text-neutral-400 mt-1">
                        URL del microservicio FastAPI (ej: http://127.0.0.1:8001)
                    </p>
                </div>

                <div class="bg-neutral-50 rounded-2xl p-4">
                    <p class="text-xs text-neutral-600 mb-2 font-medium">📖 Comandos de inicio rápido:</p>
                    <code class="text-xs text-neutral-900 block bg-white p-3 rounded-xl border border-neutral-200">
                        cd d:\xampp\htdocs\vigilante-seace\analizador-tdr<br>
                        .\setup.ps1<br>
                        python main.py
                    </code>
                </div>

                <button wire:click="probarAnalizador"
                        wire:loading.attr="disabled"
                        wire:target="probarAnalizador"
                        class="px-6 py-3 bg-primary-500 text-white rounded-full hover:bg-primary-400 font-medium text-sm transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                    <svg wire:loading.remove wire:target="probarAnalizador" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span wire:loading.remove wire:target="probarAnalizador">Verificar Conexión</span>
                    <span wire:loading wire:target="probarAnalizador">Verificando...</span>
                </button>

                @if($analizadorTestResult)
                    <div class="mt-4 p-4 rounded-2xl border {{ $analizadorTestResult['success'] ? 'bg-secondary-500/10 border-secondary-500' : 'bg-primary-500/10 border-primary-500' }}">
                        <p class="text-sm font-medium text-neutral-900 mb-2">
                            {{ $analizadorTestResult['success'] ? '✅' : '❌' }}
                            {{ $analizadorTestResult['message'] ?? $analizadorTestResult['error'] }}
                        </p>
                        @if($analizadorTestResult['success'] && isset($analizadorTestResult['data']))
                            <pre class="text-xs text-secondary-400 bg-neutral-900 p-4 rounded-xl overflow-auto leading-relaxed">{{ json_encode($analizadorTestResult['data'], JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Botón Guardar --}}
    <div class="flex justify-end">
        <button wire:click="guardarConfiguracion"
                class="px-8 py-4 bg-secondary-500 text-white rounded-full hover:bg-secondary-400 font-bold text-base transition-colors shadow-lg hover:shadow-xl">
            💾 Guardar Configuración
        </button>
    </div>

    {{-- Documentación --}}
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <h2 class="text-xl font-bold text-neutral-900 mb-4">📚 Documentación</h2>

        <div class="space-y-4">
            <div class="border-l-4 border-primary-500 pl-6 py-3">
                <h3 class="text-lg font-bold text-neutral-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Sistema de Autenticación Resiliente
                </h3>
                <p class="text-sm text-neutral-600 mb-3 leading-relaxed">
                    Todas las peticiones a la API SEACE implementan auto-recuperación:
                </p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">→</span>
                        <span><strong class="text-neutral-900">Token válido:</strong> Petición directa</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">→</span>
                        <span><strong class="text-neutral-900">Token expirado (>5min):</strong> Refresh automático + reintento</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">→</span>
                        <span><strong class="text-neutral-900">Refresh expirado:</strong> Login completo + reintento</span>
                    </li>
                </ul>
            </div>

            <div class="border-l-4 border-secondary-500 pl-6 py-3">
                <h3 class="text-lg font-bold text-neutral-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-secondary-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                    </svg>
                    Flujo de Notificaciones Telegram
                </h3>
                <p class="text-sm text-neutral-600 mb-3 leading-relaxed">
                    El bot envía alertas automáticas cuando detecta:
                </p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">✓</span>
                        <span>Nuevas convocatorias en SEACE</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">✓</span>
                        <span>Cambios de estado en contratos monitoreados</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-neutral-600">
                        <span class="text-secondary-500 font-bold mt-0.5">✓</span>
                        <span>Fechas de cotización próximas a vencer</span>
                    </li>
                </ul>
            </div>

            <div class="border-l-4 border-primary-500 pl-6 py-3">
                <h3 class="text-lg font-bold text-neutral-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    API Analizador TDR
                </h3>
                <p class="text-sm text-neutral-600 mb-3 leading-relaxed">
                    Microservicio Python con FastAPI + Gemini 2.5 Flash:
                </p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-3 text-sm text-neutral-600">
                        <code class="px-2 py-1 bg-primary-100 text-primary-500 rounded-md font-mono text-xs whitespace-nowrap mt-0.5">POST</code>
                        <span><strong class="text-neutral-900">/analyze</strong> - Análisis de 1 TDR (PDF)</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-neutral-600">
                        <code class="px-2 py-1 bg-primary-100 text-primary-500 rounded-md font-mono text-xs whitespace-nowrap mt-0.5">POST</code>
                        <span><strong class="text-neutral-900">/batch/analyze</strong> - Batch 3-10 TDRs en paralelo</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-neutral-600">
                        <code class="px-2 py-1 bg-secondary-100 text-secondary-500 rounded-md font-mono text-xs whitespace-nowrap mt-0.5">GET</code>
                        <span><strong class="text-neutral-900">/health</strong> - Health check del servicio</span>
                    </li>
                    <li class="flex items-start gap-3 text-sm text-neutral-600">
                        <code class="px-2 py-1 bg-secondary-100 text-secondary-500 rounded-md font-mono text-xs whitespace-nowrap mt-0.5">GET</code>
                        <span><strong class="text-neutral-900">/docs</strong> - Documentación interactiva Swagger</span>
                    </li>
                </ul>
                <div class="mt-4 p-3 bg-neutral-50 rounded-xl border border-neutral-200">
                    <p class="text-xs text-neutral-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Ver documentación completa: <code class="bg-white px-2 py-1 rounded font-mono text-primary-500">analizador-tdr/README.md</code></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
