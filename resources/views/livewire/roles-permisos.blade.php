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
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-neutral-900">Usuarios y roles</h2>
                <p class="text-xs text-neutral-400 mt-1">Asigna un rol principal por usuario.</p>
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
