<div class="space-y-6">
    <!-- Selector de Cuenta -->
    <div class="bg-white rounded-3xl shadow-soft p-6">
        <h2 class="text-xl font-bold text-neutral-900 mb-4">Seleccionar Cuenta SEACE</h2>

        @if($cuentas->isEmpty())
            <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                <p class="text-neutral-900">No hay cuentas activas disponibles. <a href="{{ route('cuentas.create') }}" class="underline font-medium text-primary-500">Crear una nueva cuenta</a></p>
            </div>
        @else
            <div class="flex items-center gap-4">
                <select wire:model.live="cuentaSeleccionada" class="flex-1 px-4 py-2.5 border border-neutral-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    @foreach($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}">
                            {{ $cuenta->nombre }} ({{ $cuenta->username }})
                            @if($cuenta->token_valido) 🔑 @endif
                        </option>
                    @endforeach
                </select>

                <button wire:click="cargarCuentas" class="px-4 py-2.5 bg-neutral-600 text-white rounded-full hover:bg-neutral-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>

            @if($cuentaSeleccionada)
                @php
                    $cuenta = $this->getCuentaActual();
                @endphp

                @if($cuenta)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-primary-500/10 p-4 rounded-2xl">
                        <p class="text-xs text-neutral-600 font-medium">Usuario (DNI/RUC)</p>
                        <p class="text-sm font-semibold text-neutral-900">{{ $cuenta->username }}</p>
                    </div>
                    <div class="bg-secondary-500/10 p-4 rounded-2xl">
                        <p class="text-xs text-neutral-600 font-medium">Estado Token</p>
                        <p class="text-sm font-semibold text-neutral-900">
                            {{ $cuenta->token_valido ? '✓ Válido' : '✗ Expirado/Sin token' }}
                        </p>
                    </div>
                    <div class="bg-neutral-50 p-4 rounded-2xl">
                        <p class="text-xs text-neutral-600 font-medium">Consultas Realizadas</p>
                        <p class="text-sm font-semibold text-neutral-900">{{ $cuenta->total_consultas }}</p>
                    </div>
                </div>
                @endif
            @endif
        @endif
    </div>

    <!-- Acciones Rápidas -->
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-neutral-900">Pruebas de Endpoints</h2>
        <button wire:click="limpiarResultados" class="px-5 py-2.5 bg-neutral-600 text-white rounded-full hover:bg-neutral-900 transition-colors">
            Limpiar Resultados
        </button>
    </div>

    <!-- Grid de Endpoints -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Login -->
        <div class="bg-white rounded-3xl shadow-soft p-6">
            <h3 class="text-lg font-bold text-neutral-900 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</span>
                Login (Obtener Token)
            </h3>

            <div class="mb-4 bg-neutral-50 p-4 rounded-2xl">
                <p class="text-xs text-neutral-600 font-mono">POST /seguridadproveedor/seguridad/validausuariornp</p>
                <p class="text-xs text-neutral-400 mt-1">Obtiene token y refreshToken usando username (DNI/RUC) y contraseña. Token expira en 5 minutos.</p>
            </div>

            <button
                wire:click="probarLogin"
                wire:loading.attr="disabled"
                wire:target="probarLogin"
                class="w-full px-5 py-3 bg-primary-500 text-white rounded-full hover:bg-primary-400 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors shadow-sm"
            >
                <svg wire:loading.remove wire:target="probarLogin" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <svg wire:loading wire:target="probarLogin" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="probarLogin">Probar Login</span>
                <span wire:loading wire:target="probarLogin">Ejecutando...</span>
            </button>

            @if($resultadoLogin)
                <div class="mt-4 p-4 rounded-2xl {{ $resultadoLogin['success'] ? 'bg-secondary-500/10 border-2 border-secondary-500' : 'bg-primary-500/10 border-2 border-primary-500' }}">
                    <div class="flex items-start gap-3">
                        @if($resultadoLogin['success'])
                            <svg class="w-5 h-5 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">{{ $resultadoLogin['message'] }}</p>
                                <div class="mt-2 space-y-1 text-xs text-neutral-600">
                                    <p>• Token: {{ $resultadoLogin['data']['token'] }}</p>
                                    @if(isset($resultadoLogin['data']['refreshToken']))
                                    <p>• Refresh Token: {{ $resultadoLogin['data']['refreshToken'] }}</p>
                                    @endif
                                    <p>• Respuesta: {{ $resultadoLogin['data']['respuesta'] ? '✓ Exitoso' : '✗ Fallido' }}</p>
                                </div>
                            </div>
                        @else
                            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">Error</p>
                                <p class="text-xs text-neutral-600 mt-1">{{ $resultadoLogin['error'] }}</p>
                            </div>
                        @endif
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-400 p-4 rounded-xl overflow-x-auto leading-relaxed">{{ json_encode($resultadoLogin['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>

        <!-- 2. Refresh Token -->
        <div class="bg-white rounded-3xl shadow-soft p-6">
            <h3 class="text-lg font-bold text-neutral-900 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-secondary-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</span>
                Refresh Token
            </h3>

            <div class="mb-4 bg-neutral-50 p-4 rounded-2xl">
                <p class="text-xs text-neutral-600 font-mono">POST /seguridadproveedor/seguridad/tokens/refresh</p>
                <p class="text-xs text-neutral-400 mt-1">Renueva el token enviando <strong>refreshToken</strong> y <strong>username</strong> en el body. Devuelve NUEVO token y refreshToken actualizado.</p>
                <div class="mt-2 p-2 bg-secondary-500/10 border-l-4 border-secondary-500 rounded">
                    <p class="text-xs text-neutral-900">✅ <strong>Funciona con tokens válidos:</strong> No necesitas esperar 5 minutos. Puedes refrescar el token inmediatamente después del login.</p>
                </div>
            </div>

            <button
                wire:click="probarRefresh"
                wire:loading.attr="disabled"
                wire:target="probarRefresh"
                class="w-full px-5 py-3 bg-secondary-500 text-white rounded-full hover:bg-secondary-400 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors shadow-sm"
            >
                <svg wire:loading.remove wire:target="probarRefresh" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg wire:loading wire:target="probarRefresh" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="probarRefresh">Probar Refresh</span>
                <span wire:loading wire:target="probarRefresh">Ejecutando...</span>
            </button>

            @if($resultadoRefresh)
                <div class="mt-4 p-4 rounded-2xl {{ $resultadoRefresh['success'] ? 'bg-secondary-500/10 border-2 border-secondary-500' : 'bg-primary-500/10 border-2 border-primary-500' }}">
                    <div class="flex items-start gap-3">
                        @if($resultadoRefresh['success'])
                            <svg class="w-5 h-5 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">{{ $resultadoRefresh['message'] }}</p>
                                <div class="mt-2 space-y-1 text-xs text-neutral-600">
                                    <p>• Token: {{ $resultadoRefresh['data']['token'] }}</p>
                                    @if(isset($resultadoRefresh['data']['refreshToken']))
                                    <p>• Refresh Token: {{ $resultadoRefresh['data']['refreshToken'] }}</p>
                                    @endif
                                    <p>• Respuesta: {{ $resultadoRefresh['data']['respuesta'] ? '✓ Exitoso' : '✗ Fallido' }}</p>
                                </div>
                            </div>
                        @else
                            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">Error</p>
                                <p class="text-xs text-neutral-600 mt-1">{{ $resultadoRefresh['error'] }}</p>
                            </div>
                        @endif
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-400 p-4 rounded-xl overflow-x-auto leading-relaxed">{{ json_encode($resultadoRefresh['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>

        <!-- 3. Buscador de Procesos -->
        <div class="bg-white rounded-3xl shadow-soft p-6">
            <h3 class="text-lg font-bold text-neutral-900 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">3</span>
                Buscador de Procesos
            </h3>

            <div class="mb-4 bg-neutral-50 p-4 rounded-2xl">
                <p class="text-xs text-neutral-600 font-mono">GET /contratacion/contrataciones/buscador</p>
                <p class="text-xs text-neutral-400 mt-1">Busca contratos y convocatorias con filtros avanzados</p>
            </div>

            <div class="space-y-3 mb-4">
                <div class="grid grid-cols-2 gap-3">
                    <input
                        wire:model="parametrosBuscador.pagina"
                        type="number"
                        min="1"
                        placeholder="Página"
                        class="px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                    <input
                        wire:model="parametrosBuscador.registros"
                        type="number"
                        min="1"
                        max="100"
                        placeholder="Registros"
                        class="px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                </div>
                <input
                    wire:model="parametrosBuscador.texto"
                    type="text"
                    placeholder="Texto de búsqueda (objeto, descripción, etc.)"
                    class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >

                <!-- Filtro de Entidad con Autocompletado -->
                <div class="relative" x-data="{ mostrarSugerencias: false }">
                    <input
                        wire:model.live.debounce.500ms="parametrosBuscador.entidad"
                        @input="$wire.buscarEntidades()"
                        @focus="mostrarSugerencias = true"
                        type="text"
                        placeholder="Buscar entidad (mínimo 3 caracteres)"
                        class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >

                    <!-- Indicador de carga -->
                    @if($buscandoEntidades)
                        <div class="absolute right-3 top-3">
                            <svg class="w-5 h-5 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    @endif

                    <!-- Lista de sugerencias -->
                    @if(!empty($entidadesSugeridas))
                        <div
                            x-show="mostrarSugerencias"
                            @click.away="mostrarSugerencias = false"
                            class="absolute z-10 w-full mt-1 bg-white border border-neutral-100 rounded-xl shadow-soft max-h-60 overflow-y-auto"
                        >
                            @foreach($entidadesSugeridas as $entidad)
                                <button
                                    type="button"
                                    wire:click="seleccionarEntidad('{{ addslashes($entidad['razonSocial']) }}', '{{ $entidad['codConsucode'] }}')"
                                    @click="mostrarSugerencias = false"
                                    class="w-full text-left px-3 py-2 hover:bg-primary-500/10 text-xs text-neutral-900 border-b border-neutral-50 last:border-0"
                                >
                                    <span class="font-medium">{{ $entidad['razonSocial'] }}</span>
                                    <span class="text-neutral-400 ml-2">(Código: {{ $entidad['codConsucode'] }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <select
                    wire:model="parametrosBuscador.estado"
                    class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                    <option value="">Todos los estados</option>
                    <option value="1">Borrador</option>
                    <option value="2">Vigente</option>
                    <option value="3">En Evaluación</option>
                    <option value="4">Culminado</option>
                </select>

                <!-- Filtros Geográficos en Cascada -->
                <div class="grid grid-cols-3 gap-3">
                    <!-- Departamento -->
                    <div class="relative">
                        <select
                            wire:model.live="parametrosBuscador.departamento"
                            class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                            <option value="">Departamento</option>
                            @foreach($departamentos as $dpto)
                                <option value="{{ $dpto['id'] }}">{{ $dpto['nom'] }}</option>
                            @endforeach
                        </select>
                        @if($cargandoDepartamentos)
                            <div class="absolute right-3 top-3">
                                <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Provincia -->
                    <div class="relative">
                        <select
                            wire:model.live="parametrosBuscador.provincia"
                            class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            {{ empty($parametrosBuscador['departamento']) ? 'disabled' : '' }}
                        >
                            <option value="">Provincia</option>
                            @foreach($provincias as $prov)
                                <option value="{{ $prov['id'] }}">{{ $prov['nom'] }}</option>
                            @endforeach
                        </select>
                        @if($cargandoProvincias)
                            <div class="absolute right-3 top-3">
                                <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Distrito -->
                    <div class="relative">
                        <select
                            wire:model="parametrosBuscador.distrito"
                            class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            {{ empty($parametrosBuscador['provincia']) ? 'disabled' : '' }}
                        >
                            <option value="">Distrito</option>
                            @foreach($distritos as $dist)
                                <option value="{{ $dist['id'] }}">{{ $dist['nom'] }}</option>
                            @endforeach
                        </select>
                        @if($cargandoDistritos)
                            <div class="absolute right-3 top-3">
                                <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <button
                wire:click="probarBuscador"
                wire:loading.attr="disabled"
                wire:target="probarBuscador"
                class="w-full px-5 py-3 bg-primary-500 text-white rounded-full hover:bg-primary-400 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors shadow-sm"
            >
                <svg wire:loading.remove wire:target="probarBuscador" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <svg wire:loading wire:target="probarBuscador" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="probarBuscador">Buscar Procesos</span>
                <span wire:loading wire:target="probarBuscador">Buscando...</span>
            </button>

            @if($resultadoBuscador)
                <div class="mt-4 p-4 rounded-2xl {{ $resultadoBuscador['success'] ? 'bg-secondary-500/10 border-2 border-secondary-500' : 'bg-primary-500/10 border-2 border-primary-500' }}">
                    <div class="flex items-start gap-3">
                        @if($resultadoBuscador['success'])
                            <svg class="w-5 h-5 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">{{ $resultadoBuscador['message'] }}</p>
                                <div class="mt-2 space-y-1 text-xs text-neutral-600">
                                    <p>• Total encontrados: {{ $resultadoBuscador['data']['totalElements'] }}</p>
                                    <p>• Página actual: {{ $resultadoBuscador['data']['pageNumber'] }}</p>
                                    <p>• Registros devueltos: {{ $resultadoBuscador['data']['registros_devueltos'] }}</p>
                                </div>
                            </div>
                        @else
                            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">Error</p>
                                <p class="text-xs text-neutral-600 mt-1">{{ $resultadoBuscador['error'] }}</p>
                            </div>
                        @endif
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-400 p-4 rounded-xl overflow-x-auto leading-relaxed">{{ json_encode($resultadoBuscador['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>

        <!-- 4. Listar Archivos TDR -->
        <div class="bg-white rounded-3xl shadow-soft p-6">
            <h3 class="text-lg font-bold text-neutral-900 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-primary-500 text-white rounded-full flex items-center justify-center text-sm font-semibold">4</span>
                Listar Archivos TDR
            </h3>

            <div class="mb-4 bg-neutral-50 p-4 rounded-2xl">
                <p class="text-xs text-neutral-600 font-mono">GET /archivo/archivos/listar-archivos-contrato/{idContrato}/1</p>
                <p class="text-xs text-neutral-400 mt-1">Lista los archivos TDR disponibles de un contrato específico</p>
            </div>

            @if(empty($resultadoBuscador['raw']['data']))
                <div class="bg-neutral-50 border-2 border-neutral-100 rounded-2xl p-4 mb-4">
                    <p class="text-sm text-neutral-600">⚠️ Primero debes buscar contratos para poder listar sus archivos</p>
                </div>
            @else
                <p class="text-xs text-neutral-500 mb-3">Haz clic en un proceso para traer sus archivos TDR y habilitar las acciones.</p>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-neutral-600 mb-2">Contrato seleccionado:</label>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($resultadoBuscador['raw']['data'] as $contrato)
                            <div
                                wire:click="seleccionarContratoParaArchivos({{ $contrato['idContrato'] }})"
                                class="p-3 border border-neutral-100 rounded-xl hover:border-primary-500 cursor-pointer transition-colors {{ $contratoSeleccionadoArchivos === $contrato['idContrato'] ? 'border-primary-500 bg-primary-500/5' : 'bg-white' }}"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">{{ $contrato['desContratacion'] }}</p>
                                        <p class="text-xs text-neutral-600 mt-1">{{ \Illuminate\Support\Str::limit($contrato['desObjetoContrato'], 80) }}</p>
                                        <div class="flex gap-3 mt-2 text-xs text-neutral-400">
                                            <span>📍 {{ $contrato['nomEntidad'] }}</span>
                                            <span>🕒 {{ $contrato['fecPublica'] }}</span>
                                        </div>
                                    </div>
                                    @if($contratoSeleccionadoArchivos === $contrato['idContrato'])
                                        <span class="text-xs bg-primary-500 text-white px-2 py-1 rounded-full">Seleccionado</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($loadingArchivos)
                    <div class="flex items-center gap-2 text-xs text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-2xl px-3 py-2">
                        <svg class="w-4 h-4 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 004 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Cargando archivos del proceso seleccionado...</span>
                    </div>
                @endif
            @endif

            <!-- Mensaje de descarga exitosa -->
            @if(session()->has('descarga_exitosa'))
                <div class="mt-4 bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">{{ session('descarga_exitosa') }}</p>
                </div>
            @endif

            <!-- Mensaje de análisis exitoso -->
            @if(session()->has('analisis_exitoso'))
                <div class="mt-4 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">{{ session('analisis_exitoso') }}</p>
                </div>
            @endif

            <!-- Mensaje de envío a Telegram exitoso -->
            @if(session()->has('telegram_exitoso'))
                <div class="mt-4 bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">{{ session('telegram_exitoso') }}</p>
                </div>
            @endif

            <!-- Mensajes de error -->
            @error('descarga')
                <div class="mt-4 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">❌ Error de descarga - {{ $message }}</p>
                </div>
            @enderror

            @error('analisis')
                <div class="mt-4 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">❌ Error de análisis - {{ $message }}</p>
                </div>
            @enderror

            @error('telegram')
                <div class="mt-4 bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
                    <p class="text-sm text-neutral-900 font-medium">❌ Error al enviar a Telegram - {{ $message }}</p>
                </div>
            @enderror

            <!-- Resultado del Análisis con IA -->
            @if($resultadoAnalisis)
                <!-- RESUMEN DEL PROCESO TDR -->
                @if(isset($resultadoAnalisis['contexto_contrato']) && $resultadoAnalisis['contexto_contrato'])
                    <div class="mt-4 bg-white rounded-3xl border-2 border-neutral-100 p-6 shadow-soft">
                        <div class="flex items-start justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-neutral-900 mb-1">📋 Resumen del Proceso</h3>
                                <p class="text-sm text-neutral-400">Información contextual del contrato analizado</p>
                            </div>
                            <span class="px-4 py-2 rounded-full text-xs font-bold bg-neutral-100 text-neutral-600">
                                {{ $resultadoAnalisis['contexto_contrato']['estado'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Entidad -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-neutral-400 text-xs font-medium uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    Entidad Convocante
                                </div>
                                <p class="text-base font-semibold text-neutral-900">{{ $resultadoAnalisis['contexto_contrato']['entidad'] }}</p>
                            </div>

                            <!-- Código del Proceso -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-neutral-400 text-xs font-medium uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                    Código del Proceso
                                </div>
                                <p class="text-base font-semibold text-neutral-900 font-mono">{{ $resultadoAnalisis['contexto_contrato']['codigo_proceso'] }}</p>
                            </div>

                            <!-- Objeto del Contrato -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-neutral-400 text-xs font-medium uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Tipo de Contratación
                                </div>
                                <p class="text-base font-semibold text-primary-800">{{ $resultadoAnalisis['contexto_contrato']['objeto'] }}</p>
                            </div>

                            <!-- Fecha de Cierre -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-neutral-400 text-xs font-medium uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Cierre de Cotización
                                </div>
                                <p class="text-base font-semibold text-secondary-500">{{ $resultadoAnalisis['contexto_contrato']['fecha_cierre'] ?? 'No especificada' }}</p>
                            </div>
                        </div>

                        <!-- Descripción Completa -->
                        <div class="mt-6 pt-6 border-t border-neutral-100">
                            <div class="flex items-center gap-2 text-neutral-400 text-xs font-medium uppercase tracking-wider mb-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                Descripción del Objeto
                            </div>
                            <p class="text-sm text-neutral-600 leading-relaxed">{{ $resultadoAnalisis['contexto_contrato']['descripcion'] }}</p>
                        </div>

                        <!-- Etapa y Fecha -->
                        <div class="mt-4 flex items-center gap-3 flex-wrap">
                            <span class="px-4 py-2 rounded-full text-xs font-medium bg-primary-800/10 text-primary-800">
                                {{ $resultadoAnalisis['contexto_contrato']['etapa'] }}
                            </span>
                            @if($resultadoAnalisis['contexto_contrato']['fecha_publicacion'])
                                <span class="text-xs text-neutral-400">
                                    📅 Publicado: {{ $resultadoAnalisis['contexto_contrato']['fecha_publicacion'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- ANÁLISIS INTELIGENTE DEL TDR -->
                <div class="mt-4 bg-gradient-to-br from-primary-800/5 to-secondary-500/5 border-2 {{ $resultadoAnalisis['success'] ? 'border-secondary-500' : 'border-primary-500' }} rounded-2xl p-6">
                    <div class="flex items-start gap-3 mb-4">
                        @if($resultadoAnalisis['success'])
                            <svg class="w-6 h-6 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-neutral-900 flex items-center gap-2">
                                🤖 Análisis TDR con IA
                                @if($resultadoAnalisis['success'])
                                    <span class="text-xs bg-secondary-500 text-white px-3 py-1 rounded-full font-medium">Completado</span>
                                @else
                                    <span class="text-xs bg-primary-500 text-white px-3 py-1 rounded-full font-medium">Error</span>
                                @endif
                            </h4>
                            <p class="text-xs text-neutral-600 mt-1">
                                📄 {{ $resultadoAnalisis['archivo'] ?? 'N/A' }} •
                                ⏰ {{ $resultadoAnalisis['timestamp'] ?? now()->format('Y-m-d H:i:s') }}
                            </p>
                        </div>
                    </div>

                    @if($resultadoAnalisis['success'] && isset($resultadoAnalisis['data']))
                        <div class="space-y-4">
                            <!-- Requisitos de Calificación -->
                            @if(isset($resultadoAnalisis['data']['requisitos_calificacion']))
                                <div class="bg-white rounded-xl p-4 border border-neutral-100">
                                    <h5 class="font-bold text-neutral-900 mb-2 flex items-center gap-2">
                                        📋 Requisitos de Calificación
                                    </h5>
                                    <pre class="text-xs text-neutral-700 whitespace-pre-wrap font-mono">{{ $resultadoAnalisis['data']['requisitos_calificacion'] ?? 'No especificado' }}</pre>
                                </div>
                            @endif

                            <!-- Reglas de Ejecución -->
                            @if(isset($resultadoAnalisis['data']['reglas_ejecucion']))
                                <div class="bg-white rounded-xl p-4 border border-neutral-100">
                                    <h5 class="font-bold text-neutral-900 mb-2 flex items-center gap-2">
                                        ⚙️ Reglas de Ejecución
                                    </h5>
                                    <pre class="text-xs text-neutral-700 whitespace-pre-wrap font-mono">{{ $resultadoAnalisis['data']['reglas_ejecucion'] ?? 'No especificado' }}</pre>
                                </div>
                            @endif

                            <!-- Penalidades -->
                            @if(isset($resultadoAnalisis['data']['penalidades']))
                                <div class="bg-white rounded-xl p-4 border border-neutral-100">
                                    <h5 class="font-bold text-neutral-900 mb-2 flex items-center gap-2">
                                        ⚠️ Penalidades
                                    </h5>
                                    <pre class="text-xs text-neutral-700 whitespace-pre-wrap font-mono">{{ $resultadoAnalisis['data']['penalidades'] ?? 'No especificado' }}</pre>
                                </div>
                            @endif

                            <!-- Monto Referencial -->
                            @if(isset($resultadoAnalisis['data']['monto_referencial']))
                                <div class="bg-white rounded-xl p-4 border border-neutral-100">
                                    <h5 class="font-bold text-neutral-900 mb-2 flex items-center gap-2">
                                        💰 Monto Referencial
                                    </h5>
                                    <p class="text-sm text-neutral-700 font-mono">{{ $resultadoAnalisis['data']['monto_referencial'] ?? 'No especificado' }}</p>
                                </div>
                            @endif
                        </div>
                    @elseif(!$resultadoAnalisis['success'])
                        <div class="bg-primary-500/10 rounded-xl p-4">
                            <p class="text-sm text-neutral-900 font-medium">❌ Error en el análisis</p>
                            <p class="text-xs text-neutral-600 mt-1">{{ $resultadoAnalisis['error'] ?? 'Error desconocido' }}</p>
                        </div>
                    @endif

                    <details class="mt-4">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta JSON completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-400 p-4 rounded-xl overflow-x-auto leading-relaxed">{{ json_encode($resultadoAnalisis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif

            @if($resultadoArchivos)
                <div class="mt-4 bg-neutral-50 border-2 {{ $resultadoArchivos['success'] ? 'border-secondary-500' : 'border-primary-500' }} rounded-2xl p-4">
                    <div class="flex items-start gap-3">
                        @if($resultadoArchivos['success'])
                            <svg class="w-5 h-5 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">✅ Archivos encontrados</p>
                                <div class="mt-2 space-y-1 text-xs text-neutral-600">
                                    <p>• Total archivos: {{ $resultadoArchivos['count'] }}</p>
                                    <p>• ID Contrato: {{ $resultadoArchivos['idContrato'] }}</p>
                                    <p>• Duración: {{ $resultadoArchivos['duration_ms'] }}ms</p>
                                </div>
                            </div>
                        @else
                            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">Error</p>
                                <p class="text-xs text-neutral-600 mt-1">{{ $resultadoArchivos['error'] }}</p>
                            </div>
                        @endif
                    </div>

                    @if($resultadoArchivos['success'] && !empty($resultadoArchivos['data']))
                        <!-- Cards de Archivos Estilo Sequence -->
                        <div class="mt-4 space-y-3">
                            @foreach($resultadoArchivos['data'] as $archivo)
                                <div class="bg-white rounded-2xl border border-neutral-100 p-4 hover:shadow-soft transition-shadow">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="font-semibold text-neutral-900 text-sm">{{ $archivo['nombre'] }}</p>
                                            </div>

                                            <div class="grid grid-cols-2 gap-2 text-xs text-neutral-600">
                                                <p>📄 <span class="font-medium">Tipo:</span> {{ $archivo['nombreTipoArchivo'] }}</p>
                                                <p>📊 <span class="font-medium">Tamaño:</span> {{ number_format($archivo['tamanio'] / 1024, 2) }} KB</p>
                                                <p>🔖 <span class="font-medium">Formato:</span> {{ $archivo['descripcionExtension'] }}</p>
                                                <p>🆔 <span class="font-medium">ID:</span> {{ $archivo['idContratoArchivo'] }}</p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-2 shrink-0">
                                            <!-- Botón Enviar al Bot -->
                                            <button
                                                wire:click="enviarAlBot(@js(array_merge($contratoSeleccionadoData ?? [], ['idContratoArchivo' => $archivo['idContratoArchivo'], 'nombreArchivo' => $archivo['nombre']])))"
                                                wire:loading.attr="disabled"
                                                wire:target="enviarAlBot"
                                                class="px-4 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full hover:from-primary-400 hover:to-secondary-400 font-medium text-xs flex items-center justify-center gap-2 transition-all shadow-md hover:shadow-lg whitespace-nowrap disabled:opacity-50"
                                            >
                                                <svg wire:loading.remove wire:target="enviarAlBot" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.03-2.76-.918c-.6-.183-.612-.6.125-.89l10.782-4.156c.5-.18.943.11.78.89z"/>
                                                </svg>
                                                <svg wire:loading wire:target="enviarAlBot" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 004 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="enviarAlBot">📤 Enviar al Bot</span>
                                                <span wire:loading wire:target="enviarAlBot">Enviando...</span>
                                            </button>

                                            <!-- Botón Descargar -->
                                            <button
                                                wire:click="descargarArchivo({{ $archivo['idContratoArchivo'] }}, '{{ $archivo['nombre'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="descargarArchivo"
                                                class="px-4 py-2 bg-secondary-500 text-white rounded-full hover:bg-secondary-400 font-medium text-xs flex items-center justify-center gap-2 transition-colors shadow-sm whitespace-nowrap disabled:opacity-50"
                                            >
                                                <svg wire:loading.remove wire:target="descargarArchivo" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <svg wire:loading wire:target="descargarArchivo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 004 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="descargarArchivo">Descargar</span>
                                                <span wire:loading wire:target="descargarArchivo">Descargando...</span>
                                            </button>

                                            <!-- Botón Analizar IA -->
                                            <button
                                                wire:click="analizarArchivo({{ $archivo['idContratoArchivo'] }}, '{{ $archivo['nombre'] }}', @js($contratoSeleccionadoData ?? []))"
                                                wire:loading.attr="disabled"
                                                wire:target="analizarArchivo"
                                                class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-full hover:from-purple-700 hover:to-indigo-700 font-medium text-xs flex items-center justify-center gap-2 transition-all shadow-md hover:shadow-lg whitespace-nowrap disabled:opacity-50"
                                            >
                                                <svg wire:loading.remove wire:target="analizarArchivo" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                                <svg wire:loading wire:target="analizarArchivo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 004 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="analizarArchivo">🤖 Analizar IA</span>
                                                <span wire:loading wire:target="analizarArchivo">Analizando...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($resultadoArchivos['success'] && $resultadoArchivos['count'] === 0)
                        <div class="mt-3 bg-neutral-100 rounded-xl p-3 text-center">
                            <p class="text-sm text-neutral-600">📭 Este contrato no tiene archivos TDR disponibles</p>
                        </div>
                    @endif

                    <details class="mt-3">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta JSON completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-400 p-4 rounded-xl overflow-x-auto leading-relaxed">{{ json_encode($resultadoArchivos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>

        <!-- 5. Consulta de Maestras -->
        <div class="bg-white rounded-3xl shadow-soft p-6">
            <h3 class="text-lg font-bold text-neutral-900 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-neutral-600 text-white rounded-full flex items-center justify-center text-sm font-semibold">5</span>
                Consulta de Maestras
            </h3>

            <div class="mb-4 bg-neutral-50 p-4 rounded-2xl">
                <p class="text-xs text-neutral-600 font-mono">GET /maestra/maestras/listar-{tipo}</p>
                <p class="text-xs text-neutral-400 mt-1">Obtiene catálogos de datos maestros del sistema SEACE</p>
            </div>

            <div class="mb-4">
                <select
                    wire:model="tipoMaestra"
                    class="w-full px-3 py-2.5 border border-neutral-100 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                    <option value="objetos">Objetos de Contratación</option>
                    <option value="estados">Estados de Contratación</option>
                    <option value="departamentos">Departamentos</option>
                </select>
            </div>

            <button
                wire:click="probarMaestras"
                wire:loading.attr="disabled"
                wire:target="probarMaestras"
                class="w-full px-5 py-3 bg-neutral-600 text-white rounded-full hover:bg-neutral-900 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors shadow-sm"
            >
                <svg wire:loading.remove wire:target="probarMaestras" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg wire:loading wire:target="probarMaestras" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="probarMaestras">Consultar Maestras</span>
                <span wire:loading wire:target="probarMaestras">Consultando...</span>
            </button>

            @if($resultadoMaestras)
                <div class="mt-4 p-4 rounded-2xl {{ $resultadoMaestras['success'] ? 'bg-secondary-500/10 border-2 border-secondary-500' : 'bg-primary-500/10 border-2 border-primary-500' }}">
                    <div class="flex items-start gap-3">
                        @if($resultadoMaestras['success'])
                            <svg class="w-5 h-5 text-secondary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">{{ $resultadoMaestras['message'] }}</p>
                                <div class="mt-2 space-y-1 text-xs text-neutral-600">
                                    <p>• Tipo: {{ $resultadoMaestras['data']['tipo'] }}</p>
                                    <p>• Total de registros: {{ $resultadoMaestras['data']['total_registros'] }}</p>
                                </div>
                            </div>
                        @else
                            <svg class="w-5 h-5 text-primary-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-neutral-900">Error</p>
                                <p class="text-xs text-neutral-600 mt-1">{{ $resultadoMaestras['error'] }}</p>
                            </div>
                        @endif
                    </div>

                    <details class="mt-3">
                        <summary class="text-xs text-neutral-600 cursor-pointer hover:text-neutral-900 font-medium">Ver respuesta completa</summary>
                        <pre class="mt-2 text-xs bg-neutral-900 text-secondary-500 p-3 rounded-xl overflow-x-auto">{{ json_encode($resultadoMaestras['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    // Listener para descargar archivos automáticamente SIN abrir nueva pestaña
    $wire.on('descargar-archivo', (event) => {
        // Crear un elemento <a> temporal invisible
        const link = document.createElement('a');
        link.href = event.url;
        link.download = ''; // Forzar descarga
        link.style.display = 'none';

        // Agregar al DOM, hacer click y remover
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>
@endscript
