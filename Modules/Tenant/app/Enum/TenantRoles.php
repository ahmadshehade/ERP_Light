<?php

namespace Modules\Tenant\Enum;

enum  TenantRoles: string
{

    case Guest = 'guest';
    case Owner = 'owner';
    case Manager = 'manager';
    case Employee = 'employee';
}
