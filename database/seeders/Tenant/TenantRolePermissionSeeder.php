<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Modules\Tenant\Support\TenantPermissionManagement;
use Spatie\Permission\Models\Role;

class TenantRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (TenantPermissionManagement::get() as $role => $permissions) {
            $role = Role::findByName($role, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
