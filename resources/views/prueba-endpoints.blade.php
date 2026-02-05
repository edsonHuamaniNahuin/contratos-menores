@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-3xl shadow-soft p-6 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Prueba de Endpoints SEACE</h1>
                <p class="text-sm text-neutral-600 mt-2">Prueba la conectividad y respuestas de los endpoints de la API SEACE</p>
            </div>
            <a href="{{ route('cuentas.index') }}" class="px-5 py-2.5 bg-neutral-600 text-white rounded-full text-sm font-medium hover:bg-neutral-900 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver a Cuentas
            </a>
        </div>
    </div>

    <!-- Instrucciones -->
    <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-6 mb-6">
        <div class="flex">
            <div class="shrink-0">
                <svg class="h-6 w-6 text-secondary-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-bold text-neutral-900">Instrucciones de uso</h3>
                <div class="mt-2 text-sm text-neutral-600">
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Selecciona una cuenta SEACE activa del menú desplegable</li>
                        <li>Ejecuta <strong class="font-semibold text-neutral-900">Login</strong> primero para obtener los tokens de acceso</li>
                        <li>Una vez autenticado, puedes probar los demás endpoints (Buscador, Maestras)</li>
                        <li>Usa <strong class="font-semibold text-neutral-900">Refresh Token</strong> cuando el access token expire</li>
                        <li>Los tokens se almacenan automáticamente en la base de datos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @livewire('prueba-endpoints', ['cuentaId' => request()->get('cuenta')])
</div>
@endsection
