<?php

namespace App\Services\Roles_and_Permissions;

use App\Enums\NameOfCache;
use App\Enums\NameOfRoles;
use App\Models\User;
use App\Traits\ApplyFilters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Spatie\Permission\Models\Role;

class RoleService
{

    use ApplyFilters;



    /**
     * Generate a unique cache key based on the user and provided data
     */
    private function genKey(
        array $data = [],
        int $page = 1,
        int $perPage = 15
    ): string {
        $user = Auth::user();

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
            . NameOfCache::ROLE->value
            . '_'
            . md5(json_encode($cacheData));
    }

    /**
     * Get all roles with caching based on user and filters
     */
    public function getAll(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = 15;

        $cacheKey = $this->genKey(
            $data,
            $page,
            $perPage
        );

        $cached = Cache::tags([
            NameOfCache::ROLE->value
        ])->remember(
            $cacheKey,
            60,
            function () use ($data, $perPage) {

                $roles = Role::query();

                if (!empty($data)) {
                    $this->filterData($roles, $data);
                }

                $this->sortData(
                    $roles,
                    $data,
                    ['name', 'created_at', 'updated_at']
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
     * Get a specific role
     */
    public function getRole(Role $role)
    {
        return $role->load('permissions');
    }

    /**
     * Assign a role to a user
     */
    public function assignRoleToUser(User $user, Role $role): bool
    {
        $user->assignRole($role);
        $this->clearCache();
        activity()
            ->causedBy($this->authenticatedUser())
            ->withProperties([
                'role' => $role->name,
                'user' => $user->id,
            ])
            ->log('Role assigned to user');

        return true;
    }

    /**
     * Remove a role from a user
     */
    public function removeRoleFromUser(User $user, Role $role): bool
    {
        $user->removeRole($role);
        $this->clearCache();
        activity()
            ->causedBy($this->authenticatedUser())
            ->withProperties([
                'role' => $role->name,
                'user' => $user->id,
            ])
            ->log('Role removed from user');
        return true;
    }

    /**
     * Sync roles to a user
     */
    public function syncRolesToUser(User $user, array $roles)
    {
        $roles = collect($roles);
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            $roles->push(NameOfRoles::SuperAdmin->value);
        }
        $user->syncRoles($roles);
        $this->clearCache();
        activity()
            ->causedBy($this->authenticatedUser())
            ->withProperties([
                'roles' => $roles->all(),
                'user' => $user->id,
            ])
            ->log('Roles synced for user');

        return true;
    }
    /**
     * Get roles for a specific user
     */
    public function getRolesForUser(User $user)
    {
        return $user->roles;
    }
    /**
     * Clear the cache for roles
     */
    private function clearCache()
    {
        Cache::tags([NameOfCache::ROLE->value])->flush();
    }

    /**
     * Get the authenticated user
     */
    private function authenticatedUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw new RuntimeException('Authenticated user not found.');
        }

        return $user;
    }
}
