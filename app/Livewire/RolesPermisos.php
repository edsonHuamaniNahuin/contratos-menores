<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Models\PremiumAuditLog;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PremiumAuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RolesPermisos extends Component
{
    use WithPagination;

    public array $roles = [];
    public array $permissions = [];
    public array $permissionGroups = [];
    public array $rolePermissions = [];
    public array $userRoles = [];
    public ?string $errorMessage = null;
    public int $perPage = 8;
    public string $busquedaUsuario = '';

    // ── Gestor de permisos directos por usuario ──
    public ?int $permisosUsuarioId = null;
    public string $busquedaPermiso = '';
    public string $estadoFiltroPermisos = 'todos'; // todos | no-asignados | directos
    public array $permisosUsuarioActuales = [];

    public function updatedBusquedaUsuario(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->loadRolesAndPermissions();
    }

    public function togglePermiso(int $roleId, int $permissionId): void
    {
        $current = array_map('intval', $this->rolePermissions[$roleId] ?? []);

        if (in_array($permissionId, $current, true)) {
            $this->rolePermissions[$roleId] = array_values(array_diff($current, [$permissionId]));
        } else {
            $current[] = $permissionId;
            $this->rolePermissions[$roleId] = $current;
        }

        // Persistir inmediatamente en BD
        $role = Role::findOrFail($roleId);
        $role->permissions()->sync($this->rolePermissions[$roleId]);
    }

    public function guardarRolUsuario(int $userId): void
    {
        $roleId = $this->userRoles[$userId] ?? null;
        if (!$roleId) {
            $this->errorMessage = 'Selecciona un rol valido.';
            return;
        }

        $user = User::with('roles')->findOrFail($userId);

        if ($this->isLastAdminChange($userId, (int) $roleId)) {
            $this->errorMessage = 'No puedes dejar el sistema sin al menos un administrador.';
            return;
        }

        $wasPremium = $user->hasRole('proveedor-premium');
        $newRole    = Role::findOrFail($roleId);
        $willBePremium = $newRole->slug === 'proveedor-premium';

        DB::transaction(function () use ($user, $roleId, $wasPremium, $willBePremium) {
            $user->roles()->sync([$roleId]);

            // ── Si PIERDE premium: cancelar suscripción activa + audit ──
            if ($wasPremium && !$willBePremium) {
                $activeSub = $user->activeSubscription();
                if ($activeSub) {
                    $activeSub->cancel();
                }

                PremiumAuditService::logRevoked(
                    $user,
                    PremiumAuditLog::SOURCE_ADMIN_ROLE,
                    $activeSub,
                    Auth::id(),
                    ['reason' => 'Rol cambiado por administrador']
                );
            }

            // ── Si GANA premium vía cambio de rol: crear suscripción admin + audit ──
            if (!$wasPremium && $willBePremium) {
                // Expirar suscripciones anteriores
                $user->subscriptions()
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->update(['status' => Subscription::STATUS_EXPIRED]);

                $subscription = $user->subscriptions()->create([
                    'plan'     => 'monthly',
                    'status'   => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at'  => now()->addDays(30),
                    'amount'   => 0,
                    'currency' => 'PEN',
                    'metadata' => [
                        'granted_by'  => 'admin_role_change',
                        'admin_id'    => Auth::id(),
                        'granted_at'  => now()->toDateTimeString(),
                    ],
                ]);

                PremiumAuditService::logGranted(
                    $user,
                    PremiumAuditLog::SOURCE_ADMIN_ROLE,
                    $subscription,
                    Auth::id(),
                    ['reason' => 'Rol asignado por administrador']
                );
            }
        });

        $this->userRoles[$userId] = $roleId;

        session()->flash('success', '✅ Rol actualizado');
        $this->loadRolesAndPermissions();
    }

    public function render()
    {
        $busqueda = trim($this->busquedaUsuario);

        $users = User::with('roles')
            ->when($busqueda !== '', function ($q) use ($busqueda) {
                $q->where(function ($sub) use ($busqueda) {
                    $sub->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        $this->syncUserRoles($users->getCollection());

        if ($this->permisosUsuarioId) {
            $this->cargarPermisosUsuario();
        }

        return view('livewire.roles-permisos', [
            'users' => $users,
        ]);
    }

    // ── Gestor de permisos directos por usuario ────────────────────

    public function selectUsuarioPermisos(int $userId): void
    {
        $this->permisosUsuarioId = $userId;
        $this->busquedaPermiso = '';
        $this->estadoFiltroPermisos = 'todos';
        $this->cargarPermisosUsuario();
    }

    public function cerrarPermisosUsuario(): void
    {
        $this->permisosUsuarioId = null;
        $this->busquedaPermiso = '';
        $this->estadoFiltroPermisos = 'todos';
        $this->permisosUsuarioActuales = [];
    }

    /**
     * Cargar los permisos actuales del usuario seleccionado.
     * Cada item: ['id', 'name', 'slug', 'origen' => 'rol'|'directo'].
     */
    protected function cargarPermisosUsuario(): void
    {
        $this->permisosUsuarioActuales = [];

        if (!$this->permisosUsuarioId) {
            return;
        }

        $user = User::with(['roles.permissions', 'directPermissions'])
            ->find($this->permisosUsuarioId);

        if (!$user) {
            return;
        }

        $items = [];

        foreach ($user->roles->flatMap(fn (Role $role) => $role->permissions) as $p) {
            $items[] = ['id' => (int) $p->id, 'name' => $p->name, 'slug' => $p->slug, 'origen' => 'rol'];
        }

        foreach ($user->directPermissions as $p) {
            $items[] = ['id' => (int) $p->id, 'name' => $p->name, 'slug' => $p->slug, 'origen' => 'directo'];
        }

        // Un permiso puede venir del rol Y ser directo: mostrar una sola vez (prioridad rol)
        $this->permisosUsuarioActuales = collect($items)
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * Todos los permisos agrupados por categoría, con el estado del usuario
     * seleccionado (igual mecánica visual que "Permisos por rol").
     *
     * Filtros:
     *  - $busquedaPermiso: texto (>=2 letras) sobre nombre/slug.
     *  - $estadoFiltroPermisos: todos | no-asignados | directos.
     */
    #[Computed]
    public function permisosGruposUsuario(): array
    {
        if (!$this->permisosUsuarioId) {
            return [];
        }

        $busqueda = strtolower(trim($this->busquedaPermiso));
        $soloBusqueda = $busqueda !== '' && strlen($busqueda) >= 2;

        $mapa = collect($this->permisosUsuarioActuales)
            ->mapWithKeys(fn ($p) => [$p['id'] => $p['origen']]);

        $grupos = [];

        foreach ($this->permissionGroups as $grupo) {
            $items = [];

            foreach ($grupo['permissions'] as $perm) {
                if ($soloBusqueda
                    && !str_contains(strtolower($perm['name']), $busqueda)
                    && !str_contains(strtolower($perm['slug']), $busqueda)) {
                    continue;
                }

                $origen = $mapa[$perm['id']] ?? null;

                if ($this->estadoFiltroPermisos === 'no-asignados' && $origen !== null) {
                    continue;
                }

                if ($this->estadoFiltroPermisos === 'directos' && $origen !== 'directo') {
                    continue;
                }

                $items[] = [
                    'id' => $perm['id'],
                    'name' => $perm['name'],
                    'slug' => $perm['slug'],
                    'tiene' => (bool) $origen,
                    'origen' => $origen,
                ];
            }

            if (!empty($items)) {
                $grupos[] = ['name' => $grupo['name'], 'permissions' => $items];
            }
        }

        return $grupos;
    }

    /**
     * Conteo de permisos no asignados del usuario seleccionado (para la UI).
     */
    #[Computed]
    public function permisosNoAsignadosCount(): int
    {
        if (!$this->permisosUsuarioId) {
            return 0;
        }

        $idsTiene = collect($this->permisosUsuarioActuales)->pluck('id')->all();

        return Permission::whereNotIn('id', $idsTiene)->count();
    }

    /**
     * Otorgar un permiso DIRECTAMENTE al usuario (grant individual).
     * Aditivo: no modifica los permisos del rol.
     */
    public function agregarPermisoDirecto(int $permissionId): void
    {
        if (!$this->permisosUsuarioId) {
            return;
        }

        $user = User::findOrFail($this->permisosUsuarioId);
        $perm = Permission::findOrFail($permissionId);

        $yaExiste = $user->directPermissions()->where('permission_id', $permissionId)->exists();

        if (!$yaExiste) {
            $user->directPermissions()->attach($permissionId);

            Log::info('RolesPermisos: permiso directo otorgado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'permission' => $perm->slug,
                'admin_id' => Auth::id(),
            ]);

            session()->flash('success', "✅ Permiso \"{$perm->name}\" otorgado a {$user->name} (solo a este usuario).");
        }

        $this->busquedaPermiso = '';
        $this->cargarPermisosUsuario();
    }

    /**
     * Revocar un permiso directo del usuario.
     * Solo afecta permisos directos; los del rol se mantienen intactos.
     */
    public function quitarPermisoDirecto(int $permissionId): void
    {
        if (!$this->permisosUsuarioId) {
            return;
        }

        $user = User::findOrFail($this->permisosUsuarioId);
        $perm = Permission::findOrFail($permissionId);

        $user->directPermissions()->detach($permissionId);

        Log::info('RolesPermisos: permiso directo revocado', [
            'user_id' => $user->id,
            'email' => $user->email,
            'permission' => $perm->slug,
            'admin_id' => Auth::id(),
        ]);

        session()->flash('success', "✅ Permiso \"{$perm->name}\" removido de {$user->name}.");
        $this->cargarPermisosUsuario();
    }

    protected function loadRolesAndPermissions(): void
    {
        $this->errorMessage = null;

        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $this->roles = $roles->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
        ])->toArray();

        $this->permissions = $permissions->map(fn ($permission) => [
            'id' => $permission->id,
            'name' => $permission->name,
            'slug' => $permission->slug,
            'description' => $permission->description,
        ])->toArray();

        // Agrupar permisos por concepto/vista
        $groupMap = [
            'Vistas del sistema' => ['view-tdr-repository', 'view-configuracion', 'view-buscador-publico', 'view-cuentas', 'view-prueba-endpoints', 'view-configuracion-alertas', 'view-mis-procesos'],
            'Configurar alertas' => ['add-telegram-subscription', 'add-whatsapp-subscription', 'add-email-subscription', 'manage-subscriptions'],
            'TDR y procesos' => ['import-tdr', 'analyze-tdr', 'follow-contracts', 'cotizar-seace', 'create-proforma',
                'view-buscador-mayores', 'view-detalle-mayores', 'download-tdr-mayores',
                'follow-mayores', 'analyze-tdr-mayores', 'detect-direccionamiento-mayores',
                'create-proforma-mayores', 'view-partes-mayores', 'export-mayores', 'alerta-adjudicaciones',
                'view-vigilancia-adjudicaciones'],
            'Administración' => ['manage-roles-permissions', 'view-consumo-ia', 'view-analytics', 'view-monitoreo-sistema'],
        ];

        $grouped = [];
        $assigned = [];

        foreach ($groupMap as $groupName => $slugs) {
            $items = [];
            foreach ($this->permissions as $perm) {
                if (in_array($perm['slug'], $slugs, true)) {
                    $items[] = $perm;
                    $assigned[] = $perm['slug'];
                }
            }
            if (!empty($items)) {
                $grouped[] = ['name' => $groupName, 'permissions' => $items];
            }
        }

        // Permisos no agrupados
        $unassigned = array_filter($this->permissions, fn ($p) => !in_array($p['slug'], $assigned, true));
        if (!empty($unassigned)) {
            $grouped[] = ['name' => 'Otros', 'permissions' => array_values($unassigned)];
        }

        $this->permissionGroups = $grouped;

        $this->rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->id => $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->values()->all()];
        })->toArray();
    }

    protected function syncUserRoles($users): void
    {
        foreach ($users as $user) {
            if (!array_key_exists($user->id, $this->userRoles)) {
                $this->userRoles[$user->id] = $user->roles->first()?->id;
            }
        }
    }

    protected function isLastAdminChange(int $userId, int $newRoleId): bool
    {
        $adminRole = Role::where('slug', 'admin')->first();
        if (!$adminRole) {
            return false;
        }

        $isRemovingAdmin = $this->userRoles[$userId] === $adminRole->id && $newRoleId !== $adminRole->id;
        if (!$isRemovingAdmin) {
            return false;
        }

        $adminCount = User::whereHas('roles', fn ($q) => $q->where('roles.id', $adminRole->id))->count();
        return $adminCount <= 1;
    }

    public function darDeBaja(int $userId): void
    {
        // No puede darse de baja a sí mismo
        if ($userId === Auth::id()) {
            $this->errorMessage = 'No puedes darte de baja a ti mismo.';
            return;
        }

        $user = User::findOrFail($userId);

        // Proteger al último administrador
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $user->hasRole('admin')) {
            $adminCount = User::whereHas('roles', fn ($q) => $q->where('roles.id', $adminRole->id))->count();
            if ($adminCount <= 1) {
                $this->errorMessage = 'No puedes dar de baja al único administrador del sistema.';
                return;
            }
        }

        $user->delete(); // Soft delete — preserva datos históricos

        session()->flash('success', "✅ Usuario \"{$user->name}\" dado de baja correctamente.");
        $this->loadRolesAndPermissions();
    }
}
