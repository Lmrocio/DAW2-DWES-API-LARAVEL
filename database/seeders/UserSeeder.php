<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@demo.local'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        $admin->assignRole('admin');

        $user = \App\Models\User::updateOrCreate(
            ['email' => 'user@demo.local'],
            ['name' => 'User', 'password' => bcrypt('password')]
        );

        $user->assignRole('user');
    }
}
