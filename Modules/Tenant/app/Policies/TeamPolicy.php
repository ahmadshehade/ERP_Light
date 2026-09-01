<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Team;

class TeamPolicy extends TenantBasePolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user hasPermissionTo view any models.
     */
    public function viewAny(User $user)
    {
        $tenantUser = $user->tenantUsers()->where('user_id', $user->id)->first();
        return $tenantUser->hasRole([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value
        ]);
    }

    /**
     * Determine whether the user hasPermissionTo view the model.
     */
    public function view(User $user, Team $team)
    {
        $tenantUser = $user->tenantUsers()->where('user_id', $user->id)->first();
        return $tenantUser->hasRole([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value
        ]);
    }

    /**
     * Determine whether the user hasPermissionTo create models.
     */
    public function create(User $user)
    {
        $tenantUser = $user->tenantUsers()->where('user_id', $user->id)->first();
        return $tenantUser->hasRole([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value
        ]);
    }

    /**
     * Determine whether the user hasPermissionTo update the model.
     */
    public function update(User $user, Team $team)
    {
        $tenantUser = $user->tenantUsers()->where('user_id', $user->id)->first();
        return $tenantUser->hasRole([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value
        ]);
    }

    /**
     * Determine whether the user hasPermissionTo delete the model.
     */
    public function delete(User $user, Team $team)
    {
        $tenantUser = $user->tenantUsers()->where('user_id', $user->id)->first();
        return $tenantUser->hasRole([
            TenantRoles::Owner->value,
            TenantRoles::Manager->value
        ]);
    }
}
