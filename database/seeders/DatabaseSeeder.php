<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\ChartOfAccountSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Buat Role dasar
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $akuntanRole = Role::firstOrCreate(['name' => 'akuntan', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // 3. Buat Akun Administrator Default
        $admin = User::firstOrCreate(
            ['email' => 'admin@keuangan.com'],
            [
                'name' => 'Administrator Keuangan',
                'password' => Hash::make('admin123'),
                'base_salary' => 5000000,
            ]
        );
        $admin->assignRole($adminRole);

        // 4. Buat Akun Staff / Akuntan Default
        $staff = User::firstOrCreate(
            ['email' => 'akuntan@keuangan.com'],
            [
                'name' => 'Staff Akuntansi',
                'password' => Hash::make('akuntan123'),
                'base_salary' => 4000000,
            ]
        );
        $staff->assignRole($akuntanRole);

        // 5. Panggil Seeder Chart of Accounts yang sudah dibuat sebelumnya
        $this->call([
            ChartOfAccountSeeder::class,
        ]);
    }
}
