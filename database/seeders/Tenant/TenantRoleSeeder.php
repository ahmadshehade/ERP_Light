<?php

namespace Database\Seeders\Tenant;


use Illuminate\Database\Seeder;
use Modules\Tenant\Enum\TenantRoles;
use Spatie\Permission\Models\Role;

class TenantRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TenantRoles::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }
    }
}
