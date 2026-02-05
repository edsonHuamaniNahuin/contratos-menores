@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-soft p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">Editar Cuenta SEACE</h1>
            <a href="{{ route('cuentas.index') }}" class="text-primary-500 hover:text-primary-400 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>

        @if($errors->any())
            <div class="bg-neutral-100 border-l-4 border-neutral-600 p-4 mb-6">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-neutral-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-neutral-900">Errores de validación:</h3>
                        <ul class="mt-2 text-sm text-neutral-600 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('cuentas.update', $cuenta) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- RUC Proveedor -->
                <div>
                    <label for="ruc" class="block text-sm font-medium text-neutral-600 mb-2">
                        RUC Proveedor <span class="text-neutral-900">*</span>
                    </label>
                    <input
                        type="text"
                        name="ruc"
                        id="ruc"
                        value="{{ old('ruc', $cuenta->username) }}"
                        maxlength="11"
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        placeholder="20123456789"
                    >
                    @error('ruc')
                        <p class="mt-1 text-sm text-neutral-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-medium text-neutral-600 mb-2">
                        Nombre/Alias <span class="text-neutral-900">*</span>
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        value="{{ old('nombre', $cuenta->nombre) }}"
                        required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        placeholder="Empresa XYZ"
                    >
                    @error('nombre')
                        <p class="mt-1 text-sm text-neutral-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-600 mb-2">
                        Nueva Contraseña SEACE
                    </label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-neutral-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-neutral-400">Dejar vacío para mantener la contraseña actual</p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-600 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $cuenta->email) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        placeholder="contacto@empresa.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-neutral-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label for="notas" class="block text-sm font-medium text-neutral-600 mb-2">
                    Notas
                </label>
                <textarea
                    name="notas"
                    id="notas"
                    rows="3"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    placeholder="Información adicional sobre esta cuenta..."
                >{{ old('notas', $cuenta->notas) }}</textarea>
                @error('notas')
                    <p class="mt-1 text-sm text-neutral-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Checkboxes -->
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="activa"
                        id="activa"
                        value="1"
                        {{ old('activa', $cuenta->activa) ? 'checked' : '' }}
                        class="w-4 h-4 text-primary-500 border-neutral-100 rounded focus:ring-primary-500"
                    >
                    <label for="activa" class="ml-2 text-sm text-neutral-600">
                        Cuenta activa
                    </label>
                </div>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        name="principal"
                        id="principal"
                        value="1"
                        {{ old('principal', $cuenta->principal) ? 'checked' : '' }}
                        class="w-4 h-4 text-primary-500 border-neutral-100 rounded focus:ring-primary-500"
                    >
                    <label for="principal" class="ml-2 text-sm text-neutral-600">
                        Establecer como cuenta principal
                    </label>
                </div>
            </div>

            <!-- Info de tokens -->
            @if($cuenta->access_token)
            <div class="bg-primary-100 border-l-4 border-primary-500 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-primary-400">Estado de tokens</h3>
                        <div class="mt-2 text-sm text-blue-700 space-y-1">
                            <p>• Access token: {{ $cuenta->token_valido ? '✓ Válido' : '✗ Expirado' }}</p>
                            @if($cuenta->token_expira_en)
                                <p>• Expira en: {{ $cuenta->token_expira_en }}</p>
                            @endif
                            @if($cuenta->ultimo_login_exitoso_at)
                                <p>• Último login: {{ $cuenta->ultimo_login_exitoso_at->diffForHumans() }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <a
                    href="{{ route('cuentas.index') }}"
                    class="px-6 py-2 border border-neutral-100 rounded-lg text-neutral-600 hover:bg-neutral-50 font-medium"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-400 font-medium"
                >
                    Actualizar Cuenta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
