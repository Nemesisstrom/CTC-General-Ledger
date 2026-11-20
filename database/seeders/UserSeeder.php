<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Branch::where('code', 'CBG-001')->first();

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
            'branch_id'         => $pusat?->id,
            'is_active'         => true,
            'base_salary'       => 5000000,
            'email_verified_at' => now(),
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
            'branch_id'         => $pusat?->id,
            'is_active'         => true,
            'base_salary'       => 4000000,
            'email_verified_at' => now(),
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
            'branch_id'         => $pusat?->id,
            'is_active'         => true,
            'base_salary'       => 5000000,
            'email_verified_at' => now(),
        ])->save();
        $staff->assignSystemRole('staff');
    }
}
