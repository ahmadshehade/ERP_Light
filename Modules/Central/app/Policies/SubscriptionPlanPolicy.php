<?php

namespace Modules\Central\Policies;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use App\Policies\BasePolicy;

use Modules\Central\Models\SubscriptionPlan;

class SubscriptionPlanPolicy extends BasePolicy
{



    /**
     * Summary of viewAny
     * @param User $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function  viewAny(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnySubscriptionPlan->value);
    }

    /**
     * Summary of view
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function view(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::ViewSubscriptionPlan->value);
    }

    /**
     * Summary of create
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::CreateSubscriptionPlan->value);
    }

    /**
     * Summary of update
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public  function update(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::UpdateSubscriptionPlan->value);
    }

    /**
     * Summary of delete
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function delete(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteSubscriptionPlan->value);
    }

    /**
     * Summary of restore
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function restore(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreSubscriptionPlan->value);
    }
    /**
     * Summary of forceDelete
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function forceDelete(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeleteSubscriptionPlan->value);
    }

    /**
     * Summary of deleteAll
     * @param User $user
     * @return bool
     */
    public function forceDeleteAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::DeleteAllSubscriptionPlan->value);
    }

    /**
     * Summary of restoreAll
     * @param User $user
     * @return bool
     */
    public function restoreAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAllSubscriptionPlan->value);
    }

    /**
     * Summary of viewTrashedPlans
     * @param User $user
     *  @return bool
     */
    public function viewTrashedPlans(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyTrashedSubscriptionPlan->value);
    }


    /**
     * Summary of viewTrashedPlan
     * @param User $user
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function viewTrashedPlan(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedSubscriptionPlan->value);
    }
}
