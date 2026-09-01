<?php

namespace Database\Seeders\Tenant;


use Illuminate\Database\Seeder;


class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantRoleSeeder::class,
            TenantPermissionSeeder::class,
            TenantRolePermissionSeeder::class
        ]);
    }
}
