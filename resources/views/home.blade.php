@extends('layouts.app')

@section('content')
<!-- Hero Card con estadísticas principales -->
<div class="bg-white rounded-[2rem] shadow-soft p-8 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-neutral-400 mb-2">Sistema de Monitoreo SEACE</p>
            <h2 class="text-4xl font-bold text-neutral-900 mb-3">Contratos & Convocatorias</h2>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-secondary-200 text-neutral-900">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    {{ \App\Models\Contrato::where('id_estado_contrato', 2)->count() }} Vigentes
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600">
                    {{ \App\Models\CuentaSeace::activa()->count() }} cuenta(s) activa(s)
                </span>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('cuentas.index') }}" class="px-5 py-2.5 bg-neutral-100 text-neutral-600 rounded-full text-sm font-medium hover:bg-neutral-200 transition-colors inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Cuentas
            </a>
        </div>
    </div>
</div>

<!-- Dashboard de Contratos -->
                <p class="text-xs text-neutral-400">Total</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-secondary-500">{{ \App\Models\CuentaSeace::activas()->count() }}</p>
                <p class="text-xs text-neutral-400">Activas</p>
            </div>
        </div>
    </a>

    <!-- Módulo: Prueba Endpoints -->
    <a href="{{ route('prueba-endpoints') }}" class="bg-white rounded-3xl shadow-soft p-6 hover:shadow-lg transition-shadow group">
        <div class="flex items-start justify-between mb-4">
            <div>
                <span class="inline-block px-3 py-1 bg-secondary-500/10 text-secondary-500 rounded-full text-xs font-medium mb-3">
                    DIAGNÓSTICO
                </span>
                <h3 class="text-lg font-bold text-neutral-900">Prueba Endpoints</h3>
                <p class="text-sm text-neutral-400 mt-1">Verificar conectividad API</p>
            </div>
            <div class="w-12 h-12 bg-secondary-500/10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-neutral-100">
            <div>
                <p class="text-2xl font-bold text-neutral-900">4</p>
                <p class="text-xs text-neutral-400">Endpoints</p>
            </div>
            <div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-secondary-100 text-secondary-500">
                    API Ready
                </span>
            </div>
        </div>
    </a>

    <!-- Widget de Estado del Sistema -->
    <div class="bg-white rounded-3xl shadow-soft p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <span class="inline-block px-3 py-1 bg-neutral-100 text-neutral-600 rounded-full text-xs font-medium mb-3">
                    SISTEMA
                </span>
                <h3 class="text-lg font-bold text-neutral-900">Estado</h3>
                <p class="text-sm text-neutral-400 mt-1">Monitoreo en tiempo real</p>
            </div>
            <div class="w-12 h-12 bg-neutral-100 rounded-2xl flex items-center justify-center">
                <svg class="w-6 h-6 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="space-y-3 pt-4 border-t border-neutral-100">
            <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-600">Base de Datos</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-secondary-100 text-secondary-500">
                    <span class="w-1.5 h-1.5 bg-secondary-500 rounded-full mr-1.5"></span>
                    Online
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-600">API SEACE</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-secondary-100 text-secondary-500">
                    <span class="w-1.5 h-1.5 bg-secondary-500 rounded-full mr-1.5"></span>
                    Connected
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-3xl shadow-soft p-6">
    <h3 class="text-lg font-bold text-neutral-900 mb-4">Acciones Rápidas</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('cuentas.create') }}" class="flex items-center gap-3 p-4 rounded-2xl border border-neutral-100 hover:border-primary-500 hover:bg-primary-500/5 transition-all group">
            <div class="w-10 h-10 bg-primary-500/10 rounded-xl flex items-center justify-center group-hover:bg-primary-500 group-hover:text-white transition-colors">
                <svg class="w-5 h-5 text-primary-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900">Nueva Cuenta</p>
                <p class="text-xs text-neutral-400">Agregar credencial SEACE</p>
            </div>
        </a>

        <a href="{{ route('prueba-endpoints') }}" class="flex items-center gap-3 p-4 rounded-2xl border border-neutral-100 hover:border-secondary-500 hover:bg-secondary-500/5 transition-all group">
            <div class="w-10 h-10 bg-secondary-500/10 rounded-xl flex items-center justify-center group-hover:bg-secondary-500 group-hover:text-white transition-colors">
                <svg class="w-5 h-5 text-secondary-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900">Probar API</p>
                <p class="text-xs text-neutral-400">Verificar endpoints</p>
            </div>
        </a>

        <a href="{{ route('cuentas.index') }}" class="flex items-center gap-3 p-4 rounded-2xl border border-neutral-100 hover:border-neutral-300 hover:bg-neutral-50 transition-all group">
            <div class="w-10 h-10 bg-neutral-100 rounded-xl flex items-center justify-center group-hover:bg-neutral-200 transition-colors">
                <svg class="w-5 h-5 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-neutral-900">Ver Todas</p>
                <p class="text-xs text-neutral-400">Lista completa</p>
            </div>
        </a>
    </div>
</div>

<!-- Dashboard de Contratos Livewire -->
<div class="mt-6">
    @livewire('contratos-dashboard')
</div>
@endsection
