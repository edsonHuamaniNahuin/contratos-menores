@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-neutral-900">Detalle de Cuenta SEACE</h1>
            <div class="flex gap-3">
                <a href="{{ route('cuentas.edit', $cuenta) }}" class="px-5 py-2.5 bg-primary-500 text-white rounded-full hover:bg-primary-400 flex items-center gap-2 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>
                <a href="{{ route('cuentas.index') }}" class="px-5 py-2.5 border-2 border-neutral-100 rounded-full text-neutral-600 hover:bg-neutral-50 flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <!-- Status Badges -->
        <div class="flex gap-3 mb-6">
            @if($cuenta->activa)
                <span class="px-4 py-2 bg-secondary-500/10 text-secondary-500 border-2 border-secondary-500/20 rounded-full text-sm font-semibold">✓ Activa</span>
            @else
                <span class="px-4 py-2 bg-neutral-100 text-neutral-600 border-2 border-neutral-200 rounded-full text-sm font-semibold">✗ Inactiva</span>
            @endif

            @if($cuenta->principal)
                <span class="px-4 py-2 bg-primary-500/10 text-primary-500 border-2 border-primary-500/20 rounded-full text-sm font-semibold">★ Principal</span>
            @endif

            @if($cuenta->token_valido)
                <span class="px-4 py-2 bg-secondary-500/10 text-secondary-500 border-2 border-secondary-500/20 rounded-full text-sm font-semibold">🔑 Token Válido</span>
            @else
                <span class="px-4 py-2 bg-neutral-100 text-neutral-600 border-2 border-neutral-200 rounded-full text-sm font-semibold">🔑 Sin Token</span>
            @endif
        </div>

        <!-- Información Básica -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">RUC Proveedor</h3>
                    <p class="text-xl font-bold text-neutral-900">{{ $cuenta->username }}</p>
                </div>

                <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Nombre/Alias</h3>
                    <p class="text-xl font-bold text-neutral-900">{{ $cuenta->nombre }}</p>
                </div>

                @if($cuenta->email)
                <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Email</h3>
                    <p class="text-xl font-bold text-neutral-900">{{ $cuenta->email }}</p>
                </div>
                @endif

                <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                    <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Fecha de Creación</h3>
                    <p class="text-lg font-bold text-neutral-900">{{ $cuenta->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-xs text-neutral-400 mt-1">{{ $cuenta->created_at->diffForHumans() }}</p>
                </div>
            </div>

            <!-- Notas -->
            @if($cuenta->notas)
            <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-3">Notas</h3>
                <p class="text-neutral-900 whitespace-pre-wrap leading-relaxed">{{ $cuenta->notas }}</p>
            </div>
            @endif

            <!-- Información de Tokens -->
            <div class="border-t-2 border-neutral-100 pt-6">
                <h2 class="text-2xl font-bold text-neutral-900 mb-6">Estado de Autenticación</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($cuenta->access_token)
                    <div class="bg-secondary-500/5 p-6 rounded-2xl border-2 border-secondary-500/20">
                        <h3 class="text-sm font-bold text-secondary-500 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Access Token
                        </h3>
                        <p class="text-xs font-mono text-neutral-600 break-all mb-3 bg-white p-2 rounded">{{ Str::limit($cuenta->access_token, 50) }}</p>
                        <div class="space-y-1 text-sm text-neutral-600">
                            @if($cuenta->access_token_expira_at)
                            <p class="flex items-center gap-2">
                                <span class="w-1 h-1 rounded-full bg-secondary-500"></span>
                                Expira: <strong class="text-neutral-900">{{ $cuenta->access_token_expira_at->format('d/m/Y H:i:s') }}</strong>
                            </p>
                            <p class="flex items-center gap-2">
                                <span class="w-1 h-1 rounded-full {{ $cuenta->token_valido ? 'bg-secondary-500' : 'bg-neutral-400' }}"></span>
                                {{ $cuenta->token_valido ? '✓ Válido' : '✗ Expirado' }}
                            </p>
                            @if($cuenta->token_expira_en)
                            <p class="flex items-center gap-2">
                                <span class="w-1 h-1 rounded-full bg-secondary-500"></span>
                                Tiempo restante: <strong class="text-neutral-900">{{ $cuenta->token_expira_en }}</strong>
                            </p>
                            @endif
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="bg-neutral-50 p-6 rounded-2xl border-2 border-neutral-100">
                        <h3 class="text-sm font-bold text-neutral-600 mb-3">Access Token</h3>
                        <p class="text-sm text-neutral-600">No hay token activo. Realiza login desde la sección de pruebas.</p>
                    </div>
                    @endif

                    @if($cuenta->refresh_token)
                    <div class="bg-primary-500/5 p-6 rounded-2xl border-2 border-primary-500/20">
                        <h3 class="text-sm font-bold text-primary-500 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh Token
                        </h3>
                        <p class="text-xs font-mono text-neutral-600 break-all mb-3 bg-white p-2 rounded">{{ Str::limit($cuenta->refresh_token, 50) }}</p>
                        @if($cuenta->refresh_token_expira_at)
                        <div class="space-y-1 text-sm text-neutral-600">
                            <p class="flex items-center gap-2">
                                <span class="w-1 h-1 rounded-full bg-primary-500"></span>
                                Expira: <strong class="text-neutral-900">{{ $cuenta->refresh_token_expira_at->format('d/m/Y H:i:s') }}</strong>
                            </p>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="bg-neutral-50 p-6 rounded-2xl border-2 border-neutral-100">
                        <h3 class="text-sm font-bold text-neutral-600 mb-3">Refresh Token</h3>
                        <p class="text-sm text-neutral-600">No disponible</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Estadísticas de Uso -->
            <div class="border-t-2 border-neutral-100 pt-6">
                <h2 class="text-2xl font-bold text-neutral-900 mb-6">Estadísticas de Uso</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-primary-500/5 p-5 rounded-2xl border-2 border-primary-500/20 text-center">
                        <p class="text-4xl font-bold text-primary-500">{{ $cuenta->total_logins }}</p>
                        <p class="text-xs text-neutral-600 mt-2 font-semibold">Total Logins</p>
                    </div>

                    <div class="bg-secondary-500/5 p-5 rounded-2xl border-2 border-secondary-500/20 text-center">
                        <p class="text-4xl font-bold text-secondary-500">{{ $cuenta->logins_exitosos }}</p>
                        <p class="text-xs text-neutral-600 mt-2 font-semibold">Exitosos</p>
                    </div>

                    <div class="bg-neutral-100 p-5 rounded-2xl border-2 border-neutral-200 text-center">
                        <p class="text-4xl font-bold text-neutral-600">{{ $cuenta->logins_fallidos }}</p>
                        <p class="text-xs text-neutral-600 mt-2 font-semibold">Fallidos</p>
                    </div>

                    <div class="bg-primary-500/10 p-5 rounded-2xl border-2 border-primary-500/30 text-center">
                        <p class="text-4xl font-bold text-primary-500">{{ $cuenta->total_consultas }}</p>
                        <p class="text-xs text-neutral-600 mt-2 font-semibold">Consultas API</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    @if($cuenta->ultimo_login_at)
                    <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                        <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Último Intento</h3>
                        <p class="text-base font-bold text-neutral-900">{{ $cuenta->ultimo_login_at->format('d/m/Y H:i:s') }}</p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $cuenta->ultimo_login_at->diffForHumans() }}</p>
                    </div>
                    @endif

                    @if($cuenta->ultimo_login_exitoso_at)
                    <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                        <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Login Exitoso</h3>
                        <p class="text-base font-bold text-neutral-900">{{ $cuenta->ultimo_login_exitoso_at->format('d/m/Y H:i:s') }}</p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $cuenta->ultimo_login_exitoso_at->diffForHumans() }}</p>
                    </div>
                    @endif

                    @if($cuenta->ultima_consulta_at)
                    <div class="bg-neutral-50 p-5 rounded-2xl border border-neutral-100">
                        <h3 class="text-xs font-semibold text-neutral-400 uppercase tracking-wider mb-2">Última Consulta</h3>
                        <p class="text-base font-bold text-neutral-900">{{ $cuenta->ultima_consulta_at->format('d/m/Y H:i:s') }}</p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $cuenta->ultima_consulta_at->diffForHumans() }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Acciones -->
            <div class="border-t-2 border-neutral-100 pt-6">
                <h2 class="text-2xl font-bold text-neutral-900 mb-6">Acciones</h2>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('prueba-endpoints') }}?cuenta={{ $cuenta->id }}" class="px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-full hover:from-purple-700 hover:to-indigo-700 flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Probar Endpoints
                    </a>

                    @if(!$cuenta->principal && $cuenta->activa)
                    <form action="{{ route('cuentas.set-principal', $cuenta) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-5 py-3 bg-primary-500 text-white rounded-full hover:bg-primary-400 flex items-center gap-2 shadow-sm hover:shadow-md transition-all">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            Establecer como Principal
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('cuentas.toggle-active', $cuenta) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-5 py-3 {{ $cuenta->activa ? 'bg-neutral-600 hover:bg-neutral-900' : 'bg-secondary-500 hover:bg-secondary-400' }} text-white rounded-full flex items-center gap-2 shadow-sm hover:shadow-md transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            {{ $cuenta->activa ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>

                    <form action="{{ route('cuentas.destroy', $cuenta) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta cuenta?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-5 py-3 bg-neutral-600 hover:bg-neutral-900 text-white rounded-full flex items-center gap-2 shadow-sm hover:shadow-md transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
