<?php

namespace Modules\Central\Policies;

use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use App\Policies\BasePolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Central\Models\Payment;

class PaymentPolicy extends BasePolicy
{
    use HandlesAuthorization;

    /**
     *Summary of viewAny
     */
    public function viewAny(User $user)
    {
        return $user->can(PermissionManagementPermissions::ViewAnyPayments->value);
    }

    /**
     *
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::ViewPayments->value)
            && $payment->subscription->company->owner_id == $user->id;
    }

    /**
     *
     */
    public function update(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::UpdatePayments->value);
    }

    /**
     *
     */
    public function delete(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::DeletePayments->value);
    }

    /**
     *
     */
    public function restore(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::RestorePayments->value);
    }

    /**
     *
     */
    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::ForceDeletePayments->value);
    }

    /**
     *
     */
    public function getAnyTrashed(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::ViewAnyTrashedPayments->value);
    }

    /**
     *
     */
    public function getTrashed(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::ViewTrashedPayments->value);
    }
    /**
     *
     */
    public function restoreAll(User $user): bool
    {
        return $user->can(PermissionManagementPermissions::RestoreAllPayments->value);
    }
    /**
     *
     */
    public function forceDeleteAll(User $user)
    {
        return $user->can(PermissionManagementPermissions::ForceDeletePayments->value);
    }



    /**
     *
     */
    public function cancel(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::CancelPayments->value) && $user->id ===
            $payment->subscription->company->owner_id;
    }

    /**
     *
     */
    public function refund(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::RefundPayments->value);
    }

    /**
     *
     */
    public function retry(User $user, Payment $payment): bool
    {
        return $user->can(PermissionManagementPermissions::RetryPayments->value) && $user->id ===
            $payment->subscription->company->owner_id;
    }
}
