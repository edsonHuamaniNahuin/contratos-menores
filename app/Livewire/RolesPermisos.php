<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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

        $user = User::findOrFail($userId);

        if ($this->isLastAdminChange($userId, (int) $roleId)) {
            $this->errorMessage = 'No puedes dejar el sistema sin al menos un administrador.';
            return;
        }

        $user->roles()->sync([$roleId]);
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
