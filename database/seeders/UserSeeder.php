<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrator Keuangan
        $admin = User::updateOrCreate(
            ['email' => 'admin@keuangan.com'],
            [
                'name' => 'Administrator Keuangan',
                'password' => bcrypt('admin123'),
                'role' => 'admin', // Sesuaikan dengan nama role di tabel roles
                'is_active' => true,
                'base_salary' => 5000000,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // 2. Staff Akuntansi
        $akuntan = User::updateOrCreate(
            ['email' => 'akuntan@keuangan.com'],
            [
                'name' => 'Staff Akuntansi',
                'password' => bcrypt('akuntan123'),
                'role' => 'akuntan', // Sesuaikan dengan nama role di tabel roles
                'is_active' => true,
                'base_salary' => 4000000,
                'email_verified_at' => now(),
            ]
        );
        $akuntan->syncRoles(['akuntan']);

        // 3. Staff Operasional
        $staff = User::updateOrCreate(
            ['email' => 'staff@keuangan.com'],
            [
                'name' => 'Staff Operasional',
                'password' => bcrypt('staff123'),
                'role' => 'staff',
                'is_active' => true,
                'base_salary' => 5000000,
                'email_verified_at' => now(),
            ]
        );
        $staff->syncRoles(['staff']);
    }
}
