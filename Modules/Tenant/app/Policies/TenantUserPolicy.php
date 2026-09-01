<?php

namespace Modules\Tenant\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\TenantUser;

class TenantUserPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Summary of viewAny
     * @param TenantUser $user
     * @return bool
     */
    public function viewAny(TenantUser $user): bool
    {
        return $user->hasPermissionTo(TenantPermission::TenantViewAnyUsers->value);
    }

    /**
     * Summary of view
     * @param TenantUser $user
     * @param TenantUser $model
     * @return bool
     */
    public function view(TenantUser $user, TenantUser $model): bool
    {
        return $user->hasPermissionTo(TenantPermission::TenantViewUser->value) && $model->id === $user->id;
    }
    /**
     * Summary of create
     * @param TenantUser $user
     * @return bool
     */
    public function create(TenantUser $user): bool
    {
        return $user->hasPermissionTo(TenantPermission::TenantCreateUser->value);
    }

    /**
     * Summary of update
     * @param TenantUser $user
     * @param TenantUser $model
     */
    public function update(TenantUser $user, TenantUser $model): bool
    {
        return $user->hasPermissionTo(TenantPermission::TenantUpdateUser->value);
    }

    /**
     * Summary of delete
     * @param TenantUser $user
     * @param TenantUser $model
     */
    public  function delete(TenantUser $user, TenantUser $model)
    {
        return $user->hasPermissionTo(TenantPermission::TenantDeleteUser->value);
    }
}
