<div class="p-4 sm:p-6 flex flex-col gap-6 w-full max-w-full min-w-0">
    <div class="bg-white rounded-3xl shadow-soft p-4 sm:p-8 border border-neutral-100">
        <h1 class="text-xl sm:text-3xl font-bold text-neutral-900">🔐 Roles y Permisos</h1>
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
        <div class="flex items-center justify-between mb-6 flex-col sm:flex-row gap-4">
            <div>
                <h2 class="text-xl font-bold text-neutral-900">Usuarios y roles</h2>
                <p class="text-xs text-neutral-400 mt-1">Asigna un rol principal por usuario.</p>
            </div>
            <div class="relative w-full sm:w-80">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       wire:model.live.debounce.400ms="busquedaUsuario"
                       placeholder="Buscar por nombre o email..."
                       class="w-full pl-9 pr-8 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                @if($busquedaUsuario !== '')
                    <button wire:click="$set('busquedaUsuario', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Usuario</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Email</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Rol</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-neutral-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($users as $user)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-neutral-900">{{ $user->name }}</p>
                            </td>
                            <td class="py-3 px-4 text-neutral-500 text-xs">{{ $user->email }}</td>
                            <td class="py-3 px-4">
                                <select
                                    class="px-3 py-1.5 rounded-full border border-neutral-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none text-xs"
                                    wire:model="userRoles.{{ $user->id }}"
                                    wire:change="guardarRolUsuario({{ $user->id }})"
                                >
                                    <option value="">Selecciona rol</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    @if($user->id !== auth()->id())
                                        <button
                                            x-data
                                            x-on:click="
                                                if (confirm('¿Seguro que deseas dar de baja a {{ addslashes($user->name) }}? El usuario perderá acceso al sistema.')) {
                                                    $wire.darDeBaja({{ $user->id }})
                                                }
                                            "
                                            class="px-3 py-1.5 rounded-full border border-red-200 text-red-600 text-xs font-medium hover:bg-red-50 transition-colors"
                                        >
                                            Dar de baja
                                        </button>
                                    @else
                                        <span class="text-xs text-neutral-300 italic">Tú</span>
                                    @endif
                                    <button
                                        wire:click="selectUsuarioPermisos({{ $user->id }})"
                                        class="px-3 py-1.5 rounded-full border border-primary-200 text-primary-600 text-xs font-medium hover:bg-primary-50 transition-colors"
                                        title="Gestionar permisos individuales de este usuario"
                                    >
                                        ⚙ Permisos
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 bg-neutral-50 rounded-2xl border border-neutral-200 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-xs text-neutral-500">
                Mostrando <span class="font-semibold text-neutral-900">{{ $users->firstItem() }}</span> a <span class="font-semibold text-neutral-900">{{ $users->lastItem() }}</span> de <span class="font-semibold text-neutral-900">{{ $users->total() }}</span> usuarios
            </div>
            <div class="flex items-center gap-2">
                <button
                    wire:click="previousPage"
                    class="px-3 py-1.5 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:border-primary-400 transition-colors disabled:opacity-40"
                    @if($users->onFirstPage()) disabled @endif
                >
                    ← Anterior
                </button>
                <span class="text-xs text-neutral-500 font-medium">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
                <button
                    wire:click="nextPage"
                    class="px-3 py-1.5 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:border-primary-400 transition-colors disabled:opacity-40"
                    @if(! $users->hasMorePages()) disabled @endif
                >
                    Siguiente →
                </button>
            </div>
        </div>
    </div>

    {{-- Gestor de permisos directos por usuario --}}
    @if($permisosUsuarioId)
        @php $usuarioPermisos = $users->getCollection()->firstWhere('id', $permisosUsuarioId); @endphp
        <div class="bg-white rounded-3xl shadow-soft p-6 sm:p-8 border border-primary-200" wire:key="gestor-permisos-{{ $permisosUsuarioId }}">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900">⚙ Permisos individuales</h2>
                    <p class="text-xs text-neutral-400 mt-1">
                        @if($usuarioPermisos)
                            <strong class="text-neutral-700">{{ $usuarioPermisos->name }}</strong> — {{ $usuarioPermisos->email }}
                        @endif
                        · Los permisos directos se SUMAN a los del rol, no los reemplazan.
                    </p>
                </div>
                <button wire:click="cerrarPermisosUsuario" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900">
                    Cerrar
                </button>
            </div>

            {{-- Filtro (opcional) --}}
            <div class="relative mb-5 max-w-md">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busquedaPermiso"
                    placeholder="Filtrar permisos por nombre (ej: 'adjudicación')..."
                    class="w-full pl-9 pr-3 py-2.5 rounded-xl text-sm bg-neutral-50 border border-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if($busquedaPermiso !== '')
                    <button wire:click="$set('busquedaPermiso', '')" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

            {{-- Permisos agrupados (misma mecánica que "Permisos por rol") --}}
            <div>
                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-3">
                    Permisos ({{ collect($this->permisosGruposUsuario)->sum(fn($g) => count($g['permissions'])) }} mostrados · {{ count($permisosUsuarioActuales) }} tiene)
                </p>

                @if(count($this->permisosGruposUsuario) === 0)
                    <p class="text-xs text-neutral-400">Sin permisos para mostrar.</p>
                @else
                    <div class="space-y-5">
                        @foreach($this->permisosGruposUsuario as $grupo)
                            <div>
                                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">{{ $grupo['name'] }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($grupo['permissions'] as $perm)
                                        @if($perm['origen'] === 'directo')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-green-50 text-green-700 border border-green-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ $perm['name'] }}
                                                <button
                                                    wire:click="quitarPermisoDirecto({{ $perm['id'] }})"
                                                    title="Quitar permiso directo (solo este usuario)"
                                                    class="text-green-600 hover:text-red-600 transition-colors"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </span>
                                        @elseif($perm['origen'] === 'rol')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200" title="Viene del rol asignado — no se puede quitar aquí">
                                                <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                {{ $perm['name'] }}
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="agregarPermisoDirecto({{ $perm['id'] }})"
                                                title="Agregar permiso directo (solo este usuario)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold border transition-all cursor-pointer bg-white text-neutral-600 border-neutral-200 hover:border-primary-400 hover:text-primary-600"
                                            >
                                                <svg class="w-3 h-3 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                {{ $perm['name'] }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-neutral-400 mt-3">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-neutral-300 inline-block"></span> del rol (no editable aquí)</span>
                        ·
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> directo — clic en la ✕ para quitar</span>
                        ·
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-white border border-neutral-300 inline-block"></span> no asignado — clic para agregar directo</span>
                    </p>
                @endif
            </div>
        </div>
    @endif

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
                    </div>

                    <div class="space-y-4">
                        @foreach($permissionGroups as $group)
                            <div>
                                <p class="text-xs font-semibold text-neutral-500 uppercase tracking-wider mb-2">{{ $group['name'] }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($group['permissions'] as $permission)
                                        @php
                                            $isChecked = collect($rolePermissions[$role['id']] ?? [])->contains($permission['id']);
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="togglePermiso({{ $role['id'] }}, {{ $permission['id'] }})"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold border transition-all cursor-pointer
                                                {{ $isChecked
                                                    ? 'bg-secondary-500 text-white border-secondary-500 shadow'
                                                    : 'bg-white text-neutral-600 border-neutral-200 hover:border-primary-400' }}"
                                        >
                                            @if($isChecked)
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                            {{ $permission['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
