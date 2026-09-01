<?php

namespace Database\Seeders\Tenant;


use Illuminate\Database\Seeder;
use Modules\Tenant\Enum\TenantPermission;
use Spatie\Permission\Models\Permission;

class TenantPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (TenantPermission::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }
    }
}
