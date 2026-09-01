<?php

namespace App\Enums;

enum  NameOfRoles: string
{
    case SuperAdmin = 'admin';
    case Guest = 'guest';
    case Owner = 'owner';
    case Manager = 'manager';
    case Employee = 'employee';
}
