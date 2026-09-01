<?php

namespace Database\Seeders;

use App\Enums\PermissionManagementPermissions;
use Illuminate\Database\Seeder;
use Modules\Tenant\Enum\TenantPermission as EnumTenantPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->delete();



        /*
        |--------------------------------------------------------------------------
        | Permission Management Permissions
        |--------------------------------------------------------------------------
        */
        foreach (PermissionManagementPermissions::cases()  as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (EnumTenantPermission::cases() as $permission) {

            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }
    }
}
