<?php

namespace App\Services\Roles_and_Permissions;

use App\Enums\NameOfCache;
use App\Models\User;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    use ApplyFilters;
    /**
     * Generate cache key
     */
    private function genKey(User $user, array $data = []): string
    {
        $roles = $user->roles
            ->pluck('name')
            ->sort()
            ->implode('_');

        return $user->id . '_' . $roles . '_' . md5(json_encode($data));
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(array $data = [])
    {
        $user = Auth::user();

        $cacheKey = $this->genKey($user, $data);

        return Cache::tags([NameOfCache::PERMISSION->value])
            ->remember($cacheKey, now()->addHours(24), function () use ($data) {

                $query = Permission::query();

                if (!empty($data)) {

                    $this->filterData($query, $data);
                }

                return $query->get();
            });
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

        return $permission->load('roles');
    }

    /**
     * Give permission to user
     */
    public function giveToUser(Permission $permission, User $user)
    {
        $user->givePermissionTo($permission);

        $this->clearCache();

        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokeFromUser(Permission $permission, User $user)
    {
        $user->revokePermissionTo($permission);

        $this->clearCache();

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
