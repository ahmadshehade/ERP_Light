<?php

namespace Database\Seeders;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(

            [
                'email' => 'admin@admin.com',
            ],

            [
                'name' => 'Super Admin',
                'password' => bcrypt('P@ssw0rd123123'),
            ]

        );

        $admin->syncRoles([
            NameOfRoles::SuperAdmin->value
        ]);
    }
}
