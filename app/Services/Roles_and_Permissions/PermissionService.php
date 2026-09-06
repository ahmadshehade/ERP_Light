<?php

namespace App\Services\Roles_and_Permissions;

use App\Enums\NameOfCache;
use App\Models\User;
use App\Traits\ApplyFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    use ApplyFilters;
    /**
     * Generate cache key
     */
    private function genKey(
        User $user,
        array $data = [],
        int $page = 1,
        int $perPage = 15
    ): string {
        $roles = $user->roles
            ->pluck('name')
            ->sort()
            ->implode('_');

        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage,
        ];

        return $user->id
            . '_'
            . $roles
            . '_'
            . NameOfCache::PERMISSION->value
            . '_'
            . md5(json_encode($cacheData));
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(array $data = [])
    {
        $user = Auth::user();

        $page = request()->integer('page', 1);
        $perPage = 15;

        $cacheKey = $this->genKey(
            $user,
            $data,
            $page,
            $perPage
        );

        $cached = Cache::tags([
            NameOfCache::PERMISSION->value
        ])->remember(
            $cacheKey,
            60,
            function () use ($data) {

                $query = Permission::query();

                if (!empty($data)) {
                    $this->filterData($query, $data);
                }

                $this->sortData(
                    $query,
                    $data,
                    [
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                );

                $paginator = $query->paginate(15);

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
        $user = Auth::user();
        activity()
            ->causedBy($user)
            ->withProperties([
                'permission' => $permission->name,
                'roles' => $permission->roles->pluck('name')->values()->all(),
            ])
            ->log('Permission roles synced');

        return $permission->load('roles');
    }

    /**
     * Give permission to user
     */
    public function giveToUser(Permission $permission, User $user)
    {
        $user->givePermissionTo($permission);
        $this->clearCache();
        $actor = Auth::user();
        if (!$actor instanceof User) {
            throw new RuntimeException('Authenticated user not found.', 404);
        }
        activity()
            ->causedBy($actor)
            ->withProperties([
                'permission' => $permission->name,
                'user' => $user->id,
            ])
            ->log('Permission given to user');

        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokeFromUser(Permission $permission, User $user)
    {
        $user->revokePermissionTo($permission);
        $this->clearCache();
        $actor = Auth::user();
        if (!$actor instanceof User) {
            throw new RuntimeException('Authenticated user not found.');
        }
        activity()
            ->causedBy($actor)
            ->withProperties([
                'permission' => $permission->name,
                'user' => $user->id,
            ])
            ->log('Permission revoked from user');

        return true;
    }

    /**
     * Clear cache
     */
    private function clearCache(): void
    {
        Cache::tags([NameOfCache::PERMISSION->value])->flush();
    }
}
