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
        $users = User::with('roles')->orderBy('name')->paginate($this->perPage);
        $this->syncUserRoles($users->getCollection());

        return view('livewire.roles-permisos', [
            'users' => $users,
        ]);
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
            'TDR y procesos' => ['import-tdr', 'analyze-tdr', 'follow-contracts', 'cotizar-seace'],
            'Administración' => ['manage-roles-permissions'],
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
}
