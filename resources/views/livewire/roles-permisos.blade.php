<div class="space-y-6">
    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <h1 class="text-3xl font-bold text-neutral-900">🔐 Roles y Permisos</h1>
        <p class="text-sm text-neutral-400 mt-2">
            Mantiene el acceso a vistas y funciones clave del sistema.
        </p>
    </div>

    @if(session()->has('success'))
        <div class="bg-secondary-500/10 border-l-4 border-secondary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if($errorMessage)
        <div class="bg-primary-500/10 border-l-4 border-primary-500 rounded-2xl p-4">
            <p class="text-sm text-neutral-900 font-medium">❌ {{ $errorMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-neutral-900">Usuarios y roles</h2>
                <p class="text-xs text-neutral-400 mt-1">Asigna un rol principal por usuario.</p>
            </div>
        </div>

        <div class="space-y-3">
            @foreach($users as $user)
                <div class="bg-neutral-50 rounded-2xl p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-neutral-900">{{ $user['name'] }}</p>
                        <p class="text-xs text-neutral-500">{{ $user['email'] }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select
                            class="px-4 py-2 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-sm"
                            wire:model="userRoles.{{ $user['id'] }}"
                            wire:change="guardarRolUsuario({{ $user['id'] }})"
                        >
                            <option value="">Selecciona rol</option>
                            @foreach($roles as $role)
                                <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-soft p-8 border border-neutral-100">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-neutral-900">Permisos por rol</h2>
                <p class="text-xs text-neutral-400 mt-1">Activa o desactiva permisos por cada rol.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            @foreach($roles as $role)
                <div class="bg-neutral-50 rounded-2xl p-5 border border-neutral-200">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-bold text-neutral-900">{{ $role['name'] }}</p>
                            <p class="text-xs text-neutral-500">{{ $role['description'] }}</p>
                        </div>
                        <button
                            wire:click="guardarPermisos({{ $role['id'] }})"
                            class="px-4 py-2 bg-primary-500 text-white rounded-full text-xs font-semibold hover:bg-primary-400 transition-colors"
                        >
                            Guardar permisos
                        </button>
                    </div>

                    <div class="space-y-2">
                        @foreach($permissions as $permission)
                            <label class="flex items-center justify-between gap-3 bg-white rounded-2xl border border-neutral-200 px-4 py-2">
                                <div>
                                    <p class="text-xs font-semibold text-neutral-900">{{ $permission['name'] }}</p>
                                    <p class="text-[11px] text-neutral-500">{{ $permission['slug'] }}</p>
                                </div>
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-neutral-300 text-primary-500 focus:ring-primary-500"
                                    wire:model="rolePermissions.{{ $role['id'] }}"
                                    value="{{ $permission['id'] }}"
                                >
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
