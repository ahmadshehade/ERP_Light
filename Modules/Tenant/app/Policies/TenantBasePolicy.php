<?php

namespace Modules\Tenant\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;


class TenantBasePolicy
{
    use HandlesAuthorization;


    /**
     * Summary Of before
     * @prama TenantUser $tenantUser
     * @return bool|null
     */
    public  function  before(User $user): bool|null
    {
        $tenantUser = TenantUser::query()
            ->where('user_id', $user->id)
            ->first();

        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            return true;
        }
        return null;
    }

    /**
     * Summary of getTenantUser
     * @param User $user
     * @return TenantUser
     */
    public function getTenantUser(User $user): TenantUser
    {
        return TenantUser::where('user_id', $user->id)->first();
    }
}
