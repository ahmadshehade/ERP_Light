<?php

namespace Modules\Tenant\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Models\TenantUser;

class DepartmentPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Summary of viewAny
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function viewAny(TenantUser $tenantUser): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyDepartments->value);
    }
    /**
     * Summary of view
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     *
     */
    public function view(TenantUser $tenantUser, Department $department): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewDepartment->value);
    }

    /**
     * Summary of create
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function create(TenantUser $tenantUser): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCreateDepartment->value);
    }

    /**
     * Summary of update
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function update(TenantUser $tenantUser, Department $department)
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdateDepartment->value);
    }

    /**
     * Summary Of Delete
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function delete(TenantUser $tenantUser, Department $department): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeleteDepartment->value);
    }

    /**
     * Summary Of Restore
     *  @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function restore(TenantUser $tenantUser, Department $department): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreDepartment->value);
    }
    /**
     * Summary Of ForceDelete
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public  function forceDelete(TenantUser $user, Department $department): bool
    {
        return $user->hasPermissionTo(TenantPermission::TenantForceDeleteDepartment->value);
    }

    /**
     * Summary Of RestoreAll
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function restoreAll(TenantUser $tenantUser): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreAllDepartments->value);
    }
    /**
     * Summary Of ForceDeleteAll
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function forceDeleteAll(TenantUser $tenantUser): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteAllDepartments->value);
    }

    /**
     * Summary Of getTrashed
     * @prama TenantUser $tenantUser
     * @prama Department $department
     * @return bool
     */
    public function getTrashed(TenantUser $tenantUser, Department $department): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetTrashedDepartments->value);
    }

    /**
     * Summary Of getAllTrashed
     * @prama TenantUser $tenantUser
     * @return bool
     */
    public function getAllTrashed(TenantUser $tenantUser): bool
    {
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetAllTrashedDepartments->value);
    }
}
