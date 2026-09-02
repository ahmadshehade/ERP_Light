<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\TenantUser;

class ProjectPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user hasPermissionTo view any models.
     */
    public function viewAny(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyProjects->value);
    }

    /**
     * Determine whether the user hasPermissionTo view the model.
     */
    public function view(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyProjects->value);
    }

    /**
     * Determine whether the user hasPermissionTo create models.
     */
    public function create(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCreateProject->value);
    }

    /**
     * Determine whether the user hasPermissionTo update the model.
     */
    public function update(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdateProject->value);
    }

    /**
     * Determine whether the user hasPermissionTo delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeleteProject->value);
    }

    /**
     * Determine whether the user hasPermissionTo restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreProject->value);
    }

    /**
     * Determine whether the user hasPermissionTo permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteProject->value);
    }

    /**
     * Determine whether the user hasPermissionTo get trashed.
     */
    public function getTrashed(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetTrashedProjects->value);
    }
    /**
     * Determine whether the user hasPermissionTo get all trashed.
     */
    public function getAllTrashed(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetAllTrashedProjects->value);
    }

    /**
     * Determine whether the user hasPermissionTo force delete all.
     */
    public function forceDeleteAll(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteAllProjects->value);
    }

    /**
     * Determine whether the user hasPermissionTo restore all.
     */
    public function restoreAll(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreAllProjects->value);
    }

    /**
     * Determine whether the user hasPermissionTo on hold.
     */
    public  function onHold(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantOnHoldProject->value) && $project->status == ProjectStatus::InProgress;
    }

    /**
     * Determine whether the user hasPermissionTo complete.
     */
    public  function complete(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCompleteProject->value) && $project->status == ProjectStatus::InProgress;
    }

    /**
     * Determine whether the user hasPermissionTo cancel.
     */
    public function resume(User $user, Project $project): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantResumeProject->value) && $project->status == ProjectStatus::OnHold;
    }

    /**
     * Determine whether the user hasPermissionTo cancel.
     */
    public function cancel(User $user, Project $project)
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCancelProject->value) && $project->status == ProjectStatus::InProgress;
    }
}
