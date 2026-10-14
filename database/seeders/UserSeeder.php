<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin Default
        User::create([
            'name'        => 'Administrator',
            'email'       => 'admin@keuangan.com',
            'password'    => Hash::make('admin123'),
            'role'        => 'admin',
            'base_salary' => 10000000,
        ]);

        // Akuntan Default
        User::create([
            'name'        => 'Akuntan Utama',
            'email'       => 'akuntan@keuangan.com',
            'password'    => Hash::make('akuntan123'),
            'role'        => 'akuntan',
            'base_salary' => 8000000,
        ]);
    }
}
