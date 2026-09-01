<?php

namespace App\Policies;

use App\Enums\NameOfRoles;
use App\Models\User;

class BasePolicy
{

    /**
     * Determine whether the user can perform any action before checking specific permissions.
     */
    public  function before(User $user)
    {
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            return true;
        }
        return null;
    }
}
