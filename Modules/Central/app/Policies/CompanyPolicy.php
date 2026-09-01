<?php

namespace Modules\Central\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use App\Policies\BasePolicy;
use Modules\Central\Models\Company;

class CompanyPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyCompanies->value);
    }

    /**
     * Determine whether the user can view the model.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Company $company): bool
    {
        return $user->can(PermissionManagementPermissions::ViewCompanies->value) && $user->id === $company->owner_id;
    }

    /**
     * Determine whether the user can create models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Company $company): bool
    {
        return $user->can(PermissionManagementPermissions::UpdateCompanies->value)
            || $user->id === $company->owner_id;
    }

    /**
     * Determine whether the user can delete the model.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Company $company): bool
    {

        return $user->can(PermissionManagementPermissions::DeleteCompanies->value)
            || $user->id === $company->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Company $company): bool
    {

        return $user->can(PermissionManagementPermissions::RestoreCompanies->value)
            || $user->id === $company->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteCompanies->value)
            || $user->id === $company->owner_id;
    }

    /**
     * Determine whether the user can delete any models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteAllCompanies->value);
    }

    /**
     * Determine whether the user can restore any trashed models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restoreAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAllCompanies->value);
    }

    /**
     * Determine whether the user can view trashed models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewTrashed(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedCompanies->value);
    }

    /**
     * Determine whether the user can view any trashed models.
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAnyTrashed(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyTrashedCompanies->value);
    }
}
