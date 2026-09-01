<?php

namespace App\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Enums\UserPermissions;
use App\Models\User;

class UserPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyUsers->value);
    }

    /**
     * Determine whether the user can view the specified user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionManagementPermissions::ViewUser->value)
            || $user->is($model);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::CreateUser->value);
    }

    /**
     * Determine whether the user can update the specified user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionManagementPermissions::UpdateUser->value)
            || $user->is($model);
    }

    /**
     * Determine whether the user can delete the specified user.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteUser->value)
            && !$user->is($model);
    }

    /**
     * Determine whether the user can restore the specified user.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreUser->value);
    }

    /**
     * Determine whether the user can permanently delete the specified user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteUser->value);
    }

    /**
     * Determine whether the user can view trashed users.
     */
    public function viewTrashed(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedUsers->value);
    }

    /**
     * Determine whether the user can restore all trashed users.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAnyUsers->value);
    }

    /**
     * Determine whether the user can permanently delete all trashed users.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteAnyUsers->value);
    }
}
