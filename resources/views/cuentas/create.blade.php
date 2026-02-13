@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-neutral-900">Nueva Cuenta SEACE</h1>
                <p class="text-neutral-400 mt-1">Registra una nueva credencial de acceso al sistema</p>
            </div>
            <a href="{{ route('cuentas.index') }}" class="bg-neutral-600 hover:bg-neutral-900 text-white px-6 py-2.5 rounded-full transition-all duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
        </div>

        @if($errors->any())
            <div class="bg-primary-500/10 border-2 border-primary-500 rounded-2xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-primary-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-neutral-900 mb-2">Errores de validación:</h3>
                        <ul class="space-y-1 text-sm text-neutral-600 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('cuentas.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- RUC Proveedor -->
                <div>
                    <label for="ruc" class="block text-sm font-medium text-neutral-600 mb-2">
                        RUC Proveedor <span class="text-primary-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="ruc"
                        id="ruc"
                        value="{{ old('ruc') }}"
                        maxlength="11"
                        required
                        class="w-full px-4 py-2.5 border border-neutral-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('ruc') border-primary-500 @enderror"
                        placeholder="20123456789"
                    >
                    @error('ruc')
                        <p class="mt-1.5 text-sm text-primary-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nombre -->
                <div>
                    <label for="nombre" class="block text-sm font-medium text-neutral-600 mb-2">
                        Nombre/Alias <span class="text-primary-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        value="{{ old('nombre') }}"
                        required
                        class="w-full px-4 py-2.5 border border-neutral-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('nombre') border-primary-500 @enderror"
                        placeholder="Empresa XYZ"
                    >
                    @error('nombre')
                        <p class="mt-1.5 text-sm text-primary-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-600 mb-2">
                        Contraseña SEACE <span class="text-primary-500">*</span>
                    </label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        class="w-full px-4 py-2.5 border border-neutral-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('password') border-primary-500 @enderror"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="mt-1.5 text-sm text-primary-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-neutral-400">La contraseña se almacenará encriptada</p>
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
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 border border-neutral-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('email') border-primary-500 @enderror"
                        placeholder="usuario@ejemplo.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-sm text-primary-500">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-neutral-400">Para notificaciones futuras</p>
                </div>
            </div>

            <!-- Checkbox Cuenta Activa -->
            <div class="flex items-center gap-3 p-4 bg-neutral-50 rounded-xl">
                <input
                    type="checkbox"
                    name="activa"
                    id="activa"
                    value="1"
                    {{ old('activa', true) ? 'checked' : '' }}
                    class="w-5 h-5 text-primary-500 border-neutral-300 rounded focus:ring-2 focus:ring-primary-500"
                >
                <label for="activa" class="text-sm text-neutral-600">
                    <span class="font-medium">Cuenta activa</span>
                    <span class="block text-xs text-neutral-400 mt-0.5">Esta cuenta podrá usarse inmediatamente para conectar con SEACE</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-6 border-t border-neutral-100">
                <a
                    href="{{ route('cuentas.index') }}"
                    class="px-8 py-2.5 border border-neutral-100 rounded-full text-neutral-600 hover:bg-neutral-50 font-medium transition-all"
                >
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="px-8 py-2.5 bg-primary-500 text-white rounded-full hover:bg-primary-400 font-medium transition-all shadow-sm"
                >
                    Crear Cuenta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
