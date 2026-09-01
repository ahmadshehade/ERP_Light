<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->delete();
        $users = [
            [
                'name' => 'Ahmad Shehade',
                'email' => 'eng.ahmad.shehade@gmail.com',
                'password' => bcrypt('P@ssw0rd123123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Haider Saeed',
                'email' => 'haidarasaeed954@gmail.com',
                'password' => bcrypt('P@ssw0rd123123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Saeed Mahmoud',
                'email' => 'alibenance76@gmail.com',
                'password' => bcrypt('P@ssw0rd123123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Aihamd Shehade',
                'email' => 'cache.aiham.shehade@gmail.com',
                'password' => bcrypt('P@ssw0rd123123'),
                'email_verified_at' => now(),
            ]

        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
