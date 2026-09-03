<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Task;

class TaskPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;


    /**
     * Summary of viewAny
     * @method bool viewAny(User $user)
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyTask->value);
    }


    /**
     * Summary of view
     * @method bool view(User $user, Task $task)
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        if (! $tenantUser) {
            return false;
        }
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewTask->value);
    }

    /**
     * Summary of create
     * @method bool create(User $user)
     * @return bool
     */
    public function create(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(
            TenantPermission::TenantCreateTask->value
        );
    }

    /**
     * Summary of update
     * @method bool update(User $user, Task $task)
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdateTask->value);
    }

    /**
     * Summary of delete
     * @method bool delete(User $user, Task $task)
     * @return bool
     */
    public function delete(User $user, Task $task)
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeleteTask->value);
    }

    /**
     * Summary of restore
     * @method bool restore(User $user, Task $task)
     * @return bool
     */
    public function restore(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreTask->value);
    }

    /**
     * Summary of forceDelete
     * @method bool forceDelete(User $user, Task $task)
     * @return bool
     */
    public function forceDelete(User $user, Task $task)
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteTask->value);
    }

    /**
     * Summary of viewTrashed
     * @method bool viewTrashed(User $user, Task $task)
     * @return bool
     */
    public function viewTrashed(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetTrashedTasks->value);
    }

    /**
     * Summary of viewAllTrashed
     * @method bool viewAllTrashed(User $user)
     * @return bool
     */
    public function viewAllTrashed(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetAllTrashedTasks->value);
    }

    /**
     * Summary of restoreAll
     * @method bool restoreAll(User $user)
     * @return bool
     */
    public function restoreAll(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreAllTasks->value);
    }

    /**
     * Summary of forceDeleteAll
     * @method bool forceDeleteAll(User $user)
     * @return bool
     */
    public function forceDeleteAll(User $user): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteAllTasks->value);
    }

    /**
     * Summary of completeTask
     * @method bool completeTask(User $user, Task $task)
     * @return bool
     */
    public function completeTask(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCompleteTask->value);
    }

    /**
     * Summary of cancelTask
     * @method bool cancelTask(User $user, Task $task)
     * @return bool
     */
    public function cancelTask(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCancelTask->value);
    }

    /**
     * Summary of onHoldTask
     * @method bool onHoldTask(User $user, Task $task)
     * @return bool
     */
    public function onHoldTask(User $user, Task $task): bool
    {
        $tenantUser = $this->getTenantUser($user);
        return $tenantUser->hasPermissionTo(TenantPermission::TenantOnHoldTask->value);
    }
}
