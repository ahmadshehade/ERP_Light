<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Department;


class DepartmentPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Summary of viewAny
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyDepartments->value);
    }
    /**
     * Summary of view
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     *
     */
    public function view(User $user, Department $department): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewDepartment->value);
    }

    /**
     * Summary of create
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function create(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCreateDepartment->value);
    }

    /**
     * Summary of update
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function update(User $user, Department $department)
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdateDepartment->value);
    }

    /**
     * Summary Of Delete
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function delete(User $user, Department $department): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeleteDepartment->value);
    }

    /**
     * Summary Of Restore
     *  @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function restore(User $user, Department $department): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreDepartment->value);
    }
    /**
     * Summary Of ForceDelete
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public  function forceDelete(User $user, Department $department): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteDepartment->value);
    }

    /**
     * Summary Of RestoreAll
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function restoreAll(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreAllDepartments->value);
    }
    /**
     * Summary Of ForceDeleteAll
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function forceDeleteAll(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteAllDepartments->value);
    }

    /**
     * Summary Of getTrashed
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function getTrashed(User $user, Department $department): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetTrashedDepartments->value);
    }

    /**
     * Summary Of getAllTrashed
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function getAllTrashed(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetAllTrashedDepartments->value);
    }
}
