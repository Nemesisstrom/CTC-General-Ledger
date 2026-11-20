<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrator Keuangan
        $admin = User::updateOrCreate(
            ['email' => 'admin@keuangan.com'],
            [
                'name'              => 'Administrator Keuangan',
                'password'          => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->forceFill([
            'is_active'   => true,
            'base_salary' => 5000000,
        ])->save();
        $admin->assignSystemRole('admin');

        // 2. Staff Akuntansi
        $akuntan = User::updateOrCreate(
            ['email' => 'akuntan@keuangan.com'],
            [
                'name'              => 'Staff Akuntansi',
                'password'          => bcrypt('akuntan123'),
                'email_verified_at' => now(),
            ]
        );
        $akuntan->forceFill([
            'is_active'   => true,
            'base_salary' => 4000000,
        ])->save();
        $akuntan->assignSystemRole('akuntan');

        // 3. Staff Operasional
        $staff = User::updateOrCreate(
            ['email' => 'staff@keuangan.com'],
            [
                'name'              => 'Staff Operasional',
                'password'          => bcrypt('staff123'),
                'email_verified_at' => now(),
            ]
        );
        $staff->forceFill([
            'is_active'   => true,
            'base_salary' => 5000000,
        ])->save();
        $staff->assignSystemRole('staff');
    }
}
