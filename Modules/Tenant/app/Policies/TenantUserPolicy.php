<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\TenantUser;

class TenantUserPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Summary of viewAny
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        $tenatUser = $this->getTenantUser($user);
        return $tenatUser->hasPermissionTo(TenantPermission::TenantViewAnyUsers->value);
    }

    /**
     * Summary of view
     * @param User $user
     * @param TenantUser $model
     * @return bool
     */
    public function view(User $user, TenantUser $model): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewUser->value) && $model->id === $user->id;
    }
    /**
     * Summary of create
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        $tenantUser =   $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCreateUser->value);
    }

    /**
     * Summary of update
     * @param User $user
     * @param TenantUser $model
     */
    public function update(User $user, TenantUser $model): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdateUser->value);
    }

    /**
     * Summary of delete
     * @param User $user
     * @param TenantUser $model
     */
    public  function delete(User $user, TenantUser $model)
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeleteUser->value);
    }
}
