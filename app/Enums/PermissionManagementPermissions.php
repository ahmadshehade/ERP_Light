<?php

namespace App\Enums;

enum PermissionManagementPermissions: string
{
    // Read
    case GetAllPermissions = 'get_all_permissions';
    case GetPermission = 'get_permission';

        // Role Management
    case GivePermissionToRole = 'give_permission_to_role';
    case RevokePermissionFromRole = 'revoke_permission_from_role';

        // User Management
    case GivePermissionToUser = 'give_permission_to_user';
    case RevokePermissionFromUser = 'revoke_permission_from_user';



        //users
    case ViewAnyUsers = 'view_any_users';
    case ViewUser = 'view_user';
    case CreateUser = 'create_user';
    case UpdateUser = 'update_user';
    case DeleteUser = 'delete_user';
    case RestoreUser = 'restore_user';
    case ForceDeleteUser = 'force_delete_user';
    case ViewTrashedUsers = 'view_trashed_users';
    case RestoreAnyUsers = 'restore_any_users';
    case ForceDeleteAnyUsers = 'force_delete_any_users';

        // Profile

    case CreateProfile = 'create_profile';
    case UpdateProfile = 'update_profile';
    case DeleteProfile = 'delete_profile';
    case ViewProfile = 'view_profile';
    case ViewProfileForDeleteUsers = 'view_profile_for_delete_users';


        // SubscriptionPlan
    case ViewAnySubscriptionPlan = 'view_any_subscription_plan';
    case ViewSubscriptionPlan = 'view_subscription_plan';
    case CreateSubscriptionPlan = 'create_subscription_plan';
    case UpdateSubscriptionPlan = 'update_subscription_plan';
    case DeleteSubscriptionPlan = 'delete_subscription_plan';
    case RestoreSubscriptionPlan = 'restore_subscription_plan';
    case ForceDeleteSubscriptionPlan = 'force_delete_subscription_plan';
    case DeleteAllSubscriptionPlan = 'delete_all_subscription_plan';
    case RestoreAllSubscriptionPlan = 'restore_all_subscription_plan';
    case ViewTrashedSubscriptionPlan = 'view_trashed_subscription_plan';
    case ViewAnyTrashedSubscriptionPlan = 'view_any_trashed_subscription_plan';


        //Subscription Prices
    case ViewAnySubscriptionPrices = 'view_any_subscription_prices';
    case ViewSubscriptionPrices = 'view_subscription_prices';
    case CreateSubscriptionPrices = 'create_subscription_prices';
    case UpdateSubscriptionPrices = 'update_subscription_prices';
    case DeleteSubscriptionPrices = 'delete_subscription_prices';
    case RestoreSubscriptionPrices = 'restore_subscription_prices';
    case ForceDeleteSubscriptionPrices = 'force_delete_subscription_prices';
    case DeleteAllSubscriptionPrices = 'delete_all_subscription_prices';
    case RestoreAllSubscriptionPrices = 'restore_all_subscription_prices';
    case ViewTrashedSubscriptionPrices = 'view_trashed_subscription_prices';
    case ViewAnyTrashedSubscriptionPrices = 'view_any_trashed_subscription_prices';

        //Companies
    case ViewAnyCompanies = 'view_any_companies';
    case ViewCompanies = 'view_companies';
    case CreateCompanies = 'create_companies';
    case UpdateCompanies = 'update_companies';
    case DeleteCompanies = 'delete_companies';
    case RestoreCompanies = 'restore_companies';
    case ForceDeleteCompanies = 'force_delete_companies';
    case DeleteAllCompanies = 'delete_all_companies';
    case RestoreAllCompanies = 'restore_all_companies';
    case ViewTrashedCompanies = 'view_trashed_companies';
    case ViewAnyTrashedCompanies = 'view_any_trashed_companies';


        //Subscriptions
    case ViewAnySubscriptions = 'view_any_subscriptions';
    case ViewSubscriptions = 'view_subscriptions';
    case CreateSubscriptions = 'create_subscriptions';
    case UpdateSubscriptions = 'update_subscriptions';
    case DeleteSubscriptions = 'delete_subscriptions';
    case RestoreSubscriptions = 'restore_subscriptions';
    case ForceDeleteSubscriptions = 'force_delete_subscriptions';
    case DeleteAllSubscriptions = 'delete_all_subscriptions';
    case RestoreAllSubscriptions = 'restore_all_subscriptions';
    case ViewTrashedSubscriptions = 'view_trashed_subscriptions';
    case ViewAnyTrashedSubscriptions = 'view_any_trashed_subscriptions';
    case ReNewSubscriptions = 'renew_subscriptions';


        //Payments
    case ViewAnyPayments = 'view_any_payments';
    case ViewPayments = 'view_payments';
    case CreatePayments = 'create_payments';
    case UpdatePayments = 'update_payments';
    case DeletePayments = 'delete_payments';
    case RestorePayments = 'restore_payments';
    case ForceDeletePayments = 'force_delete_payments';
    case DeleteAllPayments = 'delete_all_payments';
    case RestoreAllPayments = 'restore_all_payments';
    case ViewTrashedPayments = 'view_trashed_payments';
    case ViewAnyTrashedPayments = 'view_any_trashed_payments';

    case PayPayments = 'pay_payments';
    case CancelPayments = 'cancel_payments';
    case RefundPayments = 'refund_payments';
    case RetryPayments = 'retry_payments';
    case FailPayments = 'fail_payments';
}
