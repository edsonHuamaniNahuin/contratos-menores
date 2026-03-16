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
    public array $rolePermissions = [];
    public array $userRoles = [];
    public ?string $errorMessage = null;
    public int $perPage = 12;

    public function mount(): void
    {
        $this->loadRolesAndPermissions();
    }

    public function guardarPermisos(int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        $permissionIds = $this->rolePermissions[$roleId] ?? [];

        $role->permissions()->sync($permissionIds);

        session()->flash('success', "✅ Permisos guardados para {$role->name}");
        $this->loadRolesAndPermissions();
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

        $this->rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->id => $role->permissions->pluck('id')->values()->all()];
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
