<?php

namespace Modules\Tenant\Services\Roles_And_Permissions;



use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;
use App\Exceptions\BusinessRuleException;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleService
{
    use ApplyFilters;


    /**
     * Generate cache key
     * @pram array $data
     * @return string
     */
    private function genKey(array $data = [], int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . implode("_", $user->roles->pluck('name')->toArray()) : "";
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage
        ];
        return $userKey . "_" . NameOfCache::TENANT_ROLE->value . "_" . md5(json_encode($cacheData));
    }

    /**
     * Get all roles
     * @pram array $data
     * @return array
     */
    public function getAll(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey($data, $page, $perPage);

        $cached = Cache::tags([
            NameOfCache::TENANT_ROLE->value
        ])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($data, $perPage) {

                $roles = Role::query();

                if (!empty($data)) {
                    $this->filterData($roles, $data);
                }

                $this->sortData(
                    $roles,
                    $data,
                    ['name', 'created_at']
                );

                $paginator = $roles->paginate($perPage);

                return [
                    'ids' => $paginator
                        ->getCollection()
                        ->pluck('id')
                        ->all(),

                    'total' => $paginator->total(),

                    'per_page' => $paginator->perPage(),

                    'current_page' => $paginator->currentPage(),
                ];
            }
        );

        $roles = Role::query()
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($role) => array_search(
                    $role->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $roles,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
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
