<?php

namespace App\Services\Roles_and_Permissions;

use App\Enums\NameOfCache;
use App\Enums\NameOfRoles;
use App\Models\User;
use App\Traits\ApplyFilters;
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
    private function genKey(array $data = [])
    {
        $user = Auth::user();
        $userkey = $user->id . "_" . implode("_", $user->roles->pluck('name')->sort()->toArray()) . "_" . md5(json_encode($data));
        $cahceKey = $userkey . "_" . NameOfCache::ROLE->value;
        return $cahceKey;
    }

    /**
     * Get all roles with caching based on user and filters
     */
    public function getAll(array $data = [])
    {


        $userkey = $this->genKey($data);
        return Cache::tags([NameOfCache::ROLE->value])->remember($userkey, now()->addHours(24), function () use ($data) {
            $roles = Role::query();
            if (! empty($data)) {
                $this->filterData($roles, $data);
            }

            return $roles->paginate(15);
        });
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
