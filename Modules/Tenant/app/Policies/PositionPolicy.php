<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Models\TenantUser;

class PositionPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user hasPermissionTo view any models.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewAnyPositions->value);
    }

    /**
     * Determine whether the user hasPermissionTo view the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantViewPosition->value);
    }
    /**
     * Determine whether the user hasPermissionTo create models.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantCreatePosition->value);
    }

    /**
     * Determine whether the user hasPermissionTo update the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantUpdatePosition->value);
    }

    /**
     * Determine whether the user hasPermissionTo delete the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantDeletePosition->value);
    }

    /**
     * Determine whether the user hasPermissionTo restore the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestorePosition->value);
    }
    /**
     * Determine whether the user hasPermissionTo permanently delete the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeletePosition->value);
    }

    /**
     * Determine whether the user hasPermissionTo view trashed models.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public  function getTrashed(User $user, Position $position): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetTrashedPositions->value);
    }

    /**
     * Determine whether the user hasPermissionTo view trashed models.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function getAllTrashed(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantGetAllTrashedPositions->value);
    }

    /**
     * Determine whether the user hasPermissionTo restore the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restoreAll(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantRestoreAllPositions->value);
    }

    /**
     * Determine whether the user hasPermissionTo permanently delete the model.
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDeleteAll(User $user): bool
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        return $tenantUser->hasPermissionTo(TenantPermission::TenantForceDeleteAllPositions->value);
    }
}
