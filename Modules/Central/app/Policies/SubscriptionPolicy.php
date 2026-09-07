<?php

namespace Modules\Central\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use App\Policies\BasePolicy;
use Modules\Central\Models\Subscription;

class SubscriptionPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(
            PermissionManagementPermissions::ViewAnySubscriptions->value
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::ViewSubscriptions->value
        ) || $user->id === $subscription->company->owner_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->companies()->exists();
    }

    /**
     * Determine whether the user can update the model.
     *
     * Authorization only.
     * Subscription status is handled by the service layer.
     */
    public function update(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::UpdateSubscriptions->value
        ) || $user->id === $subscription->company->owner_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Authorization only.
     * Subscription status is handled by the service layer.
     */
    public function delete(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::DeleteSubscriptions->value
        ) || $user->id === $subscription->company->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::ForceDeleteSubscriptions->value
        );
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::RestoreSubscriptions->value
        );
    }

    /**
     * Determine whether the user can view all trashed models.
     */
    public function getAllTrashed(User $user): bool
    {
        return $user->can(
            PermissionManagementPermissions::ViewAnySubscriptions->value
        );
    }

    /**
     * Determine whether the user can view a trashed model.
     */
    public function getTrashed(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::ViewTrashedSubscriptions->value
        );
    }

    /**
     * Determine whether the user can permanently delete all trashed models.
     */
    public function forceDeleteAll(User $user): bool
    {
        return $user->can(
            PermissionManagementPermissions::DeleteAllSubscriptions->value
        );
    }

    /**
     * Determine whether the user can restore all trashed models.
     */
    public function restoreAll(User $user): bool
    {
        return $user->can(
            PermissionManagementPermissions::RestoreAllSubscriptions->value
        );
    }

    /**
     * Determine whether the user can renew the subscription.
     */
    public function reNew(
        User $user,
        Subscription $subscription
    ): bool {
        return $user->can(
            PermissionManagementPermissions::ReNewSubscriptions->value
        ) || $user->id === $subscription->company->owner_id;
    }
}
