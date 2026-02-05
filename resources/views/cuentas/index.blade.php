@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    {{-- Header con acciones --}}
    <div class="bg-white rounded-3xl shadow-soft p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-neutral-900 flex items-center gap-3">
                    <svg class="w-7 h-7 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Gestión de Cuentas SEACE
                </h2>
                <p class="text-neutral-400 mt-1">Administra las credenciales de acceso al sistema SEACE</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('prueba-endpoints') }}" class="bg-neutral-600 hover:bg-neutral-900 text-white px-6 py-2.5 rounded-full transition-all duration-200 flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Probar Endpoints
                </a>
                <a href="{{ route('cuentas.create') }}" class="bg-primary-500 hover:bg-primary-400 text-white px-6 py-2.5 rounded-full transition-all duration-200 flex items-center gap-2 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Cuenta
                </a>
            </div>
        </div>
    </div>

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="bg-secondary-500/10 border-2 border-secondary-500 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <svg class="w-6 h-6 text-secondary-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="text-neutral-900 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Tabla de cuentas --}}
    <div class="bg-white rounded-3xl shadow-soft overflow-hidden">
        <div class="bg-primary-500 px-6 py-4">
            <h5 class="text-white font-semibold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Lista de Cuentas ({{ $cuentas->count() }})
            </h5>
        </div>

        <div class="p-6">
            @if($cuentas->isEmpty())
                <div class="text-center py-16">
                    <svg class="w-20 h-20 text-neutral-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-neutral-400 mb-6">No hay cuentas registradas</p>
                    <a href="{{ route('cuentas.create') }}" class="bg-primary-500 hover:bg-primary-400 text-white px-8 py-3 rounded-full transition-all duration-200 inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crear Primera Cuenta
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-neutral-100">
                                <th class="text-left py-3 px-4 text-neutral-900 font-semibold text-sm">ID</th>
                                <th class="text-left py-3 px-4 text-neutral-900 font-semibold text-sm">Nombre</th>
                                <th class="text-left py-3 px-4 text-neutral-900 font-semibold text-sm">RUC</th>
                                <th class="text-left py-3 px-4 text-neutral-900 font-semibold text-sm">Email</th>
                                <th class="text-center py-3 px-4 text-neutral-900 font-semibold text-sm">Estado</th>
                                <th class="text-center py-3 px-4 text-neutral-900 font-semibold text-sm">Último Login</th>
                                <th class="text-center py-3 px-4 text-neutral-900 font-semibold text-sm">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuentas as $cuenta)
                                <tr class="border-b border-neutral-100 hover:bg-neutral-50 transition-colors">
                                    <td class="py-4 px-4 text-neutral-600">{{ $cuenta->id }}</td>
                                    <td class="py-4 px-4">
                                        <span class="text-neutral-900 font-semibold">{{ $cuenta->nombre }}</span>
                                    </td>
                                    <td class="py-4 px-4"><code class="text-neutral-600 bg-neutral-50 px-2 py-1 rounded">{{ $cuenta->username }}</code></td>
                                    <td class="py-4 px-4 text-neutral-600">{{ $cuenta->email ?? '-' }}</td>
                                    <td class="py-4 px-4 text-center">
                                        <form action="{{ route('cuentas.toggle-activa', $cuenta) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded-full text-xs font-medium transition-colors
                                                @if($cuenta->activa) bg-secondary-500/10 text-secondary-500 hover:bg-secondary-500/20
                                                @else bg-neutral-100 text-neutral-600 hover:bg-neutral-200
                                                @endif">
                                                {{ $cuenta->activa ? 'Activa' : 'Inactiva' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if($cuenta->last_login_at)
                                            <span class="text-neutral-600 text-sm">{{ $cuenta->last_login_at->diffForHumans() }}</span>
                                        @else
                                            <span class="text-neutral-400 text-sm">Nunca</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('cuentas.show', $cuenta) }}" class="text-neutral-600 hover:text-neutral-900 transition-colors" title="Ver">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>

                                            <a href="{{ route('cuentas.edit', $cuenta) }}" class="text-primary-500 hover:text-primary-400 transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>

                                            <form action="{{ route('cuentas.destroy', $cuenta) }}" method="POST" class="inline" onsubmit="return confirm('¿Está seguro de eliminar esta cuenta?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-primary-500 hover:text-primary-400 transition-colors" title="Eliminar">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
