<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@melon.com'],
            [
                'name'     => 'Administrator Melon',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]
        );

        // 2. Akun User / Pengamat
        User::updateOrCreate(
            ['email' => 'pengamat@melon.com'],
            [
                'name'     => 'Pengamat Lapangan',
                'password' => Hash::make('password123'),
                'role'     => 'user',
            ]
        );
    }
}
