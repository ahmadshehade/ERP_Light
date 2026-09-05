<?php

namespace Modules\Tenant\Services\Roles_And_Permissions;



use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;
use App\Exceptions\BusinessRuleException;
use Spatie\Permission\Models\Role;

class RoleService
{
    use ApplyFilters;


    /**
     * Generate cache key
     * @pram array $data
     * @return string
     */
    private function genKey(array $data = []): string
    {
        $user = Auth::user();
        return implode('_', [
            tenant('id'),
            $user->id,
            NameOfCache::TENANT_ROLE->value,
            md5(json_encode($data)),
        ]);
    }

    /**
     * Get all roles
     * @pram array $data
     * @return array
     */
    public function getAll(array $data = [])
    {
        $cacheKey = $this->genKey($data);

        return Cache::tags([
            NameOfCache::TENANT_ROLE->value
        ])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($data) {
                $roles = Role::query();

                if (!empty($data)) {
                    $this->filterData($roles, $data);
                }
                return $roles->get();
            }
        );
    }

    /**
     * Get role with permissions
     * @pram Role $role
     * @return Role
     */
    public function getRole(Role $role)
    {
        return $role->load('permissions');
    }

    /**
     * Assign role to user
     * @pram TenantUser $tenantUser
     * @pram Role $role
     * @return bool
     */
    public function assignRoleToUser(
        TenantUser $tenantUser,
        Role $role
    ): bool {
        if ($role->name === TenantRoles::Owner->value) {
            throw new BusinessRuleException(
                'Cannot assign owner role using role management',
                409
            );
        }
        $tenantUser->assignRole($role);
        $this->clearCache();
        activity()
            ->performedOn($tenantUser)
            ->withProperties([
                'role' => $role->name,
                'role_id' => $role->id,
            ])
            ->log('Role assigned to user');
        return true;
    }

    /**
     * Remove role from user
     * @pram TenantUser $tenantUser
     * @pram Role $role
     * @return bool
     */
    public function removeRoleFromUser(
        TenantUser $tenantUser,
        Role $role
    ): bool {
        if (
            $role->name === TenantRoles::Owner->value &&
            $tenantUser->hasRole(TenantRoles::Owner->value)
        ) {
            throw new BusinessRuleException(
                'Cannot remove owner role',
                403
            );
        }
        $tenantUser->removeRole($role);
        $this->clearCache();
        activity()
            ->performedOn($tenantUser)
            ->withProperties([
                'role' => $role->name,
                'role_id' => $role->id,
            ])
            ->log('Role removed from user');
        return true;
    }

    /**
     * Sync roles to user
     * @pram TenantUser $tenantUser
     * @pram array $roles
     * @return bool
     */
    public function syncRolesToUser(
        TenantUser $tenantUser,
        array $roles
    ): bool {
        $roles = collect($roles);

        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            $roles->push(TenantRoles::Owner->value);
        }
        $tenantUser->syncRoles(
            $roles->unique()->values()->all()
        );
        $this->clearCache();
        activity()
            ->performedOn($tenantUser)
            ->withProperties([
                'roles' => $roles,
            ])
            ->log('User roles synced');
        return true;
    }

    /**
     * Get roles for user
     * @pram TenantUser $tenantUser
     * @return array
     */
    public function getRolesForUser(TenantUser $tenantUser)
    {
        return $tenantUser->roles;
    }

    private function clearCache(): void
    {
        Cache::tags([
            NameOfCache::TENANT_ROLE->value
        ])->flush();
    }
}
