<?php

namespace Modules\Tenant\Services\Roles_And_Permissions;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Tenant\Models\TenantUser;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    use ApplyFilters;
    /**
     * Generate cache key
     */
    private function genKey(array $data = [], int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user
            ? $user->id . implode('_', $user->roles->pluck('name')->toArray())
            : '';
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_Page' => $perPage
        ];
        return $userKey . "_" . NameOfCache::TENANT_PERMISSION->value . "_" . md5(json_encode($cacheData));
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey($data, $page, $perPage);

        $cached = Cache::tags([
            NameOfCache::TENANT_PERMISSION->value
        ])->remember(
            $cacheKey,
            60,
            function () use ($data, $perPage) {

                $query = Permission::query();

                if (!empty($data)) {
                    $this->filterData($query, $data);
                }

                $this->sortData(
                    $query,
                    $data,
                    ['name', 'created_at']
                );

                $paginator = $query->paginate($perPage);

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

        $permissions = Permission::query()
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($permission) => array_search(
                    $permission->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $permissions,
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
     * Get permission with roles
     */
    public function getPermission(Permission $permission)
    {
        return $permission->load('roles');
    }

    /**
     * Sync roles to permission
     */
    public function syncRoles(Permission $permission, array $roles)
    {

        $permission->syncRoles($roles);

        $this->clearCache();
        activity()
            ->performedOn($permission)
            ->withProperties([
                'roles' => $roles,
            ])
            ->log('Permission roles synced');

        return $permission->load('roles');
    }

    /**
     * Give permission to user
     */
    public function giveToUser(Permission $permission, TenantUser $user)
    {
        $user->givePermissionTo($permission);
        $this->clearCache();
        activity()
            ->performedOn($user)
            ->withProperties([
                'permission' => $permission->name,
                'permission_id' => $permission->id,
            ])
            ->log('Permission granted to user');

        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokeFromUser(Permission $permission, TenantUser $user)
    {
        $user->revokePermissionTo($permission);
        $this->clearCache();
        activity()
            ->performedOn($user)
            ->withProperties([
                'permission' => $permission->name,
                'permission_id' => $permission->id,
            ])
            ->log('Permission revoked from user');

        return true;
    }

    /**
     * Clear cache
     */
    private function clearCache(): void
    {
        Cache::tags([NameOfCache::TENANT_PERMISSION->value])->flush();
    }


    /**
     * Get user permissions
     *
     */
    public function getUserPermissions(TenantUser $user)
    {
        return $user->permissions;
    }
}
