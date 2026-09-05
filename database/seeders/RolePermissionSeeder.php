<?php

namespace Database\Seeders;

use App\Enums\PermissionManagementPermissions;
use App\Support\RolePermissions;
use Illuminate\Database\Seeder;
use Modules\Tenant\Support\TenantPermissionManagement;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RolePermissions::get() as $role => $permissions) {
            $role = Role::findByName($role);

            if ($permissions === ['*']) {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
