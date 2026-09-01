<?php

namespace Modules\Central\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Policies\BasePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Central\Models\Subscription;

class SubscriptionPolicy extends BasePolicy
{
    /**
     * Summary of viewAny
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnySubscriptions->value);
    }

    /**
     * Summary of view
     * @param User $user
     *
     */
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::ViewSubscriptions->value) &&
            $user->id === $subscription->company->owner_id;
    }
    /**
     * Summary of create
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Summary of update
     * @param User $user
     * @param Subscription $subscription
     */
    public function update(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::UpdateSubscriptions->value) &&
            $user->id === $subscription->company->owner_id &&
            $subscription->status === SubscriptionStatus::PENDING->value;
    }

    /**
     * Summary of delete
     * @param User $user
     * @param Subscription $subscription
     * @return bool
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteSubscriptions->value) &&
            $user->id === $subscription->company->owner_id &&
            $subscription->status === SubscriptionStatus::PENDING->value;
    }

    /**
     * Summary of forceDelete
     * @param User $user
     * @param Subscription $subscription
     * @return bool
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteSubscriptions->value);
    }

    /**
     * Summary of restore
     * @param User $user
     * @param Subscription $subscription
     * @return bool
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreSubscriptions->value);
    }

    /**
     * Summary of getAllTrashed
     * @param User $user
     * @return bool
     */
    public function getAllTrashed(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnySubscriptions->value);
    }

    /**
     * Summary of getTrashed
     * @param User $user
     * @param Subscription $subscription
     * @return bool
     */
    public function getTrashed(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedSubscriptions->value);
    }

    /**
     * Summary of forceDeleteAll
     * @param User $user
     * @return bool
     */
    public function forceDeleteAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteAllSubscriptions->value);
    }

    /**
     * Summary of restoreAll
     * @param User $user
     * @return bool
     */
    public function restoreAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAllSubscriptions->value);
    }

    public function reNew(User $user, Subscription $subscription): bool
    {
        return $user->can(PermissionManagementPermissions::ReNewSubscriptions->value) &&
            $user->id === $subscription->company->owner_id;
    }
}
