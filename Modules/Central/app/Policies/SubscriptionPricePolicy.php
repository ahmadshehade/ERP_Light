<?php

namespace Modules\Central\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use App\Policies\BasePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Central\Models\SubscriptionPrice;

class SubscriptionPricePolicy extends BasePolicy
{

    /**
     * Summary of viewAny
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnySubscriptionPrices->value);
    }

    /**
     * Summary of view
     * @param User $user
     * @return bool
     *
     */
    public function view(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewSubscriptionPrices->value);
    }

    /**
     * Summary of create
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::CreateSubscriptionPrices->value);
    }
    /**
     * Summary of update
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::UpdateSubscriptionPrices->value);
    }
    /**
     * Summary of delete
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteSubscriptionPrices->value);
    }
    /**
     * Summary of forceDelete
     * @param User $user
     * @return bool
     */
    public function forceDelete(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteSubscriptionPrices->value);
    }
    /**
     * Summary of restore
     * @param User $user
     * @return bool
     */
    public function restore(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreSubscriptionPrices->value);
    }
    /**
     * Summary of restoreAll
     * @param User $user
     * @return bool
     */
    public function restoreAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAllSubscriptionPrices->value);
    }
    /**
     * Summary of forceDeleteAll
     * @param User $user
     * @return bool
     */
    public function forceDeleteAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteAllSubscriptionPrices->value);
    }
    /**
     * Summary of viewTrashed
     * @param User $user
     * @return bool
     */
    public function viewTrashed(User $user, SubscriptionPrice $subscriptionPrice): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedSubscriptionPrices->value);
    }

    /**
     * Summary of viewTrashedPlans
     * @param User $user
     * @return bool
     */
    public function viewAnyTrashedPrices(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyTrashedSubscriptionPrices->value);
    }
}
