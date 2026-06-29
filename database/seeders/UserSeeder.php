<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@heavenscent.id'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('super_admin');
    }
}
