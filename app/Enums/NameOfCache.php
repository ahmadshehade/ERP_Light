<?php

namespace App\Enums;

enum NameOfCache: string
{
    case USER = 'user';
    case PERMISSION = 'permission';
    case ROLE = 'role';
    case PROFILE = 'profile';
    case SUBSCRIPTION_PLAN = 'subscription_plan';
    case SUBSCRIPTION_PRICE = 'subscription_price';
    case COMPANY = 'company';
    case SUBSCRIPTION = 'subscription';
    case PAYMENT = 'payment';
    case TENANT_USER = 'tenant_user';
    case TENANT_ROLE = 'tenant_role';
    case TENANT_PERMISSION = 'tenant_permission';
    case TENANT_DEPARTMENT = 'tenant_department';
    case POSITION = 'position';
    case TEAM = 'team';
    case PROJECT = 'project';
    case TASK = 'task';
}
