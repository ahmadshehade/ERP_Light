<?php

namespace App\Support;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;

class RolePermissions
{
    public static function get(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Super Admin
            |--------------------------------------------------------------------------
            | Full system access
            |--------------------------------------------------------------------------
            */
            NameOfRoles::SuperAdmin->value => [
                '*',
            ],


            /*
            |--------------------------------------------------------------------------
            | Owner
            |--------------------------------------------------------------------------
            | Full tenant control except roles & permissions management
            |--------------------------------------------------------------------------
            */
            NameOfRoles::Owner->value => [


                // Users
                PermissionManagementPermissions::ViewAnyUsers->value,
                PermissionManagementPermissions::ViewUser->value,
                PermissionManagementPermissions::CreateUser->value,
                PermissionManagementPermissions::UpdateUser->value,
                PermissionManagementPermissions::DeleteUser->value,
                PermissionManagementPermissions::RestoreUser->value,
                PermissionManagementPermissions::ViewTrashedUsers->value,
                PermissionManagementPermissions::RestoreAnyUsers->value,
                // Profile
                PermissionManagementPermissions::CreateProfile->value,
                PermissionManagementPermissions::UpdateProfile->value,
                PermissionManagementPermissions::DeleteProfile->value,
                PermissionManagementPermissions::ViewProfile->value,
                PermissionManagementPermissions::ViewProfileForDeleteUsers->value,
                // Subscription Plans
                PermissionManagementPermissions::ViewAnySubscriptionPlan->value,
                PermissionManagementPermissions::ViewSubscriptionPlan->value,
                //Subscription Price
                PermissionManagementPermissions::ViewAnySubscriptionPrices->value,
                PermissionManagementPermissions::ViewSubscriptionPrices->value,
                //companies
                PermissionManagementPermissions::ViewAnyCompanies->value,
                PermissionManagementPermissions::ViewCompanies->value,
                PermissionManagementPermissions::CreateCompanies->value,
                PermissionManagementPermissions::UpdateCompanies->value,
                PermissionManagementPermissions::DeleteCompanies->value,

                //Subscriptions
                PermissionManagementPermissions::ViewSubscriptions->value,
                PermissionManagementPermissions::CreateSubscriptions->value,
                PermissionManagementPermissions::UpdateSubscriptions->value,
                PermissionManagementPermissions::DeleteSubscriptions->value,
                PermissionManagementPermissions::ViewAnySubscriptions->value,
                PermissionManagementPermissions::ReNewSubscriptions->value,

                //Payments
                PermissionManagementPermissions::ViewAnyPayments->value,
                PermissionManagementPermissions::ViewPayments->value,
                PermissionManagementPermissions::CreatePayments->value,
                PermissionManagementPermissions::CancelPayments->value,
                PermissionManagementPermissions::RetryPayments->value,





            ],


            /*
            |--------------------------------------------------------------------------
            | Manager
            |--------------------------------------------------------------------------
            | Operational management
            |--------------------------------------------------------------------------
            */



            /*
            |--------------------------------------------------------------------------
            | Guest
            |--------------------------------------------------------------------------
            | Read only
            |--------------------------------------------------------------------------
            */
            NameOfRoles::Guest->value => [

                PermissionManagementPermissions::ViewSubscriptionPlan->value,


                // Subscription Price

                PermissionManagementPermissions::ViewSubscriptionPrices->value,

                //company
                PermissionManagementPermissions::ViewAnyCompanies->value,
            ],

        ];
    }
}
